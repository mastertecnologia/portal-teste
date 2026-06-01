<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class LicSolicitacoesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('lic_solicitacoes');
		$this->setPrimaryKey('id');
	}
}
