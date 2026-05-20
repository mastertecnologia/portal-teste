<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class WebPushSubscription extends Entity {

	protected $_accessible = ['*' => true, 'id' => false];
}
