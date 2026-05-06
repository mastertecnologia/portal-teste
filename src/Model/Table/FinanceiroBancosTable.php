<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FinanceiroBancosTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable("financeiro_bancos");
        $this->setDisplayField("nome");
        $this->setPrimaryKey("id");
        $this->addBehavior("Timestamp");

        $this->belongsTo("Empresas", [
            "foreignKey" => "idempresa",
            "joinType" => "INNER",
        ]);

        $this->hasMany("FinanceiroLancamentos", [
            "foreignKey" => "financeiro_banco_id",
            "dependent" => false,
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
            ->scalar("codigo_banco")
            ->maxLength(
                "codigo_banco",
                10,
                "Código bancário deve ter no máximo 10 caracteres.",
            )
            ->allowEmptyString("codigo_banco");

        $validator->add("codigo_banco", "formato", [
            "rule" => function ($value) {
                return preg_match('/^\d+$/', (string) $value) === 1;
            },
            "message" => "Código bancário deve conter apenas números.",
        ]);

        $validator
            ->scalar("numero_banco")
            ->maxLength(
                "numero_banco",
                10,
                "Número do banco deve ter no máximo 10 caracteres.",
            )
            ->allowEmptyString("numero_banco");

        $validator->add("numero_banco", "formato", [
            "rule" => function ($value, $context) {
                if ($value === null || $value === "") {
                    return true;
                }
                return preg_match('/^\d+$/', (string) $value) === 1;
            },
            "message" => "Número do banco deve conter apenas números.",
        ]);

        $validator
            ->scalar("cnab")
            ->maxLength("cnab", 10, "CNAB deve ter no máximo 10 caracteres.")
            ->allowEmptyString("cnab");

        $validator->add("cnab", "formato", [
            "rule" => function ($value, $context) {
                if ($value === null || $value === "") {
                    return true;
                }
                return preg_match('/^\d+$/', (string) $value) === 1;
            },
            "message" => "CNAB deve conter apenas números.",
        ]);

        $validator
            ->scalar("nome")
            ->maxLength(
                "nome",
                255,
                "Nome do banco deve ter no máximo 255 caracteres.",
            )
            ->requirePresence("nome", "create")
            ->notEmptyString("nome", "Nome do banco obrigatório.");

        $validator
            ->scalar("numero_agencia")
            ->maxLength(
                "numero_agencia",
                20,
                "Número da agência deve ter no máximo 20 caracteres.",
            )
            ->allowEmptyString("numero_agencia");

        $validator->add("numero_agencia", "formato", [
            "rule" => function ($value, $context) {
                if ($value === null || $value === "") {
                    return true;
                }
                return preg_match('/^\d+$/', (string) $value) === 1;
            },
            "message" => "Número da agência deve conter apenas números.",
        ]);

        $validator
            ->scalar("digito_agencia")
            ->maxLength(
                "digito_agencia",
                5,
                "Dígito da agência deve ter no máximo 5 caracteres.",
            )
            ->allowEmptyString("digito_agencia");

        $validator->add("digito_agencia", "formato", [
            "rule" => function ($value, $context) {
                if ($value === null || $value === "") {
                    return true;
                }
                return preg_match('/^[0-9A-Za-z-]+$/', (string) $value) === 1;
            },
            "message" => "Dígito da agência inválido.",
        ]);

        $validator
            ->scalar("numero_conta")
            ->maxLength(
                "numero_conta",
                30,
                "Número da conta deve ter no máximo 30 caracteres.",
            )
            ->allowEmptyString("numero_conta");

        $validator->add("numero_conta", "formato", [
            "rule" => function ($value, $context) {
                if ($value === null || $value === "") {
                    return true;
                }
                return preg_match('/^\d+$/', (string) $value) === 1;
            },
            "message" => "Número da conta deve conter apenas números.",
        ]);

        $validator
            ->scalar("digito_conta")
            ->maxLength(
                "digito_conta",
                5,
                "Dígito da conta deve ter no máximo 5 caracteres.",
            )
            ->allowEmptyString("digito_conta");

        $validator->add("digito_conta", "formato", [
            "rule" => function ($value, $context) {
                if ($value === null || $value === "") {
                    return true;
                }
                return preg_match('/^[0-9A-Za-z-]+$/', (string) $value) === 1;
            },
            "message" => "Dígito da conta inválido.",
        ]);

        $validator
            ->scalar("codigo_banco_interno")
            ->maxLength(
                "codigo_banco_interno",
                50,
                "Código bancário interno deve ter no máximo 50 caracteres.",
            )
            ->allowEmptyString("codigo_banco_interno");

        $validator
            ->scalar("verifica_receber")
            ->maxLength(
                "verifica_receber",
                100,
                "Campo verificar receber deve ter no máximo 100 caracteres.",
            )
            ->allowEmptyString("verifica_receber");

        $validator
            ->scalar("utiliza_endosso")
            ->maxLength(
                "utiliza_endosso",
                10,
                "Campo utiliza endosso deve ter no máximo 10 caracteres.",
            )
            ->allowEmptyString("utiliza_endosso");

        $validator
            ->scalar("convenio")
            ->maxLength(
                "convenio",
                50,
                "Convênio deve ter no máximo 50 caracteres.",
            )
            ->allowEmptyString("convenio");

        $validator->add("convenio", "formato", [
            "rule" => function ($value, $context) {
                if ($value === null || $value === "") {
                    return true;
                }
                return preg_match('/^[0-9A-Za-z.-]+$/', (string) $value) === 1;
            },
            "message" =>
                "Convênio deve conter apenas letras, números, ponto ou hífen.",
        ]);

        $validator
            ->scalar("carteira")
            ->maxLength(
                "carteira",
                20,
                "Carteira deve ter no máximo 20 caracteres.",
            )
            ->allowEmptyString("carteira");

        $validator->add("carteira", "formato", [
            "rule" => function ($value, $context) {
                if ($value === null || $value === "") {
                    return true;
                }
                return preg_match('/^[0-9A-Za-z.-]+$/', (string) $value) === 1;
            },
            "message" =>
                "Carteira deve conter apenas letras, números, ponto ou hífen.",
        ]);

        $validator
            ->scalar("cnab_tipo")
            ->maxLength(
                "cnab_tipo",
                10,
                "Tipo CNAB deve ter no máximo 10 caracteres.",
            )
            ->allowEmptyString("cnab_tipo");

        $validator->add("cnab_tipo", "dominio", [
            "rule" => function ($value, $context) {
                if ($value === null || $value === "") {
                    return true;
                }
                return in_array((string) $value, ["240", "400"], true);
            },
            "message" => "Tipo CNAB deve ser 240 ou 400.",
        ]);

        $validator
            ->integer("proxima_remessa", "Próxima remessa inválida.")
            ->allowEmptyString("proxima_remessa");

        $validator
            ->greaterThan(
                "proxima_remessa",
                0,
                "Próxima remessa deve ser maior que zero.",
            )
            ->allowEmptyString("proxima_remessa");

        $validator
            ->boolean("ativo", "Status ativo inválido.")
            ->allowEmptyString("ativo");

        $validator->scalar("observacoes")->allowEmptyString("observacoes");

        $validator->allowEmptyString("logotipo")->add("logotipo", "tamanho", [
            "rule" => ["maxLength", 255],
            "message" => "Logotipo deve ter no máximo 255 caracteres.",
        ]);

        $validator->add("conta_bancaria_minima", "dadosConta", [
            "rule" => function ($value, $context) {
                $data = $context["data"] ?? [];

                $agencia = trim((string) ($data["numero_agencia"] ?? ""));
                $conta = trim((string) ($data["numero_conta"] ?? ""));

                if ($agencia === "" && $conta === "") {
                    return true;
                }

                return $agencia !== "" && $conta !== "";
            },
            "message" =>
                "Informe agência e conta juntas quando preencher dados bancários.",
        ]);

        return $validator;
    }

    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(["idempresa"], "Empresas"), [
            "errorField" => "idempresa",
            "message" => "Empresa informada não existe.",
        ]);

        $rules->add(
            function ($entity) {
                $codigoBanco = trim((string) ($entity->codigo_banco ?? ""));
                $numeroAgencia = trim((string) ($entity->numero_agencia ?? ""));
                $digitoAgencia = trim((string) ($entity->digito_agencia ?? ""));
                $numeroConta = trim((string) ($entity->numero_conta ?? ""));
                $digitoConta = trim((string) ($entity->digito_conta ?? ""));

                if (
                    $codigoBanco === "" &&
                    $numeroAgencia === "" &&
                    $digitoAgencia === "" &&
                    $numeroConta === "" &&
                    $digitoConta === ""
                ) {
                    return true;
                }

                $conditions = [
                    "idempresa" => (int) $entity->idempresa,
                    "codigo_banco" => $codigoBanco !== "" ? $codigoBanco : null,
                    "numero_agencia" =>
                        $numeroAgencia !== "" ? $numeroAgencia : null,
                    "digito_agencia" =>
                        $digitoAgencia !== "" ? $digitoAgencia : null,
                    "numero_conta" => $numeroConta !== "" ? $numeroConta : null,
                    "digito_conta" => $digitoConta !== "" ? $digitoConta : null,
                ];

                $query = $this->find()->where($conditions);

                if (!$entity->isNew()) {
                    $query->where(["id <>" => (int) $entity->id]);
                }

                return $query->count() === 0;
            },
            "uniqueContaPorEmpresa",
            [
                "errorField" => "numero_conta",
                "message" =>
                    "Já existe uma conta bancária com esse banco, agência e conta para esta empresa.",
            ],
        );

        $rules->add(
            function ($entity) {
                $convenio = trim((string) ($entity->convenio ?? ""));
                $carteira = trim((string) ($entity->carteira ?? ""));
                $cnabTipo = trim((string) ($entity->cnab_tipo ?? ""));
                $codigoBanco = trim((string) ($entity->codigo_banco ?? ""));

                if (
                    $convenio === "" ||
                    $carteira === "" ||
                    $cnabTipo === "" ||
                    $codigoBanco === ""
                ) {
                    return true;
                }

                $conditions = [
                    "idempresa" => (int) $entity->idempresa,
                    "codigo_banco" => $codigoBanco,
                    "convenio" => $convenio,
                    "carteira" => $carteira,
                    "cnab_tipo" => $cnabTipo,
                ];

                $query = $this->find()->where($conditions);

                if (!$entity->isNew()) {
                    $query->where(["id <>" => (int) $entity->id]);
                }

                return $query->count() === 0;
            },
            "uniqueConvenioCarteiraPorEmpresa",
            [
                "errorField" => "convenio",
                "message" =>
                    "Já existe um banco com o mesmo convênio, carteira e layout CNAB para esta empresa.",
            ],
        );

        return $rules;
    }

    public function listByEmpresa(
        int $idempresa,
        bool $apenasAtivos = true
    ): array {
        $conditions = ["FinanceiroBancos.idempresa" => $idempresa];
        if ($apenasAtivos) {
            $conditions["FinanceiroBancos.ativo"] = true;
        }

        return $this->find("list", [
            "keyField" => "id",
            "valueField" => function ($row) {
                $codigo = trim((string) ($row->codigo_banco ?? ""));
                $nome = trim((string) ($row->nome ?? ""));
                $agencia = trim((string) ($row->numero_agencia ?? ""));
                $digitoAgencia = trim((string) ($row->digito_agencia ?? ""));
                $conta = trim((string) ($row->numero_conta ?? ""));
                $digitoConta = trim((string) ($row->digito_conta ?? ""));

                $label = $codigo !== "" ? $codigo . " — " . $nome : $nome;

                $extras = [];
                if ($agencia !== "") {
                    $extras[] =
                        "Ag. " .
                        $agencia .
                        ($digitoAgencia !== "" ? "-" . $digitoAgencia : "");
                }
                if ($conta !== "") {
                    $extras[] =
                        "Cc. " .
                        $conta .
                        ($digitoConta !== "" ? "-" . $digitoConta : "");
                }

                $convenio = trim((string) ($row->convenio ?? ""));
                if ($convenio !== "") {
                    $extras[] = "Conv. " . $convenio;
                }

                $carteira = trim((string) ($row->carteira ?? ""));
                if ($carteira !== "") {
                    $extras[] = "Cart. " . $carteira;
                }

                $cnabTipo = trim((string) ($row->cnab_tipo ?? ""));
                if ($cnabTipo !== "") {
                    $extras[] = "CNAB " . $cnabTipo;
                }

                if (!empty($extras)) {
                    $label .= " (" . implode(" | ", $extras) . ")";
                }

                return $label;
            },
        ])
            ->where($conditions)
            ->order([
                "FinanceiroBancos.codigo_banco" => "ASC",
                "FinanceiroBancos.nome" => "ASC",
            ])
            ->toArray();
    }
}
