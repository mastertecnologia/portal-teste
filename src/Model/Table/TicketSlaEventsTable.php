<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TicketSlaEventsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('ticket_sla_events');
		$this->setEntityClass('App\Model\Entity\TicketSlaEvent');
		try {
			$schema = $this->getSchema();
			if (in_array('payload', $schema->columns(), true)) {
				$schema->setColumnType('payload', 'json');
			}
		} catch (\Throwable $e) {
		}
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id']);
	}

}
