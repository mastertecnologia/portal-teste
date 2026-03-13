<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ContratosHora Entity – registro de horas de contrato por cliente.
 */
class ContratosHora extends Entity {

	protected $_accessible = [
		'idcliente' => true,
		'idempresa' => true,
		'minutos_contratados' => true,
		'minutos_consumidos' => true,
		'segundos_consumidos' => true,
		'horas_contratadas' => true,
		'horas_consumidas' => true,
		'saldo' => true,
		'saldo_minutos' => true,
	];
}
