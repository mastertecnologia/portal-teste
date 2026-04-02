<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class ContractTemplate extends Entity {

	protected $_accessible = [
		'id' => false,
		'idempresa' => true,
		'nome' => true,
		'tipo_contrato' => true,
		'descricao' => true,
		'conteudo_html' => true,
		'clausulas_padrao' => true,
		'variaveis' => true,
		'ativo' => true,
		'versao' => true,
		'created' => true,
		'modified' => true,
		'empresa' => true,
		'contracts' => true,
	];

	protected $_casts = [
		'clausulas_padrao' => 'array',
		'variaveis' => 'array',
		'ativo' => 'boolean',
	];
}
