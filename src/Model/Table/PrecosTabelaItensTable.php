<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class PrecosTabelaItensTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('precos_tabela_itens');
		$this->setPrimaryKey('id');
		$this->belongsTo('PrecosTabelas', [
			'foreignKey' => 'precos_tabela_id',
		]);
		$this->belongsTo('Produtos', [
			'foreignKey' => 'produto_id',
		]);
	}
}
