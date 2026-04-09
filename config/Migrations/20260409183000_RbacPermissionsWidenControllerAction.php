<?php
use Migrations\AbstractMigration;

/**
 * permissions_registry usa listas longas de ações (vírgula); VARCHAR(80) truncava no sync / INSERT.
 */
class RbacPermissionsWidenControllerAction extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$table = $this->table('rbac_permissions');
		$table->changeColumn('controller', 'string', ['limit' => 255, 'null' => false, 'default' => '']);
		// TEXT: listas longas de ações (vírgula) em permissions_registry (ex.: tickets.view).
		$table->changeColumn('action', 'text', ['null' => false]);
		$table->update();
	}

	public function down() {
		// Reverter para VARCHAR(80) pode falhar ou truncar dados após sync — não suportado.
	}
}
