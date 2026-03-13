<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';

class ProblemasController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Ordensservico');
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
		$this->set('title', 'Tipo de OS');
		$this->set('problemas', $this->Problemas->find('all')->where() );
	}

	public function add() {
        $problema = $this->Problemas->newEntity();
        
		if ($this->request->is('post')) {
            $data = $this->request->getData();
			$problema = $this->Problemas->patchEntity($problema, $data);
			$problema->id = $this->Empresas->incrementProblema($this->Auth->user('idempresa'));
            $problema->idempresa = $this->Auth->user('idempresa');
           
            if ($this->Problemas->save($problema)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $problema->id);
                $this->Flash->success(__('O Tipo de OS foi cadastrado com sucesso!'));
                return $this->redirect(['action' => 'index']);
			}

			// Decrementa o último id em caso de erro
			$this->Empresas->decrementProblema($this->Auth->user('idempresa'));

            $this->Flash->error(__('Não foi possível cadastrar o Tipo de OS.'));
        }

		$this->set('problema', $problema);
		$this->set('title', 'Cadastro de Tipo de OS');
	}

	public function edit($id = null) {
        $problema = $this->Problemas->get($id);
        
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
            $problema = $this->Problemas->patchEntity($problema, $data);
           
            if ($this->Problemas->save($problema)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $problema->id);
                $this->Flash->success(__('O Tipo de OS foi salvo com sucesso!'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Não foi possível salvar o Tipo de OS.'));
        }
        
		$this->set('problema', $problema);
        $this->set('title', 'Editar Tipo de OS');
    }

	public function isAuthorized($user) {
		return true;
	}

	public function delete($id = null) {
		$problema = $this->Problemas->get($id);
		$idempresa = $this->Auth->user('idempresa');
		$ordens = $this->Ordensservico->find('list', ['keyField' => 'id', 'valueField' => 'id'])->where(['idproblema' => $id, 'idempresa' => $idempresa])->toArray();

		if(count($ordens) == 0){
			if ($this->Problemas->delete($problema)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $problema->id);
				// Decrementa o id
				$this->Empresas->decrementProblema($this->Auth->user('idempresa'));

				$this->Flash->success(__('O Tipo de OS foi deletado com sucesso!'));
				return $this->redirect(['action' => 'index']);
			}
		}else{
			$listordem = '';
			foreach($ordens as $ordem){
				$listordem .= ' '.strval($ordem).' ';
			}
			$this->Flash->error('Existem ordens de serviço relacionadas a essa área. Para excluir, altere a(s) ordem(ns):'.$listordem);
			return $this->redirect(['controller' => 'problemas', 'action' => 'index']);
		}
	}
}

