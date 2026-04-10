<?php
namespace App\Utility\Fiscal;

use App\Utility\ErpGridUrl;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Sincronização de notas fiscais com o ERP (WebGridPGM) via SOAP.
 *
 * O ERP mantém registos de faturação; esta classe envia dados de notas autorizadas
 * e recebe dados de produtos/clientes para vincular.
 *
 * Webservices utilizados:
 * - WSFiscal.wso — endpoint fiscal dedicado (se disponível no ERP)
 * - WsProdutos.wso — fallback para vincular produtos
 */
class FiscalErpSync {

    const WSDL_FISCAL = 'WSFiscal.wso?wsdl';

    /**
     * Envia nota fiscal autorizada para o ERP.
     *
     * @param int $notaId
     * @param int $idempresa
     * @return array{ok: bool, message: string, erp_id?: string}
     */
    public static function enviarNotaParaErp(int $notaId, int $idempresa): array {
        $FiscalNotas = TableRegistry::getTableLocator()->get('FiscalNotas');
        $Empresas = TableRegistry::getTableLocator()->get('Empresas');

        $nota = $FiscalNotas->find()
            ->contain([
                'Clientes',
                'FiscalNotasItens',
                'FiscalNotasPagamentos',
            ])
            ->where(['FiscalNotas.id' => $notaId, 'FiscalNotas.idempresa' => $idempresa])
            ->first();

        if (!$nota) {
            return ['ok' => false, 'message' => 'Nota não encontrada.'];
        }
        if ($nota->status !== 'autorizada') {
            return ['ok' => false, 'message' => 'Apenas notas autorizadas podem ser sincronizadas com o ERP.'];
        }

        try {
            $empresa = $Empresas->get($idempresa);
            $urlErp = $empresa->urlerp ?? null;
            $wsdl = ErpGridUrl::wsdl($urlErp, self::WSDL_FISCAL);

            $payload = self::buildPayload($nota, $empresa);

            $soap = new \CakeSoap(['wsdl' => $wsdl]);
            $response = $soap->call('IncluirNotaFiscal', $payload);

            $erpId = null;
            if (is_object($response) && isset($response->id)) {
                $erpId = (string)$response->id;
            } elseif (is_array($response) && isset($response['id'])) {
                $erpId = (string)$response['id'];
            }

            // Gravar referência ERP na nota
            if ($erpId !== null) {
                $nota->erp_referencia = $erpId;
                $FiscalNotas->save($nota);
            }

            FiscalAudit::write('info', 'erp_sync_enviado', [
                'nota_id' => $notaId,
                'idempresa' => $idempresa,
                'erp_id' => $erpId,
            ]);

            return ['ok' => true, 'message' => 'Nota sincronizada com o ERP.', 'erp_id' => $erpId];

        } catch (\SoapFault $e) {
            Log::warning('FiscalErpSync SOAP: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Erro SOAP ERP: ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::warning('FiscalErpSync: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Erro na sincronização: ' . $e->getMessage()];
        }
    }

    /**
     * Consulta status de uma nota no ERP.
     *
     * @param string $chaveAcesso
     * @param int $idempresa
     * @return array{ok: bool, message: string, dados?: array}
     */
    public static function consultarNotaErp(string $chaveAcesso, int $idempresa): array {
        try {
            $Empresas = TableRegistry::getTableLocator()->get('Empresas');
            $empresa = $Empresas->get($idempresa);
            $wsdl = ErpGridUrl::wsdl($empresa->urlerp ?? null, self::WSDL_FISCAL);

            $soap = new \CakeSoap(['wsdl' => $wsdl]);
            $response = $soap->call('ConsultarNotaFiscal', [
                'chaveAcesso' => $chaveAcesso,
            ]);

            $dados = [];
            if (is_object($response)) {
                $dados = json_decode(json_encode($response), true) ?: [];
            } elseif (is_array($response)) {
                $dados = $response;
            }

            return ['ok' => true, 'message' => 'Consulta realizada.', 'dados' => $dados];

        } catch (\SoapFault $e) {
            return ['ok' => false, 'message' => 'Erro SOAP: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Importa produtos do ERP para vincular em notas fiscais.
     *
     * @param int $idempresa
     * @return array{ok: bool, message: string, produtos?: array}
     */
    public static function importarProdutosErp(int $idempresa): array {
        try {
            $Empresas = TableRegistry::getTableLocator()->get('Empresas');
            $empresa = $Empresas->get($idempresa);
            $wsdl = ErpGridUrl::wsdl($empresa->urlerp ?? null, ErpGridUrl::WSDL_PRODUTOS);

            $soap = new \CakeSoap(['wsdl' => $wsdl]);
            $response = $soap->call('ListarProdutos', [
                'idempresa' => $idempresa,
            ]);

            $produtos = [];
            if (is_object($response) && isset($response->produtos)) {
                $produtos = json_decode(json_encode($response->produtos), true) ?: [];
            } elseif (is_array($response)) {
                $produtos = $response;
            }

            return ['ok' => true, 'message' => count($produtos) . ' produto(s) obtido(s).', 'produtos' => $produtos];

        } catch (\SoapFault $e) {
            return ['ok' => false, 'message' => 'Erro SOAP: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Monta payload SOAP para enviar ao ERP.
     */
    private static function buildPayload($nota, $empresa): array {
        $itens = [];
        foreach ($nota->fiscal_notas_itens ?? [] as $item) {
            $itens[] = [
                'numero_item' => (int)$item->numero_item,
                'codigo_produto' => (string)($item->codigo_produto ?? ''),
                'descricao' => (string)($item->descricao ?? ''),
                'ncm' => (string)($item->ncm ?? ''),
                'cfop' => (string)($item->cfop ?? ''),
                'quantidade' => (float)($item->quantidade ?? 0),
                'valor_unitario' => (float)($item->valor_unitario ?? 0),
                'valor_total' => (float)($item->valor_total ?? 0),
            ];
        }

        $pagamentos = [];
        foreach ($nota->fiscal_notas_pagamentos ?? [] as $pg) {
            $pagamentos[] = [
                'forma' => (string)($pg->forma_pagamento ?? ''),
                'valor' => (float)($pg->valor ?? 0),
            ];
        }

        return [
            'idempresa' => (int)$empresa->id,
            'cnpj_emitente' => preg_replace('/\D/', '', (string)($empresa->cnpj ?? '')),
            'numero' => (int)($nota->numero ?? 0),
            'serie' => (int)($nota->serie ?? 0),
            'modelo' => (string)($nota->modelo ?? '55'),
            'chave_acesso' => (string)($nota->chave_acesso ?? ''),
            'protocolo' => (string)($nota->protocolo_autorizacao ?? ''),
            'tipo_operacao' => (int)($nota->tipo_operacao ?? 1),
            'natureza_operacao' => (string)($nota->natureza_operacao ?? ''),
            'data_emissao' => $nota->data_emissao ? $nota->data_emissao->format('Y-m-d H:i:s') : '',
            'valor_produtos' => (float)($nota->valor_produtos ?? 0),
            'valor_total' => (float)($nota->valor_total ?? 0),
            'valor_icms' => (float)($nota->valor_icms ?? 0),
            'valor_pis' => (float)($nota->valor_pis ?? 0),
            'valor_cofins' => (float)($nota->valor_cofins ?? 0),
            'cnpj_destinatario' => $nota->cliente ? preg_replace('/\D/', '', (string)($nota->cliente->cnpj ?? '')) : '',
            'nome_destinatario' => $nota->cliente ? (string)($nota->cliente->razaosocial ?? $nota->cliente->nome ?? '') : '',
            'itens' => $itens,
            'pagamentos' => $pagamentos,
        ];
    }
}
