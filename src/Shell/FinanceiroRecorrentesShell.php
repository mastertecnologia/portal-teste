<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;

/**
 * Gera lançamentos financeiros a partir de templates recorrentes.
 *
 * Uso (cron diário):
 *   bin/cake financeiro_recorrentes gerar
 *   bin/cake financeiro_recorrentes gerar --dry-run
 */
class FinanceiroRecorrentesShell extends Shell {

    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->setDescription('Gera lançamentos financeiros recorrentes.');
        $parser->addSubcommand('gerar', [
            'help' => 'Gera lançamentos pendentes para o mês/período corrente.',
        ]);
        $parser->addOption('dry-run', [
            'boolean' => true,
            'help' => 'Simula sem criar lançamentos.',
        ]);
        return $parser;
    }

    public function gerar() {
        $dryRun = (bool)$this->param('dry-run');
        $recTable = TableRegistry::getTableLocator()->get('FinanceiroRecorrentes');
        $lancTable = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');

        $hoje = new \DateTime();
        $mesAtual = $hoje->format('Y-m');

        $templates = $recTable->find()
            ->where([
                'ativo' => true,
                'data_inicio <=' => $hoje->format('Y-m-d'),
                'OR' => [
                    'data_fim IS' => null,
                    'data_fim >=' => $hoje->format('Y-m-d'),
                ],
            ])
            ->toArray();

        $this->out('Templates ativos: ' . count($templates));
        $criados = 0;

        foreach ($templates as $tpl) {
            // Determinar se já foi gerado neste período
            $ultimaGeracao = $tpl->ultima_geracao ? $tpl->ultima_geracao->format('Y-m-d') : null;
            $deveMes = $this->_deveGerar($tpl, $hoje, $ultimaGeracao);

            if (!$deveMes) {
                continue;
            }

            $diaVenc = min((int)$tpl->dia_vencimento, 28);
            $dataVenc = $hoje->format('Y-m') . '-' . str_pad($diaVenc, 2, '0', STR_PAD_LEFT);

            $this->out(sprintf(
                '  [%s] %s — R$ %s — venc %s',
                $tpl->tipo, $tpl->descricao,
                number_format((float)$tpl->valor, 2, ',', '.'),
                $dataVenc
            ));

            if ($dryRun) {
                $criados++;
                continue;
            }

            $statusFin = 'aberto';
            $tipoFin = $tpl->tipo;

            $lanc = $lancTable->newEntity([
                'idempresa'       => $tpl->idempresa,
                'idcliente'       => $tpl->idcliente,
                'tipo'            => $tipoFin,
                'descricao'       => $tpl->descricao . ' (' . $hoje->format('m/Y') . ')',
                'valor'           => (float)$tpl->valor,
                'data_vencimento' => $dataVenc,
                'data_lancamento' => $hoje->format('Y-m-d'),
                'status'          => $statusFin,
                'plano_conta_id'  => $tpl->plano_conta_id,
                'centro_custo_id' => $tpl->centro_custo_id,
                'observacoes'     => 'Gerado automaticamente — recorrente #' . $tpl->id,
            ]);

            if ($lancTable->save($lanc)) {
                $tpl->ultima_geracao = $hoje->format('Y-m-d');
                $recTable->save($tpl);
                $criados++;
            } else {
                $this->err('Erro ao salvar lançamento para template #' . $tpl->id);
            }
        }

        $this->out($dryRun ? "[DRY-RUN] $criados lançamento(s) seriam criados." : "$criados lançamento(s) criado(s).");
    }

    /**
     * Determina se o template deve gerar lançamento hoje.
     */
    protected function _deveGerar($tpl, \DateTime $hoje, $ultimaGeracao): bool {
        if (!$ultimaGeracao) {
            return true;
        }

        $ultima = new \DateTime($ultimaGeracao);
        $freq = (string)$tpl->frequencia;

        switch ($freq) {
            case 'semanal':
                $diff = $hoje->diff($ultima)->days;
                return $diff >= 7;
            case 'quinzenal':
                $diff = $hoje->diff($ultima)->days;
                return $diff >= 15;
            case 'mensal':
                return $ultima->format('Y-m') < $hoje->format('Y-m');
            case 'trimestral':
                $mesesDiff = (($hoje->format('Y') - $ultima->format('Y')) * 12) + ($hoje->format('m') - $ultima->format('m'));
                return $mesesDiff >= 3;
            case 'anual':
                return $ultima->format('Y') < $hoje->format('Y');
            default:
                return $ultima->format('Y-m') < $hoje->format('Y-m');
        }
    }
}
