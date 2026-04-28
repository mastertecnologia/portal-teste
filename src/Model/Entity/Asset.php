<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class Asset extends Entity {

	protected $_accessible = [
		'id' => false,
		'idempresa' => true,
		'idcliente' => true,
		'tipo' => true,
		'categoria' => true,
		'descricao' => true,
		'marca' => true,
		'modelo' => true,
		'numero_serie' => true,
		'patrimonio' => true,
		'identificador' => true,
		'codigo_qr' => true,
		'hostname' => true,
		'ip' => true,
		'mac' => true,
		'sistema_operacional' => true,
		'usuario' => true,
		'senha' => true,
		'porta_interna' => true,
		'porta_externa' => true,
		'localizacao' => true,
		'responsavel_user_id' => true,
		'propriedade' => true,
		'status_operacional' => true,
		'ativo' => true,
		'dt_aquisicao' => true,
		'dt_instalacao' => true,
		'dt_garantia_fim' => true,
		'fornecedor' => true,
		'custo_aquisicao' => true,
		'nfe_referencia' => true,
		'observacoes' => true,
		'created' => true,
		'modified' => true,
		'cliente' => true,
		'responsavel' => true,
	];

	protected $_hidden = ['senha'];
}
