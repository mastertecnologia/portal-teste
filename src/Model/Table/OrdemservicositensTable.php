<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Registro de carrinho/itens agregados por ordem de serviço.
 */
class OrdemservicositensTable extends Table
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->setTable('ordemservicositens');
		$this->belongsTo('Ordensservico')->setForeignKey('idordem')->setDependent(false);
	}
}
