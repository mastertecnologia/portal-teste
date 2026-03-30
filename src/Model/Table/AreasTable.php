<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Status de OS (áreas) — catálogo por empresa.
 */
class AreasTable extends Table
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->setTable('areas');
	}
}
