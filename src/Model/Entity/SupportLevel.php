<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class SupportLevel extends Entity {

	protected $_accessible = [
		'*' => true,
		'id' => false,
	];

}
