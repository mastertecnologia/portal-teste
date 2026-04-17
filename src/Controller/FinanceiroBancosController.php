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

        $catalogo = FinanceiroBancosCatalogo::buscar(
            $codigo !== "" ? $codigo : $nome,
        );

        $this->set(compact("bancos", "catalogo", "codigo", "nome", "ativo"));
        $this->set("title", "Cadastro de Bancos");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Novo banco.
     */
    public function add()
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $banco = $this->FinanceiroBancos->newEntity();

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
        $this->set(compact("banco", "catalogo"));
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

        $this->set(compact("resumo"));
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

        foreach ($bancos as $banco) {
            $resumoBanco = $resumoRetorno[(int) $banco->id] ?? [
                "quantidade" => 0,
                "conciliados" => 0,
                "pendentes" => 0,
                "ultimo_evento" => null,
            ];

            $status = "Em implantação";
            if ((int) $resumoBanco["quantidade"] > 0) {
                $status =
                    (int) $resumoBanco["pendentes"] > 0
                        ? "Pendente"
                        : "Sucesso";
            }

            $descricao =
                (int) $resumoBanco["quantidade"] > 0
                    ? sprintf(
                        "%d lançamento(s) de extrato vinculados a esta conta bancária, sendo %d conciliado(s) e %d pendente(s).",
                        (int) $resumoBanco["quantidade"],
                        (int) $resumoBanco["conciliados"],
                        (int) $resumoBanco["pendentes"],
                    )
                    : "Nenhum extrato importado ainda para esta conta bancária.";

            if (!empty($resumoBanco["ultimo_evento"])) {
                $descricao .=
                    " Último movimento em " .
                    $resumoBanco["ultimo_evento"]->format("d/m/Y") .
                    ".";
            }

            $historico[] = [
                "banco" => $banco,
                "status" => $status,
                "descricao" => $descricao,
            ];
        }

        $this->set(compact("historico"));
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

        $this->set(compact("previsao"));
        $this->set("title", "Previsão de Recebimentos por Banco");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Visão consolidada por bancos.
     */
    public function previsaoPorBancos()
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $bancos = $this->FinanceiroBancos
            ->find()
            ->where(["FinanceiroBancos.idempresa" => $idempresa])
            ->order(["FinanceiroBancos.codigo_banco" => "ASC"])
            ->toArray();

        $resumo = $this->_resumoMovimentosPorBanco($idempresa, $bancos);

        $this->set(compact("resumo"));
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
