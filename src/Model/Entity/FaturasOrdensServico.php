<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FaturasOrdensServico extends Entity {

	protected $_accessible = [
		'idfatura' => true,
		'idordem' => true,
		'idempresa' => true,
		'created' => true,
	];
}
