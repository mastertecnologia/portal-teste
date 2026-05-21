<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\Financeiro\CnabService;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Utility\Hash;

/**
 * Processamento de arquivos de retorno CNAB.
 *
 * Regras principais:
 * - Usa `financeiro_lancamentos` como tabela-base dos títulos.
 * - NÃO reutiliza `status` para cobrança bancária; usa `status_cobranca`.
 * - Atualiza `status` financeiro apenas quando houver liquidação confirmada.
 * - Processa retorno CNAB 240 priorizando segmentos T e U.
 */
class RetornosController extends AppController
{
    /**
     * @var \App\Service\Financeiro\CnabService
     */
    protected $CnabService;

    public function initialize()
    {
        parent::initialize();

        $this->loadModel("FinanceiroBancos");
        $this->loadModel("FinanceiroLancamentos");

        try {
            $this->loadModel("FinanceiroRemessas");
        } catch (\Exception $e) {
            // Tabela opcional durante rollout.
        }

        try {
            $this->loadModel("FinanceiroRemessaTitulos");
        } catch (\Exception $e) {
            // Tabela opcional durante rollout.
        }

        try {
            $this->loadModel("Atividades");
        } catch (\Exception $e) {
            // Auditoria opcional.
        }

        try {
            $this->loadModel("FinanceiroRetornoArquivos");
        } catch (\Exception $e) {
            // Persistência de cabeçalho do retorno é opcional durante rollout.
        }

        try {
            $this->loadModel("FinanceiroRetornoItens");
        } catch (\Exception $e) {
            // Persistência detalhada do retorno é opcional durante rollout.
        }

        $this->CnabService = new CnabService();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->set("title", "Retornos Bancários");
    }

    public function isAuthorized($user)
    {
        if ((int) ($user["role"] ?? 1) !== 0) {
            return false;
        }

        return parent::isAuthorized($user);
    }

    /**
     * Endpoint JSON para processamento de arquivo .RET.
     *
     * Fluxo:
     * - recebe upload via multipart/form-data (campo `arquivo`)
     * - lê segmentos T/U do CNAB 240
     * - localiza os títulos por `nosso_numero` e/ou vínculo de remessa
     * - aplica baixa/liquidação ou rejeição
     * - devolve log amigável do processamento
     *
     * @return \Cake\Http\Response
     */
    public function processar()
    {
        $this->request->allowMethod(["post"]);

        $idempresa = (int) $this->Auth->user("idempresa");
        $usuarioId = (int) $this->Auth->user("id");
        $arquivo = $this->request->getData("arquivo");

        if (empty($arquivo)) {
            return $this->jsonResponse(
                [
                    "ok" => false,
                    "error" => "Arquivo de retorno não informado.",
                ],
                400,
            );
        }

        $tmpName = $this->_arquivoTmpName($arquivo);
        $clientName = $this->_arquivoClientName($arquivo);

        if ($tmpName === "" || !is_file($tmpName)) {
            return $this->jsonResponse(
                [
                    "ok" => false,
                    "error" => "Arquivo temporário inválido.",
                ],
                400,
            );
        }

        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        if ($ext !== "" && !in_array($ext, ["ret", "txt"], true)) {
            return $this->jsonResponse(
                [
                    "ok" => false,
                    "error" =>
                        "Formato inválido. Envie um arquivo .RET ou .TXT.",
                ],
                400,
            );
        }

        $conteudo = @file_get_contents($tmpName);
        if ($conteudo === false || trim($conteudo) === "") {
            return $this->jsonResponse(
                [
                    "ok" => false,
                    "error" => "Não foi possível ler o arquivo de retorno.",
                ],
                400,
            );
        }

        $linhas = preg_split("/\r\n|\n|\r/", $conteudo);
        $linhas = array_values(
            array_filter(array_map("rtrim", (array) $linhas), function ($linha) {
                return trim((string) $linha) !== "";
            }),
        );

        if (empty($linhas)) {
            return $this->jsonResponse(
                [
                    "ok" => false,
                    "error" => "Arquivo de retorno sem linhas válidas.",
                ],
                400,
            );
        }

        $bancoInformado = (int) $this->request->getData("banco_id");
        $observacoes = trim(
            (string) $this->request->getData("observacoes", ""),
        );
        $registros = $this->_parseRetorno240($linhas);
        $resumo = [
            "arquivo" => $clientName,
            "processados" => 0,
            "baixados" => 0,
            "rejeitados" => 0,
            "ignorados" => 0,
            "erros" => 0,
        ];
        $log = [];
        $retornoArquivo = null;
        $remessaRelacionadaId = null;
        $bancoDetectadoId = $bancoInformado > 0 ? $bancoInformado : null;

        $arquivoPersistido = $this->_persistirArquivoRetornoUpload(
            $clientName,
            $conteudo,
        );

        foreach ($registros as $registro) {
            $resumo["processados"]++;

            $nossoNumero = trim(
                (string) Hash::get($registro, "nosso_numero", ""),
            );
            $codigoOcorrencia = trim(
                (string) Hash::get($registro, "codigo_ocorrencia", ""),
            );
            $mensagemOcorrencia = $this->_traduzirOcorrencia(
                trim((string) Hash::get($registro, "banco_codigo", "")),
                $codigoOcorrencia,
            );
            $valorPago = (float) Hash::get($registro, "valor_pago", 0);
            $dataOcorrencia = Hash::get($registro, "data_ocorrencia");
            $liquidado = $this->_isOcorrenciaLiquidada(
                $codigoOcorrencia,
                $valorPago,
            );
            $rejeitado = $this->_isOcorrenciaRejeitada($codigoOcorrencia);

            $lancamento = $this->_localizarLancamentoPorRetorno(
                $idempresa,
                $nossoNumero,
                $bancoInformado,
                $registro,
            );

            if (
                !empty($lancamento) &&
                empty($bancoDetectadoId) &&
                !empty($lancamento->financeiro_banco_id)
            ) {
                $bancoDetectadoId = (int) $lancamento->financeiro_banco_id;
            }

            $itemRemessa = null;
            if (!empty($lancamento)) {
                $itemRemessa = $this->_localizarUltimoItemRemessaPorLancamento(
                    (int) $lancamento->id,
                );
                if (
                    !empty($itemRemessa) &&
                    empty($remessaRelacionadaId) &&
                    !empty($itemRemessa->financeiro_remessa_id)
                ) {
                    $remessaRelacionadaId =
                        (int) $itemRemessa->financeiro_remessa_id;
                }
            }

            if ($retornoArquivo === null) {
                $retornoArquivo = $this->_criarCabecalhoRetornoArquivo([
                    "idempresa" => $idempresa,
                    "financeiro_banco_id" => $bancoDetectadoId,
                    "usuario_id" => $usuarioId > 0 ? $usuarioId : null,
                    "financeiro_remessa_id" => $remessaRelacionadaId,
                    "nome_arquivo_original" => $clientName,
                    "nome_arquivo_salvo" =>
                        $arquivoPersistido["nome_arquivo_salvo"],
                    "caminho_arquivo" => $arquivoPersistido["caminho_arquivo"],
                    "layout_cnab" => "240",
                    "status_processamento" => "processado",
                    "observacoes" => $observacoes !== "" ? $observacoes : null,
                    "data_processamento" => date("Y-m-d H:i:s"),
                ]);
            }

            if (empty($lancamento)) {
                $resumo["erros"]++;
                $logItem = [
                    "status" => "erro",
                    "nosso_numero" => $nossoNumero,
                    "codigo_ocorrencia" => $codigoOcorrencia,
                    "mensagem" =>
                        $mensagemOcorrencia !== ""
                            ? "Título não localizado: " . $mensagemOcorrencia
                            : "Título não localizado para o retorno informado.",
                ];
                $log[] = $logItem;

                $this->_persistirItemRetorno(
                    $retornoArquivo,
                    null,
                    null,
                    $registro,
                    $logItem,
                );
                continue;
            }

            try {
                if ($liquidado) {
                    $this->_aplicarLiquidacao(
                        $lancamento,
                        $valorPago,
                        $dataOcorrencia,
                        $codigoOcorrencia,
                        $mensagemOcorrencia,
                    );
                    $resumo["baixados"]++;

                    $logItem = [
                        "status" => "baixado",
                        "titulo_id" => (int) $lancamento->id,
                        "nosso_numero" => (string) $lancamento->nosso_numero,
                        "codigo_ocorrencia" => $codigoOcorrencia,
                        "valor_pago" => $valorPago,
                        "mensagem" =>
                            $mensagemOcorrencia !== ""
                                ? $mensagemOcorrencia
                                : "Título baixado com sucesso.",
                    ];
                    $log[] = $logItem;

                    $this->_persistirItemRetorno(
                        $retornoArquivo,
                        $lancamento,
                        $itemRemessa,
                        $registro,
                        $logItem,
                    );
                    continue;
                }

                if ($rejeitado) {
                    $this->_aplicarRejeicao(
                        $lancamento,
                        $codigoOcorrencia,
                        $mensagemOcorrencia,
                    );
                    $resumo["rejeitados"]++;

                    $logItem = [
                        "status" => "rejeitado",
                        "titulo_id" => (int) $lancamento->id,
                        "nosso_numero" => (string) $lancamento->nosso_numero,
                        "codigo_ocorrencia" => $codigoOcorrencia,
                        "mensagem" =>
                            $mensagemOcorrencia !== ""
                                ? $mensagemOcorrencia
                                : "Título rejeitado pelo banco.",
                    ];
                    $log[] = $logItem;

                    $this->_persistirItemRetorno(
                        $retornoArquivo,
                        $lancamento,
                        $itemRemessa,
                        $registro,
                        $logItem,
                    );
                    continue;
                }

                $this->_registrarOcorrenciaInformativa(
                    $lancamento,
                    $codigoOcorrencia,
                    $mensagemOcorrencia,
                );
                $resumo["ignorados"]++;

                $logItem = [
                    "status" => "ignorado",
                    "titulo_id" => (int) $lancamento->id,
                    "nosso_numero" => (string) $lancamento->nosso_numero,
                    "codigo_ocorrencia" => $codigoOcorrencia,
                    "mensagem" =>
                        $mensagemOcorrencia !== ""
                            ? $mensagemOcorrencia
                            : "Ocorrência recebida sem baixa/rejeição automática.",
                ];
                $log[] = $logItem;

                $this->_persistirItemRetorno(
                    $retornoArquivo,
                    $lancamento,
                    $itemRemessa,
                    $registro,
                    $logItem,
                );
            } catch (\Throwable $e) {
                $resumo["erros"]++;
                $logItem = [
                    "status" => "erro",
                    "titulo_id" => (int) $lancamento->id,
                    "nosso_numero" => (string) $lancamento->nosso_numero,
                    "codigo_ocorrencia" => $codigoOcorrencia,
                    "mensagem" =>
                        "Falha ao aplicar retorno: " . $e->getMessage(),
                ];
                $log[] = $logItem;

                $this->_persistirItemRetorno(
                    $retornoArquivo,
                    $lancamento,
                    $itemRemessa,
                    $registro,
                    $logItem,
                );
            }
        }

        if ($retornoArquivo !== null) {
            $statusProcessamento = "processado";
            if (
                (int) $resumo["erros"] > 0 &&
                (int) $resumo["baixados"] === 0 &&
                (int) $resumo["rejeitados"] === 0 &&
                (int) $resumo["ignorados"] === 0
            ) {
                $statusProcessamento = "erro";
            } elseif ((int) $resumo["erros"] > 0) {
                $statusProcessamento = "processado_parcial";
            }

            $retornoArquivo = $this->FinanceiroRetornoArquivos->patchEntity(
                $retornoArquivo,
                [
                    "financeiro_banco_id" => $bancoDetectadoId,
                    "financeiro_remessa_id" => $remessaRelacionadaId,
                    "status_processamento" => $statusProcessamento,
                    "processados" => (int) $resumo["processados"],
                    "baixados" => (int) $resumo["baixados"],
                    "rejeitados" => (int) $resumo["rejeitados"],
                    "ignorados" => (int) $resumo["ignorados"],
                    "erros" => (int) $resumo["erros"],
                ],
            );
            $this->FinanceiroRetornoArquivos->save($retornoArquivo);
        }

        if (!empty($this->Atividades) && $usuarioId > 0) {
            try {
                $this->Atividades->registrar(
                    $usuarioId,
                    $this->request->getParam("controller"),
                    $this->request->getParam("action"),
                    !empty($retornoArquivo->id) ? (int) $retornoArquivo->id : 0,
                );
            } catch (\Exception $e) {
                // Não impede resposta.
            }
        }

        return $this->jsonResponse(
            [
                "ok" => true,
                "resumo" => $resumo,
                "log" => $log,
                "data" => [
                    "retorno_arquivo_id" => !empty($retornoArquivo->id)
                        ? (int) $retornoArquivo->id
                        : null,
                ],
            ],
            200,
        );
    }

    /**
     * Lê pares de segmentos T/U do CNAB 240.
     *
     * @param array $linhas
     * @return array<int,array<string,mixed>>
     */
    protected function _parseRetorno240(array $linhas)
    {
        $registros = [];
        $segmentoTAtual = null;

        foreach ($linhas as $linha) {
            $linha = rtrim((string) $linha);
            if (strlen($linha) < 20) {
                continue;
            }

            $tipoRegistro = substr($linha, 7, 1);
            if ($tipoRegistro !== "3") {
                continue;
            }

            $segmento = strtoupper(substr($linha, 13, 1));
            if ($segmento === "T") {
                $segmentoTAtual = $this->_parseSegmentoT($linha);
                continue;
            }

            if ($segmento === "U" && !empty($segmentoTAtual)) {
                $segmentoU = $this->_parseSegmentoU($linha);
                $registros[] = array_merge($segmentoTAtual, $segmentoU);
                $segmentoTAtual = null;
            }
        }

        return $registros;
    }

    /**
     * Parse simplificado do segmento T.
     *
     * @param string $linha
     * @return array<string,mixed>
     */
    protected function _parseSegmentoT($linha)
    {
        return [
            "banco_codigo" => trim(substr($linha, 0, 3)),
            "codigo_ocorrencia" => trim(substr($linha, 15, 2)),
            "nosso_numero" => $this->_limparNumero(substr($linha, 37, 20)),
            "numero_documento" => trim(substr($linha, 57, 15)),
            "valor_titulo" => $this->_parseDecimal(substr($linha, 81, 15)),
            "data_vencimento" => $this->_parseDataDdMmYyyy(
                substr($linha, 146, 8),
            ),
            "nome_pagador" => trim(substr($linha, 148, 40)),
        ];
    }

    /**
     * Parse simplificado do segmento U.
     *
     * @param string $linha
     * @return array<string,mixed>
     */
    protected function _parseSegmentoU($linha)
    {
        return [
            "valor_pago" => $this->_parseDecimal(substr($linha, 77, 15)),
            "valor_liquido" => $this->_parseDecimal(substr($linha, 92, 15)),
            "data_ocorrencia" => $this->_parseDataDdMmYyyy(
                substr($linha, 137, 8),
            ),
        ];
    }

    /**
     * Localiza o lançamento financeiro com base no retorno.
     *
     * @param int $idempresa
     * @param string $nossoNumero
     * @param int $bancoInformado
     * @param array $registro
     * @return \Cake\Datasource\EntityInterface|null
     */
    protected function _localizarLancamentoPorRetorno(
        $idempresa,
        $nossoNumero,
        $bancoInformado,
        array $registro
    ) {
        if ($nossoNumero === "") {
            return null;
        }

        $query = $this->FinanceiroLancamentos->find()->where([
            "FinanceiroLancamentos.idempresa" => (int) $idempresa,
            "FinanceiroLancamentos.nosso_numero" => $nossoNumero,
        ]);

        if ($bancoInformado > 0) {
            $query->where([
                "FinanceiroLancamentos.financeiro_banco_id" => $bancoInformado,
            ]);
        }

        $lancamento = $query
            ->order(["FinanceiroLancamentos.id" => "DESC"])
            ->first();

        if (!empty($lancamento)) {
            return $lancamento;
        }

        if (empty($this->FinanceiroRemessaTitulos)) {
            return null;
        }

        $remessaTitulo = $this->FinanceiroRemessaTitulos
            ->find()
            ->contain([
                "FinanceiroLancamentos" => function ($q) use (
                    $idempresa,
                    $bancoInformado
                ) {
                    $conditions = [
                        "FinanceiroLancamentos.idempresa" => (int) $idempresa,
                    ];
                    if ($bancoInformado > 0) {
                        $conditions[
                            "FinanceiroLancamentos.financeiro_banco_id"
                        ] = $bancoInformado;
                    }

                    return $q->where($conditions);
                },
            ])
            ->where([
                "FinanceiroRemessaTitulos.nosso_numero_remessa" => $nossoNumero,
            ])
            ->order(["FinanceiroRemessaTitulos.id" => "DESC"])
            ->first();

        if (!empty($remessaTitulo->financeiro_lancamento)) {
            return $remessaTitulo->financeiro_lancamento;
        }

        return null;
    }

    /**
     * Localiza o último item de remessa associado ao lançamento.
     *
     * @param int $lancamentoId
     * @return \Cake\Datasource\EntityInterface|null
     */
    protected function _localizarUltimoItemRemessaPorLancamento($lancamentoId)
    {
        if (
            empty($this->FinanceiroRemessaTitulos) ||
            (int) $lancamentoId <= 0
        ) {
            return null;
        }

        return $this->FinanceiroRemessaTitulos
            ->find()
            ->where([
                "FinanceiroRemessaTitulos.financeiro_lancamento_id" => (int) $lancamentoId,
            ])
            ->order(["FinanceiroRemessaTitulos.id" => "DESC"])
            ->first();
    }

    /**
     * Persiste o arquivo físico do retorno para rastreabilidade/auditoria.
     *
     * @param string $nomeOriginal
     * @param string $conteudo
     * @return array<string,string|null>
     */
    protected function _persistirArquivoRetornoUpload($nomeOriginal, $conteudo)
    {
        $nomeOriginal = trim((string) $nomeOriginal);
        if ($nomeOriginal === "" || trim((string) $conteudo) === "") {
            return [
                "nome_arquivo_salvo" => null,
                "caminho_arquivo" => null,
            ];
        }

        $ext = strtolower((string) pathinfo($nomeOriginal, PATHINFO_EXTENSION));
        if ($ext === "") {
            $ext = "ret";
        }

        $base =
            preg_replace(
                "/[^A-Za-z0-9._-]+/",
                "_",
                (string) pathinfo($nomeOriginal, PATHINFO_FILENAME),
            ) ?:
            "retorno";
        $nomeSalvo = sprintf("%s_%s.%s", $base, date("Ymd_His"), $ext);

        $caminhoRelativo = "files/retornos/" . $nomeSalvo;
        $caminhoFisico =
            WWW_ROOT . str_replace(["/", "\\"], DS, $caminhoRelativo);

        $folder = new Folder(dirname($caminhoFisico), true, 0755);
        unset($folder);

        @file_put_contents($caminhoFisico, $conteudo);

        if (!is_file($caminhoFisico)) {
            return [
                "nome_arquivo_salvo" => null,
                "caminho_arquivo" => null,
            ];
        }

        return [
            "nome_arquivo_salvo" => $nomeSalvo,
            "caminho_arquivo" => $caminhoRelativo,
        ];
    }

    /**
     * Cria o cabeçalho persistente do arquivo de retorno.
     *
     * @param array $dados
     * @return \Cake\Datasource\EntityInterface|null
     */
    protected function _criarCabecalhoRetornoArquivo(array $dados)
    {
        if (empty($this->FinanceiroRetornoArquivos)) {
            return null;
        }

        $entity = $this->FinanceiroRetornoArquivos->newEntity($dados);
        if (!$this->FinanceiroRetornoArquivos->save($entity)) {
            return null;
        }

        return $entity;
    }

    /**
     * Persiste um item de retorno processado no log detalhado.
     *
     * @param \Cake\Datasource\EntityInterface|null $retornoArquivo
     * @param \Cake\Datasource\EntityInterface|null $lancamento
     * @param \Cake\Datasource\EntityInterface|null $itemRemessa
     * @param array $registro
     * @param array $logItem
     * @return void
     */
    protected function _persistirItemRetorno(
        $retornoArquivo,
        $lancamento,
        $itemRemessa,
        array $registro,
        array $logItem
    ) {
        if (
            empty($this->FinanceiroRetornoItens) ||
            empty($retornoArquivo->id)
        ) {
            return;
        }

        $payloadJson = json_encode(
            [
                "registro" => $registro,
                "log" => $logItem,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $dataOcorrencia = Hash::get($registro, "data_ocorrencia");
        if ($dataOcorrencia instanceof \DateTimeInterface) {
            $dataOcorrencia = $dataOcorrencia->format("Y-m-d H:i:s");
        } elseif (empty($dataOcorrencia)) {
            $dataOcorrencia = null;
        }

        $dataVencimento = Hash::get($registro, "data_vencimento");
        if ($dataVencimento instanceof \DateTimeInterface) {
            $dataVencimento = $dataVencimento->format("Y-m-d");
        } elseif (empty($dataVencimento)) {
            $dataVencimento = null;
        }

        $entity = $this->FinanceiroRetornoItens->newEntity([
            "financeiro_retorno_arquivo_id" => (int) $retornoArquivo->id,
            "financeiro_lancamento_id" => !empty($lancamento->id)
                ? (int) $lancamento->id
                : null,
            "financeiro_remessa_id" => !empty(
                $itemRemessa->financeiro_remessa_id
            )
                ? (int) $itemRemessa->financeiro_remessa_id
                : null,
            "financeiro_remessa_titulo_id" => !empty($itemRemessa->id)
                ? (int) $itemRemessa->id
                : null,
            "status_item" => (string) Hash::get($logItem, "status", "ignorado"),
            "nosso_numero" => (string) Hash::get(
                $logItem,
                "nosso_numero",
                Hash::get($registro, "nosso_numero", ""),
            ),
            "numero_documento" => (string) Hash::get(
                $registro,
                "numero_documento",
                "",
            ),
            "codigo_ocorrencia" => (string) Hash::get(
                $logItem,
                "codigo_ocorrencia",
                Hash::get($registro, "codigo_ocorrencia", ""),
            ),
            "mensagem_ocorrencia" => (string) Hash::get(
                $logItem,
                "mensagem",
                "",
            ),
            "valor_titulo" => (float) Hash::get(
                $registro,
                "valor_titulo",
                !empty($lancamento->valor) ? (float) $lancamento->valor : 0,
            ),
            "valor_pago" => Hash::get(
                $logItem,
                "valor_pago",
                Hash::get($registro, "valor_pago"),
            ),
            "data_vencimento" => $dataVencimento,
            "data_ocorrencia" => $dataOcorrencia,
            "payload_json" => $payloadJson !== false ? $payloadJson : null,
        ]);

        $this->FinanceiroRetornoItens->save($entity);
    }

    /**
     * Aplica liquidação/baixa automática.
     *
     * @param \Cake\Datasource\EntityInterface $lancamento
     * @param float $valorPago
     * @param \DateTimeInterface|string|null $dataOcorrencia
     * @param string $codigoOcorrencia
     * @param string $mensagem
     * @return void
     */
    protected function _aplicarLiquidacao(
        $lancamento,
        $valorPago,
        $dataOcorrencia,
        $codigoOcorrencia,
        $mensagem
    ) {
        $lancamento->status_cobranca = "liquidado";
        $lancamento->codigo_rejeicao = null;
        $lancamento->mensagem_rejeicao = null;

        if ($dataOcorrencia instanceof \DateTimeInterface) {
            $lancamento->data_baixa = $dataOcorrencia->format("Y-m-d");
            $lancamento->data_recebimento = $dataOcorrencia->format("Y-m-d");
        } elseif (!empty($dataOcorrencia)) {
            $lancamento->data_baixa = $dataOcorrencia;
            $lancamento->data_recebimento = $dataOcorrencia;
        }

        $lancamento->valor_pago =
            $valorPago > 0 ? $valorPago : (float) $lancamento->valor;

        if ((string) $lancamento->tipo === "receita") {
            $lancamento->status = "recebido";
        } elseif ((string) $lancamento->tipo === "despesa") {
            $lancamento->status = "pago";
        }

        if (!$this->FinanceiroLancamentos->save($lancamento)) {
            throw new BadRequestException(
                "Não foi possível salvar a baixa automática do título.",
            );
        }

        $this->_atualizarUltimoItemRemessa($lancamento->id, [
            "status_item" => "baixado",
            "codigo_ocorrencia" => $codigoOcorrencia,
            "mensagem_ocorrencia" => $mensagem,
        ]);
    }

    /**
     * Aplica rejeição bancária.
     *
     * @param \Cake\Datasource\EntityInterface $lancamento
     * @param string $codigoOcorrencia
     * @param string $mensagem
     * @return void
     */
    protected function _aplicarRejeicao(
        $lancamento,
        $codigoOcorrencia,
        $mensagem
    ) {
        $lancamento->status_cobranca = "rejeitado";
        $lancamento->codigo_rejeicao =
            $codigoOcorrencia !== "" ? $codigoOcorrencia : null;
        $lancamento->mensagem_rejeicao =
            $mensagem !== ""
                ? $mensagem
                : "Título rejeitado no retorno bancário.";

        if (!$this->FinanceiroLancamentos->save($lancamento)) {
            throw new BadRequestException(
                "Não foi possível salvar a rejeição do título.",
            );
        }

        $this->_atualizarUltimoItemRemessa($lancamento->id, [
            "status_item" => "rejeitado",
            "codigo_ocorrencia" => $codigoOcorrencia,
            "mensagem_ocorrencia" => $mensagem,
        ]);
    }

    /**
     * Registra ocorrência não conclusiva.
     *
     * @param \Cake\Datasource\EntityInterface $lancamento
     * @param string $codigoOcorrencia
     * @param string $mensagem
     * @return void
     */
    protected function _registrarOcorrenciaInformativa(
        $lancamento,
        $codigoOcorrencia,
        $mensagem
    ) {
        if ($codigoOcorrencia !== "") {
            $lancamento->codigo_rejeicao = $codigoOcorrencia;
        }

        if ($mensagem !== "") {
            $lancamento->mensagem_rejeicao = $mensagem;
        }

        if (!$this->FinanceiroLancamentos->save($lancamento)) {
            throw new BadRequestException(
                "Não foi possível registrar a ocorrência informativa do retorno.",
            );
        }

        $this->_atualizarUltimoItemRemessa($lancamento->id, [
            "status_item" => "aceito",
            "codigo_ocorrencia" => $codigoOcorrencia,
            "mensagem_ocorrencia" => $mensagem,
        ]);
    }

    /**
     * Atualiza o último item de remessa relacionado ao lançamento, se a tabela existir.
     *
     * @param int $lancamentoId
     * @param array $dados
     * @return void
     */
    protected function _atualizarUltimoItemRemessa($lancamentoId, array $dados)
    {
        if (empty($this->FinanceiroRemessaTitulos)) {
            return;
        }

        $item = $this->FinanceiroRemessaTitulos
            ->find()
            ->where([
                "FinanceiroRemessaTitulos.financeiro_lancamento_id" => (int) $lancamentoId,
            ])
            ->order(["FinanceiroRemessaTitulos.id" => "DESC"])
            ->first();

        if (empty($item)) {
            return;
        }

        $item = $this->FinanceiroRemessaTitulos->patchEntity($item, $dados);
        $this->FinanceiroRemessaTitulos->save($item);
    }

    /**
     * Define se a ocorrência indica liquidação/baixa paga.
     *
     * @param string $codigoOcorrencia
     * @param float $valorPago
     * @return bool
     */
    protected function _isOcorrenciaLiquidada($codigoOcorrencia, $valorPago)
    {
        $liquidacoes = [
            "06", // liquidação normal
            "17", // liquidação após baixa
        ];

        return in_array($codigoOcorrencia, $liquidacoes, true) ||
            $valorPago > 0;
    }

    /**
     * Define se a ocorrência indica rejeição.
     *
     * @param string $codigoOcorrencia
     * @return bool
     */
    protected function _isOcorrenciaRejeitada($codigoOcorrencia)
    {
        $rejeicoes = ["03", "26", "30", "39", "44", "45"];

        return in_array($codigoOcorrencia, $rejeicoes, true);
    }

    /**
     * Traduz códigos de ocorrência em mensagens legíveis.
     *
     * @param string $codigoBanco
     * @param string $codigoOcorrencia
     * @return string
     */
    protected function _traduzirOcorrencia($codigoBanco, $codigoOcorrencia)
    {
        $mapaComum = [
            "02" => "Entrada confirmada.",
            "03" => "Entrada rejeitada.",
            "06" => "Liquidação normal confirmada.",
            "09" => "Baixado automaticamente via arquivo.",
            "10" => "Baixado conforme instrução do cedente.",
            "17" => "Liquidação após baixa ou instrução registrada.",
            "26" => "Dados do título inconsistentes para registro.",
            "30" => "Alteração ou instrução rejeitada pelo banco.",
            "39" => "Título com ocorrência impeditiva no banco.",
            "44" => "Nosso número inválido ou não localizado.",
            "45" => "Convênio, carteira ou conta incompatíveis.",
        ];

        $mapasPorBanco = [
            "001" => [
                "26" =>
                    "Banco do Brasil: rejeição por inconsistência cadastral do título.",
                "44" =>
                    "Banco do Brasil: nosso número inválido ou não encontrado.",
                "45" =>
                    "Banco do Brasil: convênio/carteira incompatíveis com a remessa.",
            ],
            "756" => [
                "26" => "Sicoob: título rejeitado por inconsistência de dados.",
                "44" => "Sicoob: nosso número inválido ou não localizado.",
                "45" =>
                    "Sicoob: convênio não permitido para este banco/carteira.",
            ],
        ];

        $codigoBanco = trim((string) $codigoBanco);
        $codigoOcorrencia = trim((string) $codigoOcorrencia);

        if ($codigoOcorrencia === "") {
            return "";
        }

        if (!empty($mapasPorBanco[$codigoBanco][$codigoOcorrencia])) {
            return $mapasPorBanco[$codigoBanco][$codigoOcorrencia];
        }

        return $mapaComum[$codigoOcorrencia] ??
            "Ocorrência bancária código " . $codigoOcorrencia . ".";
    }

    /**
     * Obtém tmp_name de upload tanto para array legado quanto UploadedFile.
     *
     * @param mixed $arquivo
     * @return string
     */
    protected function _arquivoTmpName($arquivo)
    {
        if (is_array($arquivo)) {
            return (string) Hash::get($arquivo, "tmp_name", "");
        }

        if (is_object($arquivo) && method_exists($arquivo, "getStream")) {
            try {
                $meta = $arquivo->getStream()->getMetadata();
                return (string) Hash::get((array) $meta, "uri", "");
            } catch (\Throwable $e) {
                return "";
            }
        }

        return "";
    }

    /**
     * Obtém nome original do upload.
     *
     * @param mixed $arquivo
     * @return string
     */
    protected function _arquivoClientName($arquivo)
    {
        if (is_array($arquivo)) {
            return (string) Hash::get($arquivo, "name", "");
        }

        if (
            is_object($arquivo) &&
            method_exists($arquivo, "getClientFilename")
        ) {
            return (string) $arquivo->getClientFilename();
        }

        return "";
    }

    /**
     * Converte valor numérico CNAB em decimal.
     *
     * @param string $valor
     * @return float
     */
    protected function _parseDecimal($valor)
    {
        $numero = $this->_limparNumero($valor);
        if ($numero === "") {
            return 0.0;
        }

        return ((float) $numero) / 100;
    }

    /**
     * Converte data ddmmaaaa para objeto DateTime ou null.
     *
     * @param string $valor
     * @return \DateTimeInterface|null
     */
    protected function _parseDataDdMmYyyy($valor)
    {
        $valor = preg_replace("/\D+/", "", (string) $valor) ?? "";
        if (strlen($valor) !== 8 || $valor === "00000000") {
            return null;
        }

        $dt = \DateTime::createFromFormat("dmY", $valor);
        if (!$dt instanceof \DateTime) {
            return null;
        }

        $dt->setTime(0, 0, 0);
        return $dt;
    }

    /**
     * Remove caracteres não numéricos.
     *
     * @param string $valor
     * @return string
     */
    protected function _limparNumero($valor)
    {
        return preg_replace("/\D+/", "", (string) $valor) ?? "";
    }
}
