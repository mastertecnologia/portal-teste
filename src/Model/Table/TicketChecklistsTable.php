<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TicketChecklistsTable extends Table {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('ticket_checklists');
		$this->belongsTo('TechnicalReports', ['foreignKey' => 'technical_report_id']);
	}
}
