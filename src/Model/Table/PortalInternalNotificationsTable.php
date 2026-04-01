<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class PortalInternalNotificationsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('portal_internal_notifications');
		$this->setDisplayField('title');
		$this->addBehavior('Timestamp');
	}
}
