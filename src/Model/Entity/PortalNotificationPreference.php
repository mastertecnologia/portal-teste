<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class PortalNotificationPreference extends Entity {

	protected $_accessible = [
		'*' => true,
		'id' => false,
	];
}
