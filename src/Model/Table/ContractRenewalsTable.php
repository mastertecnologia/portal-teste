<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class ContractRenewalsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_renewals');
		$this->setDisplayField('id');
		$this->setEntityClass('App\Model\Entity\ContractRenewal');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
		$this->belongsTo('NovoContracts', [
			'className' => 'Contracts',
			'foreignKey' => 'novo_contract_id',
			'joinType' => 'LEFT',
			'propertyName' => 'novo_contract',
		]);
		$this->belongsTo('Solicitante', [
			'className' => 'Users',
			'foreignKey' => 'solicitado_por',
			'joinType' => 'LEFT',
		]);
		$this->belongsTo('Aprovador', [
			'className' => 'Users',
			'foreignKey' => 'aprovado_por',
			'joinType' => 'LEFT',
		]);
	}
}
