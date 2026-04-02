<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Faturas do módulo avançado (contrato + referência mensal).
 * Não confundir com faturamento / faturas legadas do ERP.
 */
class InvoicesTable extends Table {

	/**
	 * @return string[]
	 */
	public static function allowedStatusValues() {
		return ['pending', 'paid', 'overdue', 'cancelled', 'canceled', 'draft'];
	}

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('invoices');
		$this->setDisplayField('code');
		$this->setEntityClass('App\Model\Entity\Invoice');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'LEFT']);
		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente', 'joinType' => 'LEFT']);
		$this->belongsTo('Empresas', ['foreignKey' => 'idempresa', 'joinType' => 'LEFT']);

		$this->hasMany('InvoiceItems', ['foreignKey' => 'invoice_id', 'dependent' => true]);
		$this->hasMany('InvoicePayments', ['foreignKey' => 'invoice_id', 'dependent' => true]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('contract_id')
			->requirePresence('contract_id', 'create')
			->notEmpty('contract_id');

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
			->scalar('reference_month')
			->maxLength('reference_month', 7)
			->requirePresence('reference_month', 'create')
			->notEmpty('reference_month');

		$validator
			->date('issue_date')
			->requirePresence('issue_date', 'create')
			->notEmpty('issue_date');

		$validator
			->date('due_date')
			->requirePresence('due_date', 'create')
			->notEmpty('due_date');

		$validator
			->scalar('status')
			->maxLength('status', 50)
			->requirePresence('status', 'create')
			->notEmpty('status')
			->inList('status', static::allowedStatusValues(), __('Status de fatura inválido.'));

		$validator->scalar('billing_type')->maxLength('billing_type', 50)->allowEmpty('billing_type');
		$validator->scalar('external_invoice_number')->maxLength('external_invoice_number', 100)->allowEmpty('external_invoice_number');
		$validator->scalar('pdf_path')->maxLength('pdf_path', 500)->allowEmpty('pdf_path');
		$validator->scalar('fiscal_document_path')->maxLength('fiscal_document_path', 500)->allowEmpty('fiscal_document_path');
		$validator->integer('idempresa')->allowEmpty('idempresa');
		$validator->decimal('subtotal')->allowEmpty('subtotal');
		$validator->decimal('discounts')->allowEmpty('discounts');
		$validator->decimal('additions')->allowEmpty('additions');
		$validator->decimal('taxes')->allowEmpty('taxes');
		$validator->decimal('total')->allowEmpty('total');

		return $validator;
	}
}
