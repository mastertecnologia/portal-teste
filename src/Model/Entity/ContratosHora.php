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
		'horas_utilizadas' => true,
		'saldo' => true,
		'saldo_horas' => true,
		'saldo_minutos' => true,
		'data_inicio' => true,
		'data_fim' => true,
		'ativo' => true,
		'valor_hora_comercial' => true,
		'valor_hora_adicional_comercial' => true,
		'valor_hora_especial' => true,
		'contatos_email_relatorio' => true,
	];
}
