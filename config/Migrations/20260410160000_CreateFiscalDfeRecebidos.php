<?php
use Migrations\AbstractMigration;

/**
 * Documentos extraídos do retorno Distribuição DF-e (fila de conferência / futura vinculação à NF-e entrada).
 */
class CreateFiscalDfeRecebidos extends AbstractMigration {

    public function up() {
        if ($this->hasTable('fiscal_dfe_recebidos')) {
            return;
        }
        $t = $this->table('fiscal_dfe_recebidos');
        $t->addColumn('idempresa', 'integer', ['null' => false])
            ->addColumn('nsu_doc', 'string', ['limit' => 20, 'null' => true, 'comment' => 'NSU no atributo do docZip quando existir'])
            ->addColumn('schema', 'string', ['limit' => 80, 'null' => false, 'default' => ''])
            ->addColumn('chave_acesso', 'string', ['limit' => 44, 'null' => true])
            ->addColumn('tipo_documento', 'string', ['limit' => 40, 'null' => false, 'default' => '', 'comment' => 'ex.: resNFe, resEvento'])
            ->addColumn('conteudo_hash', 'string', ['limit' => 32, 'null' => false, 'comment' => 'MD5 do xml_conteudo — dedupe'])
            ->addColumn('xml_conteudo', 'text', ['null' => false])
            ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'pendente', 'comment' => 'pendente|ignorado|vinculado'])
            ->addColumn('fiscal_nota_id', 'integer', ['null' => true, 'default' => null])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addForeignKey('idempresa', 'empresas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('fiscal_nota_id', 'fiscal_notas', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addIndex(['idempresa', 'status'], ['name' => 'ix_fiscal_dfe_emp_status'])
            ->addIndex(['idempresa', 'conteudo_hash'], ['unique' => true, 'name' => 'ux_fiscal_dfe_emp_hash'])
            ->create();
    }

    public function down() {
        if ($this->hasTable('fiscal_dfe_recebidos')) {
            $this->table('fiscal_dfe_recebidos')->drop()->save();
        }
    }
}
