<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PcpApontamentosTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('pcp_apontamentos');
		$this->setPrimaryKey('id');
		$this->belongsTo('PcpOrdensProducao', ['foreignKey' => 'idordem']);
		$this->belongsTo('PcpCentrosTrabalho', ['foreignKey' => 'idcentro']);
		$this->belongsTo('Users', ['foreignKey' => 'iduser']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->integer('idordem')
			->dateTime('inicio');

		return $validator;
	}
}
