<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Bancos — protótipo (mockup pg-bancos, pg-contas, pg-extrato, pg-conciliacao,
 * pg-transferencias, pg-fluxo-caixa).
 *
 * Lado-a-lado com FinanceiroBancosController. Somente leitura.
 */
class BancosPrototypeController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('FinanceiroBancos');
		$this->loadModel('Faturas');
		$this->loadModel('FinanceiroLancamentos');
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
	 * pg-bancos — dashboard de contas bancárias.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$rows = [];
		try {
			$rows = $this->FinanceiroBancos->find()
				->where(['FinanceiroBancos.idempresa' => $empresa])
				->order(['FinanceiroBancos.id' => 'ASC'])
				->limit(50)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$items = [];
		$totalContas = 0;
		$totalAtivas = 0;
		foreach ($rows as $b) {
			$ativo = (int)$b->get('ativo') === 1;
			$totalContas++;
			if ($ativo) {
				$totalAtivas++;
			}
			$items[] = [
				'id' => (int)$b->get('id'),
				'nome' => (string)($b->get('nome') ?? ''),
				'codigo' => (string)($b->get('codigo_banco') ?? $b->get('numero_banco') ?? ''),
				'agencia' => trim((string)$b->get('numero_agencia') . ((string)$b->get('digito_agencia') !== '' ? '-' . $b->get('digito_agencia') : '')),
				'conta' => trim((string)$b->get('numero_conta') . ((string)$b->get('digito_conta') !== '' ? '-' . $b->get('digito_conta') : '')),
				'carteira' => (string)($b->get('carteira') ?? ''),
				'ativo' => $ativo,
			];
		}

		$this->set([
			'title' => __('Bancos'),
			'erpNavActive' => 'bancos',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Bancos'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'bcItems' => $items,
			'bcKpi' => ['total' => $totalContas, 'ativas' => $totalAtivas, 'inativas' => $totalContas - $totalAtivas],
		]);
	}

	/**
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		$allowed = ['contas', 'extrato', 'conciliacao', 'transferencias', 'fluxo-caixa'];
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$set = [
			'title' => __('Bancos · {0}', ucfirst((string)$page)),
			'erpNavActive' => $page,
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Bancos'), 'url' => ['controller' => 'BancosPrototype', 'action' => 'lista']],
				['label' => ucfirst((string)$page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
		];

		if ($page === 'fluxo-caixa') {
			$set['useChartJs'] = true;
			$set['fluxoData'] = $this->buildFluxoPayload();
			$this->set($set);

			return $this->render('fluxo_caixa');
		}

		if ($page === 'conciliacao') {
			$set += $this->buildConciliacaoPayload();
			$this->set($set);

			return $this->render('conciliacao');
		}

		$this->set($set);

		return $this->render('placeholder');
	}

	/**
	 * Conciliação simplificada: lê extrato bancário, mostra status (conciliado/pendente)
	 * e tenta sugerir matching por valor + data (±3 dias) com financeiro_lancamentos.
	 *
	 * @return array<string,mixed>
	 */
	protected function buildConciliacaoPayload(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$kpi = ['conciliados' => 0, 'pendentes' => 0, 'divergentes' => 0, 'total_extrato' => 0, 'total_lancamentos' => 0];
		$items = [];

		$schema = null;
		try {
			$schema = \Cake\ORM\TableRegistry::getTableLocator()->get('Empresas')->getConnection()->getSchemaCollection()->listTables();
		} catch (\Throwable $e) {
		}

		if ($schema !== null && in_array('financeiro_extrato_bancario', $schema, true)) {
			try {
				$ext = \Cake\ORM\TableRegistry::getTableLocator()->get('FinanceiroExtratoBancario');
				$lan = \Cake\ORM\TableRegistry::getTableLocator()->get('FinanceiroLancamentos');

				$rows = $ext->find()
					->where(['FinanceiroExtratoBancario.idempresa' => $empresa])
					->order(['FinanceiroExtratoBancario.data' => 'DESC'])
					->limit(80)
					->all();

				$kpi['total_extrato'] = (int)$ext->find()->where(['FinanceiroExtratoBancario.idempresa' => $empresa])->count();
				$kpi['total_lancamentos'] = (int)$lan->find()->where(['FinanceiroLancamentos.idempresa' => $empresa])->count();

				foreach ($rows as $e) {
					$valor = (float)$e->get('valor');
					$data = $e->get('data');
					$conciliado = (int)$e->get('conciliado') === 1 || (int)$e->get('financeiro_lancamento_id') > 0;
					$matchSuggest = null;
					if (!$conciliado && $data instanceof \DateTimeInterface) {
						$ini = $data->copy()->subDays(3);
						$fim = $data->copy()->addDays(3);
						$try = $lan->find()
							->where([
								'FinanceiroLancamentos.idempresa' => $empresa,
								'FinanceiroLancamentos.valor' => $valor,
								'FinanceiroLancamentos.data_lancamento >=' => $ini,
								'FinanceiroLancamentos.data_lancamento <=' => $fim,
							])
							->limit(1)
							->first();
						if ($try !== null) {
							$matchSuggest = [
								'id' => (int)$try->get('id'),
								'descricao' => (string)$try->get('descricao'),
								'data' => $try->get('data_lancamento'),
							];
						}
					}
					if ($conciliado) {
						$kpi['conciliados']++;
						$status = 'conciliado';
					} elseif ($matchSuggest !== null) {
						$kpi['divergentes']++;
						$status = 'sugerido';
					} else {
						$kpi['pendentes']++;
						$status = 'pendente';
					}
					$items[] = [
						'id' => (int)$e->get('id'),
						'data' => $data,
						'descricao' => (string)$e->get('descricao'),
						'tipo' => strtolower((string)$e->get('tipo')),
						'valor' => $valor,
						'conta' => (string)$e->get('conta_bancaria'),
						'status' => $status,
						'match' => $matchSuggest,
					];
				}
			} catch (\Throwable $e) {
			}
		}

		return [
			'concKpi' => $kpi,
			'concItems' => $items,
		];
	}

	/**
	 * Recebimentos × pagamentos diários nos últimos 30 dias.
	 *
	 * @return array<string,array<int,float|string>>
	 */
	protected function buildFluxoPayload(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$now = \Cake\I18n\Time::now();
		$labels = [];
		$entradas = [];
		$saidas = [];
		$saldo = [];
		$acc = 0.0;
		for ($i = 29; $i >= 0; $i--) {
			$day = $now->copy()->subDays($i)->startOfDay();
			$dayEnd = $day->copy()->endOfDay();
			$labels[] = $day->format('d/m');

			$e = 0.0;
			try {
				foreach ($this->Faturas->find()
					->where(['Faturas.idempresa' => $empresa, 'Faturas.dtretorno >=' => $day, 'Faturas.dtretorno <=' => $dayEnd])
					->all() as $f) {
					$e += (float)($f->get('valor') ?? 0);
				}
			} catch (\Throwable $ex) {
			}
			$entradas[] = round($e, 2);

			$s = 0.0;
			try {
				foreach ($this->FinanceiroLancamentos->find()
					->where(['FinanceiroLancamentos.idempresa' => $empresa, 'FinanceiroLancamentos.data_baixa >=' => $day, 'FinanceiroLancamentos.data_baixa <=' => $dayEnd])
					->all() as $l) {
					$tipo = strtolower((string)($l->get('tipo') ?? ''));
					if (strpos($tipo, 'desp') !== false || strpos($tipo, 'pag') !== false || $tipo === 'p') {
						$s += (float)($l->get('valor') ?? 0);
					}
				}
			} catch (\Throwable $ex) {
			}
			$saidas[] = round($s, 2);

			$acc += $e - $s;
			$saldo[] = round($acc, 2);
		}

		return [
			'labels' => $labels,
			'entradas' => $entradas,
			'saidas' => $saidas,
			'saldo' => $saldo,
		];
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
