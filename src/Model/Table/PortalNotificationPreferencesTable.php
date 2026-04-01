<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class PortalNotificationPreferencesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('portal_notification_preferences');
		$this->addBehavior('Timestamp');
	}
}
