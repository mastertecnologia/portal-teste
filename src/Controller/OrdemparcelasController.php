<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class OrdemparcelasController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Ordemparcelas');
		$this->loadModel('User');
		$this->loadModel('Empresas');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
	}

	public function add($idordem = null) {
		$pagamento = $this->Ordemparcelas->newEntity();

		if ($this->request->is('post')) {
			$data = $this->request->getData();

			$pagamento = $this->Ordemparcelas->patchEntity($pagamento, $data);
			$pagamento->idempresa = $this->Auth->user('idempresa');
			$pagamento->iduser = $this->Auth->user('id');
			$pagamento->idordem = $idordem;
			$pagamento->data = date('d/m/Y', time());

			switch ($pagamento->nmrparcelas) {
				case '1': $pagamento->dataval2 = null;
						$pagamento->dataval3 = null;
						$pagamento->dataval4 = null;
						$pagamento->dataval5 = null; break;
				case '2': $pagamento->dataval3 = null;
						$pagamento->dataval4 = null;
						$pagamento->dataval5 = null; break;
				case '3': $pagamento->dataval4 = null;
						$pagamento->dataval5 = null; break;
				case '4': $pagamento->dataval5 = null; break;
				default: break;
			}

			$pagamentosAnt = $this->Ordemparcelas->find('all')->where(['idempresa' => $pagamento->idempresa, 'idordem' => $idordem])->toArray();
			if(sizeof($pagamentosAnt) > 0) {
				foreach ($pagamentosAnt as $reg) {
					$this->Ordemparcelas->delete($reg);
				}
			}

			if ($this->Ordemparcelas->save($pagamento)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $pagamento->id);
				$this->Flash->success("Pagamento cadastrado com sucesso");
				return $this->redirect(['controller' => 'Ordensservico', 'action' => 'edit', $idordem]);
			}

			$this->Flash->error(__('Não foi possível cadastrar o pagamento.'));
		}

		$this->set('pagamento', $pagamento);
	}

	public function delete($id = null, $redirect = 0) {
		if ($this->Auth->user('admin') !== 1) {
			$this->Flash->error('Você não possui permissões para isso. Contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$pagamento = $this->Ordemparcelas->get($id);
		$idordem = $pagamento->idordem;

		if ($this->Ordemparcelas->delete($pagamento)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			$this->Flash->success("Pagamento deletado com sucesso");

			$this->redirect(['controller' => 'Ordensservico', 'action' => 'edit', $idordem]);
		}
	}
}
