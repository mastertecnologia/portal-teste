<?php
use Migrations\AbstractMigration;

/**
 * Log de transições de status/etapa nos protótipos (Orçamento, OS, Ticket, RBAC).
 */
class PrototypeStatusHistory extends AbstractMigration {

	public function change() {
		if ($this->hasTable('prototype_status_history')) {
			return;
		}
		$table = $this->table('prototype_status_history');
		$table
			->addColumn('idempresa', 'integer', ['null' => true, 'default' => null])
			->addColumn('source_type', 'string', ['limit' => 40, 'null' => false, 'comment' => 'orcamento|os|ticket|rbac|fatura'])
			->addColumn('source_id', 'integer', ['null' => false])
			->addColumn('status_from', 'string', ['limit' => 40, 'null' => true, 'default' => null])
			->addColumn('status_to', 'string', ['limit' => 40, 'null' => false])
			->addColumn('actor_user_id', 'integer', ['null' => true, 'default' => null])
			->addColumn('actor_name', 'string', ['limit' => 120, 'null' => true, 'default' => null])
			->addColumn('actor_ip', 'string', ['limit' => 45, 'null' => true, 'default' => null])
			->addColumn('note', 'text', ['null' => true, 'default' => null])
			->addColumn('created', 'datetime', ['null' => false])
			->addIndex(['source_type', 'source_id'])
			->addIndex(['created'])
			->create();
	}
}
