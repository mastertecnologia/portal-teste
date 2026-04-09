<?php
use Migrations\AbstractMigration;

/**
 * Fase 3 (parcial): grupos de utilizadores, políticas ABAC futuras, campos, auditoria de autorização.
 * Não remove nem altera dados existentes em rbac_users_roles / rbac_roles_permissions.
 * hierarchy_level em rbac_roles para futura anti-escalação (default 0).
 */
class RbacPhase3GroupsPoliciesAudit extends AbstractMigration {

	public function up() {
		if ($this->hasTable('rbac_roles')) {
			$t = $this->table('rbac_roles');
			if (!$t->hasColumn('hierarchy_level')) {
				$t->addColumn('hierarchy_level', 'integer', [
					'null' => false,
					'default' => 0,
				])->update();
			}
		}

		if (!$this->hasTable('rbac_groups')) {
			$this->table('rbac_groups')
				->addColumn('slug', 'string', ['limit' => 64, 'null' => false])
				->addColumn('name', 'string', ['limit' => 120, 'null' => false])
				->addColumn('description', 'text', ['null' => true])
				->addColumn('is_system', 'boolean', ['null' => false, 'default' => false])
				->addColumn('active', 'boolean', ['null' => false, 'default' => true])
				->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
				->addTimestamps()
				->addIndex(['slug'], ['unique' => true, 'name' => 'ux_rbac_groups_slug'])
				->create();
		}

		if (!$this->hasTable('rbac_user_groups')) {
			$this->table('rbac_user_groups', ['id' => false, 'primary_key' => ['user_id', 'group_id']])
				->addColumn('user_id', 'integer', ['null' => false])
				->addColumn('group_id', 'integer', ['null' => false])
				->addIndex(['user_id'], ['name' => 'ix_rug_user'])
				->addIndex(['group_id'], ['name' => 'ix_rug_group'])
				->create();
		}

		if (!$this->hasTable('rbac_group_roles')) {
			$this->table('rbac_group_roles', ['id' => false, 'primary_key' => ['group_id', 'role_id']])
				->addColumn('group_id', 'integer', ['null' => false])
				->addColumn('role_id', 'integer', ['null' => false])
				->addIndex(['group_id'], ['name' => 'ix_rgr_group'])
				->addIndex(['role_id'], ['name' => 'ix_rgr_role'])
				->create();
		}

		if (!$this->hasTable('rbac_permission_policies')) {
			$this->table('rbac_permission_policies')
				->addColumn('name', 'string', ['limit' => 160, 'null' => false])
				->addColumn('rbac_permission_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('priority', 'integer', ['null' => false, 'default' => 0])
				->addColumn('conditions_json', 'text', ['null' => true, 'default' => null])
				->addColumn('active', 'boolean', ['null' => false, 'default' => true])
				->addColumn('description', 'text', ['null' => true])
				->addTimestamps()
				->addIndex(['rbac_permission_id'], ['name' => 'ix_rpp_permission'])
				->addIndex(['active', 'priority'], ['name' => 'ix_rpp_active_priority'])
				->create();
		}

		if (!$this->hasTable('rbac_field_permissions')) {
			$this->table('rbac_field_permissions')
				->addColumn('resource_key', 'string', ['limit' => 190, 'null' => false])
				->addColumn('rbac_permission_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('access_mode', 'string', ['limit' => 16, 'null' => false, 'default' => 'inherit'])
				->addColumn('active', 'boolean', ['null' => false, 'default' => true])
				->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
				->addTimestamps()
				->addIndex(['resource_key'], ['name' => 'ix_rfp_resource'])
				->addIndex(['rbac_permission_id'], ['name' => 'ix_rfp_permission'])
				->create();
		}

		if (!$this->hasTable('rbac_audit_authorizations')) {
			// Explicit created/modified before index on created — addTimestamps()+ix in one chain
			// breaks PostgreSQL DDL ordering (index references column not yet emitted).
			$this->table('rbac_audit_authorizations')
				->addColumn('user_id', 'integer', ['null' => false])
				->addColumn('granted', 'boolean', ['null' => false, 'default' => false])
				->addColumn('controller', 'string', ['limit' => 80, 'null' => false, 'default' => ''])
				->addColumn('action', 'string', ['limit' => 80, 'null' => false, 'default' => ''])
				->addColumn('permission_code', 'string', ['limit' => 120, 'null' => true, 'default' => null])
				->addColumn('context_json', 'text', ['null' => true, 'default' => null])
				->addColumn('created', 'datetime', ['null' => true, 'default' => null])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['user_id'], ['name' => 'ix_raa_user'])
				->addIndex(['created'], ['name' => 'ix_raa_created'])
				->create();
		}
	}

	public function down() {
		if ($this->hasTable('rbac_audit_authorizations')) {
			$this->table('rbac_audit_authorizations')->drop()->save();
		}
		if ($this->hasTable('rbac_field_permissions')) {
			$this->table('rbac_field_permissions')->drop()->save();
		}
		if ($this->hasTable('rbac_permission_policies')) {
			$this->table('rbac_permission_policies')->drop()->save();
		}
		if ($this->hasTable('rbac_group_roles')) {
			$this->table('rbac_group_roles')->drop()->save();
		}
		if ($this->hasTable('rbac_user_groups')) {
			$this->table('rbac_user_groups')->drop()->save();
		}
		if ($this->hasTable('rbac_groups')) {
			$this->table('rbac_groups')->drop()->save();
		}
		if ($this->hasTable('rbac_roles')) {
			$t = $this->table('rbac_roles');
			if ($t->hasColumn('hierarchy_level')) {
				$t->removeColumn('hierarchy_level')->update();
			}
		}
	}
}
