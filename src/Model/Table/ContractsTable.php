<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Contratos do módulo avançado (tabela contracts).
 * Legado comercial de itens do cliente continua em clicontratos / contratos_horas.
 */
class ContractsTable extends Table {

	/**
	 * Status permitidos (PT + active; alinhado a MODULO_CONTRATOS_COMPLETO e uso no código).
	 *
	 * @return string[]
	 */
	public static function allowedStatusValues() {
		return [
			'rascunho',
			'revisao',
			'aguardando_assinatura',
			'awaiting_signature',
			'ativo',
			'active',
			'a_vencer',
			'em_renovacao',
			'suspenso',
			'encerrado',
			'cancelado',
			'recusado',
			'assinatura_expirada',
		];
	}

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contracts');
		$this->setDisplayField('name');
		$this->setEntityClass('App\Model\Entity\Contract');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente', 'joinType' => 'LEFT']);
		$this->belongsTo('Empresas', ['foreignKey' => 'idempresa', 'joinType' => 'LEFT']);
		$this->belongsTo('ContractTemplates', ['foreignKey' => 'template_id', 'joinType' => 'LEFT']);
		$this->belongsTo('ParentContracts', [
			'className' => 'Contracts',
			'foreignKey' => 'contrato_pai_id',
			'joinType' => 'LEFT',
		]);

		$this->hasMany('ContractServices', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('ContractDocuments', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('ContractConsumptions', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('Invoices', ['foreignKey' => 'contract_id', 'dependent' => false]);
		$this->hasMany('AttendanceHistories', ['foreignKey' => 'contract_id', 'dependent' => false]);
		$this->hasMany('ChildContracts', [
			'className' => 'Contracts',
			'foreignKey' => 'contrato_pai_id',
			'dependent' => false,
		]);
		$this->hasMany('ContractSignatories', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('ContractAutentiqueLogs', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('ContractRenewals', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('ContractNotifications', ['foreignKey' => 'contract_id', 'dependent' => true]);
	}

	/**
	 * Contratos em vigor operacional (ativo EN/PT).
	 *
	 * @param \Cake\ORM\Query $query
	 * @param array $options
	 * @return \Cake\ORM\Query
	 */
	public function findActive($query, array $options = []) {
		return $query->where(['Contracts.status IN' => ['active', 'ativo']]);
	}

	/**
	 * Contratos com ciclo de vida ainda relevante para alertas e UI.
	 *
	 * @param \Cake\ORM\Query $query
	 * @param array $options
	 * @return \Cake\ORM\Query
	 */
	public function findOpenLifecycle($query, array $options = []) {
		return $query->where([
			'Contracts.status IN' => [
				'active', 'ativo', 'a_vencer', 'aguardando_assinatura', 'awaiting_signature', 'rascunho', 'revisao', 'em_renovacao',
			],
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idcliente')
			->requirePresence('idcliente', 'create')
			->notEmpty('idcliente', __('Selecione um cliente.'));

		$validator
			->scalar('code')
			->maxLength('code', 50)
			->requirePresence('code', 'create')
			->notEmpty('code');

		$validator
			->scalar('name')
			->maxLength('name', 255)
			->requirePresence('name', 'create')
			->notEmpty('name');

		$validator
			->scalar('type')
			->maxLength('type', 100)
			->requirePresence('type', 'create')
			->notEmpty('type');

		$validator
			->scalar('status')
			->maxLength('status', 50)
			->requirePresence('status', 'create')
			->notEmpty('status')
			->inList('status', static::allowedStatusValues(), __('Status de contrato inválido.'));

		$validator
			->date('start_date')
			->requirePresence('start_date', 'create')
			->notEmpty('start_date');

		$validator
			->date('end_date')
			->requirePresence('end_date', 'create')
			->notEmpty('end_date');

		$validator->scalar('nivel_sla')->maxLength('nivel_sla', 30)->allowEmpty('nivel_sla');
		$validator->scalar('autentique_doc_id')->maxLength('autentique_doc_id', 255)->allowEmpty('autentique_doc_id');
		$validator->scalar('autentique_status')->maxLength('autentique_status', 30)->allowEmpty('autentique_status');
		$validator->scalar('pdf_path')->maxLength('pdf_path', 500)->allowEmpty('pdf_path');
		$validator->scalar('signed_pdf_path')->maxLength('signed_pdf_path', 500)->allowEmpty('signed_pdf_path');
		$validator->scalar('signature_provider')->maxLength('signature_provider', 50)->allowEmpty('signature_provider');
		$validator->scalar('signed_file_url')->allowEmpty('signed_file_url');
		$validator->dateTime('sent_for_signature_at')->allowEmpty('sent_for_signature_at');
		$validator->dateTime('fully_signed_at')->allowEmpty('fully_signed_at');

		return $validator;
	}
}
