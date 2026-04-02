<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class ContractAutentiqueLog extends Entity {

	protected $_accessible = [
		'id' => false,
		'contract_id' => true,
		'evento' => true,
		'payload' => true,
		'resposta_api' => true,
		'user_id' => true,
		'created' => true,
		'contract' => true,
		'user' => true,
	];

	protected $_casts = [
		'payload' => 'array',
		'resposta_api' => 'array',
		'created' => 'datetime',
	];
}
