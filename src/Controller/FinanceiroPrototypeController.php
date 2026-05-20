<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\Time;

/**
 * Financeiro — protótipo (mockup pg-financeiro, pg-titulos, pg-contas-pagar,
 * pg-nfe, pg-dre, pg-relatorios-fin, pg-orc-faturamento, pg-orc-cobranca).
 *
 * Lado-a-lado com FinanceiroController + FaturamentoController + FaturasController.
 * Somente leitura.
 */
class FinanceiroPrototypeController extends AppController {

	public function initialize() {
		parent::initialize();
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
	 * pg-financeiro — dashboard executivo financeiro.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$now = Time::now();
		$monthStart = $now->copy()->startOfMonth();
		$monthEnd = $now->copy()->endOfMonth();

		// Faturas (CR): valor a receber, recebido no mês, atrasadas
		$crReceber = 0.0;
		$crRecebidoMes = 0.0;
		$crAtrasadas = 0;
		$crVencendo30d = 0.0;
		try {
			foreach ($this->Faturas->find()
				->where(['Faturas.idempresa' => $empresa])
				->all() as $f) {
				$v = (float)($f->get('valor') ?? 0);
				$status = strtolower((string)($f->get('status') ?? ''));
				$venc = $f->get('vencimento');
				if (strpos($status, 'pag') !== false && $f->get('dtretorno') instanceof \DateTimeInterface) {
					$rt = $f->get('dtretorno');
					if ($rt >= $monthStart && $rt <= $monthEnd) {
						$crRecebidoMes += $v;
					}
				} else {
					$crReceber += $v;
					if ($venc instanceof \DateTimeInterface) {
						if ($venc < $now) {
							$crAtrasadas++;
						} elseif ($venc <= $now->copy()->addDays(30)) {
							$crVencendo30d += $v;
						}
					}
				}
			}
		} catch (\Throwable $e) {
		}

		// Lançamentos: despesas a pagar / pagas
		$cpPagar = 0.0;
		$cpPagoMes = 0.0;
		try {
			foreach ($this->FinanceiroLancamentos->find()
				->where(['FinanceiroLancamentos.idempresa' => $empresa])
				->all() as $l) {
				$tipo = strtolower((string)($l->get('tipo') ?? ''));
				if (strpos($tipo, 'desp') === false && strpos($tipo, 'pag') === false && $tipo !== 'p') {
					continue;
				}
				$v = (float)($l->get('valor') ?? 0);
				$status = strtolower((string)($l->get('status') ?? ''));
				$baixa = $l->get('data_baixa');
				if (strpos($status, 'pag') !== false && $baixa instanceof \DateTimeInterface) {
					if ($baixa >= $monthStart && $baixa <= $monthEnd) {
						$cpPagoMes += $v;
					}
				} else {
					$cpPagar += $v;
				}
			}
		} catch (\Throwable $e) {
		}

		$this->set([
			'title' => __('Financeiro'),
			'erpNavActive' => 'financeiro',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Financeiro'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'finKpis' => [
				'cr_receber' => $crReceber,
				'cr_recebido_mes' => $crRecebidoMes,
				'cr_atrasadas' => $crAtrasadas,
				'cr_vencendo_30d' => $crVencendo30d,
				'cp_pagar' => $cpPagar,
				'cp_pago_mes' => $cpPagoMes,
				'saldo_mes' => $crRecebidoMes - $cpPagoMes,
			],
		]);
	}

	/**
	 * pg-titulos — contas a receber (faturas).
	 */
	public function titulos() {
		$empresa = (int)$this->Auth->user('idempresa');
		$now = Time::now();
		$rows = [];
		try {
			$rows = $this->Faturas->find()
				->contain(['Clientes'])
				->where(['Faturas.idempresa' => $empresa])
				->order(['Faturas.vencimento' => 'DESC'])
				->limit(150)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$kpi = ['pend' => 0, 'paga' => 0, 'vencida' => 0, 'total_valor' => 0.0];
		$items = [];
		foreach ($rows as $f) {
			$cliente = $f->cliente ?? null;
			$cn = $cliente ? (string)($cliente->get('razaosocial') ?? $cliente->get('nome') ?? '') : '—';
			$v = (float)($f->get('valor') ?? 0);
			$venc = $f->get('vencimento');
			$status = strtolower((string)($f->get('status') ?? ''));
			$pago = strpos($status, 'pag') !== false || $f->get('dtretorno') instanceof \DateTimeInterface;
			$vencida = !$pago && $venc instanceof \DateTimeInterface && $venc < $now;
			$state = $pago ? 'paga' : ($vencida ? 'vencida' : 'pend');
			$kpi[$state]++;
			$kpi['total_valor'] += $v;
			$items[] = [
				'id' => (int)$f->get('id'),
				'numero' => (string)($f->get('nro') ?? sprintf('FAT-%05d', (int)$f->get('id'))),
				'cliente' => $cn,
				'referente' => (string)($f->get('referente') ?? ''),
				'valor' => $v,
				'vencimento' => $venc,
				'status' => $state,
			];
		}

		$this->set([
			'title' => __('Contas a Receber'),
			'erpNavActive' => 'titulos',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Financeiro'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista']],
				['label' => __('Contas a Receber'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'crKpi' => $kpi,
			'crItems' => $items,
		]);

		return $this->render('titulos');
	}

	/**
	 * pg-contas-pagar — financeiro_lancamentos do tipo despesa.
	 */
	public function contasPagar() {
		$empresa = (int)$this->Auth->user('idempresa');
		$now = Time::now();
		$rows = [];
		try {
			$rows = $this->FinanceiroLancamentos->find()
				->where(['FinanceiroLancamentos.idempresa' => $empresa])
				->order(['FinanceiroLancamentos.data_vencimento' => 'DESC'])
				->limit(150)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$kpi = ['pend' => 0, 'paga' => 0, 'vencida' => 0, 'total_valor' => 0.0];
		$items = [];
		foreach ($rows as $l) {
			$tipo = strtolower((string)($l->get('tipo') ?? ''));
			$isDesp = strpos($tipo, 'desp') !== false || strpos($tipo, 'pag') !== false || $tipo === 'p';
			$v = (float)($l->get('valor') ?? 0);
			$venc = $l->get('data_vencimento');
			$status = strtolower((string)($l->get('status') ?? ''));
			$baixa = $l->get('data_baixa');
			$pago = strpos($status, 'pag') !== false || $baixa instanceof \DateTimeInterface;
			$vencida = !$pago && $venc instanceof \DateTimeInterface && $venc < $now;
			$state = $pago ? 'paga' : ($vencida ? 'vencida' : 'pend');
			$kpi[$state]++;
			if ($isDesp) {
				$kpi['total_valor'] += $v;
			}
			$items[] = [
				'id' => (int)$l->get('id'),
				'descricao' => (string)($l->get('descricao') ?? ''),
				'valor' => $v,
				'tipo' => $tipo,
				'is_despesa' => $isDesp,
				'vencimento' => $venc,
				'status' => $state,
			];
		}

		$this->set([
			'title' => __('Contas a Pagar'),
			'erpNavActive' => 'contas-pagar',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Financeiro'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista']],
				['label' => __('Contas a Pagar'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'cpKpi' => $kpi,
			'cpItems' => $items,
		]);

		return $this->render('contas_pagar');
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
		}
		$allowed = ['nfe', 'dre', 'relatorios-fin', 'orc-faturamento', 'orc-cobranca', 'cobranca'];
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

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
			$set['useChartJs'] = true;
			$set['dreData'] = $this->buildDrePayload();
			$this->set($set);

			return $this->render('dre');
		}

		if ($page === 'nfe') {
			$set += $this->buildNfePayload();
			$this->set($set);

			return $this->render('nfe');
		}

		$this->set($set);

		return $this->render('placeholder');
	}

	/**
	 * NF-e/NFS-e — KPIs por status + tabela das últimas notas (fiscal_notas).
	 *
	 * @return array<string,mixed>
	 */
	protected function buildNfePayload(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$kpi = ['emitidas' => 0, 'autorizadas' => 0, 'rejeitadas' => 0, 'canceladas' => 0, 'valor_total' => 0.0];
		$items = [];
		try {
			$tbl = \Cake\ORM\TableRegistry::getTableLocator()->get('FiscalNotas');
			$rows = $tbl->find()
				->where(['FiscalNotas.idempresa' => $empresa])
				->order(['FiscalNotas.data_emissao' => 'DESC'])
				->limit(80)
				->all();
			foreach ($rows as $n) {
				$kpi['emitidas']++;
				$st = strtolower((string)$n->get('status'));
				if (strpos($st, 'autoriz') !== false) {
					$kpi['autorizadas']++;
				} elseif (strpos($st, 'rejeit') !== false) {
					$kpi['rejeitadas']++;
				} elseif (strpos($st, 'cancel') !== false) {
					$kpi['canceladas']++;
				}
				$v = (float)$n->get('valor_total');
				$kpi['valor_total'] += $v;
				$items[] = [
					'id' => (int)$n->get('id'),
					'numero' => (string)$n->get('numero'),
					'serie' => (string)$n->get('serie'),
					'modelo' => (string)$n->get('modelo'),
					'emissao' => $n->get('data_emissao'),
					'destinatario_id' => (int)$n->get('idcliente'),
					'valor' => $v,
					'status' => (string)$n->get('status'),
					'chave' => (string)$n->get('chave_acesso'),
					'motivo_rejeicao' => (string)$n->get('motivo_rejeicao'),
				];
			}
		} catch (\Throwable $e) {
		}

		return [
			'nfeKpi' => $kpi,
			'nfeItems' => $items,
		];
	}

	/**
	 * Receita/despesa/resultado por mês nos últimos 6 meses.
	 *
	 * @return array<string,array<int,float|string>>
	 */
	protected function buildDrePayload(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$now = \Cake\I18n\Time::now();
		$labels = [];
		$receita = [];
		$despesa = [];
		$resultado = [];
		for ($i = 5; $i >= 0; $i--) {
			$ref = $now->copy()->subMonths($i);
			$start = $ref->startOfMonth();
			$end = $ref->endOfMonth();
			$labels[] = $start->i18nFormat('MMM/yy');

			$rec = 0.0;
			try {
				foreach ($this->Faturas->find()
					->where(['Faturas.idempresa' => $empresa, 'Faturas.dtretorno >=' => $start, 'Faturas.dtretorno <=' => $end])
					->all() as $f) {
					$rec += (float)($f->get('valor') ?? 0);
				}
			} catch (\Throwable $e) {
			}
			$receita[] = round($rec, 2);

			$desp = 0.0;
			try {
				foreach ($this->FinanceiroLancamentos->find()
					->where(['FinanceiroLancamentos.idempresa' => $empresa, 'FinanceiroLancamentos.data_baixa >=' => $start, 'FinanceiroLancamentos.data_baixa <=' => $end])
					->all() as $l) {
					$tipo = strtolower((string)($l->get('tipo') ?? ''));
					if (strpos($tipo, 'desp') !== false || strpos($tipo, 'pag') !== false || $tipo === 'p') {
						$desp += (float)($l->get('valor') ?? 0);
					}
				}
			} catch (\Throwable $e) {
			}
			$despesa[] = round($desp, 2);
			$resultado[] = round($rec - $desp, 2);
		}

		return [
			'labels' => $labels,
			'receita' => $receita,
			'despesa' => $despesa,
			'resultado' => $resultado,
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
