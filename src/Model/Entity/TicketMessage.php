<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class TicketMessage extends Entity {

	protected $_accessible = [
		'id' => true,
		'idempresa' => true,
		'ticket_id' => true,
		'user_id' => true,
		'message' => true,
		'type' => true,
		'metadata' => true,
		'created' => true,
	];
}
