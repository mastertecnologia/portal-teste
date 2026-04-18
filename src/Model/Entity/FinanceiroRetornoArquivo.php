<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FinanceiroRetornoArquivo extends Entity
{
    protected $_accessible = [
        'id' => false,
        'idempresa' => true,
        'financeiro_banco_id' => true,
        'usuario_id' => true,
        'financeiro_remessa_id' => true,
        'nome_arquivo_original' => true,
        'nome_arquivo_salvo' => true,
        'caminho_arquivo' => true,
        'layout_cnab' => true,
        'status_processamento' => true,
        'observacoes' => true,
        'processados' => true,
        'baixados' => true,
        'rejeitados' => true,
        'ignorados' => true,
        'erros' => true,
        'data_processamento' => true,
        'created' => true,
        'modified' => true,
        'empresa' => true,
        'financeiro_banco' => true,
        'user' => true,
        'financeiro_remessa' => true,
        'financeiro_retorno_itens' => true,
    ];
}
