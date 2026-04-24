<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TechnicalReportsTable extends Table {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('technical_reports');
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id']);
		$this->belongsTo('Assets', ['foreignKey' => 'asset_id', 'joinType' => 'LEFT']);
		$this->hasMany('TicketChecklists', ['foreignKey' => 'technical_report_id', 'dependent' => true]);
	}
}
