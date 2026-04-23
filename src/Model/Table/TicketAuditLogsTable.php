<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TicketAuditLogsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('ticket_audit_logs');
		$this->setDisplayField('id');
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id', 'joinType' => 'INNER']);
		$this->belongsTo('Users', ['foreignKey' => 'user_id', 'joinType' => 'INNER']);
	}

}
