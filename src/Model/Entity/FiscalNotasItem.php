<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalNotasItem extends Entity {

    protected $_accessible = [
        'id' => false,
        'fiscal_nota_id' => true,
        'numero_item' => true,
        'produto_id' => true,
        'codigo_produto' => true,
        'descricao' => true,
        'ncm' => true,
        'cest' => true,
        'cfop' => true,
        'unidade' => true,
        'quantidade' => true,
        'valor_unitario' => true,
        'valor_total' => true,
        'valor_desconto' => true,
        'valor_frete' => true,
        'valor_seguro' => true,
        'valor_outras_despesas' => true,
        'icms_origem' => true,
        'icms_cst' => true,
        'pis_cst' => true,
        'cofins_cst' => true,
        'ipi_cst' => true,
        'informacoes_adicionais' => true,
        'created' => true,
        'modified' => true,
        'fiscal_nota' => true,
        'fiscal_notas_impostos' => true,
        'fiscal_notas_itens_series' => true,
    ];
}
