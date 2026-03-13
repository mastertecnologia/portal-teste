<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class OrdemhorasController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Ordemhoras');
		$this->loadModel('User');
		$this->loadModel('Empresas');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
	}

	public function add($idordem = null) {
		$hora = $this->Ordemhoras->newEntity();

		if ($this->request->is('post')) {
			$data = $this->request->getData();

			$hora = $this->Ordemhoras->patchEntity($hora, $data);
			$hora->idempresa = $this->Auth->user('idempresa');
			$hora->iduser = $this->Auth->user('id');
			$hora->idordem = $idordem;

			if ($this->Ordemhoras->save($hora)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $hora->id);
				$this->Flash->success("Horas cadastradas com sucesso");
				return $this->redirect(['controller' => 'Ordensservico', 'action' => 'cadhoras', $idordem]);
			}

			$this->Flash->error(__('Não foi possível cadastrar a hora.'));
		}

		$this->set('hora', $hora);
	}

	public function delete($id = null, $redirect = 0) {
		if ($this->Auth->user('admin') !== 1) {
			$this->Flash->error('Você não possui permissões para isso. Contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$hora = $this->Ordemhoras->get($id);
		$idordem = $hora->idordem;

		if ($this->Ordemhoras->delete($hora)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			$this->Flash->success("Horas deletadas com sucesso");

			if ($redirect == 1) $this->redirect(['controller' => 'Ordensservico', 'action' => 'edit', $idordem]);
			return $this->redirect(['controller' => 'Ordensservico', 'action' => 'cadhoras', $idordem]);
		}
	}
}
