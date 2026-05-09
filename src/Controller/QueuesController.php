<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

/**
 * Filas de atendimento por empresa (técnico ↔ filas).
 */
class QueuesController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Queues');
		$this->loadModel('Tickets');
		$this->loadModel('SupportLevels');
		$this->loadModel('Users');
		$this->loadModel('Empresasusers');
		$this->loadModel('QueuesUsers');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$action = $this->request->getParam('action');
		$htmlAdmin = ['adminIndex', 'adminEdit', 'adminTechnicians', 'adminDelete', 'adminEnsureDefaults'];
		if (in_array($action, $htmlAdmin, true)) {
			$this->set('title', 'Filas e técnicos — suporte');
		}
	}

	public function isAuthorized($user) {
		$action = $this->request->getParam('action');
		if (in_array($action, ['apiForTicket', 'apiForUser', 'getAvailableQueues', 'apiSupportLevels'], true)) {
			return (int)$user['role'] === 0;
		}
		if (in_array($action, ['apiIndex', 'apiEnsureDefaults', 'apiSave'], true)) {
			return (int)$user['admin'] === 1 && (int)$user['role'] === 0;
		}
		if (in_array($action, ['adminIndex', 'adminEdit', 'adminTechnicians', 'adminDelete', 'adminEnsureDefaults'], true)) {
			return !empty($user['admin']) && (int)$user['role'] === 0;
		}

		return (int)$user['admin'] === 1 && (int)$user['role'] === 0;
	}

	/**
	 * Painel web: filas da empresa atual (Master/PGM conforme login) + atalhos.
	 */
	public function adminIndex() {
		if (!$this->_queuesTableExists()) {
			$this->Flash->error('As tabelas de filas ainda não existem neste ambiente. Execute as migrations.');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$qf = $this->Queues->find()->where(['Queues.idempresa' => $emp])->order(['Queues.sort_order' => 'ASC', 'Queues.id' => 'ASC']);
		if ($this->_supportLevelsRoutingReady()) {
			$qf->contain(['SupportLevels']);
		}
		$queues = $qf->all();
		$supportLevels = $this->_supportLevelsRoutingReady()
			? $this->SupportLevels->find()->order(['sort_order' => 'ASC'])->all()
			: [];
		$supportLevelsEnabled = $this->_supportLevelsRoutingReady();
		$this->set(compact('queues', 'supportLevels', 'emp', 'supportLevelsEnabled'));
		$this->set('hideLayoutPageTitle', true);
	}

	/**
	 * Cadastro / edição de fila (empresa atual).
	 */
	public function adminEdit($id = null) {
		if (!$this->_queuesTableExists()) {
			$this->Flash->error('Tabelas de filas não instaladas.');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$id = $id !== null ? (int)$id : 0;
		if ($this->request->is(['post', 'put'])) {
			$id = (int)$this->request->getData('id') ?: $id;
		}
		if ($id > 0) {
			$queue = $this->Queues->find()->where(['id' => $id, 'idempresa' => $emp])->first();
			if (empty($queue)) {
				$this->Flash->error('Fila não encontrada nesta empresa.');
				return $this->redirect(['action' => 'adminIndex']);
			}
		} else {
			$queue = $this->Queues->newEntity(['idempresa' => $emp, 'sort_order' => 10]);
		}
		if ($this->request->is(['post', 'put'])) {
			$queue = $this->Queues->patchEntity($queue, $this->request->getData());
			$queue->idempresa = $emp;
			$fields = ['name', 'codigo', 'sort_order', 'idempresa'];
			if ($this->_queuesColumn('description')) {
				$fields[] = 'description';
			}
			if ($this->_supportLevelsRoutingReady()) {
				$fields[] = 'support_level_id';
			}
			$fields = array_values(array_intersect($fields, $this->Queues->getSchema()->columns()));
			if ($this->Queues->save($queue, ['fields' => $fields])) {
				$this->Flash->success('Fila salva com sucesso.');
				return $this->redirect(['action' => 'adminIndex']);
			}
			$this->Flash->error('Não foi possível salvar a fila. Verifique os dados.');
		}
		$supportLevelsOptions = [];
		if ($this->_supportLevelsRoutingReady()) {
			$supportLevelsOptions = $this->SupportLevels->find('list', ['keyField' => 'id', 'valueField' => 'name'])
				->order(['sort_order' => 'ASC', 'id' => 'ASC'])
				->toArray();
		}
		$queuesHasDescription = $this->_queuesColumn('description');
		$this->set(compact('queue', 'supportLevelsOptions', 'emp', 'queuesHasDescription'));
		$this->set('hideLayoutPageTitle', true);
	}

	/**
	 * Lista técnicos vinculados à empresa atual com nível e filas (N:N).
	 */
	public function adminTechnicians() {
		if (!$this->_queuesTableExists()) {
			$this->Flash->error('Tabelas de filas não instaladas.');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$userIds = $this->Empresasusers->find()
			->select(['iduser'])
			->where(['idempresa' => $emp])
			->extract('iduser')
			->toList();
		$userIds = array_values(array_unique(array_map('intval', $userIds)));
		$tecnicos = [];
		$queuesByUser = [];
		if (!empty($userIds)) {
			$tecnicos = $this->Users->find()
				->where([
					'Users.id IN' => $userIds,
					'Users.role' => 0,
					'Users.idcliente IS' => null,
				])
				->contain($this->_usersSupportLevelColumn() ? ['SupportLevels'] : [])
				->order(['Users.name' => 'ASC', 'Users.username' => 'ASC'])
				->all();
			$tids = [];
			foreach ($tecnicos as $t) {
				$tids[] = (int)$t->id;
			}
			if (!empty($tids)) {
				$qContain = $this->_supportLevelsRoutingReady()
					? ['Queues' => ['SupportLevels'], 'SupportLevels']
					: ['Queues'];
				foreach ($this->QueuesUsers->find()->contain($qContain)->where(['user_id IN' => $tids])->all() as $link) {
					$uid = (int)$link->user_id;
					if (!isset($queuesByUser[$uid])) {
						$queuesByUser[$uid] = [];
					}
					$queuesByUser[$uid][] = $link;
				}
			}
		}
		$this->set(compact('tecnicos', 'queuesByUser', 'emp'));
		$this->set('hideLayoutPageTitle', true);
	}

	/**
	 * Remove fila se não houver tickets associados.
	 */
	public function adminDelete($id = null) {
		$this->request->allowMethod(['post']);
		if (!$this->_queuesTableExists()) {
			return $this->redirect(['action' => 'adminIndex']);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$id = (int)$id;
		$queue = $id > 0 ? $this->Queues->find()->where(['id' => $id, 'idempresa' => $emp])->first() : null;
		if (empty($queue)) {
			$this->Flash->error('Fila não encontrada.');
			return $this->redirect(['action' => 'adminIndex']);
		}
		$n = 0;
		if (in_array('queue_id', $this->Tickets->getSchema()->columns(), true)) {
			$n = $this->Tickets->find()->where(['queue_id' => $id])->count();
		}
		if ($n > 0) {
			$this->Flash->error("Existem {$n} ticket(s) nesta fila. Transfira ou reassocie antes de excluir.");
			return $this->redirect(['action' => 'adminIndex']);
		}
		if ($this->Queues->delete($queue)) {
			$this->Flash->success('Fila removida.');
		} else {
			$this->Flash->error('Não foi possível remover a fila.');
		}
		return $this->redirect(['action' => 'adminIndex']);
	}

	/**
	 * Cria filas padrão N1…serviço para a empresa atual (só se ainda não existir nenhuma).
	 */
	public function adminEnsureDefaults() {
		$this->request->allowMethod(['post']);
		if (!$this->_queuesTableExists()) {
			return $this->redirect(['action' => 'adminIndex']);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$n = $this->Queues->find()->where(['idempresa' => $emp])->count();
		if ($n > 0) {
			$this->Flash->warning('Esta empresa já possui filas cadastradas.');
			return $this->redirect(['action' => 'adminIndex']);
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
			$saveFields = array_values(array_intersect($saveFields, $this->Queues->getSchema()->columns()));
			if ($this->Queues->save($e, ['fields' => $saveFields])) {
				$created++;
			}
		}
		$this->Flash->success($created > 0 ? "Foram criadas {$created} fila(s) padrão." : 'Nenhuma fila foi criada.');
		return $this->redirect(['action' => 'adminIndex']);
	}

	protected function _usersSupportLevelColumn(): bool {
		try {
			return in_array('support_level_id', $this->Users->getSchema()->columns(), true);
		} catch (\Throwable $e) {
			return false;
		}
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
		$qf = $this->Queues->find()->where(['Queues.idempresa' => $emp])->order(['Queues.sort_order' => 'ASC', 'Queues.id' => 'ASC']);
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
	 * Query opcional escalation_only=1: apenas restringe a LISTAGEM (nível acima do ticket);
	 * não bloqueia PATCH/apiTransferirTicket — uso legado; preferir escalationOnly=false para qualquer nível.
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
		$qf = $this->Queues->find()->where(['Queues.idempresa' => $emp])->order(['Queues.sort_order' => 'ASC', 'Queues.id' => 'ASC']);
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
	 * Filas em que o técnico tem permissão explícita (queues_users).
	 * Alinhado a apiPatchAssignment: não lista fila “aberta” sem vínculo — cadastrar técnico na fila no admin.
	 * GET /queues/api-for-user/:userId — empresa da sessão; ABAC em Queues.
	 */
	public function apiForUser($userId = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		if (!$this->_queuesTableExists()) {
			return $this->_json(['ok' => false, 'error' => 'queues_not_installed'], 503);
		}
		$uid = (int)$userId;
		if ($uid <= 0) {
			return $this->_json(['ok' => false, 'error' => 'invalid_user'], 400);
		}
		$emp = (int)$this->Auth->user('idempresa');
		if ($emp <= 0) {
			return $this->_json(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$eu = $this->Empresasusers->find()
			->where(['idempresa' => $emp, 'iduser' => $uid])
			->first();
		if (empty($eu)) {
			return $this->_json(['ok' => false, 'error' => 'user_not_in_company'], 404);
		}
		$u = $this->Users->find()
			->where(['Users.id' => $uid, 'Users.role' => 0, 'Users.inativo' => 0])
			->first();
		if (empty($u)) {
			return $this->_json(['ok' => false, 'error' => 'invalid_user'], 404);
		}

		$linkedQueueIds = [];
		foreach ($this->QueuesUsers->find()->select(['queue_id'])->where(['user_id' => $uid])->all() as $link) {
			$qid = (int)$link->queue_id;
			if ($qid > 0) {
				$linkedQueueIds[$qid] = true;
			}
		}
		if ($linkedQueueIds === []) {
			return $this->_json([
				'ok' => true,
				'queues' => [],
				'userId' => $uid,
				'idempresa' => $emp,
				'company_id' => $emp,
			]);
		}

		$qf = $this->Queues->find()
			->where(['Queues.idempresa' => $emp, 'Queues.id IN' => array_keys($linkedQueueIds)])
			->order(['Queues.sort_order' => 'ASC', 'Queues.id' => 'ASC']);
		if ($this->_supportLevelsRoutingReady()) {
			$qf->contain(['SupportLevels']);
		}
		$this->Abac->applyToQuery($qf, 'Queues', 'Queues');
		$out = [];
		foreach ($qf->all() as $r) {
			$qid = (int)$r->id;
			if (!isset($linkedQueueIds[$qid])) {
				continue;
			}
			$item = [
				'id' => $qid,
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
				$item['supportLevelSort'] = $this->_queueRowLevelSort($r);
			}
			$out[] = $item;
		}

		return $this->_json([
			'ok' => true,
			'queues' => $out,
			'userId' => $uid,
			'idempresa' => $emp,
			'company_id' => $emp,
		]);
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
