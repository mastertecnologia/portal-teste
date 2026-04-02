<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Faturas do módulo avançado (contrato + referência mensal).
 * Não confundir com faturamento / faturas legadas do ERP.
 */
class InvoicesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('invoices');
		$this->setDisplayField('code');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'LEFT']);
		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente', 'joinType' => 'LEFT']);
		$this->belongsTo('Empresas', ['foreignKey' => 'idempresa', 'joinType' => 'LEFT']);

		$this->hasMany('InvoiceItems', ['foreignKey' => 'invoice_id', 'dependent' => true]);
		$this->hasMany('InvoicePayments', ['foreignKey' => 'invoice_id', 'dependent' => true]);
	}
}
