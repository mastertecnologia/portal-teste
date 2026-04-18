<?php
use Migrations\AbstractMigration;

/**
 * Cria a estrutura do módulo Financeiro > Bancos.
 *
 * - Tabela `financeiro_bancos` por empresa
 * - Vínculo opcional `financeiro_banco_id` em `financeiro_lancamentos`
 *
 * Observações:
 * - Compatível com PostgreSQL usado no projeto
 * - Não remove dados já existentes
 */
class CreateFinanceiroBancos extends AbstractMigration
{
    public function up()
    {
        if ($this->hasTable("financeiro_bancos")) {
            $this->_ensureLancamentosForeignKey();
            return;
        }

        $table = $this->table("financeiro_bancos");
        $table
            ->addColumn("idempresa", "integer", [
                "null" => false,
            ])
            ->addColumn("codigo_banco", "string", [
                "limit" => 10,
                "null" => false,
                "comment" => "Código bancário oficial. Ex: 001, 341, 756",
            ])
            ->addColumn("numero_banco", "string", [
                "limit" => 10,
                "null" => true,
                "default" => null,
                "comment" => "Número exibido do banco quando aplicável",
            ])
            ->addColumn("cnab", "string", [
                "limit" => 10,
                "null" => true,
                "default" => null,
                "comment" => "Código CNAB do banco",
            ])
            ->addColumn("nome", "string", [
                "limit" => 255,
                "null" => false,
            ])
            ->addColumn("numero_agencia", "string", [
                "limit" => 20,
                "null" => true,
                "default" => null,
            ])
            ->addColumn("digito_agencia", "string", [
                "limit" => 5,
                "null" => true,
                "default" => null,
            ])
            ->addColumn("numero_conta", "string", [
                "limit" => 30,
                "null" => true,
                "default" => null,
            ])
            ->addColumn("digito_conta", "string", [
                "limit" => 5,
                "null" => true,
                "default" => null,
            ])
            ->addColumn("codigo_banco_interno", "string", [
                "limit" => 50,
                "null" => true,
                "default" => null,
                "comment" => "Código interno/ERP do banco",
            ])
            ->addColumn("verifica_receber", "string", [
                "limit" => 100,
                "null" => true,
                "default" => null,
                "comment" =>
                    "Configuração textual usada em integrações/rotinas de recebimento",
            ])
            ->addColumn("utiliza_endosso", "string", [
                "limit" => 10,
                "null" => true,
                "default" => "N",
                "comment" => "Indicador textual de uso de endosso (ex.: S/N)",
            ])
            ->addColumn("logotipo", "string", [
                "limit" => 255,
                "null" => true,
                "default" => null,
                "comment" => "Caminho ou URL do logotipo",
            ])
            ->addColumn("observacoes", "text", [
                "null" => true,
                "default" => null,
            ])
            ->addColumn("ativo", "boolean", [
                "null" => false,
                "default" => true,
            ])
            ->addColumn("created", "datetime", [
                "null" => true,
                "default" => null,
            ])
            ->addColumn("modified", "datetime", [
                "null" => true,
                "default" => null,
            ])
            ->addIndex(["idempresa"], ["name" => "idx_fin_bancos_idempresa"])
            ->addIndex(["codigo_banco"], ["name" => "idx_fin_bancos_codigo"])
            ->addIndex(["cnab"], ["name" => "idx_fin_bancos_cnab"])
            ->addIndex(["ativo"], ["name" => "idx_fin_bancos_ativo"])
            ->addIndex(
                ["idempresa", "codigo_banco", "numero_conta"],
                [
                    "name" => "ux_fin_bancos_emp_cod_conta",
                    "unique" => true,
                ],
            )
            ->addForeignKey("idempresa", "empresas", "id", [
                "delete" => "CASCADE",
                "update" => "CASCADE",
                "constraint" => "fk_fin_bancos_empresa",
            ])
            ->create();

        $this->_ensureLancamentosForeignKey();
    }

    public function down()
    {
        if ($this->hasTable("financeiro_lancamentos")) {
            $table = $this->table("financeiro_lancamentos");

            if ($table->hasForeignKey("financeiro_banco_id")) {
                $table->dropForeignKey("financeiro_banco_id");
            }

            $indexes = $table->getIndexes();
            if (isset($indexes["idx_fin_lanc_banco"])) {
                $table->removeIndexByName("idx_fin_lanc_banco");
            }

            if ($table->hasColumn("financeiro_banco_id")) {
                $table->removeColumn("financeiro_banco_id");
            }

            $table->update();
        }

        if ($this->hasTable("financeiro_bancos")) {
            $this->table("financeiro_bancos")->drop()->save();
        }
    }

    /**
     * Garante a coluna opcional em financeiro_lancamentos.
     */
    protected function _ensureLancamentosForeignKey()
    {
        if (!$this->hasTable("financeiro_lancamentos")) {
            return;
        }

        $table = $this->table("financeiro_lancamentos");

        $needsUpdate = false;

        if (!$table->hasColumn("financeiro_banco_id")) {
            $table->addColumn("financeiro_banco_id", "integer", [
                "null" => true,
                "default" => null,
                "comment" => "Banco vinculado ao lançamento financeiro",
            ]);
            $needsUpdate = true;
        }

        $indexes = $table->getIndexes();
        if (!isset($indexes["idx_fin_lanc_banco"])) {
            $table->addIndex(
                ["financeiro_banco_id"],
                [
                    "name" => "idx_fin_lanc_banco",
                ],
            );
            $needsUpdate = true;
        }

        if (!$table->hasForeignKey("financeiro_banco_id")) {
            $table->addForeignKey(
                "financeiro_banco_id",
                "financeiro_bancos",
                "id",
                [
                    "delete" => "SET_NULL",
                    "update" => "CASCADE",
                    "constraint" => "fk_fin_lanc_banco",
                ],
            );
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $table->update();
        }
    }
}
