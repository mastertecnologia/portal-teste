<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Fatura do módulo avançado (tabela invoices), vinculada a contracts.
 */
class Invoice extends Entity {

	protected $_accessible = [
		'id' => false,
		'contract_id' => true,
		'idcliente' => true,
		'idempresa' => true,
		'code' => true,
		'reference_month' => true,
		'issue_date' => true,
		'due_date' => true,
		'subtotal' => true,
		'discounts' => true,
		'additions' => true,
		'taxes' => true,
		'total' => true,
		'status' => true,
		'billing_type' => true,
		'external_invoice_number' => true,
		'pdf_path' => true,
		'fiscal_document_path' => true,
		'created' => true,
		'modified' => true,
		'contract' => true,
		'cliente' => true,
		'empresa' => true,
		'invoice_items' => true,
		'invoice_payments' => true,
	];

	protected $_casts = [
		'issue_date' => 'date',
		'due_date' => 'date',
	];
}
