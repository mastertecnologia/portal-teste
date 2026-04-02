<?php
namespace App\Controller;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

use App\Service\PortalAdvanced\ReportExportService;

/**
 * Faturas avançadas no portal do cliente (somente leitura + export próprio).
 */
class PortalAdvancedInvoicesController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('Invoices');
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? -1) !== C_RoleCliente) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		$this->set('title', 'Minhas faturas');
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($idcliente <= 0) {
			$this->Flash->error(__('Cliente não vinculado ao usuário.'));
			$this->set('invoices', []);

			return;
		}
		try {
			$q = $this->Invoices->find()
				->where([
					'Invoices.idcliente' => $idcliente,
					'Invoices.idempresa' => $idempresa,
				])
				->order(['Invoices.modified' => 'DESC']);
			$this->paginate = ['limit' => 30];
			$this->set('invoices', $this->paginate($q));
		} catch (\Throwable $e) {
			$this->Flash->error(__('Módulo indisponível.'));
			$this->set('invoices', []);
		}
	}

	public function view($id = null) {
		$id = (int)$id;
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0 || $idcliente <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$inv = $this->Invoices->get($id, [
				'contain' => ['InvoiceItems', 'InvoicePayments', 'Contracts'],
			]);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)$inv->idcliente !== $idcliente || (int)($inv->idempresa ?? 0) !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}
		$this->set('title', 'Fatura ' . h($inv->code));
		$this->set('invoice', $inv);
	}

	public function exportar() {
		$this->autoRender = false;
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = (int)$this->Auth->user('idempresa');
		$headers = ['Código', 'Mês ref.', 'Total', 'Status', 'Vencimento'];
		$rows = [];
		if ($idcliente > 0) {
			try {
				$q = $this->Invoices->find()
					->where([
						'Invoices.idcliente' => $idcliente,
						'Invoices.idempresa' => $idempresa,
					])
					->order(['Invoices.id' => 'DESC'])
					->limit(2000);
				foreach ($q->all() as $r) {
					$rows[] = [
						$r->code,
						$r->reference_month,
						$r->total,
						$r->status,
						$r->due_date ? $r->due_date->format('Y-m-d') : '',
					];
				}
			} catch (\Throwable $e) {
				$rows = [['erro', $e->getMessage(), '', '', '']];
			}
		}
		$fh = fopen('php://temp', 'r+');
		fwrite($fh, "\xEF\xBB\xBF");
		ReportExportService::writeCsv($fh, $headers, $rows);
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);
		$fn = ReportExportService::sanitizeDownloadFilename('minhas-faturas-' . date('Ymd-His') . '.csv');

		return $this->response
			->withType('text/csv; charset=UTF-8')
			->withDownload($fn)
			->withStringBody($csv);
	}
}
