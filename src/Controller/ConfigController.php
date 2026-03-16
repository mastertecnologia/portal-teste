<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class ConfigController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Users');
		$this->loadModel('Config');
		$this->loadModel('Empresas');
		$this->loadModel('Empresasusers');
		$this->loadModel('Clientes');
		$this->loadModel('Financeiro');
		$this->loadModel('Vagasestacionamento');
		$this->loadModel('Usersvagasestacionamento');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Configurações');
		if(!$this->Auth->user('admin')) return $this->redirect(['controller' => 'users','action' => 'dashboard']);
	}

	public function index() {
		$nroUsuarios = $this->Users->find();
		$nroEmpresas = $this->Empresas->find();
		$nroEmpresasusers = $this->Empresasusers->find();
		$nroClientes = $this->Clientes->find();
		$nroUsuariosEquipe = $this->Users->find()->where(['role' => 0]);
		$nroUsuariosClientes = $this->Users->find()->where(['role' => 1]);

		$nroUsuarios->select(['count' => $nroUsuarios->func()->count('*')]);
		$nroEmpresas->select(['count' => $nroEmpresas->func()->count('*')]);
		$nroEmpresasusers->select(['count' => $nroEmpresasusers->func()->count('*')]);
		$nroClientes->select(['count' => $nroClientes->func()->count('*')]);
		$nroUsuariosEquipe->select(['count' => $nroUsuariosEquipe->func()->count('*')]);
		$nroUsuariosClientes->select(['count' => $nroUsuariosClientes->func()->count('*')]);

		$this->set('nroUsuarios', $nroUsuarios->toArray()[0]['count']);
		$this->set('nroEmpresas', $nroEmpresas->toArray()[0]['count']);
		$this->set('nroEmpresasusers', $nroEmpresasusers->toArray()[0]['count']);
		$this->set('nroClientes', $nroClientes->toArray()[0]['count']);
		$this->set('nroUsuariosEquipe', $nroUsuariosEquipe->toArray()[0]['count']);
		$this->set('nroUsuariosClientes', $nroUsuariosClientes->toArray()[0]['count']);
	}

	public function pastas() {
		$this->createConfigIfNotExist();

		$config = $this->Config->get(1);

		if ($this->request->is(['post', 'put'])) {
			$config = $this->Config->patchEntity($config, $this->request->getData());

			if ($this->Config->save($config)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), 1);
				$this->Flash->success("Diretórios alterados.");
				return $this->redirect(['action' => 'index']);
			} else $this->Flash->error("Erro ao salvar diretórios.");
			return $this->redirect(['action' => 'index']);
		}

		$this->set('config', $config);
	}

	public function acessos() {
		$this->createConfigIfNotExist();

		$config = $this->Config->get(1);

		if ($this->request->is(['post', 'put'])) {
			$config = $this->Config->patchEntity($config, $this->request->getData());

			if ($this->Config->save($config)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), 1);
				$this->Flash->success("Configurações de acesso alteradas.");
			} else $this->Flash->error("Erro ao salvar configurações de acesso.");
			return $this->redirect(['action' => 'index']);
		}

		$this->set('config', $config);
	}

	public function financeiro() {
		$this->createFinanceiroIfNotExist();

		$config = $this->Financeiro->get(1);

		if ($this->request->is(['post', 'put'])) {
			$config = $this->Financeiro->patchEntity($config, $this->request->getData());
			if ($this->Financeiro->save($config)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), 1);
				$this->Flash->success("Configurações alteradas.");
			} else $this->Flash->error("Erro ao salvar configurações.");
			return $this->redirect(['action' => 'index']);
		}

		$this->set('config', $config);
	}

	public function emailsuporte() {
		$this->createConfigIfNotExist();
		$config = $this->Config->get(1);

		if ($this->request->is(['post', 'put'])) {
			$config = $this->Config->patchEntity($config, $this->request->getData());

			if ($this->Config->save($config)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), 1);
				$this->Flash->success("E-mail alterado.");
			} else $this->Flash->error("Erro ao salvar e-mail.");
			return $this->redirect(['action' => 'index']);
		}

		$this->set('config', $config);
		$this->set('title', 'E-mail suporte');
	}

	public function createFinanceiroIfNotExist() {
		$config = $this->Financeiro->find('all')->first();

		if (empty($config)) {
			$config = $this->Config->newEntity();
			$config->desenvolvimento = 1;
			$config->suporte = 1;
			$config->id = 1;

			$this->Financeiro->save($config);
		}

		return $config;
	}

	public function createConfigIfNotExist() {
		$config = $this->Config->find('all')->first();

		if (empty($config)) {
			$config = $this->Config->newEntity();
			$config->id = 1;

			$this->Config->save($config);
		}

		return $config;
	}

	public function isAuthorized($user) {
		if ($user['role'] == 0) return true;

		return false;
	}
}
