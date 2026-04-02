<?php
namespace App\Controller;

use Cake\Database\Driver\Mysql;
use Cake\Database\Expression\QueryExpression;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
$ticketConstantsFile = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'TicketConstants.php';
if (is_file($ticketConstantsFile)) {
	require_once $ticketConstantsFile;
}

/**
 * Relatórios resumidos no portal do cliente (sem dados operacionais internos).
 */
class PortalRelatoriosController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Clicontratos');
		$this->loadModel('Tickets');
		$this->loadModel('Clientes');
		$this->loadModel('FinanceiroLancamentos');
	}

	public function isAuthorized($user) {
		if (empty($user)) {
			return false;
		}
		// Portal: apenas perfil cliente; equipe administrativa usa /relatorios (ERP).
		if ((int)($user['role'] ?? -1) !== C_RoleCliente) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		$f = $this->_portalRelFiltrosSanitizados();
		$kpis = $this->_portalRelComputeTicketKpis($f);
		$this->set('title', 'Relatórios');
		$this->set('relKpi', $kpis);
		$this->set('relFiltros', [
			'periodo' => $f['periodo'],
			'unidade' => $f['unidade'],
			'contrato' => $f['contrato'],
		]);
		$this->set('relContratos', $f['contratos_opt']);
		$this->set('relResumoAtendimentos', $this->_portalRelResumoAtendimentos($f, $kpis));
		$this->set('relResumoContratos', $this->_portalRelResumoContratos($f));
		$this->set('relResumoFinanceiro', $this->_portalRelResumoFinanceiro($f));
		$this->set('relChartTemporal', $this->_portalRelTicketsBucketsTemporal($f));
		$this->set('relTicketsAmostra', $this->_portalRelTicketsAmostraSegura($f, 12));
	}

	/**
	 * Exportação CSV (UTF-8): respeita filtros sanitizados; sem IDs operacionais.
	 */
	public function exportar() {
		$this->autoRender = false;
		$payload = $this->_portalRelMontarPayloadExportacao();
		$fh = fopen('php://temp', 'r+');
		fwrite($fh, "\xEF\xBB\xBF");
		fputcsv($fh, ['Portal do Cliente — Relatórios (resumo)']);
		fputcsv($fh, ['Gerado em', date('d/m/Y H:i')]);
		fputcsv($fh, []);
		fputcsv($fh, ['Filtros']);
		fputcsv($fh, ['Período', $payload['periodo'] !== '' ? $payload['periodo'] : 'Não informado']);
		fputcsv($fh, ['Unidade', $payload['unidade_txt']]);
		fputcsv($fh, ['Contrato', $payload['contrato_txt']]);
		fputcsv($fh, []);
		fputcsv($fh, ['Indicador', 'Valor']);
		$k = $payload['kpi'];
		fputcsv($fh, ['Tickets no período', $k['tickets']]);
		fputcsv($fh, ['Resolvidos', $k['resolvidos']]);
		fputcsv($fh, ['Pendentes', $k['pendentes']]);
		fputcsv($fh, ['SLA', $k['sla']]);
		fputcsv($fh, []);
		$this->_portalRelCsvSecao($fh, 'Atendimentos', $payload['atendimentos']);
		$this->_portalRelCsvSecao($fh, 'Contratos', $payload['contratos_sec']);
		$this->_portalRelCsvSecao($fh, 'Financeiro', $payload['financeiro']);
		$this->_portalRelCsvChamadosRecentes($fh, $payload['chamados_recentes'] ?? []);
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);
		$fn = 'relatorios-portal-' . date('Ymd-His') . '.csv';

		return $this->response
			->withType('text/csv; charset=UTF-8')
			->withDownload($fn)
			->withStringBody($csv);
	}

	/**
	 * Exportação Excel (.xlsx): mesmos dados do CSV; PhpSpreadsheet já no projeto.
	 */
	public function exportarExcel() {
		$this->autoRender = false;
		$payload = $this->_portalRelMontarPayloadExportacao();

		return $this->_portalRelRespondXlsx($payload);
	}

	/**
	 * Filtros da query string validados contra unidades e contratos permitidos ao usuário.
	 *
	 * @return array{periodo:string,unidade:string,contrato:string,contratos_opt:array<string,string>,empresas_opt:array<string,string>}
	 */
	protected function _portalRelFiltrosSanitizados() {
		$empresasOpt = $this->_portalRelEmpresasOptUsuario();
		$contratosOpt = $this->_portalRelContratosOptUsuario();
		$periodo = trim((string)$this->request->getQuery('periodo', ''));
		$unidade = (string)$this->request->getQuery('unidade', '');
		if ($unidade !== '' && !array_key_exists($unidade, $empresasOpt)) {
			$unidade = '';
		}
		$contrato = (string)$this->request->getQuery('contrato', '');
		if ($contrato !== '' && !array_key_exists($contrato, $contratosOpt)) {
			$contrato = '';
		}

		return [
			'periodo' => $periodo,
			'unidade' => $unidade,
			'contrato' => $contrato,
			'contratos_opt' => $contratosOpt,
			'empresas_opt' => $empresasOpt,
			'_intervalo' => $this->_portalRelIntervaloDatas($periodo),
			'_empresa_ids' => $this->_portalRelEmpresasEscopo([
				'unidade' => $unidade,
				'empresas_opt' => $empresasOpt,
			]),
		];
	}

	/**
	 * Contratos do cliente na empresa atual (sem listar terceiros).
	 *
	 * @return array<string,string> id => rótulo
	 */
	protected function _portalRelContratosOptUsuario() {
		$idcliente = (int)$this->Auth->user('idcliente');
		$unidade = (string)$this->request->getQuery('unidade', '');
		$empresasOpt = $this->_portalRelEmpresasOptUsuario();
		if ($unidade !== '' && !array_key_exists($unidade, $empresasOpt)) {
			$unidade = '';
		}
		$idempresa = $unidade !== '' ? (int)$unidade : (int)$this->Auth->user('idempresa');
		if ($idcliente <= 0 || $idempresa <= 0) {
			return [];
		}
		$out = [];
		$rows = $this->Clicontratos->find('all')
			->where([
				'Clicontratos.idcliente' => $idcliente,
				'Clicontratos.idempresa' => $idempresa,
			])
			->order(['Clicontratos.id' => 'ASC'])
			->limit(500)
			->toArray();
		foreach ($rows as $c) {
			$label = trim((string)($c->descricao ?? ''));
			if ($label === '') {
				$label = trim((string)($c->codproduto ?? ''));
			}
			if ($label === '') {
				$label = 'Item #' . (int)$c->id;
			}
			$out[(string)$c->id] = $label;
		}

		return $out;
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function _portalRelMontarPayloadExportacao() {
		$f = $this->_portalRelFiltrosSanitizados();
		$unidadeTxt = 'Todas as unidades';
		if ($f['unidade'] !== '' && isset($f['empresas_opt'][$f['unidade']])) {
			$unidadeTxt = (string)$f['empresas_opt'][$f['unidade']];
		}
		$contratoTxt = 'Todos os contratos';
		if ($f['contrato'] !== '' && isset($f['contratos_opt'][$f['contrato']])) {
			$contratoTxt = (string)$f['contratos_opt'][$f['contrato']];
		}
		$kpi = $this->_portalRelComputeTicketKpis($f);

		return [
			'periodo' => $f['periodo'],
			'unidade_txt' => $unidadeTxt,
			'contrato_txt' => $contratoTxt,
			'kpi' => $kpi,
			'atendimentos' => $this->_portalRelResumoAtendimentos($f, $kpi),
			'contratos_sec' => $this->_portalRelResumoContratos($f),
			'financeiro' => $this->_portalRelResumoFinanceiro($f),
			'chamados_recentes' => $this->_portalRelTicketsAmostraSegura($f, 20),
		];
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return \Cake\Http\Response
	 */
	protected function _portalRelRespondXlsx(array $payload) {
		if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
			$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
			$sheet->setTitle('Resumo');
			$row = 1;
			$sheet->setCellValue('A' . $row, 'Portal do Cliente — Relatórios (resumo)');
			$row++;
			$sheet->setCellValue('A' . $row, 'Gerado em');
			$sheet->setCellValue('B' . $row, date('d/m/Y H:i'));
			$row += 2;
			$sheet->setCellValue('A' . $row, 'Filtros');
			$row++;
			$sheet->setCellValue('A' . $row, 'Período');
			$sheet->setCellValue('B' . $row, $payload['periodo'] !== '' ? $payload['periodo'] : 'Não informado');
			$row++;
			$sheet->setCellValue('A' . $row, 'Unidade');
			$sheet->setCellValue('B' . $row, $payload['unidade_txt']);
			$row++;
			$sheet->setCellValue('A' . $row, 'Contrato');
			$sheet->setCellValue('B' . $row, $payload['contrato_txt']);
			$row += 2;
			$sheet->setCellValue('A' . $row, 'Indicador');
			$sheet->setCellValue('B' . $row, 'Valor');
			$row++;
			$k = $payload['kpi'];
			foreach ([
				['Tickets no período', $k['tickets']],
				['Resolvidos', $k['resolvidos']],
				['Pendentes', $k['pendentes']],
				['SLA', $k['sla']],
			] as $pair) {
				$sheet->setCellValue('A' . $row, $pair[0]);
				$sheet->setCellValue('B' . $row, $pair[1]);
				$row++;
			}
			$row++;
			$row = $this->_portalRelXlsxSecao($sheet, $row, 'Atendimentos', $payload['atendimentos']);
			$row++;
			$row = $this->_portalRelXlsxSecao($sheet, $row, 'Contratos', $payload['contratos_sec']);
			$row++;
			$row = $this->_portalRelXlsxSecao($sheet, $row, 'Financeiro', $payload['financeiro']);
			$row++;
			$row = $this->_portalRelXlsxChamadosRecentes($sheet, $row, $payload['chamados_recentes'] ?? []);

			$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
			$tmp = TMP . 'rel_portal_' . str_replace('.', '', uniqid('', true)) . '.xlsx';
			try {
				$writer->save($tmp);
				$body = (string)file_get_contents($tmp);
			} finally {
				if (is_file($tmp)) {
					@unlink($tmp);
				}
			}
			$fn = 'relatorios-portal-' . date('Ymd-His') . '.xlsx';

			return $this->response
				->withType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
				->withDownload($fn)
				->withStringBody($body);
		}

		return $this->_portalRelRespondXlsxSpreadsheetMl($payload);
	}

	/**
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param array<int,array<string,string>> $linhas
	 * @return int próxima linha livre
	 */
	protected function _portalRelXlsxSecao($sheet, int $row, $titulo, array $linhas) {
		$sheet->setCellValue('A' . $row, $titulo);
		$row++;
		$sheet->setCellValue('A' . $row, 'Descrição');
		$sheet->setCellValue('B' . $row, 'Valor');
		$row++;
		foreach ($linhas as $r) {
			$sheet->setCellValue('A' . $row, $r['label'] ?? '');
			$sheet->setCellValue('B' . $row, $r['valor'] ?? '');
			$row++;
		}

		return $row;
	}

	/**
	 * Fallback XML (Excel) se PhpSpreadsheet indisponível.
	 *
	 * @param array<string,mixed> $payload
	 * @return \Cake\Http\Response
	 */
	protected function _portalRelRespondXlsxSpreadsheetMl(array $payload) {
		$esc = function ($s) {
			return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
		};
		$rows = [];
		$rows[] = ['Portal do Cliente — Relatórios (resumo)', ''];
		$rows[] = ['Gerado em', date('d/m/Y H:i')];
		$rows[] = ['', ''];
		$rows[] = ['Filtros', ''];
		$rows[] = ['Período', $payload['periodo'] !== '' ? $payload['periodo'] : 'Não informado'];
		$rows[] = ['Unidade', $payload['unidade_txt']];
		$rows[] = ['Contrato', $payload['contrato_txt']];
		$rows[] = ['', ''];
		$rows[] = ['Indicador', 'Valor'];
		$k = $payload['kpi'];
		$rows[] = ['Tickets no período', $k['tickets']];
		$rows[] = ['Resolvidos', $k['resolvidos']];
		$rows[] = ['Pendentes', $k['pendentes']];
		$rows[] = ['SLA', $k['sla']];
		$rows[] = ['', ''];
		foreach (['Atendimentos' => $payload['atendimentos'], 'Contratos' => $payload['contratos_sec'], 'Financeiro' => $payload['financeiro']] as $tit => $sec) {
			$rows[] = [$tit, ''];
			$rows[] = ['Descrição', 'Valor'];
			foreach ($sec as $r) {
				$rows[] = [$r['label'] ?? '', $r['valor'] ?? ''];
			}
			$rows[] = ['', ''];
		}
		$rows[] = ['Chamados recentes (sem identificador)', ''];
		$rows[] = ['Assunto', 'Abertura', 'Situação'];
		foreach ($payload['chamados_recentes'] ?? [] as $c) {
			$rows[] = [$c['assunto'] ?? '', $c['abertura'] ?? '', $c['situacao'] ?? ''];
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
		$xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
		$xml .= '<Worksheet ss:Name="Resumo"><Table>' . "\n";
		foreach ($rows as $r) {
			$xml .= '<Row>';
			foreach ($r as $cell) {
				$xml .= '<Cell><Data ss:Type="String">' . $esc($cell) . '</Data></Cell>';
			}
			$xml .= "</Row>\n";
		}
		$xml .= "</Table></Worksheet></Workbook>\n";
		$fn = 'relatorios-portal-' . date('Ymd-His') . '.xls';

		return $this->response
			->withType('application/vnd.ms-excel')
			->withDownload($fn)
			->withStringBody($xml);
	}

	protected function _portalRelCsvSecao($fh, $titulo, array $linhas) {
		fputcsv($fh, [$titulo]);
		fputcsv($fh, ['Descrição', 'Valor']);
		foreach ($linhas as $row) {
			fputcsv($fh, [$row['label'], $row['valor']]);
		}
		fputcsv($fh, []);
	}

	/**
	 * @param array<int,array{assunto:string,abertura:string,situacao:string}> $chamados
	 */
	protected function _portalRelCsvChamadosRecentes($fh, array $chamados) {
		fputcsv($fh, ['Chamados recentes (sem identificador)']);
		fputcsv($fh, ['Assunto', 'Abertura', 'Situação']);
		foreach ($chamados as $c) {
			fputcsv($fh, [$c['assunto'] ?? '', $c['abertura'] ?? '', $c['situacao'] ?? '']);
		}
		fputcsv($fh, []);
	}

	/**
	 * @param array<int,array{assunto:string,abertura:string,situacao:string}> $chamados
	 * @return int próxima linha livre
	 */
	protected function _portalRelXlsxChamadosRecentes($sheet, int $row, array $chamados) {
		$sheet->setCellValue('A' . $row, 'Chamados recentes (sem identificador)');
		$row++;
		$sheet->setCellValue('A' . $row, 'Assunto');
		$sheet->setCellValue('B' . $row, 'Abertura');
		$sheet->setCellValue('C' . $row, 'Situação');
		$row++;
		foreach ($chamados as $c) {
			$sheet->setCellValue('A' . $row, $c['assunto'] ?? '');
			$sheet->setCellValue('B' . $row, $c['abertura'] ?? '');
			$sheet->setCellValue('C' . $row, $c['situacao'] ?? '');
			$row++;
		}

		return $row;
	}

	/**
	 * Agrupa tickets do escopo por semana (início na segunda); se muitas colunas, por mês.
	 * Preferência: agregação SQL (MySQL) para não carregar todas as linhas em memória.
	 *
	 * @return array{mode:string,points:array<int,array{label:string,count:int}>}
	 */
	protected function _portalRelTicketsBucketsTemporal(array $f) {
		$base = $this->_portalRelTicketsBaseQuery($f);
		if ($base === null) {
			return ['mode' => 'week', 'points' => []];
		}
		$driver = $this->Tickets->getConnection()->getDriver();
		if ($driver instanceof Mysql) {
			$weekPoints = $this->_portalRelAggregateBucketsMysql(clone $base, false);
			if ($weekPoints !== null) {
				if (count($weekPoints) > 24) {
					$monthPoints = $this->_portalRelAggregateBucketsMysql(clone $base, true);
					if ($monthPoints !== null) {
						return ['mode' => 'month', 'points' => $monthPoints];
					}

					return $this->_portalRelBucketsTemporalPhp($f);
				}

				return ['mode' => 'week', 'points' => $weekPoints];
			}
		}

		return $this->_portalRelBucketsTemporalPhp($f);
	}

	/**
	 * @param \Cake\ORM\Query $base mesmo escopo de _portalRelTicketsBaseQuery
	 * @return array<int,array{label:string,count:int}>|null null = falha (usa fallback PHP)
	 */
	protected function _portalRelAggregateBucketsMysql($base, $useMonth) {
		try {
			$bucketSql = $useMonth
				? "DATE_FORMAT(Tickets.created, '%Y-%m')"
				: 'DATE_SUB(DATE(Tickets.created), INTERVAL WEEKDAY(DATE(Tickets.created)) DAY)';
			$expr = $base->newExpr($bucketSql);
			$base->select(['bk' => $expr, 'cnt' => $base->func()->count('*')])
				->group([$expr])
				->order([$expr => 'ASC']);
			$raw = $base->enableHydration(false)->toArray();
			$out = [];
			foreach ($raw as $r) {
				$bk = $r['bk'] ?? null;
				if ($bk === null) {
					continue;
				}
				$cnt = (int)($r['cnt'] ?? 0);
				if ($useMonth) {
					$k = (string)$bk;
					$label = strlen($k) === 7 ? (substr($k, 5, 2) . '/' . substr($k, 0, 4)) : $k;
				} else {
					$ts = strtotime((string)$bk);
					$label = $ts !== false ? date('d/m', $ts) : (string)$bk;
				}
				$out[] = ['label' => $label, 'count' => $cnt];
			}

			return $out;
		} catch (\Throwable $e) {
			$this->log('PortalRelatorios aggregate buckets MySQL: ' . $e->getMessage(), 'warning');

			return null;
		}
	}

	/**
	 * Fallback: carrega `created` em memória (legado / não-MySQL).
	 *
	 * @return array{mode:string,points:array<int,array{label:string,count:int}>}
	 */
	protected function _portalRelBucketsTemporalPhp(array $f) {
		$base = $this->_portalRelTicketsBaseQuery($f);
		if ($base === null) {
			return ['mode' => 'week', 'points' => []];
		}
		$rows = (clone $base)
			->select(['Tickets.created'])
			->enableHydration(false)
			->toArray();
		if ($rows === []) {
			return ['mode' => 'week', 'points' => []];
		}
		$buckets = [];
		foreach ($rows as $row) {
			$ts = $this->_portalRelCreatedTimestamp($row['created'] ?? null);
			if ($ts === null) {
				continue;
			}
			$dow = (int)date('N', $ts);
			$weekStart = date('Y-m-d', strtotime('-' . ($dow - 1) . ' days', $ts));
			$buckets[$weekStart] = ($buckets[$weekStart] ?? 0) + 1;
		}
		ksort($buckets);
		if (count($buckets) > 24) {
			$buckets = [];
			foreach ($rows as $row) {
				$ts = $this->_portalRelCreatedTimestamp($row['created'] ?? null);
				if ($ts === null) {
					continue;
				}
				$mk = date('Y-m', $ts);
				$buckets[$mk] = ($buckets[$mk] ?? 0) + 1;
			}
			ksort($buckets);
			$points = [];
			foreach ($buckets as $k => $n) {
				$points[] = [
					'label' => strlen($k) === 7 ? (substr($k, 5, 2) . '/' . substr($k, 0, 4)) : $k,
					'count' => (int)$n,
				];
			}

			return ['mode' => 'month', 'points' => $points];
		}
		$points = [];
		foreach ($buckets as $k => $n) {
			$points[] = [
				'label' => date('d/m', strtotime($k)),
				'count' => (int)$n,
			];
		}

		return ['mode' => 'week', 'points' => $points];
	}

	/**
	 * @param mixed $created
	 * @return int|null timestamp
	 */
	protected function _portalRelCreatedTimestamp($created) {
		if ($created instanceof \DateTimeInterface) {
			return $created->getTimestamp();
		}
		$ts = strtotime((string)$created);

		return $ts !== false ? $ts : null;
	}

	/**
	 * @return array<int,array{id:int,assunto:string,situacao:string,abertura:string}>
	 */
	protected function _portalRelTicketsAmostraSegura(array $f, $limit = 12) {
		$base = $this->_portalRelTicketsBaseQuery($f);
		if ($base === null) {
			return [];
		}
		$limit = max(1, min(25, (int)$limit));
		$rows = (clone $base)
			->select(['Tickets.id', 'Tickets.assunto', 'Tickets.situacao', 'Tickets.created'])
			->order(['Tickets.id' => 'DESC'])
			->limit($limit)
			->enableHydration(false)
			->toArray();
		$out = [];
		foreach ($rows as $r) {
			$as = trim((string)($r['assunto'] ?? ''));
			if (mb_strlen($as) > 100) {
				$as = mb_substr($as, 0, 97) . '…';
			}
			$out[] = [
				'id' => (int)($r['id'] ?? 0),
				'assunto' => $as !== '' ? $as : '—',
				'situacao' => $this->_portalRelSituacaoLegenda((int)($r['situacao'] ?? -1)),
				'abertura' => $this->_portalRelFormatarDataHora($r['created'] ?? null),
			];
		}

		return $out;
	}

	protected function _portalRelSituacaoLegenda($situacao) {
		$pend = defined('C_TicketSituacaoPendente') ? (int)C_TicketSituacaoPendente : 0;
		$em = defined('C_TicketSituacaoEmandamento') ? (int)C_TicketSituacaoEmandamento : 1;
		$res = defined('C_TicketSituacaoResolvido') ? (int)C_TicketSituacaoResolvido : 2;
		$fec = defined('C_TicketSituacaoFechado') ? (int)C_TicketSituacaoFechado : 3;
		$map = [
			$pend => 'Pendente',
			$em => 'Em andamento',
			$res => 'Resolvido',
			$fec => 'Fechado',
		];

		return $map[(int)$situacao] ?? '—';
	}

	/**
	 * @param mixed $v
	 */
	protected function _portalRelFormatarDataHora($v) {
		$ts = $this->_portalRelCreatedTimestamp($v);

		return $ts !== null ? date('d/m/Y H:i', $ts) : '—';
	}

	/**
	 * @param string $s data dd/mm/aaaa
	 * @return \DateTimeImmutable|null
	 */
	protected function _portalRelParseBrDate($s) {
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
	 * Intervalo [início, fim] como string SQL; padrão últimos 90 dias.
	 *
	 * @return array{0:string,1:string}
	 */
	protected function _portalRelIntervaloDatas($periodo) {
		$periodo = trim((string)$periodo);
		if ($periodo !== '' && preg_match('/(\d{1,2}\/\d{1,2}\/\d{4})\s+a\s+(\d{1,2}\/\d{1,2}\/\d{4})/u', $periodo, $m)) {
			$ini = $this->_portalRelParseBrDate($m[1]);
			$fim = $this->_portalRelParseBrDate($m[2]);
			if ($ini !== null && $fim !== null) {
				if ($ini > $fim) {
					$t = $ini;
					$ini = $fim;
					$fim = $t;
				}

				return [
					$ini->format('Y-m-d 00:00:00'),
					$fim->format('Y-m-d 23:59:59'),
				];
			}
		}
		$fim = new \DateTimeImmutable('today');
		$ini = $fim->modify('-90 days');

		return [
			$ini->format('Y-m-d 00:00:00'),
			$fim->format('Y-m-d 23:59:59'),
		];
	}

	/**
	 * @param array{unidade:string,empresas_opt:array<string,string>} $f
	 * @return int[]
	 */
	protected function _portalRelEmpresasEscopo(array $f) {
		if ($f['unidade'] !== '') {
			return [(int)$f['unidade']];
		}
		$ids = [];
		foreach (array_keys($f['empresas_opt']) as $k) {
			$ids[] = (int)$k;
		}
		if ($ids === []) {
			$e = (int)$this->Auth->user('idempresa');

			return $e > 0 ? [$e] : [];
		}

		return $ids;
	}

	/**
	 * @return \Cake\ORM\Query|null
	 */
	protected function _portalRelTicketsBaseQuery(array $f) {
		$empresaIds = $f['_empresa_ids'] ?? [];
		if ($empresaIds === []) {
			return null;
		}
		$idClienteAuth = (int)$this->Auth->user('idcliente');
		$cliente = $this->Clientes->findById($idClienteAuth)->first();
		if (empty($cliente)) {
			return null;
		}
		[$start, $end] = $f['_intervalo'];
		$q = $this->Tickets->find()->contain(['Clientes']);
		$q->where([
			'Tickets.idempresa IN' => $empresaIds,
			'Tickets.created >=' => $start,
			'Tickets.created <=' => $end,
			'OR' => ['Clientes.cpf' => $cliente->cpf, 'Clientes.cnpj' => $cliente->cnpj],
		]);
		if (!$this->Auth->user('permissaoacesso')) {
			$q->where(['Tickets.idautor' => (int)$this->Auth->user('id')]);
		}
		$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');

		return $q;
	}

	/**
	 * @return array{tickets:string,resolvidos:string,pendentes:string,sla:string}
	 */
	protected function _portalRelComputeTicketKpis(array $f) {
		$base = $this->_portalRelTicketsBaseQuery($f);
		if ($base === null) {
			return [
				'tickets' => '0',
				'resolvidos' => '0',
				'pendentes' => '0',
				'sla' => '—',
			];
		}
		$pend = defined('C_TicketSituacaoPendente') ? (int)C_TicketSituacaoPendente : 0;
		$em = defined('C_TicketSituacaoEmandamento') ? (int)C_TicketSituacaoEmandamento : 1;
		$res = defined('C_TicketSituacaoResolvido') ? (int)C_TicketSituacaoResolvido : 2;
		$fec = defined('C_TicketSituacaoFechado') ? (int)C_TicketSituacaoFechado : 3;

		$total = (clone $base)->count();
		$resolvidos = (clone $base)->where(['Tickets.situacao IN' => [$res, $fec]])->count();
		$pendentes = (clone $base)->where(['Tickets.situacao IN' => [$pend, $em]])->count();
		$sla = $this->_portalRelKpiSlaPct(clone $base);

		return [
			'tickets' => (string)(int)$total,
			'resolvidos' => (string)(int)$resolvidos,
			'pendentes' => (string)(int)$pendentes,
			'sla' => $sla,
		];
	}

	/**
	 * @param \Cake\ORM\Query $q query já com escopo portal
	 */
	protected function _portalRelKpiSlaPct($q) {
		$cols = $this->Tickets->getSchema()->columns();
		if (!in_array('sla_status', $cols, true)) {
			return '—';
		}
		$tracked = (clone $q)
			->where(function (QueryExpression $exp) {
				return $exp->isNotNull('Tickets.sla_status');
			})
			->count();
		if ($tracked === 0) {
			return 'n/d';
		}
		$viol = (clone $q)->where(['Tickets.sla_status' => 'violado'])->count();

		return (string)(int)round(100 * ($tracked - $viol) / $tracked) . '%';
	}

	/**
	 * @param array{tickets:string,resolvidos:string,pendentes:string,sla:string} $kpis
	 * @return array<int,array{label:string,valor:string,hint?:string}>
	 */
	protected function _portalRelResumoAtendimentos(array $f, array $kpis) {
		return [
			[
				'label' => 'Volume no período',
				'valor' => $kpis['tickets'],
				'hint' => 'Chamados criados no intervalo (mesma regra da lista de tickets)',
			],
			[
				'label' => 'Resolvidos / fechados',
				'valor' => $kpis['resolvidos'],
				'hint' => 'Situações resolvido e fechado',
			],
			[
				'label' => 'Em aberto ou em andamento',
				'valor' => $kpis['pendentes'],
				'hint' => 'Pendente e em andamento',
			],
		];
	}

	/**
	 * @return array<int,array{label:string,valor:string,hint?:string}>
	 */
	protected function _portalRelResumoContratos(array $f) {
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = $f['unidade'] !== '' ? (int)$f['unidade'] : (int)$this->Auth->user('idempresa');
		$rows = [
			[
				'label' => 'Itens de contrato (unidade)',
				'valor' => '0',
				'hint' => 'Registros em contratos do cliente na empresa do filtro',
			],
		];
		if ($idcliente <= 0 || $idempresa <= 0) {
			return $rows;
		}
		try {
			$c = $this->Clicontratos->find()
				->where(['Clicontratos.idcliente' => $idcliente, 'Clicontratos.idempresa' => $idempresa])
				->count();
			$rows[0]['valor'] = (string)(int)$c;
		} catch (\Throwable $e) {
			$this->log('PortalRelatorios contratos: ' . $e->getMessage(), 'warning');
		}
		if ($f['contrato'] !== '' && isset($f['contratos_opt'][$f['contrato']])) {
			$rows[] = [
				'label' => 'Contrato selecionado (filtro)',
				'valor' => (string)$f['contratos_opt'][$f['contrato']],
				'hint' => 'Apenas contexto do filtro; tickets não são filtrados por contrato nesta versão',
			];
		}

		return $rows;
	}

	/**
	 * @return array<int,array{label:string,valor:string,hint?:string}>
	 */
	protected function _portalRelResumoFinanceiro(array $f) {
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = $f['unidade'] !== '' ? (int)$f['unidade'] : (int)$this->Auth->user('idempresa');
		[$start, $end] = $f['_intervalo'];
		$ds = substr($start, 0, 10);
		$de = substr($end, 0, 10);
		$out = [
			['label' => 'Receitas no período', 'valor' => '—', 'hint' => 'Soma por data de lançamento'],
			['label' => 'Valor em atraso (receitas abertas)', 'valor' => '—', 'hint' => 'Vencimento anterior a hoje'],
		];
		if ($idcliente <= 0 || $idempresa <= 0) {
			return $out;
		}
		try {
			$q = $this->FinanceiroLancamentos->find();
			$q->select(['s' => $q->func()->sum('FinanceiroLancamentos.valor')])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.idcliente' => $idcliente,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.data_lancamento >=' => $ds,
					'FinanceiroLancamentos.data_lancamento <=' => $de,
				]);
			$row = $q->first();
			$sum = $row && $row->s !== null ? (float)$row->s : 0.0;
			$out[0]['valor'] = $this->_portalRelFmtBrl($sum);

			$hoje = date('Y-m-d');
			$q2 = $this->FinanceiroLancamentos->find();
			$q2->select(['s' => $q2->func()->sum('FinanceiroLancamentos.valor')])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.idcliente' => $idcliente,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.status' => 'aberto',
					'FinanceiroLancamentos.data_vencimento IS NOT' => null,
					'FinanceiroLancamentos.data_vencimento <' => $hoje,
				]);
			$r2 = $q2->first();
			$sum2 = $r2 && $r2->s !== null ? (float)$r2->s : 0.0;
			$out[1]['valor'] = $this->_portalRelFmtBrl($sum2);
		} catch (\Throwable $e) {
			$this->log('PortalRelatorios financeiro: ' . $e->getMessage(), 'warning');
		}

		return $out;
	}

	protected function _portalRelFmtBrl($amount) {
		return 'R$ ' . number_format((float)$amount, 2, ',', '.');
	}

	/**
	 * Mesma lógica do sidebar do portal (nomes fantasia), sem expor IDs no export.
	 *
	 * @return array<string,string>
	 */
	protected function _portalRelEmpresasOptUsuario() {
		$iduser = (int)$this->Auth->user('id');
		if ($iduser <= 0) {
			return [];
		}
		$out = [];
		foreach (
			$this->Empresasusers
				->find('all')
				->where(['iduser' => $iduser])
				->contain(['Empresas' => ['fields' => ['nomefantasia']]])
				->order(['Empresas.nomefantasia' => 'ASC'])
				->toArray() as $reg
		) {
			$out[(string)$reg->idempresa] = (string)$reg->empresa->nomefantasia;
		}

		return $out;
	}
}
