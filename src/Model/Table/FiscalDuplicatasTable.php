<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class FiscalDuplicatasTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_duplicatas');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('FiscalNotas', [
            'foreignKey' => 'fiscal_nota_id',
        ]);
        $this->belongsTo('Clientes', [
            'foreignKey' => 'idcliente',
        ]);
        $this->belongsTo('FinanceiroLancamentos', [
            'foreignKey' => 'financeiro_lancamento_id',
        ]);
    }

    public function validationDefault(Validator $validator) {
        $validator->requirePresence('tipo', 'create')
            ->inList('tipo', ['receber', 'pagar'])
            ->requirePresence('valor', 'create')
            ->decimal('valor')
            ->inList('status', ['aberta', 'paga', 'cancelada']);
        return $validator;
    }

    /**
     * Gera duplicatas a partir de uma nota fiscal autorizada.
     *
     * @param \App\Model\Entity\FiscalNota $nota
     * @return array<\App\Model\Entity\FiscalDuplicata> Duplicatas criadas
     */
    public function gerarDeNota($nota): array {
        $tipoOp = (int)($nota->tipo_operacao ?? 1);
        $tipo = ($tipoOp === 0) ? 'pagar' : 'receber';
        $pagamentos = $nota->fiscal_notas_pagamentos ?? [];
        $criadas = [];

        if (empty($pagamentos)) {
            // Uma única duplicata com o valor total
            $dup = $this->newEntity([
                'idempresa' => $nota->idempresa,
                'fiscal_nota_id' => $nota->id,
                'idcliente' => $nota->idcliente,
                'numero_duplicata' => ($nota->numero ?? $nota->id) . '/1',
                'tipo' => $tipo,
                'valor' => (float)($nota->valor_total ?? 0),
                'data_vencimento' => $nota->data_emissao ? $nota->data_emissao->modify('+30 days')->format('Y-m-d') : null,
                'status' => 'aberta',
            ]);
            if ($this->save($dup)) {
                $criadas[] = $dup;
            }
        } else {
            $seq = 1;
            foreach ($pagamentos as $pg) {
                $forma = (string)($pg->forma_pagamento ?? '');
                // Pagamentos à vista (01=Dinheiro, 17=PIX, 90=Sem pagamento) → gera como paga
                $avista = in_array($forma, ['01', '17', '90'], true);
                $dup = $this->newEntity([
                    'idempresa' => $nota->idempresa,
                    'fiscal_nota_id' => $nota->id,
                    'idcliente' => $nota->idcliente,
                    'numero_duplicata' => ($nota->numero ?? $nota->id) . '/' . $seq,
                    'tipo' => $tipo,
                    'valor' => (float)($pg->valor ?? 0),
                    'data_vencimento' => $pg->data_vencimento ?? ($nota->data_emissao ? $nota->data_emissao->modify('+30 days')->format('Y-m-d') : null),
                    'status' => $avista ? 'paga' : 'aberta',
                    'data_pagamento' => $avista ? ($nota->data_emissao ? $nota->data_emissao->format('Y-m-d') : date('Y-m-d')) : null,
                    'valor_pago' => $avista ? (float)($pg->valor ?? 0) : null,
                ]);
                if ($this->save($dup)) {
                    $criadas[] = $dup;
                }
                $seq++;
            }
        }

        return $criadas;
    }

    /**
     * Gera lançamentos financeiros a partir das duplicatas de uma nota.
     * Vincula cada duplicata ao lançamento criado via financeiro_lancamento_id.
     *
     * @param array<\App\Model\Entity\FiscalDuplicata> $duplicatas
     * @param \App\Model\Entity\FiscalNota $nota
     * @return int Quantidade de lançamentos criados
     */
    public function sincronizarFinanceiro(array $duplicatas, $nota): int {
        $lancTable = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
        $count = 0;

        foreach ($duplicatas as $dup) {
            if (!empty($dup->financeiro_lancamento_id)) {
                continue;
            }

            $tipoFin = ($dup->tipo === 'receber') ? 'receita' : 'despesa';
            $statusFin = 'aberto';
            $dataReceb = null;
            if ($dup->status === 'paga') {
                $statusFin = ($tipoFin === 'receita') ? 'recebido' : 'pago';
                $dataReceb = $dup->data_pagamento ?? date('Y-m-d');
            }

            $descricao = 'NF ' . ($nota->numero ?? $nota->id);
            if (!empty($dup->numero_duplicata)) {
                $descricao .= ' — Dup ' . $dup->numero_duplicata;
            }

            $lanc = $lancTable->newEntity([
                'idempresa'       => $dup->idempresa,
                'idcliente'       => $dup->idcliente,
                'tipo'            => $tipoFin,
                'descricao'       => $descricao,
                'valor'           => (float)$dup->valor,
                'data_vencimento' => $dup->data_vencimento,
                'data_lancamento' => $nota->data_emissao ? $nota->data_emissao->format('Y-m-d') : date('Y-m-d'),
                'data_recebimento' => $dataReceb,
                'status'          => $statusFin,
                'observacoes'     => 'Gerado automaticamente a partir da nota fiscal #' . $nota->id,
            ]);

            if ($lancTable->save($lanc)) {
                $dup->financeiro_lancamento_id = $lanc->id;
                $this->save($dup);
                $count++;
            }
        }

        return $count;
    }
}
