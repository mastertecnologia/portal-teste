<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\Clientes\ClienteCorrelatedIds;
use App\Utility\VaultCrypto;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

if (!defined('C_RoleCliente')) {
	define('C_RoleCliente', 1);
}

/**
 * Ativos / CMDB ITSM.
 *
 * Gere o cadastro de ativos de TI (computadores, servidores, impressoras, switches, etc.)
 * vinculados a clientes. Linkado a chamados via tabela pivot ticket_assets.
 */
class AtivosController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Assets');
		$this->loadModel('Clientes');
		$this->loadModel('Users');
		$this->loadModel('Empresasusers');
		$this->loadModel('TicketAssets');
		try {
			$this->loadModel('Atividades');
		} catch (\Exception $e) {
			// opcional
		}
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Ativos de TI');
		$shellAction = $this->request->getParam('action');
		if (in_array($shellAction, ['index', 'view', 'add', 'edit'], true)) {
			$this->set('hideLayoutPageTitle', true);
		}
		// Cliente portal só pode ver os próprios; equipe gerencia tudo
		if ((int)$this->Auth->user('role') === C_RoleCliente && !in_array($this->request->getParam('action'), ['index', 'view'], true)) {
			$this->Flash->error('Você não possui permissão para realizar esta ação.');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}
	}

	/**
	 * Cliente do ID informado, restrito à empresa do usuário (mitiga IDOR).
	 *
	 * @param int|string|null $id
	 */
	protected function _findClienteForCurrentUser($id) {
		if ($id === null || $id === '') {
			return null;
		}
		$q = $this->Clientes->find()->where(['id' => (int)$id]);
		$this->Abac->applyToQuery($q, 'Clientes');

		return $q->first();
	}

	/**
	 * Ativo do ID informado, restrito à empresa do usuário.
	 *
	 * @param int|string|null $id
	 */
	protected function _findAssetForCurrentUser($id) {
		if ($id === null || $id === '') {
			return null;
		}
		$idempresa = (int)$this->Auth->user('idempresa');

		return $this->Assets->find()
			->contain(['Clientes'])
			->where(['Assets.id' => (int)$id, 'Assets.idempresa' => $idempresa])
			->first();
	}

	/**
	 * Listagem com filtros (cliente, tipo, status, busca textual).
	 */
	public function index() {
		$idempresa = (int)$this->Auth->user('idempresa');
		$idclienteFiltro = $this->request->getQuery('idcliente');
		$tipoFiltro = trim((string)$this->request->getQuery('tipo'));
		$statusFiltro = trim((string)$this->request->getQuery('status'));
		$busca = trim((string)$this->request->getQuery('q'));

		$q = $this->Assets->find()
			->contain(['Clientes' => ['fields' => ['id', 'razaosocial', 'nome', 'nomefantasia', 'tipo']]])
			->where(['Assets.idempresa' => $idempresa])
			->order(['Assets.id' => 'DESC']);

		if ($idclienteFiltro !== null && $idclienteFiltro !== '') {
			$q->where(['Assets.idcliente' => (int)$idclienteFiltro]);
		}
		if ($tipoFiltro !== '') {
			$q->where(['Assets.tipo' => $tipoFiltro]);
		}
		if ($statusFiltro !== '') {
			$q->where(['Assets.status_operacional' => $statusFiltro]);
		}
		if ($busca !== '') {
			$like = '%' . $busca . '%';
			$q->where([
				'OR' => [
					['Assets.descricao ILIKE' => $like],
					['Assets.identificador ILIKE' => $like],
					['Assets.numero_serie ILIKE' => $like],
					['Assets.hostname ILIKE' => $like],
					['Assets.codigo_qr ILIKE' => $like],
					['Assets.patrimonio ILIKE' => $like],
				],
			]);
		}

		$ativos = $q->limit(500)->toArray();

		// KPIs de cabeçalho
		$kpisQ = $this->Assets->find()
			->select([
				'total' => 'COUNT(*)',
				'em_uso' => 'SUM(CASE WHEN status_operacional = \'em_uso\' THEN 1 ELSE 0 END)',
				'estoque' => 'SUM(CASE WHEN status_operacional = \'estoque\' THEN 1 ELSE 0 END)',
				'manutencao' => 'SUM(CASE WHEN status_operacional = \'manutencao\' THEN 1 ELSE 0 END)',
				'descartado' => 'SUM(CASE WHEN status_operacional = \'descartado\' THEN 1 ELSE 0 END)',
			])
			->where(['idempresa' => $idempresa])
			->disableHydration();
		$kpis = $kpisQ->first();
		$kpis = is_array($kpis) ? $kpis : [];
		$kpis = [
			'total' => (int)($kpis['total'] ?? 0),
			'em_uso' => (int)($kpis['em_uso'] ?? 0),
			'estoque' => (int)($kpis['estoque'] ?? 0),
			'manutencao' => (int)($kpis['manutencao'] ?? 0),
			'descartado' => (int)($kpis['descartado'] ?? 0),
		];

		$clientesOpts = $this->Clientes->find('list', [
			'keyField' => 'id',
			'valueField' => function ($r) {
				return $r->razaosocial ?: ($r->nomefantasia ?: ($r->nome ?: ('Cliente #' . $r->id)));
			},
		])
			->order(['razaosocial' => 'ASC', 'nome' => 'ASC'])
			->toArray();
		$this->Abac->applyToQuery($this->Clientes->find(), 'Clientes');

		$this->set([
			'ativos' => $ativos,
			'kpis' => $kpis,
			'clientesOpts' => $clientesOpts,
			'tiposOpts' => $this->_tiposOptions(),
			'statusOpts' => $this->_statusOptions(),
			'filtros' => [
				'idcliente' => $idclienteFiltro,
				'tipo' => $tipoFiltro,
				'status' => $statusFiltro,
				'q' => $busca,
			],
		]);
		$this->set('title', 'Ativos de TI / CMDB');
		$this->set('topbarCurrentLabel', __('Ativos de TI / CMDB'));
	}

	public function view($id = null) {
		$asset = $this->_findAssetForCurrentUser($id);
		if (empty($asset)) {
			throw new NotFoundException(__('Ativo não encontrado.'));
		}
		$tickets = $this->TicketAssets->find()
			->contain(['Tickets'])
			->where(['TicketAssets.asset_id' => (int)$asset->id])
			->order(['TicketAssets.id' => 'DESC'])
			->limit(50)
			->toArray();

		$this->set([
			'asset' => $asset,
			'tickets' => $tickets,
		]);
		$this->set('title', 'Ativo: ' . ($asset->descricao ?: ('#' . $asset->id)));
		$this->set('topbarCurrentLabel', $asset->descricao ?: ('#' . $asset->id));
	}

	public function add() {
		$idempresa = (int)$this->Auth->user('idempresa');
		$idclientePrefill = (int)$this->request->getQuery('idcliente');

		$asset = $this->Assets->newEntity([
			'idempresa' => $idempresa,
			'idcliente' => $idclientePrefill ?: null,
			'ativo' => true,
			'status_operacional' => 'em_uso',
			'propriedade' => 'proprio',
		]);

		if ($this->request->is('post')) {
			$data = (array)$this->request->getData();
			$data['idempresa'] = $idempresa;
			$this->_normalizeAssetBrDates($data);
			$passwordEncryptionFailed = false;
			try {
				$this->_encryptAssetPassword($data, $idempresa);
			} catch (\Exception $e) {
				$this->Flash->error(__('Não foi possível criptografar a senha informada.'));
				$passwordEncryptionFailed = true;
			}
			$asset = $this->Assets->patchEntity($asset, $data);
			if (!$passwordEncryptionFailed && $this->Assets->save($asset)) {
				if (!empty($this->Atividades) && $this->Auth->user('id')) {
					$this->Atividades->registrar(
						(int)$this->Auth->user('id'),
						$this->request->getParam('controller'),
						$this->request->getParam('action'),
						(int)$asset->id
					);
				}
				$this->Flash->success(__('Ativo cadastrado com sucesso.'));
				return $this->redirect(['action' => 'edit', $asset->id]);
			}
			if (!$passwordEncryptionFailed) {
				$this->Flash->error(__('Não foi possível salvar o ativo. Verifique os campos.'));
			}
		}

		$this->set([
			'asset' => $asset,
			'clientesOpts' => $this->_clientesOptions(),
			'usersOpts' => $this->_usersOptions(),
			'tiposOpts' => $this->_tiposOptions(),
			'statusOpts' => $this->_statusOptions(),
			'propriedadeOpts' => $this->_propriedadeOptions(),
		]);
		$this->set('title', 'Cadastrar Ativo');
		$this->set('topbarCurrentLabel', __('Cadastrar ativo'));
	}

	public function edit($id = null) {
		$asset = $this->_findAssetForCurrentUser($id);
		if (empty($asset)) {
			throw new NotFoundException(__('Ativo não encontrado.'));
		}
		$idempresa = (int)$this->Auth->user('idempresa');

		if ($this->request->is(['patch', 'post', 'put'])) {
			$data = (array)$this->request->getData();
			unset($data['idempresa']);
			$this->_normalizeAssetBrDates($data);
			$passwordEncryptionFailed = false;
			try {
				$this->_encryptAssetPassword($data, $idempresa, true);
			} catch (\Exception $e) {
				$this->Flash->error(__('Não foi possível criptografar a senha informada.'));
				$passwordEncryptionFailed = true;
			}
			$asset = $this->Assets->patchEntity($asset, $data);
			if (!$passwordEncryptionFailed && $this->Assets->save($asset)) {
				if (!empty($this->Atividades) && $this->Auth->user('id')) {
					$this->Atividades->registrar(
						(int)$this->Auth->user('id'),
						$this->request->getParam('controller'),
						$this->request->getParam('action'),
						(int)$asset->id
					);
				}
				$this->Flash->success(__('Ativo atualizado com sucesso.'));
				return $this->redirect(['action' => 'edit', $asset->id]);
			}
			if (!$passwordEncryptionFailed) {
				$this->Flash->error(__('Não foi possível atualizar o ativo.'));
			}
		}

		// Histórico de chamados onde o CI apareceu
		$ticketsHist = $this->TicketAssets->find()
			->contain(['Tickets'])
			->where(['TicketAssets.asset_id' => (int)$asset->id, 'TicketAssets.idempresa' => $idempresa])
			->order(['TicketAssets.id' => 'DESC'])
			->limit(50)
			->toArray();

		$this->set([
			'asset' => $asset,
			'clientesOpts' => $this->_clientesOptions(),
			'usersOpts' => $this->_usersOptions(),
			'tiposOpts' => $this->_tiposOptions(),
			'statusOpts' => $this->_statusOptions(),
			'propriedadeOpts' => $this->_propriedadeOptions(),
			'ticketsHist' => $ticketsHist,
		]);
		$this->set('title', 'Editar Ativo: ' . ($asset->descricao ?: ('#' . $asset->id)));
		$this->set('topbarCurrentLabel', __('Editar ativo'));
	}

	/**
	 * Criptografa a senha de acesso do ativo antes do patchEntity.
	 *
	 * @param array<string, mixed> $data
	 */
	protected function _encryptAssetPassword(array &$data, int $idempresa, bool $preserveWhenEmpty = false): void {
		$plain = isset($data['senha']) && is_scalar($data['senha']) ? trim((string)$data['senha']) : '';
		if ($plain === '') {
			if ($preserveWhenEmpty) {
				unset($data['senha']);
			}

			return;
		}
		$data['senha'] = VaultCrypto::encrypt($plain, $idempresa);
	}

	/**
	 * Converte datas do formulário (dd/mm/aaaa) para Y-m-d antes do patchEntity.
	 *
	 * @param array<string, mixed> $data
	 */
	protected function _normalizeAssetBrDates(array &$data): void {
		foreach (['dt_aquisicao', 'dt_instalacao', 'dt_garantia_fim'] as $field) {
			if (!array_key_exists($field, $data)) {
				continue;
			}
			$v = $data[$field];
			if ($v === null || $v === '') {
				$data[$field] = null;
				continue;
			}
			if (!is_scalar($v)) {
				continue;
			}
			$v = trim((string)$v);
			if ($v === '') {
				$data[$field] = null;
				continue;
			}
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
				continue;
			}
			$dt = \DateTimeImmutable::createFromFormat('d/m/Y', $v);
			if ($dt !== false) {
				$data[$field] = $dt->format('Y-m-d');
			}
		}
	}

	public function delete($id = null) {
		$this->request->allowMethod(['post', 'delete']);
		$asset = $this->_findAssetForCurrentUser($id);
		if (empty($asset)) {
			throw new NotFoundException(__('Ativo não encontrado.'));
		}

		if ($this->Assets->delete($asset)) {
			if (!empty($this->Atividades) && $this->Auth->user('id')) {
				$this->Atividades->registrar(
					(int)$this->Auth->user('id'),
					$this->request->getParam('controller'),
					$this->request->getParam('action'),
					(int)$asset->id
				);
			}
			$this->Flash->success(__('Ativo excluído.'));
		} else {
			$this->Flash->error(__('Não foi possível excluir o ativo.'));
		}

		return $this->redirect(['action' => 'index']);
	}

	public function inativar($id = null) {
		return $this->_alternarAtivo($id, false, 'Ativo inativado.');
	}

	public function reativar($id = null) {
		return $this->_alternarAtivo($id, true, 'Ativo reativado.');
	}

	protected function _alternarAtivo($id, bool $ativo, string $okMsg) {
		$this->request->allowMethod(['post']);
		$asset = $this->_findAssetForCurrentUser($id);
		if (empty($asset)) {
			throw new NotFoundException(__('Ativo não encontrado.'));
		}
		$asset->ativo = $ativo;
		if ($this->Assets->save($asset)) {
			$this->Flash->success(__($okMsg));
		} else {
			$this->Flash->error(__('Não foi possível alterar o status.'));
		}

		return $this->redirect($this->request->referer() ?: ['action' => 'index']);
	}

	/**
	 * Etiqueta com QR. Stub HTML para impressão direta (PDF na fase 2).
	 */
	public function qr($id = null) {
		$asset = $this->_findAssetForCurrentUser($id);
		if (empty($asset)) {
			throw new NotFoundException(__('Ativo não encontrado.'));
		}
		$payload = $asset->codigo_qr ?: ('ATV-' . str_pad((string)$asset->id, 6, '0', STR_PAD_LEFT));
		$this->set([
			'asset' => $asset,
			'qrPayload' => $payload,
			'qrImageUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($payload),
		]);
		$this->set('title', 'Etiqueta — ' . ($asset->descricao ?: '#' . $asset->id));
		$this->set('disablePgmAppShellPremium', true);
		$this->viewBuilder()->setLayout('ajax');
	}

	/**
	 * JSON: ativos vinculados a um cliente (para autocomplete React).
	 * GET /ativos/api/by-cliente/:idcliente?q=&exclude_ticket=
	 */
	public function apiAssetsByCliente($idcliente = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$idempresa = (int)$this->Auth->user('idempresa');
		$idc = (int)$idcliente;
		if ($idc <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'idcliente_required'], 400);
		}
		$cliente = $this->_findClienteForCurrentUser($idc);
		if (empty($cliente)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$clienteIdsCorrel = ClienteCorrelatedIds::forEmpresaCliente($this->Clientes, $idempresa, $idc);
		$busca = trim((string)$this->request->getQuery('q'));
		$excludeTicket = (int)$this->request->getQuery('exclude_ticket');

		$q = $this->Assets->find()
			->where(['Assets.idempresa' => $idempresa, 'Assets.idcliente IN' => $clienteIdsCorrel])
			->order(['Assets.descricao' => 'ASC'])
			->limit(200);
		if ($busca !== '') {
			$like = '%' . $busca . '%';
			$q->where([
				'OR' => [
					['Assets.descricao ILIKE' => $like],
					['Assets.identificador ILIKE' => $like],
					['Assets.numero_serie ILIKE' => $like],
					['Assets.hostname ILIKE' => $like],
					['Assets.codigo_qr ILIKE' => $like],
				],
			]);
		}
		if ($excludeTicket > 0) {
			$jaVinc = $this->TicketAssets->find()
				->select(['asset_id'])
				->where(['ticket_id' => $excludeTicket])
				->disableHydration()
				->toArray();
			$ids = array_map(fn($r) => (int)$r['asset_id'], $jaVinc);
			if (!empty($ids)) {
				$q->where(['Assets.id NOT IN' => $ids]);
			}
		}

		$rows = [];
		foreach ($q as $a) {
			$rows[] = $this->_assetRowJson($a);
		}

		return $this->jsonResponse(['ok' => true, 'rows' => $rows]);
	}

	protected function _assetRowJson($a): array {
		return [
			'id' => (int)$a->id,
			'descricao' => (string)($a->descricao ?? ''),
			'tipo' => (string)($a->tipo ?? ''),
			'marca' => (string)($a->marca ?? ''),
			'modelo' => (string)($a->modelo ?? ''),
			'numero_serie' => (string)($a->numero_serie ?? ''),
			'identificador' => (string)($a->identificador ?? ''),
			'codigo_qr' => (string)($a->codigo_qr ?? ''),
			'hostname' => (string)($a->hostname ?? ''),
			'localizacao' => (string)($a->localizacao ?? ''),
			'status_operacional' => (string)($a->status_operacional ?? ''),
			'ativo' => (bool)($a->ativo ?? true),
		];
	}

	protected function _clientesOptions(): array {
		$opts = $this->Clientes->find()
			->select(['id', 'razaosocial', 'nomefantasia', 'nome', 'tipo'])
			->order(['razaosocial' => 'ASC', 'nome' => 'ASC'])
			->limit(2000)
			->toArray();
		$out = [];
		foreach ($opts as $c) {
			$out[$c->id] = $c->razaosocial ?: ($c->nomefantasia ?: ($c->nome ?: ('Cliente #' . $c->id)));
		}

		return $out;
	}

	protected function _usersOptions(): array {
		$idempresa = (int)$this->Auth->user('idempresa');
		// `users` não tem coluna `idempresa`; o vínculo é via tabela pivot `empresas_users` (Empresasusers).
		// Mesmo padrão usado em TicketsController para listar técnicos da empresa.
		$rows = $this->Empresasusers->find('all', ['contain' => ['Users']])
			->where([
				'Empresasusers.idempresa' => $idempresa,
				'Users.role' => 0,
				'Users.inativo' => 0,
			])
			->order(['Users.name' => 'ASC'])
			->limit(2000)
			->toArray();
		$out = ['' => '— Sem responsável —'];
		$seen = [];
		foreach ($rows as $r) {
			$u = $r->user ?? null;
			if (!$u || isset($seen[(int)$u->id])) {
				continue;
			}
			$seen[(int)$u->id] = true;
			$label = $u->name ?: ($u->username ?: ($u->email ?: ('User #' . $u->id)));
			$out[(int)$u->id] = $label;
		}

		return $out;
	}

	protected function _tiposOptions(): array {
		return [
			'' => '— Selecione —',
			'notebook' => 'Notebook',
			'desktop' => 'Desktop',
			'servidor' => 'Servidor',
			'impressora' => 'Impressora',
			'switch' => 'Switch',
			'roteador' => 'Roteador',
			'firewall' => 'Firewall',
			'access_point' => 'Access Point',
			'storage' => 'Storage / NAS',
			'monitor' => 'Monitor',
			'mobile' => 'Mobile / Tablet',
			'nobreak' => 'Nobreak',
			'camera' => 'Câmera',
			'periferico' => 'Periférico',
			'software' => 'Software / Licença',
			'outro' => 'Outro',
		];
	}

	protected function _statusOptions(): array {
		return [
			'em_uso' => 'Em uso',
			'estoque' => 'Em estoque',
			'manutencao' => 'Em manutenção',
			'reservado' => 'Reservado',
			'descartado' => 'Descartado',
			'perdido' => 'Perdido / Roubado',
		];
	}

	protected function _propriedadeOptions(): array {
		return [
			'proprio' => 'Próprio',
			'locado' => 'Locado',
			'comodato' => 'Comodato',
		];
	}
}
