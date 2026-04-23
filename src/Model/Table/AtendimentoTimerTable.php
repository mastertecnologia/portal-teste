<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * AtendimentoTimer – registros do timer de horas técnicas por ticket.
 * Tabela: atendimento_timer (ou atendimento_timers por convenção CakePHP).
 */
class AtendimentoTimerTable extends Table {

	public function initialize(array $config) {
		$this->setTable('atendimento_timer');
		$this->belongsTo('Tickets', ['foreignKey' => 'idticket']);
		$this->belongsTo('Users', ['foreignKey' => 'idusuario']);
	}
}
