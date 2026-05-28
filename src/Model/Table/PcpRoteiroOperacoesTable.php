<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PcpRoteiroOperacoesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('pcp_roteiro_operacoes');
		$this->setPrimaryKey('id');
		$this->belongsTo('Empresas')->setForeignKey('idempresa');
		$this->belongsTo('Produtos')->setForeignKey('idproduto');
		$this->belongsTo('PcpCentrosTrabalho', [
			'foreignKey' => 'idcentro',
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->integer('idproduto')
			->requirePresence('operacao', 'create')
			->notEmptyString('operacao');

		return $validator;
	}
}
