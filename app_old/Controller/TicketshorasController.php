<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class TicketshorasController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Ticketshoras');
		$this->loadModel('User');
		$this->loadModel('Empresas');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
	}

	public function add($idticket = null) {
		$hora = $this->Ticketshoras->newEntity();

		if ($this->request->is('post')) {
			$data = $this->request->getData();

			$hora = $this->Ticketshoras->patchEntity($hora, $data);
			$hora->idempresa = $this->Auth->user('idempresa');
			$hora->iduser = $this->Auth->user('id');
			$hora->idticket = $idticket;

			if ($this->Ticketshoras->save($hora)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $hora->id);
				$this->Flash->success("Horas cadastradas com sucesso");
				return $this->redirect(['controller' => 'Tickets', 'action' => 'cadhoras', $idticket]);
			}

			$this->Flash->error(__('Não foi possível cadastrar a hora.'));
		}

		$this->set('hora', $hora);
	}

	public function delete($id = null, $redirect = 0) {
		if ($this->Auth->user('admin') !== 1) {
			$this->Flash->error('Você não possui permissões para editar sua conta. Contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$hora = $this->Ticketshoras->get($id);
		$idticket = $hora->idticket;

		if ($this->Ticketshoras->delete($hora)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);

			if ($redirect == 1) $this->redirect(['controller' => 'Tickets', 'action' => 'edit', $idticket]);

			return $this->redirect(['controller' => 'Tickets', 'action' => 'cadhoras', $idticket]);
		}
	}
}
