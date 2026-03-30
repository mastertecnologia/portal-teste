<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Itens do carrinho / linhas ligadas a ordem de serviço.
 */
class ItensordemTable extends Table
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->setTable('itensordem');
	}
}
