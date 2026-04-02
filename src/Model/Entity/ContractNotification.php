<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class ContractNotification extends Entity {

	protected $_accessible = [
		'id' => false,
		'contract_id' => true,
		'tipo' => true,
		'destinatario' => true,
		'canal' => true,
		'enviado' => true,
		'enviado_em' => true,
		'erro' => true,
		'created' => true,
		'contract' => true,
	];

	protected $_casts = [
		'enviado' => 'boolean',
		'enviado_em' => 'datetime',
		'created' => 'datetime',
	];
}
