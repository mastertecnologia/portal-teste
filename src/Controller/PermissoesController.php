<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\RbacPermissionResolver;
use App\Utility\RbacUserRolesResolver;
use Cake\Core\Configure;
use Cake\Event\Event;

/**
 * Catálogo RBAC/ABAC e matriz de papéis (painel administrativo).
 * A aplicação em massa nos controllers usa o legado (admin/role) até plugar RbacComponent.
 */
class PermissoesController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('RbacPermissions');
		$this->loadModel('RbacRoles');
		$this->loadModel('RbacRolesPermissions');
		$this->loadModel('RbacUsersRoles');
		$this->loadModel('RbacGroups');
		$this->loadModel('RbacUserGroups');
		$this->loadModel('RbacGroupRoles');
		$this->loadModel('Users');
		$this->loadModel('RbacAuditAuthorizations');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('hideLayoutPageTitle', true);
		$rb = Configure::read('Rbac');
		$this->set('rbacRuntimeMode', !empty($rb['mode']) ? $rb['mode'] : 'off');
	}

	public function isAuthorized($user) {
		return !empty($user['admin']) && (int)$user['role'] === 0;
	}

	public function adminIndex() {
		$this->set('title', 'Catálogo de permissões');
		if (!$this->_rbacTablesExist()) {
			$this->set('rbacMissing', true);

			return;
		}
		$this->_ensureDefaultRoles();
		$permissions = $this->RbacPermissions->find()
			->order(['module' => 'ASC', 'sort_order' => 'ASC', 'name' => 'ASC'])
			->all();
		$byModule = [];
		foreach ($permissions as $p) {
			$m = $p->module ?: 'Outros';
			if (!isset($byModule[$m])) {
				$byModule[$m] = [];
			}
			$byModule[$m][] = $p;
		}
		$roles = $this->RbacRoles->find()->where(['active' => true])->order(['sort_order' => 'ASC'])->all();
		$this->set([
			'byModule' => $byModule,
			'roles' => $roles,
			'nPerm' => $permissions->count(),
			'nRoles' => $roles->count(),
		]);
	}

	public function adminSyncRegistry() {
		$this->request->allowMethod(['post']);
		if (!$this->_rbacTablesExist()) {
			$this->Flash->error('Execute a migration RBAC (rbac_*) antes de sincronizar.');

			return $this->redirect(['action' => 'adminIndex']);
		}
		$this->_ensureDefaultRoles();
		$file = CONFIG . 'permissions_registry.php';
		if (!is_file($file)) {
			$this->Flash->error('Arquivo config/permissions_registry.php não encontrado.');

			return $this->redirect(['action' => 'adminIndex']);
		}
		$registry = require $file;
		if (!is_array($registry)) {
			$this->Flash->error('Registry inválido.');

			return $this->redirect(['action' => 'adminIndex']);
		}
		$n = 0;
		foreach ($registry as $row) {
			if (empty($row['code'])) {
				continue;
			}
			$exists = $this->RbacPermissions->find()->where(['code' => $row['code']])->first();
			if ($exists) {
				continue;
			}
			$entity = $this->RbacPermissions->newEntity([
				'code' => $row['code'],
				'name' => isset($row['name']) ? $row['name'] : $row['code'],
				'module' => isset($row['module']) ? $row['module'] : 'Outros',
				'controller' => isset($row['controller']) ? $row['controller'] : '',
				'action' => isset($row['action']) ? $row['action'] : '*',
				'perm_type' => isset($row['perm_type']) ? $row['perm_type'] : 'rbac',
				'abac_scope' => isset($row['abac_scope']) ? $row['abac_scope'] : null,
				'description' => isset($row['description']) ? $row['description'] : null,
				'sort_order' => isset($row['sort_order']) ? (int)$row['sort_order'] : 0,
			]);
			if ($this->RbacPermissions->save($entity)) {
				$n++;
			}
		}
		$this->Flash->success("Catálogo sincronizado: {$n} nova(s) permissão(ões). Registros existentes foram preservados.");

		return $this->redirect(['action' => 'adminIndex']);
	}

	public function adminMatrix() {
		$this->set('title', 'Matriz papéis × permissões');
		if (!$this->_rbacTablesExist()) {
			$this->set('rbacMissing', true);

			return;
		}
		$this->_ensureDefaultRoles();
		$permissions = $this->RbacPermissions->find()
			->order(['module' => 'ASC', 'sort_order' => 'ASC', 'name' => 'ASC'])
			->all();
		$roles = $this->RbacRoles->find()->where(['active' => true])->order(['sort_order' => 'ASC'])->all();
		$map = [];
		foreach ($this->RbacRolesPermissions->find()->all() as $l) {
			$map[(int)$l->role_id][(int)$l->permission_id] = true;
		}
		$this->set(compact('permissions', 'roles', 'map'));
	}

	public function adminGrantSuperAll() {
		$this->request->allowMethod(['post']);
		if (!$this->_rbacTablesExist()) {
			$this->Flash->error('Tabelas RBAC ausentes.');

			return $this->redirect(['action' => 'adminIndex']);
		}
		$this->_ensureDefaultRoles();
		$role = $this->RbacRoles->find()->where(['slug' => 'super_admin'])->first();
		if (empty($role)) {
			$this->Flash->error('Papel super_admin não encontrado.');

			return $this->redirect(['action' => 'adminMatrix']);
		}
		$this->RbacRolesPermissions->deleteAll(['role_id' => $role->id]);
		foreach ($this->RbacPermissions->find()->all() as $p) {
			$link = $this->RbacRolesPermissions->newEntity([
				'role_id' => $role->id,
				'permission_id' => $p->id,
			]);
			$this->RbacRolesPermissions->save($link);
		}
		$this->Flash->success('Todas as permissões do catálogo foram associadas ao papel Super administrador.');

		return $this->redirect(['action' => 'adminMatrix']);
	}

	public function adminUsers() {
		$this->set('title', 'Papéis por usuário');
		if (!$this->_rbacUsersTablesExist()) {
			$this->set('rbacMissing', true);

			return;
		}
		$this->_ensureDefaultRoles();
		$users = $this->Users->find()
			->select(['id', 'username', 'name', 'email'])
			->where(['role' => 0, 'idcliente IS' => null])
			->order(['name' => 'ASC', 'username' => 'ASC'])
			->all();
		$roleCounts = [];
		foreach ($this->RbacUsersRoles->find()->all() as $link) {
			$uid = (int)$link->user_id;
			if (!isset($roleCounts[$uid])) {
				$roleCounts[$uid] = 0;
			}
			$roleCounts[$uid]++;
		}
		$this->set(compact('users', 'roleCounts'));
	}

	public function adminUserRoles($id = null) {
		$this->set('title', 'Papéis RBAC do usuário');
		$id = (int)$id;
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException(__('Usuário inválido.'));
		}
		if (!$this->_rbacUsersTablesExist()) {
			$this->set('rbacMissing', true);

			return;
		}
		$this->_ensureDefaultRoles();
		$user = $this->Users->find()
			->where(['id' => $id, 'role' => 0, 'idcliente IS' => null])
			->first();
		if (empty($user)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Usuário da equipe não encontrado.'));
		}

		if ($this->request->is(['post', 'put'])) {
			$ids = $this->request->getData('role_ids');
			if (!is_array($ids)) {
				$ids = [];
			}
			$ids = array_values(array_unique(array_map('intval', $ids)));
			$this->RbacUsersRoles->deleteAll(['user_id' => $id]);
			foreach ($ids as $rid) {
				if ($rid <= 0) {
					continue;
				}
				$existsRole = $this->RbacRoles->find()->where(['id' => $rid, 'active' => true])->first();
				if (!$existsRole) {
					continue;
				}
				$link = $this->RbacUsersRoles->newEntity(['user_id' => $id, 'role_id' => $rid]);
				$this->RbacUsersRoles->save($link);
			}
			$this->Flash->success('Papéis atualizados.');

			return $this->redirect(['action' => 'adminUsers']);
		}

		$roles = $this->RbacRoles->find()->where(['active' => true])->order(['sort_order' => 'ASC'])->all();
		$selected = $this->RbacUsersRoles->find()
			->select(['role_id'])
			->where(['user_id' => $id])
			->extract('role_id')
			->toList();
		$selected = array_map('intval', $selected);
		$this->set(compact('user', 'roles', 'selected'));
	}

	public function adminUserEffective($id = null) {
		$this->set('title', 'Permissões efetivas (relatório)');
		$id = (int)$id;
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException(__('Usuário inválido.'));
		}
		if (!$this->_rbacUsersTablesExist()) {
			$this->set('rbacMissing', true);

			return;
		}
		$this->_ensureDefaultRoles();
		$user = $this->Users->find()
			->where(['id' => $id, 'role' => 0, 'idcliente IS' => null])
			->first();
		if (empty($user)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Usuário da equipe não encontrado.'));
		}

		$roleIds = RbacUserRolesResolver::effectiveRoleIds($id);

		$directRoles = [];
		foreach ($this->RbacUsersRoles->find()->where(['user_id' => $id])->all() as $link) {
			$r = $this->RbacRoles->find()->where(['id' => (int)$link->role_id])->first();
			if ($r) {
				$directRoles[] = $r;
			}
		}

		$groupBlocks = [];
		if (RbacUserRolesResolver::groupTablesExist()) {
			try {
				foreach ($this->RbacUserGroups->find()->where(['user_id' => $id])->all() as $ug) {
					$gid = (int)$ug->group_id;
					$g = $this->RbacGroups->find()->where(['id' => $gid])->first();
					$gRoles = [];
					foreach ($this->RbacGroupRoles->find()->where(['group_id' => $gid])->all() as $gr) {
						$r = $this->RbacRoles->find()->where(['id' => (int)$gr->role_id])->first();
						if ($r) {
							$gRoles[] = $r;
						}
					}
					$groupBlocks[] = [
						'group' => $g,
						'roles' => $gRoles,
					];
				}
			} catch (\Exception $e) {
				$groupBlocks = [];
			}
		}

		$permIdsRaw = [];
		if ($roleIds !== []) {
			$raw = $this->RbacRolesPermissions->find()
				->select(['permission_id'])
				->where(['role_id IN' => $roleIds])
				->extract('permission_id')
				->toList();
			$permIdsRaw = array_values(array_unique(array_map('intval', $raw)));
		}
		$nPermLinks = count($permIdsRaw);
		$permIdsExpanded = RbacPermissionResolver::expandPermissionIds($permIdsRaw);
		$nPermExpanded = count($permIdsExpanded);

		$byModule = [];
		if ($permIdsExpanded !== []) {
			$perms = $this->RbacPermissions->find()
				->where(['id IN' => $permIdsExpanded])
				->order(['module' => 'ASC', 'sort_order' => 'ASC', 'code' => 'ASC'])
				->all();
			foreach ($perms as $p) {
				$m = $p->module ?: 'Outros';
				if (!isset($byModule[$m])) {
					$byModule[$m] = [];
				}
				$byModule[$m][] = $p;
			}
		}

		$this->set(compact(
			'user',
			'roleIds',
			'directRoles',
			'groupBlocks',
			'byModule',
			'nPermLinks',
			'nPermExpanded'
		));
	}

	public function adminGroups() {
		$this->set('title', 'Grupos RBAC');
		if (!$this->_rbacGroupTablesExist()) {
			$this->set('rbacGroupsMissing', true);

			return;
		}
		$this->_ensureDefaultRoles();
		$groups = $this->RbacGroups->find()->order(['sort_order' => 'ASC', 'name' => 'ASC'])->all();
		$userCounts = [];
		$roleCounts = [];
		foreach ($this->RbacUserGroups->find()->all() as $l) {
			$gid = (int)$l->group_id;
			$userCounts[$gid] = ($userCounts[$gid] ?? 0) + 1;
		}
		foreach ($this->RbacGroupRoles->find()->all() as $l) {
			$gid = (int)$l->group_id;
			$roleCounts[$gid] = ($roleCounts[$gid] ?? 0) + 1;
		}
		$this->set(compact('groups', 'userCounts', 'roleCounts'));
	}

	public function adminGroupEdit($id = null) {
		$this->set('title', 'Grupo RBAC');
		if (!$this->_rbacGroupTablesExist()) {
			$this->set('rbacGroupsMissing', true);

			return;
		}
		$id = (int)($id !== null ? $id : 0);
		if ($id > 0) {
			$group = $this->RbacGroups->find()->where(['id' => $id])->first();
			if (empty($group)) {
				throw new \Cake\Http\Exception\NotFoundException(__('Grupo não encontrado.'));
			}
		} else {
			$group = $this->RbacGroups->newEntity([
				'active' => true,
				'sort_order' => 100,
				'is_system' => false,
			]);
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			if ($id > 0 && !empty($group->is_system) && isset($data['slug'])) {
				unset($data['slug']);
			}
			if (isset($data['slug']) && is_string($data['slug'])) {
				$data['slug'] = strtolower(trim($data['slug']));
			}
			$this->RbacGroups->patchEntity($group, $data);
			if ($id <= 0) {
				$group->is_system = false;
			}
			if ($id <= 0 && empty($group->slug)) {
				$this->Flash->error('Informe um slug (ex.: equipe_noc).');

				$this->set(compact('group'));

				return;
			}
			if (!empty($group->slug) && !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $group->slug)) {
				$this->Flash->error('Slug inválido: use letras minúsculas, números, _ e -.');
				$this->set(compact('group'));

				return;
			}
			$dupQ = $this->RbacGroups->find()->where(['slug' => $group->slug]);
			if ($id > 0) {
				$dupQ->where(['id !=' => $id]);
			}
			$dup = $dupQ->first();
			if ($dup) {
				$this->Flash->error('Já existe um grupo com este slug.');
				$this->set(compact('group'));

				return;
			}
			if ($this->RbacGroups->save($group)) {
				$this->Flash->success($id > 0 ? 'Grupo atualizado.' : 'Grupo criado.');

				return $this->redirect(['action' => 'adminGroups']);
			}
			$this->Flash->error('Não foi possível salvar. Verifique os dados.');
		}

		$this->set(compact('group'));
	}

	public function adminGroupRoles($id = null) {
		$this->set('title', 'Papéis do grupo RBAC');
		$gid = (int)($id !== null ? $id : 0);
		if ($gid <= 0) {
			throw new \Cake\Http\Exception\NotFoundException(__('Grupo inválido.'));
		}
		if (!$this->_rbacGroupTablesExist()) {
			$this->set('rbacGroupsMissing', true);

			return;
		}
		$this->_ensureDefaultRoles();
		$group = $this->RbacGroups->find()->where(['id' => $gid])->first();
		if (empty($group)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Grupo não encontrado.'));
		}

		if ($this->request->is(['post', 'put'])) {
			$ids = $this->request->getData('role_ids');
			if (!is_array($ids)) {
				$ids = [];
			}
			$ids = array_values(array_unique(array_map('intval', $ids)));
			$this->RbacGroupRoles->deleteAll(['group_id' => $gid]);
			foreach ($ids as $rid) {
				if ($rid <= 0) {
					continue;
				}
				$existsRole = $this->RbacRoles->find()->where(['id' => $rid, 'active' => true])->first();
				if (!$existsRole) {
					continue;
				}
				$link = $this->RbacGroupRoles->newEntity(['group_id' => $gid, 'role_id' => $rid]);
				$this->RbacGroupRoles->save($link);
			}
			$this->Flash->success('Papéis do grupo atualizados.');

			return $this->redirect(['action' => 'adminGroups']);
		}

		$roles = $this->RbacRoles->find()->where(['active' => true])->order(['sort_order' => 'ASC'])->all();
		$selected = $this->RbacGroupRoles->find()
			->select(['role_id'])
			->where(['group_id' => $gid])
			->extract('role_id')
			->toList();
		$selected = array_map('intval', $selected);
		$this->set(compact('group', 'roles', 'selected'));
	}

	public function adminGroupUsers($id = null) {
		$this->set('title', 'Membros do grupo RBAC');
		$gid = (int)($id !== null ? $id : 0);
		if ($gid <= 0) {
			throw new \Cake\Http\Exception\NotFoundException(__('Grupo inválido.'));
		}
		if (!$this->_rbacGroupTablesExist()) {
			$this->set('rbacGroupsMissing', true);

			return;
		}
		$group = $this->RbacGroups->find()->where(['id' => $gid])->first();
		if (empty($group)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Grupo não encontrado.'));
		}

		if ($this->request->is(['post', 'put'])) {
			$ids = $this->request->getData('user_ids');
			if (!is_array($ids)) {
				$ids = [];
			}
			$ids = array_values(array_unique(array_map('intval', $ids)));
			$this->RbacUserGroups->deleteAll(['group_id' => $gid]);
			foreach ($ids as $uid) {
				if ($uid <= 0) {
					continue;
				}
				$u = $this->Users->find()
					->where(['id' => $uid, 'role' => 0, 'idcliente IS' => null])
					->first();
				if (!$u) {
					continue;
				}
				$link = $this->RbacUserGroups->newEntity(['user_id' => $uid, 'group_id' => $gid]);
				$this->RbacUserGroups->save($link);
			}
			$this->Flash->success('Membros do grupo atualizados.');

			return $this->redirect(['action' => 'adminGroups']);
		}

		$users = $this->Users->find()
			->select(['id', 'username', 'name', 'email'])
			->where(['role' => 0, 'idcliente IS' => null])
			->order(['name' => 'ASC', 'username' => 'ASC'])
			->all();
		$selected = $this->RbacUserGroups->find()
			->select(['user_id'])
			->where(['group_id' => $gid])
			->extract('user_id')
			->toList();
		$selected = array_map('intval', $selected);
		$this->set(compact('group', 'users', 'selected'));
	}

	public function adminGroupDelete($id = null) {
		$this->request->allowMethod(['post']);
		$gid = (int)($id !== null ? $id : 0);
		if ($gid <= 0) {
			throw new \Cake\Http\Exception\NotFoundException(__('Grupo inválido.'));
		}
		if (!$this->_rbacGroupTablesExist()) {
			$this->Flash->error('Tabelas de grupos ausentes.');

			return $this->redirect(['action' => 'adminIndex']);
		}
		$group = $this->RbacGroups->find()->where(['id' => $gid])->first();
		if (empty($group)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Grupo não encontrado.'));
		}
		if (!empty($group->is_system)) {
			$this->Flash->error('Grupos de sistema não podem ser excluídos.');

			return $this->redirect(['action' => 'adminGroups']);
		}
		$this->RbacGroupRoles->deleteAll(['group_id' => $gid]);
		$this->RbacUserGroups->deleteAll(['group_id' => $gid]);
		$this->RbacGroups->delete($group);
		$this->Flash->success('Grupo excluído.');

		return $this->redirect(['action' => 'adminGroups']);
	}

	public function adminRbacAudit() {
		$this->set('title', 'Auditoria RBAC');
		if (!$this->_rbacAuditTableExists()) {
			$this->set('rbacAuditMissing', true);

			return;
		}
		$rb = Configure::read('Rbac');
		$this->set('auditDecisionsDbMode', is_array($rb) && array_key_exists('audit_decisions_db', $rb) ? $rb['audit_decisions_db'] : false);

		$query = $this->RbacAuditAuthorizations->find()
			->order(['RbacAuditAuthorizations.id' => 'DESC']);
		$this->paginate = [
			'limit' => 40,
			'maxLimit' => 100,
		];
		$this->set('auditRows', $this->paginate($query));
	}

	protected function _rbacAuditTableExists() {
		try {
			$tables = $this->RbacPermissions->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_audit_authorizations', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function _rbacGroupTablesExist() {
		try {
			$tables = $this->RbacPermissions->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_groups', $tables, true)
				&& in_array('rbac_user_groups', $tables, true)
				&& in_array('rbac_group_roles', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function _rbacUsersTablesExist() {
		try {
			$tables = $this->RbacPermissions->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_users_roles', $tables, true)
				&& $this->_rbacTablesExist();
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function _rbacTablesExist() {
		try {
			$tables = $this->RbacPermissions->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_permissions', $tables, true)
				&& in_array('rbac_roles', $tables, true)
				&& in_array('rbac_roles_permissions', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function _ensureDefaultRoles() {
		$defaults = [
			['slug' => 'super_admin', 'name' => 'Super administrador', 'description' => 'Acesso total ao catálogo quando vinculado.', 'sort_order' => 10],
			['slug' => 'admin_equipe', 'name' => 'Administrador da equipe', 'description' => 'Usuários internos, filas e parâmetros.', 'sort_order' => 20],
			['slug' => 'operacao', 'name' => 'Operação', 'description' => 'OS, tickets, orçamentos, agenda.', 'sort_order' => 30],
			['slug' => 'financeiro', 'name' => 'Financeiro', 'description' => 'Locação e faturas.', 'sort_order' => 40],
			['slug' => 'leitura', 'name' => 'Somente leitura', 'description' => 'Consulta sem alteração.', 'sort_order' => 50],
			['slug' => 'cliente_portal', 'name' => 'Cliente portal', 'description' => 'Usuário externo (ABAC por cliente).', 'sort_order' => 60],
		];
		foreach ($defaults as $d) {
			$exists = $this->RbacRoles->find()->where(['slug' => $d['slug']])->first();
			if ($exists) {
				continue;
			}
			$e = $this->RbacRoles->newEntity($d + ['is_system' => true, 'active' => true]);
			$this->RbacRoles->save($e);
		}
	}
}
