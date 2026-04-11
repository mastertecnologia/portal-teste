<?php
namespace App\Utility\Fiscal;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * Envia notificações internas (sino) e e-mail para usuários da equipe
 * sobre eventos do módulo fiscal (novas notas recebidas, etc.).
 */
class FiscalNotificacaoService {

    /**
     * Notifica todos os usuários equipe (role=0) de uma empresa sobre novas NF-e recebidas.
     *
     * @param int $idempresa
     * @param array $notasImportadas Lista de ['nota_id' => int, 'chave_acesso' => string, 'emit_nome' => string, 'valor' => float]
     */
    public static function notificarNotasRecebidas(int $idempresa, array $notasImportadas) {
        if (empty($notasImportadas)) {
            return;
        }

        $count = count($notasImportadas);
        $title = $count === 1
            ? 'Nova NF-e recebida'
            : "{$count} novas NF-e recebidas";

        // Montar mensagem com resumo das notas
        $lines = [];
        $maxLines = 5;
        foreach (array_slice($notasImportadas, 0, $maxLines) as $n) {
            $emitente = $n['emit_nome'] ?? 'Emitente desconhecido';
            $valor = isset($n['valor']) ? 'R$ ' . number_format((float)$n['valor'], 2, ',', '.') : '';
            $lines[] = "• {$emitente}" . ($valor ? " — {$valor}" : '');
        }
        if ($count > $maxLines) {
            $lines[] = "… e mais " . ($count - $maxLines) . " nota(s).";
        }
        $message = implode("\n", $lines);

        // URL de ação: ir para lista de notas de entrada
        $actionUrl = Router::url([
            'controller' => 'FiscalNotasEntrada',
            'action' => 'index',
        ], false);

        // Buscar usuários equipe da empresa
        $userIds = self::_getEquipeUserIds($idempresa);
        if (empty($userIds)) {
            return;
        }

        // Gravar notificação no sino para cada usuário
        try {
            $Notif = TableRegistry::get('PortalInternalNotifications');
            foreach ($userIds as $uid) {
                $entity = $Notif->newEntity([
                    'user_id' => $uid,
                    'type' => 'fiscal_dfe_novas_notas',
                    'title' => $title,
                    'message' => $message,
                    'action_url' => $actionUrl,
                    'is_read' => 0,
                ]);
                $Notif->save($entity);
            }
        } catch (\Throwable $e) {
            Log::error('FiscalNotificacaoService::notificarNotasRecebidas — ' . $e->getMessage());
        }
    }

    /**
     * Retorna IDs dos usuários equipe (role=0) da empresa.
     */
    private static function _getEquipeUserIds(int $idempresa): array {
        try {
            $Users = TableRegistry::get('Users');
            $rows = $Users->find()
                ->select(['id'])
                ->where([
                    'idempresa' => $idempresa,
                    'role' => 0,
                    'ativo' => 1,
                ])
                ->toArray();
            return array_map(function ($u) {
                return (int)$u->id;
            }, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
