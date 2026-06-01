<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class LicAuditoriaEventosTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('lic_auditoria_eventos');
		$this->setPrimaryKey('id');
	}
}
