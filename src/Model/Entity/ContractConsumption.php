<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Consumo de franquia por contrato (referência mensal).
 */
class ContractConsumption extends Entity {

	protected $_accessible = [
		'id' => false,
		'contract_id' => true,
		'ticket_id' => true,
		'service_order_id' => true,
		'reference_month' => true,
		'consumed_hours' => true,
		'consumed_amount' => true,
		'notes' => true,
		'created' => true,
		'modified' => true,
		'contract' => true,
		'ticket' => true,
	];

	protected $_casts = [
		'created' => 'datetime',
		'modified' => 'datetime',
	];
}
