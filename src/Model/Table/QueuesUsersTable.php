<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class QueuesUsersTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('queues_users');
		$this->belongsTo('Queues', ['foreignKey' => 'queue_id']);
		$this->belongsTo('Users', ['foreignKey' => 'user_id']);
		$this->belongsTo('SupportLevels', ['foreignKey' => 'support_level_id', 'joinType' => 'LEFT']);
	}

}
