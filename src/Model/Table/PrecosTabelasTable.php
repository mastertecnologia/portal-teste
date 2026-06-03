<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class PrecosTabelasTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('precos_tabelas');
		$this->setPrimaryKey('id');
		$this->hasMany('PrecosTabelaItens', [
			'foreignKey' => 'precos_tabela_id',
		]);
		$this->belongsTo('Empresas', [
			'foreignKey' => 'idempresa',
		]);
	}
}
