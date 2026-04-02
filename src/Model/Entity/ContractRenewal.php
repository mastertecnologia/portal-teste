<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class ContractRenewal extends Entity {

	protected $_accessible = [
		'id' => false,
		'contract_id' => true,
		'novo_contract_id' => true,
		'status' => true,
		'solicitado_por' => true,
		'solicitado_em' => true,
		'nova_vigencia_inicio' => true,
		'nova_vigencia_fim' => true,
		'novo_valor_mensal' => true,
		'observacoes' => true,
		'aprovado_por' => true,
		'aprovado_em' => true,
		'created' => true,
		'modified' => true,
		'contract' => true,
		'novo_contract' => true,
		'solicitante' => true,
		'aprovador' => true,
	];

	protected $_casts = [
		'solicitado_em' => 'datetime',
		'aprovado_em' => 'datetime',
		'nova_vigencia_inicio' => 'date',
		'nova_vigencia_fim' => 'date',
	];
}
