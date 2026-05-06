<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class WorkflowStatesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('workflow_states');
		$this->setDisplayField('nome');
	}

}
