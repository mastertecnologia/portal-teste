<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Filas de atendimento por empresa (técnico ↔ filas).
 */
class QueuesController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Queues');
		$this->loadModel('Tickets');
	}

	public function isAuthorized($user) {
		$action = $this->request->getParam('action');
		if (in_array($action, ['apiForTicket', 'getAvailableQueues'], true)) {
			return (int)$user['role'] === 0;
		}
		if (in_array($action, ['apiIndex', 'apiEnsureDefaults'], true)) {
			return (int)$user['admin'] === 1 && (int)$user['role'] === 0;
		}

		return (int)$user['admin'] === 1 && (int)$user['role'] === 0;
	}

	public function apiIndex() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		if (!$this->_queuesTableExists()) {
			return $this->_json(['ok' => false, 'error' => 'queues_not_installed'], 503);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$rows = $this->Queues->find()
			->where(['idempresa' => $emp])
			->order(['sort_order' => 'ASC', 'id' => 'ASC'])
			->all();
		$out = [];
		foreach ($rows as $r) {
			$e = (int)$r->idempresa;
			$out[] = [
				'id' => (int)$r->id,
				'name' => (string)$r->name,
				'company_id' => $e,
				'idempresa' => $e,
				'codigo' => $r->codigo !== null ? (string)$r->codigo : null,
				'sort_order' => (int)$r->sort_order,
			];
		}

		return $this->_json(['ok' => true, 'queues' => $out]);
	}

	/**
	 * Filas da mesma empresa do ticket (para transferência / UI).
	 */
	public function apiForTicket($ticketId = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		if (!$this->_queuesTableExists()) {
			return $this->_json(['ok' => false, 'error' => 'queues_not_installed'], 503);
		}
		$ticketId = (int)$ticketId;
		$ticket = $this->Tickets->find()
			->select(['id', 'idempresa'])
			->where(['id' => $ticketId, 'idempresa' => $this->Auth->user('idempresa')])
			->first();
		if (empty($ticket)) {
			return $this->_json(['ok' => false, 'error' => 'not_found'], 404);
		}
		$emp = (int)$ticket->idempresa;
		$rows = $this->Queues->find()
			->where(['idempresa' => $emp])
			->order(['sort_order' => 'ASC', 'id' => 'ASC'])
			->all();
		$out = [];
		foreach ($rows as $r) {
			$out[] = [
				'id' => (int)$r->id,
				'name' => (string)$r->name,
				'company_id' => $emp,
				'idempresa' => $emp,
				'codigo' => $r->codigo !== null ? (string)$r->codigo : null,
			];
		}

		return $this->_json(['ok' => true, 'queues' => $out, 'ticketId' => $ticketId, 'idempresa' => $emp, 'company_id' => $emp]);
	}

	/**
	 * Listagem de filas da mesma empresa do ticket (alias para o modal de transferência).
	 */
	public function getAvailableQueues($ticketId = null) {
		return $this->apiForTicket($ticketId);
	}

	/**
	 * Cria filas padrão (N1…serviço) na empresa atual, se ainda não existir nenhuma.
	 */
	public function apiEnsureDefaults() {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		if (!$this->_queuesTableExists()) {
			return $this->_json(['ok' => false, 'error' => 'queues_not_installed'], 503);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$n = $this->Queues->find()->where(['idempresa' => $emp])->count();
		if ($n > 0) {
			return $this->_json(['ok' => true, 'created' => 0, 'message' => 'already_has_queues']);
		}
		$defs = [
			['name' => 'N1 — Suporte inicial / triagem', 'codigo' => 'n1', 'sort_order' => 1],
			['name' => 'N2 — Suporte avançado / field service', 'codigo' => 'n2', 'sort_order' => 2],
			['name' => 'N3 — Infraestrutura / especializado', 'codigo' => 'n3', 'sort_order' => 3],
			['name' => 'NOC — Monitoramento', 'codigo' => 'noc', 'sort_order' => 4],
			['name' => 'Requisições de serviço', 'codigo' => 'servico', 'sort_order' => 5],
		];
		$created = 0;
		foreach ($defs as $d) {
			$e = $this->Queues->newEntity([
				'name' => $d['name'],
				'codigo' => $d['codigo'],
				'sort_order' => $d['sort_order'],
				'idempresa' => $emp,
			]);
			if ($this->Queues->save($e)) {
				$created++;
			}
		}

		return $this->_json(['ok' => true, 'created' => $created]);
	}

	protected function _queuesTableExists(): bool {
		try {
			$tables = $this->Queues->getConnection()->getSchemaCollection()->listTables();

			return in_array('queues', $tables, true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function _json(array $data, $status = 200) {
		return $this->response
			->withType('application/json')
			->withStatus($status)
			->withStringBody(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

}
