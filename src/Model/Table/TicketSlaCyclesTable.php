<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TicketSlaCyclesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('ticket_sla_cycles');
		$this->setEntityClass('App\Model\Entity\TicketSlaCycle');
		try {
			$schema = $this->getSchema();
			if (in_array('metadata', $schema->columns(), true)) {
				$schema->setColumnType('metadata', 'json');
			}
		} catch (\Throwable $e) {
		}
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id']);
	}

}
