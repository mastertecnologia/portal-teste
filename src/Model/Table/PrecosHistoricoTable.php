<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class PrecosHistoricoTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('precos_historico');
		$this->setPrimaryKey('id');
		$this->belongsTo('Produtos', ['foreignKey' => 'produto_id']);
		$this->belongsTo('PrecosTabelas', ['foreignKey' => 'precos_tabela_id']);
	}
}
