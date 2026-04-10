<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalCalculator;
use App\Utility\Fiscal\FiscalDfeDocZipSummary;
use PHPUnit\Framework\TestCase;

class FiscalDfeDocZipSummaryTest extends TestCase {

    public function testResumeUmDocZipComResNFe(): void {
        $chave = FiscalCalculator::gerarChaveAcesso('35', '2401', '11222333000181', '55', '1', '3', '1', '12345678');
        $inner = '<?xml version="1.0" encoding="UTF-8"?><resNFe xmlns="http://www.portalfiscal.inf.br/nfe"><chNFe>' . $chave . '</chNFe></resNFe>';
        $gz = gzencode($inner);
        $this->assertNotFalse($gz);
        $b64 = base64_encode($gz);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<retDistDFeInt xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<docZip schema="resNFe_v1.00">' . $b64 . '</docZip>'
            . '</retDistDFeInt>';

        $sum = FiscalDfeDocZipSummary::summarizeRetornoXml($xml);
        $this->assertSame(1, $sum['doc_count']);
        $this->assertCount(1, $sum['items']);
        $this->assertSame($chave, $sum['items'][0]['chave']);
        $this->assertSame('resNFe', $sum['items'][0]['tipo']);
    }

    public function testExtractDocumentosParaIngestIncluiXmlPlain(): void {
        $chave = FiscalCalculator::gerarChaveAcesso('35', '2401', '11222333000181', '55', '1', '4', '1', '87654321');
        $inner = '<?xml version="1.0" encoding="UTF-8"?><resNFe xmlns="http://www.portalfiscal.inf.br/nfe"><chNFe>' . $chave . '</chNFe></resNFe>';
        $b64 = base64_encode((string)gzencode($inner));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<retDistDFeInt xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<docZip schema="resNFe_v1.00" NSU="42">' . $b64 . '</docZip>'
            . '</retDistDFeInt>';

        $docs = FiscalDfeDocZipSummary::extractDocumentosParaIngest($xml);
        $this->assertCount(1, $docs);
        $this->assertSame('42', $docs[0]['nsu']);
        $this->assertSame($chave, $docs[0]['chave']);
        $this->assertStringContainsString('<chNFe>', $docs[0]['xml_plain']);
    }

    public function testPrimeiroDocumentoComInfNFeIgnoraResNFeELeNfeProc(): void {
        $emitCnpj = '11222333000181';
        $destCnpj = '99888777000166';
        $chave = FiscalCalculator::gerarChaveAcesso('35', '2401', $emitCnpj, '55', '1', '5', '1', '44444444');
        $innerRes = '<?xml version="1.0" encoding="UTF-8"?><resNFe xmlns="http://www.portalfiscal.inf.br/nfe"><chNFe>' . $chave . '</chNFe></resNFe>';
        $innerProc = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<NFe><infNFe Id="NFe' . $chave . '">'
            . '<ide><mod>55</mod><serie>1</serie><nNF>5</nNF><dhEmi>2024-01-15T10:00:00-03:00</dhEmi></ide>'
            . '<emit><CNPJ>' . $emitCnpj . '</CNPJ><xNome>E</xNome></emit>'
            . '<dest><CNPJ>' . $destCnpj . '</CNPJ><xNome>D</xNome></dest>'
            . '<det nItem="1"><prod><cProd>P</cProd><xProd>X</xProd><NCM>12345678</NCM><CFOP>1102</CFOP>'
            . '<uCom>UN</uCom><qCom>1</qCom><vUnCom>2</vUnCom><vProd>2</vProd></prod></det>'
            . '<total><ICMSTot><vProd>2</vProd><vNF>2</vNF></ICMSTot></total>'
            . '</infNFe></NFe>'
            . '<protNFe><infProt><chNFe>' . $chave . '</chNFe><nProt>1</nProt></infProt></protNFe>'
            . '</nfeProc>';
        $b64a = base64_encode((string)gzencode($innerRes));
        $b64b = base64_encode((string)gzencode($innerProc));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<retDistDFeInt xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<docZip schema="resNFe_v1.00">' . $b64a . '</docZip>'
            . '<docZip schema="procNFe_v4.00">' . $b64b . '</docZip>'
            . '</retDistDFeInt>';

        $pick = FiscalDfeDocZipSummary::primeiroDocumentoComInfNFe($xml);
        $this->assertNotNull($pick);
        $this->assertSame('nfeProc', $pick['tipo']);
        $this->assertStringContainsString('infNFe', $pick['xml_plain']);
    }
}
