<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class WorkflowState extends Entity {

	protected $_accessible = [
		'nome' => true,
		'codigo' => true,
		'is_inicial' => true,
		'is_final' => true,
		'created_at' => true,
	];

}
