<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';


class BancosenhasController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Empresas');
	}	
	
	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
	}

	public function index() {
		$this->set('title', 'Banco de Senhas');
		$this->set(
			'senhas',
			$this->Bancosenhas
				->find('all')
				->where(['idempresa' => $this->Auth->user('idempresa')])
		);
	}

	public function add() {
		$senha = $this->Bancosenhas->newEntity();
		
		if ($this->request->is('post')) {
			$data = $this->request->getData();

			if($data['confirmasenha'] !== $data['senha']) {
				$this->Flash->error(__('As senhas não conferem.'));
				return $this->redirect(['action' => 'index']);

			}
			$senha = $this->Bancosenhas->patchEntity($senha, $data);
			$senha->idempresa = $this->Auth->user('idempresa');
			$senha->senha = criptografaSenha($data['senha']);
		   
			if ($this->Bancosenhas->save($senha)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $senha->id);
				$this->Flash->success(__('A senha foi cadastrada com sucesso!.'));
				return $this->redirect(['action' => 'index']);
			}
			
			$this->Flash->error(__('Não foi possível cadastrar a senha.'));
		}

		$this->set('senha', $senha);
		$this->set('title', 'Cadastro de Senha');
	}

	public function edit($id = null) {
		$senha = $this->Bancosenhas->get($id);
		if ($senha->idempresa != $this->Auth->user('idempresa')) {
			$this->Flash->error('Você não possui permissão para editar este registro.');
			return $this->redirect(['action' => 'index']);
		}
		
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$senha = $this->Bancosenhas->patchEntity($senha, $data);
		   
			if ($this->Bancosenhas->save($senha)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $senha->id);
				$this->Flash->success(__('O registro foi alterado com sucesso!.'));
				return $this->redirect(['action' => 'index']);
			}
			
			$this->Flash->error(__('Não foi possível alterar o registro.'));
		}

		$this->set('senha', $senha);
		$this->set('title', 'Edição');
	}

	public function isAuthorized($user) {
		// Apenas usuários administradores podem acessar o banco de senhas
		return !empty($user) && !empty($user['admin']);
	}

	public function delete($id = null) {
		$senha = $this->Bancosenhas->get($id);
		if ($senha->idempresa != $this->Auth->user('idempresa')) {
			$this->Flash->error('Você não possui permissão para excluir este registro.');
			return $this->redirect(['action' => 'index']);
		}
		
		if ($this->Bancosenhas->delete($senha)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $senha->id);
			$this->Flash->success(__('O registro foi deletado com sucesso!'));
			return $this->redirect(['action' => 'index']);
		}
		
	}
	
	public function verificasenha($id, $senhaadm) {
		$this->autoRender = false;
		$senha = $this->Bancosenhas->get($id);
		$empresa = $this->Empresas->get($senha->idempresa);

		// Garante que o registro pertence à mesma empresa do usuário logado
		if ($senha->idempresa != $this->Auth->user('idempresa')) {
			echo 'A senha não pertence à sua empresa.';
			return;
		}

		echo $empresa->senhaadministrativa == criptografasenha($senhaadm)
			? descriptografasenha($senha->senha)
			: 'A senha administrativa informada não confere com a senha cadastrada';
	}

	public function changePassword($id = null) {
		$this->set('title', 'Alterar Senha');

		$senha = $this->Bancosenhas->get($id);

		if ($senha->idempresa != $this->Auth->user('idempresa')) {
			$this->Flash->error('Você não possui permissão para alterar esta senha.');
			return $this->redirect(['action' => 'index']);
		}

		if (!empty($this->request->data)) {
			$data = $this->request->data();

			if($data['password1'] !== $data['password2']){
				$this->Flash->success('As senhas não conferem!');
				return $this->redirect(['action' => 'changePassword', $id]);
			}

			$senhaatual = descriptografaSenha($senha->senha); 

			if($data['old_password'] !== $senhaatual){
				$this->Flash->error('A senha antiga não confere!');
				return $this->redirect(['action' => 'changePassword', $id]);
			}
			$encryptionNova = criptografaSenha($data['password1']); 

			$senha->senha = $encryptionNova;
			$senha = $this->Bancosenhas->patchEntity($senha, $data);

			if ($this->Bancosenhas->save($senha)) {
				$this->Flash->success('A senha foi alterada com sucesso!');

				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Flash->error('Ocorreu um erro ao salvar a senha!');
			}
		}

		$this->set('senha', $senha);
	}
}

