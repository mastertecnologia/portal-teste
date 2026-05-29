<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Utility\FinanceiroContasPagarPrototypeBuilder;
use App\Utility\FinanceiroDrePrototypeBuilder;
use App\Utility\FinanceiroNfePrototypeBuilder;
use App\Utility\FinanceiroOrcFlowPrototypeBuilder;
use App\Utility\FinanceiroRelatoriosPrototypeBuilder;
use App\Utility\FinanceiroTitulosPrototypeBuilder;
use App\Utility\PortalUi;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\Time;

/**
 * Financeiro — protótipo premium (pg-financeiro, pg-titulos, pg-contas-pagar,
 * pg-nfe, pg-dre, pg-relatorios-fin, pg-orc-faturamento, pg-orc-cobranca).
 */
class FinanceiroPrototypeController extends AppController {

	use ErpPrototypeRbacTrait;

	public function initialize() {
		parent::initialize();
		$this->loadModel('FinanceiroLancamentos');
		$this->loadModel('Faturas');
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
	}

	/**
	 * pg-financeiro — dashboard executivo financeiro.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$now = Time::now();
		$monthStart = $now->copy()->startOfMonth();
		$monthEnd = $now->copy()->endOfMonth();

		$titulosBuilder = new FinanceiroTitulosPrototypeBuilder();
		$titulosPayload = $titulosBuilder->build($empresa);
		$cpBuilder = new FinanceiroContasPagarPrototypeBuilder();
		$cpPayload = $cpBuilder->build($empresa);

		$crKpi = $titulosPayload['crKpi'];
		$cpKpi = $cpPayload['cpKpi'];

		$this->set([
			'title' => __('Financeiro'),
			'erpNavActive' => 'financeiro',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Financeiro'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'finKpis' => [
				'cr_receber' => (float)$crKpi['total_receber'],
				'cr_recebido_mes' => (float)$crKpi['pago_mes'],
				'cr_atrasadas' => (int)($titulosPayload['crPaginacao']['total'] ?? 0),
				'cr_vencendo_30d' => (float)$crKpi['vence_30d'],
				'cp_pagar' => (float)$cpKpi['total_pagar'],
				'cp_pago_mes' => (float)$cpKpi['pago_mes'],
				'saldo_mes' => (float)$crKpi['pago_mes'] - (float)$cpKpi['pago_mes'],
			],
		]);
	}

	/**
	 * pg-titulos — contas a receber.
	 */
	public function titulos() {
		$empresa = (int)$this->Auth->user('idempresa');
		$req = $this->request->getQueryParams();
		$payload = (new FinanceiroTitulosPrototypeBuilder())->build($empresa, [
			'tab' => (string)($req['tab'] ?? 'todos'),
			'cliente' => (int)($req['cliente'] ?? 0),
			'banco' => (int)($req['banco'] ?? 0),
			'busca' => (string)($req['q'] ?? $req['busca'] ?? ''),
			'page' => (int)($req['page'] ?? 1),
		]);

		$this->set(array_merge($payload, [
			'title' => __('Contas a Receber'),
			'erpNavActive' => 'titulos',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Financeiro'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista']],
				['label' => __('Contas a Receber'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
		]));

		return $this->render('titulos');
	}

	/**
	 * pg-contas-pagar.
	 */
	public function contasPagar() {
		$empresa = (int)$this->Auth->user('idempresa');
		$req = $this->request->getQueryParams();
		$payload = (new FinanceiroContasPagarPrototypeBuilder())->build($empresa, [
			'status' => (string)($req['status'] ?? ''),
			'fornecedor' => (int)($req['fornecedor'] ?? 0),
			'centro' => (int)($req['centro'] ?? 0),
			'busca' => (string)($req['q'] ?? $req['busca'] ?? ''),
			'page' => (int)($req['page'] ?? 1),
		]);

		$this->set(array_merge($payload, [
			'title' => __('Contas a Pagar'),
			'erpNavActive' => 'contas-pagar',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Financeiro'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista']],
				['label' => __('Contas a Pagar'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
		]));

		return $this->render('contas_pagar');
	}

	/**
	 * pg-relatorios-fin.
	 */
	public function relatoriosFin() {
		$empresa = (int)$this->Auth->user('idempresa');
		$payload = (new FinanceiroRelatoriosPrototypeBuilder())->build($empresa);

		$this->set(array_merge($payload, [
			'title' => __('Relatórios Financeiros'),
			'erpNavActive' => 'relatorios-fin',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Financeiro'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista']],
				['label' => __('Relatórios'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
		]));

		return $this->render('relatorios_fin');
	}

	/**
	 * pg-orc-faturamento.
	 */
	public function orcFaturamento() {
		$empresa = (int)$this->Auth->user('idempresa');
		$idFat = (int)$this->request->getQuery('idfaturamento', 0);
		$idOrc = (int)$this->request->getQuery('id', 0);
		$payload = (new FinanceiroOrcFlowPrototypeBuilder())->buildFaturamento($empresa, $idFat, $idOrc);
		if ($payload === null) {
			throw new NotFoundException(__('Faturamento não encontrado.'));
		}

		$this->set(array_merge($payload, [
			'title' => __('Faturamento do Orçamento #{0}', $payload['orcId'] > 0 ? $payload['orcId'] : $payload['fatNumero']),
			'erpNavActive' => 'orc-faturamento',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Orçamentos'), 'url' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista']],
				['label' => __('Faturamento'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
		]));

		return $this->render('orc_faturamento');
	}

	/**
	 * pg-orc-cobranca.
	 */
	public function orcCobranca() {
		$empresa = (int)$this->Auth->user('idempresa');
		$idFat = (int)$this->request->getQuery('idfaturamento', 0);
		$idOrc = (int)$this->request->getQuery('id', 0);
		$payload = (new FinanceiroOrcFlowPrototypeBuilder())->buildCobranca($empresa, $idFat, $idOrc);
		if ($payload === null) {
			throw new NotFoundException(__('Cobrança não encontrada.'));
		}

		$this->set(array_merge($payload, [
			'title' => __('Cobrança & Baixa · Orç. #{0}', $payload['orcId'] > 0 ? $payload['orcId'] : $payload['fatNumero']),
			'erpNavActive' => 'orc-cobranca',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Orçamentos'), 'url' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista']],
				['label' => __('Cobrança'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
		]));

		return $this->render('orc_cobranca');
	}

	/**
	 * @param string $page
	 */
	public function view($page = 'lista') {
		switch ($page) {
			case 'lista':
				return $this->lista();
			case 'titulos':
				return $this->titulos();
			case 'contas-pagar':
				return $this->contasPagar();
			case 'relatorios-fin':
				return $this->relatoriosFin();
			case 'orc-faturamento':
			case 'faturamento':
				return $this->orcFaturamento();
			case 'orc-cobranca':
			case 'cobranca':
				return $this->orcCobranca();
		}

		$allowed = ['nfe', 'dre'];
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$empresa = (int)$this->Auth->user('idempresa');
		$req = $this->request->getQueryParams();
		$set = [
			'title' => __('Financeiro · {0}', ucfirst((string)$page)),
			'erpNavActive' => $page,
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Financeiro'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista']],
				['label' => ucfirst((string)$page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
		];

		if ($page === 'dre') {
			$payload = (new FinanceiroDrePrototypeBuilder())->build(
				$empresa,
				(string)($req['periodo'] ?? '')
			);
			$this->set($set + $payload);

			return $this->render('dre');
		}

		if ($page === 'nfe') {
			$payload = (new FinanceiroNfePrototypeBuilder())->build($empresa, [
				'tab' => (string)($req['tab'] ?? 'todas'),
				'busca' => (string)($req['q'] ?? $req['busca'] ?? ''),
				'modelo' => (string)($req['modelo'] ?? ''),
			]);
			$this->set($set + $payload);

			return $this->render('nfe');
		}

		$this->set($set);

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
