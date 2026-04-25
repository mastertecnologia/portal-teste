<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TicketMessagesTable extends Table {

	public function initialize(array $config) {
		$this->setTable('ticket_messages');
		$this->setPrimaryKey('id');
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id']);
		$this->belongsTo('Users', ['foreignKey' => 'user_id']);
		$schema = $this->getSchema();
		if (in_array('metadata', $schema->columns(), true)) {
			$schema->setColumnType('metadata', 'json');
		}
	}
}
