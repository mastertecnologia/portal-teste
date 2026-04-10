<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalNota extends Entity {

    protected $_accessible = [
        'id' => false,
        'idempresa' => true,
        'idcliente' => true,
        'user_id' => true,
        'modelo' => true,
        'serie' => true,
        'numero' => true,
        'chave_acesso' => true,
        'natureza_operacao' => true,
        'natureza_operacao_id' => true,
        'tipo_operacao' => true,
        'finalidade' => true,
        'presenca' => true,
        'data_emissao' => true,
        'data_saida' => true,
        'valor_produtos' => true,
        'valor_frete' => true,
        'valor_seguro' => true,
        'valor_desconto' => true,
        'valor_outras_despesas' => true,
        'valor_total_impostos' => true,
        'valor_total' => true,
        'valor_icms' => true,
        'valor_icms_st' => true,
        'valor_ipi' => true,
        'valor_pis' => true,
        'valor_cofins' => true,
        'valor_iss' => true,
        'informacoes_complementares' => true,
        'informacoes_fisco' => true,
        'frete_modalidade' => true,
        'transportadora_cnpj' => true,
        'transportadora_nome' => true,
        'veiculo_placa' => true,
        'veiculo_uf' => true,
        'volumes_quantidade' => true,
        'volumes_especie' => true,
        'volumes_peso_bruto' => true,
        'volumes_peso_liquido' => true,
        'status' => true,
        'protocolo_autorizacao' => true,
        'data_autorizacao' => true,
        'motivo_rejeicao' => true,
        'codigo_retorno_sefaz' => true,
        'mensagem_retorno_sefaz' => true,
        'faturamento_id' => true,
        'ordem_servico_id' => true,
        'created' => true,
        'modified' => true,
        'empresa' => true,
        'cliente' => true,
        'user' => true,
        'fiscal_notas_itens' => true,
        'fiscal_notas_pagamentos' => true,
        'fiscal_notas_eventos' => true,
        'fiscal_notas_xmls' => true,
    ];

    /**
     * Verifica se a nota pode ser editada.
     */
    protected function _getEditavel() {
        return in_array($this->status, ['rascunho', 'rejeitada']);
    }

    /**
     * Verifica se a nota pode ser cancelada.
     */
    protected function _getCancelavel() {
        return $this->status === 'autorizada';
    }
}
