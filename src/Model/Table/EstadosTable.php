<?php
namespace App\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Table;

/**
 * UF / estado — usado por Cidades e impressão de OS (Ordensservico::imprimir).
 *
 * Nome físico da tabela: Configure::read('App.estados_table') ou env PGM_DB_ESTADOS_TABLE (default estados).
 */
class EstadosTable extends Table
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$table = Configure::read('App.estados_table');
		$this->setTable(is_string($table) && $table !== '' ? $table : 'estados');
		$this->setDisplayField('nome');
		$this->hasMany('Cidades', [
			'foreignKey' => 'idestado',
		]);
	}
}
