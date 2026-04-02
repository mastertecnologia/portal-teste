<?php
namespace App\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;

/**
 * Linha do tempo detalhada (nota pública / interna / metadata).
 * Tabela física: attendance_timeline
 */
class AttendanceTimelineTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('attendance_timeline');
		$this->setDisplayField('event_label');

		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id', 'joinType' => 'INNER']);
		$this->belongsTo('AttendanceHistories', ['foreignKey' => 'history_id', 'joinType' => 'LEFT']);
		$this->belongsTo('Users', ['foreignKey' => 'actor_user_id', 'joinType' => 'LEFT']);
	}

	protected function _initializeSchema(TableSchema $schema) {
		$schema->setColumnType('metadata', 'json');

		return $schema;
	}
}
