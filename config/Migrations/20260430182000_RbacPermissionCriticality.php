<?php
use Migrations\AbstractMigration;

class RbacPermissionCriticality extends AbstractMigration {
	public function up() {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$table = $this->table('rbac_permissions');
		if (!$table->hasColumn('criticality')) {
			$table
				->addColumn('criticality', 'string', ['limit' => 20, 'null' => false, 'default' => 'low', 'after' => 'abac_scope'])
				->update();
		}
		$conn = $this->getAdapter()->getConnection();
		$conn->exec("UPDATE rbac_permissions SET criticality='critical' WHERE code LIKE 'permissoes.%' OR code IN ('senhas.view','bancosenhas.view')");
		$conn->exec("UPDATE rbac_permissions SET criticality='high' WHERE criticality='low' AND (code LIKE 'users.%' OR code LIKE 'tickets.%')");
		$conn->exec("UPDATE rbac_permissions SET criticality='medium' WHERE criticality='low' AND (code LIKE 'financeiro.%' OR code LIKE 'faturamento.%')");
	}

	public function down() {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$table = $this->table('rbac_permissions');
		if ($table->hasColumn('criticality')) {
			$table->removeColumn('criticality')->update();
		}
	}
}

