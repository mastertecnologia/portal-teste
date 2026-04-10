<?php
namespace App\Utility\Fiscal;

/**
 * Extrai resumo e documentos completos dos blocos docZip em retDistDFeInt (gzip+base64).
 */
class FiscalDfeDocZipSummary {

    private const PREVIEW_LIMIT = 25;

    private const INGEST_LIMIT = 100;

    /**
     * @return array{doc_count:int, items: array<int, array{schema:string, chave:string|null, tipo:string}>}
     */
    public static function summarizeRetornoXml(string $xml): array {
        $out = ['doc_count' => 0, 'items' => []];
        if ($xml === '') {
            return $out;
        }
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xml)) {
            return $out;
        }
        $nodes = $doc->getElementsByTagName('docZip');
        $out['doc_count'] = $nodes->length;
        for ($i = 0; $i < $nodes->length && count($out['items']) < self::PREVIEW_LIMIT; $i++) {
            $node = $nodes->item($i);
            if (!$node instanceof \DOMElement) {
                continue;
            }
            $row = self::decodeDocZipElement($node);
            if ($row === null) {
                $out['items'][] = [
                    'schema' => (string)$node->getAttribute('schema'),
                    'chave' => null,
                    'tipo' => 'decode_erro',
                ];
                continue;
            }
            $out['items'][] = [
                'schema' => $row['schema'],
                'chave' => $row['chave'],
                'tipo' => $row['tipo'],
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{schema:string, nsu:string, chave:?string, tipo:string, xml_plain:string}>
     */
    public static function extractDocumentosParaIngest(string $xml): array {
        if ($xml === '') {
            return [];
        }
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xml)) {
            return [];
        }
        $nodes = $doc->getElementsByTagName('docZip');
        $list = [];
        for ($i = 0; $i < $nodes->length && count($list) < self::INGEST_LIMIT; $i++) {
            $node = $nodes->item($i);
            if (!$node instanceof \DOMElement) {
                continue;
            }
            $row = self::decodeDocZipElement($node);
            if ($row === null || $row['xml_plain'] === '') {
                continue;
            }
            $list[] = $row;
        }

        return $list;
    }

    /**
     * Primeiro documento decodificado do retDistDFeInt cujo XML contenha infNFe (NF-e completa / nfeProc).
     *
     * @return array{schema:string, nsu:string, chave:?string, tipo:string, xml_plain:string}|null
     */
    public static function primeiroDocumentoComInfNFe(string $xmlRetorno): ?array {
        foreach (self::extractDocumentosParaIngest($xmlRetorno) as $d) {
            if (($d['tipo'] ?? '') === 'binario' || ($d['xml_plain'] ?? '') === '') {
                continue;
            }
            $inner = new \DOMDocument();
            if (!@$inner->loadXML($d['xml_plain'])) {
                continue;
            }
            $xp = new \DOMXPath($inner);
            if ($xp->query('//*[local-name()="infNFe"]')->length > 0) {
                return $d;
            }
        }

        return null;
    }

    /**
     * @return array{schema:string, nsu:string, chave:?string, tipo:string, xml_plain:string}|null
     */
    private static function decodeDocZipElement(\DOMElement $node): ?array {
        $schema = (string)$node->getAttribute('schema');
        $nsu = (string)($node->getAttribute('NSU') ?: $node->getAttribute('nsu'));
        $b64 = preg_replace('/\s+/', '', trim((string)$node->textContent));
        $decoded = base64_decode($b64, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }
        $plain = @gzdecode($decoded);
        if ($plain === false) {
            $plain = $decoded;
        }
        $inner = new \DOMDocument();
        $chave = null;
        $tipo = 'xml';
        if (@$inner->loadXML($plain)) {
            $tipo = self::guessTipo($inner);
            $chave = self::firstTagValue($inner, 'chNFe')
                ?? self::firstTagValue($inner, 'chCTe')
                ?? self::firstTagValue($inner, 'chMDFe');
        } else {
            $tipo = 'binario';
        }

        return [
            'schema' => $schema,
            'nsu' => $nsu,
            'chave' => $chave,
            'tipo' => $tipo,
            'xml_plain' => $plain,
        ];
    }

    private static function guessTipo(\DOMDocument $inner): string {
        $root = $inner->documentElement;
        if ($root) {
            return $root->localName ?: $root->nodeName;
        }

        return 'xml';
    }

    private static function firstTagValue(\DOMDocument $doc, string $tag): ?string {
        $list = $doc->getElementsByTagName($tag);
        if ($list->length === 0) {
            return null;
        }
        $v = trim((string)$list->item(0)->nodeValue);

        return $v !== '' ? $v : null;
    }
}
