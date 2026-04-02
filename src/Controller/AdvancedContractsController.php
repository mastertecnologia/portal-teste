<?php
namespace App\Controller;

use App\Service\ContractPdfService;

/**
 * Contratos do módulo avançado (tabela contracts). Equipe interna (role 0).
 * Legado: Clicontratos / contratos_horas.
 */
class AdvancedContractsController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('Contracts');
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? 1) !== 0) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		$this->set('title', 'Contratos (módulo avançado)');
		$idempresa = (int)$this->Auth->user('idempresa');
		$cid = (int)$this->request->getQuery('idcliente', 0);
		try {
			$q = $this->Contracts->find()
				->contain(['Clientes'])
				->where(['Contracts.idempresa' => $idempresa])
				->order(['Contracts.modified' => 'DESC']);
			if ($cid > 0) {
				$q->where(['Contracts.idcliente' => $cid]);
			}
			$this->paginate = ['limit' => 30];
			$this->set('contracts', $this->paginate($q));
		} catch (\Throwable $e) {
			$this->Flash->error(__('Tabela contracts indisponível. Execute a migration do módulo avançado.'));
			$this->set('contracts', []);
		}

		$clicontratosLegado = [];
		try {
			$this->loadModel('Clicontratos');
			$legq = $this->Clicontratos->find()
				->contain(['Clientes'])
				->where(['Clicontratos.idempresa' => $idempresa]);
			if ($cid > 0) {
				$legq->where(['Clicontratos.idcliente' => $cid]);
			}
			$clicontratosLegado = $legq->order(['Clicontratos.modified' => 'DESC'])->limit(200)->all();
		} catch (\Throwable $e) {
			$clicontratosLegado = [];
		}
		$this->set('clicontratosLegado', $clicontratosLegado);
	}

	public function view($id = null) {
		$id = (int)$id;
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$c = $this->Contracts->get($id, [
				'contain' => [
					'Clientes',
					'ContractServices',
					'ContractDocuments',
					'ContractTemplates',
					'ParentContracts',
					'ContractSignatories',
					'ContractRenewals' => ['NovoContracts', 'Solicitante', 'Aprovador'],
					'ContractNotifications',
				],
			]);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)$c->idempresa !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}
		$this->set('title', 'Contrato: ' . h($c->name));
		$this->set('contract', $c);
	}

	/**
	 * Gera PDF (mPDF), grava em disco e atualiza pdf_path.
	 *
	 * @param int|null $id
	 * @return \Cake\Http\Response
	 */
	public function exportPdf($id = null) {
		$id = (int)$id;
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$c = $this->Contracts->get($id, [
				'contain' => ['Clientes', 'Empresas', 'ContractTemplates', 'ContractServices'],
			]);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)$c->idempresa !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}

		try {
			$svc = new ContractPdfService();
			$tpl = !empty($c->contract_template) ? $c->contract_template : null;
			$path = $svc->gerar($c, $tpl, (array)$c->contract_services);
			$this->Contracts->patchEntity($c, ['pdf_path' => $path]);
			$this->Contracts->save($c);
		} catch (\Throwable $e) {
			$this->Flash->error(__('Não foi possível gerar o PDF.') . ' ' . $e->getMessage());

			return $this->redirect(['action' => 'view', $id]);
		}

		$this->autoRender = false;
		$body = file_get_contents($path);
		$fn = basename($path);

		return $this->response
			->withType('application/pdf')
			->withDownload($fn)
			->withStringBody($body !== false ? $body : '');
	}
}
