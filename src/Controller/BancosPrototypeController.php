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

		if ($page === 'extrato') {
			$set += $this->buildExtratoPayload();
			$this->set($set);

			return $this->render('extrato');
		}

		if ($page === 'transferencias') {
			$set += $this->buildTransferenciasPayload();
			$this->set($set);

			return $this->render('transferencias');
		}

		$this->set($set);

		return $this->render('placeholder');
	}

	/**
	 * Extrato bancário com filtros (período, conta, tipo).
	 *
	 * @return array<string,mixed>
	 */
	protected function buildExtratoPayload(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$query = $this->request->getQueryParams();
		$filtroTipo = (string)($query['tipo'] ?? '');
		$filtroConta = trim((string)($query['conta'] ?? ''));
		$diasFiltro = (int)($query['dias'] ?? 30);
		if ($diasFiltro <= 0 || $diasFiltro > 365) {
			$diasFiltro = 30;
		}

		$items = [];
		$kpi = ['entradas' => 0.0, 'saidas' => 0.0, 'pendentes' => 0, 'total_mov' => 0];
		$contas = [];

		$schema = null;
		try {
			$schema = \Cake\ORM\TableRegistry::getTableLocator()->get('Empresas')->getConnection()->getSchemaCollection()->listTables();
		} catch (\Throwable $e) {
		}

		if ($schema !== null && in_array('financeiro_extrato_bancario', $schema, true)) {
			try {
				$ext = \Cake\ORM\TableRegistry::getTableLocator()->get('FinanceiroExtratoBancario');
				$desde = \Cake\I18n\Time::now()->subDays($diasFiltro);
				$where = [
					'FinanceiroExtratoBancario.idempresa' => $empresa,
					'FinanceiroExtratoBancario.data >=' => $desde,
				];
				if ($filtroConta !== '') {
					$where['FinanceiroExtratoBancario.conta_bancaria'] = $filtroConta;
				}
				if (in_array($filtroTipo, ['c', 'd'], true)) {
					$where['FinanceiroExtratoBancario.tipo'] = $filtroTipo === 'c' ? 'C' : 'D';
				}
				$rows = $ext->find()
					->where($where)
					->order(['FinanceiroExtratoBancario.data' => 'DESC'])
					->limit(150)
					->all();
				foreach ($rows as $r) {
					$valor = (float)$r->get('valor');
					$tipo = strtolower((string)$r->get('tipo'));
					$isEntrada = $tipo === 'c' || $tipo === 'credito' || $tipo === 'cr';
					if ($isEntrada) {
						$kpi['entradas'] += abs($valor);
					} else {
						$kpi['saidas'] += abs($valor);
					}
					if ((int)$r->get('conciliado') === 0 && (int)$r->get('financeiro_lancamento_id') === 0) {
						$kpi['pendentes']++;
					}
					$kpi['total_mov']++;
					$items[] = [
						'id' => (int)$r->get('id'),
						'data' => $r->get('data'),
						'descricao' => (string)$r->get('descricao'),
						'tipo' => $tipo,
						'is_entrada' => $isEntrada,
						'valor' => abs($valor),
						'conta' => (string)$r->get('conta_bancaria'),
						'origem' => (string)$r->get('origem'),
						'fitid' => (string)$r->get('fitid'),
						'conciliado' => (int)$r->get('conciliado') === 1 || (int)$r->get('financeiro_lancamento_id') > 0,
					];
				}

				$rowsContas = $ext->find()
					->select(['conta_bancaria'])
					->where(['FinanceiroExtratoBancario.idempresa' => $empresa])
					->group(['conta_bancaria'])
					->extract('conta_bancaria')
					->toList();
				foreach ($rowsContas as $c) {
					$c = (string)$c;
					if ($c !== '') {
						$contas[] = $c;
					}
				}
			} catch (\Throwable $e) {
			}
		}

		return [
			'extKpi' => $kpi,
			'extItems' => $items,
			'extContas' => $contas,
			'extFiltros' => [
				'tipo' => $filtroTipo,
				'conta' => $filtroConta,
				'dias' => $diasFiltro,
			],
		];
	}

	/**
	 * Remessas bancárias (CNAB) — usado como base para PIX/transferências.
	 *
	 * @return array<string,mixed>
	 */
	protected function buildTransferenciasPayload(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$items = [];
		$kpi = ['geradas' => 0, 'enviadas' => 0, 'processadas' => 0, 'valor_total' => 0.0];

		$schema = null;
		try {
			$schema = \Cake\ORM\TableRegistry::getTableLocator()->get('Empresas')->getConnection()->getSchemaCollection()->listTables();
		} catch (\Throwable $e) {
		}

		if ($schema !== null && in_array('financeiro_remessas', $schema, true)) {
			try {
				$rem = \Cake\ORM\TableRegistry::getTableLocator()->get('FinanceiroRemessas');
				foreach ($rem->find()
					->where(['FinanceiroRemessas.idempresa' => $empresa])
					->order(['FinanceiroRemessas.data_geracao' => 'DESC'])
					->limit(40)
					->all() as $r) {
					$v = (float)$r->get('valor_total');
					$kpi['valor_total'] += $v;
					$st = strtolower((string)$r->get('status'));
					if (strpos($st, 'process') !== false || strpos($st, 'retorn') !== false) {
						$kpi['processadas']++;
					} elseif (strpos($st, 'enviad') !== false) {
						$kpi['enviadas']++;
					} else {
						$kpi['geradas']++;
					}
					$items[] = [
						'id' => (int)$r->get('id'),
						'numero' => (int)$r->get('numero_remessa'),
						'cnab' => (string)$r->get('cnab_layout'),
						'arquivo' => (string)$r->get('nome_arquivo'),
						'data' => $r->get('data_geracao'),
						'titulos' => (int)$r->get('quantidade_titulos'),
						'valor' => $v,
						'status' => (string)$r->get('status'),
					];
				}
			} catch (\Throwable $e) {
			}
		}

		return [
			'tfKpi' => $kpi,
			'tfItems' => $items,
		];
	}

	/**
	 * POST /bancos-prototype/conciliar — aceita match sugerido.
	 * Recebe extrato_id + lancamento_id; vincula e marca como conciliado.
	 */
	public function conciliar() {
		$this->request->allowMethod(['post']);
		$empresa = (int)$this->Auth->user('idempresa');
		$eid = (int)$this->request->getData('extrato_id');
		$lid = (int)$this->request->getData('lancamento_id');
		if ($eid <= 0 || $lid <= 0) {
			$this->Flash->error(__('Dados inválidos.'));

			return $this->redirect(['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao']);
		}
		try {
			$ext = \Cake\ORM\TableRegistry::getTableLocator()->get('FinanceiroExtratoBancario');
			$row = $ext->find()->where(['id' => $eid, 'idempresa' => $empresa])->first();
			if ($row === null) {
				$this->Flash->error(__('Movimento de extrato fora do escopo.'));

				return $this->redirect(['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao']);
			}
			if ((int)$row->get('conciliado') === 1 || (int)$row->get('financeiro_lancamento_id') > 0) {
				$this->Flash->info(__('Movimento já conciliado.'));

				return $this->redirect(['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao']);
			}
			$lan = \Cake\ORM\TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
			$lanRow = $lan->find()->where(['id' => $lid, 'idempresa' => $empresa])->first();
			if ($lanRow === null) {
				$this->Flash->error(__('Lançamento financeiro fora do escopo.'));

				return $this->redirect(['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao']);
			}
			$row->set('financeiro_lancamento_id', (int)$lanRow->get('id'));
			$row->set('conciliado', 1);
			if ($ext->save($row)) {
				$this->Flash->success(__('Movimento conciliado com o lançamento #{0}.', (int)$lanRow->get('id')));
			} else {
				$this->Flash->error(__('Falha ao gravar conciliação.'));
			}
		} catch (\Throwable $e) {
			$this->Flash->error(__('Erro: {0}', $e->getMessage()));
		}

		return $this->redirect(['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao']);
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
