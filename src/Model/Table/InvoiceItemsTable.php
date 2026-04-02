<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class InvoiceItemsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('invoice_items');
		$this->setDisplayField('description');
		$this->belongsTo('Invoices', ['foreignKey' => 'invoice_id', 'joinType' => 'INNER']);
	}
}
