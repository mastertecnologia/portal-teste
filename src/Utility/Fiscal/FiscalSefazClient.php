<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * Cliente SOAP 1.2 para comunicacao com os Web Services da SEFAZ.
 *
 * Usa cURL com envelope SOAP 1.2 montado manualmente (padrao das bibliotecas
 * NFe brasileiras — sped-nfe, nfe-api) para controle total sobre namespaces,
 * versoes e mTLS com certificado A1.
 *
 * Suporta NFe 4.00 e eventos (cancelamento, CCe, inutilizacao).
 */
class FiscalSefazClient {

    /** @var FiscalSigner */
    private $signer;

    /** @var array Configuracao fiscal da empresa */
    private $config;

    /** @var int Ambiente (1=Producao, 2=Homologacao) */
    private $ambiente;

    /** @var string Grupo de webservices (svrs, svan, etc.) */
    private $wsGroup;

    /** @var int Timeout SOAP */
    private $timeout;

    /** @var int Retentativas extra em falhas de rede */
    private $retryMax;

    /** @var string|null Caminho temporario do PEM (certificado + chave) */
    private $tempCertPath;

    /**
     * Mapa servico → [metodo SOAP, namespace WSDL].
     * Usado quando o chamador nao fornece namespace explicitamente.
     */
    private static $SERVICE_MAP = [
        'NfeAutorizacao'       => ['nfeAutorizacaoLote',   'http://www.portalfiscal.inf.br/nfe/wsdl/NFeAutorizacao4'],
        'NfeRetAutorizacao'    => ['nfeRetAutorizacaoLote', 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeRetAutorizacao4'],
        'NfeConsultaProtocolo' => ['nfeConsultaNF',         'http://www.portalfiscal.inf.br/nfe/wsdl/NFeConsultaProtocolo4'],
        'NfeStatusServico'     => ['nfeStatusServicoNF',    'http://www.portalfiscal.inf.br/nfe/wsdl/NFeStatusServico4'],
        'NfeInutilizacao'      => ['nfeInutilizacaoNF',     'http://www.portalfiscal.inf.br/nfe/wsdl/NFeInutilizacao4'],
        'RecepcaoEvento'       => ['nfeRecepcaoEvento',     'http://www.portalfiscal.inf.br/nfe/wsdl/NFeRecepcaoEvento4'],
        'CadConsultaCadastro'  => ['consultaCadastro',      'http://www.portalfiscal.inf.br/nfe/wsdl/CadConsultaCadastro4'],
        'NFeDistribuicaoDFe'   => ['nfeDistDFeInteresse',   'http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe'],
    ];

    public function __construct(FiscalSigner $signer, array $config) {
        $this->signer = $signer;
        $this->config = $config;
        $this->ambiente = (int)($config['ambiente'] ?? 2);
        $this->wsGroup = 'svrs';
        $this->timeout = (int)Configure::read('Fiscal.soap_timeout', 30);
        $this->retryMax = (int)Configure::read('Fiscal.soap_retry_max', 0);
    }

    // ── Servicos publicos ────────────────────────────────────────────

    /**
     * Consulta status do servico SEFAZ.
     */
    public function statusServico() {
        $xml = '<consStatServ xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            . '<tpAmb>' . $this->ambiente . '</tpAmb>'
            . '<cUF>' . $this->getCodigoUF() . '</cUF>'
            . '<xServ>STATUS</xServ>'
            . '</consStatServ>';

        return $this->send('NfeStatusServico', $xml, 'nfeStatusServicoNF');
    }

    /**
     * Envia NFe para autorizacao (sincrono).
     */
    public function autorizar($xmlAssinado) {
        $builder = new FiscalXmlBuilder([], $this->config, [], []);
        $xmlLote = $builder->buildEnviNFe($xmlAssinado);
        return $this->send('NfeAutorizacao', $xmlLote, 'nfeAutorizacaoLote');
    }

    /**
     * Consulta protocolo de autorizacao.
     */
    public function consultarProtocolo($chaveAcesso) {
        $xml = '<consSitNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            . '<tpAmb>' . $this->ambiente . '</tpAmb>'
            . '<xServ>CONSULTAR</xServ>'
            . '<chNFe>' . $chaveAcesso . '</chNFe>'
            . '</consSitNFe>';

        return $this->send('NfeConsultaProtocolo', $xml, 'nfeConsultaNF');
    }

    /**
     * Envia evento (cancelamento, carta de correcao, etc.).
     */
    public function enviarEvento($xmlEventoAssinado) {
        return $this->send('RecepcaoEvento', $xmlEventoAssinado, 'nfeRecepcaoEvento');
    }

    /**
     * Consulta cadastro do contribuinte (CNPJ/IE) na SEFAZ.
     *
     * @param string $cnpjOuIe Documento (14 digitos CNPJ ou IE)
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
                'mensagem' => 'UF invalida: ' . $uf,
                'protocolo' => null,
                'cadastro' => [],
            ];
        }

        // ConsCad usa versao 2.00 (consCad_v2.00.xsd), NAO a versao da NFe
        $xml = '<ConsCad xmlns="http://www.portalfiscal.inf.br/nfe" versao="2.00">'
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
                'mensagem' => "URL do servico 'CadConsultaCadastro' nao configurada.",
                'protocolo' => null,
                'cadastro' => [],
            ];
        }

        $wsNs = 'http://www.portalfiscal.inf.br/nfe/wsdl/CadConsultaCadastro4';
        // CadConsultaCadastro4: sem nfeCabecMsg no SOAP (layout legado v2); corpo ConsCad permanece versao 2.00 (XSD consCad).
        $result = $this->sendAtUrl($url, $xml, 'consultaCadastro', 'CadConsultaCadastro', $wsNs);
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
     * Envia inutilizacao de numeracao.
     */
    public function inutilizar($xmlInutAssinado) {
        return $this->send('NfeInutilizacao', $xmlInutAssinado, 'nfeInutilizacaoNF');
    }

    /**
     * Distribuicao DF-e (servico nacional AN).
     *
     * @param string $cnpjInteressado CNPJ do destinatario (14 digitos)
     * @param string $ultNsu Ultimo NSU recebido (15 digitos; use 0 na primeira consulta)
     * @return array
     */
    public function distribuicaoDfeInteresse($cnpjInteressado, $ultNsu = '0') {
        $cnpj = preg_replace('/\D/', '', (string)$cnpjInteressado);
        if (strlen($cnpj) !== 14) {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => 'VAL',
                'mensagem' => 'CNPJ do interessado invalido (14 digitos).',
                'protocolo' => null,
                'ult_nsu' => null,
                'max_nsu' => null,
                'doc_zip_count' => 0,
            ];
        }
        $ultNsuLimpo = preg_replace('/\D/', '', (string)$ultNsu);
        $ultNsu15 = str_pad(substr($ultNsuLimpo, 0, 15), 15, '0', STR_PAD_LEFT);

        $xml = '<distDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">'
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
                'mensagem' => 'URL NFeDistribuicaoDFe (nacional) nao configurada.',
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
     * Distribuicao DF-e por chave de acesso (consChNFe).
     *
     * @param string $cnpjInteressado CNPJ do destinatario (14 digitos)
     * @param string $chaveAcesso Chave da NF-e (44 digitos)
     * @return array
     */
    public function distribuicaoDfePorChaveNfe($cnpjInteressado, $chaveAcesso) {
        $cnpj = preg_replace('/\D/', '', (string)$cnpjInteressado);
        if (strlen($cnpj) !== 14) {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => 'VAL',
                'mensagem' => 'CNPJ do interessado invalido (14 digitos).',
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
                'mensagem' => 'Chave de acesso deve ter 44 digitos.',
                'protocolo' => null,
                'ult_nsu' => null,
                'max_nsu' => null,
                'doc_zip_count' => 0,
            ];
        }

        $xml = '<distDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">'
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
                'mensagem' => 'URL NFeDistribuicaoDFe (nacional) nao configurada.',
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

    // ── Transporte SOAP 1.2 via cURL ─────────────────────────────────

    /**
     * Envelope SOAP 1.2 (Body apenas). XML interno em nfeDadosMsg sem CDATA — alinhado ao SoapClient / ASMX SVRS.
     */
    private function buildSoap12Envelope($method, $wsdlNs, $xmlClean) {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
            . ' xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'
            . '<soap12:Body>'
            . '<nfeDadosMsg xmlns="' . $wsdlNs . '">' . $xmlClean . '</nfeDadosMsg>'
            . '</soap12:Body>'
            . '</soap12:Envelope>';
    }

    /**
     * Envia SOAP request para a SEFAZ (rota interna via getServiceUrl).
     *
     * @param string $servico Nome do servico (NfeAutorizacao, NfeStatusServico, etc.)
     * @param string $xml XML a enviar (corpo interno, sem XML declaration)
     * @param string $method Metodo SOAP
     * @return array
     */
    private function send($servico, $xml, $method) {
        $url = $this->getServiceUrl($servico);
        if (!$url) {
            return [
                'success' => false,
                'xml_retorno' => '',
                'codigo' => '999',
                'mensagem' => "URL do servico '{$servico}' nao configurada.",
                'protocolo' => null,
                'ult_nsu' => null,
                'max_nsu' => null,
                'doc_zip_count' => 0,
            ];
        }

        return $this->sendAtUrl($url, $xml, $method, $servico);
    }

    /**
     * Envia SOAP 1.2 via cURL com mTLS (certificado A1).
     *
     * Monta o envelope SOAP manualmente para controle total sobre namespaces
     * e versoes, evitando problemas do SoapClient com WSDL + XSD_ANYXML.
     *
     * @param string      $url           URL do servico .asmx (sem ?WSDL)
     * @param string      $xml           Corpo interno (nfeDadosMsg), sem <?xml ...?>
     * @param string      $method        Nome do metodo SOAP (ex.: nfeStatusServicoNF)
     * @param string      $servico       Rotulo para logs e lookup (ex.: NfeStatusServico)
     * @param string|null $nfeDadosMsgNs xmlns do wrapper; null = lookup em SERVICE_MAP
     * @return array
     */
    private function sendAtUrl($url, $xml, $method, $servico, $nfeDadosMsgNs = null) {
        $maxAttempts = 1 + max(0, min(3, $this->retryMax));
        $delayMs = (int)Configure::read('Fiscal.soap_retry_delay_ms', 500);

        // Resolve namespace WSDL
        $wsdlNs = $nfeDadosMsgNs;
        if (!$wsdlNs && isset(self::$SERVICE_MAP[$servico])) {
            $wsdlNs = self::$SERVICE_MAP[$servico][1];
        }
        if (!$wsdlNs) {
            $wsdlNs = 'http://www.portalfiscal.inf.br/nfe/wsdl/' . $servico;
        }
        $soapAction = $wsdlNs . '/' . $method;

        // Remove XML declaration do conteudo interno (nao pode estar dentro do envelope SOAP)
        $xmlClean = preg_replace('/<\?xml[^?]*\?\>\s*/', '', $xml);

        $envelope = $this->buildSoap12Envelope($method, $wsdlNs, $xmlClean);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $certPem = $this->prepareCertFile();
                $certSize = @filesize($certPem);
                if ($certSize === false || $certSize < 100) {
                    throw new \RuntimeException('Certificado PEM temporario invalido ou vazio para mTLS.');
                }
                try {
                    $ch = curl_init($url);
                    if (!$ch) {
                        throw new \RuntimeException('Falha ao inicializar cURL.');
                    }
                    $curlOpts = [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => $envelope,
                        CURLOPT_HTTPHEADER     => [
                            'Content-Type: application/soap+xml;charset=UTF-8;action="' . $soapAction . '"',
                            'Content-Length: ' . strlen($envelope),
                        ],
                        CURLOPT_SSLCERT        => $certPem,
                        CURLOPT_SSLKEY         => $certPem,
                        CURLOPT_TIMEOUT        => $this->timeout,
                        CURLOPT_CONNECTTIMEOUT => $this->timeout,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => 0,
                    ];
                    if (defined('CURLOPT_SSLCERTTYPE')) {
                        $curlOpts[CURLOPT_SSLCERTTYPE] = 'PEM';
                    }
                    curl_setopt_array($ch, $curlOpts);

                    $response = curl_exec($ch);
                    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    $curlErrno = (int)curl_errno($ch);
                    curl_close($ch);

                    if ($response === false || $curlErrno) {
                        throw new \RuntimeException(
                            'cURL erro ' . $curlErrno . ': ' . ($curlError ?: 'Sem resposta do servidor')
                        );
                    }

                    Log::debug(sprintf(
                        'SEFAZ cURL servico=%s HTTP=%d bytes=%d',
                        $servico, $httpCode, strlen($response)
                    ));

                    // Verifica SOAP Fault
                    $faultMsg = $this->extractSoapFault($response);
                    if ($faultMsg) {
                        throw new \RuntimeException('SEFAZ SOAP Fault: ' . $faultMsg);
                    }
                    if ($httpCode >= 500) {
                        throw new \RuntimeException(
                            'HTTP ' . $httpCode . ': ' . mb_substr($response, 0, 300)
                        );
                    }

                    // Extrai XML de retorno do envelope SOAP
                    $xmlRetorno = $this->extractXmlFromSoapResponse($response);

                    $result = $this->parseRetorno($xmlRetorno);
                    $result['xml_retorno'] = $xmlRetorno;
                    return $result;
                } finally {
                    $this->cleanupCertFile();
                }
            } catch (\Exception $e) {
                Log::error(sprintf(
                    'SEFAZ Error ambiente=%d servico=%s tentativa=%d/%d: %s',
                    $this->ambiente,
                    $servico,
                    $attempt,
                    $maxAttempts,
                    $e->getMessage()
                ));
                if ($attempt < $maxAttempts && self::isTransientException($e)) {
                    Log::warning(sprintf('SEFAZ retentativa servico=%s tentativa=%d', $servico, $attempt + 1));
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
            'mensagem' => 'Falha apos retentativas SEFAZ.',
            'protocolo' => null,
            'ult_nsu' => null,
            'max_nsu' => null,
            'doc_zip_count' => 0,
        ];
    }

    // ── Parse da resposta SOAP ───────────────────────────────────────

    /**
     * Extrai o XML de retorno de dentro do envelope SOAP.
     *
     * Estrutura esperada:
     *   soap:Envelope > soap:Body > {method}Response > {method}Result > <retXxx ...>
     */
    private function extractXmlFromSoapResponse($soapXml) {
        if (empty($soapXml)) {
            return '';
        }

        $doc = new \DOMDocument();
        if (!@$doc->loadXML($soapXml)) {
            return '';
        }

        // Localiza soap:Body (SOAP 1.2 ou 1.1)
        $body = $doc->getElementsByTagNameNS('http://www.w3.org/2003/05/soap-envelope', 'Body')->item(0);
        if (!$body) {
            $body = $doc->getElementsByTagNameNS('http://schemas.xmlsoap.org/soap/envelope/', 'Body')->item(0);
        }
        if (!$body) {
            return '';
        }

        // Navega: Body > {method}Response > {method}Result > conteudo XML
        foreach ($body->childNodes as $responseNode) {
            if ($responseNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            foreach ($responseNode->childNodes as $resultNode) {
                if ($resultNode->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                // Constroi XML a partir dos filhos do nó Result
                $innerParts = [];
                foreach ($resultNode->childNodes as $child) {
                    $innerParts[] = $doc->saveXML($child);
                }
                return trim(implode('', $innerParts));
            }
        }

        return '';
    }

    /**
     * Extrai mensagem de erro de um SOAP Fault, ou '' se nao houver fault.
     */
    private function extractSoapFault($soapXml) {
        if (empty($soapXml)) {
            return '';
        }
        // Verificacao rapida antes de parsear DOM
        if (strpos($soapXml, 'Fault') === false) {
            return '';
        }

        $doc = new \DOMDocument();
        if (!@$doc->loadXML($soapXml)) {
            return '';
        }

        // SOAP 1.2: <env:Fault><env:Reason><env:Text>...</env:Text></env:Reason>
        $reason = $doc->getElementsByTagNameNS('http://www.w3.org/2003/05/soap-envelope', 'Reason');
        if ($reason->length > 0) {
            return trim($reason->item(0)->textContent);
        }

        // SOAP 1.1: <Fault><faultstring>...</faultstring>
        $faultstring = $doc->getElementsByTagName('faultstring');
        if ($faultstring->length > 0) {
            return trim($faultstring->item(0)->textContent);
        }

        return '';
    }

    /**
     * Apenas falhas tipicas de rede/timeout — nao retentar rejeicao fiscal.
     */
    private static function isTransientException(\Exception $e) {
        $m = strtolower((string)$e->getMessage());
        foreach (['timeout', 'timed out', 'connection', 'could not connect', 'reset by peer', 'curl erro', 'eof', 'temporar'] as $frag) {
            if (strpos($m, $frag) !== false) {
                return true;
            }
        }
        return false;
    }

    // ── Parse retorno SEFAZ (XML interno) ────────────────────────────

    /**
     * Faz parse do XML de retorno da SEFAZ (conteudo dentro do SOAP).
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
            $result['mensagem'] = 'XML de retorno invalido';
            return $result;
        }

        $tags = ['retEnviNFe', 'retConsSitNFe', 'retConsStatServ', 'retInutNFe', 'retEvento', 'retEnvEvento', 'retDistDFeInt', 'retConsCad'];
        foreach ($tags as $tag) {
            $nodes = $doc->getElementsByTagName($tag);
            if ($nodes->length > 0) {
                $node = $nodes->item(0);
                $cStat = $this->getTagValue($node, 'cStat');
                $xMotivo = $this->getTagValue($node, 'xMotivo');
                $result['codigo'] = $cStat;
                $result['mensagem'] = $xMotivo;

                // Codigos de sucesso: 100 (autorizado), 101 (cancelado), 102 (inutilizado), 107 (servico OK), 135 (evento registrado), 128 (lote processado)
                $result['success'] = in_array((int)$cStat, [100, 101, 102, 107, 128, 135]);

                if ($tag === 'retConsCad') {
                    // 111 = uma ocorrencia; 112 = mais de uma
                    $result['success'] = in_array((int)$cStat, [111, 112], true);
                }

                if ($tag === 'retDistDFeInt') {
                    // 137 = sem documento; 138 = documento localizado
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

    // ── Helpers ──────────────────────────────────────────────────────

    private function getServiceUrl($servico) {
        $urls = Configure::read("Fiscal.webservices.{$this->wsGroup}.{$this->ambiente}");
        return $urls[$servico] ?? null;
    }

    /**
     * Prepara arquivo temporario PEM com cert + key para cURL mTLS.
     */
    private function prepareCertFile() {
        $tempDir = sys_get_temp_dir();
        $this->tempCertPath = $tempDir . DIRECTORY_SEPARATOR . 'pgm_fiscal_' . uniqid('', true) . '.pem';
        file_put_contents($this->tempCertPath, $this->signer->exportSslPemBundle());
        chmod($this->tempCertPath, 0600);
        return $this->tempCertPath;
    }

    private function cleanupCertFile() {
        if ($this->tempCertPath && file_exists($this->tempCertPath)) {
            unlink($this->tempCertPath);
            $this->tempCertPath = null;
        }
    }

    private function getCodigoUF() {
        $uf = $this->config['uf'] ?? 'SP';
        return Configure::read('Fiscal.ufs.' . $uf) ?? '35';
    }
}
