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
		'contract_service_id' => true,
		'ticket_id' => true,
		'service_order_id' => true,
		'period_type' => true,
		'reference_month' => true,
		'source_type' => true,
		'source_id' => true,
		'source_hash' => true,
		'consumed_quantity' => true,
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
