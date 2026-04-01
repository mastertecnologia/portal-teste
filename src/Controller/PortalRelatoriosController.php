<?php
namespace App\Controller;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

/**
 * Relatórios resumidos no portal do cliente (sem dados operacionais internos).
 */
class PortalRelatoriosController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Clicontratos');
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
		$this->set('title', 'Relatórios');
		$this->set('relKpi', [
			'tickets' => '0',
			'resolvidos' => '0',
			'pendentes' => '0',
			'sla' => '—',
		]);
		$this->set('relFiltros', [
			'periodo' => $f['periodo'],
			'unidade' => $f['unidade'],
			'contrato' => $f['contrato'],
		]);
		$this->set('relContratos', $f['contratos_opt']);
		$this->set('relResumoAtendimentos', $this->_portalRelResumoAtendimentosPlaceholder());
		$this->set('relResumoContratos', $this->_portalRelResumoContratosPlaceholder());
		$this->set('relResumoFinanceiro', $this->_portalRelResumoFinanceiroPlaceholder());
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
		];
	}

	/**
	 * Contratos do cliente na empresa atual (sem listar terceiros).
	 *
	 * @return array<string,string> id => rótulo
	 */
	protected function _portalRelContratosOptUsuario() {
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = (int)$this->Auth->user('idempresa');
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
		$kpi = [
			'tickets' => '0',
			'resolvidos' => '0',
			'pendentes' => '0',
			'sla' => '—',
		];

		return [
			'periodo' => $f['periodo'],
			'unidade_txt' => $unidadeTxt,
			'contrato_txt' => $contratoTxt,
			'kpi' => $kpi,
			'atendimentos' => $this->_portalRelResumoAtendimentosPlaceholder(),
			'contratos_sec' => $this->_portalRelResumoContratosPlaceholder(),
			'financeiro' => $this->_portalRelResumoFinanceiroPlaceholder(),
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
			$this->_portalRelXlsxSecao($sheet, $row, 'Financeiro', $payload['financeiro']);

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

	protected function _portalRelResumoAtendimentosPlaceholder() {
		return [
			['label' => 'Volume no período', 'valor' => '—', 'hint' => 'Total de chamados visíveis ao seu perfil'],
			['label' => 'Tendência', 'valor' => '—', 'hint' => 'Comparativo será exibido quando houver histórico'],
		];
	}

	protected function _portalRelResumoContratosPlaceholder() {
		return [
			['label' => 'Contratos ativos', 'valor' => '—', 'hint' => 'Quantidade consolidada'],
			['label' => 'Situação geral', 'valor' => '—', 'hint' => 'Resumo sem detalhe interno'],
		];
	}

	protected function _portalRelResumoFinanceiroPlaceholder() {
		return [
			['label' => 'Faturas no período', 'valor' => '—', 'hint' => 'Contagem autorizada ao portal'],
			['label' => 'Situação de pagamento', 'valor' => '—', 'hint' => 'Visão resumida'],
		];
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
