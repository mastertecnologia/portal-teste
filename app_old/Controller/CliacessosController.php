<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';

class CliacessosController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Clientes');
		$this->loadModel('Cliacessos');
		$this->loadModel('Empresas');
		$this->loadModel('Users');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
	}

	public function add() {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$acesso = $this->Cliacessos->newEntity();

		if ($this->request->is('post')) {
			$data = $this->request->getData();
		
			if($data['confirmasenha'] !== $data['senha']) {
				$this->Flash->error(__('As senhas não conferem.'));
				return $this->redirect(['action' => 'index']);

			}
			$acesso = $this->Cliacessos->patchEntity($acesso, $data);
			$acesso->idempresa = $this->Auth->user('idempresa');
			$acesso->senha = criptografaSenha($data['senha']);
			$acesso->usuario = $data['usuarioaa'];

			if ($this->Cliacessos->save($acesso)) {
				$idcliente = $acesso->idcliente;
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idcliente);
				return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $idcliente]);
			}
		}

		$this->set('cliacesso', $acesso);
	}

	public function edit($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$acesso = $this->Cliacessos->get($id);

		$this->set('title', 'Acessos');

		if ($this->request->is(['put', 'post'])) {
			$data = $this->request->getData();

			$acesso = $this->Cliacessos->patchEntity($acesso, $data);

			if ($this->Cliacessos->save($acesso)) {
				$idcliente = $acesso->idcliente;

				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idcliente);

				return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $idcliente]);
			}
		}

		$this->set('cliacesso', $acesso);
	}

	public function isAuthorized($user) {
		return true;
	}

	public function delete($id = null) {
		if($this->Auth->user('admin') == 0){
			return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $idcliente]);
		}

		$acesso = $this->Cliacessos->get($id);

		if ($this->Cliacessos->delete($acesso)) {
			$idcliente = $acesso->idcliente;
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idcliente);
			return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $idcliente]);
		}
	}

	public function verificasenha() {
		$this->autoRender = false;
		extract($this->request->getData());
		$acesso = $this->Cliacessos->get($id);
		$empresa = $this->Empresas->get($acesso->idempresa);
		if($this->Auth->user('role') == 1) {
			if($this->Auth->user('permissaoacesso') == 1) {
				$cliente = $this->Clientes->get($idcliente);
				$return = $cliente->senha == criptografasenha($senhaadm) ? descriptografasenha($acesso->senha) : 'A senha administrativa informada não confere com a senha cadastrada' ;
				die($return);
			}else die('Você não possui permissão para visualizar esta senha, contate um administrador');
		}
		$return = $empresa->senhaadministrativa == criptografasenha($senhaadm) ? descriptografasenha($acesso->senha) : 'A senha administrativa informada não confere com a senha cadastrada' ;
		die($return);
	}
	
	public function changePassword($id = null) {
		$this->set('title', 'Alterar Senha');

		$acesso = $this->Cliacessos->get($id);

		if (!empty($this->request->data)) {
			$data = $this->request->data();

			if($data['password1'] !== $data['password2']){
				$this->Flash->success('As senhas não conferem!');
				return $this->redirect(['action' => 'changePassword', $id]);
			}

			$senhaatual = descriptografaSenha($acesso->senha); 

			if($data['old_password'] !== $senhaatual){
				$this->Flash->error('A senha antiga não confere!');
				return $this->redirect(['action' => 'changePassword', $id]);
			}
			$encryptionNova = criptografaSenha($data['password1']); 

			$acesso->senha = $encryptionNova;
			$acesso = $this->Users->patchEntity($acesso, $data);

			if ($this->Cliacessos->save($acesso)) {
				$idcliente = $acesso->idcliente;
				$this->Flash->success('A senha foi alterada com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);
				return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $idcliente]);
			} else {
				$this->Flash->error('Ocorreu um erro ao salvar a senha!');
			}
		}

		$this->set('cliacesso', $acesso);
	}
}
