<?php
use Migrations\AbstractMigration;

/**
 * Dados do contabilista para registro 0100 (EFD-ICMS/IPI / SPED Fiscal).
 */
class AddFiscalEmpresasConfigSpedContabilista extends AbstractMigration {

    public function change() {
        if (!$this->hasTable('fiscal_empresas_config')) {
            return;
        }
        $t = $this->table('fiscal_empresas_config');
        if ($t->hasColumn('sped_contabilista_nome')) {
            return;
        }

        $t
            ->addColumn('sped_contabilista_nome', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('sped_contabilista_cpf', 'string', ['limit' => 14, 'null' => true])
            ->addColumn('sped_contabilista_crc', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('sped_contabilista_cnpj', 'string', ['limit' => 18, 'null' => true])
            ->addColumn('sped_contabilista_cep', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('sped_contabilista_logradouro', 'string', ['limit' => 60, 'null' => true])
            ->addColumn('sped_contabilista_numero', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('sped_contabilista_complemento', 'string', ['limit' => 60, 'null' => true])
            ->addColumn('sped_contabilista_bairro', 'string', ['limit' => 60, 'null' => true])
            ->addColumn('sped_contabilista_fone', 'string', ['limit' => 11, 'null' => true])
            ->addColumn('sped_contabilista_fax', 'string', ['limit' => 11, 'null' => true])
            ->addColumn('sped_contabilista_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('sped_contabilista_cod_municipio', 'string', ['limit' => 7, 'null' => true])
            ->update();
    }
}
