<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Contratos do módulo avançado (tabela contracts).
 * Legado comercial de itens do cliente continua em clicontratos / contratos_horas.
 */
class ContractsTable extends Table {

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

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idcliente')
			->requirePresence('idcliente', 'create')
			->notEmpty('idcliente');

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
			->notEmpty('status');

		$validator
			->date('start_date')
			->requirePresence('start_date', 'create')
			->notEmpty('start_date');

		$validator
			->date('end_date')
			->requirePresence('end_date', 'create')
			->notEmpty('end_date');

		$validator->scalar('nivel_sla')->maxLength('nivel_sla', 30)->allowEmpty('nivel_sla');
		$validator->scalar('autentique_doc_id')->maxLength('autentique_doc_id', 100)->allowEmpty('autentique_doc_id');
		$validator->scalar('autentique_status')->maxLength('autentique_status', 30)->allowEmpty('autentique_status');
		$validator->scalar('pdf_path')->maxLength('pdf_path', 500)->allowEmpty('pdf_path');
		$validator->scalar('signed_pdf_path')->maxLength('signed_pdf_path', 500)->allowEmpty('signed_pdf_path');

		return $validator;
	}
}
