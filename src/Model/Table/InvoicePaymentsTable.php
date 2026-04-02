<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class InvoicePaymentsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('invoice_payments');
		$this->belongsTo('Invoices', ['foreignKey' => 'invoice_id', 'joinType' => 'INNER']);
	}
}
