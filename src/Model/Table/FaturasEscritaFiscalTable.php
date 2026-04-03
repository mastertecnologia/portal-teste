<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class FaturasEscritaFiscalTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Timestamp');
		$this->setTable('faturas_escrita_fiscal');
		$this->setDisplayField('id');
		$this->belongsTo('Faturas')->setForeignKey('idfatura')->setDependent(false);
	}

}
