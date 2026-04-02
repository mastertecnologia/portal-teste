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
		'status' => true,
		'link_assinatura' => true,
		'assinado_em' => true,
		'ip_assinatura' => true,
		'created' => true,
		'modified' => true,
		'contract' => true,
	];

	protected $_casts = [
		'obrigatorio' => 'boolean',
		'assinado_em' => 'datetime',
	];
}
