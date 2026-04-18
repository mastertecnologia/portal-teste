<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FinanceiroRemessaTitulosTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable("financeiro_remessa_titulos");
        $this->setDisplayField("id");
        $this->setPrimaryKey("id");
        $this->addBehavior("Timestamp");

        $this->belongsTo("FinanceiroRemessas", [
            "foreignKey" => "financeiro_remessa_id",
            "joinType" => "INNER",
        ]);

        $this->belongsTo("FinanceiroLancamentos", [
            "foreignKey" => "financeiro_lancamento_id",
            "joinType" => "INNER",
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator->integer("id")->allowEmptyString("id", null, "create");

        $validator
            ->integer("financeiro_remessa_id", "Remessa inválida.")
            ->requirePresence("financeiro_remessa_id", "create")
            ->notEmptyString("financeiro_remessa_id", "Remessa obrigatória.");

        $validator
            ->integer("financeiro_lancamento_id", "Título inválido.")
            ->requirePresence("financeiro_lancamento_id", "create")
            ->notEmptyString("financeiro_lancamento_id", "Título obrigatório.");

        $validator
            ->scalar("nosso_numero_remessa")
            ->maxLength(
                "nosso_numero_remessa",
                40,
                "Nosso número da remessa deve ter no máximo 40 caracteres.",
            )
            ->allowEmptyString("nosso_numero_remessa");

        $validator
            ->scalar("numero_documento")
            ->maxLength(
                "numero_documento",
                40,
                "Número do documento deve ter no máximo 40 caracteres.",
            )
            ->allowEmptyString("numero_documento");

        $validator
            ->decimal("valor_titulo", 2, "Valor do título inválido.")
            ->requirePresence("valor_titulo", "create")
            ->notEmptyString("valor_titulo", "Valor do título obrigatório.");

        $validator
            ->date("data_vencimento", ["ymd"], "Data de vencimento inválida.")
            ->allowEmptyDate("data_vencimento");

        $validator
            ->scalar("status_item")
            ->maxLength(
                "status_item",
                30,
                "Status do item deve ter no máximo 30 caracteres.",
            )
            ->requirePresence("status_item", "create")
            ->notEmptyString("status_item", "Status do item obrigatório.");

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

        return $validator;
    }

    public function buildRules(RulesChecker $rules)
    {
        $rules->add(
            $rules->existsIn(
                ["financeiro_remessa_id"],
                "FinanceiroRemessas",
            ),
            [
                "errorField" => "financeiro_remessa_id",
                "message" => "Remessa informada não existe.",
            ],
        );

        $rules->add(
            $rules->existsIn(
                ["financeiro_lancamento_id"],
                "FinanceiroLancamentos",
            ),
            [
                "errorField" => "financeiro_lancamento_id",
                "message" => "Título informado não existe.",
            ],
        );

        $rules->add(
            function ($entity) {
                if (
                    empty($entity->financeiro_remessa_id) ||
                    empty($entity->financeiro_lancamento_id)
                ) {
                    return true;
                }

                $query = $this->find()->where([
                    "financeiro_remessa_id" => (int) $entity->financeiro_remessa_id,
                    "financeiro_lancamento_id" => (int) $entity->financeiro_lancamento_id,
                ]);

                if (!$entity->isNew()) {
                    $query->where(["id <>" => (int) $entity->id]);
                }

                return $query->count() === 0;
            },
            "uniqueLancamentoPorRemessa",
            [
                "errorField" => "financeiro_lancamento_id",
                "message" => "Este título já foi incluído nesta remessa.",
            ],
        );

        return $rules;
    }
}
