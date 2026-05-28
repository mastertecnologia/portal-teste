<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PcpBomItensTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('pcp_bom_itens');
		$this->setPrimaryKey('id');
		$this->belongsTo('Empresas')->setForeignKey('idempresa');
		$this->belongsTo('ParentProdutos', [
			'className' => 'Produtos',
			'foreignKey' => 'parent_produto_id',
		]);
		$this->belongsTo('ChildProdutos', [
			'className' => 'Produtos',
			'foreignKey' => 'child_produto_id',
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->integer('parent_produto_id')
			->integer('child_produto_id')
			->decimal('quantidade');

		return $validator;
	}
}
