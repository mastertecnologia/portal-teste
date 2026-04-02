<?php
namespace App\Controller;

/**
 * Histórico consolidado de atendimento (attendance_histories). Equipe interna (role 0).
 */
class AdvancedAttendanceController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('AttendanceHistories');
		$this->loadModel('AttendanceTimeline');
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? 1) !== 0) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		$this->set('title', 'Histórico de atendimento (módulo avançado)');
		$idempresa = (int)$this->Auth->user('idempresa');
		$cid = (int)$this->request->getQuery('idcliente', 0);
		try {
			$q = $this->AttendanceHistories->find()
				->contain(['Tickets', 'Clientes'])
				->where(['AttendanceHistories.idempresa' => $idempresa])
				->order(['AttendanceHistories.modified' => 'DESC']);
			if ($cid > 0) {
				$q->where(['AttendanceHistories.idcliente' => $cid]);
			}
			$this->paginate = ['limit' => 30];
			$this->set('histories', $this->paginate($q));
		} catch (\Throwable $e) {
			$this->Flash->error(__('Tabela attendance_histories indisponível. Execute a migration do módulo avançado.'));
			$this->set('histories', []);
		}

		$ticketsRecent = [];
		try {
			$this->loadModel('Tickets');
			$tq = $this->Tickets->find()
				->contain(['Clientes'])
				->where(['Tickets.idempresa' => $idempresa]);
			if ($cid > 0) {
				$tq->where(['Tickets.idcliente' => $cid]);
			}
			$ticketsRecent = $tq->order(['Tickets.modified' => 'DESC'])->limit(50)->all();
		} catch (\Throwable $e) {
			$ticketsRecent = [];
		}
		$this->set('ticketsRecent', $ticketsRecent);
	}

	public function view($id = null) {
		$id = (int)$id;
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$h = $this->AttendanceHistories->get($id, [
				'contain' => ['Tickets', 'Clientes', 'Contracts'],
			]);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)($h->idempresa ?? 0) !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}
		$timeline = $this->AttendanceTimeline->find()
			->where(['ticket_id' => $h->ticket_id])
			->order(['AttendanceTimeline.created' => 'ASC'])
			->all();
		$this->set('title', 'Atendimento #' . $id);
		$this->set('history', $h);
		$this->set('timeline', $timeline);
		$this->set('hideInternalNotes', false);
	}
}
