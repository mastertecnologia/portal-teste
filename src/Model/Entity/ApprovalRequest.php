<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class ApprovalRequest extends Entity {

	protected $_accessible = [
		'*' => true,
		'id' => false,
	];
}
