<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AtendimentoTimer Entity
 * Registro de início/pausa/fim do timer de horas técnicas por ticket.
 */
class AtendimentoTimer extends Entity {

	protected $_accessible = [
		'idticket' => true,
		'iduser' => true,
		'idusuario' => true,
		'idempresa' => true,
		'id_contrato_horas' => true,
		'hora_inicio' => true,
		'horainicio' => true,
		'hora_pausa' => true,
		'horapausa' => true,
		'hora_fim' => true,
		'horafim' => true,
		'duracaominutos' => true,
		'duracao_calculada' => true,
		'duracao_calculada_minutos' => true,
		'tipo_horario' => true,
		'minutos_comercial' => true,
		'minutos_especial' => true,
		'observacao' => true,
	];
}
