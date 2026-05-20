<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Controller\Traits\PrototypeApiSecurityTrait;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Ordens de Serviço — protótipo (telas do mockup pgm_erp_completo.html, prefixo `pg-os-*`).
 *
 * Lado-a-lado com OrdensservicoController (legado). Rotas em /ordens-prototype/*.
 * Somente leitura nesta fase 2.
 */
class OrdensservicoPrototypeController extends AppController {

	use PrototypeApiSecurityTrait;

	public function initialize() {
		parent::initialize();
		$this->loadModel('Ordensservico');
		$this->loadModel('Clientes');
		$this->loadModel('Users');
	}

	/**
	 * Carrega catálogo de clientes ativos para o wizard de OS.
	 *
	 * @return array<int,string>
	 */
	protected function buildClientesOptions(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			foreach ($this->Clientes->find()
				->where(['Clientes.idempresa' => $empresa, 'Clientes.inativo' => 0])
				->order(['Clientes.nome' => 'ASC'])
				->limit(200)
				->all() as $c) {
				$out[(int)$c->get('id')] = (int)$c->get('tipo') === 2
					? (string)($c->get('razaosocial') ?? $c->get('nome'))
					: (string)$c->get('nome');
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	public function beforeFilter(Event $event) {
		$redirect = $this->request->getRequestTarget();
		$staffLogin = [
			'controller' => 'Users',
			'action' => 'acessoEmpresa',
			'prefix' => false,
			'?' => ['redirect' => $redirect],
		];
		$this->Auth->setConfig('loginAction', $staffLogin);
		$this->Auth->setConfig('unauthorizedRedirect', $staffLogin);
		parent::beforeFilter($event);
		$this->viewBuilder()->setLayout('erp_prototype');
	}

	/**
	 * pg-os-lista — listagem de OS com KPIs + tabela.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$query = $this->request->getQueryParams();
		$filtroSit = $query['situacao'] ?? '';
		$filtroCliente = trim((string)($query['cliente'] ?? ''));
		$filtroDe = trim((string)($query['de'] ?? ''));
		$filtroAte = trim((string)($query['ate'] ?? ''));

		$where = ['Ordensservico.idempresa' => $empresa];
		if ($filtroSit !== '' && is_numeric($filtroSit)) {
			$where['Ordensservico.situacao'] = (int)$filtroSit;
		}
		if ($filtroCliente !== '') {
			$where['Ordensservico.idcliente'] = (int)$filtroCliente;
		}
		if ($filtroDe !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroDe)) {
			$where['Ordensservico.dataabertura >='] = $filtroDe;
		}
		if ($filtroAte !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroAte)) {
			$where['Ordensservico.dataabertura <='] = $filtroAte;
		}

		$rows = [];
		try {
			$q = $this->Ordensservico->find()
				->contain(['Clientes'])
				->where($where)
				->order(['Ordensservico.id' => 'DESC'])
				->limit(100);
			$rows = $q->all()->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$counts = ['abertas' => 0, 'em_execucao' => 0, 'aguardando' => 0, 'concluidas' => 0, 'total' => 0];
		$totalValor = 0.0;
		$items = [];
		foreach ($rows as $os) {
			$counts['total']++;
			$st = strtolower(trim((string)($os->get('situacao') ?? '')));
			if (strpos($st, 'concl') !== false || strpos($st, 'fech') !== false) {
				$counts['concluidas']++;
			} elseif (strpos($st, 'execu') !== false || strpos($st, 'andam') !== false) {
				$counts['em_execucao']++;
			} elseif (strpos($st, 'aguard') !== false || strpos($st, 'aprov') !== false) {
				$counts['aguardando']++;
			} else {
				$counts['abertas']++;
			}
			$valor = (float)($os->get('valortotal') ?? $os->get('valor_total') ?? 0);
			$totalValor += $valor;
			$cl = $os->cliente ?? null;
			$items[] = [
				'id' => (int)$os->get('id'),
				'cliente' => $cl ? (string)($cl->get('razaosocial') ?? $cl->get('nome') ?? '') : '—',
				'descricao' => (string)($os->get('descricao') ?? $os->get('observacao') ?? ''),
				'valor' => $valor,
				'situacao' => (string)($os->get('situacao') ?? '—'),
				'data' => $os->get('dataabertura') ?? $os->get('created') ?? $os->get('data'),
			];
		}

		$this->set([
			'title' => __('Ordens de Serviço'),
			'erpNavActive' => 'os-lista',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Operações')],
				['label' => __('Ordens de Serviço'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'osCounts' => $counts,
			'osTotalValor' => $totalValor,
			'osItems' => $items,
			'osClientesOptions' => $this->buildClientesOptions(),
			'osFiltros' => [
				'situacao' => $filtroSit,
				'cliente' => $filtroCliente,
				'de' => $filtroDe,
				'ate' => $filtroAte,
			],
		]);
	}

	/**
	 * GET /ordens-prototype/export.csv — exporta OS filtradas.
	 */
	public function exportCsv() {
		$empresa = (int)$this->Auth->user('idempresa');
		$query = $this->request->getQueryParams();
		$where = ['Ordensservico.idempresa' => $empresa];
		if (isset($query['situacao']) && is_numeric($query['situacao']) && $query['situacao'] !== '') {
			$where['Ordensservico.situacao'] = (int)$query['situacao'];
		}
		if (!empty($query['cliente'])) {
			$where['Ordensservico.idcliente'] = (int)$query['cliente'];
		}
		if (!empty($query['de']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$query['de'])) {
			$where['Ordensservico.dataabertura >='] = $query['de'];
		}
		if (!empty($query['ate']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$query['ate'])) {
			$where['Ordensservico.dataabertura <='] = $query['ate'];
		}
		$rows = $this->Ordensservico->find()->contain(['Clientes'])->where($where)->order(['Ordensservico.id' => 'DESC'])->limit(5000)->all();

		$this->autoRender = false;
		$fname = 'ordens-' . date('Ymd-His') . '.csv';
		$this->response = $this->response
			->withType('text/csv')
			->withHeader('Content-Disposition', 'attachment; filename="' . $fname . '"');

		$out = fopen('php://temp', 'w+');
		fwrite($out, "\xEF\xBB\xBF");
		fputcsv($out, ['ID', 'Cliente', 'Descrição', 'Valor total', 'Situação', 'Abertura'], ';');
		foreach ($rows as $os) {
			$cli = $os->cliente ?? null;
			fputcsv($out, [
				'OS-' . str_pad((string)$os->get('id'), 5, '0', STR_PAD_LEFT),
				$cli ? (string)($cli->get('razaosocial') ?? $cli->get('nome') ?? '') : '',
				\Cake\Utility\Text::truncate((string)($os->get('descricao') ?? $os->get('observacao') ?? ''), 200, ['ellipsis' => '…']),
				number_format((float)($os->get('valortotal') ?? $os->get('valor_total') ?? 0), 2, ',', '.'),
				(string)($os->get('situacao') ?? ''),
				$os->get('dataabertura') instanceof \DateTimeInterface ? $os->get('dataabertura')->format('d/m/Y') : '',
			], ';');
		}
		rewind($out);

		return $this->response->withStringBody(stream_get_contents($out));
	}

	/**
	 * POST /ordens-prototype/avancar-etapa — muda situação da OS.
	 * situacao: 0 Aberta, 1 Em execução, 2 Aguardando aprovação, 3 Concluída
	 */
	public function avancarEtapa() {
		$this->request->allowMethod(['post']);
		$empresa = (int)$this->Auth->user('idempresa');
		$id = (int)$this->request->getData('ordem_id');
		$nova = (int)$this->request->getData('nova_situacao');
		if ($id <= 0 || !in_array($nova, [0, 1, 2, 3], true)) {
			$this->Flash->error(__('Dados inválidos.'));

			return $this->redirect(['controller' => 'OrdensservicoPrototype', 'action' => 'lista']);
		}
		try {
			$os = $this->Ordensservico->find()->where(['id' => $id, 'idempresa' => $empresa])->first();
			if ($os === null) {
				$this->Flash->error(__('OS fora do escopo.'));

				return $this->redirect(['controller' => 'OrdensservicoPrototype', 'action' => 'lista']);
			}
			$atual = (int)$os->get('situacao');
			// Transições básicas: sempre permite voltar 1 passo ou avançar 1 passo
			$diff = $nova - $atual;
			if ($diff !== 1 && $diff !== -1 && $diff !== 0) {
				$this->Flash->error(__('Salte uma etapa por vez (atual: {0} → desejada: {1}).', $atual, $nova));

				return $this->redirect(['controller' => 'OrdensservicoPrototype', 'action' => 'detalhe', $id]);
			}
			$labels = [0 => __('Aberta'), 1 => __('Em execução'), 2 => __('Aguardando aprovação'), 3 => __('Concluída')];
			$os->set('situacao', $nova);
			if ($this->Ordensservico->save($os)) {
				$lbl = (string)($labels[$nova] ?? $nova);
				$this->Flash->success(__('OS movida para "{0}".', $lbl));
				(new \App\Service\PrototypeStatusHistoryService())->record(
					'os', $id, (string)($labels[$atual] ?? $atual), $lbl,
					(array)$this->Auth->user(), '', $empresa
				);
				// Push hook ao técnico responsável (iduser)
				try {
					$tecId = (int)$os->get('iduser');
					if ($tecId > 0) {
						(new \App\Service\WebPushSenderService())->sendToUser($tecId, [
							'title' => '🛠 ' . __('OS {0} · {1}', sprintf('OS-%05d', $id), $lbl),
							'body' => __('Etapa alterada por {0}.', trim((string)$this->Auth->user('name')) ?: 'equipe'),
							'url' => $this->Url->build(['controller' => 'OrdensservicoPrototype', 'action' => 'detalhe', $id]),
							'tag' => 'os-' . $id,
						]);
					}
				} catch (\Throwable $e) {
				}
			}
		} catch (\Throwable $e) {
			$this->Flash->error(__('Erro: {0}', $e->getMessage()));
		}

		return $this->redirect(['controller' => 'OrdensservicoPrototype', 'action' => 'detalhe', $id]);
	}

	/**
	 * POST /ordens-prototype/api/atualizar-item — edita qtd/valor unitário.
	 */
	public function apiAtualizarItem() {
		$this->request->allowMethod(['post']);
		if ($guard = $this->guardApiEquipe()) {
			return $guard;
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$itemId = (int)$this->request->getData('item_id');
		$qtd = (float)str_replace(',', '.', (string)$this->request->getData('quantidade'));
		$vu = (float)str_replace(',', '.', (string)$this->request->getData('valor_unitario'));
		if ($itemId <= 0 || $qtd <= 0 || $vu < 0) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Dados inválidos.')]));
		}
		try {
			$itens = $this->loadModel('Itensordem');
			$row = $itens->find()->where(['Itensordem.id' => $itemId, 'Itensordem.idempresa' => $empresa])->first();
			if ($row === null) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Item fora do escopo.')]));
			}
			$row->set('quantidade', $qtd);
			$row->set('valorunitario', $vu);
			$row->set('valortotal', $qtd * $vu);
			if (!$itens->save($row)) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Falha ao salvar.')]));
			}

			return $this->response->withStringBody(json_encode([
				'ok' => true,
				'item' => ['id' => $itemId, 'qtd' => $qtd, 'vlr' => $vu, 'subtotal' => $qtd * $vu],
			], JSON_UNESCAPED_UNICODE));
		} catch (\Throwable $e) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => $e->getMessage()]));
		}
	}

	/**
	 * POST /ordens-prototype/api/excluir-item — remove item da OS.
	 */
	public function apiExcluirItem() {
		$this->request->allowMethod(['post']);
		if ($guard = $this->guardApiEquipe()) {
			return $guard;
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$itemId = (int)$this->request->getData('item_id');
		if ($itemId <= 0) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('ID inválido.')]));
		}
		try {
			$itens = $this->loadModel('Itensordem');
			$row = $itens->find()->where(['Itensordem.id' => $itemId, 'Itensordem.idempresa' => $empresa])->first();
			if ($row === null) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Item fora do escopo.')]));
			}
			if (!$itens->delete($row)) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Falha ao excluir.')]));
			}

			return $this->response->withStringBody(json_encode(['ok' => true]));
		} catch (\Throwable $e) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => $e->getMessage()]));
		}
	}

	/**
	 * POST /ordens-prototype/api/adicionar-item — adiciona item à OS.
	 * Aceita ordem_id + descricao + quantidade + valor_unitario; cria itensordem.
	 */
	public function apiAdicionarItem() {
		$this->request->allowMethod(['post']);
		if ($guard = $this->guardApiEquipe()) {
			return $guard;
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$osId = (int)$this->request->getData('ordem_id');
		$descricao = trim((string)$this->request->getData('descricao'));
		$qtd = (float)str_replace(',', '.', (string)$this->request->getData('quantidade'));
		$vu = (float)str_replace(',', '.', (string)$this->request->getData('valor_unitario'));
		$unidade = trim((string)$this->request->getData('unidade'));
		if ($osId <= 0 || $descricao === '' || $qtd <= 0) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Descrição e quantidade obrigatórias.')]));
		}
		try {
			$os = $this->Ordensservico->find()->where(['id' => $osId, 'idempresa' => $empresa])->first();
			if ($os === null) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('OS fora do escopo.')]));
			}
			$itens = $this->loadModel('Itensordem');
			$subtotal = $qtd * $vu;
			$entity = $itens->newEntity([
				'idempresa' => $empresa,
				'idordempk' => $osId,
				'descricao' => $descricao,
				'unidade' => $unidade !== '' ? $unidade : 'un',
				'quantidade' => $qtd,
				'valorunitario' => $vu,
				'valordesconto' => 0,
				'valortotal' => $subtotal,
				'tipo' => 'serv',
			], ['validate' => false]);
			$saved = $itens->save($entity);
			if (!$saved) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Falha ao gravar item.')]));
			}

			return $this->response->withStringBody(json_encode([
				'ok' => true,
				'item' => [
					'id' => (int)$entity->get('id'),
					'descricao' => $descricao,
					'unidade' => $unidade,
					'qtd' => $qtd,
					'vlr' => $vu,
					'subtotal' => $subtotal,
				],
			], JSON_UNESCAPED_UNICODE));
		} catch (\Throwable $e) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => $e->getMessage()]));
		}
	}

	/**
	 * Salva OS rascunho a partir do wizard premium (pg-os-abertura).
	 */
	public function salvarRascunho() {
		$this->request->allowMethod(['post']);
		$empresa = (int)$this->Auth->user('idempresa');
		$autor = (int)$this->Auth->user('id');
		$idcliente = (int)$this->request->getData('idcliente');
		$relato = trim((string)$this->request->getData('relato'));
		$prio = (int)$this->request->getData('prioridade');
		if ($idcliente <= 0) {
			$this->Flash->error(__('Selecione um cliente para abrir a OS.'));

			return $this->redirect(['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura']);
		}
		try {
			$Clientes = $this->loadModel('Clientes');
			$cli = $Clientes->find()->where(['Clientes.id' => $idcliente, 'Clientes.idempresa' => $empresa])->first();
			if ($cli === null) {
				$this->Flash->error(__('Cliente fora do seu escopo.'));

				return $this->redirect(['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura']);
			}
			$entity = $this->Ordensservico->newEntity([
				'idempresa' => $empresa,
				'idcliente' => $idcliente,
				'iduser' => $autor,
				'dataabertura' => date('Y-m-d'),
				'relato' => $relato !== '' ? $relato : __('OS aberta via wizard premium'),
				'situacao' => 0,
				'prioridade' => in_array($prio, [1, 2, 3], true) ? $prio : 2,
				'contrato' => 0,
				'locacao' => 0,
			], ['validate' => false]);
			$saved = $this->Ordensservico->save($entity);
			if ($saved === false) {
				$this->Flash->error(__('Falha ao gravar a OS.'));

				return $this->redirect(['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura']);
			}
			$this->Flash->success(__('OS {0} aberta com sucesso.', sprintf('OS-%05d', (int)$entity->get('id'))));

			return $this->redirect(['controller' => 'OrdensservicoPrototype', 'action' => 'detalhe', (int)$entity->get('id')]);
		} catch (\Throwable $e) {
			$this->Flash->error(__('Erro ao gravar: {0}', $e->getMessage()));

			return $this->redirect(['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura']);
		}
	}

	/**
	 * Detalhe de uma OS (carrega cliente + itens + horas).
	 *
	 * @param string|int $id
	 */
	public function detalhe($id) {
		$id = (int)$id;
		if ($id <= 0) {
			throw new NotFoundException(__('OS inválida.'));
		}
		$empresa = (int)$this->Auth->user('idempresa');
		$os = null;
		try {
			$os = $this->Ordensservico->find()
				->contain(['Clientes'])
				->where(['Ordensservico.id' => $id, 'Ordensservico.idempresa' => $empresa])
				->first();
		} catch (\Throwable $e) {
		}
		if ($os === null) {
			throw new NotFoundException(__('OS não encontrada ou fora do seu escopo.'));
		}

		$itens = [];
		try {
			$tblIt = $this->loadModel('Itensordem');
			$itens = $tblIt->find()
				->where(['Itensordem.idordempk' => $id])
				->order(['Itensordem.id' => 'ASC'])
				->all()
				->toArray();
		} catch (\Throwable $e) {
		}

		$movs = [];
		try {
			$tblMov = $this->loadModel('Ordemmovs');
			$rows = $tblMov->find()
				->where(['Ordemmovs.idordem' => $id])
				->order(['Ordemmovs.data' => 'DESC'])
				->limit(20)
				->all();
			foreach ($rows as $m) {
				$movs[] = [
					'data' => $m->get('data'),
					'sitantiga' => (string)($m->get('sitantiga') ?? ''),
					'sitnova' => (string)($m->get('sitnova') ?? ''),
					'obs' => (string)($m->get('obs') ?? ''),
				];
			}
		} catch (\Throwable $e) {
		}

		$linhas = [];
		$totalItens = 0.0;
		foreach ($itens as $it) {
			$qtd = (float)($it->get('quantidade') ?? 1);
			$vu = (float)($it->get('valorunitario') ?? 0);
			$desc = (float)($it->get('valordesconto') ?? 0);
			$subtotal = (float)($it->get('valortotal') ?? ($qtd * $vu - $desc));
			$totalItens += $subtotal;
			$linhas[] = [
				'id' => (int)$it->get('id'),
				'descricao' => (string)($it->get('descricao') ?? ''),
				'unidade' => (string)($it->get('unidade') ?? ''),
				'qtd' => $qtd,
				'vlr' => $vu,
				'desconto' => $desc,
				'subtotal' => $subtotal,
			];
		}

		$cliente = $os->cliente ?? null;
		$historico = (new \App\Service\PrototypeStatusHistoryService())->fetch('os', $id, 30);
		$this->set([
			'title' => __('OS #{0}', $id),
			'osHistorico' => $historico,
			'erpNavActive' => 'os-lista',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Operações')],
				['label' => __('Ordens de Serviço'), 'url' => ['controller' => 'OrdensservicoPrototype', 'action' => 'lista']],
				['label' => '#' . $id, 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'os' => [
				'id' => (int)$os->get('id'),
				'cliente' => $cliente ? (string)($cliente->get('razaosocial') ?? $cliente->get('nome') ?? '') : '—',
				'cliente_cnpj' => $cliente ? (string)($cliente->get('cnpj') ?? $cliente->get('cpf') ?? '') : '',
				'descricao' => (string)($os->get('descricao') ?? $os->get('observacao') ?? ''),
				'situacao' => (string)($os->get('situacao') ?? ''),
				'valortotal' => (float)($os->get('valortotal') ?? $os->get('valor_total') ?? $totalItens),
				'abertura' => $os->get('dataabertura') ?? $os->get('created') ?? $os->get('data'),
			],
			'osLinhas' => $linhas,
			'osTotalItens' => $totalItens,
			'osMovs' => $movs,
		]);

		return $this->render('detalhe');
	}

	/**
	 * Telas do wizard OS (abertura|execucao|aprovacao|conclusao|faturamento|cobranca|sucesso|kanban).
	 *
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		$wizard = ['abertura' => 1, 'execucao' => 2, 'aprovacao' => 3, 'conclusao' => 4, 'sucesso' => 5];
		$allowed = array_merge(array_keys($wizard), ['faturamento', 'cobranca', 'kanban']);
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$steps = [
			['label' => __('Abertura'), 'state' => 'pending'],
			['label' => __('Execução'), 'state' => 'pending'],
			['label' => __('Aprovação cliente'), 'state' => 'pending'],
			['label' => __('Conclusão'), 'state' => 'pending'],
			['label' => __('Sucesso'), 'state' => 'pending'],
		];
		if (isset($wizard[$page])) {
			$current = (int)$wizard[$page] - 1;
			for ($i = 0; $i < $current; $i++) {
				$steps[$i]['state'] = 'done';
			}
			$steps[$current]['state'] = 'active';
		}

		$this->set([
			'title' => __('OS · {0}', ucfirst($page)),
			'erpNavActive' => 'os-' . $page,
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Operações')],
				['label' => __('Ordens de Serviço'), 'url' => ['controller' => 'OrdensservicoPrototype', 'action' => 'lista']],
				['label' => ucfirst($page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
			'wizardSteps' => $steps,
			'wizardCurrent' => $page,
		]);

		$dedicated = ['abertura', 'execucao', 'aprovacao', 'conclusao', 'sucesso'];
		if (in_array($page, $dedicated, true)) {
			if ($page === 'abertura') {
				$this->set('osClientesOptions', $this->buildClientesOptions());
			}

			return $this->render('wizard_' . $page);
		}

		return $this->render('placeholder');
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function loadEmpresasParaTopbar(): array {
		try {
			$tbl = $this->loadModel('Empresas');
		} catch (\Throwable $e) {
			return [];
		}
		$active = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			$q = $tbl->find()->order(['id' => 'ASC'])->limit(20);
			foreach ($q->all() as $e) {
				$nome = (string)($e->get('razaosocial') ?? $e->get('nome') ?? '');
				if ($nome === '') {
					continue;
				}
				$out[] = [
					'id' => (int)$e->get('id'),
					'nome' => $nome,
					'cnpj' => (string)($e->get('cnpj') ?? ''),
					'current' => (int)$e->get('id') === $active,
				];
			}
		} catch (\Throwable $e) {
			return [];
		}

		return $out;
	}
}
