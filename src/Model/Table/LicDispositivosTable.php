<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class LicDispositivosTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('lic_dispositivos');
		$this->setPrimaryKey('id');
	}
}
