<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Controller\Date;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';

class VisitasController extends AppController { 
	public function initialize() {
		parent::initialize();
		$this->loadModel('Clientes');
		$this->loadModel('Empresas');
		$this->loadModel('Tickets');
	}

	public function index() {
		$role = $this->Auth->user('role');
		if ($role == 1) return $this->redirect(['action' => 'calendario']);
		$visitas = $this->Visitas->find('all', ['contain' => ['Clientes', 'Users'],])->where(['Visitas.idempresa' => $this->Auth->user('idempresa')])->toArray();

		$this->set('title', 'Agenda');
		$this->set('visitas', $visitas);
	}

	public function indexcliente(){
		$id = $this->Auth->user('id');
		$idcliente = $this->Users->get($id)->idcliente;
		$visitas = $this->Visitas->find('all', [ 'contain' => [ 'Users']])->where(['idcliente' => $idcliente])->toArray();

		$this->set('title', 'Lista de Visitas');
		$this->set('visitas', $visitas);

	}

	public function calendario(){
		$role = $this->Auth->user('role');
		$idcliente = $this->Auth->user('idcliente');
		$id = $this->Auth->user('id');
		$idempresa = $this->Auth->user('idempresa');

		if($role == 0) $visitas = $this->Visitas->find('all', ['contain' => ['Clientes' => ['fields' => ['Clientes.razaosocial', 'Clientes.nome'] ],],])->where(['Visitas.idempresa' => $idempresa]);
		else $visitas = $this->Visitas->find('all', ['contain' => ['Clientes' => ['fields' => ['Clientes.razaosocial'],],]])->where(['idcliente' => $idcliente]);

		$users = $this->Visitas->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])->order(['username'])->where(['role' => 0, 'inativo' => 0])->toArray();

		$clientes = $this->Clientes->find('all')->where(['inativo' => '0'])->order(['razaosocial'])->toArray();
		$clientesOpt = [];
		foreach($clientes as $reg){
			if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
			else $clientesOpt[$reg->id] = $reg->nome;
		}

		//add visita pelo calendário
		$visitaAdd = $this->Visitas->newEntity();
		if ($this->request->is('post')) {
			$visita = $this->Visitas->patchEntity($visitaAdd, $this->request->getData());

			$originalDate = $this->request->getData()['data'];
			$dataquebrada = explode('/', $originalDate);
			$data1 = $dataquebrada[1] . '/' . $dataquebrada[0] . '/' . $dataquebrada[2];
			$data2 = date('m/d/Y');
			
			if(strtotime($data1) < strtotime($data2)){
				$this->Flash->error(__('Não é possível cadastrar uma visita em uma data anterior.'));
				return $this->redirect(['action' => 'calendario']);
			}
			
			$visita->idautor = $this->Auth->user('id');
			$visita->idempresa = $idempresa;
			if(isset($this->request->getData()['valor']))  $visita->valor = $this->request->getData()['valor'];

			if ($this->Visitas->save($visitaAdd)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $visita->id);
				$this->Flash->success(__('Visita agendada com sucesso!'));
				return $this->redirect(['action' => 'calendario']);
			}else{
				$this->Flash->error(__('Não foi possível agendar a visita.'));
			}
		}

		asort($clientesOpt);
		$this->set('title', 'Agenda');
		$this->set('clientes', $clientesOpt);
		$this->set(compact('visitas', 'users', 'visitaAdd'));
	}

	public function view($id = null){
		$visita = $this->Visitas->get($id, ['contain' => ['Clientes'],'contain' => ['Users'],]);
		$clientes = $this->Visitas->Clientes->find('list');

		$this->set('title', 'Detalhes Visita');
		$this->set('visita', $visita);
	}

	public function add($idticket = null){
		if ($this->Auth->user('role') == 1) return $this->redirect(['controller' => 'tickets', 'action' => 'add', 5]);
		$visita = $this->Visitas->newEntity();

		$ticket = $this->Tickets->findById($idticket)->first();
		if(!empty($ticket->data)) {
			$visita->motivo = $ticket->solicitacao;
			$visita->data = date_format($ticket->data, 'd/m/Y');
			$visita->idcliente = $ticket->idcliente;
		}

		if ($this->request->is('post')) {
			$visita = $this->Visitas->patchEntity($visita, $this->request->getData());

			$originalDate = $this->request->getData()['data'];
			$dataquebrada = explode('/', $originalDate);
			$data1 = $dataquebrada[1] . '/' . $dataquebrada[0] . '/' . $dataquebrada[2];
			$data2 = date('m/d/Y');
			
			if(strtotime($data1) < strtotime($data2)){
				$this->Flash->error(__('Não é possível cadastrar uma visita em uma data anterior.'));
				return $this->redirect(['action' => 'calendario']);
			}

			$visita->idautor = $this->Auth->user('id');
			$visita->idempresa = $this->Auth->user('idempresa');
			if(isset($this->request->getData()['valor']))  $visita->valor = $this->request->getData()['valor'];

			if ($this->Visitas->save($visita)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $visita->id);
				$this->Flash->success(__('Visita cadastrada com sucesso'));
				return $this->redirect(['action' => 'add']);
			}
			$this->Flash->error(__('Não foi possível salvar a visita.'));
		}

		$users = $this->Visitas->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])->order(['username'])->where(['role' => 0, 'inativo' => 0])->toArray();
		$clientes = $this->Clientes->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'inativo' => 0])->order(['razaosocial'])->toArray();
		$clientesOpt = [];
		foreach($clientes as $reg){
			if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
			else $clientesOpt[$reg->id] = $reg->nome;
		}
		asort($clientesOpt);

		$this->set('title', 'Cadastrar Visitas');
		$this->set('clientes', $clientesOpt);

		$this->set(compact('visita', 'users'));
	}

	public function edit($id = null) {
		error_reporting(E_ERROR | E_PARSE);
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'calendario']);

		$visita = $this->Visitas->get($id, [
			'contain' => ['Users']
		]);

		$visita->data = date_format($visita->data, 'd/m/Y');
		$visita->horaini = date_format($visita->horaini, 'H:i');
		$visita->horafim = date_format($visita->horafim, 'H:i');

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			if(isset($this->request->getData()['nomecliente'])) $visita->nomecliente = $this->request->getData()['nomecliente'];
			if(isset($this->request->getData()['valor']))  $visita->valor = $this->request->getData()['valor'];

			$this->Visitas->patchEntity($visita, $data);

			if ($this->Visitas->save($visita)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $visita->id);
				$this->Flash->success('A visita foi alterada com sucesso!');
				return $this->redirect(['action' => 'calendario']);
			}else{
			$this->Flash->error('Ocorreu um erro ao editar a visita! Tente novamente mais tarde.');
			}
		}

		$users = $this->Visitas->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])->order(['username'])->where(['role' => 0, 'inativo' => 0])->toArray();
		$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $this->Auth->user('idempresa'), 'inativo' => 0])->order(['razaosocial'])->toArray();
		$clientesOpt = [];
		foreach($clientes as $reg){
			if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
			else $clientesOpt[$reg->id] = $reg->nome;
		}

		$this->set('title', 'Editar Visita');
		$this->set('clientes', $clientesOpt);
		$this->set(compact('visita', 'users'));
	}

	public function delete($id = null) {
		if($this->Auth->user('admin') == 0){
			$this->Flash->error('Você não possui permissão para excluir esta visita!');
			return $this->redirect(['action' => 'calendario']);
		}
		$visita = $this->Visitas->get($id);

		if($visita->situacao == C_UserSituacaoCancelada && $this->Auth->user('admin') != 1){
			$this->Flash->error('Você não possui permissão para excluir esta visita!');
			return $this->redirect(['action' => 'calendario']);
		}

		if ($this->Visitas->delete($visita)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $visita->id);
			$this->Flash->success('A visita foi deletada com sucesso!');
			return $this->redirect(['action' => 'calendario']);
		}
	}
}
