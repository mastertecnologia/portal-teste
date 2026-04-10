<?php
use Migrations\AbstractMigration;

/**
 * Adiciona colunas de contingência à tabela fiscal_empresas_config.
 */
class AddContingenciaColumns extends AbstractMigration {

    public function up() {
        $table = $this->table('fiscal_empresas_config');
        if (!$table->hasColumn('contingencia_modo')) {
            $table->addColumn('contingencia_modo', 'string', [
                'limit' => 20,
                'null' => true,
                'default' => null,
                'comment' => 'Modo de contingência: svc_rs, svc_an, epec ou null (normal)',
            ]);
        }
        if (!$table->hasColumn('contingencia_justificativa')) {
            $table->addColumn('contingencia_justificativa', 'text', [
                'null' => true,
                'default' => null,
            ]);
        }
        if (!$table->hasColumn('contingencia_inicio')) {
            $table->addColumn('contingencia_inicio', 'datetime', [
                'null' => true,
                'default' => null,
            ]);
        }
        $table->update();
    }

    public function down() {
        $table = $this->table('fiscal_empresas_config');
        if ($table->hasColumn('contingencia_modo')) {
            $table->removeColumn('contingencia_modo');
        }
        if ($table->hasColumn('contingencia_justificativa')) {
            $table->removeColumn('contingencia_justificativa');
        }
        if ($table->hasColumn('contingencia_inicio')) {
            $table->removeColumn('contingencia_inicio');
        }
        $table->update();
    }
}
