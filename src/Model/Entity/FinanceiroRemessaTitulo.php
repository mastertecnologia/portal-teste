<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FinanceiroRemessaTitulo extends Entity
{
    protected $_accessible = [
        "financeiro_remessa_id" => true,
        "financeiro_lancamento_id" => true,
        "nosso_numero_remessa" => true,
        "numero_documento" => true,
        "valor_titulo" => true,
        "data_vencimento" => true,
        "status_item" => true,
        "codigo_ocorrencia" => true,
        "mensagem_ocorrencia" => true,
        "created" => true,
        "modified" => true,
        "financeiro_remessa" => true,
        "financeiro_lancamento" => true,
    ];
}
