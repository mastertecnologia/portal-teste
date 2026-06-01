<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class LicCofreItensTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('lic_cofre_itens');
		$this->setPrimaryKey('id');
		$this->belongsTo('Clientes', [
			'foreignKey' => 'idcliente',
			'joinType' => 'LEFT',
		]);
		$this->belongsTo('LicLicencas', [
			'foreignKey' => 'idlicenca',
			'joinType' => 'LEFT',
		]);
	}
}
