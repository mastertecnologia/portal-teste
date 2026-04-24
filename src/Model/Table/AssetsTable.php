<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class AssetsTable extends Table {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('assets');
		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente']);
		$this->hasMany('TechnicalReports', ['foreignKey' => 'asset_id']);
	}
}
