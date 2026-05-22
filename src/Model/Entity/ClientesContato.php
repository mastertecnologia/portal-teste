<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class ClientesContato extends Entity {

	protected $_accessible = [
		'idcliente' => true,
		'idempresa' => true,
		'nome' => true,
		'cargo' => true,
		'email' => true,
		'fone' => true,
		'principal' => true,
		'created' => true,
		'modified' => true,
		'id' => false,
	];
}
