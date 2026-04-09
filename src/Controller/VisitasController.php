<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Controller\Date;
use App\Service\Agenda\BrasilFeriadosService;

$__pgmUserConstants = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (is_file($__pgmUserConstants)) {
	require_once $__pgmUserConstants;
}
if (!defined('C_RoleCliente')) {
	define('C_RoleCliente', 1);
}
if (!defined('C_RoleFuncionario')) {
	define('C_RoleFuncionario', 0);
}
if (!defined('C_ClientesTipoJuridica')) {
	define('C_ClientesTipoJuridica', 2);
}
if (!defined('C_ClientesTipoFisica')) {
	define('C_ClientesTipoFisica', 1);
}

class VisitasController extends AppController { 
	public function initialize() {
		parent::initialize();
		$this->loadModel('Clientes');
		$this->loadModel('Empresas');
		$this->loadModel('Tickets');
		$this->loadModel('Feriados');
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
			$this->normalizarCamposAgendaVisita($visitaAdd, $this->request->getData());
			$visitaAdd->set('lembrete_notificado_em', null);

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
		$this->set($this->opcoesAgendaFormulario());
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
			$this->normalizarCamposAgendaVisita($visita, $this->request->getData());
			$visita->set('lembrete_notificado_em', null);

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
		$this->set($this->opcoesAgendaFormulario());

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
			$antes = $this->Visitas->get($id, ['contain' => []]);
			if(isset($this->request->getData()['nomecliente'])) $visita->nomecliente = $this->request->getData()['nomecliente'];
			if(isset($this->request->getData()['valor']))  $visita->valor = $this->request->getData()['valor'];

			$this->Visitas->patchEntity($visita, $data);
			$this->normalizarCamposAgendaVisita($visita, $data);
			if ($this->visitaLembreteDeveResetar($antes, $visita)) {
				$visita->set('lembrete_notificado_em', null);
			}

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
		$this->set($this->opcoesAgendaFormulario());
		$this->set(compact('visita', 'users'));
	}

	public function feriados() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$start = $this->request->getQuery('start');
		$end = $this->request->getQuery('end');
		if (!is_string($start) || !is_string($end)) {
			return $this->_agendaJsonCalendario([]);
		}
		$startYmd = substr($start, 0, 10);
		$endYmd = substr($end, 0, 10);
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startYmd) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endYmd)) {
			return $this->_agendaJsonCalendario([]);
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$uf = null;
		if ($idempresa > 0) {
			try {
				$emp = $this->Empresas->find()
					->where(['Empresas.id' => $idempresa])
					->contain(['Cidades.Estados'])
					->first();
				if ($emp && !empty($emp->cidade) && !empty($emp->cidade->estado)) {
					$sg = trim((string)$emp->cidade->estado->sigla);
					if (strlen($sg) >= 2) {
						$uf = strtoupper(substr($sg, 0, 2));
					}
				}
			} catch (\Throwable $e) {
			}
		}
		$svc = new BrasilFeriadosService();
		$events = $svc->fullCalendarEvents($startYmd, $endYmd, $uf);
		foreach ($this->Feriados->findParaCalendario($startYmd, $endYmd, $idempresa) as $f) {
			$d = $f->data;
			if (!($d instanceof \DateTimeInterface)) {
				continue;
			}
			$events[] = [
				'title' => (string)$f->descricao,
				'start' => $d->format('Y-m-d'),
				'allDay' => true,
				'editable' => false,
				'classNames' => ['pgm-fc-feriado', 'pgm-fc-feriado-empresa'],
			];
		}

		return $this->_agendaJsonCalendario($events);
	}

	protected function _agendaJsonCalendario(array $events) {
		$this->response = $this->response->withType('application/json')
			->withStringBody(json_encode($events, JSON_UNESCAPED_UNICODE));

		return $this->response;
	}

	protected function opcoesAgendaFormulario() {
		return [
			'opLembrete' => [
				'' => 'Sem lembrete',
				'15' => '15 minutos antes',
				'30' => '30 minutos antes',
				'60' => '1 hora antes',
				'120' => '2 horas antes',
				'1440' => '1 dia antes',
				'2880' => '2 dias antes',
			],
			'opAgendaTipo' => [
				0 => 'Visita',
				1 => 'Reunião',
				2 => 'Tarefa',
				3 => 'Lembrete',
			],
		];
	}

	protected function normalizarCamposAgendaVisita($visita, array $data) {
		$tipo = isset($data['agenda_tipo']) ? (int)$data['agenda_tipo'] : (int)$visita->get('agenda_tipo');
		if ($tipo < 0 || $tipo > 3) {
			$tipo = 0;
		}
		$visita->set('agenda_tipo', $tipo);
		$tit = isset($data['agenda_titulo']) ? trim((string)$data['agenda_titulo']) : '';
		$visita->set('agenda_titulo', $tit !== '' ? $tit : null);
		$lm = isset($data['lembrete_minutos']) ? $data['lembrete_minutos'] : '';
		if ($lm === '' || $lm === null) {
			$visita->set('lembrete_minutos', null);
		} else {
			$visita->set('lembrete_minutos', max(1, (int)$lm));
		}
	}

	protected function visitaFingerprintInicio($visita) {
		$d = $visita->get('data');
		$h = $visita->get('horaini');
		if ($d instanceof \DateTimeInterface && $h instanceof \DateTimeInterface) {
			return $d->format('Y-m-d') . ' ' . $h->format('H:i:s');
		}

		return null;
	}

	protected function visitaLembreteDeveResetar($antes, $depois) {
		$la = (int)($antes->get('lembrete_minutos') ?? 0);
		$ld = (int)($depois->get('lembrete_minutos') ?? 0);
		if ($la !== $ld) {
			return true;
		}
		$fa = $this->visitaFingerprintInicio($antes);
		$fd = $this->visitaFingerprintInicio($depois);
		if ($fa !== null && $fd !== null && $fa !== $fd) {
			return true;
		}

		return false;
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
