<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class LicCatalogoProduto extends Entity {

	protected $_accessible = [
		'idempresa' => true,
		'idcategoria' => true,
		'sku' => true,
		'nome' => true,
		'idfornecedor_cliente' => true,
		'ativo' => true,
		'created' => true,
		'modified' => true,
		'lic_categoria' => true,
	];
}
