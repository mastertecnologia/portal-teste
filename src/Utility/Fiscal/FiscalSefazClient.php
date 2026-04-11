<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * Cliente SOAP para comunicação com os Web Services da SEFAZ.
 *
 * Suporta NFe 4.00 e eventos (cancelamento, CCe, inutilização).
 */
class FiscalSefazClient {

    /** @var FiscalSigner */
    private $signer;

    /** @var array Configuração fiscal da empresa */
    private $config;

    /** @var int Ambiente (1=Produção, 2=Homologação) */
    private $ambiente;

    /** @var string Grupo de webservices (svrs, svan, etc.) */
    private $wsGroup;

    /** @var int Timeout SOAP */
    private $timeout;

    /** @var int Retentativas extra em falhas de rede (Fase 2) */
    private $retryMax;

    /** @var string Caminho temporário do PEM (certificado + chave) */
    private $tempCertPath;

    public function __construct(FiscalSigner $signer, array $config) {
        $this->signer = $signer;
        $this->config = $config;
        $this->ambiente = (int)($config['ambiente'] ?? 2);
        $this->wsGroup = 'svrs';
        $this->timeout = (int)Configure::read('Fiscal.soap_timeout', 30);
        $this->retryMax = (int)Configure::read('Fiscal.soap_retry_max', 0);
    }

    /**
     * Consulta status do serviço SEFAZ.
     */
    public function statusServico() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<consStatServ xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            . '<tpAmb>' . $this->ambiente . '</tpAmb>'
            . '<cUF>' . $this->getCodigoUF() . '</cUF>'
            . '<xServ>STATUS</xServ>'
            . '</consStatServ>';

        return $this->send('NfeStatusServico', $xml, 'nfeStatusServicoNF');
    }

    /**
     * Envia NFe para autorização (síncrono).
     */
    public function autorizar($xmlAssinado) {
        $builder = new FiscalXmlBuilder([], $this->config, [], []);
        $xmlLote = $builder->buildEnviNFe($xmlAssinado);
        return $this->send('NfeAutorizacao', $xmlLote, 'nfeAutorizacaoLote');
    }

    /**
     * Consulta protocolo de autorização.
     */
    public function consultarProtocolo($chaveAcesso) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<consSitNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            . '<tpAmb>' . $this->ambiente . '</tpAmb>'
            . '<xServ>CONSULTAR</xServ>'
            . '<chNFe>' . $chaveAcesso . '</chNFe>'
            . '</consSitNFe>';

        return $this->send('NfeConsultaProtocolo', $xml, 'nfeConsultaNF');
    }

    /**
     * Envia evento (cancelamento, carta de correção, etc.).
     */
    public function enviarEvento($xmlEventoAssinado) {
        return $this->send('RecepcaoEvento', $xmlEventoAssinado, 'nfeRecepcaoEvento');
    }

    /**
     * Consulta cadastro do contribuinte (CNPJ/IE) na SEFAZ.
     *
     * @param string $cnpjOuIe Documento (14 dígitos CNPJ ou IE)
     * @param string $uf UF do contribuinte (ex.: SP)
     * @param string $tipo 'cnpj' ou 'ie'
     * @return array
     */
    public function consultarCadastro($cnpjOuIe, $uf, $tipo = 'cnpj') {
        $doc = preg_replace('/\D/', '', (string)$cnpjOuIe);
        $tagDoc = ($tipo === 'ie') ? 'IE' : 'CNPJ';
        $cuf = Configure::read('Fiscal.ufs.' . strtoupper($uf));
        if (!$cuf) {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => 'VAL',
                'mensagem' => 'UF inválida: ' . $uf,
                'protocolo' => null,
                'cadastro' => [],
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<ConsCad xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            . '<infCons>'
            . '<xServ>CONS-CAD</xServ>'
            . '<UF>' . strtoupper($uf) . '</UF>'
            . '<' . $tagDoc . '>' . $doc . '</' . $tagDoc . '>'
            . '</infCons>'
            . '</ConsCad>';

        $url = $this->getServiceUrl('CadConsultaCadastro');
        if (!$url) {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => '999',
                'mensagem' => "URL do serviço 'CadConsultaCadastro' não configurada.",
                'protocolo' => null,
                'cadastro' => [],
            ];
        }

        $wsNs = 'http://www.portalfiscal.inf.br/nfe/wsdl/CadConsultaCadastro4';
        $result = $this->sendAtUrl($url, $xml, 'consultaCadastro', 'CadConsultaCadastro', $wsNs, [
            'nfe_cabec_msg' => true,
            'cabec_namespace' => $wsNs,
            'cabec_cuf' => (int)$cuf,
            'cabec_versao_dados' => '4.00',
        ]);
        $result['cadastro'] = $this->parseCadastroRetorno($result['xml_retorno'] ?? '');
        return $result;
    }

    /**
     * Extrai dados de cadastro do retorno da consulta.
     */
    private function parseCadastroRetorno($xmlRetorno) {
        $dados = [];
        if (empty($xmlRetorno)) {
            return $dados;
        }
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xmlRetorno)) {
            return $dados;
        }
        $infCad = $doc->getElementsByTagName('infCad');
        for ($i = 0; $i < $infCad->length; $i++) {
            $node = $infCad->item($i);
            $item = [];
            foreach (['CNPJ', 'IE', 'xNome', 'xFant', 'xRegApur', 'CNAE', 'dIniAtiv', 'dUltSit', 'cSit', 'indCredNFe', 'indCredCTe', 'UF'] as $tag) {
                $t = $node->getElementsByTagName($tag);
                $item[$tag] = ($t->length > 0) ? $t->item(0)->nodeValue : null;
            }
            $ender = $node->getElementsByTagName('ender');
            if ($ender->length > 0) {
                $e = $ender->item(0);
                foreach (['xLgr', 'nro', 'xCpl', 'xBairro', 'cMun', 'xMun', 'CEP'] as $eTag) {
                    $et = $e->getElementsByTagName($eTag);
                    $item[$eTag] = ($et->length > 0) ? $et->item(0)->nodeValue : null;
                }
            }
            $dados[] = $item;
        }
        return $dados;
    }

    /**
     * Envia inutilização de numeração.
     */
    public function inutilizar($xmlInutAssinado) {
        return $this->send('NfeInutilizacao', $xmlInutAssinado, 'nfeInutilizacaoNF');
    }

    /**
     * Distribuição DF-e (serviço nacional AN — consulta de documentos fiscais eletrônicos).
     *
     * @param string $cnpjInteressado CNPJ do destinatário (14 dígitos)
     * @param string $ultNsu Último NSU recebido (15 dígitos; use 0 na primeira consulta)
     * @return array Mesmo formato de send + ult_nsu, max_nsu, doc_zip_count quando aplicável
     */
    public function distribuicaoDfeInteresse($cnpjInteressado, $ultNsu = '0') {
        $cnpj = preg_replace('/\D/', '', (string)$cnpjInteressado);
        if (strlen($cnpj) !== 14) {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => 'VAL',
                'mensagem' => 'CNPJ do interessado inválido (14 dígitos).',
                'protocolo' => null,
                'ult_nsu' => null,
                'max_nsu' => null,
                'doc_zip_count' => 0,
            ];
        }
        $ultNsuLimpo = preg_replace('/\D/', '', (string)$ultNsu);
        $ultNsu15 = str_pad(substr($ultNsuLimpo, 0, 15), 15, '0', STR_PAD_LEFT);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<distDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">'
            . '<tpAmb>' . $this->ambiente . '</tpAmb>'
            . '<cUFAutor>91</cUFAutor>'
            . '<CNPJ>' . $cnpj . '</CNPJ>'
            . '<distNSU><ultNSU>' . $ultNsu15 . '</ultNSU></distNSU>'
            . '</distDFeInt>';

        $url = Configure::read('Fiscal.webservices.nacional.' . $this->ambiente . '.NFeDistribuicaoDFe');
        if (!is_string($url) || $url === '') {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => 'CFG',
                'mensagem' => 'URL NFeDistribuicaoDFe (nacional) não configurada.',
                'protocolo' => null,
                'ult_nsu' => null,
                'max_nsu' => null,
                'doc_zip_count' => 0,
            ];
        }

        return $this->sendAtUrl(
            $url,
            $xml,
            'nfeDistDFeInteresse',
            'NFeDistribuicaoDFe',
            'http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe'
        );
    }

    /**
     * Distribuição DF-e por chave de acesso (consChNFe) — obtém XML completo quando a AN disponibilizar (ex.: após manifestação).
     *
     * @param string $cnpjInteressado CNPJ do destinatário (14 dígitos)
     * @param string $chaveAcesso Chave da NF-e (44 dígitos)
     * @return array Mesmo formato de distribuicaoDfeInteresse()
     */
    public function distribuicaoDfePorChaveNfe($cnpjInteressado, $chaveAcesso) {
        $cnpj = preg_replace('/\D/', '', (string)$cnpjInteressado);
        if (strlen($cnpj) !== 14) {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => 'VAL',
                'mensagem' => 'CNPJ do interessado inválido (14 dígitos).',
                'protocolo' => null,
                'ult_nsu' => null,
                'max_nsu' => null,
                'doc_zip_count' => 0,
            ];
        }
        $ch = preg_replace('/\D/', '', (string)$chaveAcesso);
        if (strlen($ch) !== 44) {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => 'VAL',
                'mensagem' => 'Chave de acesso deve ter 44 dígitos.',
                'protocolo' => null,
                'ult_nsu' => null,
                'max_nsu' => null,
                'doc_zip_count' => 0,
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<distDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">'
            . '<tpAmb>' . $this->ambiente . '</tpAmb>'
            . '<cUFAutor>91</cUFAutor>'
            . '<CNPJ>' . $cnpj . '</CNPJ>'
            . '<consChNFe><chNFe>' . $ch . '</chNFe></consChNFe>'
            . '</distDFeInt>';

        $url = Configure::read('Fiscal.webservices.nacional.' . $this->ambiente . '.NFeDistribuicaoDFe');
        if (!is_string($url) || $url === '') {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => 'CFG',
                'mensagem' => 'URL NFeDistribuicaoDFe (nacional) não configurada.',
                'protocolo' => null,
                'ult_nsu' => null,
                'max_nsu' => null,
                'doc_zip_count' => 0,
            ];
        }

        return $this->sendAtUrl(
            $url,
            $xml,
            'nfeDistDFeInteresse',
            'NFeDistribuicaoDFe',
            'http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe'
        );
    }

    /**
     * Envia SOAP request para a SEFAZ.
     *
     * @param string $servico Nome do serviço (NfeAutorizacao, NfeStatusServico, etc.)
     * @param string $xml XML a enviar
     * @param string $method Método SOAP
     * @return array ['success' => bool, 'xml_retorno' => string, 'codigo' => string, 'mensagem' => string, 'protocolo' => string|null]
     */
    private function send($servico, $xml, $method) {
        $url = $this->getServiceUrl($servico);
        if (!$url) {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => '999',
                'mensagem' => "URL do serviço '{$servico}' não configurada.",
                'protocolo' => null,
                'ult_nsu' => null,
                'max_nsu' => null,
                'doc_zip_count' => 0,
            ];
        }

        return $this->sendAtUrl($url, $xml, $method, $servico, null);
    }

    /**
     * @param string $url URL base .asmx (sem ?WSDL)
     * @param string $xml Corpo interno (nfeDadosMsg)
     * @param string $method Nome do método SOAP (ex.: nfeAutorizacaoLote)
     * @param string $servico Rótulo para logs (ex.: NfeAutorizacao ou NFeDistribuicaoDFe)
     * @param string|null $nfeDadosMsgNs xmlns do envelope nfeDadosMsg; null = padrão por método
     * @param array        $soapExtras    nfe_cabec_msg, cabec_namespace, cabec_cuf, cabec_versao_dados (CadConsultaCadastro4)
     * @return array
     */
    private function sendAtUrl($url, $xml, $method, $servico, $nfeDadosMsgNs = null, array $soapExtras = []) {
        $maxAttempts = 1 + max(0, min(3, $this->retryMax));
        $delayMs = (int)Configure::read('Fiscal.soap_retry_delay_ms', 500);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                try {
                    $certPem = $this->prepareCertFile();

                    $context = stream_context_create([
                        'ssl' => [
                            'local_cert' => $certPem,
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                        'http' => [
                            'timeout' => $this->timeout,
                        ],
                    ]);

                    $soapParams = [
                        'encoding' => 'UTF-8',
                        'trace' => true,
                        'exceptions' => true,
                        'connection_timeout' => $this->timeout,
                        'stream_context' => $context,
                        'soap_version' => SOAP_1_2,
                        'style' => SOAP_DOCUMENT,
                        'use' => SOAP_LITERAL,
                    ];

                    $client = new \SoapClient($url . '?WSDL', $soapParams);

                    $msgNs = $nfeDadosMsgNs ?? ('http://www.portalfiscal.inf.br/nfe/wsdl/' . $method);
                    $params = new \SoapVar(
                        '<nfeDadosMsg xmlns="' . $msgNs . '">' . $xml . '</nfeDadosMsg>',
                        XSD_ANYXML
                    );

                    $inputHeaders = null;
                    if (!empty($soapExtras['nfe_cabec_msg'])) {
                        $hdrNs = (string)($soapExtras['cabec_namespace'] ?? $msgNs);
                        $cUFcab = (int)($soapExtras['cabec_cuf'] ?? 35);
                        $verDados = (string)($soapExtras['cabec_versao_dados'] ?? '4.00');
                        $cabecXml = '<nfeCabecMsg xmlns="' . htmlspecialchars($hdrNs, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '">'
                            . '<cUF>' . $cUFcab . '</cUF>'
                            . '<versaoDados>' . htmlspecialchars($verDados, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</versaoDados>'
                            . '</nfeCabecMsg>';
                        $cabecVar = new \SoapVar($cabecXml, XSD_ANYXML);
                        $inputHeaders = [
                            new \SoapHeader($hdrNs, 'nfeCabecMsg', $cabecVar, false),
                        ];
                    }

                    $response = $inputHeaders === null
                        ? $client->__soapCall($method, [$params])
                        : $client->__soapCall($method, [$params], null, $inputHeaders);

                    $xmlRetorno = '';
                    if ($response instanceof \stdClass) {
                        $xmlRetorno = $this->extractXmlFromResponse($response);
                    } elseif (is_string($response)) {
                        $xmlRetorno = $response;
                    }

                    $result = $this->parseRetorno($xmlRetorno);
                    $result['xml_retorno'] = $xmlRetorno;

                    return $result;
                } finally {
                    $this->cleanupCertFile();
                }
            } catch (\SoapFault $e) {
                Log::error(sprintf(
                    'SEFAZ SOAP Fault ambiente=%d servico=%s tentativa=%d/%d: %s',
                    $this->ambiente,
                    $servico,
                    $attempt,
                    $maxAttempts,
                    $e->getMessage()
                ));
                if ($attempt < $maxAttempts && self::isTransientSoapFault($e)) {
                    Log::warning(sprintf('SEFAZ retentativa (rede) servico=%s tentativa=%d', $servico, $attempt + 1));
                    usleep($delayMs * 1000);
                    continue;
                }

                return [
                    'success' => false,
                    'xml_retorno' => '',
                    'codigo' => 'SOAP_FAULT',
                    'mensagem' => $e->getMessage(),
                    'protocolo' => null,
                    'ult_nsu' => null,
                    'max_nsu' => null,
                    'doc_zip_count' => 0,
                ];
            } catch (\Exception $e) {
                Log::error(sprintf(
                    'SEFAZ Error ambiente=%d servico=%s tentativa=%d/%d: %s',
                    $this->ambiente,
                    $servico,
                    $attempt,
                    $maxAttempts,
                    $e->getMessage()
                ));
                if ($attempt < $maxAttempts && self::isTransientNetworkException($e)) {
                    usleep($delayMs * 1000);
                    continue;
                }

                return [
                    'success' => false,
                    'xml_retorno' => '',
                    'codigo' => 'ERROR',
                    'mensagem' => $e->getMessage(),
                    'protocolo' => null,
                    'ult_nsu' => null,
                    'max_nsu' => null,
                    'doc_zip_count' => 0,
                ];
            }
        }

        return [
            'success' => false,
            'xml_retorno' => '',
            'codigo' => 'ERROR',
            'mensagem' => 'Falha após retentativas SEFAZ.',
            'protocolo' => null,
            'ult_nsu' => null,
            'max_nsu' => null,
            'doc_zip_count' => 0,
        ];
    }

    /**
     * Apenas falhas típicas de rede/timeout — não retentar rejeição fiscal.
     */
    private static function isTransientSoapFault(\SoapFault $e) {
        return self::messageLooksTransient($e->getMessage());
    }

    private static function isTransientNetworkException(\Exception $e) {
        return self::messageLooksTransient($e->getMessage());
    }

    /**
     * @param string $message
     * @return bool
     */
    private static function messageLooksTransient($message) {
        $m = strtolower((string)$message);
        foreach (['timeout', 'timed out', 'connection', 'could not connect', 'failed to load', 'reset by peer', 'ssl', 'eof', 'temporar'] as $frag) {
            if (strpos($m, $frag) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtém URL do serviço.
     */
    private function getServiceUrl($servico) {
        $urls = Configure::read("Fiscal.webservices.{$this->wsGroup}.{$this->ambiente}");
        return $urls[$servico] ?? null;
    }

    /**
     * Prepara arquivo temporário PEM com cert + key.
     */
    private function prepareCertFile() {
        $tempDir = sys_get_temp_dir();
        $this->tempCertPath = $tempDir . DIRECTORY_SEPARATOR . 'pgm_fiscal_' . uniqid('', true) . '.pem';
        file_put_contents($this->tempCertPath, $this->signer->exportSslPemBundle());
        chmod($this->tempCertPath, 0600);
        return $this->tempCertPath;
    }

    /**
     * Remove arquivo temporário do certificado.
     */
    private function cleanupCertFile() {
        if ($this->tempCertPath && file_exists($this->tempCertPath)) {
            unlink($this->tempCertPath);
            $this->tempCertPath = null;
        }
    }

    /**
     * Extrai XML da resposta SOAP.
     */
    private function extractXmlFromResponse($response) {
        if (isset($response->nfeResultMsg)) {
            if ($response->nfeResultMsg instanceof \DOMDocument) {
                return $response->nfeResultMsg->saveXML();
            }
            return (string)$response->nfeResultMsg;
        }
        if (isset($response->nfeDistDFeInteresseResult)) {
            if ($response->nfeDistDFeInteresseResult instanceof \DOMDocument) {
                return $response->nfeDistDFeInteresseResult->saveXML();
            }

            return (string)$response->nfeDistDFeInteresseResult;
        }
        if (isset($response->consultaCadastroResult)) {
            if ($response->consultaCadastroResult instanceof \DOMDocument) {
                return $response->consultaCadastroResult->saveXML();
            }

            return (string)$response->consultaCadastroResult;
        }

        return json_encode($response);
    }

    /**
     * Faz parse do XML de retorno da SEFAZ.
     */
    private function parseRetorno($xmlRetorno) {
        $result = [
            'success' => false,
            'codigo' => '',
            'mensagem' => '',
            'protocolo' => null,
            'data_recebimento' => null,
            'chave_acesso' => null,
            'ult_nsu' => null,
            'max_nsu' => null,
            'doc_zip_count' => 0,
        ];

        if (empty($xmlRetorno)) {
            $result['mensagem'] = 'Retorno vazio da SEFAZ';
            return $result;
        }

        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xmlRetorno)) {
            $result['mensagem'] = 'XML de retorno inválido';
            return $result;
        }

        // Tenta extrair cStat e xMotivo de vários formatos de retorno
        $tags = ['retEnviNFe', 'retConsSitNFe', 'retConsStatServ', 'retInutNFe', 'retEvento', 'retEnvEvento', 'retDistDFeInt', 'retConsCad'];
        foreach ($tags as $tag) {
            $nodes = $doc->getElementsByTagName($tag);
            if ($nodes->length > 0) {
                $node = $nodes->item(0);
                $cStat = $this->getTagValue($node, 'cStat');
                $xMotivo = $this->getTagValue($node, 'xMotivo');
                $result['codigo'] = $cStat;
                $result['mensagem'] = $xMotivo;

                // Códigos de sucesso: 100 (autorizado), 101 (cancelado), 102 (inutilizado), 107 (serviço OK), 135 (evento registrado), 128 (lote processado)
                $result['success'] = in_array((int)$cStat, [100, 101, 102, 107, 128, 135]);

                if ($tag === 'retConsCad') {
                    // 111 = uma ocorrência; 112 = mais de uma (SVRS / manual consulta cadastro)
                    $result['success'] = in_array((int)$cStat, [111, 112], true);
                }

                if ($tag === 'retDistDFeInt') {
                    // 137 = sem documento; 138 = documento localizado (656 = consumo indevido — tratar como falha operacional)
                    $result['success'] = in_array((int)$cStat, [137, 138], true);
                    $result['ult_nsu'] = $this->getTagValue($node, 'ultNSU');
                    $result['max_nsu'] = $this->getTagValue($node, 'maxNSU');
                    $docZip = $doc->getElementsByTagName('docZip');
                    $result['doc_zip_count'] = $docZip->length;
                }

                if ($tag === 'retInutNFe') {
                    $infInutNodes = $node->getElementsByTagName('infInut');
                    if ($infInutNodes->length > 0) {
                        $nProt = $this->getTagValue($infInutNodes->item(0), 'nProt');
                        if ($nProt !== null && $nProt !== '') {
                            $result['protocolo'] = $nProt;
                        }
                    }
                }

                if (in_array($tag, ['retEvento', 'retEnvEvento'], true)) {
                    $infEv = $node->getElementsByTagName('infEvento');
                    if ($infEv->length > 0) {
                        $nProtEv = $this->getTagValue($infEv->item(0), 'nProt');
                        if ($nProtEv !== null && $nProtEv !== '') {
                            $result['protocolo'] = $nProtEv;
                        }
                    }
                }

                $protNFe = $doc->getElementsByTagName('protNFe');
                if ($protNFe->length > 0) {
                    $infProt = $protNFe->item(0)->getElementsByTagName('infProt');
                    if ($infProt->length > 0) {
                        $prot = $infProt->item(0);
                        $result['protocolo'] = $this->getTagValue($prot, 'nProt');
                        $result['data_recebimento'] = $this->getTagValue($prot, 'dhRecbto');
                        $result['chave_acesso'] = $this->getTagValue($prot, 'chNFe');
                        $cStatProt = $this->getTagValue($prot, 'cStat');
                        if ($cStatProt) {
                            $result['codigo'] = $cStatProt;
                            $result['mensagem'] = $this->getTagValue($prot, 'xMotivo') ?: $xMotivo;
                            $result['success'] = in_array((int)$cStatProt, [100, 101, 102, 135]);
                        }
                    }
                }
                break;
            }
        }

        return $result;
    }

    private function getTagValue(\DOMElement $node, $tagName) {
        $tags = $node->getElementsByTagName($tagName);
        if ($tags->length > 0) {
            return $tags->item(0)->nodeValue;
        }
        return null;
    }

    private function getCodigoUF() {
        $uf = $this->config['uf'] ?? 'SP';
        return Configure::read('Fiscal.ufs.' . $uf) ?? '35';
    }
}
