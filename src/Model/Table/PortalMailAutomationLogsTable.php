<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class PortalMailAutomationLogsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('portal_mail_automation_logs');
	}
}
