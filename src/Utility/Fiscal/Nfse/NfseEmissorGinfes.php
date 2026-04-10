<?php
declare(strict_types=1);

namespace App\Utility\Fiscal\Nfse;

use Cake\Core\Configure;

/**
 * NFS-e via padrão **GINFES / GISS Online** (SOAP), layout ABRASF 2.04 ou lote v3 conforme `Fiscal.nfse_ginfes.mode`.
 *
 * Piloto documental: município **Itu/SP** (IBGE 3523909), URLs tipo `https://ws-itu.giss.com.br/service-ws/nf/nfse-ws?wsdl`
 * (homologação: `v2-ws-homologacao.giss.com.br`). Confirmar WSDL e certificado com a prefeitura.
 *
 * @see docs/FISCAL_NFSE_PILOTO.md
 */
class NfseEmissorGinfes implements NfseEmissorInterface {

    public function emitir(array $nota, array $config, array $empresa): array {
        $wsdl = trim((string)(Configure::read('Fiscal.nfse_ginfes.wsdl') ?: (getenv('NFSE_GINFES_WSDL') ?: '')));
        if ($wsdl === '') {
            return [
                'success' => false,
                'mensagem' => 'NFS-e GINFES/GISS: defina Fiscal.nfse_ginfes.wsdl ou a variável de ambiente NFSE_GINFES_WSDL. '
                    . 'Consulte docs/FISCAL_NFSE_PILOTO.md (piloto Itu/SP, IBGE 3523909).',
            ];
        }

        $im = preg_replace('/\D/', '', (string)($config['inscricao_municipal'] ?? ''));
        if ($im === '') {
            return [
                'success' => false,
                'mensagem' => 'Inscrição municipal do prestador é obrigatória para NFS-e (configuração fiscal da empresa).',
            ];
        }

        $cnpjPrest = preg_replace('/\D/', '', (string)($empresa['cnpj'] ?? ''));
        if (strlen($cnpjPrest) !== 14) {
            return ['success' => false, 'mensagem' => 'CNPJ do emitente inválido para NFS-e.'];
        }

        $mode = strtolower(trim((string)Configure::read('Fiscal.nfse_ginfes.mode', 'recepcionar_lote_v3')));

        try {
            $soapOpts = $this->soapOptions();
            if ($mode === 'abrasf_gerar_nfse') {
                return $this->emitirGerarNfseAbrasf($wsdl, $soapOpts, $nota, $config, $empresa, $cnpjPrest, $im);
            }

            return $this->emitirRecepcionarLoteV3($wsdl, $soapOpts, $nota, $config, $empresa, $cnpjPrest, $im);
        } catch (\SoapFault $e) {
            return ['success' => false, 'mensagem' => '[GINFES/GISS SOAP] ' . $e->getMessage()];
        } catch (\Throwable $e) {
            return ['success' => false, 'mensagem' => '[GINFES/GISS] ' . $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function soapOptions(): array {
        $opts = [
            'soap_version' => SOAP_1_2,
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => (int)Configure::read('Fiscal.soap_timeout', 30),
        ];
        $pem = trim((string)(Configure::read('Fiscal.nfse_ginfes.local_cert_pem') ?: (getenv('NFSE_GINFES_LOCAL_CERT_PEM') ?: '')));
        if ($pem !== '' && @is_readable($pem)) {
            $opts['stream_context'] = stream_context_create([
                'ssl' => [
                    'local_cert' => $pem,
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);
        }

        return $opts;
    }

    /**
     * Operação típica GINFES v3 / GISS (arg0 cabecalho, arg1 XML lote CDATA).
     *
     * @param array<string, mixed> $nota
     * @param array<string, mixed> $config
     * @param array<string, mixed> $empresa
     * @return array{success:bool, protocolo?:string, mensagem?:string}
     */
    private function emitirRecepcionarLoteV3(
        string $wsdl,
        array $soapOpts,
        array $nota,
        array $config,
        array $empresa,
        string $cnpjPrest,
        string $im
    ): array {
        $client = new \SoapClient($wsdl, $soapOpts);
        $op = trim((string)Configure::read('Fiscal.nfse_ginfes.soap_operation', 'RecepcionarLoteRpsV3'));
        $cabec = '<ns2:cabecalho versao="3" xmlns:ns2="http://www.ginfes.com.br/cabecalho_v03.xsd">'
            . '<versaoDados>3</versaoDados></ns2:cabecalho>';
        $dados = $this->montarEnviarLoteRpsEnvioV03($nota, $config, $empresa, $cnpjPrest, $im);

        $response = $client->__soapCall($op, [
            new \SoapParam($cabec, 'arg0'),
            new \SoapParam($dados, 'arg1'),
        ]);

        return $this->interpretarRespostaSoap($client, $response);
    }

    /**
     * Operação estilo WEBISS / nfse.abrasf.org.br (GerarNfseRequest).
     *
     * @param array<string, mixed> $nota
     * @param array<string, mixed> $config
     * @param array<string, mixed> $empresa
     * @return array{success:bool, protocolo?:string, mensagem?:string}
     */
    private function emitirGerarNfseAbrasf(
        string $wsdl,
        array $soapOpts,
        array $nota,
        array $config,
        array $empresa,
        string $cnpjPrest,
        string $im
    ): array {
        $client = new \SoapClient($wsdl, $soapOpts);
        $cabec = '<?xml version="1.0" encoding="UTF-8"?><cabecalho versao="2.04" xmlns="http://www.abrasf.org.br/nfse.xsd">'
            . '<versaoDados>2.04</versaoDados></cabecalho>';
        $dados = $this->montarGerarNfseEnvio204($nota, $config, $empresa, $cnpjPrest, $im);

        $response = $client->__soapCall('GerarNfse', [
            new \SoapParam($cabec, 'nfseCabecMsg'),
            new \SoapParam($dados, 'nfseDadosMsg'),
        ]);

        return $this->interpretarRespostaSoap($client, $response);
    }

    /**
     * @param array<string, mixed> $nota
     * @param array<string, mixed> $config
     * @param array<string, mixed> $empresa
     */
    private function montarEnviarLoteRpsEnvioV03(
        array $nota,
        array $config,
        array $empresa,
        string $cnpjPrest,
        string $im
    ): string {
        $serie = (string)($nota['serie'] ?? '1');
        $numero = (string)($nota['numero'] ?? '1');
        $valor = number_format((float)($nota['valor_total'] ?? 0), 2, '.', '');
        $data = !empty($nota['data_emissao']) ? date('Y-m-d', strtotime((string)$nota['data_emissao'])) : date('Y-m-d');
        $tom = $this->tomadorXml($nota);
        $disc = $this->xmlEsc((string)($nota['natureza_operacao'] ?? 'Serviço'));
        $codMun = preg_replace('/\D/', '', (string)($config['codigo_municipio_ibge'] ?? ''));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<EnviarLoteRpsEnvio xmlns="http://www.ginfes.com.br/tipos_v03.xsd">'
            . '<LoteRps Id="L1" versao="3">'
            . '<NumeroLote>1</NumeroLote>'
            . '<CpfCnpj><Cnpj>' . $cnpjPrest . '</Cnpj></CpfCnpj>'
            . '<InscricaoMunicipal>' . $this->xmlEsc($im) . '</InscricaoMunicipal>'
            . '<QuantidadeRps>1</QuantidadeRps>'
            . '<ListaRps><Rps>'
            . '<InfDeclaracaoPrestacaoServico Id="Rps' . $this->xmlEsc($numero) . '">'
            . '<Rps><IdentificacaoRps><Numero>' . $this->xmlEsc($numero) . '</Numero><Serie>' . $this->xmlEsc($serie) . '</Serie><Tipo>1</Tipo></IdentificacaoRps>'
            . '<DataEmissao>' . $data . '</DataEmissao></Rps>'
            . '<Servico><Valores><ValorServicos>' . $valor . '</ValorServicos></Valores>'
            . '<ItemListaServico>01.01</ItemListaServico>'
            . '<CodigoMunicipio>' . ($codMun !== '' ? $this->xmlEsc($codMun) : '3523909') . '</CodigoMunicipio>'
            . '<Discriminacao>' . $disc . '</Discriminacao></Servico>'
            . '<Prestador><CpfCnpj><Cnpj>' . $cnpjPrest . '</Cnpj></CpfCnpj>'
            . '<InscricaoMunicipal>' . $this->xmlEsc($im) . '</InscricaoMunicipal></Prestador>'
            . $tom
            . '</InfDeclaracaoPrestacaoServico>'
            . '</Rps></ListaRps></LoteRps></EnviarLoteRpsEnvio>';
    }

    /**
     * @param array<string, mixed> $nota
     * @param array<string, mixed> $config
     * @param array<string, mixed> $empresa
     */
    private function montarGerarNfseEnvio204(
        array $nota,
        array $config,
        array $empresa,
        string $cnpjPrest,
        string $im
    ): string {
        $serie = (string)($nota['serie'] ?? '1');
        $numero = (string)($nota['numero'] ?? '1');
        $valor = number_format((float)($nota['valor_total'] ?? 0), 2, '.', '');
        $data = !empty($nota['data_emissao']) ? date('Y-m-d', strtotime((string)$nota['data_emissao'])) : date('Y-m-d');
        $tom = $this->tomadorXml($nota);
        $disc = $this->xmlEsc((string)($nota['natureza_operacao'] ?? 'Serviço'));
        $codMun = preg_replace('/\D/', '', (string)($config['codigo_municipio_ibge'] ?? ''));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<GerarNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">'
            . '<Rps><InfDeclaracaoPrestacaoServico Id="rps' . $this->xmlEsc($numero) . '">'
            . '<Rps><IdentificacaoRps><Numero>' . $this->xmlEsc($numero) . '</Numero><Serie>' . $this->xmlEsc($serie) . '</Serie><Tipo>1</Tipo></IdentificacaoRps>'
            . '<DataEmissao>' . $data . '</DataEmissao></Rps>'
            . '<Servico><Valores><ValorServicos>' . $valor . '</ValorServicos></Valores>'
            . '<ItemListaServico>01.01</ItemListaServico>'
            . '<CodigoMunicipio>' . ($codMun !== '' ? $this->xmlEsc($codMun) : '3523909') . '</CodigoMunicipio>'
            . '<Discriminacao>' . $disc . '</Discriminacao></Servico>'
            . '<Prestador><CpfCnpj><Cnpj>' . $cnpjPrest . '</Cnpj></CpfCnpj>'
            . '<InscricaoMunicipal>' . $this->xmlEsc($im) . '</InscricaoMunicipal></Prestador>'
            . $tom
            . '</InfDeclaracaoPrestacaoServico></Rps>'
            . '</GerarNfseEnvio>';
    }

    /**
     * @param array<string, mixed> $nota
     */
    private function tomadorXml(array $nota): string {
        $cli = $nota['cliente'] ?? [];
        if (!is_array($cli)) {
            $cli = [];
        }
        $doc = preg_replace('/\D/', '', (string)($cli['cnpj'] ?? $cli['cpf'] ?? ''));
        if (strlen($doc) === 14) {
            return '<TomadorServico><IdentificacaoTomador><CpfCnpj><Cnpj>' . $doc . '</Cnpj></CpfCnpj></IdentificacaoTomador>'
                . '<RazaoSocial>' . $this->xmlEsc((string)($cli['razaosocial'] ?? $cli['nome'] ?? 'Tomador')) . '</RazaoSocial></TomadorServico>';
        }
        if (strlen($doc) === 11) {
            return '<TomadorServico><IdentificacaoTomador><CpfCnpj><Cpf>' . $doc . '</Cpf></CpfCnpj></IdentificacaoTomador>'
                . '<RazaoSocial>' . $this->xmlEsc((string)($cli['nome'] ?? $cli['razaosocial'] ?? 'Tomador')) . '</RazaoSocial></TomadorServico>';
        }

        return '<TomadorServico><RazaoSocial>Consumidor</RazaoSocial></TomadorServico>';
    }

    /**
     * @param mixed $response
     * @return array{success:bool, protocolo?:string, mensagem?:string}
     */
    private function interpretarRespostaSoap(\SoapClient $client, $response): array {
        $raw = $client->__getLastResponse();
        if (is_string($raw) && (stripos($raw, 'ListaNfse') !== false || stripos($raw, 'CompNfse') !== false || stripos($raw, 'NumeroNfse') !== false)) {
            if (preg_match('/<Numero>(\d+)<\/Numero>/', $raw, $m)) {
                return ['success' => true, 'protocolo' => $m[1], 'mensagem' => 'NFS-e processada (ver XML de retorno).'];
            }

            return ['success' => true, 'protocolo' => '', 'mensagem' => 'Resposta contém NFS-e; conferir XML retornado.'];
        }
        if (is_string($raw) && (stripos($raw, 'Fault') !== false || stripos($raw, 'MensagemRetorno') !== false || stripos($raw, 'Codigo') !== false)) {
            return ['success' => false, 'mensagem' => $this->extrairTrechoResposta($raw)];
        }

        return ['success' => false, 'mensagem' => 'Resposta SOAP não reconhecida: ' . $this->extrairTrechoResposta($raw)];
    }

    private function extrairTrechoResposta(string $raw): string {
        $raw = preg_replace('/\s+/', ' ', $raw);

        return substr((string)$raw, 0, 900);
    }

    private function xmlEsc(string $s): string {
        return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
