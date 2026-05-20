<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class TicketCsatResponsesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('ticket_csat_responses');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\TicketCsatResponse');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id', 'joinType' => 'INNER']);
		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente', 'joinType' => 'LEFT']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')->requirePresence('idempresa', 'create')->notEmpty('idempresa')
			->integer('ticket_id')->requirePresence('ticket_id', 'create')->notEmpty('ticket_id')
			->integer('csat_score')->range('csat_score', [1, 5])->requirePresence('csat_score', 'create')
			->integer('nps_score')->range('nps_score', [0, 10])->allowEmpty('nps_score')
			->scalar('token')->maxLength('token', 60)->requirePresence('token', 'create')->notEmpty('token')
			->dateTime('responded_at')->requirePresence('responded_at', 'create');

		return $validator;
	}
}
