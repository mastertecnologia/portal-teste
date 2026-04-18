<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FinanceiroBanco extends Entity
{
    protected $_accessible = [
        "id" => false,
        "idempresa" => true,
        "codigo_banco" => true,
        "numero_banco" => true,
        "cnab" => true,
        "nome" => true,
        "ativo" => true,
        "numero_agencia" => true,
        "digito_agencia" => true,
        "numero_conta" => true,
        "digito_conta" => true,
        "codigo_banco_interno" => true,
        "verifica_receber" => true,
        "utiliza_endosso" => true,
        "convenio" => true,
        "carteira" => true,
        "cnab_tipo" => true,
        "proxima_remessa" => true,
        "logotipo" => true,
        "observacoes" => true,
        "created" => true,
        "modified" => true,
    ];
}
