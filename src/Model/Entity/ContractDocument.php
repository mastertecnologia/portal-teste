<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Documento anexo ao contrato (módulo avançado).
 */
class ContractDocument extends Entity {

	protected $_accessible = [
		'id' => false,
		'contract_id' => true,
		'title' => true,
		'file_path' => true,
		'is_public' => true,
		'created' => true,
		'contract' => true,
	];

	protected $_casts = [
		'is_public' => 'boolean',
		'created' => 'datetime',
	];
}
