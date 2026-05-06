<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Opcao A global: empresa_id pode ser NULL para politicas workflow_sla reutilizadas por todas empresas.
 */
class AlterWorkflowSlaPoliciesEmpresaNullable extends AbstractMigration {

	public function change() {
		if (!$this->hasTable('workflow_sla_policies')) {
			return;
		}
		$this->table('workflow_sla_policies')
			->changeColumn('empresa_id', 'integer', [
				'default' => null,
				'null' => true,
			])
			->update();
	}
}
