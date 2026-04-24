<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class HolidaysTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('holidays');
		$this->setEntityClass('App\Model\Entity\Holiday');
	}
}
