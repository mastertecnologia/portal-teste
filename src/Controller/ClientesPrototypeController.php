<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Controller\Traits\PrototypeApiSecurityTrait;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Clientes — protótipo (mockup pg-clientes, pg-cliente-novo, pg-cliente-360).
 *
 * Lado-a-lado com ClientesController (legado). Rotas em /clientes-prototype/*.
 * Somente leitura nesta fase.
 */
class ClientesPrototypeController extends AppController {

	use ErpPrototypeRbacTrait;
	use PrototypeApiSecurityTrait;

	public function initialize() {
		parent::initialize();
		$this->loadModel('Clientes');
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
	 * pg-clientes — lista com KPIs (jurídica/física, ativos/inativos) + busca.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$busca = trim((string)$this->request->getQuery('q', ''));
		$filtroTipo = (string)$this->request->getQuery('tipo', '');
		$filtroStatus = (string)$this->request->getQuery('status', '');
		$where = ['Clientes.idempresa' => $empresa];
		if ($busca !== '') {
			$where['OR'] = [
				'Clientes.nome ILIKE' => '%' . $busca . '%',
				'Clientes.razaosocial ILIKE' => '%' . $busca . '%',
				'Clientes.nomefantasia ILIKE' => '%' . $busca . '%',
				'Clientes.cnpj ILIKE' => '%' . $busca . '%',
				'Clientes.cpf ILIKE' => '%' . $busca . '%',
				'Clientes.email ILIKE' => '%' . $busca . '%',
				'Clientes.fone ILIKE' => '%' . $busca . '%',
			];
		}
		if ($filtroTipo === 'pj') {
			$where['Clientes.tipo'] = 2;
		} elseif ($filtroTipo === 'pf') {
			$where['Clientes.tipo IS NOT'] = 2;
		}
		if ($filtroStatus === 'ativo') {
			$where['Clientes.inativo'] = 0;
		} elseif ($filtroStatus === 'inativo') {
			$where['Clientes.inativo'] = 1;
		}
		$rows = [];
		try {
			$rows = $this->Clientes->find()
				->where($where)
				->order(['Clientes.id' => 'DESC'])
				->limit(200)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$counts = ['total' => 0, 'pj' => 0, 'pf' => 0, 'ativos' => 0, 'inativos' => 0, 'inadimplentes' => 0];
		$items = [];
		foreach ($rows as $r) {
			$counts['total']++;
			$tipo = (int)$r->get('tipo');
			$pj = $tipo === 2 || $tipo === (defined('C_ClientesTipoJuridica') ? (int)C_ClientesTipoJuridica : 2);
			if ($pj) {
				$counts['pj']++;
				$nome = (string)($r->get('razaosocial') ?? $r->get('nome') ?? '');
			} else {
				$counts['pf']++;
				$nome = (string)($r->get('nome') ?? '');
			}
			$inativo = (int)$r->get('inativo') === 1;
			if ($inativo) {
				$counts['inativos']++;
			} else {
				$counts['ativos']++;
			}
			$items[] = [
				'id' => (int)$r->get('id'),
				'public_code' => trim((string)($r->get('public_code') ?? '')),
				'tipo' => $pj ? 'PJ' : 'PF',
				'nome' => $nome,
				'fantasia' => (string)($r->get('nomefantasia') ?? ''),
				'cnpj' => (string)($r->get('cnpj') ?? $r->get('cpf') ?? ''),
				'email' => (string)($r->get('email') ?? ''),
				'fone' => (string)($r->get('fone') ?? $r->get('fone2') ?? ''),
				'inativo' => $inativo,
				'desde' => $r->get('membrodesde'),
			];
		}

		$counts['inadimplentes'] = $this->countClientesInadimplentes($empresa);

		$this->set([
			'title' => __('Clientes'),
			'erpNavActive' => 'clientes',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Clientes'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'cliCounts' => $counts,
			'cliItems' => $items,
			'cliFiltros' => ['q' => $busca, 'tipo' => $filtroTipo, 'status' => $filtroStatus],
		]);
	}

	/**
	 * POST /clientes-prototype/api/atualizar-contato — edita telefone/e-mail inline.
	 */
	public function apiAtualizarContato() {
		$this->request->allowMethod(['post']);
		if ($guard = $this->guardApiEquipe()) {
			return $guard;
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$cliId = (int)$this->request->getData('cliente_id');
		$campo = (string)$this->request->getData('campo');
		$valor = trim((string)$this->request->getData('valor'));
		if (!in_array($campo, ['email', 'fone'], true) || $cliId <= 0) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Campo inválido.')]));
		}
		if ($campo === 'email' && $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('E-mail inválido.')]));
		}
		try {
			$row = $this->Clientes->find()->where(['Clientes.id' => $cliId, 'Clientes.idempresa' => $empresa])->first();
			if ($row === null) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Fora do escopo.')]));
			}
			$row->set($campo, $valor !== '' ? $valor : null);
			if (!$this->Clientes->save($row)) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Falha ao salvar.')]));
			}

			return $this->response->withStringBody(json_encode(['ok' => true, 'campo' => $campo, 'valor' => $valor]));
		} catch (\Throwable $e) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => $e->getMessage()]));
		}
	}

	/**
	 * GET /clientes-prototype/export.csv — exporta clientes.
	 */
	public function exportCsv() {
		$empresa = (int)$this->Auth->user('idempresa');
		$rows = [];
		try {
			$rows = $this->Clientes->find()
				->where(['Clientes.idempresa' => $empresa])
				->order(['Clientes.id' => 'DESC'])
				->limit(10000)
				->all();
		} catch (\Throwable $e) {
		}
		$this->autoRender = false;
		$fname = 'clientes-' . date('Ymd-His') . '.csv';
		$this->response = $this->response
			->withType('text/csv')
			->withHeader('Content-Disposition', 'attachment; filename="' . $fname . '"');
		$out = fopen('php://temp', 'w+');
		fwrite($out, "\xEF\xBB\xBF");
		fputcsv($out, ['ID', 'Tipo', 'Nome / Razão social', 'Fantasia', 'CNPJ/CPF', 'E-mail', 'Telefone', 'Cidade', 'Estado', 'Status', 'Desde'], ';');
		foreach ($rows as $c) {
			$tipo = (int)$c->get('tipo') === 2 ? 'PJ' : 'PF';
			$nome = $tipo === 'PJ'
				? (string)($c->get('razaosocial') ?? $c->get('nome'))
				: (string)$c->get('nome');
			fputcsv($out, [
				(int)$c->get('id'),
				$tipo,
				$nome,
				(string)($c->get('nomefantasia') ?? ''),
				(string)($c->get('cnpj') ?? $c->get('cpf') ?? ''),
				(string)($c->get('email') ?? ''),
				(string)($c->get('fone') ?? $c->get('fone2') ?? ''),
				(string)($c->get('idcidade') ?? ''),
				(string)($c->get('estado') ?? ''),
				(int)$c->get('inativo') === 1 ? 'Inativo' : 'Ativo',
				$c->get('membrodesde') instanceof \DateTimeInterface ? $c->get('membrodesde')->format('d/m/Y') : '',
			], ';');
		}
		rewind($out);

		return $this->response->withStringBody(stream_get_contents($out));
	}

	/**
	 * Telas adicionais (novo, 360, import, export).
	 *
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		if ($page === 'novo') {
			return $this->redirect(['controller' => 'Clientes', 'action' => 'add']);
		}
		if ($page === 'export') {
			return $this->redirect(['action' => 'exportCsv']);
		}
		$allowed = ['360', 'export', 'import'];
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$this->set([
			'title' => __('Clientes · {0}', ucfirst($page)),
			'erpNavActive' => 'clientes',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Clientes'), 'url' => ['controller' => 'ClientesPrototype', 'action' => 'lista']],
				['label' => ucfirst($page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
		]);

		if ($page === '360') {
			return $this->visao360();
		}
		if ($page === 'import') {
			return $this->render('import');
		}

		return $this->render('placeholder');
	}

	/**
	 * pg-cliente-360 — visão 360º (KPIs + tickets + faturas + OS + contratos).
	 */
	protected function visao360() {
		$cliId = (int)$this->request->getQuery('id', 0);
		if ($cliId > 0) {
			return $this->redirect(['controller' => 'Clientes', 'action' => 'visao360', $cliId]);
		}

		$empresa = (int)$this->Auth->user('idempresa');

		$payload = [
			'cliente' => null,
			'tickets_abertos' => 0,
			'os_andamento' => 0,
			'contratos' => 0,
			'faturas_vencidas' => 0,
			'ltv' => 0.0,
		];

		try {
			if ($cliId > 0) {
				$cli = $this->Clientes->find()
					->where(['Clientes.id' => $cliId, 'Clientes.idempresa' => $empresa])
					->first();
				if ($cli) {
					$payload['cliente'] = [
						'id' => (int)$cli->get('id'),
						'nome' => (int)$cli->get('tipo') === 2
							? (string)($cli->get('razaosocial') ?? $cli->get('nome'))
							: (string)$cli->get('nome'),
						'fantasia' => (string)($cli->get('nomefantasia') ?? ''),
						'cnpj' => (string)($cli->get('cnpj') ?? $cli->get('cpf') ?? ''),
						'email' => (string)($cli->get('email') ?? ''),
						'fone' => (string)($cli->get('fone') ?? $cli->get('fone2') ?? ''),
						'desde' => $cli->get('membrodesde'),
						'endereco' => trim(
							(string)$cli->get('endereco') . ', ' .
							(string)$cli->get('nroendereco') . ' · ' .
							(string)$cli->get('bairro') . ' · ' .
							(string)$cli->get('estado')
						),
					];
				}
			}
		} catch (\Throwable $e) {
		}

		$payload['timeline'] = [];

		try {
			$tickets = $this->loadModel('Tickets');
			$closed = [];
			if (defined('C_TicketSituacaoFechado')) {
				$closed[] = (int)C_TicketSituacaoFechado;
			}
			if (defined('C_TicketSituacaoResolvido')) {
				$closed[] = (int)C_TicketSituacaoResolvido;
			}
			$where = ['Tickets.idempresa' => $empresa];
			if ($cliId > 0) {
				$where['Tickets.idcliente'] = $cliId;
			}
			$wAbertos = $where;
			if ($closed !== []) {
				$wAbertos['Tickets.situacao NOT IN'] = $closed;
			}
			$payload['tickets_abertos'] = (int)$tickets->find()->where($wAbertos)->count();
			// Timeline: últimos 5 tickets
			foreach ($tickets->find()->where($where)->order(['Tickets.created' => 'DESC'])->limit(5)->all() as $t) {
				$payload['timeline'][] = [
					'kind' => 'ticket',
					'icon' => '🎟',
					'label' => '#' . (int)$t->get('id') . ' · ' . \Cake\Utility\Text::truncate((string)$t->get('solicitacao'), 60, ['ellipsis' => '…']),
					'sub' => (string)$t->get('situacao'),
					'data' => $t->get('created'),
					'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', (int)$t->get('id')],
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$os = $this->loadModel('Ordensservico');
			$w = ['Ordensservico.idempresa' => $empresa];
			if ($cliId > 0) {
				$w['Ordensservico.idcliente'] = $cliId;
			}
			foreach ($os->find()->where($w)->order(['Ordensservico.id' => 'DESC'])->limit(5)->all() as $o) {
				$payload['timeline'][] = [
					'kind' => 'os',
					'icon' => '🛠',
					'label' => sprintf('OS-%05d', (int)$o->get('id')) . ' · ' . \Cake\Utility\Text::truncate((string)($o->get('relato') ?? $o->get('descricao') ?? ''), 60, ['ellipsis' => '…']),
					'sub' => (string)$o->get('situacao'),
					'data' => $o->get('dataabertura') ?? $o->get('created'),
					'url' => ['controller' => 'OrdensservicoPrototype', 'action' => 'detalhe', (int)$o->get('id')],
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$faturas = $this->loadModel('Faturas');
			$w = ['Faturas.idempresa' => $empresa];
			if ($cliId > 0) {
				$w['Faturas.idcliente'] = $cliId;
			}
			$rows = $faturas->find()->where($w)->order(['Faturas.vencimento' => 'DESC'])->limit(50)->all();
			$now = \Cake\I18n\Time::now();
			$ltv = 0.0;
			$timelineFat = 0;
			foreach ($rows as $f) {
				$v = (float)($f->get('valor') ?? 0);
				$ltv += $v;
				$status = strtolower((string)($f->get('status') ?? ''));
				$pago = strpos($status, 'pag') !== false || $f->get('dtretorno') instanceof \DateTimeInterface;
				$venc = $f->get('vencimento');
				if (!$pago && $venc instanceof \DateTimeInterface && $venc < $now) {
					$payload['faturas_vencidas']++;
				}
				if ($timelineFat < 5) {
					$payload['timeline'][] = [
						'kind' => 'fatura',
						'icon' => '💵',
						'label' => 'Fatura ' . (string)($f->get('nro') ?? '#' . (int)$f->get('id')) . ' · ' . ($pago ? 'paga' : ($venc instanceof \DateTimeInterface && $venc < $now ? 'vencida' : 'pendente')),
						'sub' => 'R$ ' . number_format($v, 2, ',', '.'),
						'data' => $venc,
						'url' => ['controller' => 'Faturas', 'action' => 'view', (int)$f->get('id')],
					];
					$timelineFat++;
				}
			}
			$payload['ltv'] = $ltv;
		} catch (\Throwable $e) {
		}

		// Ordena timeline por data desc (mais recentes primeiro)
		usort($payload['timeline'], static function ($a, $b) {
			$ta = $a['data'] instanceof \DateTimeInterface ? $a['data']->getTimestamp() : 0;
			$tb = $b['data'] instanceof \DateTimeInterface ? $b['data']->getTimestamp() : 0;

			return $tb <=> $ta;
		});
		$payload['timeline'] = array_slice($payload['timeline'], 0, 15);

		$this->set('payload360', $payload);

		return $this->render('cliente_360');
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	/**
	 * Clientes com ao menos uma fatura vencida e não quitada (escopo empresa).
	 */
	protected function countClientesInadimplentes(int $empresa): int {
		if ($empresa <= 0) {
			return 0;
		}
		try {
			$faturas = $this->loadModel('Faturas');
		} catch (\Throwable $e) {
			return 0;
		}
		$now = \Cake\I18n\FrozenTime::now();
		$clientes = [];
		try {
			$rows = $faturas->find()
				->select(['idcliente', 'status', 'vencimento', 'dtretorno'])
				->where([
					'Faturas.idempresa' => $empresa,
					'Faturas.idcliente IS NOT' => null,
					'Faturas.vencimento <' => $now,
				])
				->limit(5000)
				->all();
			foreach ($rows as $f) {
				$cid = (int)$f->get('idcliente');
				if ($cid <= 0) {
					continue;
				}
				$status = strtolower((string)($f->get('status') ?? ''));
				$pago = strpos($status, 'pag') !== false || $f->get('dtretorno') instanceof \DateTimeInterface;
				if (!$pago) {
					$clientes[$cid] = true;
				}
			}
		} catch (\Throwable $e) {
			return 0;
		}

		return count($clientes);
	}

	protected function loadEmpresasParaTopbar(): array {
		try {
			$tbl = $this->loadModel('Empresas');
		} catch (\Throwable $e) {
			return [];
		}
		$active = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			foreach ($tbl->find()->order(['id' => 'ASC'])->limit(20)->all() as $e) {
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
