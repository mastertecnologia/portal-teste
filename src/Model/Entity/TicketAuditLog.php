<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class TicketAuditLog extends Entity {

	protected $_accessible = [
		'ticket_id' => true,
		'user_id' => true,
		'old_time' => true,
		'new_time' => true,
		'reason' => true,
		'created' => true,
		'ticket' => true,
		'user' => true,
	];

}
