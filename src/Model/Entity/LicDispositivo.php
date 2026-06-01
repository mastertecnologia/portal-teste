<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class LicDispositivo extends Entity {

	protected $_accessible = [
		'idempresa' => true,
		'idcliente' => true,
		'hostname' => true,
		'serial' => true,
		'so' => true,
		'ultimo_visto' => true,
		'created' => true,
		'modified' => true,
		'cliente' => true,
	];
}
