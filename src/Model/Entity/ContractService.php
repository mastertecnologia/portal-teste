<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class ContractService extends Entity {

	protected $_accessible = [
		'id' => false,
		'contract_id' => true,
		'service_name' => true,
		'service_description' => true,
		'is_included' => true,
		'max_hours' => true,
		'tipo_item' => true,
		'unidade' => true,
		'franquia_horas' => true,
		'valor_unitario' => true,
		'valor_total' => true,
		'vigencia_inicio' => true,
		'vigencia_fim' => true,
		'ativo' => true,
		'observacoes' => true,
		'created' => true,
		'modified' => true,
		'contract' => true,
	];

	protected $_casts = [
		'is_included' => 'boolean',
		'ativo' => 'boolean',
		'vigencia_inicio' => 'date',
		'vigencia_fim' => 'date',
	];
}
