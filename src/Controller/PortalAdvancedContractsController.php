<?php
namespace App\Controller;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

/**
 * Contratos avançados no portal do cliente (somente leitura).
 */
class PortalAdvancedContractsController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('Contracts');
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? -1) !== C_RoleCliente) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		$this->set('title', 'Meus contratos');
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($idcliente <= 0) {
			$this->Flash->error(__('Cliente não vinculado ao usuário.'));
			$this->set('contracts', []);

			return;
		}
		try {
			$q = $this->Contracts->find()
				->contain(['Clientes'])
				->where([
					'Contracts.idcliente' => $idcliente,
					'Contracts.idempresa' => $idempresa,
				])
				->order(['Contracts.modified' => 'DESC']);
			$this->paginate = ['limit' => 30];
			$this->set('contracts', $this->paginate($q));
		} catch (\Throwable $e) {
			$this->Flash->error(__('Módulo indisponível.'));
			$this->set('contracts', []);
		}

		$clicontratosLegado = [];
		try {
			$this->loadModel('Clicontratos');
			$clicontratosLegado = $this->Clicontratos->find()
				->contain(['Clientes'])
				->where([
					'Clicontratos.idempresa' => $idempresa,
					'Clicontratos.idcliente' => $idcliente,
				])
				->order(['Clicontratos.modified' => 'DESC'])
				->limit(150)
				->all();
		} catch (\Throwable $e) {
			$clicontratosLegado = [];
		}
		$this->set('clicontratosLegado', $clicontratosLegado);
	}

	public function view($id = null) {
		$id = (int)$id;
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0 || $idcliente <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$c = $this->Contracts->get($id, [
				'contain' => [
					'ContractServices',
					'ContractDocuments' => function (\Cake\ORM\Query $q) {
						return $q->where(['ContractDocuments.is_public' => true]);
					},
					'ContractTemplates',
					'ContractSignatories',
				],
			]);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)$c->idcliente !== $idcliente || (int)($c->idempresa ?? 0) !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}
		$this->set('title', h($c->name));
		$this->set('contract', $c);
	}

	/**
	 * PDF já gerado (pdf_path). Não expõe geração arbitrária no portal.
	 *
	 * @param int|null $id
	 * @return \Cake\Http\Response
	 */
	public function exportPdf($id = null) {
		$id = (int)$id;
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0 || $idcliente <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$c = $this->Contracts->get($id, ['contain' => ['Clientes']]);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)$c->idcliente !== $idcliente || (int)($c->idempresa ?? 0) !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}
		$path = (string)($c->pdf_path ?? '');
		if ($path === '' || !is_readable($path)) {
			$this->Flash->error(__('PDF ainda não disponível para este contrato.'));

			return $this->redirect(['action' => 'view', $id]);
		}

		$this->autoRender = false;
		$body = file_get_contents($path);

		return $this->response
			->withType('application/pdf')
			->withDownload('contrato-' . (int)$id . '.pdf')
			->withStringBody($body !== false ? $body : '');
	}

	/**
	 * Consumo de franquia (horas) por contrato e mês de referência.
	 *
	 * @return void
	 */
	public function franquia() {
		$this->set('title', 'Franquia de horas');
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = (int)$this->Auth->user('idempresa');
		$mes = trim((string)$this->request->getQuery('mes', date('Y-m')));
		if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
			$mes = date('Y-m');
		}
		$rows = [];
		if ($idcliente <= 0) {
			$this->Flash->error(__('Cliente não vinculado ao usuário.'));
			$this->set(compact('rows', 'mes'));

			return;
		}
		try {
			$this->loadModel('ContractConsumptions');
			$list = $this->Contracts->find()
				->where([
					'Contracts.idcliente' => $idcliente,
					'Contracts.idempresa' => $idempresa,
				])
				->order(['Contracts.name' => 'ASC'])
				->all();
			foreach ($list as $contract) {
				$qSum = $this->ContractConsumptions->find();
				$sum = $qSum->select(['h' => $qSum->func()->sum('consumed_hours')])
					->where([
						'contract_id' => $contract->id,
						'reference_month' => $mes,
					])
					->first();
				$consumed = (float)($sum && isset($sum->h) ? $sum->h : 0);
				$rows[] = [
					'contract' => $contract,
					'consumed_hours' => $consumed,
					'included_hours' => (float)($contract->included_hours ?? 0),
				];
			}
		} catch (\Throwable $e) {
			$this->Flash->error(__('Não foi possível carregar os consumos.'));
		}
		$this->set(compact('rows', 'mes'));
	}
}
