<?php
namespace App\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;

/**
 * Auditoria genérica (entidade + ação + diff JSON).
 */
class AuditLogsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('audit_logs');
		$this->belongsTo('Users', ['foreignKey' => 'user_id', 'joinType' => 'LEFT']);
	}

	protected function _initializeSchema(TableSchema $schema) {
		$schema->setColumnType('old_data', 'json');
		$schema->setColumnType('new_data', 'json');

		return $schema;
	}
}
