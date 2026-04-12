<?php
$soapXml = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <nfeResultMsg xmlns="http://www.portalfiscal.inf.br/nfe/wsdl/NFeStatusServico4">
      <retConsStatServ versao="4.00" xmlns="http://www.portalfiscal.inf.br/nfe">
        <tpAmb>1</tpAmb>
        <verAplic>SVRS2024</verAplic>
        <cStat>107</cStat>
        <xMotivo>Servico em Operacao</xMotivo>
      </retConsStatServ>
    </nfeResultMsg>
  </soap12:Body>
</soap12:Envelope>';

$doc = new \DOMDocument();
$doc->loadXML($soapXml);
$body = $doc->getElementsByTagNameNS('*', 'Body')->item(0);

$innerParts = [];
foreach ($body->childNodes as $responseNode) {
    if ($responseNode->nodeType !== XML_ELEMENT_NODE) continue;
    foreach ($responseNode->childNodes as $resultNode) {
        if ($resultNode->nodeType !== XML_ELEMENT_NODE) continue;
        foreach ($resultNode->childNodes as $child) {
            $innerParts[] = $doc->saveXML($child);
        }
        $extracted = trim(implode('', $innerParts));
        echo "Extracted:\n" . $extracted . "\n";
        
        $testDoc = new \DOMDocument();
        $ok = @$testDoc->loadXML($extracted);
        echo "Valid XML? " . ($ok ? "YES" : "NO") . "\n";
        exit;
    }
}
