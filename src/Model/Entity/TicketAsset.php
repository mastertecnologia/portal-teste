<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class TicketAsset extends Entity {

	protected $_accessible = [
		'id' => false,
		'idempresa' => true,
		'ticket_id' => true,
		'asset_id' => true,
		'papel' => true,
		'user_id' => true,
		'observacao' => true,
		'created' => true,
		'asset' => true,
		'ticket' => true,
	];
}
