<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class PortalInternalNotification extends Entity {

	protected $_accessible = [
		'*' => true,
		'id' => false,
	];
}
