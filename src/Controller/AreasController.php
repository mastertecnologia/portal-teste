<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

/**
 * Status de OS (áreas) — CRUD por empresa.
 */
class AreasController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Areas');
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
		$idempresa = $this->Auth->user('idempresa');
		$areas = $this->Areas->find('all')
			->where(['idempresa' => $idempresa])
			->order(['descricao' => 'ASC'])
			->all();
		$this->set('title', 'Status de OS');
		$this->set('areas', $areas);
	}

	public function add() {
		$area = $this->Areas->newEntity();

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$area = $this->Areas->patchEntity($area, $data);
			$area->id = $this->Empresas->incrementArea($this->Auth->user('idempresa'));
			$area->idempresa = $this->Auth->user('idempresa');

			if ($this->Areas->save($area)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $area->id);
				$this->Flash->success(__('O status foi cadastrado com sucesso!'));

				return $this->redirect(['action' => 'index']);
			}

			$this->Empresas->decrementArea($this->Auth->user('idempresa'));
			$this->Flash->error(__('Não foi possível cadastrar o status.'));
		}

		$this->set('area', $area);
		$this->set('title', 'Cadastro de status');
	}

	public function edit($id = null) {
		$area = $this->Areas->get($id);
		if ((int)$area->idempresa !== (int)$this->Auth->user('idempresa')) {
			throw new NotFoundException(__('Registro não encontrado.'));
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$area = $this->Areas->patchEntity($area, $data);

			if ($this->Areas->save($area)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $area->id);
				$this->Flash->success(__('O status foi salvo com sucesso!'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('Não foi possível salvar o status.'));
		}

		$this->set('area', $area);
		$this->set('title', 'Editar status');
	}

	public function delete($id = null) {
		$area = $this->Areas->get($id);
		if ((int)$area->idempresa !== (int)$this->Auth->user('idempresa')) {
			throw new NotFoundException(__('Registro não encontrado.'));
		}

		$idempresa = $this->Auth->user('idempresa');
		$ordens = $this->Ordensservico->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->where(['idarea' => $id, 'idempresa' => $idempresa])
			->toArray();

		if (count($ordens) === 0) {
			if ($this->Areas->delete($area)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $area->id);
				$this->Empresas->decrementArea($this->Auth->user('idempresa'), $area->id);
				$this->Flash->success(__('O status foi excluído com sucesso!'));

				return $this->redirect(['action' => 'index']);
			}
		} else {
			$listordem = '';
			foreach ($ordens as $ordem) {
				$listordem .= ' ' . strval($ordem) . ' ';
			}
			$this->Flash->error('Existem ordens de serviço relacionadas a esse status. Para excluir, altere a(s) ordem(ns):' . $listordem);

			return $this->redirect(['controller' => 'Areas', 'action' => 'index']);
		}

		$this->Flash->error(__('Não foi possível excluir.'));

		return $this->redirect(['action' => 'index']);
	}

	public function isAuthorized($user) {
		return true;
	}
}
