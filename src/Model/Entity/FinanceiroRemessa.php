<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FinanceiroRemessa extends Entity
{
    protected $_accessible = [
        'id' => false,
        'idempresa' => true,
        'financeiro_banco_id' => true,
        'usuario_id' => true,
        'cnab_layout' => true,
        'sequencial_arquivo' => true,
        'numero_remessa' => true,
        'data_geracao' => true,
        'status' => true,
        'nome_arquivo' => true,
        'caminho_arquivo' => true,
        'quantidade_titulos' => true,
        'valor_total' => true,
        'observacoes' => true,
        'created' => true,
        'modified' => true,
        'empresa' => true,
        'financeiro_banco' => true,
        'user' => true,
        'financeiro_remessa_titulos' => true,
    ];
}
