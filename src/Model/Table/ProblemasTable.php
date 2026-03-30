<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Tipos de OS (problemas).
 */
class ProblemasTable extends Table
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->setTable('problemas');
	}
}
