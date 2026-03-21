<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class SlaPoliciesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('sla_policies');
		$this->setDisplayField('nome');
		$this->belongsTo('Empresas', ['foreignKey' => 'idempresa']);
		$this->hasMany('Tickets', ['foreignKey' => 'sla_policy_id']);
	}

}
