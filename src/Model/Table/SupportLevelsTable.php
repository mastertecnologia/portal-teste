<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class SupportLevelsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('support_levels');
		$this->setDisplayField('name');
		$this->hasMany('Queues', ['foreignKey' => 'support_level_id']);
	}

}
