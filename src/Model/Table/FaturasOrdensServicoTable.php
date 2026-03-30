<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Vínculo N:N entre fatura (locação) e ordens de serviço (pré-faturamento).
 */
class FaturasOrdensServicoTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('faturas_ordens_servico');
		$this->setEntityClass('App\Model\Entity\FaturasOrdensServico');
		$this->setDisplayField('id');
		$this->belongsTo('Faturas')->setForeignKey('idfatura')->setJoinType('INNER');
		$this->belongsTo('Ordensservico')->setForeignKey('idordem')->setJoinType('INNER');
	}
}
