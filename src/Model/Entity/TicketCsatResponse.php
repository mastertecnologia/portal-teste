<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class TicketCsatResponse extends Entity {

	protected $_accessible = [
		'*' => true,
		'id' => false,
	];
}
