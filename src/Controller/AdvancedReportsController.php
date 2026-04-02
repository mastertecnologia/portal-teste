<?php
namespace App\Controller;

use App\Service\PortalAdvanced\ReportExportService;
use Cake\ORM\TableRegistry;

/**
 * Indicadores resumidos (tickets + módulo avançado). Equipe interna (role 0).
 */
class AdvancedReportsController extends AppController {

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? 1) !== 0) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		$this->set('title', 'Indicadores (módulo avançado)');
		$idempresa = (int)$this->Auth->user('idempresa');
		$ps = trim((string)$this->request->getQuery('period_start', ''));
		$pe = trim((string)$this->request->getQuery('period_end', ''));

		$ticketsCount = null;
		try {
			$T = TableRegistry::get('Tickets');
			$tq = $T->find()->where(['Tickets.idempresa' => $idempresa]);
			if ($ps !== '') {
				$tq->where(['Tickets.created >=' => $ps . ' 00:00:00']);
			}
			if ($pe !== '') {
				$tq->where(['Tickets.created <=' => $pe . ' 23:59:59']);
			}
			$ticketsCount = $tq->count();
		} catch (\Throwable $e) {
			$ticketsCount = '—';
		}

		$contractsCount = null;
		$invoicesTotal = null;
		try {
			$C = TableRegistry::get('Contracts');
			$cq = $C->find()->where(['Contracts.idempresa' => $idempresa]);
			if ($ps !== '') {
				$cq->where(['Contracts.created >=' => $ps . ' 00:00:00']);
			}
			if ($pe !== '') {
				$cq->where(['Contracts.created <=' => $pe . ' 23:59:59']);
			}
			$contractsCount = $cq->count();
		} catch (\Throwable $e) {
			$contractsCount = '—';
		}

		try {
			$I = TableRegistry::get('Invoices');
			$iq = $I->find();
			$iq->where(['Invoices.idempresa' => $idempresa]);
			if ($ps !== '') {
				$iq->where(['Invoices.created >=' => $ps . ' 00:00:00']);
			}
			if ($pe !== '') {
				$iq->where(['Invoices.created <=' => $pe . ' 23:59:59']);
			}
			$iq->select(['s' => $iq->func()->sum('Invoices.total')]);
			$row = $iq->first();
			$invoicesTotal = $row && $row->s !== null ? $row->s : 0;
		} catch (\Throwable $e) {
			$invoicesTotal = '—';
		}

		$this->set(compact('ticketsCount', 'contractsCount', 'invoicesTotal', 'ps', 'pe'));
	}

	public function export() {
		$this->autoRender = false;
		$this->index();
		$ticketsCount = $this->viewVars['ticketsCount'] ?? '—';
		$contractsCount = $this->viewVars['contractsCount'] ?? '—';
		$invoicesTotal = $this->viewVars['invoicesTotal'] ?? '—';
		$ps = $this->viewVars['ps'] ?? '';
		$pe = $this->viewVars['pe'] ?? '';

		$headers = ['Indicador', 'Valor'];
		$rows = [
			['Período início', $ps],
			['Período fim', $pe],
			['Tickets (empresa)', $ticketsCount],
			['Contratos avançados (criados no período)', $contractsCount],
			['Soma total faturas avançadas (período)', $invoicesTotal],
		];
		$fh = fopen('php://temp', 'r+');
		fwrite($fh, "\xEF\xBB\xBF");
		ReportExportService::writeCsv($fh, $headers, $rows);
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);
		$fn = ReportExportService::sanitizeDownloadFilename('indicadores-avancados-' . date('Ymd-His') . '.csv');

		return $this->response
			->withType('text/csv; charset=UTF-8')
			->withDownload($fn)
			->withStringBody($csv);
	}
}
