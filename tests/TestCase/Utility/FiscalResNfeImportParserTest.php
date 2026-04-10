<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalCalculator;
use App\Utility\Fiscal\FiscalResNfeImportParser;
use PHPUnit\Framework\TestCase;

class FiscalResNfeImportParserTest extends TestCase {

    public function testParseNfeProcExtraiChaveItensETotais(): void {
        $emitCnpj = '11222333000181';
        $destCnpj = '99888777000166';
        $chave = FiscalCalculator::gerarChaveAcesso('35', '2401', $emitCnpj, '55', '1', '10', '1', '12345678');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<NFe><infNFe Id="NFe' . $chave . '">'
            . '<ide><cUF>35</cUF><natOp>Compra para industrialização</natOp><mod>55</mod><serie>1</serie><nNF>10</nNF>'
            . '<dhEmi>2024-01-15T10:00:00-03:00</dhEmi></ide>'
            . '<emit><CNPJ>' . $emitCnpj . '</CNPJ><xNome>Fornecedor Teste</xNome></emit>'
            . '<dest><CNPJ>' . $destCnpj . '</CNPJ><xNome>Destinatário</xNome></dest>'
            . '<det nItem="1"><prod><cProd>P1</cProd><xProd>Produto A</xProd><NCM>12345678</NCM><CFOP>1102</CFOP>'
            . '<uCom>UN</uCom><qCom>2.0000</qCom><vUnCom>5.50</vUnCom><vProd>11.00</vProd></prod></det>'
            . '<total><ICMSTot><vProd>11.00</vProd><vNF>11.00</vNF></ICMSTot></total>'
            . '</infNFe></NFe>'
            . '<protNFe><infProt><chNFe>' . $chave . '</chNFe><nProt>135240000000000</nProt></infProt></protNFe>'
            . '</nfeProc>';

        $out = FiscalResNfeImportParser::parse($xml);
        $this->assertSame([], $out['erros']);
        $this->assertNotNull($out['dados']);
        $d = $out['dados'];
        $this->assertSame($chave, $d['chave_acesso']);
        $this->assertSame('55', $d['modelo']);
        $this->assertSame(1, $d['serie']);
        $this->assertSame(10, $d['numero']);
        $this->assertSame($emitCnpj, $d['emit_cnpj']);
        $this->assertSame($destCnpj, $d['dest_cnpj']);
        $this->assertCount(1, $d['itens']);
        $this->assertSame('Produto A', $d['itens'][0]['descricao']);
        $this->assertEqualsWithDelta(11.0, (float)$d['valor_total'], 0.001);
        $this->assertSame('135240000000000', $d['protocolo_autorizacao']);
    }

    public function testParseXmlVazioRetornaErro(): void {
        $out = FiscalResNfeImportParser::parse('');
        $this->assertNotSame([], $out['erros']);
        $this->assertNull($out['dados']);
    }

    public function testResNFeSoComChaveRetornaMensagemEspecifica(): void {
        $chave = FiscalCalculator::gerarChaveAcesso('35', '2401', '11222333000181', '55', '1', '99', '1', '11111111');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<resNFe xmlns="http://www.portalfiscal.inf.br/nfe"><chNFe>' . $chave . '</chNFe></resNFe>';
        $out = FiscalResNfeImportParser::parse($xml);
        $this->assertNull($out['dados']);
        $this->assertCount(1, $out['erros']);
        $this->assertStringContainsString('resumo', strtolower($out['erros'][0]));
        $this->assertStringContainsString('nfeproc', strtolower($out['erros'][0]));
    }

    public function testChaveSeResumoResNfeDetectaSoChave(): void {
        $chave = FiscalCalculator::gerarChaveAcesso('35', '2401', '11222333000181', '55', '1', '7', '1', '22222222');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<resNFe xmlns="http://www.portalfiscal.inf.br/nfe"><chNFe>' . $chave . '</chNFe></resNFe>';
        $this->assertSame($chave, FiscalResNfeImportParser::chaveSeResumoResNfe($xml));
    }

    public function testChaveSeResumoResNfeNullParaNfeProc(): void {
        $emitCnpj = '11222333000181';
        $destCnpj = '99888777000166';
        $chave = FiscalCalculator::gerarChaveAcesso('35', '2401', $emitCnpj, '55', '1', '11', '1', '33333333');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<NFe><infNFe Id="NFe' . $chave . '">'
            . '<ide><mod>55</mod><serie>1</serie><nNF>11</nNF><dhEmi>2024-01-15T10:00:00-03:00</dhEmi></ide>'
            . '<emit><CNPJ>' . $emitCnpj . '</CNPJ><xNome>E</xNome></emit>'
            . '<dest><CNPJ>' . $destCnpj . '</CNPJ><xNome>D</xNome></dest>'
            . '<det nItem="1"><prod><cProd>P</cProd><xProd>X</xProd><NCM>12345678</NCM><CFOP>1102</CFOP>'
            . '<uCom>UN</uCom><qCom>1</qCom><vUnCom>1</vUnCom><vProd>1</vProd></prod></det>'
            . '<total><ICMSTot><vProd>1</vProd><vNF>1</vNF></ICMSTot></total>'
            . '</infNFe></NFe>'
            . '<protNFe><infProt><chNFe>' . $chave . '</chNFe><nProt>1</nProt></infProt></protNFe>'
            . '</nfeProc>';
        $this->assertNull(FiscalResNfeImportParser::chaveSeResumoResNfe($xml));
    }
}
