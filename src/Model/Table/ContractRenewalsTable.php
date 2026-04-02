<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

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

	public function validationDefault(Validator $validator) {
		$validator
			->integer('contract_id')
			->requirePresence('contract_id', 'create')
			->notEmpty('contract_id');

		$validator
			->scalar('status')
			->maxLength('status', 30)
			->requirePresence('status', 'create')
			->notEmpty('status')
			->inList('status', ['pendente', 'aprovada', 'recusada', 'expirada'], __('Status de renovação inválido.'));

		$validator->integer('novo_contract_id')->allowEmpty('novo_contract_id');
		$validator->integer('solicitado_por')->allowEmpty('solicitado_por');
		$validator->dateTime('solicitado_em')->allowEmpty('solicitado_em');
		$validator->date('nova_vigencia_inicio')->allowEmpty('nova_vigencia_inicio');
		$validator->date('nova_vigencia_fim')->allowEmpty('nova_vigencia_fim');
		$validator->decimal('novo_valor_mensal')->allowEmpty('novo_valor_mensal');
		$validator->scalar('observacoes')->allowEmpty('observacoes');
		$validator->integer('aprovado_por')->allowEmpty('aprovado_por');
		$validator->dateTime('aprovado_em')->allowEmpty('aprovado_em');

		return $validator;
	}
}
