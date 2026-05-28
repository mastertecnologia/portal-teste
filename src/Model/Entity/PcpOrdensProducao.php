<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class PcpOrdensProducao extends Entity {

	protected $_accessible = [
		'idempresa' => true,
		'numero' => true,
		'idproduto' => true,
		'descricao' => true,
		'quantidade' => true,
		'quantidade_produzida' => true,
		'status' => true,
		'data_inicio_prev' => true,
		'data_fim_prev' => true,
		'created' => true,
		'modified' => true,
		'produto' => true,
		'pcp_apontamentos' => true,
	];
}
