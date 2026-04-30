<?php
use Migrations\AbstractMigration;

class RbacUsersRolesUniqueIndex extends AbstractMigration {
	public function up() {
		if (!$this->hasTable('rbac_users_roles')) {
			return;
		}
		$conn = $this->getAdapter()->getConnection();
		$pkSql = "SELECT tc.constraint_name
			FROM information_schema.table_constraints tc
			JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
			WHERE tc.table_name = 'rbac_users_roles'
			  AND tc.constraint_type = 'PRIMARY KEY'
			GROUP BY tc.constraint_name
			HAVING SUM(CASE WHEN kcu.column_name IN ('user_id','role_id') THEN 1 ELSE 0 END) = 2";
		$pk = $conn->query($pkSql)->fetchAll();
		if (!empty($pk)) {
			return; // já protegido por PK composta
		}

		$idxSql = "SELECT indexname FROM pg_indexes WHERE tablename='rbac_users_roles' AND indexdef ILIKE '%UNIQUE%' AND indexdef ILIKE '%(user_id, role_id)%'";
		$idx = $conn->query($idxSql)->fetchAll();
		if (!empty($idx)) {
			return;
		}
		$conn->exec('CREATE UNIQUE INDEX rbac_users_roles_user_role_uq ON rbac_users_roles (user_id, role_id)');
	}

	public function down() {
		if (!$this->hasTable('rbac_users_roles')) {
			return;
		}
		$conn = $this->getAdapter()->getConnection();
		$conn->exec('DROP INDEX IF EXISTS rbac_users_roles_user_role_uq');
	}
}

