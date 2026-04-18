<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FinanceiroRemessasTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable("financeiro_remessas");
        $this->setDisplayField("nome_arquivo");
        $this->setPrimaryKey("id");
        $this->addBehavior("Timestamp");

        $this->belongsTo("Empresas", [
            "foreignKey" => "idempresa",
            "joinType" => "INNER",
        ]);

        $this->belongsTo("FinanceiroBancos", [
            "foreignKey" => "financeiro_banco_id",
            "joinType" => "INNER",
        ]);

        $this->belongsTo("Users", [
            "foreignKey" => "usuario_id",
            "joinType" => "LEFT",
        ]);

        $this->hasMany("FinanceiroRemessaTitulos", [
            "foreignKey" => "financeiro_remessa_id",
            "dependent" => true,
            "cascadeCallbacks" => true,
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator->integer("id")->allowEmptyString("id", null, "create");

        $validator
            ->integer("idempresa", "Empresa inválida.")
            ->requirePresence("idempresa", "create")
            ->notEmptyString("idempresa", "Empresa obrigatória.");

        $validator
            ->integer("financeiro_banco_id", "Banco inválido.")
            ->requirePresence("financeiro_banco_id", "create")
            ->notEmptyString("financeiro_banco_id", "Banco obrigatório.");

        $validator
            ->integer("usuario_id", "Usuário inválido.")
            ->allowEmptyString("usuario_id");

        $validator
            ->scalar("cnab_layout")
            ->maxLength("cnab_layout", 10, "Layout CNAB deve ter no máximo 10 caracteres.")
            ->requirePresence("cnab_layout", "create")
            ->notEmptyString("cnab_layout", "Layout CNAB obrigatório.");

        $validator->add("cnab_layout", "formato", [
            "rule" => function ($value) {
                $value = trim((string) $value);
                return in_array($value, ["240", "400"], true);
            },
            "message" => "Layout CNAB deve ser 240 ou 400.",
        ]);

        $validator
            ->integer("sequencial_arquivo", "Sequencial inválido.")
            ->requirePresence("sequencial_arquivo", "create")
            ->notEmptyString("sequencial_arquivo", "Sequencial obrigatório.")
            ->greaterThan("sequencial_arquivo", 0, "Sequencial deve ser maior que zero.");

        $validator
            ->scalar("numero_remessa")
            ->maxLength("numero_remessa", 30, "Número da remessa deve ter no máximo 30 caracteres.")
            ->allowEmptyString("numero_remessa");

        $validator
            ->date("data_geracao", ["ymd"], "Data de geração inválida.")
            ->requirePresence("data_geracao", "create")
            ->notEmptyDate("data_geracao", "Data de geração obrigatória.");

        $validator
            ->scalar("status")
            ->maxLength("status", 30, "Status deve ter no máximo 30 caracteres.")
            ->requirePresence("status", "create")
            ->notEmptyString("status", "Status obrigatório.");

        $validator->add("status", "dominio", [
            "rule" => function ($value) {
                return in_array((string) $value, [
                    "gerada",
                    "enviada",
                    "processada",
                    "erro",
                    "cancelada",
                ], true);
            },
            "message" => "Status da remessa inválido.",
        ]);

        $validator
            ->scalar("nome_arquivo")
            ->maxLength("nome_arquivo", 255, "Nome do arquivo deve ter no máximo 255 caracteres.")
            ->requirePresence("nome_arquivo", "create")
            ->notEmptyString("nome_arquivo", "Nome do arquivo obrigatório.");

        $validator
            ->scalar("caminho_arquivo")
            ->maxLength("caminho_arquivo", 255, "Caminho do arquivo deve ter no máximo 255 caracteres.")
            ->allowEmptyString("caminho_arquivo");

        $validator
            ->integer("quantidade_titulos", "Quantidade de títulos inválida.")
            ->requirePresence("quantidade_titulos", "create")
            ->notEmptyString("quantidade_titulos", "Quantidade de títulos obrigatória.")
            ->greaterThanOrEqual("quantidade_titulos", 0, "Quantidade de títulos não pode ser negativa.");

        $validator
            ->decimal("valor_total", 2, "Valor total inválido.")
            ->requirePresence("valor_total", "create")
            ->notEmptyString("valor_total", "Valor total obrigatório.");

        $validator
            ->greaterThanOrEqual("valor_total", 0, "Valor total não pode ser negativo.");

        $validator
            ->scalar("observacoes")
            ->allowEmptyString("observacoes");

        return $validator;
    }

    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(["idempresa"], "Empresas"), [
            "errorField" => "idempresa",
            "message" => "Empresa informada não existe.",
        ]);

        $rules->add($rules->existsIn(["financeiro_banco_id"], "FinanceiroBancos"), [
            "errorField" => "financeiro_banco_id",
            "message" => "Banco informado não existe.",
        ]);

        $rules->add(
            function ($entity) {
                if (empty($entity->usuario_id)) {
                    return true;
                }

                return $this->Users->exists([
                    "id" => (int) $entity->usuario_id,
                ]);
            },
            "usuarioExiste",
            [
                "errorField" => "usuario_id",
                "message" => "Usuário informado não existe.",
            ]
        );

        $rules->add(
            function ($entity) {
                $conditions = [
                    "idempresa" => (int) $entity->idempresa,
                    "financeiro_banco_id" => (int) $entity->financeiro_banco_id,
                    "cnab_layout" => (string) $entity->cnab_layout,
                    "sequencial_arquivo" => (int) $entity->sequencial_arquivo,
                ];

                $query = $this->find()->where($conditions);

                if (!$entity->isNew()) {
                    $query->where(["id <>" => (int) $entity->id]);
                }

                return $query->count() === 0;
            },
            "sequencialUnicoPorBanco",
            [
                "errorField" => "sequencial_arquivo",
                "message" => "Já existe uma remessa com este sequencial para o banco e layout informados.",
            ]
        );

        return $rules;
    }
}
