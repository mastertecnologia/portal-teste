<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Políticas SLA podem ser inativadas sem apagar (ex.: gestão por contrato).
 */
class WorkflowSlaPoliciesAtivo extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('workflow_sla_policies')) {
			return;
		}
		$t = $this->table('workflow_sla_policies');
		if (!$t->hasColumn('ativo')) {
			$t->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
				->addIndex(['ativo'], ['name' => 'ix_wf_sla_policies_ativo'])
				->update();
		}
	}

	public function down() {
		if (!$this->hasTable('workflow_sla_policies')) {
			return;
		}
		$t = $this->table('workflow_sla_policies');
		if ($t->hasColumn('ativo')) {
			try {
				$t->removeIndex(['ativo'])->update();
			} catch (\Throwable $e) {
			}
			$t->removeColumn('ativo')->update();
		}
	}
}
