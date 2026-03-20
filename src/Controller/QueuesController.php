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
		$this->loadModel('SupportLevels');
	}

	public function isAuthorized($user) {
		$action = $this->request->getParam('action');
		if (in_array($action, ['apiForTicket', 'getAvailableQueues', 'apiSupportLevels'], true)) {
			return (int)$user['role'] === 0;
		}
		if (in_array($action, ['apiIndex', 'apiEnsureDefaults', 'apiSave'], true)) {
			return (int)$user['admin'] === 1 && (int)$user['role'] === 0;
		}

		return (int)$user['admin'] === 1 && (int)$user['role'] === 0;
	}

	public function apiSupportLevels() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		if (!$this->_supportLevelsRoutingReady()) {
			return $this->_json(['ok' => true, 'supportLevels' => [], 'enabled' => false]);
		}
		$out = [];
		foreach ($this->SupportLevels->find()->order(['sort_order' => 'ASC', 'id' => 'ASC'])->all() as $r) {
			$out[] = [
				'id' => (int)$r->id,
				'name' => (string)$r->name,
				'description' => $r->description !== null ? (string)$r->description : null,
				'sort_order' => (int)$r->sort_order,
			];
		}

		return $this->_json(['ok' => true, 'supportLevels' => $out, 'enabled' => true]);
	}

	public function apiIndex() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		if (!$this->_queuesTableExists()) {
			return $this->_json(['ok' => false, 'error' => 'queues_not_installed'], 503);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$qf = $this->Queues->find()->where(['idempresa' => $emp])->order(['sort_order' => 'ASC', 'id' => 'ASC']);
		if ($this->_supportLevelsRoutingReady()) {
			$qf->contain(['SupportLevels']);
		}
		$out = [];
		foreach ($qf->all() as $r) {
			$item = [
				'id' => (int)$r->id,
				'name' => (string)$r->name,
				'company_id' => $emp,
				'idempresa' => $emp,
				'codigo' => $r->codigo !== null ? (string)$r->codigo : null,
				'sort_order' => (int)$r->sort_order,
				'description' => isset($r->description) && $r->description !== null ? (string)$r->description : null,
			];
			if ($this->_supportLevelsRoutingReady()) {
				$item['support_level_id'] = !empty($r->support_level_id) ? (int)$r->support_level_id : null;
				$item['supportLevelName'] = !empty($r->support_level) ? (string)$r->support_level->name : null;
			}
			$out[] = $item;
		}

		return $this->_json(['ok' => true, 'queues' => $out]);
	}

	/**
	 * Cria ou atualiza fila na empresa atual (admin).
	 * POST JSON: { id?, name, support_level_id?, description?, codigo?, sort_order? }
	 */
	public function apiSave() {
		$this->request->allowMethod(['post', 'put']);
		$this->autoRender = false;
		if (!$this->_queuesTableExists()) {
			return $this->_json(['ok' => false, 'error' => 'queues_not_installed'], 503);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$name = isset($body['name']) ? trim((string)$body['name']) : '';
		if ($name === '') {
			return $this->_json(['ok' => false, 'error' => 'nome_obrigatorio'], 400);
		}
		$id = isset($body['id']) ? (int)$body['id'] : 0;
		$q = null;
		if ($id > 0) {
			$q = $this->Queues->find()->where(['id' => $id, 'idempresa' => $emp])->first();
			if (empty($q)) {
				return $this->_json(['ok' => false, 'error' => 'not_found'], 404);
			}
		} else {
			$q = $this->Queues->newEntity(['idempresa' => $emp]);
		}
		$q->name = $name;
		if (array_key_exists('codigo', $body)) {
			$c = $body['codigo'];
			$q->codigo = $c !== null && $c !== '' ? trim((string)$c) : null;
		}
		if (array_key_exists('sort_order', $body) && $body['sort_order'] !== '' && $body['sort_order'] !== null) {
			$q->sort_order = (int)$body['sort_order'];
		}
		if ($this->_queuesColumn('description') && array_key_exists('description', $body)) {
			$d = $body['description'];
			$q->description = $d !== null && trim((string)$d) !== '' ? trim((string)$d) : null;
		}
		if ($this->_supportLevelsRoutingReady() && array_key_exists('support_level_id', $body)) {
			$sl = $body['support_level_id'];
			if ($sl === null || $sl === '') {
				$q->support_level_id = null;
			} else {
				$sid = (int)$sl;
				$okSl = $this->SupportLevels->find()->where(['id' => $sid])->first();
				if (empty($okSl)) {
					return $this->_json(['ok' => false, 'error' => 'support_level_invalido'], 400);
				}
				$q->support_level_id = $sid;
			}
		}
		$fields = ['name', 'idempresa', 'codigo', 'sort_order'];
		if ($this->_queuesColumn('description')) {
			$fields[] = 'description';
		}
		if ($this->_supportLevelsRoutingReady()) {
			$fields[] = 'support_level_id';
		}
		if (!$this->Queues->save($q, ['fields' => array_values(array_intersect($fields, $this->Queues->getSchema()->columns()))])) {
			return $this->_json(['ok' => false, 'error' => 'save_failed'], 500);
		}

		return $this->_json(['ok' => true, 'queue' => ['id' => (int)$q->id, 'name' => (string)$q->name]]);
	}

	/**
	 * Filas da mesma empresa do ticket (para transferência / UI).
	 * Query: escalation_only=1 — só filas com nível (sort) estritamente acima do nível atual do ticket.
	 */
	public function apiForTicket($ticketId = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		if (!$this->_queuesTableExists()) {
			return $this->_json(['ok' => false, 'error' => 'queues_not_installed'], 503);
		}
		$ticketId = (int)$ticketId;
		$tcols = $this->Tickets->getSchema()->columns();
		$select = ['id', 'idempresa', 'queue_id', 'nivel_atendimento'];
		if (in_array('support_level_id', $tcols, true)) {
			$select[] = 'support_level_id';
		}
		$ticket = $this->Tickets->find()
			->select($select)
			->where(['id' => $ticketId, 'idempresa' => $this->Auth->user('idempresa')])
			->first();
		if (empty($ticket)) {
			return $this->_json(['ok' => false, 'error' => 'not_found'], 404);
		}
		$emp = (int)$ticket->idempresa;
		$escalationOnly = (string)$this->request->getQuery('escalation_only') === '1'
			|| (string)$this->request->getQuery('escalation_only') === 'true';
		$curOrd = $this->_ticketCurrentLevelSort($ticket);
		$qf = $this->Queues->find()->where(['idempresa' => $emp])->order(['sort_order' => 'ASC', 'id' => 'ASC']);
		if ($this->_supportLevelsRoutingReady()) {
			$qf->contain(['SupportLevels']);
		}
		$out = [];
		foreach ($qf->all() as $r) {
			$dstOrd = $this->_queueRowLevelSort($r);
			if ($escalationOnly && $this->_supportLevelsRoutingReady()) {
				if ($curOrd > 0 && $dstOrd > 0 && $dstOrd <= $curOrd) {
					continue;
				}
			}
			$item = [
				'id' => (int)$r->id,
				'name' => (string)$r->name,
				'company_id' => $emp,
				'idempresa' => $emp,
				'codigo' => $r->codigo !== null ? (string)$r->codigo : null,
				'sort_order' => (int)$r->sort_order,
			];
			if ($this->_supportLevelsRoutingReady()) {
				$item['support_level_id'] = !empty($r->support_level_id) ? (int)$r->support_level_id : null;
				$item['supportLevelName'] = !empty($r->support_level) ? (string)$r->support_level->name : null;
				$item['supportLevelSort'] = $dstOrd;
			}
			$out[] = $item;
		}

		return $this->_json([
			'ok' => true,
			'queues' => $out,
			'ticketId' => $ticketId,
			'idempresa' => $emp,
			'company_id' => $emp,
			'escalationOnly' => $escalationOnly,
			'supportLevelsEnabled' => $this->_supportLevelsRoutingReady(),
		]);
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
		$mapCodOrd = ['n1' => 1, 'n2' => 2, 'n3' => 3, 'noc' => 4, 'servico' => 5];
		$defs = [
			['name' => 'N1 — Suporte inicial / triagem', 'codigo' => 'n1', 'sort_order' => 1],
			['name' => 'N2 — Suporte avançado / field service', 'codigo' => 'n2', 'sort_order' => 2],
			['name' => 'N3 — Infraestrutura / especializado', 'codigo' => 'n3', 'sort_order' => 3],
			['name' => 'NOC — Monitoramento', 'codigo' => 'noc', 'sort_order' => 4],
			['name' => 'Requisições de serviço', 'codigo' => 'servico', 'sort_order' => 5],
		];
		$created = 0;
		foreach ($defs as $d) {
			$data = [
				'name' => $d['name'],
				'codigo' => $d['codigo'],
				'sort_order' => $d['sort_order'],
				'idempresa' => $emp,
			];
			if ($this->_supportLevelsRoutingReady()) {
				$ord = $mapCodOrd[$d['codigo']] ?? null;
				if ($ord !== null) {
					$sl = $this->SupportLevels->find()->where(['sort_order' => $ord])->first();
					if ($sl) {
						$data['support_level_id'] = (int)$sl->id;
					}
				}
			}
			$e = $this->Queues->newEntity($data);
			$saveFields = ['name', 'codigo', 'sort_order', 'idempresa'];
			if ($this->_supportLevelsRoutingReady()) {
				$saveFields[] = 'support_level_id';
			}
			if ($this->Queues->save($e, ['fields' => array_values(array_intersect($saveFields, $this->Queues->getSchema()->columns()))])) {
				$created++;
			}
		}

		return $this->_json(['ok' => true, 'created' => $created]);
	}

	protected function _queuesColumn(string $col): bool {
		try {
			return in_array($col, $this->Queues->getSchema()->columns(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function _supportLevelsRoutingReady(): bool {
		static $ok = null;
		if ($ok !== null) {
			return $ok;
		}
		try {
			$tables = $this->Queues->getConnection()->getSchemaCollection()->listTables();
			$ok = in_array('support_levels', $tables, true)
				&& in_array('support_level_id', $this->Queues->getSchema()->columns(), true);
		} catch (\Throwable $e) {
			$ok = false;
		}

		return $ok;
	}

	protected function _supportLevelSortById(?int $levelId): int {
		if (!$this->_supportLevelsRoutingReady() || $levelId === null || $levelId <= 0) {
			return 0;
		}
		static $cache = [];
		if (isset($cache[$levelId])) {
			return $cache[$levelId];
		}
		$sl = $this->SupportLevels->find()->select(['sort_order'])->where(['id' => $levelId])->first();
		$cache[$levelId] = $sl ? (int)$sl->sort_order : 0;

		return $cache[$levelId];
	}

	protected function _queueRowLevelSort($queueRow): int {
		if (!empty($queueRow->support_level)) {
			return (int)$queueRow->support_level->sort_order;
		}
		if ($this->_supportLevelsRoutingReady() && !empty($queueRow->support_level_id)) {
			return $this->_supportLevelSortById((int)$queueRow->support_level_id);
		}

		return (int)($queueRow->sort_order ?? 0);
	}

	protected function _ticketCurrentLevelSort($ticket): int {
		$tcols = $this->Tickets->getSchema()->columns();
		if ($this->_supportLevelsRoutingReady() && in_array('support_level_id', $tcols, true)
			&& !empty($ticket->support_level_id) && (int)$ticket->support_level_id > 0) {
			$s = $this->_supportLevelSortById((int)$ticket->support_level_id);
			if ($s > 0) {
				return $s;
			}
		}
		if (!empty($ticket->queue_id)) {
			try {
				$q = $this->Queues->get((int)$ticket->queue_id, ['contain' => $this->_supportLevelsRoutingReady() ? ['SupportLevels'] : []]);

				return $this->_queueRowLevelSort($q);
			} catch (\Throwable $e) {
			}
		}
		if (in_array('nivel_atendimento', $tcols, true)) {
			return (int)($ticket->nivel_atendimento ?? 1);
		}

		return 0;
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
