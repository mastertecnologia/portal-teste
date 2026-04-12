<?php
namespace App\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Core\Configure;

/**
 * Testa a construcao do envelope SOAP 1.2 do FiscalSefazClient
 * sem efetivamente chamar a SEFAZ (mock do cURL nao necessario,
 * validamos XML/envelope via reflection).
 */
class FiscalSefazClientSoapTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
        Configure::write('Fiscal', Configure::read('Fiscal') ?: []);
        Configure::write('Fiscal.soap_timeout', 10);
        Configure::write('Fiscal.soap_retry_max', 0);
        Configure::write('Fiscal.ufs', [
            'SP' => 35, 'RJ' => 33, 'MG' => 31, 'RS' => 43,
        ]);
        Configure::write('Fiscal.webservices.svrs', [
            2 => [
                'NfeStatusServico'    => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                'CadConsultaCadastro' => 'https://cad-homologacao.svrs.rs.gov.br/ws/cadconsultacadastro/cadconsultacadastro4.asmx',
                'NfeConsultaProtocolo'=> 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
            ],
        ]);
    }

    /**
     * Verifica que o ConsCad usa versao 2.00 (consCad_v2.00.xsd).
     */
    public function testConsCadUsesVersion200() {
        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $source = file_get_contents($class->getFileName());

        // O XML <ConsCad> DEVE usar versao="2.00", NUNCA 4.00
        $this->assertStringContainsString(
            '<ConsCad xmlns="http://www.portalfiscal.inf.br/nfe" versao="2.00">',
            $source,
            'ConsCad deve usar versao 2.00 (consCad_v2.00.xsd)'
        );
        $this->assertStringNotContainsString(
            '<ConsCad xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">',
            $source,
            'ConsCad NAO deve usar versao 4.00'
        );
    }

    /**
     * Verifica que consStatServ usa versao 4.00.
     */
    public function testConsStatServUsesVersion400() {
        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $source = file_get_contents($class->getFileName());

        $this->assertStringContainsString(
            '<consStatServ xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">',
            $source
        );
    }

    /**
     * Verifica que distDFeInt usa versao 1.01.
     */
    public function testDistDfeIntUsesVersion101() {
        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $source = file_get_contents($class->getFileName());

        $this->assertStringContainsString(
            '<distDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">',
            $source
        );
    }

    /**
     * Verifica que os XMLs internos NAO contem <?xml ...?> declaration.
     *
     * O sendAtUrl faz strip automatico, mas os metodos publicos tambem
     * nao devem emitir a declaration (melhoria de clareza).
     */
    public function testNoXmlDeclarationInInnerXml() {
        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $source = file_get_contents($class->getFileName());

        // Deve haver <?xml...?> APENAS no envelope SOAP, nao nos metodos statusServico/consultarCadastro/etc.
        // Contamos ocorrencias: 1 no envelope (sendAtUrl) e 0 nos builders
        $matches = [];
        preg_match_all('/<\?xml\s[^?]*\?>/', $source, $matches);
        $count = count($matches[0]);
        // Deve ter exatamente 1 ocorrencia (no envelope SOAP dentro de sendAtUrl)
        $this->assertEquals(1, $count, 'Deve haver exatamente 1 <?xml?> declaration (no envelope SOAP)');
    }

    /**
     * Verifica que o SERVICE_MAP contem todas as entradas necessarias.
     */
    public function testServiceMapCompleteness() {
        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $prop = $class->getProperty('SERVICE_MAP');
        $prop->setAccessible(true);
        $map = $prop->getValue();

        $expectedServices = [
            'NfeAutorizacao', 'NfeRetAutorizacao', 'NfeConsultaProtocolo',
            'NfeStatusServico', 'NfeInutilizacao', 'RecepcaoEvento',
            'CadConsultaCadastro', 'NFeDistribuicaoDFe',
        ];

        foreach ($expectedServices as $svc) {
            $this->assertArrayHasKey($svc, $map, "SERVICE_MAP deve conter '$svc'");
            $this->assertCount(2, $map[$svc], "SERVICE_MAP[$svc] deve ter [method, namespace]");
            $this->assertStringStartsWith('http://www.portalfiscal.inf.br/nfe/wsdl/', $map[$svc][1]);
        }
    }

    /**
     * Envelope: nfeDadosMsg com XML cru (sem CDATA), sem Header — padrao Cad4 / status SVRS.
     */
    public function testBuildSoap12EnvelopeEmbedsRawXmlInNfeDadosMsg() {
        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $method = $class->getMethod('buildSoap12Envelope');
        $method->setAccessible(true);

        $signerStub = $this->createMock(\App\Utility\Fiscal\FiscalSigner::class);
        $instance = new \App\Utility\Fiscal\FiscalSefazClient($signerStub, ['ambiente' => 2, 'uf' => 'SP']);

        $inner = '<consStatServ xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            . '<tpAmb>2</tpAmb><cUF>35</cUF><xServ>STATUS</xServ></consStatServ>';
        $ns = 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeStatusServico4';
        $env = $method->invoke($instance, 'nfeStatusServicoNF', $ns, $inner);

        $this->assertStringNotContainsString('<soap12:Header>', $env);
        $this->assertStringNotContainsString('<![CDATA[', $env);
        $this->assertStringContainsString('<nfeDadosMsg>' . $inner . '</nfeDadosMsg>', $env);
    }

    /**
     * Testa extractXmlFromSoapResponse com resposta real.
     */
    public function testExtractXmlFromSoapResponse() {
        $soapResponse = '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
            . '<soap:Body>'
            . '<nfeStatusServicoNFResponse xmlns="http://www.portalfiscal.inf.br/nfe/wsdl/NFeStatusServico4">'
            . '<nfeStatusServicoNFResult>'
            . '<retConsStatServ versao="4.00" xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<tpAmb>2</tpAmb>'
            . '<verAplic>SVRS202406171005</verAplic>'
            . '<cStat>107</cStat>'
            . '<xMotivo>Servico em Operacao</xMotivo>'
            . '<cUF>43</cUF>'
            . '<dhRecbto>2024-06-20T15:30:00-03:00</dhRecbto>'
            . '<tMed>1</tMed>'
            . '</retConsStatServ>'
            . '</nfeStatusServicoNFResult>'
            . '</nfeStatusServicoNFResponse>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $method = $class->getMethod('extractXmlFromSoapResponse');
        $method->setAccessible(true);

        // Precisamos de uma instancia dummy (nao sera usada para SOAP real)
        $signerStub = $this->createMock(\App\Utility\Fiscal\FiscalSigner::class);
        $instance = new \App\Utility\Fiscal\FiscalSefazClient($signerStub, ['ambiente' => 2, 'uf' => 'SP']);

        $xmlRetorno = $method->invoke($instance, $soapResponse);

        $this->assertNotEmpty($xmlRetorno, 'Deve extrair XML do envelope SOAP');
        $this->assertStringContainsString('<retConsStatServ', $xmlRetorno);
        $this->assertStringContainsString('<cStat>107</cStat>', $xmlRetorno);
        $this->assertStringContainsString('Servico em Operacao', $xmlRetorno);
        $this->assertStringNotContainsString('soap:Envelope', $xmlRetorno);
    }

    /**
     * Testa extractSoapFault com Fault SOAP 1.2.
     */
    public function testExtractSoapFault12() {
        $faultXml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">'
            . '<soap:Body>'
            . '<soap:Fault>'
            . '<soap:Code><soap:Value>soap:Sender</soap:Value></soap:Code>'
            . '<soap:Reason><soap:Text xml:lang="pt-BR">Mensagem SOAP invalida</soap:Text></soap:Reason>'
            . '</soap:Fault>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $method = $class->getMethod('extractSoapFault');
        $method->setAccessible(true);

        $signerStub = $this->createMock(\App\Utility\Fiscal\FiscalSigner::class);
        $instance = new \App\Utility\Fiscal\FiscalSefazClient($signerStub, ['ambiente' => 2, 'uf' => 'SP']);

        $fault = $method->invoke($instance, $faultXml);
        $this->assertStringContainsString('Mensagem SOAP invalida', $fault);
    }

    /**
     * Testa extractXmlFromSoapResponse com resposta de ConsCad.
     */
    public function testExtractConsCadResponse() {
        $soapResponse = '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">'
            . '<soap:Body>'
            . '<consultaCadastroResponse xmlns="http://www.portalfiscal.inf.br/nfe/wsdl/CadConsultaCadastro4">'
            . '<consultaCadastroResult>'
            . '<retConsCad versao="2.00" xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<infCons>'
            . '<verAplic>RS20240101100000</verAplic>'
            . '<cStat>111</cStat>'
            . '<xMotivo>Consulta cadastro com uma ocorrencia</xMotivo>'
            . '<UF>SP</UF>'
            . '<infCad><CNPJ>12345678000190</CNPJ><IE>123456789</IE><xNome>Empresa Teste</xNome></infCad>'
            . '</infCons>'
            . '</retConsCad>'
            . '</consultaCadastroResult>'
            . '</consultaCadastroResponse>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $method = $class->getMethod('extractXmlFromSoapResponse');
        $method->setAccessible(true);

        $signerStub = $this->createMock(\App\Utility\Fiscal\FiscalSigner::class);
        $instance = new \App\Utility\Fiscal\FiscalSefazClient($signerStub, ['ambiente' => 2, 'uf' => 'SP']);

        $xmlRetorno = $method->invoke($instance, $soapResponse);

        $this->assertStringContainsString('<retConsCad', $xmlRetorno);
        $this->assertStringContainsString('<cStat>111</cStat>', $xmlRetorno);
        $this->assertStringContainsString('versao="2.00"', $xmlRetorno);
    }

    /**
     * Testa parseRetorno com retConsStatServ (status servico).
     */
    public function testParseRetornoStatusServico() {
        $xml = '<retConsStatServ versao="4.00" xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<tpAmb>2</tpAmb>'
            . '<verAplic>SVRS202406171005</verAplic>'
            . '<cStat>107</cStat>'
            . '<xMotivo>Servico em Operacao</xMotivo>'
            . '<cUF>43</cUF>'
            . '<dhRecbto>2024-06-20T15:30:00-03:00</dhRecbto>'
            . '<tMed>1</tMed>'
            . '</retConsStatServ>';

        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $method = $class->getMethod('parseRetorno');
        $method->setAccessible(true);

        $signerStub = $this->createMock(\App\Utility\Fiscal\FiscalSigner::class);
        $instance = new \App\Utility\Fiscal\FiscalSefazClient($signerStub, ['ambiente' => 2, 'uf' => 'SP']);

        $result = $method->invoke($instance, $xml);

        $this->assertTrue($result['success']);
        $this->assertEquals('107', $result['codigo']);
        $this->assertStringContainsString('Operacao', $result['mensagem']);
    }

    /**
     * Testa parseRetorno com retConsCad (consulta cadastro).
     */
    public function testParseRetornoConsCad() {
        $xml = '<retConsCad versao="2.00" xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<infCons>'
            . '<verAplic>RS20240101100000</verAplic>'
            . '<cStat>111</cStat>'
            . '<xMotivo>Consulta cadastro com uma ocorrencia</xMotivo>'
            . '<UF>SP</UF>'
            . '<infCad><CNPJ>12345678000190</CNPJ><IE>123456789</IE><xNome>Empresa Teste</xNome></infCad>'
            . '</infCons>'
            . '</retConsCad>';

        $class = new \ReflectionClass(\App\Utility\Fiscal\FiscalSefazClient::class);
        $method = $class->getMethod('parseRetorno');
        $method->setAccessible(true);

        $signerStub = $this->createMock(\App\Utility\Fiscal\FiscalSigner::class);
        $instance = new \App\Utility\Fiscal\FiscalSefazClient($signerStub, ['ambiente' => 2, 'uf' => 'SP']);

        $result = $method->invoke($instance, $xml);

        $this->assertTrue($result['success']);
        $this->assertEquals('111', $result['codigo']);
    }
}
