<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LicAssentosTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('lic_assentos');
		$this->setPrimaryKey('id');
		$this->belongsTo('LicLicencas', ['foreignKey' => 'idlicenca']);
	}

	public function validationDefault(Validator $validator) {
		$validator->integer('idlicenca')->requirePresence('idlicenca', 'create')->notEmpty('idlicenca');

		return $validator;
	}
}
