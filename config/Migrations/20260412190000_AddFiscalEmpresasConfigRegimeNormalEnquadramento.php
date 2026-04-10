<?php
/**
 * Regime Normal (CRT=3): distingue Lucro Presumido vs Lucro Real para alíquotas PIS/COFINS de referência.
 * CRT na NF-e continua 1|2|3 conforme regime_tributario.
 */
use Migrations\AbstractMigration;

class AddFiscalEmpresasConfigRegimeNormalEnquadramento extends AbstractMigration {

    public function up() {
        if (!$this->hasTable('fiscal_empresas_config')) {
            return;
        }
        $table = $this->table('fiscal_empresas_config');
        if ($table->hasColumn('regime_normal_enquadramento')) {
            return;
        }
        $table->addColumn('regime_normal_enquadramento', 'integer', [
            'null' => true,
            'default' => null,
            'comment' => '1=Lucro Presumido 2=Lucro Real; apenas regime_tributario=3',
        ])->update();

        $this->execute(
            'UPDATE fiscal_empresas_config SET regime_normal_enquadramento = 2 '
            . 'WHERE regime_tributario = 3 AND regime_normal_enquadramento IS NULL'
        );
    }

    public function down() {
        if (!$this->hasTable('fiscal_empresas_config')) {
            return;
        }
        $table = $this->table('fiscal_empresas_config');
        if ($table->hasColumn('regime_normal_enquadramento')) {
            $table->removeColumn('regime_normal_enquadramento')->update();
        }
    }
}
