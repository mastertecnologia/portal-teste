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
}
