<?php
namespace App\Controller;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

/**
 * Histórico de atendimento no portal: sem notas internas na timeline.
 */
class PortalAdvancedAttendanceController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('AttendanceHistories');
		$this->loadModel('AttendanceTimeline');
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? -1) !== C_RoleCliente) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		$this->set('title', 'Histórico de atendimento');
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($idcliente <= 0) {
			$this->Flash->error(__('Cliente não vinculado ao usuário.'));
			$this->set('histories', []);

			return;
		}
		try {
			$q = $this->AttendanceHistories->find()
				->contain(['Tickets'])
				->where([
					'AttendanceHistories.idcliente' => $idcliente,
					'AttendanceHistories.idempresa' => $idempresa,
				])
				->order(['AttendanceHistories.modified' => 'DESC']);
			$this->paginate = ['limit' => 30];
			$this->set('histories', $this->paginate($q));
		} catch (\Throwable $e) {
			$this->Flash->error(__('Módulo indisponível.'));
			$this->set('histories', []);
		}

		$ticketsRecent = [];
		try {
			$this->loadModel('Tickets');
			$ticketsRecent = $this->Tickets->find()
				->contain(['Clientes'])
				->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.idcliente' => $idcliente,
				])
				->order(['Tickets.created' => 'DESC'])
				->limit(40)
				->all();
		} catch (\Throwable $e) {
			$ticketsRecent = [];
		}
		$this->set('ticketsRecent', $ticketsRecent);
	}

	public function view($id = null) {
		$id = (int)$id;
		$idcliente = (int)$this->Auth->user('idcliente');
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0 || $idcliente <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$h = $this->AttendanceHistories->get($id, [
				'contain' => ['Tickets', 'Contracts'],
			]);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)$h->idcliente !== $idcliente || (int)($h->idempresa ?? 0) !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}
		$timeline = $this->AttendanceTimeline->find()
			->select(['id', 'ticket_id', 'event_type', 'event_label', 'public_note', 'created', 'actor_type'])
			->where(['ticket_id' => $h->ticket_id])
			->order(['AttendanceTimeline.created' => 'ASC'])
			->all();
		$this->set('title', 'Atendimento');
		$this->set('history', $h);
		$this->set('timeline', $timeline);
	}
}
