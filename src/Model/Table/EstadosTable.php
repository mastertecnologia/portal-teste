<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * UF / estado — usado por Cidades e impressão de OS (Ordensservico::imprimir).
 */
class EstadosTable extends Table
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->setTable('estados');
		$this->setDisplayField('nome');
		$this->hasMany('Cidades', [
			'foreignKey' => 'idestado',
		]);
	}
}
