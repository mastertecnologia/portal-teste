<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;

/**
 * Alertas de vencimento financeiro — notifica equipe via sino (portal_internal_notifications).
 *
 * Cron diário:
 *   bin/cake financeiro_alertas verificar
 *   bin/cake financeiro_alertas verificar --dias 3
 *   bin/cake financeiro_alertas verificar --dry-run
 */
class FinanceiroAlertasShell extends Shell {

    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->setDescription('Alertas de vencimento financeiro.');
        $parser->addSubcommand('verificar', [
            'help' => 'Verifica vencimentos próximos e envia notificação.',
        ]);
        $parser->addOption('dias', [
            'help' => 'Dias de antecedência para alertar (default: 3).',
            'default' => '3',
        ]);
        $parser->addOption('dry-run', [
            'boolean' => true,
            'help' => 'Simula sem enviar notificações.',
        ]);
        return $parser;
    }

    public function verificar() {
        $dryRun = (bool)$this->param('dry-run');
        $dias = max(1, (int)$this->param('dias'));

        $lancTable = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
        $notifTable = TableRegistry::getTableLocator()->get('PortalInternalNotifications');
        $usersTable = TableRegistry::getTableLocator()->get('Users');

        $hoje = date('Y-m-d');
        $limite = date('Y-m-d', strtotime("+$dias days"));

        // Lançamentos que vencem nos próximos N dias ou já venceram (aberto)
        $lancamentos = $lancTable->find()
            ->where([
                'status' => 'aberto',
                'data_vencimento <=' => $limite,
            ])
            ->contain(['Clientes' => ['fields' => ['id', 'razaosocial', 'tipo', 'nome']]])
            ->order(['data_vencimento' => 'ASC'])
            ->toArray();

        if (empty($lancamentos)) {
            $this->out('Nenhum vencimento próximo.');
            return;
        }

        // Agrupar por empresa
        $porEmpresa = [];
        foreach ($lancamentos as $l) {
            $porEmpresa[$l->idempresa][] = $l;
        }

        $totalNotif = 0;

        foreach ($porEmpresa as $idempresa => $lancs) {
            // Resumo
            $vencidos = [];
            $aVencer = [];
            foreach ($lancs as $l) {
                if ($l->data_vencimento->format('Y-m-d') < $hoje) {
                    $vencidos[] = $l;
                } else {
                    $aVencer[] = $l;
                }
            }

            $linhas = [];
            if (!empty($vencidos)) {
                $totalVenc = array_sum(array_map(function($l) { return (float)$l->valor; }, $vencidos));
                $linhas[] = count($vencidos) . ' título(s) vencido(s) — R$ ' . number_format($totalVenc, 2, ',', '.');
            }
            if (!empty($aVencer)) {
                $totalAv = array_sum(array_map(function($l) { return (float)$l->valor; }, $aVencer));
                $linhas[] = count($aVencer) . ' título(s) a vencer em ' . $dias . ' dias — R$ ' . number_format($totalAv, 2, ',', '.');
            }

            $mensagem = "Financeiro: " . implode(' | ', $linhas);

            $this->out("  Empresa #$idempresa: " . $mensagem);

            if ($dryRun) {
                $totalNotif++;
                continue;
            }

            // Notificar equipe da empresa
            $equipe = $usersTable->find()
                ->where(['idempresa' => $idempresa, 'role' => 0, 'inativo' => 0])
                ->select(['id'])
                ->toArray();

            foreach ($equipe as $u) {
                $notif = $notifTable->newEntity([
                    'user_id' => $u->id,
                    'idempresa' => $idempresa,
                    'titulo' => 'Alertas de vencimento',
                    'mensagem' => $mensagem,
                    'tipo' => 'alerta',
                    'lido' => false,
                ]);
                $notifTable->save($notif);
            }
            $totalNotif++;
        }

        $this->out($dryRun
            ? "[DRY-RUN] $totalNotif empresa(s) seriam notificada(s)."
            : "$totalNotif empresa(s) notificada(s)."
        );
    }
}
