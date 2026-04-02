<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ContractSignatoriesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_signatories');
		$this->setDisplayField('nome');
		$this->setEntityClass('App\Model\Entity\ContractSignatory');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('contract_id')
			->requirePresence('contract_id', 'create')
			->notEmpty('contract_id');

		$validator
			->scalar('nome')
			->maxLength('nome', 200)
			->requirePresence('nome', 'create')
			->notEmpty('nome');

		$validator
			->scalar('email')
			->maxLength('email', 200)
			->requirePresence('email', 'create')
			->notEmpty('email')
			->email('email');

		$validator->scalar('cpf')->maxLength('cpf', 20)->allowEmpty('cpf');
		$validator->scalar('tipo')->maxLength('tipo', 30)->allowEmpty('tipo');
		$validator->integer('ordem')->allowEmpty('ordem');
		$validator->boolean('obrigatorio')->allowEmpty('obrigatorio');
		$validator->scalar('auth_type')->maxLength('auth_type', 50)->allowEmpty('auth_type');
		$validator->scalar('action_type')->maxLength('action_type', 30)->allowEmpty('action_type');
		$validator->scalar('autentique_id')->maxLength('autentique_id', 100)->allowEmpty('autentique_id');
		$validator->scalar('autentique_signer_id')->maxLength('autentique_signer_id', 255)->allowEmpty('autentique_signer_id');
		$validator
			->scalar('status')
			->maxLength('status', 30)
			->allowEmpty('status');

		$validator->scalar('link_assinatura')->allowEmpty('link_assinatura');
		$validator->dateTime('assinado_em')->allowEmpty('assinado_em');
		$validator->dateTime('visualizado_em')->allowEmpty('visualizado_em');
		$validator->dateTime('recusado_em')->allowEmpty('recusado_em');
		$validator->scalar('motivo_recusa')->allowEmpty('motivo_recusa');
		$validator->scalar('ip_assinatura')->maxLength('ip_assinatura', 50)->allowEmpty('ip_assinatura');

		return $validator;
	}
}
