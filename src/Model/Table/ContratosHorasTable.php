<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * ContratosHoras – horas de contrato por cliente.
 * Tabela: contratos_horas (ou contrato_horas em alguns ambientes).
 * Colunas possíveis: horas_contratadas, horas_mensais (plano «N horas mensais» no resumo), saldo (em horas) ou minutos_contratados/minutos_consumidos.
 */
class ContratosHorasTable extends Table {

	public function initialize(array $config) {
		$this->setTable('contratos_horas');
		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente']);
	}
}
