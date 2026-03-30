<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Parâmetros globais do portal (URL pública, pastas, e-mail, etc.).
 */
class ConfigTable extends Table
{
	public function initialize(array $config)
	{
		parent::initialize($config);
	}
}
