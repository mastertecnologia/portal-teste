<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\PortalUi;
use Cake\Event\Event;

$__pgmUserConstants =
    ROOT . DS . "vendor" . DS . "PGMPackages" . DS . "UserConstants.php";
if (is_file($__pgmUserConstants)) {
    require_once $__pgmUserConstants;
}
if (!defined("C_RoleCliente")) {
    define("C_RoleCliente", 1);
}
if (!defined("C_RoleFuncionario")) {
    define("C_RoleFuncionario", 0);
}

/**
 * Módulo Financeiro
 * Dashboard, Contas a Receber, Contas a Pagar e Fluxo de Caixa.
 */
class FinanceiroController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel("FinanceiroLancamentos");
        $this->loadModel("Clientes");
        $this->loadModel("Faturamento");
        $this->loadModel("FinanceiroLancamentoAnexos");
        $this->loadModel("FinanceiroPlanoContas");
        $this->loadModel("FinanceiroCentrosCusto");
        $this->loadModel("FinanceiroRecorrentes");
        $this->loadModel("FinanceiroBancos");
        $this->loadModel("Atividades");
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->set("title", "Financeiro");
    }

    public function isAuthorized($user)
    {
        if ((int) ($user["role"] ?? 1) === C_RoleCliente) {
            return false;
        }
        return parent::isAuthorized($user);
    }

    /* ── Dashboard financeiro ──────────────────────────────────────────── */
    public function index()
    {
        $prototypeLista = PortalUi::redirectToPrototypeIfEnabled('financeiro', 'FinanceiroPrototype', 'lista');
        if ($prototypeLista !== null) {
            return $this->redirect($prototypeLista);
        }

        $idempresa = $this->Auth->user("idempresa");

        $lancamentos = $this->FinanceiroLancamentos
            ->find("all")
            ->where(["FinanceiroLancamentos.idempresa" => $idempresa])
            ->contain([
                "Clientes" => [
                    "fields" => ["id", "razaosocial", "tipo", "nome"],
                ],
            ])
            ->order(["FinanceiroLancamentos.data_vencimento" => "ASC"])
            ->toArray();

        // KPIs
        $kpi = [
            "total_receitas" => 0,
            "total_despesas" => 0,
            "a_receber" => 0,
            "a_pagar" => 0,
            "vencidos" => 0,
            "recebido_mes" => 0,
            "pago_mes" => 0,
        ];
        $hoje = date("Y-m-d");
        $mesAtual = date("Y-m");

        foreach ($lancamentos as $l) {
            if ($l->tipo === "receita") {
                $kpi["total_receitas"] += $l->valor;
                if ($l->status === "aberto") {
                    $kpi["a_receber"] += $l->valor;
                    if (
                        $l->data_vencimento &&
                        $l->data_vencimento->format("Y-m-d") < $hoje
                    ) {
                        $kpi["vencidos"] += $l->valor;
                    }
                }
                if (
                    $l->status === "recebido" &&
                    $l->data_recebimento &&
                    $l->data_recebimento->format("Y-m") === $mesAtual
                ) {
                    $kpi["recebido_mes"] += $l->valor;
                }
            } else {
                $kpi["total_despesas"] += $l->valor;
                if ($l->status === "aberto") {
                    $kpi["a_pagar"] += $l->valor;
                }
                if (
                    $l->status === "pago" &&
                    $l->data_recebimento &&
                    $l->data_recebimento->format("Y-m") === $mesAtual
                ) {
                    $kpi["pago_mes"] += $l->valor;
                }
            }
        }

        // Últimos 6 meses — receitas x despesas por mês
        $grafico = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = date("Y-m", strtotime("-$i months"));
            $grafico[$mes] = ["receita" => 0, "despesa" => 0];
        }
        foreach ($lancamentos as $l) {
            $mes = $l->data_lancamento
                ? $l->data_lancamento->format("Y-m")
                : null;
            if ($mes && isset($grafico[$mes])) {
                $grafico[$mes][
                    $l->tipo === "receita" ? "receita" : "despesa"
                ] += $l->valor;
            }
        }

        // Próximos vencimentos (30 dias)
        $vencimentos = array_filter($lancamentos, function ($l) use ($hoje) {
            if ($l->status !== "aberto" || !$l->data_vencimento) {
                return false;
            }
            $dv = $l->data_vencimento->format("Y-m-d");
            return $dv >= $hoje && $dv <= date("Y-m-d", strtotime("+30 days"));
        });

        $this->set(compact("kpi", "grafico", "vencimentos", "lancamentos"));
        $this->set("hideLayoutPageTitle", true);
    }

    /* ── Fluxo de Caixa ───────────────────────────────────────────────── */
    public function fluxoCaixa()
    {
        $idempresa = $this->Auth->user("idempresa");
        $meses = (int) ($this->request->getQuery("meses") ?: 6);
        if ($meses < 1) {
            $meses = 6;
        }
        if ($meses > 24) {
            $meses = 24;
        }

        $lancamentos = $this->FinanceiroLancamentos
            ->find("all")
            ->where(["FinanceiroLancamentos.idempresa" => $idempresa])
            ->order(["FinanceiroLancamentos.data_vencimento" => "ASC"])
            ->toArray();

        // Build month buckets: past $meses/2 + future $meses/2
        $passado = (int) floor($meses / 2);
        $futuro = $meses - $passado;
        $buckets = [];
        for ($i = -$passado; $i < $futuro; $i++) {
            $m = date("Y-m", strtotime("$i months"));
            $buckets[$m] = [
                "receita_prevista" => 0,
                "despesa_prevista" => 0,
                "receita_realizada" => 0,
                "despesa_realizada" => 0,
            ];
        }

        foreach ($lancamentos as $l) {
            // Previsto: baseado em data_vencimento
            $mesVenc = $l->data_vencimento
                ? $l->data_vencimento->format("Y-m")
                : null;
            if ($mesVenc && isset($buckets[$mesVenc])) {
                if ($l->tipo === "receita") {
                    $buckets[$mesVenc]["receita_prevista"] += (float) $l->valor;
                } else {
                    $buckets[$mesVenc]["despesa_prevista"] += (float) $l->valor;
                }
            }

            // Realizado: baseado em data_recebimento (para recebidos/pagos)
            $mesReal = $l->data_recebimento
                ? $l->data_recebimento->format("Y-m")
                : null;
            if (
                $mesReal &&
                isset($buckets[$mesReal]) &&
                in_array($l->status, ["recebido", "pago"], true)
            ) {
                if ($l->tipo === "receita") {
                    $buckets[$mesReal]["receita_realizada"] +=
                        (float) $l->valor;
                } else {
                    $buckets[$mesReal]["despesa_realizada"] +=
                        (float) $l->valor;
                }
            }
        }

        // Saldo acumulado
        $saldo = 0;
        $saldoAcumulado = [];
        foreach ($buckets as $mes => $b) {
            $saldo += $b["receita_realizada"] - $b["despesa_realizada"];
            $saldoAcumulado[$mes] = $saldo;
        }

        $this->set(compact("buckets", "saldoAcumulado", "meses"));
        $this->set("title", "Fluxo de Caixa");
        $this->set("hideLayoutPageTitle", true);
    }

    /* ── Lançamentos recorrentes ──────────────────────────────────────── */
    public function recorrentes()
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $templates = $this->FinanceiroRecorrentes
            ->find()
            ->where(["FinanceiroRecorrentes.idempresa" => $idempresa])
            ->contain([
                "Clientes" => [
                    "fields" => ["id", "razaosocial", "tipo", "nome"],
                ],
                "FinanceiroPlanoContas" => [
                    "fields" => ["id", "codigo", "descricao"],
                ],
                "FinanceiroCentrosCusto" => [
                    "fields" => ["id", "codigo", "descricao"],
                ],
            ])
            ->order([
                "FinanceiroRecorrentes.ativo" => "DESC",
                "FinanceiroRecorrentes.descricao" => "ASC",
            ])
            ->toArray();

        $this->set(compact("templates"));
        $this->set("title", "Lançamentos Recorrentes");
    }

    public function addRecorrente()
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $recorrente = $this->FinanceiroRecorrentes->newEntity();

        if ($this->request->is("post")) {
            $data = $this->request->getData();
            $data["idempresa"] = $idempresa;
            $recorrente = $this->FinanceiroRecorrentes->patchEntity(
                $recorrente,
                $data,
            );
            if ($this->FinanceiroRecorrentes->save($recorrente)) {
                $this->Flash->success(__("Lançamento recorrente criado."));
                return $this->redirect(["action" => "recorrentes"]);
            }
            $this->Flash->error(__("Não foi possível salvar."));
        }

        $planoContas = $this->FinanceiroPlanoContas->listByEmpresa(
            $idempresa,
            null,
            true,
        );
        $centrosCusto = $this->FinanceiroCentrosCusto->listByEmpresa(
            $idempresa,
        );
        $clientes = $this->Clientes
            ->find("list", [
                "keyField" => "id",
                "valueField" => "razaosocial",
            ])
            ->where(["idempresa" => $idempresa, "inativo" => 0])
            ->order(["razaosocial"])
            ->toArray();

        $this->set(
            compact("recorrente", "planoContas", "centrosCusto", "clientes"),
        );
        $this->set("title", "Novo Recorrente");
    }

    public function editRecorrente($id = null)
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $recorrente = $this->FinanceiroRecorrentes
            ->find()
            ->where(["id" => $id, "idempresa" => $idempresa])
            ->first();

        if (empty($recorrente)) {
            $this->Flash->error(__("Não encontrado."));
            return $this->redirect(["action" => "recorrentes"]);
        }

        if ($this->request->is(["put", "patch", "post"])) {
            $data = $this->request->getData();
            unset($data["idempresa"]);
            $recorrente = $this->FinanceiroRecorrentes->patchEntity(
                $recorrente,
                $data,
            );
            if ($this->FinanceiroRecorrentes->save($recorrente)) {
                $this->Flash->success(__("Recorrente atualizado."));
                return $this->redirect(["action" => "recorrentes"]);
            }
            $this->Flash->error(__("Não foi possível salvar."));
        }

        $planoContas = $this->FinanceiroPlanoContas->listByEmpresa(
            $idempresa,
            null,
            true,
        );
        $centrosCusto = $this->FinanceiroCentrosCusto->listByEmpresa(
            $idempresa,
        );
        $clientes = $this->Clientes
            ->find("list", [
                "keyField" => "id",
                "valueField" => "razaosocial",
            ])
            ->where(["idempresa" => $idempresa, "inativo" => 0])
            ->order(["razaosocial"])
            ->toArray();

        $this->set(
            compact("recorrente", "planoContas", "centrosCusto", "clientes"),
        );
        $this->set("title", "Editar Recorrente");
    }

    public function deleteRecorrente($id = null)
    {
        $this->request->allowMethod(["post"]);
        $idempresa = (int) $this->Auth->user("idempresa");
        $recorrente = $this->FinanceiroRecorrentes
            ->find()
            ->where(["id" => $id, "idempresa" => $idempresa])
            ->first();

        if (!empty($recorrente)) {
            $this->FinanceiroRecorrentes->delete($recorrente);
            $this->Flash->success(__("Recorrente removido."));
        } else {
            $this->Flash->error(__("Não encontrado."));
        }
        return $this->redirect(["action" => "recorrentes"]);
    }

    /* ── Conciliação bancária ─────────────────────────────────────────── */
    public function conciliacao()
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $this->loadModel("FinanceiroExtratoBancario");

        $filtro = $this->request->getQuery("filtro") ?? "pendentes";

        $q = $this->FinanceiroExtratoBancario
            ->find()
            ->where(["FinanceiroExtratoBancario.idempresa" => $idempresa])
            ->contain([
                "FinanceiroLancamentos" => [
                    "fields" => ["id", "descricao", "valor", "status"],
                ],
            ])
            ->order(["FinanceiroExtratoBancario.data" => "DESC"]);

        if ($filtro === "pendentes") {
            $q->where(["FinanceiroExtratoBancario.conciliado" => false]);
        } elseif ($filtro === "conciliados") {
            $q->where(["FinanceiroExtratoBancario.conciliado" => true]);
        }

        $extratos = $q->toArray();

        // Lançamentos não conciliados para matching
        $lancNaoConciliados = $this->FinanceiroLancamentos
            ->find()
            ->contain([
                "FinanceiroBancos" => [
                    "fields" => [
                        "id",
                        "codigo_banco",
                        "numero_agencia",
                        "digito_agencia",
                        "numero_conta",
                        "digito_conta",
                    ],
                ],
            ])
            ->where([
                "FinanceiroLancamentos.idempresa" => $idempresa,
                "FinanceiroLancamentos.status IN" => ["aberto"],
            ])
            ->order(["data_vencimento" => "ASC"])
            ->toArray();

        $sugestoesConciliacao = [];
        $lancamentosCompativeis = [];

        foreach ($extratos as $extrato) {
            $sugestoesConciliacao[(int) $extrato->id] = [];
            $lancamentosCompativeis[(int) $extrato->id] = [];

            if (!empty($extrato->conciliado)) {
                continue;
            }

            $tipoEsperado =
                ($extrato->tipo ?? "credito") === "debito"
                    ? "despesa"
                    : "receita";
            $contaExtrato = (string) ($extrato->conta_bancaria ?? "");
            $valorExtrato = (float) ($extrato->valor ?? 0);
            $dataExtrato = !empty($extrato->data)
                ? $extrato->data->format("Y-m-d")
                : null;

            $candidatos = [];
            foreach ($lancNaoConciliados as $lanc) {
                if (($lanc->tipo ?? null) !== $tipoEsperado) {
                    continue;
                }

                $score = 0;
                $motivos = [];
                $valorMatch = false;
                $dataMatch = false;
                $bancoMatch = false;

                $diferencaValor = abs(
                    (float) ($lanc->valor ?? 0) - $valorExtrato,
                );
                if ($diferencaValor < 0.01) {
                    $score += 70;
                    $motivos[] = "mesmo valor";
                    $valorMatch = true;
                } elseif ($diferencaValor <= 5) {
                    $score += 35;
                    $motivos[] = "valor próximo";
                    $valorMatch = true;
                } elseif ($diferencaValor <= 20) {
                    $score += 15;
                    $motivos[] = "valor semelhante";
                } else {
                    continue;
                }

                if (!empty($lanc->data_vencimento) && $dataExtrato !== null) {
                    $dias = abs(
                        floor(
                            (strtotime(
                                $lanc->data_vencimento->format("Y-m-d"),
                            ) -
                                strtotime($dataExtrato)) /
                                86400,
                        ),
                    );

                    if ($dias <= 1) {
                        $score += 25;
                        $motivos[] = "vencimento muito próximo";
                        $dataMatch = true;
                    } elseif ($dias <= 3) {
                        $score += 18;
                        $motivos[] = "vencimento próximo";
                        $dataMatch = true;
                    } elseif ($dias <= 7) {
                        $score += 10;
                        $motivos[] = "vencimento compatível";
                        $dataMatch = true;
                    }
                }

                if (!empty($contaExtrato) && !empty($lanc->financeiro_banco)) {
                    $bancoConta = $this->_formatarContaExtratoComparacao(
                        $lanc->financeiro_banco,
                    );
                    if (
                        $bancoConta !== "" &&
                        $this->_contaExtratoCompativel(
                            $contaExtrato,
                            $bancoConta,
                        )
                    ) {
                        $bancoMatch = true;
                        $score += 40;
                        $motivos[] = "mesma conta bancária";
                    }
                }

                if ($score < 20) {
                    continue;
                }

                $candidatos[] = [
                    "id" => (int) $lanc->id,
                    "lancamento" => $lanc,
                    "score" => $score,
                    "motivos" => $motivos,
                    "banco_match" => $bancoMatch,
                    "valor_match" => $valorMatch,
                    "data_match" => $dataMatch,
                ];
            }

            usort($candidatos, function ($a, $b) {
                if ($a["score"] === $b["score"]) {
                    return $a["id"] <=> $b["id"];
                }
                return $b["score"] <=> $a["score"];
            });

            $sugestoesConciliacao[(int) $extrato->id] = array_slice(
                $candidatos,
                0,
                5,
            );

            $idsSugestoes = array_map(function ($item) {
                return (int) $item["id"];
            }, $sugestoesConciliacao[(int) $extrato->id]);

            foreach ($lancNaoConciliados as $lanc) {
                if (($lanc->tipo ?? null) !== $tipoEsperado) {
                    continue;
                }
                if (in_array((int) $lanc->id, $idsSugestoes, true)) {
                    continue;
                }

                $lancamentosCompativeis[(int) $extrato->id][] = $lanc;

                if (count($lancamentosCompativeis[(int) $extrato->id]) >= 10) {
                    break;
                }
            }
        }

        $this->set(
            compact(
                "extratos",
                "filtro",
                "lancNaoConciliados",
                "sugestoesConciliacao",
                "lancamentosCompativeis",
            ),
        );
        $this->set("title", "Conciliação Bancária");
    }

    /**
     * POST — importar arquivo OFX ou CSV.
     */
    public function importarExtrato()
    {
        $this->request->allowMethod(["post"]);
        $idempresa = (int) $this->Auth->user("idempresa");
        $this->loadModel("FinanceiroExtratoBancario");

        $file = $this->request->getData("arquivo");
        if (
            empty($file) ||
            !is_array($file) ||
            (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        ) {
            $this->Flash->error(__("Selecione um arquivo OFX ou CSV."));
            return $this->redirect(["action" => "conciliacao"]);
        }

        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $conteudo = file_get_contents($file["tmp_name"]);

        $importados = 0;
        $duplicados = 0;

        if ($ext === "ofx") {
            $result = $this->_parseOfx($conteudo, $idempresa);
        } elseif ($ext === "csv") {
            $result = $this->_parseCsv($conteudo, $idempresa);
        } else {
            $this->Flash->error(__("Formato não suportado. Use OFX ou CSV."));
            return $this->redirect(["action" => "conciliacao"]);
        }

        foreach ($result as $row) {
            // Dedup by fitid
            if (!empty($row["fitid"])) {
                $exists = $this->FinanceiroExtratoBancario
                    ->find()
                    ->where([
                        "idempresa" => $idempresa,
                        "fitid" => $row["fitid"],
                    ])
                    ->first();
                if ($exists) {
                    $duplicados++;
                    continue;
                }
            }

            $entity = $this->FinanceiroExtratoBancario->newEntity($row);
            if ($this->FinanceiroExtratoBancario->save($entity)) {
                $importados++;
            }
        }

        $msg = "$importados transação(ões) importada(s).";
        if ($duplicados > 0) {
            $msg .= " $duplicados duplicada(s) ignorada(s).";
        }
        $this->Flash->success($msg);
        return $this->redirect(["action" => "conciliacao"]);
    }

    /**
     * POST AJAX — conciliar extrato com lançamento.
     */
    public function conciliarExtrato($idExtrato = null)
    {
        $this->request->allowMethod(["post"]);
        $idempresa = (int) $this->Auth->user("idempresa");
        $this->loadModel("FinanceiroExtratoBancario");

        $extrato = $this->FinanceiroExtratoBancario
            ->find()
            ->where(["id" => $idExtrato, "idempresa" => $idempresa])
            ->first();

        if (empty($extrato)) {
            return $this->jsonResponse(
                ["ok" => false, "msg" => "Extrato não encontrado."],
                404,
            );
        }

        $idLanc = (int) $this->request->getData("financeiro_lancamento_id");
        if (!$idLanc) {
            return $this->jsonResponse(
                ["ok" => false, "msg" => "Lançamento não informado."],
                400,
            );
        }

        $lanc = $this->FinanceiroLancamentos
            ->find()
            ->where(["id" => $idLanc, "idempresa" => $idempresa])
            ->first();

        if (empty($lanc)) {
            return $this->jsonResponse(
                ["ok" => false, "msg" => "Lançamento não encontrado."],
                404,
            );
        }

        $extrato->financeiro_lancamento_id = $lanc->id;
        $extrato->conciliado = true;
        $this->FinanceiroExtratoBancario->save($extrato);

        // Marcar lançamento como recebido/pago
        if ($lanc->status === "aberto") {
            $lanc->status = $lanc->tipo === "receita" ? "recebido" : "pago";
            $lanc->data_recebimento = $extrato->data
                ? $extrato->data->format("Y-m-d")
                : date("Y-m-d");
            $this->FinanceiroLancamentos->save($lanc);
        }

        return $this->jsonResponse(["ok" => true]);
    }

    /**
     * Parse OFX content into array of rows.
     */
    protected function _parseOfx(string $content, int $idempresa): array
    {
        $rows = [];
        $conta = "";

        // Extract account number
        if (preg_match('/<ACCTID>([^<\n]+)/i', $content, $m)) {
            $conta = trim($m[1]);
        }

        // Extract transactions
        preg_match_all("/<STMTTRN>(.*?)<\/STMTTRN>/si", $content, $matches);
        foreach ($matches[1] as $block) {
            $tipo = "credito";
            $valor = 0;
            $data = null;
            $descricao = "";
            $fitid = "";

            if (preg_match('/<TRNTYPE>([^<\n]+)/i', $block, $m)) {
                $tipo =
                    strtoupper(trim($m[1])) === "DEBIT" ? "debito" : "credito";
            }
            if (preg_match('/<TRNAMT>([^<\n]+)/i', $block, $m)) {
                $valor = abs((float) str_replace(",", ".", trim($m[1])));
                if ((float) str_replace(",", ".", trim($m[1])) < 0) {
                    $tipo = "debito";
                }
            }
            if (preg_match("/<DTPOSTED>(\d{8})/i", $block, $m)) {
                $data =
                    substr($m[1], 0, 4) .
                    "-" .
                    substr($m[1], 4, 2) .
                    "-" .
                    substr($m[1], 6, 2);
            }
            if (preg_match('/<MEMO>([^<\n]+)/i', $block, $m)) {
                $descricao = trim($m[1]);
            }
            if (preg_match('/<FITID>([^<\n]+)/i', $block, $m)) {
                $fitid = trim($m[1]);
            }

            $rows[] = [
                "idempresa" => $idempresa,
                "data" => $data,
                "descricao" => $descricao,
                "valor" => $valor,
                "tipo" => $tipo,
                "fitid" => $fitid,
                "conta_bancaria" => $conta,
                "origem" => "ofx",
                "conciliado" => false,
            ];
        }

        return $rows;
    }

    /**
     * Parse CSV (data;descricao;valor) into array of rows.
     */
    protected function _parseCsv(string $content, int $idempresa): array
    {
        $rows = [];
        $lines = preg_split('/\r?\n/', $content);
        $header = true;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === "") {
                continue;
            }

            // Skip header
            if ($header) {
                $header = false;
                if (
                    stripos($line, "data") !== false ||
                    stripos($line, "date") !== false
                ) {
                    continue;
                }
            }

            $sep = strpos($line, ";") !== false ? ";" : ",";
            $cols = str_getcsv($line, $sep);
            if (count($cols) < 3) {
                continue;
            }

            $dataStr = trim($cols[0]);
            $descricao = trim($cols[1]);
            $valorStr = str_replace([".", ","], ["", "."], trim($cols[2]));
            $valor = (float) $valorStr;
            $tipo = $valor >= 0 ? "credito" : "debito";
            $valor = abs($valor);

            // Try to parse date
            $data = null;
            if (preg_match("/(\d{2})\/(\d{2})\/(\d{4})/", $dataStr, $m)) {
                $data = $m[3] . "-" . $m[2] . "-" . $m[1];
            } elseif (preg_match("/(\d{4})-(\d{2})-(\d{2})/", $dataStr, $m)) {
                $data = $dataStr;
            }

            $rows[] = [
                "idempresa" => $idempresa,
                "data" => $data,
                "descricao" => $descricao,
                "valor" => $valor,
                "tipo" => $tipo,
                "fitid" => md5($dataStr . $descricao . $valorStr),
                "conta_bancaria" => null,
                "origem" => "csv",
                "conciliado" => false,
            ];
        }

        return $rows;
    }

    /* ── DRE — Demonstrativo de Resultado do Exercício ────────────────── */
    public function dre()
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $periodo = $this->request->getQuery("periodo") ?? date("Y-m");

        // Parse período: pode ser "YYYY-MM" (mês) ou "YYYY" (anual)
        $anoFiltro = null;
        $mesFiltro = null;
        if (preg_match('/^(\d{4})-(\d{2})$/', $periodo, $m)) {
            $anoFiltro = $m[1];
            $mesFiltro = $m[2];
            $dataInicio = "$anoFiltro-$mesFiltro-01";
            $dataFim = date("Y-m-t", strtotime($dataInicio));
            $labelPeriodo = date("F/Y", strtotime($dataInicio));
        } elseif (preg_match('/^(\d{4})$/', $periodo, $m)) {
            $anoFiltro = $m[1];
            $dataInicio = "$anoFiltro-01-01";
            $dataFim = "$anoFiltro-12-31";
            $labelPeriodo = "Ano $anoFiltro";
        } else {
            $dataInicio = date("Y-m-01");
            $dataFim = date("Y-m-t");
            $labelPeriodo = date("F/Y");
        }

        // Buscar lançamentos realizados (recebido/pago) no período
        $lancamentos = $this->FinanceiroLancamentos
            ->find()
            ->where([
                "FinanceiroLancamentos.idempresa" => $idempresa,
                "FinanceiroLancamentos.status IN" => ["recebido", "pago"],
                "FinanceiroLancamentos.data_recebimento >=" => $dataInicio,
                "FinanceiroLancamentos.data_recebimento <=" => $dataFim,
            ])
            ->contain([
                "FinanceiroPlanoContas" => [
                    "fields" => ["id", "codigo", "descricao", "tipo"],
                ],
            ])
            ->toArray();

        // Agrupar por plano de contas
        $receitas = [];
        $despesas = [];
        $totalReceitas = 0;
        $totalDespesas = 0;

        foreach ($lancamentos as $l) {
            $contaLabel = "(Sem classificação)";
            $contaCodigo = "999";
            if (!empty($l->financeiro_plano_conta)) {
                $contaLabel =
                    $l->financeiro_plano_conta->codigo .
                    " — " .
                    $l->financeiro_plano_conta->descricao;
                $contaCodigo = $l->financeiro_plano_conta->codigo;
            }

            if ($l->tipo === "receita") {
                if (!isset($receitas[$contaCodigo])) {
                    $receitas[$contaCodigo] = [
                        "label" => $contaLabel,
                        "valor" => 0,
                    ];
                }
                $receitas[$contaCodigo]["valor"] += (float) $l->valor;
                $totalReceitas += (float) $l->valor;
            } else {
                if (!isset($despesas[$contaCodigo])) {
                    $despesas[$contaCodigo] = [
                        "label" => $contaLabel,
                        "valor" => 0,
                    ];
                }
                $despesas[$contaCodigo]["valor"] += (float) $l->valor;
                $totalDespesas += (float) $l->valor;
            }
        }

        ksort($receitas);
        ksort($despesas);
        $resultado = $totalReceitas - $totalDespesas;

        $this->set(
            compact(
                "receitas",
                "despesas",
                "totalReceitas",
                "totalDespesas",
                "resultado",
                "periodo",
                "labelPeriodo",
            ),
        );
        $this->set("title", "DRE");
        $this->set("hideLayoutPageTitle", true);
    }

    /* ── Contas a receber ──────────────────────────────────────────────── */
    public function contasReceber()
    {
        $prototype = PortalUi::redirectToPrototypeIfEnabled('financeiro', 'FinanceiroPrototype', 'titulos');
        if ($prototype !== null) {
            return $this->redirect($prototype);
        }

        $idempresa = $this->Auth->user("idempresa");
        $cliente = $this->request->getQuery("cliente") ?? "";
        $status = $this->request->getQuery("status") ?? "aberto";

        $q = $this->FinanceiroLancamentos
            ->find("all")
            ->where([
                "FinanceiroLancamentos.idempresa" => $idempresa,
                "FinanceiroLancamentos.tipo" => "receita",
            ])
            ->contain([
                "Clientes" => [
                    "fields" => ["id", "razaosocial", "tipo", "nome"],
                ],
                "Faturamento" => ["fields" => ["id", "numero"]],
                "FinanceiroPlanoContas" => [
                    "fields" => ["id", "codigo", "descricao"],
                ],
                "FinanceiroCentrosCusto" => [
                    "fields" => ["id", "codigo", "descricao"],
                ],
                "FinanceiroBancos" => [
                    "fields" => [
                        "id",
                        "codigo_banco",
                        "nome",
                        "numero_agencia",
                        "numero_conta",
                    ],
                ],
            ])
            ->order(["FinanceiroLancamentos.data_vencimento" => "ASC"]);

        if ($status !== "") {
            $q->where(["FinanceiroLancamentos.status" => $status]);
        }
        if ($cliente !== "" && $cliente !== "0") {
            $q->where(["FinanceiroLancamentos.idcliente" => $cliente]);
        }

        $lancamentos = $q->toArray();

        $clientes = $this->Clientes
            ->find("list", [
                "keyField" => "id",
                "valueField" => "razaosocial",
            ])
            ->where(["idempresa" => $idempresa, "inativo" => 0])
            ->order(["razaosocial"])
            ->toArray();

        $this->set(compact("lancamentos", "clientes", "status", "cliente"));
        $this->set("title", "Contas a Receber");
    }

    /**
     * Formulário para novo lançamento de receita.
     */
    public function addReceita()
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $lancamento = $this->FinanceiroLancamentos->newEntity();

        if ($this->request->is("post")) {
            $data = $this->request->getData();
            $data["idempresa"] = $idempresa;
            $data["tipo"] = "receita";
            $data["status"] = "aberto";
            $data["idautor"] = (int) $this->Auth->user("id");
            $data["data_lancamento"] = date("Y-m-d");

            $lancamento = $this->FinanceiroLancamentos->patchEntity(
                $lancamento,
                $data,
            );
            if ($this->FinanceiroLancamentos->save($lancamento)) {
                $uid = $this->Auth->user("id");
                if ($uid) {
                    $this->Atividades->registrar(
                        $uid,
                        "Financeiro",
                        "addReceita",
                        (int) $lancamento->id,
                    );
                }
                $this->Flash->success(__("Receita registrada."));
                return $this->redirect(["action" => "contasReceber"]);
            }
            $this->Flash->error(
                __("Não foi possível salvar. Verifique os campos."),
            );
        }

        $planoContas = $this->FinanceiroPlanoContas->listByEmpresa(
            $idempresa,
            "receita",
            true,
        );
        $centrosCusto = $this->FinanceiroCentrosCusto->listByEmpresa(
            $idempresa,
        );
        $financeiroBancos = $this->FinanceiroBancos->listByEmpresa($idempresa);
        $clientes = $this->Clientes
            ->find("list", [
                "keyField" => "id",
                "valueField" => "razaosocial",
            ])
            ->where(["idempresa" => $idempresa, "inativo" => 0])
            ->order(["razaosocial"])
            ->toArray();

        $bancos = $financeiroBancos;

        $this->set(
            compact(
                "lancamento",
                "planoContas",
                "centrosCusto",
                "financeiroBancos",
                "bancos",
                "clientes",
            ),
        );
        $this->set("title", "Nova Receita");
    }

    /**
     * Edição de lançamento de receita.
     */
    public function editReceita($id = null)
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $lancamento = $this->FinanceiroLancamentos
            ->find()
            ->where([
                "id" => $id,
                "idempresa" => $idempresa,
                "tipo" => "receita",
            ])
            ->first();

        if (empty($lancamento)) {
            $this->Flash->error(__("Lançamento não encontrado."));
            return $this->redirect(["action" => "contasReceber"]);
        }

        if ($this->request->is(["put", "patch", "post"])) {
            $data = $this->request->getData();
            unset($data["idempresa"], $data["tipo"], $data["idautor"]);
            $lancamento = $this->FinanceiroLancamentos->patchEntity(
                $lancamento,
                $data,
            );
            if ($this->FinanceiroLancamentos->save($lancamento)) {
                $uid = $this->Auth->user("id");
                if ($uid) {
                    $this->Atividades->registrar(
                        $uid,
                        "Financeiro",
                        "editReceita",
                        (int) $lancamento->id,
                    );
                }
                $this->Flash->success(__("Receita atualizada."));
                return $this->redirect(["action" => "contasReceber"]);
            }
            $this->Flash->error(__("Não foi possível salvar."));
        }

        $planoContas = $this->FinanceiroPlanoContas->listByEmpresa(
            $idempresa,
            "receita",
            true,
        );
        $centrosCusto = $this->FinanceiroCentrosCusto->listByEmpresa(
            $idempresa,
        );
        $financeiroBancos = $this->FinanceiroBancos->listByEmpresa($idempresa);
        $clientes = $this->Clientes
            ->find("list", [
                "keyField" => "id",
                "valueField" => "razaosocial",
            ])
            ->where(["idempresa" => $idempresa, "inativo" => 0])
            ->order(["razaosocial"])
            ->toArray();

        $bancos = $financeiroBancos;

        $this->set(
            compact(
                "lancamento",
                "planoContas",
                "centrosCusto",
                "financeiroBancos",
                "bancos",
                "clientes",
            ),
        );
        $this->set("title", "Editar Receita");
    }

    /**
     * Detalhe do lançamento (contas a receber / fatura financeira).
     */
    public function fatura($id = null)
    {
        $idempresa = $this->Auth->user("idempresa");
        $lancamento = $this->FinanceiroLancamentos
            ->find("all")
            ->where([
                "FinanceiroLancamentos.id" => $id,
                "FinanceiroLancamentos.idempresa" => $idempresa,
            ])
            ->contain([
                "Clientes" => [
                    "fields" => [
                        "id",
                        "razaosocial",
                        "tipo",
                        "nome",
                        "cnpj",
                        "cpf",
                    ],
                ],
                "Faturamento" => [
                    "fields" => [
                        "id",
                        "numero",
                        "status",
                        "valor_total",
                        "valor_subtotal",
                        "valor_desconto",
                    ],
                    "FaturamentoItens",
                ],
                "Users" => ["fields" => ["id", "name", "username"]],
                "FinanceiroLancamentoAnexos" => [
                    "sort" => ["FinanceiroLancamentoAnexos.id" => "DESC"],
                    "Users" => ["fields" => ["id", "name", "username"]],
                ],
            ])
            ->first();

        if (empty($lancamento)) {
            throw new \Cake\Http\Exception\NotFoundException(
                __("Lançamento não encontrado."),
            );
        }

        $historicoFatura = $this->_buildHistoricoFatura($lancamento);

        $auditoriaFatura = $this->Atividades
            ->find("all")
            ->contain([
                "Users" => [
                    "fields" => ["Users.id", "Users.name", "Users.username"],
                ],
            ])
            ->where([
                "Atividades.controller" => "Financeiro",
                "Atividades.idtable" => (int) $lancamento->id,
            ])
            ->order(["Atividades.id" => "DESC"])
            ->limit(80)
            ->toArray();

        $this->set(compact("lancamento", "historicoFatura", "auditoriaFatura"));
        $this->set("title", "Detalhe da fatura");
        $this->set("hideLayoutPageTitle", true);
    }

    /**
     * Exporta resumo do lançamento em CSV (UTF-8).
     */
    public function exportarFatura($id = null)
    {
        $this->autoRender = false;
        $idempresa = $this->Auth->user("idempresa");
        $l = $this->FinanceiroLancamentos
            ->find("all")
            ->where([
                "FinanceiroLancamentos.id" => $id,
                "FinanceiroLancamentos.idempresa" => $idempresa,
            ])
            ->contain([
                "Clientes" => ["fields" => ["razaosocial", "tipo", "nome"]],
            ])
            ->first();

        if (empty($l)) {
            throw new \Cake\Http\Exception\NotFoundException(
                __("Lançamento não encontrado."),
            );
        }

        $nomeCli = "—";
        if (!empty($l->cliente)) {
            $nomeCli =
                $l->cliente->tipo == 1
                    ? (string) ($l->cliente->nome ?? "")
                    : (string) ($l->cliente->razaosocial ?? "");
        }

        $fh = fopen("php://temp", "r+");
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ["Campo", "Valor"]);
        fputcsv($fh, ["ID lançamento", (string) $l->id]);
        fputcsv($fh, ["Cliente", $nomeCli]);
        fputcsv($fh, ["Descrição", (string) $l->descricao]);
        fputcsv($fh, ["Valor", number_format((float) $l->valor, 2, ",", ".")]);
        fputcsv($fh, [
            "Vencimento",
            $l->data_vencimento ? $l->data_vencimento->format("d/m/Y") : "",
        ]);
        fputcsv($fh, ["Status", (string) $l->status]);
        fputcsv($fh, [
            "Data recebimento",
            $l->data_recebimento ? $l->data_recebimento->format("d/m/Y") : "",
        ]);
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        $fn =
            "fatura-financeiro-" .
            (int) $l->id .
            "-" .
            date("Ymd-His") .
            ".csv";

        return $this->response
            ->withType("text/csv; charset=UTF-8")
            ->withDownload($fn)
            ->withStringBody($csv);
    }

    /**
     * Exporta detalhe do lançamento em PDF (mPDF), alinhado a Relatórios/Orcamentos.
     */
    public function exportarFaturaPdf($id = null)
    {
        if (!class_exists(\Mpdf\Mpdf::class)) {
            $this->Flash->error(
                __("Biblioteca mPDF não disponível. Use a exportação em CSV."),
            );
            return $this->redirect(["action" => "fatura", $id]);
        }

        $idempresa = $this->Auth->user("idempresa");
        $lancamento = $this->FinanceiroLancamentos
            ->find("all")
            ->where([
                "FinanceiroLancamentos.id" => $id,
                "FinanceiroLancamentos.idempresa" => $idempresa,
            ])
            ->contain([
                "Clientes" => [
                    "fields" => [
                        "id",
                        "razaosocial",
                        "tipo",
                        "nome",
                        "cnpj",
                        "cpf",
                    ],
                ],
                "Faturamento" => [
                    "fields" => [
                        "id",
                        "numero",
                        "status",
                        "valor_total",
                        "valor_subtotal",
                        "valor_desconto",
                    ],
                    "FaturamentoItens",
                ],
                "Users" => ["fields" => ["id", "name", "username"]],
            ])
            ->first();

        if (empty($lancamento)) {
            throw new \Cake\Http\Exception\NotFoundException(
                __("Lançamento não encontrado."),
            );
        }

        $historicoFatura = $this->_buildHistoricoFatura($lancamento);

        $this->autoRender = false;
        $this->viewBuilder()->setLayout(false);
        $this->set(compact("lancamento", "historicoFatura"));
        $view = $this->createView();
        $html = $view->render("pdf_fatura_financeiro");

        $tmpDir = TMP . "mpdf" . DS;
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            "mode" => "utf-8",
            "format" => "A4",
            "tempDir" => $tmpDir,
        ]);
        $mpdf->WriteHTML($html);
        $pdf = $mpdf->Output("", "S");
        $fn =
            "fatura-financeiro-" .
            (int) $lancamento->id .
            "-" .
            date("Ymd-His") .
            ".pdf";

        return $this->response
            ->withType("application/pdf")
            ->withDownload($fn)
            ->withStringBody($pdf);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $l
     * @return array<int,array{dt:\DateTimeInterface|string,texto:string}>
     */
    protected function _buildHistoricoFatura($l)
    {
        $rows = [];
        if (!empty($l->created)) {
            $rows[] = ["dt" => $l->created, "texto" => "Lançamento criado"];
        }
        if (
            !empty($l->modified) &&
            (string) $l->modified !== (string) $l->created
        ) {
            $rows[] = ["dt" => $l->modified, "texto" => "Registro alterado"];
        }
        if (
            in_array($l->status, ["recebido", "pago"], true) &&
            !empty($l->data_recebimento)
        ) {
            $rows[] = [
                "dt" => $l->data_recebimento,
                "texto" => "Recebimento registrado",
            ];
        }
        usort($rows, function ($a, $b) {
            $ta =
                $a["dt"] instanceof \DateTimeInterface
                    ? $a["dt"]->getTimestamp()
                    : 0;
            $tb =
                $b["dt"] instanceof \DateTimeInterface
                    ? $b["dt"]->getTimestamp()
                    : 0;
            return $tb <=> $ta;
        });

        return $rows;
    }

    /**
     * Diretório físico dos anexos do lançamento (por empresa).
     */
    protected function _dirFinanceiroAnexos($idempresa, $idlancamento)
    {
        return WWW_ROOT .
            "arquivos" .
            DS .
            "financeiro_lancamentos" .
            DS .
            (int) $idempresa .
            DS .
            (int) $idlancamento;
    }

    /**
     * POST — upload de anexo na fatura (lançamento).
     */
    public function adicionarAnexoFatura($id = null)
    {
        $this->request->allowMethod(["post"]);
        $idempresa = (int) $this->Auth->user("idempresa");
        $lancamento = $this->FinanceiroLancamentos
            ->find("all")
            ->where([
                "FinanceiroLancamentos.id" => $id,
                "FinanceiroLancamentos.idempresa" => $idempresa,
            ])
            ->first();

        if (empty($lancamento)) {
            $this->Flash->error(__("Lançamento não encontrado."));
            return $this->redirect(["action" => "contasReceber"]);
        }

        $file = $this->request->getData("anexo");
        if (
            empty($file) ||
            !is_array($file) ||
            (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            empty($file["tmp_name"])
        ) {
            $this->Flash->error(__("Selecione um arquivo para enviar."));
            return $this->redirect(["action" => "fatura", $id]);
        }

        $orig = (string) ($file["name"] ?? "");
        if (
            $orig === "" ||
            strpos($orig, "..") !== false ||
            strpos($orig, "/") !== false ||
            strpos($orig, "\\") !== false
        ) {
            $this->Flash->error(__("Nome de arquivo inválido."));
            return $this->redirect(["action" => "fatura", $id]);
        }

        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $base = pathinfo($orig, PATHINFO_FILENAME);
        $safeBase =
            trim(
                preg_replace("/[^a-zA-Z0-9._-]+/", "_", (string) $base),
                "._-",
            ) ?:
            "arquivo";
        $safeExt =
            $ext !== ""
                ? "." . preg_replace("/[^a-zA-Z0-9]/", "", (string) $ext)
                : "";
        $stored = uniqid("fl_", true) . "_" . $safeBase . $safeExt;

        $dir = $this->_dirFinanceiroAnexos($idempresa, (int) $id);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $full = $dir . DS . $stored;
        if (!move_uploaded_file($file["tmp_name"], $full)) {
            $this->Flash->error(__("Não foi possível salvar o arquivo."));
            return $this->redirect(["action" => "fatura", $id]);
        }

        $anexo = $this->FinanceiroLancamentoAnexos->newEntity([
            "idlancamento" => (int) $id,
            "idempresa" => $idempresa,
            "arquivo" => $stored,
            "nome_original" => $orig,
            "iduser" => (int) $this->Auth->user("id"),
        ]);
        if (!$this->FinanceiroLancamentoAnexos->save($anexo)) {
            @unlink($full);
            $this->Flash->error(__("Erro ao registrar o anexo."));
            return $this->redirect(["action" => "fatura", $id]);
        }

        $uid = $this->Auth->user("id");
        if ($uid) {
            $this->Atividades->registrar(
                $uid,
                "Financeiro",
                "adicionarAnexoFatura",
                (int) $id,
            );
        }
        $this->Flash->success(__("Anexo enviado."));
        return $this->redirect(["action" => "fatura", $id]);
    }

    /**
     * GET — download de anexo.
     */
    public function baixarAnexoFatura($idanexo = null)
    {
        $this->autoRender = false;
        $idempresa = (int) $this->Auth->user("idempresa");
        $anexo = $this->FinanceiroLancamentoAnexos
            ->find("all")
            ->where([
                "FinanceiroLancamentoAnexos.id" => $idanexo,
                "FinanceiroLancamentoAnexos.idempresa" => $idempresa,
            ])
            ->first();

        if (empty($anexo)) {
            throw new \Cake\Http\Exception\NotFoundException(
                __("Anexo não encontrado."),
            );
        }

        $full =
            $this->_dirFinanceiroAnexos(
                $anexo->idempresa,
                $anexo->idlancamento,
            ) .
            DS .
            $anexo->arquivo;
        if (!is_readable($full)) {
            $this->Flash->error(__("Arquivo não localizado no servidor."));
            return $this->redirect([
                "action" => "fatura",
                $anexo->idlancamento,
            ]);
        }

        $downloadName =
            (string) ($anexo->nome_original ?:
            basename((string) $anexo->arquivo));
        $downloadName = str_replace(
            ['"', "\r", "\n"],
            "",
            basename($downloadName),
        );

        return $this->response->withFile($full, [
            "download" => true,
            "name" => $downloadName,
        ]);
    }

    /**
     * POST — remove anexo.
     */
    public function removerAnexoFatura($idanexo = null)
    {
        $this->request->allowMethod(["post"]);
        $idempresa = (int) $this->Auth->user("idempresa");
        $anexo = $this->FinanceiroLancamentoAnexos
            ->find("all")
            ->where([
                "FinanceiroLancamentoAnexos.id" => $idanexo,
                "FinanceiroLancamentoAnexos.idempresa" => $idempresa,
            ])
            ->first();

        if (empty($anexo)) {
            $this->Flash->error(__("Anexo não encontrado."));
            return $this->redirect(
                $this->referer(["action" => "contasReceber"]),
            );
        }

        $idLanc = (int) $anexo->idlancamento;
        $full =
            $this->_dirFinanceiroAnexos(
                $anexo->idempresa,
                $anexo->idlancamento,
            ) .
            DS .
            $anexo->arquivo;
        if (is_file($full)) {
            @unlink($full);
        }
        $this->FinanceiroLancamentoAnexos->delete($anexo);
        $uid = $this->Auth->user("id");
        if ($uid) {
            $this->Atividades->registrar(
                $uid,
                "Financeiro",
                "removerAnexoFatura",
                $idLanc,
            );
        }
        $this->Flash->success(__("Anexo removido."));
        return $this->redirect(["action" => "fatura", $idLanc]);
    }

    /* ── Contas a pagar ───────────────────────────────────────────────── */
    public function contasPagar()
    {
        $idempresa = $this->Auth->user("idempresa");
        $status = $this->request->getQuery("status") ?? "aberto";
        $fornecedor = $this->request->getQuery("fornecedor") ?? "";

        $q = $this->FinanceiroLancamentos
            ->find("all")
            ->where([
                "FinanceiroLancamentos.idempresa" => $idempresa,
                "FinanceiroLancamentos.tipo" => "despesa",
            ])
            ->contain([
                "Clientes" => [
                    "fields" => ["id", "razaosocial", "tipo", "nome"],
                ],
                "FinanceiroPlanoContas" => [
                    "fields" => ["id", "codigo", "descricao"],
                ],
                "FinanceiroCentrosCusto" => [
                    "fields" => ["id", "codigo", "descricao"],
                ],
                "FinanceiroBancos" => [
                    "fields" => [
                        "id",
                        "codigo_banco",
                        "nome",
                        "numero_agencia",
                        "numero_conta",
                    ],
                ],
            ])
            ->order(["FinanceiroLancamentos.data_vencimento" => "ASC"]);

        if ($status !== "") {
            $q->where(["FinanceiroLancamentos.status" => $status]);
        }
        if ($fornecedor !== "" && $fornecedor !== "0") {
            $q->where(["FinanceiroLancamentos.idcliente" => $fornecedor]);
        }

        $lancamentos = $q->toArray();

        $fornecedores = $this->Clientes
            ->find("list", [
                "keyField" => "id",
                "valueField" => "razaosocial",
            ])
            ->where(["idempresa" => $idempresa, "inativo" => 0])
            ->order(["razaosocial"])
            ->toArray();

        $this->set(
            compact("lancamentos", "fornecedores", "status", "fornecedor"),
        );
        $this->set("title", "Contas a Pagar");
    }

    /**
     * Formulário para novo lançamento de despesa.
     */
    public function addDespesa()
    {
        $idempresa = (int) $this->Auth->user("idempresa");

        $lancamento = $this->FinanceiroLancamentos->newEntity();

        if ($this->request->is("post")) {
            $data = $this->request->getData();
            $data["idempresa"] = $idempresa;
            $data["tipo"] = "despesa";
            $data["status"] = "aberto";
            $data["idautor"] = (int) $this->Auth->user("id");
            $data["data_lancamento"] = date("Y-m-d");

            $lancamento = $this->FinanceiroLancamentos->patchEntity(
                $lancamento,
                $data,
            );
            if ($this->FinanceiroLancamentos->save($lancamento)) {
                $uid = $this->Auth->user("id");
                if ($uid) {
                    $this->Atividades->registrar(
                        $uid,
                        "Financeiro",
                        "addDespesa",
                        (int) $lancamento->id,
                    );
                }
                $this->Flash->success(__("Despesa registrada."));
                return $this->redirect(["action" => "contasPagar"]);
            }
            $this->Flash->error(
                __("Não foi possível salvar. Verifique os campos."),
            );
        }

        $planoContas = $this->FinanceiroPlanoContas->listByEmpresa(
            $idempresa,
            "despesa",
            true,
        );
        $centrosCusto = $this->FinanceiroCentrosCusto->listByEmpresa(
            $idempresa,
        );
        $financeiroBancos = $this->FinanceiroBancos->listByEmpresa($idempresa);
        $fornecedores = $this->Clientes
            ->find("list", [
                "keyField" => "id",
                "valueField" => "razaosocial",
            ])
            ->where(["idempresa" => $idempresa, "inativo" => 0])
            ->order(["razaosocial"])
            ->toArray();

        $bancos = $financeiroBancos;

        $this->set(
            compact(
                "lancamento",
                "planoContas",
                "centrosCusto",
                "financeiroBancos",
                "bancos",
                "fornecedores",
            ),
        );
        $this->set("title", "Nova Despesa");
    }

    /**
     * Edição de lançamento de despesa.
     */
    public function editDespesa($id = null)
    {
        $idempresa = (int) $this->Auth->user("idempresa");
        $lancamento = $this->FinanceiroLancamentos
            ->find()
            ->where([
                "id" => $id,
                "idempresa" => $idempresa,
                "tipo" => "despesa",
            ])
            ->first();

        if (empty($lancamento)) {
            $this->Flash->error(__("Lançamento não encontrado."));
            return $this->redirect(["action" => "contasPagar"]);
        }

        if ($this->request->is(["put", "patch", "post"])) {
            $data = $this->request->getData();
            unset($data["idempresa"], $data["tipo"], $data["idautor"]);
            $lancamento = $this->FinanceiroLancamentos->patchEntity(
                $lancamento,
                $data,
            );
            if ($this->FinanceiroLancamentos->save($lancamento)) {
                $uid = $this->Auth->user("id");
                if ($uid) {
                    $this->Atividades->registrar(
                        $uid,
                        "Financeiro",
                        "editDespesa",
                        (int) $lancamento->id,
                    );
                }
                $this->Flash->success(__("Despesa atualizada."));
                return $this->redirect(["action" => "contasPagar"]);
            }
            $this->Flash->error(__("Não foi possível salvar."));
        }

        $planoContas = $this->FinanceiroPlanoContas->listByEmpresa(
            $idempresa,
            "despesa",
            true,
        );
        $centrosCusto = $this->FinanceiroCentrosCusto->listByEmpresa(
            $idempresa,
        );
        $financeiroBancos = $this->FinanceiroBancos->listByEmpresa($idempresa);
        $fornecedores = $this->Clientes
            ->find("list", [
                "keyField" => "id",
                "valueField" => "razaosocial",
            ])
            ->where(["idempresa" => $idempresa, "inativo" => 0])
            ->order(["razaosocial"])
            ->toArray();

        $bancos = $financeiroBancos;

        $this->set(
            compact(
                "lancamento",
                "planoContas",
                "centrosCusto",
                "financeiroBancos",
                "bancos",
                "fornecedores",
            ),
        );
        $this->set("title", "Editar Despesa");
    }

    /* ── Registrar pagamento (despesa) ────────────────────────────────── */
    public function registrarPagamento($id = null)
    {
        $this->request->allowMethod(["post"]);
        $idempresa = $this->Auth->user("idempresa");

        $lancamento = $this->FinanceiroLancamentos
            ->find("all")
            ->where([
                "id" => $id,
                "idempresa" => $idempresa,
                "tipo" => "despesa",
            ])
            ->first();

        if (empty($lancamento)) {
            return $this->jsonResponse(
                ["ok" => false, "msg" => "Não encontrado."],
                404,
            );
        }

        $lancamento->status = "pago";
        $lancamento->data_recebimento =
            $this->request->getData("data_pagamento") ?? date("Y-m-d");
        if (!$this->FinanceiroLancamentos->save($lancamento)) {
            return $this->jsonResponse(
                ["ok" => false, "msg" => "Não foi possível salvar."],
                500,
            );
        }

        $uid = $this->Auth->user("id");
        if ($uid) {
            $this->Atividades->registrar(
                $uid,
                "Financeiro",
                "registrarPagamento",
                (int) $lancamento->id,
            );
        }

        return $this->jsonResponse(["ok" => true]);
    }

    /* ── Cancelar lançamento (despesa) ────────────────────────────────── */
    public function cancelarDespesa($id = null)
    {
        $this->request->allowMethod(["post"]);
        $idempresa = $this->Auth->user("idempresa");

        $lancamento = $this->FinanceiroLancamentos
            ->find("all")
            ->where([
                "id" => $id,
                "idempresa" => $idempresa,
                "tipo" => "despesa",
            ])
            ->first();

        if (empty($lancamento)) {
            return $this->jsonResponse(
                ["ok" => false, "msg" => "Não encontrado."],
                404,
            );
        }

        $lancamento->status = "cancelado";
        if (!$this->FinanceiroLancamentos->save($lancamento)) {
            return $this->jsonResponse(
                ["ok" => false, "msg" => "Não foi possível salvar."],
                500,
            );
        }

        $uid = $this->Auth->user("id");
        if ($uid) {
            $this->Atividades->registrar(
                $uid,
                "Financeiro",
                "cancelarDespesa",
                (int) $lancamento->id,
            );
        }

        return $this->jsonResponse(["ok" => true]);
    }

    /* ── Registrar recebimento ─────────────────────────────────────────── */
    public function registrarRecebimento($id = null)
    {
        $this->request->allowMethod(["post"]);
        $idempresa = $this->Auth->user("idempresa");

        $lancamento = $this->FinanceiroLancamentos
            ->find("all")
            ->where(["id" => $id, "idempresa" => $idempresa])
            ->first();

        if (empty($lancamento)) {
            return $this->jsonResponse(
                ["ok" => false, "msg" => "Não encontrado."],
                404,
            );
        }

        $lancamento->status = "recebido";
        $lancamento->data_recebimento =
            $this->request->getData("data_recebimento") ?? date("Y-m-d");
        if (!$this->FinanceiroLancamentos->save($lancamento)) {
            return $this->jsonResponse(
                ["ok" => false, "msg" => "Não foi possível salvar."],
                500,
            );
        }

        $uid = $this->Auth->user("id");
        if ($uid) {
            $this->Atividades->registrar(
                $uid,
                "Financeiro",
                "registrarRecebimento",
                (int) $lancamento->id,
            );
        }

        // Atualiza status do faturamento vinculado
        if (!empty($lancamento->idfaturamento)) {
            $fat = $this->Faturamento
                ->find("all")
                ->where(["id" => $lancamento->idfaturamento])
                ->first();
            if (!empty($fat)) {
                $fat->status = "pago";
                $this->Faturamento->save($fat);
            }
        }

        return $this->jsonResponse(["ok" => true]);
    }

    /**
     * Formata conta bancária do lançamento para comparação com extrato.
     *
     * @param object $banco
     * @return string
     */
    protected function _formatarContaExtratoComparacao($banco): string
    {
        $agencia = trim((string) ($banco->numero_agencia ?? ""));
        $digitoAgencia = trim((string) ($banco->digito_agencia ?? ""));
        $conta = trim((string) ($banco->numero_conta ?? ""));
        $digitoConta = trim((string) ($banco->digito_conta ?? ""));

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

        return $contaFmt !== "" ? $contaFmt : $agenciaFmt;
    }

    /**
     * Compara conta do extrato com conta do banco de forma tolerante.
     *
     * @param string $contaExtrato
     * @param string $contaBanco
     * @return bool
     */
    protected function _contaExtratoCompativel(
        string $contaExtrato,
        string $contaBanco
    ): bool {
        $extratoNormalizado = preg_replace("/\D+/", "", $contaExtrato) ?? "";
        $bancoNormalizado = preg_replace("/\D+/", "", $contaBanco) ?? "";

        if ($extratoNormalizado === "" || $bancoNormalizado === "") {
            return false;
        }

        if ($extratoNormalizado === $bancoNormalizado) {
            return true;
        }

        if (
            strlen($bancoNormalizado) >= 4 &&
            substr($extratoNormalizado, -strlen($bancoNormalizado)) ===
                $bancoNormalizado
        ) {
            return true;
        }

        if (
            strlen($extratoNormalizado) >= 4 &&
            substr($bancoNormalizado, -strlen($extratoNormalizado)) ===
                $extratoNormalizado
        ) {
            return true;
        }

        return false;
    }
}
