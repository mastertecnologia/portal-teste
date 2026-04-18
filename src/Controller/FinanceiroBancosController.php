<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\FinanceiroBancosCatalogo;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Utility\Hash;

/**
 * Módulo Financeiro > Bancos
 *
 * Estrutura principal:
 * - Painel do módulo
 * - Cadastro de bancos por empresa
 * - Ações auxiliares para remessa/retorno
 * - Relatórios bancários básicos
 */
class FinanceiroBancosController extends AppController
{
    /**
     * @var bool
     */
    protected $financeiroLancamentosDisponivel = false;

    /**
     * @var bool
     */
    protected $financeiroExtratoDisponivel = false;

    public function initialize()
    {
        parent::initialize();

        $this->loadModel("FinanceiroBancos");

        try {
            $this->loadModel("FinanceiroLancamentos");
            $this->financeiroLancamentosDisponivel = true;
        } catch (\Exception $e) {
            $this->financeiroLancamentosDisponivel = false;
        }

        try {
            $this->loadModel("FinanceiroExtratoBancario");
            $this->financeiroExtratoDisponivel = true;
        } catch (\Exception $e) {
            $this->financeiroExtratoDisponivel = false;
        }

        try {
            $this->loadModel("Atividades");
        } catch (\Exception $e) {
            // Atividades é opcional neste módulo.
        }
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->set("title", "Financeiro — Bancos");
    }

    public function isAuthorized($user)
    {
        if ((int) ($user["role"] ?? 1) !== 0) {
            return false;
        }

        return parent::isAuthorized($user);
    }

    /**
     * Hub principal do módulo Bancos.
     */
    public function index()
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $totais = [
            "bancos" => 0,
            "ativos" => 0,
            "inativos" => 0,
            "receber" => 0.0,
            "pagar" => 0.0,
        ];

        $bancos = $this->FinanceiroBancos
            ->find()
            ->where(["FinanceiroBancos.idempresa" => $idempresa])
            ->order(["FinanceiroBancos.nome" => "ASC"])
            ->toArray();

        $totais["bancos"] = count($bancos);
        foreach ($bancos as $banco) {
            if (!empty($banco->ativo)) {
                $totais["ativos"]++;
            } else {
                $totais["inativos"]++;
            }
        }

        if ($this->financeiroLancamentosDisponivel) {
            try {
                $resumo = $this->_resumoLancamentosPorBanco($idempresa);
                $totais["receber"] = (float) $resumo["receber"];
                $totais["pagar"] = (float) $resumo["pagar"];
            } catch (\Exception $e) {
                // Mantém o painel funcional mesmo sem integração completa.
            }
        }

        $this->set(compact("bancos", "totais"));
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Lista/cadastro de bancos.
     */
    public function cadastrar()
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $codigo = trim((string) $this->request->getQuery("codigo"));
        $nome = trim((string) $this->request->getQuery("nome"));
        $ativo = (string) $this->request->getQuery("ativo", "");
        $contaStatus = trim(
            (string) $this->request->getQuery("conta_status", ""),
        );

        $conditions = [
            "FinanceiroBancos.idempresa" => $idempresa,
        ];

        if ($codigo !== "") {
            $conditions["OR"] = [
                "FinanceiroBancos.codigo_banco" => $codigo,
                "FinanceiroBancos.numero_banco" => $codigo,
                "FinanceiroBancos.cnab" => $codigo,
                "FinanceiroBancos.numero_agencia" => $codigo,
                "FinanceiroBancos.numero_conta" => $codigo,
            ];
        }

        if ($nome !== "") {
            $conditions["FinanceiroBancos.nome ILIKE"] = "%" . $nome . "%";
        }

        if ($ativo !== "") {
            $conditions["FinanceiroBancos.ativo"] = (int) $ativo;
        }

        $bancos = $this->FinanceiroBancos
            ->find()
            ->where($conditions)
            ->order([
                "FinanceiroBancos.ativo" => "DESC",
                "FinanceiroBancos.codigo_banco" => "ASC",
                "FinanceiroBancos.nome" => "ASC",
            ])
            ->toArray();

        if ($contaStatus !== "") {
            $bancos = array_values(
                array_filter($bancos, function ($banco) use ($contaStatus) {
                    $incompleta = $this->_bancoContaIncompleta($banco);

                    if ($contaStatus === "completa") {
                        return !$incompleta;
                    }

                    if ($contaStatus === "incompleta") {
                        return $incompleta;
                    }

                    return true;
                }),
            );
        }

        $metricasCadastro = [
            "bancos" => count($bancos),
            "ativos" => 0,
            "inativos" => 0,
            "conta_completa" => 0,
            "conta_incompleta" => 0,
        ];

        foreach ($bancos as $banco) {
            if (!empty($banco->ativo)) {
                $metricasCadastro["ativos"]++;
            } else {
                $metricasCadastro["inativos"]++;
            }

            if ($this->_bancoContaIncompleta($banco)) {
                $metricasCadastro["conta_incompleta"]++;
            } else {
                $metricasCadastro["conta_completa"]++;
            }
        }

        $catalogo = FinanceiroBancosCatalogo::buscar(
            $codigo !== "" ? $codigo : $nome,
        );

        $bancoSelecionado = !empty($bancos) ? $bancos[0] : null;

        $this->set(
            compact(
                "bancos",
                "catalogo",
                "codigo",
                "nome",
                "ativo",
                "contaStatus",
                "metricasCadastro",
                "bancoSelecionado",
            ),
        );
        $this->set("title", "Cadastro de Bancos");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Novo banco.
     */
    public function add()
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $codigoPrefill = trim((string) $this->request->getQuery("codigo"));
        $dadosIniciais = [];
        if ($codigoPrefill !== "") {
            $catalogoPrefill = FinanceiroBancosCatalogo::porCodigo(
                $codigoPrefill,
            );
            if (!empty($catalogoPrefill)) {
                $dadosIniciais = [
                    "codigo_banco" => $catalogoPrefill["codigo"] ?? "",
                    "numero_banco" => $catalogoPrefill["codigo"] ?? "",
                    "cnab" => $catalogoPrefill["cnab"] ?? "",
                    "nome" => $catalogoPrefill["nome"] ?? "",
                    "ativo" => true,
                ];
            }
        }

        $banco = $this->FinanceiroBancos->newEntity($dadosIniciais);

        if ($this->request->is("post")) {
            $data = $this->_normalizarDadosBanco(
                (array) $this->request->getData(),
            );
            $data["idempresa"] = $idempresa;

            if (empty($data["nome"]) && !empty($data["codigo_banco"])) {
                $catalogo = FinanceiroBancosCatalogo::porCodigo(
                    $data["codigo_banco"],
                );
                if (!empty($catalogo)) {
                    $data["nome"] = $catalogo["nome"];
                    $data["numero_banco"] =
                        $data["numero_banco"] ?: $catalogo["codigo"];
                    $data["cnab"] = $data["cnab"] ?: $catalogo["cnab"];
                }
            }

            $banco = $this->FinanceiroBancos->patchEntity($banco, $data);
            if ($this->FinanceiroBancos->save($banco)) {
                if (!empty($this->Atividades) && $this->Auth->user("id")) {
                    $this->Atividades->registrar(
                        (int) $this->Auth->user("id"),
                        $this->request->getParam("controller"),
                        $this->request->getParam("action"),
                        (int) $banco->id,
                    );
                }

                $this->Flash->success(__("Banco cadastrado com sucesso."));
                return $this->redirect(["action" => "cadastrar"]);
            }

            $this->Flash->error(
                __("Não foi possível salvar o banco. Verifique os campos."),
            );
        }

        $catalogo = FinanceiroBancosCatalogo::todos();
        $this->set(compact("banco", "catalogo", "codigoPrefill"));
        $this->set("title", "Novo Banco");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Edição de banco.
     *
     * @param int|null $id
     * @return \Cake\Http\Response|null
     */
    public function edit($id = null)
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $banco = $this->FinanceiroBancos
            ->find()
            ->where([
                "FinanceiroBancos.id" => $id,
                "FinanceiroBancos.idempresa" => $idempresa,
            ])
            ->first();

        if (empty($banco)) {
            throw new NotFoundException(__("Banco não encontrado."));
        }

        if ($this->request->is(["patch", "post", "put"])) {
            $data = $this->_normalizarDadosBanco(
                (array) $this->request->getData(),
            );
            unset($data["idempresa"]);

            if (empty($data["nome"]) && !empty($data["codigo_banco"])) {
                $catalogoItem = FinanceiroBancosCatalogo::porCodigo(
                    $data["codigo_banco"],
                );
                if (!empty($catalogoItem)) {
                    $data["nome"] = $catalogoItem["nome"];
                    $data["numero_banco"] =
                        $data["numero_banco"] ?: $catalogoItem["codigo"];
                    $data["cnab"] = $data["cnab"] ?: $catalogoItem["cnab"];
                }
            }

            $banco = $this->FinanceiroBancos->patchEntity($banco, $data);
            if ($this->FinanceiroBancos->save($banco)) {
                if (!empty($this->Atividades) && $this->Auth->user("id")) {
                    $this->Atividades->registrar(
                        (int) $this->Auth->user("id"),
                        $this->request->getParam("controller"),
                        $this->request->getParam("action"),
                        (int) $banco->id,
                    );
                }

                $this->Flash->success(__("Banco atualizado com sucesso."));
                return $this->redirect(["action" => "cadastrar"]);
            }

            $this->Flash->error(__("Não foi possível atualizar o banco."));
        }

        $catalogo = FinanceiroBancosCatalogo::todos();
        $this->set(compact("banco", "catalogo"));
        $this->set("title", "Editar Banco");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Exclusão de banco.
     *
     * @param int|null $id
     * @return \Cake\Http\Response
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(["post", "delete"]);
        $idempresa = (int) $this->Auth->user("idempresa");

        $banco = $this->FinanceiroBancos
            ->find()
            ->where([
                "FinanceiroBancos.id" => $id,
                "FinanceiroBancos.idempresa" => $idempresa,
            ])
            ->first();

        if (empty($banco)) {
            throw new NotFoundException(__("Banco não encontrado."));
        }

        $vinculado = false;
        if ($this->financeiroLancamentosDisponivel) {
            try {
                $vinculado =
                    $this->FinanceiroLancamentos
                        ->find()
                        ->where([
                            "FinanceiroLancamentos.idempresa" => $idempresa,
                            "FinanceiroLancamentos.financeiro_banco_id" => (int) $id,
                        ])
                        ->count() > 0;
            } catch (\Exception $e) {
                $vinculado = false;
            }
        }

        if (!$vinculado && $this->financeiroExtratoDisponivel) {
            try {
                $contaReferencia = $this->_formatarContaBanco($banco);
                $contaVariacoes = $this->_contasReferenciaExtrato($banco);
                if ($contaReferencia !== "" || !empty($contaVariacoes)) {
                    $rowsExtrato = $this->FinanceiroExtratoBancario
                        ->find()
                        ->where([
                            "FinanceiroExtratoBancario.idempresa" => $idempresa,
                        ])
                        ->select(["conta_bancaria"])
                        ->toArray();

                    foreach ($rowsExtrato as $rowExtrato) {
                        if (
                            $this->_contaExtratoCombinaBanco(
                                (string) ($rowExtrato->conta_bancaria ?? ""),
                                $contaVariacoes,
                            )
                        ) {
                            $vinculado = true;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                $vinculado = $vinculado || false;
            }
        }

        if ($vinculado) {
            $this->Flash->error(
                __(
                    "Este banco está vinculado a lançamentos financeiros e não pode ser excluído.",
                ),
            );
            return $this->redirect(["action" => "cadastrar"]);
        }

        if ($this->FinanceiroBancos->delete($banco)) {
            if (!empty($this->Atividades) && $this->Auth->user("id")) {
                $this->Atividades->registrar(
                    (int) $this->Auth->user("id"),
                    $this->request->getParam("controller"),
                    $this->request->getParam("action"),
                    (int) $id,
                );
            }
            $this->Flash->success(__("Banco excluído com sucesso."));
        } else {
            $this->Flash->error(__("Não foi possível excluir o banco."));
        }

        return $this->redirect(["action" => "cadastrar"]);
    }

    /**
     * Busca rápida de dados do catálogo por código bancário.
     * Responde JSON para autofill do formulário.
     *
     * @return \Cake\Http\Response
     */
    public function buscarCatalogo()
    {
        $this->request->allowMethod(["get"]);
        $codigo = trim((string) $this->request->getQuery("codigo"));
        $item = FinanceiroBancosCatalogo::porCodigo($codigo);

        if (empty($item)) {
            return $this->jsonResponse(
                [
                    "ok" => false,
                    "msg" => "Banco não encontrado no catálogo.",
                ],
                404,
            );
        }

        return $this->jsonResponse([
            "ok" => true,
            "item" => $item,
        ]);
    }

    /**
     * Cria rapidamente um banco da empresa a partir do catálogo.
     *
     * @return \Cake\Http\Response
     */
    public function bootstrapBancoPorCodigo()
    {
        $this->request->allowMethod(["post"]);
        $idempresa = (int) $this->Auth->user("idempresa");

        $codigo = trim((string) $this->request->getData("codigo"));
        $item = FinanceiroBancosCatalogo::porCodigo($codigo);

        if (empty($item)) {
            return $this->jsonResponse(
                [
                    "ok" => false,
                    "msg" => "Código bancário não encontrado no catálogo.",
                ],
                404,
            );
        }

        $existente = $this->FinanceiroBancos
            ->find()
            ->where([
                "FinanceiroBancos.idempresa" => $idempresa,
                "FinanceiroBancos.codigo_banco" => $item["codigo"],
            ])
            ->first();

        if (!empty($existente)) {
            return $this->jsonResponse([
                "ok" => true,
                "msg" => "Banco já cadastrado para esta empresa.",
                "id" => (int) $existente->id,
            ]);
        }

        $novo = $this->FinanceiroBancos->newEntity([
            "idempresa" => $idempresa,
            "codigo_banco" => $item["codigo"],
            "numero_banco" => $item["codigo"],
            "cnab" => $item["cnab"],
            "nome" => $item["nome"],
            "ativo" => true,
        ]);

        if (!$this->FinanceiroBancos->save($novo)) {
            return $this->jsonResponse(
                [
                    "ok" => false,
                    "msg" => "Não foi possível criar o banco automaticamente.",
                    "errors" => $novo->getErrors(),
                ],
                422,
            );
        }

        return $this->jsonResponse([
            "ok" => true,
            "msg" => "Banco criado com sucesso.",
            "id" => (int) $novo->id,
        ]);
    }

    /**
     * Tela de criação de remessa bancária.
     */
    public function remessa()
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $bancoId = (int) $this->request->getQuery("banco_id");

        $bancos = $this->FinanceiroBancos
            ->find("list", [
                "keyField" => "id",
                "valueField" => function ($row) {
                    $label =
                        trim((string) $row->codigo_banco) .
                        " — " .
                        trim((string) $row->nome);

                    $conta = $this->_formatarContaBanco($row);
                    if ($conta !== "") {
                        $label .= " (" . $conta . ")";
                    }

                    return $label;
                },
            ])
            ->where([
                "FinanceiroBancos.idempresa" => $idempresa,
                "FinanceiroBancos.ativo" => true,
            ])
            ->order(["FinanceiroBancos.codigo_banco" => "ASC"])
            ->toArray();

        $lancamentos = [];
        $total = 0.0;

        if ($bancoId > 0 && $this->financeiroLancamentosDisponivel) {
            try {
                $lancamentos = $this->FinanceiroLancamentos
                    ->find()
                    ->contain([
                        "Clientes" => [
                            "fields" => ["id", "razaosocial", "tipo", "nome"],
                        ],
                    ])
                    ->where([
                        "FinanceiroLancamentos.idempresa" => $idempresa,
                        "FinanceiroLancamentos.tipo" => "receita",
                        "FinanceiroLancamentos.status" => "aberto",
                        "FinanceiroLancamentos.financeiro_banco_id" => $bancoId,
                    ])
                    ->order(["FinanceiroLancamentos.data_vencimento" => "ASC"])
                    ->toArray();

                foreach ($lancamentos as $l) {
                    $total += (float) $l->valor;
                }
            } catch (\Exception $e) {
                $lancamentos = [];
            }
        }

        $this->set(compact("bancos", "bancoId", "lancamentos", "total"));
        $this->set("title", "Criação de Remessa Bancária");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Tela de criação de remessa bancária multiempresas.
     *
     * Nesta primeira versão, funciona como visão consolidada da empresa atual
     * e deixa claro o espaço para futura expansão multiempresa.
     */
    public function remessaMultiempresas()
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $bancos = $this->FinanceiroBancos
            ->find()
            ->where([
                "FinanceiroBancos.idempresa" => $idempresa,
                "FinanceiroBancos.ativo" => true,
            ])
            ->order(["FinanceiroBancos.codigo_banco" => "ASC"])
            ->toArray();

        $resumo = [];
        foreach ($bancos as $banco) {
            $item = [
                "banco" => $banco,
                "quantidade" => 0,
                "total" => 0.0,
            ];

            if ($this->financeiroLancamentosDisponivel) {
                try {
                    $rows = $this->FinanceiroLancamentos
                        ->find()
                        ->where([
                            "FinanceiroLancamentos.idempresa" => $idempresa,
                            "FinanceiroLancamentos.tipo" => "receita",
                            "FinanceiroLancamentos.status" => "aberto",
                            "FinanceiroLancamentos.financeiro_banco_id" =>
                                (int) $banco->id,
                        ])
                        ->toArray();

                    $item["quantidade"] = count($rows);
                    foreach ($rows as $row) {
                        $item["total"] += (float) $row->valor;
                    }
                } catch (\Exception $e) {
                    // ignora e mantém zerado
                }
            }

            $resumo[] = $item;
        }

        $this->set(compact("resumo"));
        $this->set("title", "Criação de Remessa Bancária Multiempresas");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Retornos bancários.
     */
    public function retorno()
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $bancos = $this->FinanceiroBancos
            ->find()
            ->where(["FinanceiroBancos.idempresa" => $idempresa])
            ->order(["FinanceiroBancos.codigo_banco" => "ASC"])
            ->toArray();

        $resumoRetorno = $this->_resumoRetornosPorBanco($idempresa, $bancos);

        $this->set(compact("bancos", "resumoRetorno"));
        $this->set("title", "Retornos Bancários");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Hub de relatórios bancários.
     */
    public function relatorios()
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $bancos = $this->FinanceiroBancos
            ->find()
            ->where(["FinanceiroBancos.idempresa" => $idempresa])
            ->order(["FinanceiroBancos.codigo_banco" => "ASC"])
            ->toArray();

        $resumoRelatorios = [
            "bancos" => count($bancos),
            "ativos" => 0,
            "incompletos" => 0,
            "com_movimento" => 0,
            "total_receber" => 0.0,
            "total_recebido" => 0.0,
            "total_pagar" => 0.0,
            "total_pago" => 0.0,
        ];

        foreach ($bancos as $banco) {
            if (!empty($banco->ativo)) {
                $resumoRelatorios["ativos"]++;
            }
            if ($this->_bancoContaIncompleta($banco)) {
                $resumoRelatorios["incompletos"]++;
            }
        }

        $resumoMovimentos = $this->_resumoMovimentosPorBanco(
            $idempresa,
            $bancos,
        );
        foreach ($resumoMovimentos as $item) {
            if (
                (float) ($item["receber"] ?? 0) > 0 ||
                (float) ($item["recebido"] ?? 0) > 0 ||
                (float) ($item["pagar"] ?? 0) > 0 ||
                (float) ($item["pago"] ?? 0) > 0
            ) {
                $resumoRelatorios["com_movimento"]++;
            }

            $resumoRelatorios["total_receber"] +=
                (float) ($item["receber"] ?? 0);
            $resumoRelatorios["total_recebido"] +=
                (float) ($item["recebido"] ?? 0);
            $resumoRelatorios["total_pagar"] += (float) ($item["pagar"] ?? 0);
            $resumoRelatorios["total_pago"] += (float) ($item["pago"] ?? 0);
        }

        $this->set(compact("resumoRelatorios"));
        $this->set("title", "Relatórios Bancários");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Relação de bancos cadastrados.
     */
    public function relacaoBancos()
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $bancos = $this->FinanceiroBancos
            ->find()
            ->where(["FinanceiroBancos.idempresa" => $idempresa])
            ->order([
                "FinanceiroBancos.ativo" => "DESC",
                "FinanceiroBancos.codigo_banco" => "ASC",
                "FinanceiroBancos.nome" => "ASC",
            ])
            ->toArray();

        $this->set(compact("bancos"));
        $this->set("title", "Relação de Bancos");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Relação de remessas bancárias.
     *
     * Primeira versão baseada em previsão de remessa por banco.
     */
    public function relacaoRemessas()
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $resumo = [];

        $codigo = trim((string) $this->request->getQuery("codigo"));
        $nome = trim((string) $this->request->getQuery("nome"));
        $situacao = trim((string) $this->request->getQuery("situacao", ""));

        $bancos = $this->FinanceiroBancos
            ->find()
            ->where([
                "FinanceiroBancos.idempresa" => $idempresa,
                "FinanceiroBancos.ativo" => true,
            ])
            ->order(["FinanceiroBancos.codigo_banco" => "ASC"])
            ->toArray();

        foreach ($bancos as $banco) {
            $quantidade = 0;
            $total = 0.0;
            $proximoVencimento = null;
            $ultimoRecebimento = null;
            $contaIncompleta = $this->_bancoContaIncompleta($banco);

            if ($this->financeiroLancamentosDisponivel) {
                try {
                    $rows = $this->FinanceiroLancamentos
                        ->find()
                        ->where([
                            "FinanceiroLancamentos.idempresa" => $idempresa,
                            "FinanceiroLancamentos.tipo" => "receita",
                            "FinanceiroLancamentos.status" => "aberto",
                            "FinanceiroLancamentos.financeiro_banco_id" =>
                                (int) $banco->id,
                        ])
                        ->order([
                            "FinanceiroLancamentos.data_vencimento" => "ASC",
                        ])
                        ->toArray();

                    $quantidade = count($rows);
                    foreach ($rows as $row) {
                        $total += (float) $row->valor;
                        if (
                            $proximoVencimento === null &&
                            !empty($row->data_vencimento)
                        ) {
                            $proximoVencimento = $row->data_vencimento;
                        }
                    }

                    $ultimoRecebido = $this->FinanceiroLancamentos
                        ->find()
                        ->where([
                            "FinanceiroLancamentos.idempresa" => $idempresa,
                            "FinanceiroLancamentos.tipo" => "receita",
                            "FinanceiroLancamentos.status" => "recebido",
                            "FinanceiroLancamentos.financeiro_banco_id" =>
                                (int) $banco->id,
                        ])
                        ->order([
                            "FinanceiroLancamentos.data_recebimento" => "DESC",
                            "FinanceiroLancamentos.id" => "DESC",
                        ])
                        ->first();

                    if (!empty($ultimoRecebido->data_recebimento)) {
                        $ultimoRecebimento = $ultimoRecebido->data_recebimento;
                    }
                } catch (\Exception $e) {
                    // ignora
                }
            }

            $resumo[] = [
                "banco" => $banco,
                "quantidade" => $quantidade,
                "total" => $total,
                "proximo_vencimento" => $proximoVencimento,
                "ultimo_recebimento" => $ultimoRecebimento,
                "conta_incompleta" => $contaIncompleta,
            ];
        }

        if ($codigo !== "") {
            $resumo = array_values(
                array_filter($resumo, function ($item) use ($codigo) {
                    $banco = $item["banco"] ?? null;
                    if (empty($banco)) {
                        return false;
                    }

                    return trim((string) ($banco->codigo_banco ?? "")) ===
                        $codigo ||
                        trim((string) ($banco->numero_banco ?? "")) ===
                            $codigo ||
                        trim((string) ($banco->cnab ?? "")) === $codigo;
                }),
            );
        }

        if ($nome !== "") {
            $nomeLower = mb_strtolower($nome);
            $resumo = array_values(
                array_filter($resumo, function ($item) use ($nomeLower) {
                    $banco = $item["banco"] ?? null;
                    $nomeBanco = mb_strtolower(
                        trim((string) ($banco->nome ?? "")),
                    );

                    return $nomeBanco !== "" &&
                        mb_strpos($nomeBanco, $nomeLower) !== false;
                }),
            );
        }

        if ($situacao !== "") {
            $resumo = array_values(
                array_filter($resumo, function ($item) use ($situacao) {
                    $quantidade = (int) ($item["quantidade"] ?? 0);
                    $contaIncompleta = !empty($item["conta_incompleta"]);

                    if ($situacao === "com_remessa") {
                        return $quantidade > 0;
                    }

                    if ($situacao === "sem_remessa") {
                        return $quantidade <= 0;
                    }

                    if ($situacao === "conta_incompleta") {
                        return $contaIncompleta;
                    }

                    return true;
                }),
            );
        }

        $metricasRemessas = [
            "bancos" => count($resumo),
            "com_remessa" => 0,
            "sem_remessa" => 0,
            "conta_incompleta" => 0,
            "qtd_titulos" => 0,
            "valor_total" => 0.0,
        ];

        foreach ($resumo as $item) {
            $quantidade = (int) ($item["quantidade"] ?? 0);
            $total = (float) ($item["total"] ?? 0);

            if ($quantidade > 0) {
                $metricasRemessas["com_remessa"]++;
            } else {
                $metricasRemessas["sem_remessa"]++;
            }

            if (!empty($item["conta_incompleta"])) {
                $metricasRemessas["conta_incompleta"]++;
            }

            $metricasRemessas["qtd_titulos"] += $quantidade;
            $metricasRemessas["valor_total"] += $total;
        }

        usort($resumo, function ($a, $b) {
            $aQtd = (int) ($a["quantidade"] ?? 0);
            $bQtd = (int) ($b["quantidade"] ?? 0);

            if ($aQtd !== $bQtd) {
                return $bQtd <=> $aQtd;
            }

            $aTotal = (float) ($a["total"] ?? 0);
            $bTotal = (float) ($b["total"] ?? 0);

            if ($aTotal !== $bTotal) {
                return $bTotal <=> $aTotal;
            }

            $aNome = (string) ($a["banco"]->nome ?? "");
            $bNome = (string) ($b["banco"]->nome ?? "");

            return strcmp($aNome, $bNome);
        });

        $this->set(
            compact("resumo", "codigo", "nome", "situacao", "metricasRemessas"),
        );
        $this->set("title", "Relação de Remessas Bancárias");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Histórico de retorno bancário.
     *
     * Como ainda não há tabela de retornos, esta ação entrega uma visão inicial
     * para evolução futura do módulo.
     */
    public function historicoRetorno()
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $bancos = $this->FinanceiroBancos
            ->find()
            ->where(["FinanceiroBancos.idempresa" => $idempresa])
            ->order(["FinanceiroBancos.codigo_banco" => "ASC"])
            ->toArray();

        $historico = [];
        $resumoRetorno = $this->_resumoRetornosPorBanco($idempresa, $bancos);
        $metricasHistorico = [
            "bancos" => count($bancos),
            "com_extrato" => 0,
            "sem_extrato" => 0,
            "com_pendencia" => 0,
            "conciliados" => 0,
            "pendentes" => 0,
            "total_eventos" => 0,
            "ultimo_evento_geral" => null,
        ];

        foreach ($bancos as $banco) {
            $resumoBanco = $resumoRetorno[(int) $banco->id] ?? [
                "quantidade" => 0,
                "conciliados" => 0,
                "pendentes" => 0,
                "ultimo_evento" => null,
            ];

            $quantidade = (int) ($resumoBanco["quantidade"] ?? 0);
            $conciliados = (int) ($resumoBanco["conciliados"] ?? 0);
            $pendentes = (int) ($resumoBanco["pendentes"] ?? 0);
            $ultimoEvento = $resumoBanco["ultimo_evento"] ?? null;
            $contaIncompleta = $this->_bancoContaIncompleta($banco);

            $status = "Em implantação";
            if ($quantidade > 0) {
                $status = $pendentes > 0 ? "Pendente" : "Sucesso";
            } elseif ($contaIncompleta) {
                $status = "Conta incompleta";
            }

            $descricao =
                $quantidade > 0
                    ? sprintf(
                        "%d lançamento(s) de extrato vinculados a esta conta bancária, sendo %d conciliado(s) e %d pendente(s).",
                        $quantidade,
                        $conciliados,
                        $pendentes,
                    )
                    : "Nenhum extrato importado ainda para esta conta bancária.";

            if ($contaIncompleta) {
                $descricao .=
                    " Cadastro bancário incompleto para conciliação automática.";
            }

            if (!empty($ultimoEvento)) {
                $descricao .=
                    " Último movimento em " .
                    $ultimoEvento->format("d/m/Y") .
                    ".";

                if (
                    $metricasHistorico["ultimo_evento_geral"] === null ||
                    $ultimoEvento > $metricasHistorico["ultimo_evento_geral"]
                ) {
                    $metricasHistorico["ultimo_evento_geral"] = $ultimoEvento;
                }
            }

            if ($quantidade > 0) {
                $metricasHistorico["com_extrato"]++;
            } else {
                $metricasHistorico["sem_extrato"]++;
            }

            if ($pendentes > 0) {
                $metricasHistorico["com_pendencia"]++;
            }

            $metricasHistorico["conciliados"] += $conciliados;
            $metricasHistorico["pendentes"] += $pendentes;
            $metricasHistorico["total_eventos"] += $quantidade;

            $historico[] = [
                "banco" => $banco,
                "status" => $status,
                "descricao" => $descricao,
                "quantidade" => $quantidade,
                "conciliados" => $conciliados,
                "pendentes" => $pendentes,
                "ultimo_evento" => $ultimoEvento,
                "conta_incompleta" => $contaIncompleta,
            ];
        }

        $this->set(compact("historico", "metricasHistorico"));
        $this->set("title", "Histórico de Retorno Bancário");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Previsão de recebimentos por banco.
     */
    public function previsaoRecebimentosPorBanco()
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $previsao = [];

        $codigo = trim((string) $this->request->getQuery("codigo"));
        $nome = trim((string) $this->request->getQuery("nome"));
        $situacao = trim((string) $this->request->getQuery("situacao", ""));

        $bancos = $this->FinanceiroBancos
            ->find()
            ->where([
                "FinanceiroBancos.idempresa" => $idempresa,
                "FinanceiroBancos.ativo" => true,
            ])
            ->order(["FinanceiroBancos.codigo_banco" => "ASC"])
            ->toArray();

        foreach ($bancos as $banco) {
            $total = 0.0;
            $qtd = 0;
            $proximoVencimento = null;
            $diasParaProximoVencimento = null;
            $contaIncompleta = $this->_bancoContaIncompleta($banco);

            if ($this->financeiroLancamentosDisponivel) {
                try {
                    $rows = $this->FinanceiroLancamentos
                        ->find()
                        ->where([
                            "FinanceiroLancamentos.idempresa" => $idempresa,
                            "FinanceiroLancamentos.tipo" => "receita",
                            "FinanceiroLancamentos.status" => "aberto",
                            "FinanceiroLancamentos.financeiro_banco_id" =>
                                (int) $banco->id,
                        ])
                        ->order([
                            "FinanceiroLancamentos.data_vencimento" => "ASC",
                        ])
                        ->toArray();

                    $qtd = count($rows);
                    foreach ($rows as $row) {
                        $total += (float) $row->valor;
                        if (
                            $proximoVencimento === null &&
                            !empty($row->data_vencimento)
                        ) {
                            $proximoVencimento = $row->data_vencimento;
                        }
                    }

                    if (!empty($proximoVencimento)) {
                        $diasParaProximoVencimento = (int) floor(
                            (strtotime($proximoVencimento->format("Y-m-d")) -
                                strtotime(date("Y-m-d"))) /
                                86400,
                        );
                    }
                } catch (\Exception $e) {
                    // ignora
                }
            }

            $previsao[] = [
                "banco" => $banco,
                "qtd" => $qtd,
                "total" => $total,
                "proximo_vencimento" => $proximoVencimento,
                "dias_para_proximo_vencimento" => $diasParaProximoVencimento,
                "conta_incompleta" => $contaIncompleta,
            ];
        }

        if ($codigo !== "") {
            $previsao = array_values(
                array_filter($previsao, function ($item) use ($codigo) {
                    $banco = $item["banco"] ?? null;
                    if (empty($banco)) {
                        return false;
                    }

                    return trim((string) ($banco->codigo_banco ?? "")) ===
                        $codigo ||
                        trim((string) ($banco->numero_banco ?? "")) ===
                            $codigo ||
                        trim((string) ($banco->cnab ?? "")) === $codigo;
                }),
            );
        }

        if ($nome !== "") {
            $nomeLower = mb_strtolower($nome);
            $previsao = array_values(
                array_filter($previsao, function ($item) use ($nomeLower) {
                    $banco = $item["banco"] ?? null;
                    $nomeBanco = mb_strtolower(
                        trim((string) ($banco->nome ?? "")),
                    );

                    return $nomeBanco !== "" &&
                        mb_strpos($nomeBanco, $nomeLower) !== false;
                }),
            );
        }

        if ($situacao !== "") {
            $previsao = array_values(
                array_filter($previsao, function ($item) use ($situacao) {
                    $qtd = (int) ($item["qtd"] ?? 0);
                    $contaIncompleta = !empty($item["conta_incompleta"]);
                    $dias = $item["dias_para_proximo_vencimento"] ?? null;

                    if ($situacao === "com_previsao") {
                        return $qtd > 0;
                    }

                    if ($situacao === "sem_previsao") {
                        return $qtd <= 0;
                    }

                    if ($situacao === "conta_incompleta") {
                        return $contaIncompleta;
                    }

                    if ($situacao === "vence_hoje") {
                        return $qtd > 0 && $dias !== null && (int) $dias === 0;
                    }

                    if ($situacao === "vence_semana") {
                        return $qtd > 0 &&
                            $dias !== null &&
                            (int) $dias >= 0 &&
                            (int) $dias <= 7;
                    }

                    if ($situacao === "vencido") {
                        return $qtd > 0 && $dias !== null && (int) $dias < 0;
                    }

                    return true;
                }),
            );
        }

        $metricasPrevisao = [
            "bancos" => count($previsao),
            "com_previsao" => 0,
            "sem_previsao" => 0,
            "conta_incompleta" => 0,
            "qtd_titulos" => 0,
            "valor_total" => 0.0,
            "vence_hoje" => 0,
            "vence_semana" => 0,
            "vencidos" => 0,
        ];

        foreach ($previsao as $item) {
            $qtd = (int) ($item["qtd"] ?? 0);
            $total = (float) ($item["total"] ?? 0);
            $dias = $item["dias_para_proximo_vencimento"] ?? null;

            if ($qtd > 0) {
                $metricasPrevisao["com_previsao"]++;
            } else {
                $metricasPrevisao["sem_previsao"]++;
            }

            if (!empty($item["conta_incompleta"])) {
                $metricasPrevisao["conta_incompleta"]++;
            }

            if ($qtd > 0 && $dias !== null && (int) $dias === 0) {
                $metricasPrevisao["vence_hoje"]++;
            }

            if (
                $qtd > 0 &&
                $dias !== null &&
                (int) $dias >= 0 &&
                (int) $dias <= 7
            ) {
                $metricasPrevisao["vence_semana"]++;
            }

            if ($qtd > 0 && $dias !== null && (int) $dias < 0) {
                $metricasPrevisao["vencidos"]++;
            }

            $metricasPrevisao["qtd_titulos"] += $qtd;
            $metricasPrevisao["valor_total"] += $total;
        }

        usort($previsao, function ($a, $b) {
            $aQtd = (int) ($a["qtd"] ?? 0);
            $bQtd = (int) ($b["qtd"] ?? 0);

            $aDias = $a["dias_para_proximo_vencimento"];
            $bDias = $b["dias_para_proximo_vencimento"];

            $aPrioridade = 4;
            $bPrioridade = 4;

            if ($aQtd > 0) {
                if ($aDias !== null && (int) $aDias < 0) {
                    $aPrioridade = 1;
                } elseif ($aDias !== null && (int) $aDias <= 7) {
                    $aPrioridade = 2;
                } else {
                    $aPrioridade = 3;
                }
            }

            if ($bQtd > 0) {
                if ($bDias !== null && (int) $bDias < 0) {
                    $bPrioridade = 1;
                } elseif ($bDias !== null && (int) $bDias <= 7) {
                    $bPrioridade = 2;
                } else {
                    $bPrioridade = 3;
                }
            }

            if ($aPrioridade !== $bPrioridade) {
                return $aPrioridade <=> $bPrioridade;
            }

            if ($aQtd !== $bQtd) {
                return $bQtd <=> $aQtd;
            }

            $aTotal = (float) ($a["total"] ?? 0);
            $bTotal = (float) ($b["total"] ?? 0);

            if ($aTotal !== $bTotal) {
                return $bTotal <=> $aTotal;
            }

            $aNome = (string) ($a["banco"]->nome ?? "");
            $bNome = (string) ($b["banco"]->nome ?? "");

            return strcmp($aNome, $bNome);
        });

        $this->set(
            compact(
                "previsao",
                "codigo",
                "nome",
                "situacao",
                "metricasPrevisao",
            ),
        );
        $this->set("title", "Previsão de Recebimentos por Banco");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Visão consolidada por bancos.
     */
    public function previsaoPorBancos()
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $codigo = trim((string) $this->request->getQuery("codigo"));
        $nome = trim((string) $this->request->getQuery("nome"));
        $situacao = trim((string) $this->request->getQuery("situacao", ""));

        $bancos = $this->FinanceiroBancos
            ->find()
            ->where(["FinanceiroBancos.idempresa" => $idempresa])
            ->order(["FinanceiroBancos.codigo_banco" => "ASC"])
            ->toArray();

        $resumo = $this->_resumoMovimentosPorBanco($idempresa, $bancos);

        if ($codigo !== "") {
            $resumo = array_values(
                array_filter($resumo, function ($item) use ($codigo) {
                    $banco = $item["banco"] ?? null;
                    if (empty($banco)) {
                        return false;
                    }

                    return trim((string) ($banco->codigo_banco ?? "")) ===
                        $codigo ||
                        trim((string) ($banco->numero_banco ?? "")) ===
                            $codigo ||
                        trim((string) ($banco->cnab ?? "")) === $codigo;
                }),
            );
        }

        if ($nome !== "") {
            $nomeLower = mb_strtolower($nome);
            $resumo = array_values(
                array_filter($resumo, function ($item) use ($nomeLower) {
                    $banco = $item["banco"] ?? null;
                    $nomeBanco = mb_strtolower(
                        trim((string) ($banco->nome ?? "")),
                    );

                    return $nomeBanco !== "" &&
                        mb_strpos($nomeBanco, $nomeLower) !== false;
                }),
            );
        }

        if ($situacao !== "") {
            $resumo = array_values(
                array_filter($resumo, function ($item) use ($situacao) {
                    $receber = (float) ($item["receber"] ?? 0);
                    $recebido = (float) ($item["recebido"] ?? 0);
                    $pagar = (float) ($item["pagar"] ?? 0);
                    $pago = (float) ($item["pago"] ?? 0);
                    $saldo = $receber + $recebido - ($pagar + $pago);
                    $contaIncompleta = !empty($item["conta_incompleta"]);

                    if ($situacao === "saldo_positivo") {
                        return $saldo >= 0;
                    }

                    if ($situacao === "saldo_negativo") {
                        return $saldo < 0;
                    }

                    if ($situacao === "conta_incompleta") {
                        return $contaIncompleta;
                    }

                    if ($situacao === "com_movimento") {
                        return $receber > 0 ||
                            $recebido > 0 ||
                            $pagar > 0 ||
                            $pago > 0;
                    }

                    if ($situacao === "sem_movimento") {
                        return $receber <= 0 &&
                            $recebido <= 0 &&
                            $pagar <= 0 &&
                            $pago <= 0;
                    }

                    return true;
                }),
            );
        }

        $metricasResumo = [
            "bancos" => count($resumo),
            "saldo_positivo" => 0,
            "saldo_negativo" => 0,
            "conta_incompleta" => 0,
            "com_movimento" => 0,
            "sem_movimento" => 0,
            "total_receber" => 0.0,
            "total_recebido" => 0.0,
            "total_pagar" => 0.0,
            "total_pago" => 0.0,
            "saldo_total" => 0.0,
        ];

        foreach ($resumo as $item) {
            $receber = (float) ($item["receber"] ?? 0);
            $recebido = (float) ($item["recebido"] ?? 0);
            $pagar = (float) ($item["pagar"] ?? 0);
            $pago = (float) ($item["pago"] ?? 0);
            $saldo = $receber + $recebido - ($pagar + $pago);

            if ($saldo >= 0) {
                $metricasResumo["saldo_positivo"]++;
            } else {
                $metricasResumo["saldo_negativo"]++;
            }

            if (!empty($item["conta_incompleta"])) {
                $metricasResumo["conta_incompleta"]++;
            }

            if ($receber > 0 || $recebido > 0 || $pagar > 0 || $pago > 0) {
                $metricasResumo["com_movimento"]++;
            } else {
                $metricasResumo["sem_movimento"]++;
            }

            $metricasResumo["total_receber"] += $receber;
            $metricasResumo["total_recebido"] += $recebido;
            $metricasResumo["total_pagar"] += $pagar;
            $metricasResumo["total_pago"] += $pago;
            $metricasResumo["saldo_total"] += $saldo;
        }

        usort($resumo, function ($a, $b) {
            $aReceber = (float) ($a["receber"] ?? 0);
            $aRecebido = (float) ($a["recebido"] ?? 0);
            $aPagar = (float) ($a["pagar"] ?? 0);
            $aPago = (float) ($a["pago"] ?? 0);
            $aSaldo = $aReceber + $aRecebido - ($aPagar + $aPago);

            $bReceber = (float) ($b["receber"] ?? 0);
            $bRecebido = (float) ($b["recebido"] ?? 0);
            $bPagar = (float) ($b["pagar"] ?? 0);
            $bPago = (float) ($b["pago"] ?? 0);
            $bSaldo = $bReceber + $bRecebido - ($bPagar + $bPago);

            $aMovimento = $aReceber + $aRecebido + $aPagar + $aPago;
            $bMovimento = $bReceber + $bRecebido + $bPagar + $bPago;

            if ($aSaldo < 0 !== $bSaldo < 0) {
                return $aSaldo < 0 ? -1 : 1;
            }

            if ($aMovimento !== $bMovimento) {
                return $bMovimento <=> $aMovimento;
            }

            if ($aSaldo !== $bSaldo) {
                return $aSaldo <=> $bSaldo;
            }

            $aNome = (string) ($a["banco"]->nome ?? "");
            $bNome = (string) ($b["banco"]->nome ?? "");

            return strcmp($aNome, $bNome);
        });

        $this->set(
            compact("resumo", "codigo", "nome", "situacao", "metricasResumo"),
        );
        $this->set("title", "Previsão por Bancos");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Retorna se o banco está com agência/conta incompletas.
     *
     * @param object $banco
     * @return bool
     */
    protected function _bancoContaIncompleta($banco)
    {
        return empty($banco->numero_agencia) || empty($banco->numero_conta);
    }

    /**
     * Resume movimentos financeiros por banco.
     *
     * @param int $idempresa
     * @param array $bancos
     * @return array<int,array<string,mixed>>
     */
    protected function _resumoMovimentosPorBanco($idempresa, array $bancos)
    {
        $resumo = [];

        foreach ($bancos as $banco) {
            $item = [
                "banco" => $banco,
                "receber" => 0.0,
                "recebido" => 0.0,
                "pagar" => 0.0,
                "pago" => 0.0,
                "conta_incompleta" => $this->_bancoContaIncompleta($banco),
                "ultimo_movimento" => null,
            ];

            if ($this->financeiroLancamentosDisponivel) {
                try {
                    $rows = $this->FinanceiroLancamentos
                        ->find()
                        ->where([
                            "FinanceiroLancamentos.idempresa" => (int) $idempresa,
                            "FinanceiroLancamentos.financeiro_banco_id" =>
                                (int) $banco->id,
                        ])
                        ->order([
                            "FinanceiroLancamentos.data_recebimento" => "DESC",
                            "FinanceiroLancamentos.data_vencimento" => "DESC",
                            "FinanceiroLancamentos.id" => "DESC",
                        ])
                        ->toArray();

                    foreach ($rows as $row) {
                        $valor = (float) $row->valor;

                        if (
                            $item["ultimo_movimento"] === null &&
                            !empty($row->data_recebimento)
                        ) {
                            $item["ultimo_movimento"] = $row->data_recebimento;
                        }

                        if (
                            $row->tipo === "receita" &&
                            $row->status === "aberto"
                        ) {
                            $item["receber"] += $valor;
                        } elseif (
                            $row->tipo === "receita" &&
                            $row->status === "recebido"
                        ) {
                            $item["recebido"] += $valor;
                        } elseif (
                            $row->tipo === "despesa" &&
                            $row->status === "aberto"
                        ) {
                            $item["pagar"] += $valor;
                        } elseif (
                            $row->tipo === "despesa" &&
                            $row->status === "pago"
                        ) {
                            $item["pago"] += $valor;
                        }
                    }
                } catch (\Exception $e) {
                    // ignora e mantém zerado
                }
            }

            $resumo[] = $item;
        }

        return $resumo;
    }

    /**
     * Normaliza payload do formulário.
     *
     * @param array $data
     * @return array
     */
    protected function _normalizarDadosBanco(array $data)
    {
        $data["codigo_banco"] = trim(
            (string) Hash::get($data, "codigo_banco", ""),
        );
        $data["numero_banco"] = trim(
            (string) Hash::get($data, "numero_banco", ""),
        );
        $data["cnab"] = trim((string) Hash::get($data, "cnab", ""));
        $data["nome"] = trim((string) Hash::get($data, "nome", ""));
        $data["numero_agencia"] = trim(
            (string) Hash::get($data, "numero_agencia", ""),
        );
        $data["digito_agencia"] = trim(
            (string) Hash::get($data, "digito_agencia", ""),
        );
        $data["numero_conta"] = trim(
            (string) Hash::get($data, "numero_conta", ""),
        );
        $data["digito_conta"] = trim(
            (string) Hash::get($data, "digito_conta", ""),
        );
        $data["codigo_banco_interno"] = trim(
            (string) Hash::get($data, "codigo_banco_interno", ""),
        );
        $data["verifica_receber"] = trim(
            (string) Hash::get($data, "verifica_receber", ""),
        );
        $data["utiliza_endosso"] = trim(
            (string) Hash::get($data, "utiliza_endosso", ""),
        );
        $data["logotipo"] = trim((string) Hash::get($data, "logotipo", ""));
        $data["observacoes"] = trim(
            (string) Hash::get($data, "observacoes", ""),
        );
        $data["ativo"] = (bool) Hash::get($data, "ativo", true);

        if ($data["numero_banco"] === "" && $data["codigo_banco"] !== "") {
            $data["numero_banco"] = $data["codigo_banco"];
        }

        if ($data["cnab"] === "" && $data["codigo_banco"] !== "") {
            $catalogo = FinanceiroBancosCatalogo::porCodigo(
                $data["codigo_banco"],
            );
            if (!empty($catalogo["cnab"])) {
                $data["cnab"] = (string) $catalogo["cnab"];
            }
        }

        return $data;
    }

    /**
     * Resumo do financeiro agrupado por vínculo bancário.
     *
     * @param int $idempresa
     * @return array<string,float>
     */
    protected function _resumoLancamentosPorBanco($idempresa)
    {
        $resumo = [
            "receber" => 0.0,
            "pagar" => 0.0,
        ];

        if (!$this->financeiroLancamentosDisponivel) {
            return $resumo;
        }

        $rows = $this->FinanceiroLancamentos
            ->find()
            ->where([
                "FinanceiroLancamentos.idempresa" => (int) $idempresa,
            ])
            ->toArray();

        foreach ($rows as $row) {
            if (empty($row->financeiro_banco_id)) {
                continue;
            }

            if ($row->tipo === "receita" && $row->status === "aberto") {
                $resumo["receber"] += (float) $row->valor;
            }
            if ($row->tipo === "despesa" && $row->status === "aberto") {
                $resumo["pagar"] += (float) $row->valor;
            }
        }

        return $resumo;
    }

    /**
     * Formata agência/conta para cruzamento com extrato bancário.
     *
     * @param object $banco
     * @return string
     */
    protected function _formatarContaBanco($banco)
    {
        $agencia = trim((string) ($banco->numero_agencia ?? ""));
        $digitoAgencia = trim((string) ($banco->digito_agencia ?? ""));
        $conta = trim((string) ($banco->numero_conta ?? ""));
        $digitoConta = trim((string) ($banco->digito_conta ?? ""));

        if ($agencia === "" && $conta === "") {
            return "";
        }

        $agenciaFmt = $agencia;
        if ($agenciaFmt !== "" && $digitoAgencia !== "") {
            $agenciaFmt .= "-" . $digitoAgencia;
        }

        $contaFmt = $conta;
        if ($contaFmt !== "" && $digitoConta !== "") {
            $contaFmt .= "-" . $digitoConta;
        }

        if ($agenciaFmt !== "" && $contaFmt !== "") {
            return $agenciaFmt . " / " . $contaFmt;
        }

        return $agenciaFmt !== "" ? $agenciaFmt : $contaFmt;
    }

    /**
     * Resume extratos importados por banco com base na conta bancária cadastrada.
     *
     * @param int $idempresa
     * @param array $bancos
     * @return array<int,array<string,mixed>>
     */
    protected function _resumoRetornosPorBanco($idempresa, array $bancos)
    {
        $resumo = [];

        foreach ($bancos as $banco) {
            $resumo[(int) $banco->id] = [
                "quantidade" => 0,
                "conciliados" => 0,
                "pendentes" => 0,
                "ultimo_evento" => null,
            ];
        }

        if (!$this->financeiroExtratoDisponivel || empty($bancos)) {
            return $resumo;
        }

        try {
            $rowsExtrato = $this->FinanceiroExtratoBancario
                ->find()
                ->where([
                    "FinanceiroExtratoBancario.idempresa" => (int) $idempresa,
                ])
                ->order(["FinanceiroExtratoBancario.data" => "DESC"])
                ->toArray();
        } catch (\Exception $e) {
            return $resumo;
        }

        if (empty($rowsExtrato)) {
            return $resumo;
        }

        foreach ($bancos as $banco) {
            $contaVariacoes = $this->_contasReferenciaExtrato($banco);
            if (empty($contaVariacoes)) {
                continue;
            }

            foreach ($rowsExtrato as $row) {
                if (
                    !$this->_contaExtratoCombinaBanco(
                        (string) ($row->conta_bancaria ?? ""),
                        $contaVariacoes,
                    )
                ) {
                    continue;
                }

                $resumo[(int) $banco->id]["quantidade"]++;

                if (
                    $resumo[(int) $banco->id]["ultimo_evento"] === null &&
                    !empty($row->data)
                ) {
                    $resumo[(int) $banco->id]["ultimo_evento"] = $row->data;
                }

                if (!empty($row->conciliado)) {
                    $resumo[(int) $banco->id]["conciliados"]++;
                } else {
                    $resumo[(int) $banco->id]["pendentes"]++;
                }
            }
        }

        return $resumo;
    }

    /**
     * Gera variações da conta cadastrada para cruzamento com ACCTID bruto de OFX
     * e outros formatos resumidos gravados no extrato.
     *
     * @param object $banco
     * @return array<int,string>
     */
    protected function _contasReferenciaExtrato($banco)
    {
        $agencia = trim((string) ($banco->numero_agencia ?? ""));
        $digitoAgencia = trim((string) ($banco->digito_agencia ?? ""));
        $conta = trim((string) ($banco->numero_conta ?? ""));
        $digitoConta = trim((string) ($banco->digito_conta ?? ""));

        $variacoes = [];
        $formatada = $this->_formatarContaBanco($banco);
        if ($formatada !== "") {
            $variacoes[] = $formatada;
        }

        $agenciaFmt = $agencia;
        if ($agenciaFmt !== "" && $digitoAgencia !== "") {
            $agenciaFmt .= "-" . $digitoAgencia;
        }

        $contaFmt = $conta;
        if ($contaFmt !== "" && $digitoConta !== "") {
            $contaFmt .= "-" . $digitoConta;
        }

        if ($contaFmt !== "") {
            $variacoes[] = $contaFmt;
        }
        if ($conta !== "") {
            $variacoes[] = $conta;
        }

        if ($agenciaFmt !== "" && $contaFmt !== "") {
            $variacoes[] = $agenciaFmt . "/" . $contaFmt;
            $variacoes[] = $agenciaFmt . " / " . $contaFmt;
        }

        if ($agencia !== "" && $conta !== "") {
            $variacoes[] = $agencia . "/" . $conta;
            $variacoes[] = $agencia . " / " . $conta;
            $variacoes[] = $agencia . $conta;
        }

        if ($conta !== "" && $digitoConta !== "") {
            $variacoes[] = $conta . $digitoConta;
        }

        $variacoes = array_values(
            array_unique(
                array_filter($variacoes, function ($item) {
                    return trim((string) $item) !== "";
                }),
            ),
        );

        return $variacoes;
    }

    /**
     * Compara conta do extrato com conta cadastrada aceitando formatos brutos
     * comuns de OFX, inclusive apenas número da conta ou sequência numérica.
     *
     * @param string $contaExtrato
     * @param array<int,string> $contasReferencia
     * @return bool
     */
    protected function _contaExtratoCombinaBanco(
        $contaExtrato,
        array $contasReferencia,
    ) {
        $contaExtrato = trim((string) $contaExtrato);
        if ($contaExtrato === "" || empty($contasReferencia)) {
            return false;
        }

        $contaExtratoNormalizada = $this->_normalizarContaComparacao(
            $contaExtrato,
        );

        foreach ($contasReferencia as $referencia) {
            $referencia = trim((string) $referencia);
            if ($referencia === "") {
                continue;
            }

            if (strcasecmp($contaExtrato, $referencia) === 0) {
                return true;
            }

            $referenciaNormalizada = $this->_normalizarContaComparacao(
                $referencia,
            );
            if ($referenciaNormalizada === "") {
                continue;
            }

            if ($contaExtratoNormalizada === $referenciaNormalizada) {
                return true;
            }

            if (
                strlen($referenciaNormalizada) >= 4 &&
                substr(
                    $contaExtratoNormalizada,
                    -strlen($referenciaNormalizada),
                ) === $referenciaNormalizada
            ) {
                return true;
            }

            if (
                strlen($contaExtratoNormalizada) >= 4 &&
                substr(
                    $referenciaNormalizada,
                    -strlen($contaExtratoNormalizada),
                ) === $contaExtratoNormalizada
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliza conta para comparação tolerante.
     *
     * @param string $valor
     * @return string
     */
    protected function _normalizarContaComparacao($valor)
    {
        return preg_replace("/\D+/", "", (string) $valor) ?? "";
    }
}
