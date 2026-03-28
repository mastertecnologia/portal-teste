<?php
use Migrations\AbstractMigration;

/**
 * Base RBAC/ABAC: permissões catalogadas, papéis e vínculos.
 * Catálogo funcional é preenchido via PermissoesController::adminSyncRegistry (config/permissions_registry.php).
 */
class RbacPermissionsFoundation extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('rbac_permissions')) {
			$this->table('rbac_permissions')
				->addColumn('code', 'string', ['limit' => 120, 'null' => false])
				->addColumn('name', 'string', ['limit' => 255, 'null' => false])
				->addColumn('module', 'string', ['limit' => 100, 'null' => false, 'default' => ''])
				->addColumn('controller', 'string', ['limit' => 80, 'null' => false, 'default' => ''])
				->addColumn('action', 'string', ['limit' => 80, 'null' => false, 'default' => '*'])
				->addColumn('perm_type', 'string', ['limit' => 16, 'null' => false, 'default' => 'rbac'])
				->addColumn('abac_scope', 'string', ['limit' => 32, 'null' => true, 'default' => null])
				->addColumn('description', 'text', ['null' => true])
				->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
				->addTimestamps()
				->addIndex(['code'], ['unique' => true, 'name' => 'ux_rbac_permissions_code'])
				->addIndex(['module'], ['name' => 'ix_rbac_permissions_module'])
				->create();
		}

		if (!$this->hasTable('rbac_roles')) {
			$this->table('rbac_roles')
				->addColumn('slug', 'string', ['limit' => 64, 'null' => false])
				->addColumn('name', 'string', ['limit' => 120, 'null' => false])
				->addColumn('description', 'text', ['null' => true])
				->addColumn('is_system', 'boolean', ['null' => false, 'default' => true])
				->addColumn('active', 'boolean', ['null' => false, 'default' => true])
				->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
				->addTimestamps()
				->addIndex(['slug'], ['unique' => true, 'name' => 'ux_rbac_roles_slug'])
				->create();
		}

		if (!$this->hasTable('rbac_roles_permissions')) {
			$this->table('rbac_roles_permissions', ['id' => false, 'primary_key' => ['role_id', 'permission_id']])
				->addColumn('role_id', 'integer', ['null' => false])
				->addColumn('permission_id', 'integer', ['null' => false])
				->addIndex(['role_id'], ['name' => 'ix_rrp_role'])
				->addIndex(['permission_id'], ['name' => 'ix_rrp_permission'])
				->create();
		}

		if (!$this->hasTable('rbac_users_roles')) {
			$this->table('rbac_users_roles', ['id' => false, 'primary_key' => ['user_id', 'role_id']])
				->addColumn('user_id', 'integer', ['null' => false])
				->addColumn('role_id', 'integer', ['null' => false])
				->addIndex(['user_id'], ['name' => 'ix_rur_user'])
				->addIndex(['role_id'], ['name' => 'ix_rur_role'])
				->create();
		}
	}

	public function down() {
		if ($this->hasTable('rbac_users_roles')) {
			$this->table('rbac_users_roles')->drop()->save();
		}
		if ($this->hasTable('rbac_roles_permissions')) {
			$this->table('rbac_roles_permissions')->drop()->save();
		}
		if ($this->hasTable('rbac_roles')) {
			$this->table('rbac_roles')->drop()->save();
		}
		if ($this->hasTable('rbac_permissions')) {
			$this->table('rbac_permissions')->drop()->save();
		}
	}
}
