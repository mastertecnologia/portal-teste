<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\RbacChecker;
use App\Utility\RbacHierarchy;
use App\Utility\RbacEffectivePermissionIds;
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
		$this->loadModel('RbacPermissionPolicies');
		$this->loadModel('RbacFieldPermissions');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('hideLayoutPageTitle', true);
		$rb = Configure::read('Rbac');
		$this->set('rbacRuntimeMode', !empty($rb['mode']) ? $rb['mode'] : 'off');
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? -1) !== 0) {
			return false;
		}
		if (!empty($user['admin'])) {
			return true;
		}
		$action = (string)$this->request->getParam('action');
		$delegate = [
			'adminIndex' => ['permissoes.catalog.view'],
			'adminSyncRegistry' => ['permissoes.registry.sync'],
			'adminMatrix' => ['permissoes.matrix.view'],
			'adminGrantSuperAll' => ['permissoes.matrix.grant_super'],
			'adminRoles' => ['permissoes.roles.edit'],
			'adminRoleEdit' => ['permissoes.roles.edit'],
			'adminUsers' => ['permissoes.users.list', 'usuarios.roles.assign'],
			'adminUserRoles' => ['permissoes.users.assign_roles', 'usuarios.roles.assign'],
			'adminUserEffective' => ['permissoes.users.effective', 'usuarios.roles.assign'],
			'adminPermissionPolicies' => ['permissoes.policies.manage'],
			'adminPermissionPolicyEdit' => ['permissoes.policies.manage'],
			'adminPermissionPolicyDelete' => ['permissoes.policies.manage'],
			'adminFieldPermissions' => ['permissoes.fields.manage'],
			'adminFieldPermissionEdit' => ['permissoes.fields.manage'],
			'adminFieldPermissionDelete' => ['permissoes.fields.manage'],
			'adminRbacAudit' => ['permissoes.audit.view'],
			'adminGroups' => ['permissoes.groups.manage', 'usuarios.groups.assign'],
			'adminGroupEdit' => ['permissoes.groups.manage', 'usuarios.groups.assign'],
			'adminGroupRoles' => ['permissoes.groups.manage', 'usuarios.groups.assign'],
			'adminGroupUsers' => ['permissoes.groups.manage', 'usuarios.groups.assign'],
			'adminGroupDelete' => ['permissoes.groups.manage', 'usuarios.groups.assign'],
		];
		if (!isset($delegate[$action])) {
			return false;
		}
		$uid = (int)$user['id'];
		foreach ($delegate[$action] as $code) {
			if (RbacChecker::userHasPermissionCode($uid, $code)) {
				return true;
			}
		}

		return false;
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
		$result = $this->RbacPermissions->syncMissingFromRegistry();
		if ($result['errors'] !== []) {
			$this->Flash->error('Sincronização com erros: ' . implode(' | ', array_slice($result['errors'], 0, 8)));
			if ($result['inserted'] === 0) {
				return $this->redirect(['action' => 'adminIndex']);
			}
		}
		$n = $result['inserted'];
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

		$matrixSpotlightUser = null;
		$matrixSpotlightPermIds = [];
		$matrixEquipeUserOptions = [];
		if ($this->_rbacUsersTablesExist()) {
			foreach ($this->Users->find()
				->select(['id', 'username', 'name'])
				->where(['role' => 0, 'idcliente IS' => null])
				->order(['name' => 'ASC', 'username' => 'ASC'])
				->all() as $u) {
				$matrixEquipeUserOptions[(int)$u->id] = ($u->name ?: $u->username) . ' (' . $u->username . ')';
			}
			$uid = (int)$this->request->getQuery('user_id');
			if ($uid > 0) {
				$spotUser = $this->Users->find()
					->select(['id', 'username', 'name'])
					->where(['id' => $uid, 'role' => 0, 'idcliente IS' => null])
					->first();
				if ($spotUser) {
					$matrixSpotlightUser = $spotUser;
					$matrixSpotlightPermIds = RbacEffectivePermissionIds::effectivePermissionIdMapForUser($uid);
				} else {
					$this->Flash->error(__('Utilizador da equipe não encontrado para o ID indicado.'));
				}
			}
		}

		$this->set(compact('permissions', 'roles', 'map', 'matrixSpotlightUser', 'matrixSpotlightPermIds', 'matrixEquipeUserOptions'));
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

	public function adminRoles() {
		$this->set('title', 'Papéis RBAC');
		if (!$this->_rbacTablesExist()) {
			$this->set('rbacMissing', true);

			return;
		}
		$this->_ensureDefaultRoles();
		$roles = $this->RbacRoles->find()->order(['sort_order' => 'ASC', 'name' => 'ASC'])->all();
		$this->set(compact('roles'));
	}

	public function adminRoleEdit($id = null) {
		$this->set('title', 'Editar papel RBAC');
		if (!$this->_rbacTablesExist()) {
			$this->set('rbacMissing', true);

			return;
		}
		$this->_ensureDefaultRoles();
		$rid = (int)($id !== null ? $id : 0);
		if ($rid <= 0) {
			throw new \Cake\Http\Exception\NotFoundException(__('Papel inválido.'));
		}
		$role = $this->RbacRoles->find()->where(['id' => $rid])->first();
		if (empty($role)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Papel não encontrado.'));
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			if (!array_key_exists('active', $data)) {
				$data['active'] = false;
			}
			$fields = ['name', 'description', 'sort_order', 'active', 'hierarchy_level'];
			if (empty($role->is_system)) {
				$fields[] = 'slug';
			}
			$this->RbacRoles->patchEntity($role, $data, ['fields' => $fields]);
			$role->hierarchy_level = max(0, min(999999, (int)$role->hierarchy_level));
			$role->sort_order = (int)$role->sort_order;
			if (empty($role->is_system)) {
				$slug = strtolower(trim((string)$role->slug));
				if ($slug === '' || !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $slug)) {
					$this->Flash->error('Slug inválido: use letras minúsculas, números, _ e -.');
					$this->set(compact('role'));

					return;
				}
				$dup = $this->RbacRoles->find()
					->where(['slug' => $slug, 'id !=' => $rid])
					->first();
				if ($dup) {
					$this->Flash->error('Já existe um papel com este slug.');
					$this->set(compact('role'));

					return;
				}
				$role->slug = $slug;
			}
			if (trim((string)$role->name) === '') {
				$this->Flash->error('Nome é obrigatório.');
				$this->set(compact('role'));

				return;
			}
			if ($this->RbacRoles->save($role)) {
				$this->Flash->success('Papel atualizado.');

				return $this->redirect(['action' => 'adminRoles']);
			}
			$this->Flash->error('Não foi possível salvar.');
		}

		$this->set(compact('role'));
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

		$selected = $this->RbacUsersRoles->find()
			->select(['role_id'])
			->where(['user_id' => $id])
			->extract('role_id')
			->toList();
		$selected = array_map('intval', $selected);

		if ($this->request->is(['post', 'put'])) {
			$ids = $this->request->getData('role_ids');
			if (!is_array($ids)) {
				$ids = [];
			}
			$ids = array_values(array_unique(array_map('intval', $ids)));
			$opId = (int)$this->Auth->user('id');
			$cap = RbacHierarchy::operatorAssignHierarchyCap($this->Auth->user('admin'), $opId);
			$allIds = array_values(array_unique(array_merge($selected, $ids)));
			$roleIdToLevel = [];
			if ($allIds !== []) {
				foreach ($this->RbacRoles->find()->where(['id IN' => $allIds])->all() as $rRow) {
					$roleIdToLevel[(int)$rRow->id] = (int)($rRow->hierarchy_level ?? 0);
				}
			}
			list($finalIds, $stripped) = RbacHierarchy::finalizeRoleIdsForSave($cap, $selected, $ids, $roleIdToLevel);
			if ($stripped !== []) {
				$this->Flash->warning('Alguns papéis excedem seu nível hierárquico (hierarchy_level) e não foram aplicados.');
			}
			$this->RbacUsersRoles->deleteAll(['user_id' => $id]);
			foreach ($finalIds as $rid) {
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

		$rolesAll = $this->RbacRoles->find()->where(['active' => true])->order(['sort_order' => 'ASC'])->all();
		$capView = RbacHierarchy::operatorAssignHierarchyCap($this->Auth->user('admin'), (int)$this->Auth->user('id'));
		$roles = RbacHierarchy::rolesVisibleForAssign($capView, $selected, $rolesAll);
		$this->set('rbacHierarchyCap', $capView);
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

		$permIdsRaw = RbacEffectivePermissionIds::roleLinkPermissionIds($id);
		$nPermLinks = count($permIdsRaw);
		$effMap = RbacEffectivePermissionIds::effectivePermissionIdMapForUser($id);
		$permIdsExpanded = array_keys($effMap);
		sort($permIdsExpanded, SORT_NUMERIC);
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

		$selected = $this->RbacGroupRoles->find()
			->select(['role_id'])
			->where(['group_id' => $gid])
			->extract('role_id')
			->toList();
		$selected = array_map('intval', $selected);

		if ($this->request->is(['post', 'put'])) {
			$ids = $this->request->getData('role_ids');
			if (!is_array($ids)) {
				$ids = [];
			}
			$ids = array_values(array_unique(array_map('intval', $ids)));
			$cap = RbacHierarchy::operatorAssignHierarchyCap($this->Auth->user('admin'), (int)$this->Auth->user('id'));
			$allIds = array_values(array_unique(array_merge($selected, $ids)));
			$roleIdToLevel = [];
			if ($allIds !== []) {
				foreach ($this->RbacRoles->find()->where(['id IN' => $allIds])->all() as $rRow) {
					$roleIdToLevel[(int)$rRow->id] = (int)($rRow->hierarchy_level ?? 0);
				}
			}
			list($finalIds, $stripped) = RbacHierarchy::finalizeRoleIdsForSave($cap, $selected, $ids, $roleIdToLevel);
			if ($stripped !== []) {
				$this->Flash->warning('Alguns papéis excedem seu nível hierárquico (hierarchy_level) e não foram aplicados ao grupo.');
			}
			$this->RbacGroupRoles->deleteAll(['group_id' => $gid]);
			foreach ($finalIds as $rid) {
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

		$rolesAll = $this->RbacRoles->find()->where(['active' => true])->order(['sort_order' => 'ASC'])->all();
		$capView = RbacHierarchy::operatorAssignHierarchyCap($this->Auth->user('admin'), (int)$this->Auth->user('id'));
		$roles = RbacHierarchy::rolesVisibleForAssign($capView, $selected, $rolesAll);
		$this->set('rbacHierarchyCap', $capView);
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

	public function adminPermissionPolicies() {
		$this->set('title', 'Políticas por permissão');
		if (!$this->_rbacPermissionPoliciesTableExists()) {
			$this->set('rbacPoliciesMissing', true);

			return;
		}
		$query = $this->RbacPermissionPolicies->find()
			->contain(['RbacPermissions'])
			->order(['RbacPermissionPolicies.priority' => 'DESC', 'RbacPermissionPolicies.id' => 'DESC']);
		$this->paginate = [
			'limit' => 40,
			'maxLimit' => 100,
		];
		$this->set('policyRows', $this->paginate($query));
	}

	public function adminPermissionPolicyEdit($id = null) {
		$this->set('title', 'Política RBAC');
		if (!$this->_rbacPermissionPoliciesTableExists()) {
			$this->set('rbacPoliciesMissing', true);

			return;
		}
		$id = (int)($id !== null ? $id : 0);
		if ($id > 0) {
			$policy = $this->RbacPermissionPolicies->find()
				->contain(['RbacPermissions'])
				->where(['RbacPermissionPolicies.id' => $id])
				->first();
			if (empty($policy)) {
				throw new \Cake\Http\Exception\NotFoundException(__('Política não encontrada.'));
			}
		} else {
			$policy = $this->RbacPermissionPolicies->newEntity([
				'active' => true,
				'priority' => 0,
			]);
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			if (isset($data['conditions_json']) && is_string($data['conditions_json'])) {
				$t = trim($data['conditions_json']);
				if ($t === '') {
					$data['conditions_json'] = null;
				} else {
					json_decode($t);
					if (json_last_error() !== JSON_ERROR_NONE) {
						$this->Flash->error('conditions_json não é JSON válido.');
						$this->RbacPermissionPolicies->patchEntity($policy, $data);
						$this->_setPermissionPolicyFormVars($policy, $id);

						return;
					}
					$data['conditions_json'] = $t;
				}
			}
			if ($id <= 0 && (empty($data['rbac_permission_id']) || (int)$data['rbac_permission_id'] <= 0)) {
				$this->Flash->error('Selecione a permissão de catálogo.');
				$this->RbacPermissionPolicies->patchEntity($policy, $data);
				$this->_setPermissionPolicyFormVars($policy, $id);

				return;
			}
			$this->RbacPermissionPolicies->patchEntity($policy, $data);
			$permProbe = $this->RbacPermissions->find()
				->where(['id' => (int)$policy->rbac_permission_id])
				->first();
			if (!$permProbe) {
				$this->Flash->error('Permissão inválida.');
				$this->_setPermissionPolicyFormVars($policy, $id);

				return;
			}
			if ($this->RbacPermissionPolicies->save($policy)) {
				$this->Flash->success($id > 0 ? 'Política atualizada.' : 'Política criada.');

				return $this->redirect(['action' => 'adminPermissionPolicies']);
			}
			$this->Flash->error('Não foi possível salvar.');
		}

		$this->_setPermissionPolicyFormVars($policy, $id);
	}

	public function adminPermissionPolicyDelete($id = null) {
		$this->request->allowMethod(['post']);
		$pid = (int)($id !== null ? $id : 0);
		if ($pid <= 0) {
			throw new \Cake\Http\Exception\NotFoundException(__('Política inválida.'));
		}
		if (!$this->_rbacPermissionPoliciesTableExists()) {
			$this->Flash->error('Tabela de políticas ausente.');

			return $this->redirect(['action' => 'adminIndex']);
		}
		$policy = $this->RbacPermissionPolicies->find()->where(['id' => $pid])->first();
		if (empty($policy)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Política não encontrada.'));
		}
		$this->RbacPermissionPolicies->delete($policy);
		$this->Flash->success('Política excluída.');

		return $this->redirect(['action' => 'adminPermissionPolicies']);
	}

	public function adminFieldPermissions() {
		$this->set('title', 'Campos por permissão');
		if (!$this->_rbacFieldPermissionsTableExists()) {
			$this->set('rbacFieldPermsMissing', true);

			return;
		}
		$query = $this->RbacFieldPermissions->find()
			->contain(['RbacPermissions'])
			->order(['RbacFieldPermissions.sort_order' => 'DESC', 'RbacFieldPermissions.id' => 'DESC']);
		$this->paginate = [
			'limit' => 40,
			'maxLimit' => 100,
		];
		$this->set('fieldRows', $this->paginate($query));
	}

	public function adminFieldPermissionEdit($id = null) {
		$this->set('title', 'Campo RBAC');
		if (!$this->_rbacFieldPermissionsTableExists()) {
			$this->set('rbacFieldPermsMissing', true);

			return;
		}
		$id = (int)($id !== null ? $id : 0);
		if ($id > 0) {
			$fieldPerm = $this->RbacFieldPermissions->find()
				->contain(['RbacPermissions'])
				->where(['RbacFieldPermissions.id' => $id])
				->first();
			if (empty($fieldPerm)) {
				throw new \Cake\Http\Exception\NotFoundException(__('Regra não encontrada.'));
			}
		} else {
			$fieldPerm = $this->RbacFieldPermissions->newEntity([
				'active' => true,
				'sort_order' => 0,
				'access_mode' => 'hidden',
			]);
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$this->RbacFieldPermissions->patchEntity($fieldPerm, $data);
			$mode = (string)$fieldPerm->access_mode;
			if ($mode !== 'inherit' && (empty($fieldPerm->rbac_permission_id) || (int)$fieldPerm->rbac_permission_id <= 0)) {
				$this->Flash->error('Para modos hidden ou readonly selecione a permissão de catálogo.');
				$this->_setFieldPermissionFormVars($fieldPerm, $id);

				return;
			}
			if ($mode === 'inherit') {
				$fieldPerm->rbac_permission_id = null;
			}
			if (trim((string)$fieldPerm->resource_key) === '') {
				$this->Flash->error('Indique a chave do recurso/campo.');
				$this->_setFieldPermissionFormVars($fieldPerm, $id);

				return;
			}
			$permProbe = null;
			if ($fieldPerm->rbac_permission_id) {
				$permProbe = $this->RbacPermissions->find()
					->where(['id' => (int)$fieldPerm->rbac_permission_id])
					->first();
			}
			if ($mode !== 'inherit' && !$permProbe) {
				$this->Flash->error('Permissão inválida.');
				$this->_setFieldPermissionFormVars($fieldPerm, $id);

				return;
			}
			if ($this->RbacFieldPermissions->save($fieldPerm)) {
				$this->Flash->success($id > 0 ? 'Regra atualizada.' : 'Regra criada.');

				return $this->redirect(['action' => 'adminFieldPermissions']);
			}
			$this->Flash->error('Não foi possível salvar.');
		}

		$this->_setFieldPermissionFormVars($fieldPerm, $id);
	}

	public function adminFieldPermissionDelete($id = null) {
		$this->request->allowMethod(['post']);
		$fid = (int)($id !== null ? $id : 0);
		if ($fid <= 0) {
			throw new \Cake\Http\Exception\NotFoundException(__('Regra inválida.'));
		}
		if (!$this->_rbacFieldPermissionsTableExists()) {
			$this->Flash->error('Tabela de campos ausente.');

			return $this->redirect(['action' => 'adminIndex']);
		}
		$row = $this->RbacFieldPermissions->find()->where(['id' => $fid])->first();
		if (empty($row)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Regra não encontrada.'));
		}
		$this->RbacFieldPermissions->delete($row);
		$this->Flash->success('Regra excluída.');

		return $this->redirect(['action' => 'adminFieldPermissions']);
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

	protected function _setPermissionPolicyFormVars($policy, $id) {
		$permList = [];
		foreach ($this->RbacPermissions->find()->order(['RbacPermissions.code' => 'ASC'])->all() as $r) {
			$permList[(int)$r->id] = $r->code . ' — ' . $r->name;
		}
		$this->set(compact('policy', 'permList', 'id'));
	}

	protected function _setFieldPermissionFormVars($fieldPerm, $id) {
		$permList = [];
		foreach ($this->RbacPermissions->find()->order(['RbacPermissions.code' => 'ASC'])->all() as $r) {
			$permList[(int)$r->id] = $r->code . ' — ' . $r->name;
		}
		$accessModes = [
			'inherit' => 'inherit — não aplicar regra (herda da tela)',
			'hidden' => 'hidden — ocultar se o utilizador não tiver a permissão',
			'readonly' => 'readonly — só leitura se tiver a permissão; ocultar se não tiver',
		];
		$this->set(compact('fieldPerm', 'permList', 'id', 'accessModes'));
	}

	protected function _rbacPermissionPoliciesTableExists() {
		try {
			$tables = $this->RbacPermissions->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_permission_policies', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function _rbacAuditTableExists() {
		try {
			$tables = $this->RbacPermissions->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_audit_authorizations', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function _rbacFieldPermissionsTableExists() {
		try {
			$tables = $this->RbacPermissions->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_field_permissions', $tables, true);
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
		$this->RbacRoles->ensureDefaultSystemRoles();
	}
}
