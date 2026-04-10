<?php
use Migrations\AbstractMigration;

/**
 * Índice composto para listagens por empresa e busca parcial por chave (WHERE idempresa = ? AND chave_acesso ILIKE ?).
 */
class AddIndexFiscalDfeRecebidosEmpresaChave extends AbstractMigration {

    public function up() {
        if (!$this->hasTable('fiscal_dfe_recebidos')) {
            return;
        }
        $this->table('fiscal_dfe_recebidos')
            ->addIndex(['idempresa', 'chave_acesso'], ['name' => 'ix_fiscal_dfe_emp_chave'])
            ->update();
    }

    public function down() {
        if (!$this->hasTable('fiscal_dfe_recebidos')) {
            return;
        }
        $this->table('fiscal_dfe_recebidos')
            ->removeIndex(['idempresa', 'chave_acesso'])
            ->update();
    }
}
