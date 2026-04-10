<?php
namespace App\Utility\Fiscal;

/**
 * Extrai dados de NF-e autorizada (resNFe / nfeProc) para rascunho de nota de entrada no portal.
 *
 * Usa apenas DOM (sem XPath de namespace fixo): local-name().
 */
class FiscalResNfeImportParser {

    /**
     * Indica se o XML é um resumo resNFe sem infNFe (só metadados/chave), elegível para consulta consChNFe na Distribuição DF-e.
     *
     * @return string|null Chave de 44 dígitos válida ou null
     */
    public static function chaveSeResumoResNfe(string $xml): ?string {
        if ($xml === '') {
            return null;
        }
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xml)) {
            return null;
        }
        $root = $doc->documentElement;
        if (!$root || strtolower($root->localName) !== 'resnfe') {
            return null;
        }
        if (self::findFirstElementByLocalName($root, 'infNFe', true)) {
            return null;
        }
        $ch = self::firstTextByLocalNameDeep($doc, 'chNFe');
        $ch44 = $ch !== null ? preg_replace('/\D/', '', $ch) : '';
        if (strlen($ch44) !== 44 || !FiscalValidator::validarChaveAcesso($ch44)) {
            return null;
        }

        return $ch44;
    }

    /**
     * @return array{erros: string[], dados: ?array}
     * dados keys: chave_acesso, modelo, serie, numero, natureza_operacao, data_emissao (string Y-m-d H:i:s),
     * valor_total, valor_produtos, informacoes_complementares, protocolo_autorizacao,
     * emit_cnpj, emit_xnome, dest_cnpj, itens: list of item arrays
     */
    public static function parse(string $xml): array {
        $erros = [];
        if ($xml === '') {
            return ['erros' => ['XML vazio.'], 'dados' => null];
        }
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xml)) {
            return ['erros' => ['XML inválido.'], 'dados' => null];
        }

        $infNfe = self::findFirstElementByLocalName($doc->documentElement, 'infNFe', true);
        if (!$infNfe) {
            $msg = self::mensagemSemInfNfe($doc);

            return ['erros' => [$msg], 'dados' => null];
        }

        $ide = self::childByLocalName($infNfe, 'ide');
        if (!$ide) {
            $erros[] = 'Bloco ide ausente.';
        }

        $mod = $ide ? self::textChild($ide, 'mod') : '';
        if ($mod !== '55' && $mod !== '65') {
            $erros[] = 'Apenas modelo 55 ou 65 é suportado para importação de entrada.';
        }

        $chave = self::chaveFromInfNfe($infNfe, $doc);
        if ($chave === null || strlen($chave) !== 44) {
            $erros[] = 'Chave de acesso não encontrada ou inválida.';
        } elseif (!FiscalValidator::validarChaveAcesso($chave)) {
            $erros[] = 'Chave de acesso com dígito verificador inválido.';
        }

        $emit = self::childByLocalName($infNfe, 'emit');
        $emitCnpj = $emit ? self::cnpjOrCpf($emit) : null;
        if ($emitCnpj === null || strlen($emitCnpj) !== 14) {
            $erros[] = 'Emitente (fornecedor) sem CNPJ válido no XML.';
        }

        $dest = self::childByLocalName($infNfe, 'dest');
        $destCnpj = $dest ? self::cnpjOrCpf($dest) : null;

        $natOp = $ide ? self::textChild($ide, 'natOp') : '';
        if ($natOp === '') {
            $natOp = 'Importado DF-e';
        }

        $serie = $ide ? (int)self::textChild($ide, 'serie') : 1;
        $nNF = $ide ? self::textChild($ide, 'nNF') : '';
        $numero = $nNF !== '' ? (int)$nNF : null;

        $dhEmi = $ide ? self::textChild($ide, 'dhEmi') : '';
        $dataEmissao = self::normalizarDh($dhEmi);

        $totalEl = self::childByLocalName($infNfe, 'total');
        $icmsTot = $totalEl ? self::childByLocalName($totalEl, 'ICMSTot') : null;
        $vNF = $icmsTot ? (float)str_replace(',', '.', self::textChild($icmsTot, 'vNF') ?: '0') : 0.0;
        $vProd = $icmsTot ? (float)str_replace(',', '.', self::textChild($icmsTot, 'vProd') ?: '0') : $vNF;

        $infAdic = self::childByLocalName($infNfe, 'infAdic');
        $infCpl = $infAdic ? self::textChild($infAdic, 'infCpl') : '';

        $nProt = self::protocoloFromDoc($doc);

        $itens = self::parseItens($infNfe);
        if ($itens === []) {
            $erros[] = 'Nenhum item (det) encontrado na NF-e.';
        }

        if ($erros !== []) {
            return ['erros' => $erros, 'dados' => null];
        }

        return [
            'erros' => [],
            'dados' => [
                'chave_acesso' => $chave,
                'modelo' => $mod,
                'serie' => $serie > 0 ? $serie : 1,
                'numero' => $numero,
                'natureza_operacao' => $natOp,
                'data_emissao' => $dataEmissao,
                'valor_total' => $vNF,
                'valor_produtos' => $vProd,
                'informacoes_complementares' => $infCpl !== '' ? $infCpl : null,
                'protocolo_autorizacao' => $nProt,
                'emit_cnpj' => $emitCnpj,
                'emit_xnome' => $emit ? self::textChild($emit, 'xNome') : '',
                'dest_cnpj' => $destCnpj,
                'itens' => $itens,
            ],
        ];
    }

    private static function findFirstElementByLocalName(?\DOMElement $root, string $local, bool $deep): ?\DOMElement {
        if (!$root) {
            return null;
        }
        if ($root->localName === $local) {
            return $root;
        }
        foreach ($root->getElementsByTagNameNS('*', $local) as $n) {
            if ($n instanceof \DOMElement) {
                return $n;
            }
        }
        if ($deep) {
            $xp = new \DOMXPath($root->ownerDocument);
            $list = $xp->query('//*[local-name()="' . $local . '"]');
            if ($list->length > 0 && $list->item(0) instanceof \DOMElement) {
                return $list->item(0);
            }
        }

        return null;
    }

    private static function childByLocalName(\DOMElement $parent, string $local): ?\DOMElement {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->localName === $local) {
                return $child;
            }
        }

        return null;
    }

    private static function textChild(\DOMElement $parent, string $local): string {
        $el = self::childByLocalName($parent, $local);
        if (!$el) {
            return '';
        }

        return trim((string)$el->textContent);
    }

    private static function chaveFromInfNfe(\DOMElement $infNfe, \DOMDocument $doc): ?string {
        $id = $infNfe->getAttribute('Id');
        if (strpos($id, 'NFe') === 0 && strlen($id) >= 47) {
            $c = substr($id, 3, 44);
            if (strlen($c) === 44 && ctype_digit($c)) {
                return $c;
            }
        }
        $prot = self::findFirstElementByLocalName($doc->documentElement, 'infProt', true);
        if ($prot) {
            $ch = self::textChild($prot, 'chNFe');
            $ch = preg_replace('/\D/', '', $ch);

            return strlen($ch) === 44 ? $ch : null;
        }

        return null;
    }

    private static function protocoloFromDoc(\DOMDocument $doc): ?string {
        $prot = self::findFirstElementByLocalName($doc->documentElement, 'infProt', true);
        if (!$prot) {
            return null;
        }
        $n = self::textChild($prot, 'nProt');

        return $n !== '' ? $n : null;
    }

    private static function cnpjOrCpf(\DOMElement $emitDest): ?string {
        $cnpj = self::textChild($emitDest, 'CNPJ');
        if ($cnpj !== '') {
            $c = preg_replace('/\D/', '', $cnpj);

            return strlen($c) === 14 ? $c : null;
        }
        $cpf = self::textChild($emitDest, 'CPF');
        if ($cpf !== '') {
            return null;
        }

        return null;
    }

    private static function normalizarDh(string $dh): string {
        $dh = trim($dh);
        if ($dh === '') {
            return date('Y-m-d H:i:s');
        }
        $ts = strtotime($dh);

        return $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function parseItens(\DOMElement $infNfe): array {
        $out = [];
        foreach ($infNfe->childNodes as $det) {
            if (!$det instanceof \DOMElement || $det->localName !== 'det') {
                continue;
            }
            $prod = self::childByLocalName($det, 'prod');
            if (!$prod) {
                continue;
            }
            $nItem = (int)$det->getAttribute('nItem');
            if ($nItem < 1) {
                $nItem = count($out) + 1;
            }
            $qCom = (float)str_replace(',', '.', self::textChild($prod, 'qCom') ?: '0');
            $vUnCom = (float)str_replace(',', '.', self::textChild($prod, 'vUnCom') ?: '0');
            $vProd = (float)str_replace(',', '.', self::textChild($prod, 'vProd') ?: '0');
            if ($vProd <= 0 && $qCom > 0 && $vUnCom > 0) {
                $vProd = round($qCom * $vUnCom, 2);
            }
            if ($qCom <= 0 || $vUnCom <= 0) {
                continue;
            }
            $out[] = [
                'numero_item' => $nItem,
                'codigo_produto' => self::textChild($prod, 'cProd') ?: null,
                'descricao' => self::textChild($prod, 'xProd') ?: 'Item',
                'ncm' => self::textChild($prod, 'NCM') ?: null,
                'cfop' => self::textChild($prod, 'CFOP') ?: '1102',
                'unidade' => self::textChild($prod, 'uCom') ?: 'UN',
                'quantidade' => $qCom,
                'valor_unitario' => $vUnCom,
                'valor_total' => $vProd > 0 ? $vProd : round($qCom * $vUnCom, 2),
            ];
        }

        return $out;
    }

    /**
     * Explica ausência de infNFe (ex.: resNFe só com chNFe na distribuição).
     */
    private static function mensagemSemInfNfe(\DOMDocument $doc): string {
        $root = $doc->documentElement;
        if ($root && strtolower($root->localName) === 'resnfe') {
            $ch = self::firstTextByLocalNameDeep($doc, 'chNFe');
            $ch = $ch !== null ? preg_replace('/\D/', '', $ch) : '';
            if (strlen($ch) === 44) {
                return 'Este XML é um resumo (resNFe) com apenas a chave de acesso. Para gerar rascunho de entrada com itens é necessário o XML completo da NF-e (nfeProc com NFe, ou resNFe que inclua o corpo da nota).';
            }
        }

        return 'Não foi encontrado infNFe (conteúdo não parece NF-e autorizada com corpo da nota).';
    }

    private static function firstTextByLocalNameDeep(\DOMDocument $doc, string $local): ?string {
        $xp = new \DOMXPath($doc);
        $list = $xp->query('//*[local-name()="' . $local . '"]');
        if ($list->length < 1) {
            return null;
        }
        $n = $list->item(0);
        if (!$n) {
            return null;
        }
        $t = trim((string)$n->textContent);

        return $t !== '' ? $t : null;
    }
}
