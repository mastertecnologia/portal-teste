<?php
use Migrations\AbstractMigration;

/**
 * Alinhar rbac_permissions: Permissoes admin* (underscore), Filas, Config bootstrap, Empresas, Portal relatórios, Visitas portal, usuarios.assign*.
 */
class RbacAdminQueuesPortalVisitasActionsPatch extends AbstractMigration {

	protected function _appendActions($code, $csv) {
		$conn = $this->getAdapter()->getConnection();
		$stmt = $conn->prepare('SELECT id, action FROM rbac_permissions WHERE code = ? LIMIT 1');
		$stmt->execute([$code]);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$row) {
			return;
		}
		$action = (string)$row['action'];
		foreach (array_filter(array_map('trim', explode(',', $csv))) as $p) {
			$p = strtolower($p);
			if ($p === '') {
				continue;
			}
			if (stripos($action, $p) !== false) {
				continue;
			}
			$action .= ($action === '' ? '' : ',') . $p;
		}
		if ($action === $row['action']) {
			return;
		}
		$u = $conn->prepare('UPDATE rbac_permissions SET action = ? WHERE id = ?');
		$u->execute([$action, $row['id']]);
	}

	public function up() {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$this->_appendActions('config.bootstrap', 'create_financeiro_if_not_exist,create_config_if_not_exist');
		$this->_appendActions('permissoes.catalog.view', 'admin_index');
		$this->_appendActions('permissoes.registry.sync', 'admin_sync_registry');
		$this->_appendActions('permissoes.matrix.view', 'admin_matrix');
		$this->_appendActions('permissoes.matrix.grant_super', 'admin_grant_super_all');
		$this->_appendActions('permissoes.users.list', 'admin_users');
		$this->_appendActions('permissoes.users.assign_roles', 'admin_user_roles');
		$this->_appendActions('permissoes.users.effective', 'admin_user_effective');
		$this->_appendActions('permissoes.groups.manage', 'admin_groups,admin_group_edit,admin_group_roles,admin_group_users,admin_group_delete');
		$this->_appendActions('permissoes.audit.view', 'admin_rbac_audit');
		$this->_appendActions('permissoes.policies.manage', 'admin_permission_policies,admin_permission_policy_edit,admin_permission_policy_delete');
		$this->_appendActions('permissoes.fields.manage', 'admin_field_permissions,admin_field_permission_edit,admin_field_permission_delete');
		$this->_appendActions('permissoes.roles.edit', 'admin_roles,admin_role_edit');
		$this->_appendActions('usuarios.roles.assign', 'admin_users,admin_user_roles,admin_user_effective');
		$this->_appendActions('usuarios.groups.assign', 'admin_groups,admin_group_edit,admin_group_roles,admin_group_users,admin_group_delete');
		$this->_appendActions('empresas.create', 'move_file,dir_logotipos');
		$this->_appendActions('empresas.update', 'delete_logotipo,delete_folder,move_file,dir_logotipos');
		$this->_appendActions('empresas.tokens.sync', 'update_token');
		$this->_appendActions('queues.admin.panel', 'admin_index');
		$this->_appendActions('queues.admin.form', 'admin_edit,admin_ensure_defaults');
		$this->_appendActions('queues.admin.technicians', 'admin_technicians');
		$this->_appendActions('queues.admin.delete', 'admin_delete');
		$this->_appendActions('queues.json.read', 'api_index,api_for_ticket,get_available_queues');
		$this->_appendActions('queues.json.write', 'api_save,api_ensure_defaults,api_support_levels');
		$this->_appendActions('portal.relatorios.export', 'exportar_excel');
		$this->_appendActions('visitas.portal.view', 'index_cliente');
	}

	public function down() {
		// Patch aditivo; rollback manual se necessário.
	}
}
