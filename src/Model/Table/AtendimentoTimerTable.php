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
		$this->belongsTo('Users', ['foreignKey' => $this->usuarioColumn()]);
	}

	/**
	 * Coluna de vínculo ao usuário no banco: scripts oficiais usam idusuario; instalações antigas podem ter iduser.
	 */
	public function usuarioColumn(): string {
		try {
			$cols = $this->getSchema()->columns();
			if (in_array('idusuario', $cols, true)) {
				return 'idusuario';
			}
			if (in_array('iduser', $cols, true)) {
				return 'iduser';
			}
		} catch (\Throwable $e) {
		}

		return 'idusuario';
	}
}
