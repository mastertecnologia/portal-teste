<?php
use Migrations\AbstractMigration;

/**
 * RBAC: adiciona a action de download de remessa à permissão `financeiro.bancos`.
 *
 * Objetivo:
 * - Garantir que FinanceiroBancosController::downloadRemessa fique coberto
 *   pelos mesmos papéis que já possuem acesso ao módulo de bancos.
 *
 * Observações:
 * - Mantém compatibilidade com variantes camelCase e underscore.
 * - Não remove permissões em produção no `down()`.
 */
class RbacFinanceiroBancosDownloadRemessaAction extends AbstractMigration
{
    public function up()
    {
        if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
            return;
        }

        if (!$this->hasTable('rbac_permissions')) {
            return;
        }

        $row = $this->fetchRow(
            "SELECT id, action FROM rbac_permissions WHERE code = 'financeiro.bancos' LIMIT 1"
        );

        if (empty($row)) {
            return;
        }

        $actionsRaw = trim((string)($row['action'] ?? ''));
        $actions = preg_split('/\s*,\s*/', $actionsRaw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $required = [
            'downloadRemessa',
            'download_remessa',
        ];

        foreach ($required as $action) {
            if (!in_array($action, $actions, true)) {
                $actions[] = $action;
            }
        }

        $actions = array_values(array_unique(array_filter($actions, function ($value) {
            return trim((string)$value) !== '';
        })));

        $novoAction = implode(',', $actions);

        $this->execute(sprintf(
            "UPDATE rbac_permissions SET action = '%s' WHERE code = 'financeiro.bancos'",
            str_replace("'", "''", $novoAction)
        ));
    }

    public function down()
    {
        // Não remove a action em produção.
    }
}
