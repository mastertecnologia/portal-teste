<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class ContractSignatory extends Entity {

	protected $_accessible = [
		'id' => false,
		'contract_id' => true,
		'nome' => true,
		'email' => true,
		'cpf' => true,
		'tipo' => true,
		'ordem' => true,
		'obrigatorio' => true,
		'autentique_id' => true,
		'auth_type' => true,
		'action_type' => true,
		'autentique_signer_id' => true,
		'status' => true,
		'link_assinatura' => true,
		'assinado_em' => true,
		'visualizado_em' => true,
		'recusado_em' => true,
		'motivo_recusa' => true,
		'ip_assinatura' => true,
		'created' => true,
		'modified' => true,
		'contract' => true,
	];

	protected $_casts = [
		'obrigatorio' => 'boolean',
		'assinado_em' => 'datetime',
		'visualizado_em' => 'datetime',
		'recusado_em' => 'datetime',
	];
}
