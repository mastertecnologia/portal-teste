<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;

/**
 * Fase 8 — diagnóstico de rollout RBAC (rbac_users_roles + herdados via grupos Fase 3).
 *
 * Uso:
 *   bin/cake rbac_rollout stats
 *   bin/cake rbac_rollout stats --include_inactive
 *   bin/cake rbac_rollout unassigned_equipe
 *   bin/cake rbac_rollout unassigned_equipe --limit=50
 *   bin/cake rbac_rollout audit_recent --limit=30
 */
class RbacRolloutShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription('Diagnóstico RBAC: equipe/portal com ou sem papéis (direto e/ou via rbac_user_groups + rbac_group_roles).');
		$parser->addOption('include_inactive', [
			'boolean' => true,
			'default' => false,
			'help' => 'Incluir utilizadores com users.inativo=1 nas contagens/listagem.',
		]);
		$parser->addOption('limit', [
			'short' => 'l',
			'default' => '100',
			'help' => 'Máximo de linhas (unassigned_equipe ou audit_recent).',
		]);

		return $parser;
	}

	public function main() {
		$this->out('Comandos: stats | unassigned_equipe | audit_recent');
		$this->out('Ex.: bin/cake rbac_rollout stats');
		$this->out('    bin/cake rbac_rollout unassigned_equipe --limit=50');
		$this->out('    bin/cake rbac_rollout audit_recent --limit=30');
	}

	public function audit_recent() {
		if (!$this->_auditTableExists()) {
			$this->err('Tabela rbac_audit_authorizations ausente (migration Fase 3).');

			return;
		}
		$limit = isset($this->params['limit']) ? (int)$this->params['limit'] : 50;
		if ($limit < 1) {
			$limit = 50;
		}
		if ($limit > 500) {
			$limit = 500;
		}
		$rows = TableRegistry::get('RbacAuditAuthorizations')->find()
			->order(['id' => 'DESC'])
			->limit($limit)
			->all();
		$this->out(sprintf('--- rbac_audit_authorizations (últimas %d) ---', $limit));
		if ($rows->count() === 0) {
			$this->out('Nenhum registo. Ative Rbac.audit_decisions_db em config/rbac.php.');

			return;
		}
		foreach ($rows as $r) {
			$g = !empty($r->granted) ? 'ALLOW' : 'DENY';
			$this->out(sprintf(
				'id=%d %s user=%d %s::%s code=%s ctx=%s',
				(int)$r->id,
				$g,
				(int)$r->user_id,
				(string)$r->controller,
				(string)$r->action,
				$r->permission_code !== null ? (string)$r->permission_code : '—',
				$r->context_json !== null ? substr((string)$r->context_json, 0, 120) : '—'
			));
		}
	}

	public function stats() {
		if (!$this->_rbacTablesOk()) {
			$this->err('Tabelas rbac_permissions / rbac_users_roles indisponíveis.');

			return;
		}

		$includeInactive = !empty($this->params['include_inactive']);
		$users = TableRegistry::get('Users');
		$assignedIds = $this->_effectiveRbacUserIds();

		$this->out('--- RBAC rollout (stats) ---');
		if ($includeInactive) {
			$this->out('Filtro users.inativo: nenhum (inclui inativos).');
		} else {
			$this->out('Filtro users.inativo: apenas ativos (0 ou NULL).');
		}

		foreach ([0 => 'equipe (role=0)', 1 => 'portal (role=1)'] as $role => $label) {
			$q = $users->find()->where(['role' => $role]);
			if (!$includeInactive) {
				$q->where($this->_activeUserConditions());
			}
			$total = $q->count();
			$with = 0;
			if ($assignedIds !== []) {
				$qw = $users->find()->where(['role' => $role, 'id IN' => $assignedIds]);
				if (!$includeInactive) {
					$qw->where($this->_activeUserConditions());
				}
				$with = $qw->count();
			}
			$without = max(0, $total - $with);
			$this->out(sprintf('%s: total=%d | com papéis RBAC (direto ou grupo)=%d | sem=%d', $label, $total, $with, $without));
		}
	}

	public function unassigned_equipe() {
		if (!$this->_rbacTablesOk()) {
			$this->err('Tabelas rbac_* indisponíveis.');

			return;
		}

		$limit = isset($this->params['limit']) ? (int)$this->params['limit'] : 100;
		if ($limit < 1) {
			$limit = 100;
		}
		if ($limit > 5000) {
			$limit = 5000;
		}

		$includeInactive = !empty($this->params['include_inactive']);
		$assignedIds = $this->_effectiveRbacUserIds();

		$users = TableRegistry::get('Users');
		$q = $users->find()
			->select(['id', 'username', 'name', 'inativo'])
			->where(['role' => 0])
			->order(['id' => 'ASC'])
			->limit($limit);

		if (!$includeInactive) {
			$q->where($this->_activeUserConditions());
		}
		if ($assignedIds !== []) {
			$q->where(['id NOT IN' => $assignedIds]);
		}

		$rows = $q->toArray();
		$this->out(sprintf('--- Equipe sem papéis RBAC efetivos (direto nem grupo; até %d linhas) ---', $limit));
		if ($rows === []) {
			$this->out('Nenhum registo (ou todos já têm papel).');

			return;
		}
		foreach ($rows as $u) {
			$this->out(sprintf(
				'id=%d username=%s name=%s inativo=%s',
				(int)$u->id,
				(string)$u->username,
				(string)$u->name,
				isset($u->inativo) ? (string)$u->inativo : ''
			));
		}
	}

	/**
	 * Utilizadores com rbac_users_roles OU com grupo que tem rbac_group_roles (alinha ao RbacComponent).
	 *
	 * @return int[]
	 */
	protected function _effectiveRbacUserIds() {
		$raw = TableRegistry::get('RbacUsersRoles')->find()
			->select(['user_id'])
			->all()
			->extract('user_id')
			->toList();

		$ids = array_values(array_unique(array_map('intval', $raw)));

		if (!$this->_groupTablesExist()) {
			return $ids;
		}
		try {
			$gWithRoles = TableRegistry::get('RbacGroupRoles')->find()
				->select(['group_id'])
				->all()
				->extract('group_id')
				->toList();
			$gWithRoles = array_values(array_unique(array_map('intval', $gWithRoles)));
			if ($gWithRoles === []) {
				return $ids;
			}
			$fromGroups = TableRegistry::get('RbacUserGroups')->find()
				->select(['user_id'])
				->where(['group_id IN' => $gWithRoles])
				->all()
				->extract('user_id')
				->toList();
			$fromGroups = array_values(array_unique(array_map('intval', $fromGroups)));
			if ($fromGroups !== []) {
				$ids = array_values(array_unique(array_merge($ids, $fromGroups)));
			}
		} catch (\Exception $e) {
		}

		return $ids;
	}

	protected function _groupTablesExist() {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_user_groups', $tables, true)
				&& in_array('rbac_group_roles', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function _activeUserConditions() {
		return [
			'OR' => [
				['inativo' => 0],
				['inativo IS' => null],
			],
		];
	}

	protected function _rbacTablesOk() {
		try {
			$conn = TableRegistry::get('RbacPermissions')->getConnection();
			$tables = $conn->getSchemaCollection()->listTables();

			return in_array('rbac_permissions', $tables, true)
				&& in_array('rbac_users_roles', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function _auditTableExists() {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_audit_authorizations', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}
}
