<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class QueuesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('queues');
		$this->setDisplayField('name');
		$this->belongsTo('SupportLevels', ['foreignKey' => 'support_level_id', 'joinType' => 'LEFT']);
		$this->belongsTo('ParentQueues', ['className' => 'Queues', 'foreignKey' => 'fila_pai_id', 'joinType' => 'LEFT']);
		$this->belongsTo('NextQueues', ['className' => 'Queues', 'foreignKey' => 'proxima_fila_id', 'joinType' => 'LEFT']);
		$this->hasMany('QueuesUsers', ['foreignKey' => 'queue_id', 'dependent' => true]);
		$this->hasMany('Tickets', ['foreignKey' => 'queue_id']);
	}

}
