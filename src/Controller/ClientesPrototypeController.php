<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Clientes — protótipo (mockup pg-clientes, pg-cliente-novo, pg-cliente-360).
 *
 * Lado-a-lado com ClientesController (legado). Rotas em /clientes-prototype/*.
 * Somente leitura nesta fase.
 */
class ClientesPrototypeController extends AppController {

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

	public function isAuthorized($user) {
		if (empty($user)) {
			return false;
		}
		if ((int)($user['role'] ?? -1) !== 0) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	/**
	 * pg-clientes — lista com KPIs (jurídica/física, ativos/inativos) + busca.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$rows = [];
		try {
			$rows = $this->Clientes->find()
				->where(['Clientes.idempresa' => $empresa])
				->order(['Clientes.id' => 'DESC'])
				->limit(200)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$counts = ['total' => 0, 'pj' => 0, 'pf' => 0, 'ativos' => 0, 'inativos' => 0];
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
		]);
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
		$allowed = ['novo', '360', 'export', 'import'];
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
		$empresa = (int)$this->Auth->user('idempresa');
		$cliId = (int)$this->request->getQuery('id', 0);

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
			if ($closed !== []) {
				$where['Tickets.situacao NOT IN'] = $closed;
			}
			$payload['tickets_abertos'] = (int)$tickets->find()->where($where)->count();
		} catch (\Throwable $e) {
		}

		try {
			$faturas = $this->loadModel('Faturas');
			$w = ['Faturas.idempresa' => $empresa];
			if ($cliId > 0) {
				$w['Faturas.idcliente'] = $cliId;
			}
			$rows = $faturas->find()->where($w)->all();
			$now = \Cake\I18n\Time::now();
			$ltv = 0.0;
			foreach ($rows as $f) {
				$v = (float)($f->get('valor') ?? 0);
				$ltv += $v;
				$status = strtolower((string)($f->get('status') ?? ''));
				$pago = strpos($status, 'pag') !== false || $f->get('dtretorno') instanceof \DateTimeInterface;
				$venc = $f->get('vencimento');
				if (!$pago && $venc instanceof \DateTimeInterface && $venc < $now) {
					$payload['faturas_vencidas']++;
				}
			}
			$payload['ltv'] = $ltv;
		} catch (\Throwable $e) {
		}

		$this->set('payload360', $payload);

		return $this->render('cliente_360');
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
