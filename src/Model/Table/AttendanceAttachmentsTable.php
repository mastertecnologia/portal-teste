<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Anexos do fluxo avançado (is_public). Legado: ticketsanexos.
 */
class AttendanceAttachmentsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('attendance_attachments');
		$this->setDisplayField('file_name');

		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id', 'joinType' => 'INNER']);
		$this->belongsTo('Users', ['foreignKey' => 'uploaded_by', 'joinType' => 'INNER']);
	}
}
