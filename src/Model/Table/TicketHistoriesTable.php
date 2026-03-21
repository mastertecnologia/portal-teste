<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TicketHistoriesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('ticket_histories');
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id']);
		$this->belongsTo('Users', ['foreignKey' => 'usuario_id', 'joinType' => 'LEFT']);
	}

}
