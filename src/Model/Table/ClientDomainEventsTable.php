<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class ClientDomainEventsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('client_domain_events');
		$this->setDisplayField('event_type');
	}
}
