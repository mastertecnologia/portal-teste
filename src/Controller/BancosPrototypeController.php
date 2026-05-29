<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Utility\FinanceiroBancosCatalogo;
use App\Utility\FinanceiroBancosPrototypeUi;
use App\Utility\FinanceiroTransferenciasPrototypeBuilder;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Utility\Hash;

/**
 * Bancos — protótipo (mockup pg-bancos, pg-contas, pg-extrato, pg-conciliacao,
 * pg-transferencias, pg-fluxo-caixa).
 */
class BancosPrototypeController extends AppController {

	use ErpPrototypeRbacTrait;

	/** @var bool */
	protected $financeiroExtratoDisponivel = false;

	public function initialize() {
		parent::initialize();
		$this->loadModel('FinanceiroBancos');
		$this->loadModel('Faturas');
		$this->loadModel('FinanceiroLancamentos');
		try {
			$this->loadModel('FinanceiroExtratoBancario');
			$this->financeiroExtratoDisponivel = true;
		} catch (\Throwable $e) {
			$this->financeiroExtratoDisponivel = false;
		}
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
	 * pg-bancos — dashboard consolidado.
	 */
	public function lista() {
		$ctx = $this->buildBancosContext();
		$this->set([
			'title' => __('Bancos · Visão consolidada'),
			'erpNavActive' => 'bancos',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Bancos'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'bcItems' => $ctx['items'],
			'bcKpi' => $ctx['kpi'],
			'chart7d' => $ctx['chart7d'],
			'distribuicao' => $ctx['distribuicao'],
			'ultimosMov' => $ctx['ultimosMov'],
			'bancosCatalogo' => FinanceiroBancosCatalogo::todos(),
			'abrirModalConta' => $this->request->getQuery('nova') === '1',
		]);

		return $this->render('dashboard');
	}

	/**
	 * pg-contas — lista tabular + integrações.
	 */
	public function contas() {
		$ctx = $this->buildBancosContext();
		$this->set([
			'title' => __('Contas Bancárias'),
			'erpNavActive' => 'contas',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Bancos'), 'url' => ['controller' => 'BancosPrototype', 'action' => 'lista']],
				['label' => __('Contas Bancárias'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'bcItems' => $ctx['items'],
			'bcKpi' => $ctx['kpi'],
			'integracoes' => $ctx['integracoes'],
			'bancosCatalogo' => FinanceiroBancosCatalogo::todos(),
			'abrirModalConta' => $this->request->getQuery('nova') === '1',
		]);

		return $this->render('contas');
	}

	/**
	 * POST — cadastro de conta bancária (modal Nova conta).
	 */
	public function salvarConta() {
		$this->request->allowMethod(['post']);
		$idempresa = (int)$this->Auth->user('idempresa');
		$data = $this->_normalizarDadosBancoModal((array)$this->request->getData());
		$data['idempresa'] = $idempresa;
		$data['ativo'] = true;

		if (empty($data['nome']) && !empty($data['codigo_banco'])) {
			$catalogo = FinanceiroBancosCatalogo::porCodigo($data['codigo_banco']);
			if (!empty($catalogo)) {
				$data['nome'] = $catalogo['nome'];
				$data['numero_banco'] = $data['numero_banco'] ?: $catalogo['codigo'];
				$data['cnab'] = $data['cnab'] ?: $catalogo['cnab'];
			}
		}

		$banco = $this->FinanceiroBancos->newEntity($data);
		if ($this->FinanceiroBancos->save($banco)) {
			$this->Flash->success(__('Conta bancária cadastrada com sucesso.'));

			return $this->redirect(['action' => 'view', 'contas']);
		}

		$this->Flash->error(__('Não foi possível salvar a conta. Verifique os campos obrigatórios.'));

		return $this->redirect(['action' => 'view', 'contas', '?' => ['nova' => '1']]);
	}

	/**
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		if ($page === 'contas') {
			return $this->contas();
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
	 * GET /bancos-prototype/extrato/export.csv — exporta extrato filtrado.
	 */
	public function exportExtratoCsv() {
		$empresa = (int)$this->Auth->user('idempresa');
		$q = $this->request->getQueryParams();
		$dias = (int)($q['dias'] ?? 30);
		if ($dias <= 0 || $dias > 365) { $dias = 30; }
		$desde = \Cake\I18n\Time::now()->subDays($dias);
		$where = ['FinanceiroExtratoBancario.idempresa' => $empresa, 'FinanceiroExtratoBancario.data >=' => $desde];
		if (!empty($q['conta'])) { $where['FinanceiroExtratoBancario.conta_bancaria'] = (string)$q['conta']; }
		if (!empty($q['tipo']) && in_array($q['tipo'], ['c', 'd'], true)) {
			$where['FinanceiroExtratoBancario.tipo'] = strtoupper((string)$q['tipo']);
		}
		try {
			$ext = \Cake\ORM\TableRegistry::getTableLocator()->get('FinanceiroExtratoBancario');
			$rows = $ext->find()->where($where)->order(['FinanceiroExtratoBancario.data' => 'DESC'])->limit(10000)->all();
		} catch (\Throwable $e) {
			$rows = [];
		}
		$this->autoRender = false;
		$fname = 'extrato-' . date('Ymd-His') . '.csv';
		$this->response = $this->response
			->withType('text/csv')
			->withHeader('Content-Disposition', 'attachment; filename="' . $fname . '"');
		$out = fopen('php://temp', 'w+');
		fwrite($out, "\xEF\xBB\xBF");
		fputcsv($out, ['Data', 'Descrição', 'Conta', 'Tipo', 'Valor', 'Conciliado'], ';');
		foreach ($rows as $r) {
			$tipo = strtolower((string)$r->get('tipo'));
			fputcsv($out, [
				$r->get('data') instanceof \DateTimeInterface ? $r->get('data')->format('d/m/Y') : '',
				(string)$r->get('descricao'),
				(string)$r->get('conta_bancaria'),
				$tipo === 'c' ? 'Crédito' : ($tipo === 'd' ? 'Débito' : $tipo),
				number_format((float)$r->get('valor'), 2, ',', '.'),
				(int)$r->get('conciliado') === 1 || (int)$r->get('financeiro_lancamento_id') > 0 ? 'Sim' : 'Não',
			], ';');
		}
		rewind($out);

		return $this->response->withStringBody(stream_get_contents($out));
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
	 * POST /bancos-prototype/rejeitar-match — registra rejeição (não vincula).
	 * Mantém o movimento como pendente e adiciona log no campo descricao
	 * (sufixo "[NO-MATCH:lid=X]") para próximos ciclos pularem aquela sugestão.
	 */
	public function rejeitarMatch() {
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
				$this->Flash->error(__('Movimento fora do escopo.'));

				return $this->redirect(['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao']);
			}
			$desc = (string)$row->get('descricao');
			$marker = '[NO-MATCH:lid=' . $lid . ']';
			if (strpos($desc, $marker) === false) {
				$row->set('descricao', trim($desc . ' ' . $marker));
				$ext->save($row);
			}
			$this->Flash->info(__('Match #{0} marcado como não correspondente; o sistema não vai sugerir de novo.', $lid));
		} catch (\Throwable $e) {
			$this->Flash->error(__('Erro: {0}', $e->getMessage()));
		}

		return $this->redirect(['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao']);
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
					$valorAbs = abs($valor);
					$data = $e->get('data');
					$descExt = (string)$e->get('descricao');
					$conciliado = (int)$e->get('conciliado') === 1 || (int)$e->get('financeiro_lancamento_id') > 0;
					$matchSuggest = null;
					if (!$conciliado && $data instanceof \DateTimeInterface) {
						$ini = $data->copy()->subDays(5);
						$fim = $data->copy()->addDays(5);
						$tol = max(1.0, $valorAbs * 0.01);
						// IDs já rejeitados manualmente (marker no descritivo: [NO-MATCH:lid=X])
						$rejeitados = [];
						if (preg_match_all('/\[NO-MATCH:lid=(\d+)\]/', $descExt, $mm)) {
							$rejeitados = array_map('intval', $mm[1]);
						}
						$candidatos = $lan->find()
							->where([
								'FinanceiroLancamentos.idempresa' => $empresa,
								'FinanceiroLancamentos.data_lancamento >=' => $ini,
								'FinanceiroLancamentos.data_lancamento <=' => $fim,
							])
							->all();
						$bestScore = 0.0;
						foreach ($candidatos as $c) {
							$cid = (int)$c->get('id');
							if (in_array($cid, $rejeitados, true)) {
								continue;
							}
							$vc = abs((float)$c->get('valor'));
							$diff = abs($vc - $valorAbs);
							if ($diff > $tol) {
								continue;
							}
							// Score: 60 (valor) + 0..30 (data proximity) + 0..10 (texto)
							$score = 60.0;
							$dt = $c->get('data_lancamento');
							if ($dt instanceof \DateTimeInterface) {
								$days = abs((int)$data->diffInDays($dt));
								$score += max(0, 30 - $days * 5);
							}
							$descLan = (string)$c->get('descricao');
							if ($descExt !== '' && $descLan !== '') {
								$pct = 0.0;
								similar_text(strtoupper($descExt), strtoupper($descLan), $pct);
								$score += min(10, $pct / 10);
							}
							if ($score > $bestScore) {
								$bestScore = $score;
								$matchSuggest = [
									'id' => (int)$c->get('id'),
									'descricao' => $descLan,
									'data' => $dt,
									'valor' => (float)$c->get('valor'),
									'score' => round($score),
									'diff_valor' => round($diff, 2),
								];
							}
						}
					}
					if ($conciliado) {
						$kpi['conciliados']++;
						$status = 'conciliado';
					} elseif ($matchSuggest !== null && (int)$matchSuggest['score'] >= 70) {
						$kpi['divergentes']++;
						$status = 'sugerido';
					} else {
						$kpi['pendentes']++;
						$status = 'pendente';
					}
					$items[] = [
						'id' => (int)$e->get('id'),
						'data' => $data,
						'descricao' => $descExt,
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
	 * @return array{items:array<int,array<string,mixed>>,kpi:array<string,mixed>,chart7d:array<string,mixed>,distribuicao:array<int,array<string,mixed>>,ultimosMov:array<int,array<string,mixed>>,integracoes:array<string,mixed>}
	 */
	protected function buildBancosContext(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$rows = [];
		try {
			$rows = $this->FinanceiroBancos->find()
				->where(['FinanceiroBancos.idempresa' => $empresa])
				->order(['FinanceiroBancos.nome' => 'ASC'])
				->limit(80)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$movPorBanco = $this->_resumoMovimentosPorBanco($empresa, $rows);
		$extratoPorBanco = $this->_resumoExtratoPorBanco($empresa, $rows);
		$hoje = \Cake\I18n\Time::now();
		$inicioHoje = $hoje->copy()->startOfDay();
		$fimHoje = $hoje->copy()->endOfDay();

		$items = [];
		$totalAtivas = 0;
		$saldoTotal = 0.0;
		$ultimaSyncGlobal = null;

		foreach ($rows as $b) {
			$id = (int)$b->get('id');
			$ativo = (bool)$b->get('ativo');
			if ($ativo) {
				$totalAtivas++;
			}
			$codigo = (string)($b->get('codigo_banco') ?? $b->get('numero_banco') ?? '');
			$nome = (string)($b->get('nome') ?? '');
			$brand = FinanceiroBancosPrototypeUi::branding($codigo, $nome);
			[$agFmt, $ccFmt] = FinanceiroBancosPrototypeUi::formatAgenciaConta($b);
			$mov = $movPorBanco[$id] ?? ['receber' => 0.0, 'recebido' => 0.0, 'pagar' => 0.0, 'pago' => 0.0];
			$saldo = (float)$mov['receber'] + (float)$mov['recebido'] - (float)$mov['pagar'] - (float)$mov['pago'];
			$saldoTotal += $saldo;
			$ext = $extratoPorBanco[$id] ?? ['ultima' => null, 'entradas_hoje' => 0.0, 'saidas_hoje' => 0.0];
			$ultima = $ext['ultima'];
			if ($ultima instanceof \DateTimeInterface) {
				if ($ultimaSyncGlobal === null || $ultima > $ultimaSyncGlobal) {
					$ultimaSyncGlobal = $ultima;
				}
			}
			$syncLabel = $this->_labelUltimaSync($ultima, $hoje);
			$syncStale = $ultima instanceof \DateTimeInterface && $ultima < $hoje->copy()->subHours(3);
			$variacaoHoje = (float)$ext['entradas_hoje'] - (float)$ext['saidas_hoje'];
			$tipoLabel = FinanceiroBancosPrototypeUi::tipoContaLabel((string)$b->get('observacoes'));
			$tipoKind = 'aprov';
			if (stripos($tipoLabel, 'invest') !== false) {
				$tipoKind = 'pendente';
			} elseif (stripos($tipoLabel, 'cooper') !== false) {
				$tipoKind = 'prod';
			}

			$items[] = [
				'id' => $id,
				'nome' => $nome,
				'codigo' => $codigo,
				'codigo_fmt' => str_pad(ltrim($codigo, '0') ?: $codigo, 3, '0', STR_PAD_LEFT),
				'agencia' => $agFmt,
				'conta' => $ccFmt,
				'carteira' => (string)($b->get('carteira') ?? ''),
				'convenio' => (string)($b->get('convenio') ?? ''),
				'ativo' => $ativo,
				'saldo' => $saldo,
				'variacao_hoje' => $variacaoHoje,
				'ultima_sync' => $ultima,
				'sync_label' => $syncLabel,
				'sync_stale' => $syncStale,
				'tipo_label' => $tipoLabel,
				'tipo_kind' => $tipoKind,
				'brand' => $brand,
				'conta_extrato' => $this->_formatarContaBanco($b),
			];
		}

		usort($items, static function ($a, $b) {
			return ($b['saldo'] <=> $a['saldo']);
		});

		$distribuicao = [];
		foreach ($items as $it) {
			$pct = $saldoTotal > 0 ? round(100 * (float)$it['saldo'] / $saldoTotal, 1) : 0.0;
			$distribuicao[] = [
				'nome' => $it['nome'],
				'saldo' => (float)$it['saldo'],
				'pct' => $pct,
				'bar' => $it['brand']['bar'],
			];
		}

		$kpiHoje = $this->_kpiExtratoHoje($empresa, $inicioHoje, $fimHoje);
		$conc = $this->buildConciliacaoPayload();
		$pendValor = 0.0;
		foreach ($conc['concItems'] ?? [] as $row) {
			if (($row['status'] ?? '') === 'pendente' || ($row['status'] ?? '') === 'sugerido') {
				$pendValor += abs((float)($row['valor'] ?? 0));
			}
		}
		$aPagar7d = $this->_totalPagarProximosDias($empresa, 7);

		$kpi = [
			'total' => count($rows),
			'ativas' => $totalAtivas,
			'inativas' => count($rows) - $totalAtivas,
			'saldo_total' => $saldoTotal,
			'entradas_hoje' => $kpiHoje['entradas'],
			'saidas_hoje' => $kpiHoje['saidas'],
			'mov_entradas_hoje' => $kpiHoje['mov_entradas'],
			'mov_saidas_hoje' => $kpiHoje['mov_saidas'],
			'variacao_hoje' => $kpiHoje['entradas'] - $kpiHoje['saidas'],
			'pendentes_valor' => $pendValor,
			'pendentes_count' => (int)($conc['concKpi']['pendentes'] ?? 0) + (int)($conc['concKpi']['divergentes'] ?? 0),
			'a_pagar_7d' => $aPagar7d['valor'],
			'a_pagar_count' => $aPagar7d['count'],
			'ultima_sync' => $ultimaSyncGlobal,
			'ultima_sync_label' => $this->_labelUltimaSync($ultimaSyncGlobal, $hoje, true),
		];

		$cnabCount = 0;
		foreach ($rows as $b) {
			if (trim((string)$b->get('carteira')) !== '' || trim((string)$b->get('convenio')) !== '') {
				$cnabCount++;
			}
		}

		return [
			'items' => $items,
			'kpi' => $kpi,
			'chart7d' => $this->_chartMovimentacao7d($empresa),
			'distribuicao' => $distribuicao,
			'ultimosMov' => $this->_ultimosMovimentos($empresa, 5),
			'integracoes' => [
				'cnab_bancos' => $cnabCount,
				'extrato_auto' => $this->financeiroExtratoDisponivel,
				'contas_ativas' => $totalAtivas,
			],
		];
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	protected function _normalizarDadosBancoModal(array $data): array {
		$codigo = trim((string)Hash::get($data, 'codigo_banco', ''));
		[$ag, $dgAg] = FinanceiroBancosPrototypeUi::splitNumeroDigito((string)Hash::get($data, 'agencia', ''));
		[$cc, $dgCc] = FinanceiroBancosPrototypeUi::splitNumeroDigito((string)Hash::get($data, 'conta', ''));
		$apelido = trim((string)Hash::get($data, 'apelido', ''));
		$tipo = trim((string)Hash::get($data, 'tipo_conta', ''));
		$obs = [];
		if ($tipo !== '') {
			$obs[] = 'tipo_conta:' . $tipo;
		}
		$saldoIni = $this->_parseBrlCampoModal((string)Hash::get($data, 'saldo_inicial', ''));
		if ($saldoIni !== null) {
			$obs[] = 'saldo_inicial:' . $saldoIni;
		}
		$limite = $this->_parseBrlCampoModal((string)Hash::get($data, 'limite_cheque_especial', ''));
		if ($limite !== null) {
			$obs[] = 'limite_cheque:' . $limite;
		}
		$contaContabil = trim((string)Hash::get($data, 'conta_contabil', ''));
		if ($contaContabil !== '') {
			$obs[] = 'conta_contabil:' . $contaContabil;
		}
		$especie = trim((string)Hash::get($data, 'especie_titulo', ''));
		if ($especie !== '') {
			$obs[] = 'especie_titulo:' . $especie;
		}
		if (!empty($data['integracao_cnab'])) {
			$obs[] = 'integracao:cnab240';
		}
		if (!empty($data['integracao_ofx'])) {
			$obs[] = 'integracao:ofx';
		}
		if (!empty($data['integracao_pix'])) {
			$obs[] = 'integracao:pix';
		}

		$proxTitulo = preg_replace('/\D/', '', (string)Hash::get($data, 'proximo_titulo', ''));
		$proxRemessa = (int)Hash::get($data, 'proxima_remessa', 0);
		if ($proxTitulo !== '') {
			$proxRemessa = (int)$proxTitulo;
		}
		if ($proxRemessa <= 0) {
			$proxRemessa = 1;
		}

		$out = [
			'codigo_banco' => $codigo,
			'numero_banco' => $codigo,
			'cnab' => trim((string)Hash::get($data, 'cnab', '')),
			'nome' => $apelido,
			'numero_agencia' => $ag,
			'digito_agencia' => $dgAg,
			'numero_conta' => $cc,
			'digito_conta' => $dgCc,
			'carteira' => trim((string)Hash::get($data, 'carteira', '')),
			'convenio' => trim((string)Hash::get($data, 'convenio', '')),
			'proxima_remessa' => $proxRemessa,
			'cnab_tipo' => '240',
			'observacoes' => implode(' | ', $obs),
			'ativo' => true,
		];
		if ($out['cnab'] === '' && $codigo !== '') {
			$cat = FinanceiroBancosCatalogo::porCodigo($codigo);
			if (!empty($cat['cnab'])) {
				$out['cnab'] = (string)$cat['cnab'];
			}
		}
		return $out;
	}

	/**
	 * Converte "R$ 1.234,56" ou "1234.56" para decimal com ponto.
	 */
	protected function _parseBrlCampoModal(string $raw): ?string {
		$raw = trim(str_replace(['R$', ' '], '', $raw));
		if ($raw === '') {
			return null;
		}
		if (strpos($raw, ',') !== false) {
			$raw = str_replace('.', '', $raw);
			$raw = str_replace(',', '.', $raw);
		}
		if (!is_numeric($raw)) {
			return null;
		}

		return number_format((float)$raw, 2, '.', '');
	}

	/**
	 * @param array<int,object> $bancos
	 * @return array<int,array{receber:float,recebido:float,pagar:float,pago:float}>
	 */
	protected function _resumoMovimentosPorBanco(int $idempresa, array $bancos): array {
		$out = [];
		foreach ($bancos as $banco) {
			$out[(int)$banco->get('id')] = [
				'receber' => 0.0,
				'recebido' => 0.0,
				'pagar' => 0.0,
				'pago' => 0.0,
			];
		}
		try {
			$rows = $this->FinanceiroLancamentos->find()
				->where(['FinanceiroLancamentos.idempresa' => $idempresa])
				->all();
			foreach ($rows as $row) {
				$bid = (int)$row->get('financeiro_banco_id');
				if (!isset($out[$bid])) {
					continue;
				}
				$valor = (float)$row->get('valor');
				$tipo = (string)$row->get('tipo');
				$status = (string)$row->get('status');
				if ($tipo === 'receita' && $status === 'aberto') {
					$out[$bid]['receber'] += $valor;
				} elseif ($tipo === 'receita' && $status === 'recebido') {
					$out[$bid]['recebido'] += $valor;
				} elseif ($tipo === 'despesa' && $status === 'aberto') {
					$out[$bid]['pagar'] += $valor;
				} elseif ($tipo === 'despesa' && $status === 'pago') {
					$out[$bid]['pago'] += $valor;
				}
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param array<int,object> $bancos
	 * @return array<int,array{ultima:?\\DateTimeInterface,entradas_hoje:float,saidas_hoje:float}>
	 */
	protected function _resumoExtratoPorBanco(int $idempresa, array $bancos): array {
		$out = [];
		$hojeIni = \Cake\I18n\Time::now()->startOfDay();
		foreach ($bancos as $banco) {
			$out[(int)$banco->get('id')] = [
				'ultima' => null,
				'entradas_hoje' => 0.0,
				'saidas_hoje' => 0.0,
			];
		}
		if (!$this->financeiroExtratoDisponivel || $bancos === []) {
			return $out;
		}
		try {
			$rows = $this->FinanceiroExtratoBancario->find()
				->where(['FinanceiroExtratoBancario.idempresa' => $idempresa])
				->order(['FinanceiroExtratoBancario.data' => 'DESC'])
				->limit(5000)
				->all();
			foreach ($bancos as $banco) {
				$id = (int)$banco->get('id');
				$refs = $this->_contasReferenciaExtrato($banco);
				if ($refs === []) {
					continue;
				}
				foreach ($rows as $r) {
					$conta = (string)$r->get('conta_bancaria');
					if (!in_array($conta, $refs, true)) {
						continue;
					}
					$dt = $r->get('data');
					if ($dt instanceof \DateTimeInterface) {
						if ($out[$id]['ultima'] === null || $dt > $out[$id]['ultima']) {
							$out[$id]['ultima'] = $dt;
						}
						if ($dt >= $hojeIni) {
							$valor = abs((float)$r->get('valor'));
							$tipo = strtolower((string)$r->get('tipo'));
							$entrada = $tipo === 'c' || $tipo === 'credito' || $tipo === 'cr';
							if ($entrada) {
								$out[$id]['entradas_hoje'] += $valor;
							} else {
								$out[$id]['saidas_hoje'] += $valor;
							}
						}
					}
				}
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param object $banco
	 * @return array<int,string>
	 */
	protected function _contasReferenciaExtrato($banco): array {
		$refs = [];
		$fmt = $this->_formatarContaBanco($banco);
		if ($fmt !== '') {
			$refs[] = $fmt;
			$refs[] = str_replace(' / ', '/', $fmt);
		}
		$nome = trim((string)$banco->get('nome'));
		if ($nome !== '') {
			$refs[] = $nome;
		}

		return array_values(array_unique(array_filter($refs)));
	}

	/**
	 * @param object $banco
	 */
	protected function _formatarContaBanco($banco): string {
		[$ag, $cc] = FinanceiroBancosPrototypeUi::formatAgenciaConta($banco);
		if ($ag === '—' && $cc === '—') {
			return '';
		}
		if ($ag !== '—' && $cc !== '—') {
			return $ag . ' / ' . $cc;
		}

		return $ag !== '—' ? $ag : $cc;
	}

	protected function _labelUltimaSync(?\DateTimeInterface $dt, \DateTimeInterface $agora, bool $longo = false): string {
		if ($dt === null) {
			return __('Sem sync');
		}
		$diffH = (int)floor(($agora->getTimestamp() - $dt->getTimestamp()) / 3600);
		if ($diffH < 1) {
			return $longo
				? __('Hoje {0}', $dt->format('H:i'))
				: __('Hoje {0}', $dt->format('H:i'));
		}
		if ($diffH < 24) {
			return __('{0}h atrás', $diffH);
		}

		return $dt->format('d/m H:i');
	}

	/**
	 * @return array{entradas:float,saidas:float,mov_entradas:int,mov_saidas:int}
	 */
	protected function _kpiExtratoHoje(int $empresa, \DateTimeInterface $ini, \DateTimeInterface $fim): array {
		$kpi = ['entradas' => 0.0, 'saidas' => 0.0, 'mov_entradas' => 0, 'mov_saidas' => 0];
		if (!$this->financeiroExtratoDisponivel) {
			return $kpi;
		}
		try {
			$rows = $this->FinanceiroExtratoBancario->find()
				->where([
					'FinanceiroExtratoBancario.idempresa' => $empresa,
					'FinanceiroExtratoBancario.data >=' => $ini,
					'FinanceiroExtratoBancario.data <=' => $fim,
				])
				->all();
			foreach ($rows as $r) {
				$valor = abs((float)$r->get('valor'));
				$tipo = strtolower((string)$r->get('tipo'));
				$entrada = $tipo === 'c' || $tipo === 'credito' || $tipo === 'cr';
				if ($entrada) {
					$kpi['entradas'] += $valor;
					$kpi['mov_entradas']++;
				} else {
					$kpi['saidas'] += $valor;
					$kpi['mov_saidas']++;
				}
			}
		} catch (\Throwable $e) {
		}

		return $kpi;
	}

	/**
	 * @return array{valor:float,count:int}
	 */
	protected function _totalPagarProximosDias(int $empresa, int $dias): array {
		$total = 0.0;
		$count = 0;
		$fim = \Cake\I18n\Time::now()->addDays($dias)->endOfDay();
		try {
			$rows = $this->FinanceiroLancamentos->find()
				->where([
					'FinanceiroLancamentos.idempresa' => $empresa,
					'FinanceiroLancamentos.tipo' => 'despesa',
					'FinanceiroLancamentos.status' => 'aberto',
					'FinanceiroLancamentos.data_vencimento <=' => $fim,
				])
				->all();
			foreach ($rows as $r) {
				$total += (float)$r->get('valor');
				$count++;
			}
		} catch (\Throwable $e) {
		}

		return ['valor' => $total, 'count' => $count];
	}

	/**
	 * @return array{labels:array<int,string>,entradas:array<int,float>,saidas:array<int,float>,max:float}
	 */
	protected function _chartMovimentacao7d(int $empresa): array {
		$labels = [];
		$entradas = [];
		$saidas = [];
		$max = 1.0;
		$now = \Cake\I18n\Time::now();
		$diasSem = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
		for ($i = 6; $i >= 0; $i--) {
			$day = $now->copy()->subDays($i)->startOfDay();
			$dayEnd = $day->copy()->endOfDay();
			$labels[] = $i === 0 ? __('Hoje') : $diasSem[(int)$day->format('w')];
			$e = 0.0;
			$s = 0.0;
			if ($this->financeiroExtratoDisponivel) {
				try {
					$rows = $this->FinanceiroExtratoBancario->find()
						->where([
							'FinanceiroExtratoBancario.idempresa' => $empresa,
							'FinanceiroExtratoBancario.data >=' => $day,
							'FinanceiroExtratoBancario.data <=' => $dayEnd,
						])
						->all();
					foreach ($rows as $r) {
						$valor = abs((float)$r->get('valor'));
						$tipo = strtolower((string)$r->get('tipo'));
						if ($tipo === 'c' || $tipo === 'credito' || $tipo === 'cr') {
							$e += $valor;
						} else {
							$s += $valor;
						}
					}
				} catch (\Throwable $ex) {
				}
			}
			$entradas[] = round($e, 2);
			$saidas[] = round($s, 2);
			$max = max($max, $e, $s);
		}

		return compact('labels', 'entradas', 'saidas', 'max');
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function _ultimosMovimentos(int $empresa, int $limit): array {
		$items = [];
		if (!$this->financeiroExtratoDisponivel) {
			return $items;
		}
		try {
			$rows = $this->FinanceiroExtratoBancario->find()
				->where(['FinanceiroExtratoBancario.idempresa' => $empresa])
				->order(['FinanceiroExtratoBancario.data' => 'DESC'])
				->limit($limit)
				->all();
			foreach ($rows as $r) {
				$valor = abs((float)$r->get('valor'));
				$tipo = strtolower((string)$r->get('tipo'));
				$entrada = $tipo === 'c' || $tipo === 'credito' || $tipo === 'cr';
				$conc = (int)$r->get('conciliado') === 1 || (int)$r->get('financeiro_lancamento_id') > 0;
				$dt = $r->get('data');
				$items[] = [
					'data' => $dt,
					'data_label' => $dt instanceof \DateTimeInterface ? $dt->format('d/m H:i') : '',
					'descricao' => (string)$r->get('descricao'),
					'conta' => (string)$r->get('conta_bancaria'),
					'entrada' => $entrada,
					'valor' => $valor,
					'conciliado' => $conc,
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
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
