<?php
use Migrations\AbstractMigration;

/**
 * Adiciona flag para auto-importação de notas via DF-e cron.
 * Quando ativo, o cron cria notas de entrada automaticamente e notifica no sino.
 */
class AddDfeAutoImportConfig extends AbstractMigration {

    public function change() {
        if (!$this->hasTable('fiscal_empresas_config')) {
            return;
        }
        $t = $this->table('fiscal_empresas_config');
        if ($t->hasColumn('dfe_auto_import')) {
            return;
        }
        $t->addColumn('dfe_auto_import', 'boolean', [
                'null' => false,
                'default' => false,
                'comment' => 'Auto-criar notas de entrada a partir da fila DF-e recebidos',
            ])
            ->addColumn('dfe_auto_import_notificar', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Notificar equipe no sino quando novas notas forem auto-importadas',
            ])
            ->update();
    }
}
