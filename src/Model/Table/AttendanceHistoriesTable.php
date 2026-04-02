<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Snapshot consolidado de atendimento por ticket (módulo avançado).
 * Complementa ticket_histories (eventos discretos) e tickets (SLA em colunas).
 */
class AttendanceHistoriesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('attendance_histories');
		$this->setDisplayField('subject');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id', 'joinType' => 'LEFT']);
		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente', 'joinType' => 'LEFT']);
		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'LEFT']);
		$this->belongsTo('Empresas', ['foreignKey' => 'idempresa', 'joinType' => 'LEFT']);
		$this->belongsTo('Users', ['foreignKey' => 'technician_id', 'joinType' => 'LEFT']);

		$this->hasMany('AttendanceTimeline', ['foreignKey' => 'history_id', 'dependent' => false]);
	}
}
