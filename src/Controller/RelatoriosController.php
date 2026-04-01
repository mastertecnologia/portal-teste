<?php
namespace App\Controller;

use Cake\Database\Expression\QueryExpression;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

/**
 * Relatórios e Indicadores (ERP interno).
 */
class RelatoriosController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Tickets');
		$this->loadModel('Clientes');
		$this->loadModel('FinanceiroLancamentos');
		$this->loadModel('Clicontratos');
	}

	public function isAuthorized($user) {
		if ((int)($user['role'] ?? 1) === C_RoleCliente) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		$this->viewBuilder()->setLayout('default');
		$this->set('title', 'Relatórios e Indicadores');

		$aba = $this->_relatoriosAbaSanitizada();
		$f = $this->_relatoriosFiltrosFromRequest();
		$clientesList = $this->_relatoriosClientesList();
		$f = $this->_relatoriosSanitizaFiltrosComListas($f, $clientesList);

		$tecnicosList = $this->_relatoriosTecnicosList();
		if ($f['idtecnico'] !== null && !isset($tecnicosList[$f['idtecnico']])) {
			$f['idtecnico'] = null;
		}

		$contratosList = $this->_relatoriosContratosList($clientesList);
		if ($f['idcontrato'] !== null && !isset($contratosList[$f['idcontrato']])) {
			$f['idcontrato'] = null;
		}
		$f = $this->_relatoriosSanitizaContratoVersusCliente($f);

		$kpis = $this->_relatoriosComputeKpis($f);
		$ticketsAmostra = $this->_relatoriosTicketsAmostra($f, 50);
		$sitLabels = $this->_relatoriosSitLabels();
		$financeiroLinhas = [
			['label' => 'Receita no período (data de lançamento)', 'valor' => $kpis['receita']],
			['label' => 'Valor em atraso (receitas abertas vencidas)', 'valor' => $kpis['inadimplencia']],
		];
		if (!empty($f['idcontrato']) && $f['idcliente'] === null) {
			$cidFin = $this->_relatoriosClienteParaFinanceiro($f);
			if ($cidFin !== null) {
				$nm = $clientesList[$cidFin] ?? ('Cliente #' . $cidFin);
				$financeiroLinhas[] = ['label' => 'Escopo financeiro (cliente do contrato)', 'valor' => $nm];
			}
		}
		$contratosRows = $this->_relatoriosContratosAmostra($clientesList, 15, $f);
		$tecnicosRows = $this->_relatoriosTicketsPorTecnico($f, $tecnicosList);

		$this->set('relatoriosKpis', $kpis);
		$this->set('relatoriosAbaAtiva', $aba);
		$this->set('relatoriosClientesList', $clientesList);
		$this->set('relatoriosTecnicosList', $tecnicosList);
		$this->set('relatoriosContratosList', $contratosList);
		$this->set('relatoriosPeriodoPadrao', $f['periodo_padrao']);
		$this->set('relatoriosPeriodoLabel', $f['periodo_label']);
		$this->set('relatoriosSelCliente', $f['idcliente']);
		$this->set('relatoriosSelTecnico', $f['idtecnico']);
		$this->set('relatoriosSelContrato', $f['idcontrato']);
		$this->set('relatoriosTicketsAmostra', $ticketsAmostra);
		$this->set('relatoriosSitLabels', $sitLabels);
		$this->set('relatoriosFinanceiroLinhas', $financeiroLinhas);
		$this->set('relatoriosContratosRows', $contratosRows);
		$this->set('relatoriosTecnicosRows', $tecnicosRows);
	}

	/**
	 * Exporta resumo dos indicadores (mesmos filtros da tela).
	 */
	public function exportar() {
		$formato = strtolower((string)$this->request->getQuery('formato', 'csv'));
		if (!in_array($formato, ['pdf', 'csv', 'xlsx'], true)) {
			$formato = 'csv';
		}

		$f = $this->_relatoriosFiltrosFromRequest();
		$clientesList = $this->_relatoriosClientesList();
		$f = $this->_relatoriosSanitizaFiltrosComListas($f, $clientesList);
		$tecnicosList = $this->_relatoriosTecnicosList();
		if ($f['idtecnico'] !== null && !isset($tecnicosList[$f['idtecnico']])) {
			$f['idtecnico'] = null;
		}
		$contratosList = $this->_relatoriosContratosList($clientesList);
		if ($f['idcontrato'] !== null && !isset($contratosList[$f['idcontrato']])) {
			$f['idcontrato'] = null;
		}
		$f = $this->_relatoriosSanitizaContratoVersusCliente($f);

		$kpis = $this->_relatoriosComputeKpis($f);
		$rotulos = $this->_relatoriosRotulosFiltros($f, $clientesList, $tecnicosList, $contratosList);

		$redirectQuery = $this->request->getQueryParams();
		unset($redirectQuery['formato']);

		if ($formato === 'pdf') {
			return $this->_relatoriosExportPdf($kpis, $rotulos, $redirectQuery);
		}
		if ($formato === 'csv') {
			return $this->_relatoriosExportCsv($kpis, $rotulos);
		}

		return $this->_relatoriosExportXlsx($kpis, $rotulos);
	}

	protected function _relatoriosAbaSanitizada() {
		$aba = strtolower((string)$this->request->getQuery('aba', 'atendimento'));
		$aba = preg_replace('/[^a-z]/', '', $aba);
		if (!in_array($aba, ['atendimento', 'contratos', 'financeiro', 'tecnicos'], true)) {
			return 'atendimento';
		}

		return $aba;
	}

	protected function _relatoriosParseBrDate($s) {
		if ($s === null) {
			return null;
		}
		$s = trim((string)$s);
		if ($s === '') {
			return null;
		}
		if (!preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $s, $m)) {
			return null;
		}
		$d = (int)$m[1];
		$mo = (int)$m[2];
		$y = (int)$m[3];
		if (!checkdate($mo, $d, $y)) {
			return null;
		}

		return \DateTimeImmutable::createFromFormat('!Y-n-j', sprintf('%04d-%d-%d', $y, $mo, $d));
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function _relatoriosFiltrosFromRequest() {
		$req = $this->request->getQueryParams();
		$iniStr = isset($req['periodo_ini']) ? trim((string)$req['periodo_ini']) : '';
		$fimStr = isset($req['periodo_fim']) ? trim((string)$req['periodo_fim']) : '';
		$dtIni = $this->_relatoriosParseBrDate($iniStr);
		$dtFim = $this->_relatoriosParseBrDate($fimStr);
		$periodoPadrao = false;
		if ($dtIni === null && $dtFim === null) {
			$periodoPadrao = true;
			$dtFim = new \DateTimeImmutable('today');
			$dtIni = $dtFim->modify('-60 days');
		} elseif ($dtIni === null) {
			$dtIni = $dtFim->modify('-60 days');
		} elseif ($dtFim === null) {
			$dtFim = new \DateTimeImmutable('today');
		}
		if ($dtIni > $dtFim) {
			$t = $dtIni;
			$dtIni = $dtFim;
			$dtFim = $t;
		}

		$idCliente = null;
		if (isset($req['idcliente']) && $req['idcliente'] !== '' && ctype_digit((string)$req['idcliente'])) {
			$cid = (int)$req['idcliente'];
			if ($cid > 0) {
				$idCliente = $cid;
			}
		}

		$idTecnico = null;
		if (isset($req['idtecnico']) && $req['idtecnico'] !== '' && ctype_digit((string)$req['idtecnico'])) {
			$tid = (int)$req['idtecnico'];
			if ($tid > 0) {
				$idTecnico = $tid;
			}
		}

		$idContrato = null;
		if (isset($req['idcontrato']) && $req['idcontrato'] !== '' && ctype_digit((string)$req['idcontrato'])) {
			$ctid = (int)$req['idcontrato'];
			if ($ctid > 0) {
				$idContrato = $ctid;
			}
		}

		$periodoLabel = $dtIni->format('d/m/Y') . ' a ' . $dtFim->format('d/m/Y');

		return [
			'created_start' => $dtIni->format('Y-m-d') . ' 00:00:00',
			'created_end' => $dtFim->format('Y-m-d') . ' 23:59:59',
			'period_date_start' => $dtIni->format('Y-m-d'),
			'period_date_end' => $dtFim->format('Y-m-d'),
			'idcliente' => $idCliente,
			'idtecnico' => $idTecnico,
			'idcontrato' => $idContrato,
			'periodo_padrao' => $periodoPadrao,
			'periodo_label' => $periodoLabel,
		];
	}

	/**
	 * @param array<string,mixed> $f
	 * @param array<int,string> $clientesList
	 * @return array<string,mixed>
	 */
	protected function _relatoriosSanitizaFiltrosComListas(array $f, array $clientesList) {
		if ($f['idcliente'] !== null && !isset($clientesList[$f['idcliente']])) {
			$f['idcliente'] = null;
		}

		return $f;
	}

	/**
	 * Cliente e contrato inconsistentes: remove o contrato (mantém o cliente explícito).
	 *
	 * @param array<string,mixed> $f
	 * @return array<string,mixed>
	 */
	protected function _relatoriosSanitizaContratoVersusCliente(array $f) {
		if ($f['idcontrato'] === null || $f['idcliente'] === null) {
			return $f;
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$q = $this->Clicontratos->find()->where(['Clicontratos.id' => $f['idcontrato']]);
		if (in_array('idempresa', $this->Clicontratos->getSchema()->columns(), true)) {
			$q->where(['Clicontratos.idempresa' => $idempresa]);
		}
		$c = $q->first();
		if (!$c || (int)$c->idcliente === (int)$f['idcliente']) {
			return $f;
		}
		$f['idcontrato'] = null;

		return $f;
	}

	/**
	 * Cliente efetivo para lançamentos financeiros: filtro de cliente OU cliente do contrato.
	 *
	 * @param array<string,mixed> $f
	 * @return int|null
	 */
	protected function _relatoriosClienteParaFinanceiro(array $f) {
		if ($f['idcliente'] !== null) {
			return (int)$f['idcliente'];
		}
		if ($f['idcontrato'] === null) {
			return null;
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$q = $this->Clicontratos->find()->where(['Clicontratos.id' => $f['idcontrato']]);
		if (in_array('idempresa', $this->Clicontratos->getSchema()->columns(), true)) {
			$q->where(['Clicontratos.idempresa' => $idempresa]);
		}
		$c = $q->first();
		if (!$c) {
			return null;
		}

		return (int)$c->idcliente;
	}

	/**
	 * @return array<int,string>
	 */
	protected function _relatoriosClientesList() {
		$clientesFis = $this->Clientes->find('all')
			->where(['AND' => ['inativo' => '0', 'tipo' => '1']])
			->order(['nome']);
		$this->Abac->applyToQuery($clientesFis, 'Clientes');
		$clientesJur = $this->Clientes->find('all')
			->where(['AND' => ['inativo' => '0', 'tipo' => '2']])
			->order(['razaosocial']);
		$this->Abac->applyToQuery($clientesJur, 'Clientes');
		$list = [];
		foreach ($clientesJur->all() as $reg) {
			$list[(int)$reg->id] = (string)$reg->razaosocial;
		}
		foreach ($clientesFis->all() as $reg) {
			$list[(int)$reg->id] = (string)$reg->nome;
		}

		return $list;
	}

	/**
	 * @return array<int,string>
	 */
	protected function _relatoriosTecnicosList() {
		$empresa = (int)$this->Auth->user('idempresa');
		$qry = $this->Empresasusers->find('all', ['contain' => ['Users']])
			->where([
				'Empresasusers.idempresa' => $empresa,
				'Users.role' => C_RoleFuncionario,
				'Users.inativo' => 0,
			]);
		$list = [];
		foreach ($qry->order(['Users.name' => 'ASC'])->toArray() as $r) {
			$u = $r->user ?? $r->users ?? null;
			if (!$u) {
				continue;
			}
			$nm = trim((string)($u->name ?? ''));
			if ($nm === '') {
				$nm = trim((string)($u->username ?? ''));
			}
			if ($nm === '') {
				$nm = 'Usuário #' . (int)$u->id;
			}
			$list[(int)$u->id] = $nm;
		}

		return $list;
	}

	/**
	 * @param array<int,string> $clientesList
	 * @return array<int,string>
	 */
	protected function _relatoriosContratosList(array $clientesList) {
		$idempresa = (int)$this->Auth->user('idempresa');
		$cids = array_keys($clientesList);
		if ($cids === []) {
			return [];
		}
		$schema = $this->Clicontratos->getSchema();
		if (!in_array('idempresa', $schema->columns(), true)) {
			return [];
		}
		$rows = $this->Clicontratos->find('all')
			->where(['Clicontratos.idempresa' => $idempresa, 'Clicontratos.idcliente IN' => $cids])
			->order(['Clicontratos.id' => 'DESC'])
			->limit(500)
			->toArray();
		$list = [];
		foreach ($rows as $r) {
			$d = (string)($r->descricao ?? '');
			if (mb_strlen($d) > 55) {
				$d = mb_substr($d, 0, 52) . '…';
			}
			$list[(int)$r->id] = '#' . (int)$r->id . ($d !== '' ? ' — ' . $d : '');
		}

		return $list;
	}

	/**
	 * @param array<string,mixed> $f
	 * @return \Cake\ORM\Query
	 */
	protected function _relatoriosQueryTickets(array $f) {
		$q = $this->Tickets->find();
		$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
		$q->where([
			'Tickets.created >=' => $f['created_start'],
			'Tickets.created <=' => $f['created_end'],
		]);
		if ($f['idcliente'] !== null) {
			$q->where(['Tickets.idcliente' => $f['idcliente']]);
		}
		if ($f['idtecnico'] !== null) {
			$cols = $this->Tickets->getSchema()->columns();
			if (in_array('idtecnico_responsavel', $cols, true)) {
				$q->where(['Tickets.idtecnico_responsavel' => $f['idtecnico']]);
			}
		}

		return $q;
	}

	/**
	 * @param array<string,mixed> $f
	 * @return array<string,string>
	 */
	protected function _relatoriosComputeKpis(array $f) {
		$ticketsCount = $this->_relatoriosQueryTickets($f)->count();
		$sla = $this->_relatoriosKpiSlaPct($f);
		$receita = '—';
		$inad = '—';
		try {
			$receita = $this->_relatoriosSumReceitaPeriodo($f);
			$inad = $this->_relatoriosSumInadimplencia($f);
		} catch (\Throwable $e) {
			$this->log('Relatorios KPIs financeiros: ' . $e->getMessage(), 'warning');
		}

		return [
			'tickets' => (string)(int)$ticketsCount,
			'sla' => $sla,
			'receita' => $receita,
			'inadimplencia' => $inad,
		];
	}

	/**
	 * @param array<string,mixed> $f
	 */
	protected function _relatoriosKpiSlaPct(array $f) {
		$cols = $this->Tickets->getSchema()->columns();
		if (!in_array('sla_status', $cols, true)) {
			return '—';
		}
		$tracked = $this->_relatoriosQueryTickets($f)
			->where(function (QueryExpression $exp) {
				return $exp->isNotNull('Tickets.sla_status');
			})
			->count();
		if ($tracked === 0) {
			return 'n/d';
		}
		$viol = $this->_relatoriosQueryTickets($f)
			->where(['Tickets.sla_status' => 'violado'])
			->count();

		return (string)(int)round(100 * ($tracked - $viol) / $tracked) . '%';
	}

	/**
	 * @param array<string,mixed> $f
	 */
	protected function _relatoriosSumReceitaPeriodo(array $f) {
		$idempresa = (int)$this->Auth->user('idempresa');
		$q = $this->FinanceiroLancamentos->find();
		$q->select(['s' => $q->func()->sum('FinanceiroLancamentos.valor')])
			->where([
				'FinanceiroLancamentos.idempresa' => $idempresa,
				'FinanceiroLancamentos.tipo' => 'receita',
				'FinanceiroLancamentos.data_lancamento >=' => $f['period_date_start'],
				'FinanceiroLancamentos.data_lancamento <=' => $f['period_date_end'],
			]);
		$cidFin = $this->_relatoriosClienteParaFinanceiro($f);
		if ($cidFin !== null) {
			$q->where(['FinanceiroLancamentos.idcliente' => $cidFin]);
		}
		$row = $q->first();
		$sum = $row && $row->s !== null ? (float)$row->s : 0.0;

		return $this->_relatoriosFmtBrl($sum);
	}

	/**
	 * Receitas em aberto com vencimento anterior a hoje (escopo empresa + cliente).
	 *
	 * @param array<string,mixed> $f
	 */
	protected function _relatoriosSumInadimplencia(array $f) {
		$idempresa = (int)$this->Auth->user('idempresa');
		$hoje = date('Y-m-d');
		$q = $this->FinanceiroLancamentos->find();
		$q->select(['s' => $q->func()->sum('FinanceiroLancamentos.valor')])
			->where([
				'FinanceiroLancamentos.idempresa' => $idempresa,
				'FinanceiroLancamentos.tipo' => 'receita',
				'FinanceiroLancamentos.status' => 'aberto',
				'FinanceiroLancamentos.data_vencimento IS NOT' => null,
				'FinanceiroLancamentos.data_vencimento <' => $hoje,
			]);
		$cidFin = $this->_relatoriosClienteParaFinanceiro($f);
		if ($cidFin !== null) {
			$q->where(['FinanceiroLancamentos.idcliente' => $cidFin]);
		}
		$row = $q->first();
		$sum = $row && $row->s !== null ? (float)$row->s : 0.0;

		return $this->_relatoriosFmtBrl($sum);
	}

	protected function _relatoriosFmtBrl($amount) {
		return 'R$ ' . number_format((float)$amount, 2, ',', '.');
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	protected function _relatoriosSitLabels() {
		return [
			(int)C_TicketSituacaoPendente => 'Pendente',
			(int)C_TicketSituacaoEmandamento => 'Em andamento',
			(int)C_TicketSituacaoResolvido => 'Resolvido',
			(int)C_TicketSituacaoFechado => 'Fechado',
		];
	}

	/**
	 * @param array<string,mixed> $f
	 * @return \App\Model\Entity\Ticket[]
	 */
	protected function _relatoriosTicketsAmostra(array $f, $limit) {
		return $this->_relatoriosQueryTickets($f)
			->contain(['Clientes', 'users'])
			->order(['Tickets.id' => 'DESC'])
			->limit((int)$limit)
			->toArray();
	}

	/**
	 * @param array<int,string> $clientesList
	 * @param array<string,mixed> $f
	 * @return \Cake\Datasource\EntityInterface[]
	 */
	protected function _relatoriosContratosAmostra(array $clientesList, $limit, array $f = []) {
		$idempresa = (int)$this->Auth->user('idempresa');
		$cids = array_keys($clientesList);
		if ($cids === [] || !in_array('idempresa', $this->Clicontratos->getSchema()->columns(), true)) {
			return [];
		}

		$q = $this->Clicontratos->find('all', ['contain' => ['Clientes']])
			->where(['Clicontratos.idempresa' => $idempresa, 'Clicontratos.idcliente IN' => $cids]);
		if (!empty($f['idcontrato'])) {
			$q->where(['Clicontratos.id' => $f['idcontrato']]);
		}

		return $q->order(['Clicontratos.id' => 'DESC'])
			->limit((int)$limit)
			->toArray();
	}

	/**
	 * @param array<string,mixed> $f
	 * @param array<int,string> $tecnicosList
	 * @return array<int,array{nome:string,tickets:int}>
	 */
	protected function _relatoriosTicketsPorTecnico(array $f, array $tecnicosList) {
		$cols = $this->Tickets->getSchema()->columns();
		if (!in_array('idtecnico_responsavel', $cols, true)) {
			return [];
		}
		$q = $this->_relatoriosQueryTickets($f);
		$q->select([
			'tec' => 'Tickets.idtecnico_responsavel',
			'cnt' => $q->func()->count('*'),
		])
			->where(function (QueryExpression $exp) {
				return $exp->isNotNull('Tickets.idtecnico_responsavel');
			})
			->group(['Tickets.idtecnico_responsavel'])
			->order(['cnt' => 'DESC'])
			->enableHydration(false);
		$out = [];
		foreach ($q->toArray() as $row) {
			$tid = isset($row['tec']) ? (int)$row['tec'] : 0;
			if ($tid <= 0) {
				continue;
			}
			$out[] = [
				'nome' => $tecnicosList[$tid] ?? ('#' . $tid),
				'tickets' => (int)($row['cnt'] ?? 0),
			];
		}

		return $out;
	}

	/**
	 * @param array<string,mixed> $f
	 * @param array<int,string> $clientesList
	 * @param array<int,string> $tecnicosList
	 * @param array<int,string> $contratosList
	 * @return string[]
	 */
	protected function _relatoriosRotulosFiltros(array $f, array $clientesList, array $tecnicosList, array $contratosList) {
		$lines = [];
		$lines[] = 'Período (tickets criados): ' . $f['periodo_label'];
		if (!empty($f['periodo_padrao'])) {
			$lines[] = 'Obs.: período padrão últimos 60 dias (datas em branco).';
		}
		$lines[] = 'Cliente: ' . ($f['idcliente'] !== null
			? ($clientesList[$f['idcliente']] ?? ('#' . $f['idcliente']))
			: 'Todos');
		$lines[] = 'Técnico (responsável): ' . ($f['idtecnico'] !== null
			? ($tecnicosList[$f['idtecnico']] ?? ('#' . $f['idtecnico']))
			: 'Todos');
		$lines[] = 'Contrato (aba Contratos): ' . ($f['idcontrato'] !== null
			? ($contratosList[$f['idcontrato']] ?? ('#' . $f['idcontrato']))
			: 'Todos');
		if (!empty($f['idcontrato']) && $f['idcliente'] === null) {
			$lines[] = 'Financeiro: receita e inadimplência limitadas ao cliente do contrato selecionado.';
		}

		return $lines;
	}

	/**
	 * @param array<string,string> $kpis
	 * @param string[] $rotulos
	 * @param array<string,string> $redirectQuery
	 * @return \Cake\Http\Response
	 */
	protected function _relatoriosExportPdf(array $kpis, array $rotulos, array $redirectQuery) {
		if (!class_exists(\Mpdf\Mpdf::class)) {
			$this->Flash->error('Biblioteca mPDF não disponível.');
			return $this->redirect(['action' => 'index', '?' => $redirectQuery]);
		}
		$this->autoRender = false;
		$this->viewBuilder()->setLayout(false);
		$this->set(compact('kpis', 'rotulos'));
		$view = $this->createView();
		$html = $view->render('pdf_export');
		$tmpDir = TMP . 'mpdf' . DS;
		if (!is_dir($tmpDir)) {
			mkdir($tmpDir, 0775, true);
		}
		$mpdf = new \Mpdf\Mpdf([
			'mode' => 'utf-8',
			'format' => 'A4',
			'tempDir' => $tmpDir,
		]);
		$mpdf->WriteHTML($html);
		$pdf = $mpdf->Output('', 'S');
		$fn = 'Relatorio-indicadores-' . date('Ymd-His') . '.pdf';

		return $this->response
			->withType('application/pdf')
			->withDownload($fn)
			->withStringBody($pdf);
	}

	/**
	 * @param array<string,string> $kpis
	 * @param string[] $rotulos
	 * @return \Cake\Http\Response
	 */
	protected function _relatoriosExportCsv(array $kpis, array $rotulos) {
		$fh = fopen('php://temp', 'r+');
		fwrite($fh, "\xEF\xBB\xBF");
		fputcsv($fh, ['Critérios']);
		foreach ($rotulos as $line) {
			fputcsv($fh, [$line]);
		}
		fputcsv($fh, []);
		fputcsv($fh, ['Indicador', 'Valor']);
		fputcsv($fh, ['Tickets no período', $kpis['tickets']]);
		fputcsv($fh, ['SLA (% dentro do prazo, tickets rastreados)', $kpis['sla']]);
		fputcsv($fh, ['Receita no período', $kpis['receita']]);
		fputcsv($fh, ['Inadimplência (valor vencido em aberto)', $kpis['inadimplencia']]);
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);
		$fn = 'Relatorio-indicadores-' . date('Ymd-His') . '.csv';

		return $this->response
			->withType('text/csv; charset=UTF-8')
			->withDownload($fn)
			->withStringBody($csv);
	}

	/**
	 * Planilha .xlsx (PhpSpreadsheet). Sem a biblioteca, usa SpreadsheetML (.xls).
	 *
	 * @param array<string,string> $kpis
	 * @param string[] $rotulos
	 * @return \Cake\Http\Response
	 */
	protected function _relatoriosExportXlsx(array $kpis, array $rotulos) {
		if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
			return $this->_relatoriosExportSpreadsheetMl($kpis, $rotulos);
		}
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle('Relatorio');
		$row = 1;
		$sheet->setCellValue('A' . $row, 'Critérios');
		$row++;
		foreach ($rotulos as $line) {
			$sheet->setCellValue('A' . $row, $line);
			$row++;
		}
		$row++;
		$sheet->setCellValue('A' . $row, 'Indicador');
		$sheet->setCellValue('B' . $row, 'Valor');
		$row++;
		$pairs = [
			['Tickets no período', $kpis['tickets']],
			['SLA', $kpis['sla']],
			['Receita no período', $kpis['receita']],
			['Inadimplência', $kpis['inadimplencia']],
		];
		foreach ($pairs as $pair) {
			$sheet->setCellValue('A' . $row, $pair[0]);
			$sheet->setCellValue('B' . $row, $pair[1]);
			$row++;
		}
		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$tmp = TMP . 'rel_ind_' . str_replace('.', '', uniqid('', true)) . '.xlsx';
		try {
			$writer->save($tmp);
			$body = (string)file_get_contents($tmp);
		} finally {
			if (is_file($tmp)) {
				@unlink($tmp);
			}
		}
		$fn = 'Relatorio-indicadores-' . date('Ymd-His') . '.xlsx';

		return $this->response
			->withType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
			->withDownload($fn)
			->withStringBody($body);
	}

	/**
	 * Excel abre como planilha (SpreadsheetML); extensão .xls — fallback.
	 *
	 * @param array<string,string> $kpis
	 * @param string[] $rotulos
	 * @return \Cake\Http\Response
	 */
	protected function _relatoriosExportSpreadsheetMl(array $kpis, array $rotulos) {
		$esc = function ($s) {
			return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
		};
		$rows = [];
		$rows[] = ['Critérios', ''];
		foreach ($rotulos as $line) {
			$rows[] = [$line, ''];
		}
		$rows[] = ['', ''];
		$rows[] = ['Indicador', 'Valor'];
		$rows[] = ['Tickets no período', $kpis['tickets']];
		$rows[] = ['SLA', $kpis['sla']];
		$rows[] = ['Receita no período', $kpis['receita']];
		$rows[] = ['Inadimplência', $kpis['inadimplencia']];

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
		$xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
		$xml .= '<Worksheet ss:Name="Relatorio"><Table>' . "\n";
		foreach ($rows as $r) {
			$xml .= '<Row>';
			foreach ($r as $cell) {
				$xml .= '<Cell><Data ss:Type="String">' . $esc($cell) . '</Data></Cell>';
			}
			$xml .= "</Row>\n";
		}
		$xml .= '</Table></Worksheet></Workbook>';
		$fn = 'Relatorio-indicadores-' . date('Ymd-His') . '.xls';

		return $this->response
			->withType('application/vnd.ms-excel')
			->withDownload($fn)
			->withStringBody($xml);
	}
}
