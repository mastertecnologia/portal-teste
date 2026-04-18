<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FinanceiroRetornoItem extends Entity
{
    protected $_accessible = [
        'financeiro_retorno_arquivo_id' => true,
        'financeiro_lancamento_id' => true,
        'financeiro_remessa_id' => true,
        'financeiro_remessa_titulo_id' => true,
        'status_item' => true,
        'nosso_numero' => true,
        'numero_documento' => true,
        'codigo_ocorrencia' => true,
        'mensagem_ocorrencia' => true,
        'valor_titulo' => true,
        'valor_pago' => true,
        'data_vencimento' => true,
        'data_ocorrencia' => true,
        'linha_segmento_t' => true,
        'linha_segmento_u' => true,
        'payload_json' => true,
        'created' => true,
        'modified' => true,
        'financeiro_retorno_arquivo' => true,
        'financeiro_lancamento' => true,
        'financeiro_remessa' => true,
        'financeiro_remessa_titulo' => true,
    ];
}
