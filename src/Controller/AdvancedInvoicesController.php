<?php
namespace App\Controller;

use App\Service\PortalAdvanced\AuditLogService;
use App\Service\PortalAdvanced\ReportExportService;

/**
 * Faturas módulo avançado (invoices). Equipe interna (role 0).
 */
class AdvancedInvoicesController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('Invoices');
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? 1) !== 0) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		$this->set('title', 'Faturas (módulo avançado)');
		$idempresa = (int)$this->Auth->user('idempresa');
		try {
			$q = $this->Invoices->find()
				->contain(['Clientes'])
				->where(['Invoices.idempresa' => $idempresa])
				->order(['Invoices.modified' => 'DESC']);
			$st = trim((string)$this->request->getQuery('status', ''));
			if ($st !== '') {
				$q->where(['Invoices.status' => $st]);
			}
			$rm = trim((string)$this->request->getQuery('reference_month', ''));
			if (preg_match('/^\d{4}-\d{2}$/', $rm)) {
				$q->where(['Invoices.reference_month' => $rm]);
			}
			$this->paginate = ['limit' => 30];
			$this->set('invoices', $this->paginate($q));
		} catch (\Throwable $e) {
			$this->Flash->error(__('Tabela invoices indisponível. Execute a migration do módulo avançado.'));
			$this->set('invoices', []);
		}
	}

	public function view($id = null) {
		$id = (int)$id;
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$inv = $this->Invoices->get($id, [
				'contain' => ['InvoiceItems', 'InvoicePayments', 'Contracts', 'Clientes'],
			]);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)($inv->idempresa ?? 0) !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}
		$this->set('title', 'Fatura ' . h($inv->code));
		$this->set('invoice', $inv);
	}

	public function markPaid($id = null) {
		$this->request->allowMethod(['post']);
		$id = (int)$id;
		$idempresa = (int)$this->Auth->user('idempresa');
		$uid = (int)$this->Auth->user('id');
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$inv = $this->Invoices->get($id);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)($inv->idempresa ?? 0) !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}
		$old = ['status' => $inv->status];
		$inv->status = 'paid';
		if ($this->Invoices->save($inv)) {
			AuditLogService::log($uid, 'Invoice', $id, 'mark_paid', $old, ['status' => 'paid'], $this->request->clientIp(), $this->request->getHeaderLine('User-Agent'));
			$this->Flash->success(__('Fatura marcada como paga.'));
		} else {
			$this->Flash->error(__('Não foi possível atualizar a fatura.'));
		}

		return $this->redirect(['action' => 'view', $id]);
	}

	/**
	 * CSV da listagem atual (mesmos filtros de index).
	 */
	public function export() {
		$this->autoRender = false;
		$idempresa = (int)$this->Auth->user('idempresa');
		$headers = ['Código', 'Mês ref.', 'Cliente', 'Total', 'Status', 'Vencimento'];
		$rows = [];
		try {
			$q = $this->Invoices->find()
				->contain(['Clientes'])
				->where(['Invoices.idempresa' => $idempresa])
				->order(['Invoices.id' => 'DESC'])
				->limit(5000);
			$st = trim((string)$this->request->getQuery('status', ''));
			if ($st !== '') {
				$q->where(['Invoices.status' => $st]);
			}
			$rm = trim((string)$this->request->getQuery('reference_month', ''));
			if (preg_match('/^\d{4}-\d{2}$/', $rm)) {
				$q->where(['Invoices.reference_month' => $rm]);
			}
			foreach ($q->all() as $r) {
				$cn = '';
				if (!empty($r->cliente)) {
					$cn = (string)($r->cliente->razaosocial ?: $r->cliente->nome);
				}
				$rows[] = [
					$r->code,
					$r->reference_month,
					$cn,
					$r->total,
					$r->status,
					$r->due_date ? $r->due_date->format('Y-m-d') : '',
				];
			}
		} catch (\Throwable $e) {
			$rows = [['erro', $e->getMessage(), '', '', '', '']];
		}
		$fh = fopen('php://temp', 'r+');
		fwrite($fh, "\xEF\xBB\xBF");
		ReportExportService::writeCsv($fh, $headers, $rows);
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);
		$fn = ReportExportService::sanitizeDownloadFilename('faturas-avancadas-' . date('Ymd-His') . '.csv');

		return $this->response
			->withType('text/csv; charset=UTF-8')
			->withDownload($fn)
			->withStringBody($csv);
	}
}
