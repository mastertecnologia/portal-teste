<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FinanceiroRetornoItensTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable("financeiro_retorno_itens");
        $this->setDisplayField("nosso_numero");
        $this->setPrimaryKey("id");
        $this->addBehavior("Timestamp");

        $this->belongsTo("FinanceiroRetornoArquivos", [
            "foreignKey" => "financeiro_retorno_arquivo_id",
            "joinType" => "INNER",
        ]);

        $this->belongsTo("FinanceiroLancamentos", [
            "foreignKey" => "financeiro_lancamento_id",
            "joinType" => "LEFT",
        ]);

        $this->belongsTo("FinanceiroRemessas", [
            "foreignKey" => "financeiro_remessa_id",
            "joinType" => "LEFT",
        ]);

        $this->belongsTo("FinanceiroRemessaTitulos", [
            "foreignKey" => "financeiro_remessa_titulo_id",
            "joinType" => "LEFT",
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator->integer("id")->allowEmptyString("id", null, "create");

        $validator
            ->integer(
                "financeiro_retorno_arquivo_id",
                "Arquivo de retorno inválido.",
            )
            ->requirePresence("financeiro_retorno_arquivo_id", "create")
            ->notEmptyString(
                "financeiro_retorno_arquivo_id",
                "Arquivo de retorno obrigatório.",
            );

        $validator
            ->integer("financeiro_lancamento_id", "Lançamento inválido.")
            ->allowEmptyString("financeiro_lancamento_id");

        $validator
            ->integer("financeiro_remessa_id", "Remessa inválida.")
            ->allowEmptyString("financeiro_remessa_id");

        $validator
            ->integer(
                "financeiro_remessa_titulo_id",
                "Item de remessa inválido.",
            )
            ->allowEmptyString("financeiro_remessa_titulo_id");

        $validator
            ->scalar("status_item")
            ->maxLength(
                "status_item",
                30,
                "Status do item deve ter no máximo 30 caracteres.",
            )
            ->requirePresence("status_item", "create")
            ->notEmptyString("status_item", "Status do item obrigatório.");

        $validator->add("status_item", "dominio", [
            "rule" => function ($value) {
                return in_array((string) $value, [
                    "baixado",
                    "rejeitado",
                    "ignorado",
                    "erro",
                    "aceito",
                ], true);
            },
            "message" => "Status do item de retorno inválido.",
        ]);

        $validator
            ->scalar("nosso_numero")
            ->maxLength(
                "nosso_numero",
                40,
                "Nosso número deve ter no máximo 40 caracteres.",
            )
            ->allowEmptyString("nosso_numero");

        $validator
            ->scalar("numero_documento")
            ->maxLength(
                "numero_documento",
                40,
                "Número do documento deve ter no máximo 40 caracteres.",
            )
            ->allowEmptyString("numero_documento");

        $validator
            ->scalar("codigo_ocorrencia")
            ->maxLength(
                "codigo_ocorrencia",
                10,
                "Código de ocorrência deve ter no máximo 10 caracteres.",
            )
            ->allowEmptyString("codigo_ocorrencia");

        $validator
            ->scalar("mensagem_ocorrencia")
            ->allowEmptyString("mensagem_ocorrencia");

        $validator
            ->decimal("valor_titulo", 2, "Valor do título inválido.")
            ->requirePresence("valor_titulo", "create")
            ->notEmptyString("valor_titulo", "Valor do título obrigatório.")
            ->greaterThanOrEqual(
                "valor_titulo",
                0,
                "Valor do título não pode ser negativo.",
            );

        $validator
            ->decimal("valor_pago", 2, "Valor pago inválido.")
            ->allowEmptyString("valor_pago")
            ->greaterThanOrEqual(
                "valor_pago",
                0,
                "Valor pago não pode ser negativo.",
            );

        $validator
            ->date("data_vencimento", ["ymd"], "Data de vencimento inválida.")
            ->allowEmptyDate("data_vencimento");

        $validator
            ->dateTime("data_ocorrencia", "Data da ocorrência inválida.")
            ->allowEmptyDateTime("data_ocorrencia");

        $validator
            ->scalar("linha_segmento_t")
            ->allowEmptyString("linha_segmento_t");

        $validator
            ->scalar("linha_segmento_u")
            ->allowEmptyString("linha_segmento_u");

        $validator
            ->scalar("payload_json")
            ->allowEmptyString("payload_json");

        return $validator;
    }

    public function buildRules(RulesChecker $rules)
    {
        $rules->add(
            $rules->existsIn(
                ["financeiro_retorno_arquivo_id"],
                "FinanceiroRetornoArquivos",
            ),
            [
                "errorField" => "financeiro_retorno_arquivo_id",
                "message" => "Arquivo de retorno informado não existe.",
            ],
        );

        $rules->add(
            function ($entity) {
                if (empty($entity->financeiro_lancamento_id)) {
                    return true;
                }

                return $this->FinanceiroLancamentos->exists([
                    "id" => (int) $entity->financeiro_lancamento_id,
                ]);
            },
            "financeiroLancamentoExiste",
            [
                "errorField" => "financeiro_lancamento_id",
                "message" => "Lançamento financeiro informado não existe.",
            ],
        );

        $rules->add(
            function ($entity) {
                if (empty($entity->financeiro_remessa_id)) {
                    return true;
                }

                return $this->FinanceiroRemessas->exists([
                    "id" => (int) $entity->financeiro_remessa_id,
                ]);
            },
            "financeiroRemessaExiste",
            [
                "errorField" => "financeiro_remessa_id",
                "message" => "Remessa informada não existe.",
            ],
        );

        $rules->add(
            function ($entity) {
                if (empty($entity->financeiro_remessa_titulo_id)) {
                    return true;
                }

                return $this->FinanceiroRemessaTitulos->exists([
                    "id" => (int) $entity->financeiro_remessa_titulo_id,
                ]);
            },
            "financeiroRemessaTituloExiste",
            [
                "errorField" => "financeiro_remessa_titulo_id",
                "message" => "Item de remessa informado não existe.",
            ],
        );

        return $rules;
    }
}
