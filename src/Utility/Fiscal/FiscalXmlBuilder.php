<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;

/**
 * Gerador de XML para NFe/NFCe (layout 4.00).
 *
 * Constrói o XML conforme Manual de Orientação do Contribuinte.
 * Utiliza DOMDocument nativo do PHP.
 */
class FiscalXmlBuilder {

    private const NAMESPACE_NFE = 'http://www.portalfiscal.inf.br/nfe';
    private const VERSAO = '4.00';

    /** @var \DOMDocument */
    private $dom;

    /** @var array Dados da nota (entity FiscalNota + relations) */
    private $nota;

    /** @var array Configuração fiscal da empresa */
    private $config;

    /** @var array Dados da empresa emitente */
    private $empresa;

    /** @var array Dados do destinatário (cliente) */
    private $destinatario;

    public function __construct(array $nota, array $config, array $empresa, array $destinatario) {
        $this->nota = $nota;
        $this->config = $config;
        $this->empresa = $empresa;
        $this->destinatario = $destinatario;
        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = false;
        $this->dom->preserveWhiteSpace = false;
    }

    /**
     * Gera o XML completo da NFe (sem assinatura).
     *
     * @return string XML como string
     */
    public function build() {
        $NFe = $this->dom->createElementNS(self::NAMESPACE_NFE, 'NFe');
        $this->dom->appendChild($NFe);

        $infNFe = $this->dom->createElement('infNFe');
        $infNFe->setAttribute('versao', self::VERSAO);
        $infNFe->setAttribute('Id', 'NFe' . $this->nota['chave_acesso']);
        $NFe->appendChild($infNFe);

        // ide — Identificação da NFe
        $infNFe->appendChild($this->buildIde());

        // emit — Emitente
        $infNFe->appendChild($this->buildEmit());

        // dest — Destinatário
        $infNFe->appendChild($this->buildDest());

        // det — Itens
        $itens = $this->nota['fiscal_notas_itens'] ?? [];
        foreach ($itens as $item) {
            $infNFe->appendChild($this->buildDet($item));
        }

        // total
        $infNFe->appendChild($this->buildTotal());

        // transp — Transporte
        $infNFe->appendChild($this->buildTransp());

        // pag — Pagamentos
        $infNFe->appendChild($this->buildPag());

        // infAdic — Informações adicionais
        if (!empty($this->nota['informacoes_complementares']) || !empty($this->nota['informacoes_fisco'])) {
            $infNFe->appendChild($this->buildInfAdic());
        }

        return $this->dom->saveXML();
    }

    /**
     * Gera XML do lote de envio (enviNFe).
     */
    public function buildEnviNFe($xmlNFe, $idLote = '1') {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $enviNFe = $doc->createElementNS(self::NAMESPACE_NFE, 'enviNFe');
        $enviNFe->setAttribute('versao', self::VERSAO);
        $doc->appendChild($enviNFe);

        $this->addElement($doc, $enviNFe, 'idLote', $idLote);
        $this->addElement($doc, $enviNFe, 'indSinc', '1'); // Síncrono

        $nfeDoc = new \DOMDocument();
        $nfeDoc->loadXML($xmlNFe);
        $nfeNode = $doc->importNode($nfeDoc->documentElement, true);
        $enviNFe->appendChild($nfeNode);

        return $doc->saveXML();
    }

    /**
     * Gera XML de cancelamento (evento 110111).
     */
    public function buildCancelamento($chaveAcesso, $protocolo, $justificativa, $cnpj, $dataEvento = null) {
        return $this->buildEvento('110111', $chaveAcesso, $cnpj, 1, $dataEvento, function ($detEvento, $doc) use ($protocolo, $justificativa) {
            $this->addElement($doc, $detEvento, 'descEvento', 'Cancelamento');
            $this->addElement($doc, $detEvento, 'nProt', $protocolo);
            $this->addElement($doc, $detEvento, 'xJust', substr($justificativa, 0, 255));
        });
    }

    /**
     * Gera XML de Carta de Correção (evento 110110).
     */
    public function buildCartaCorrecao($chaveAcesso, $correcao, $cnpj, $sequencia = 1, $dataEvento = null) {
        return $this->buildEvento('110110', $chaveAcesso, $cnpj, $sequencia, $dataEvento, function ($detEvento, $doc) use ($correcao) {
            $this->addElement($doc, $detEvento, 'descEvento', 'Carta de Correcao');
            $this->addElement($doc, $detEvento, 'xCorrecao', substr($correcao, 0, 1000));
            $this->addElement($doc, $detEvento, 'xCondUso', 'A Carta de Correcao e disciplinada pelo paragrafo 1o-A do art. 7o do Convenio S/N, de 15 de dezembro de 1970 e pode ser utilizada para regularizacao de erro ocorrido na emissao de documento fiscal, desde que o erro nao esteja relacionado com: I - as variaveis que determinam o valor do imposto tais como: base de calculo, aliquota, diferenca de preco, quantidade, valor da operacao ou da prestacao; II - a correcao de dados cadastrais que implique mudanca do remetente ou do destinatario; III - a data de emissao ou de saida.');
        });
    }

    /**
     * Gera XML de inutilização de numeração.
     */
    public function buildInutilizacao($ano, $cnpj, $modelo, $serie, $nInicial, $nFinal, $justificativa) {
        $cnpjLimpo = preg_replace('/\D/', '', $cnpj);
        $id = 'ID'
            . str_pad($this->config['uf'] ? (Configure::read('Fiscal.ufs.' . $this->config['uf']) ?? '35') : '35', 2, '0', STR_PAD_LEFT)
            . $ano
            . str_pad($cnpjLimpo, 14, '0', STR_PAD_LEFT)
            . str_pad($modelo, 2, '0', STR_PAD_LEFT)
            . str_pad($serie, 3, '0', STR_PAD_LEFT)
            . str_pad($nInicial, 9, '0', STR_PAD_LEFT)
            . str_pad($nFinal, 9, '0', STR_PAD_LEFT);

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $inutNFe = $doc->createElementNS(self::NAMESPACE_NFE, 'inutNFe');
        $inutNFe->setAttribute('versao', self::VERSAO);
        $doc->appendChild($inutNFe);

        $infInut = $doc->createElement('infInut');
        $infInut->setAttribute('Id', $id);
        $inutNFe->appendChild($infInut);

        $cuf = $this->config['uf'] ? (Configure::read('Fiscal.ufs.' . $this->config['uf']) ?? '35') : '35';
        $this->addElement($doc, $infInut, 'tpAmb', (string)($this->config['ambiente'] ?? 2));
        $this->addElement($doc, $infInut, 'xServ', 'INUTILIZAR');
        $this->addElement($doc, $infInut, 'cUF', (string)$cuf);
        $this->addElement($doc, $infInut, 'ano', (string)$ano);
        $this->addElement($doc, $infInut, 'CNPJ', $cnpjLimpo);
        $this->addElement($doc, $infInut, 'mod', (string)$modelo);
        $this->addElement($doc, $infInut, 'serie', (string)$serie);
        $this->addElement($doc, $infInut, 'nNFIni', (string)$nInicial);
        $this->addElement($doc, $infInut, 'nNFFin', (string)$nFinal);
        $this->addElement($doc, $infInut, 'xJust', substr($justificativa, 0, 255));

        return $doc->saveXML();
    }

    /**
     * Manifestação do destinatário (eventos 210200–210240) — NF-e recebida (entrada).
     *
     * @param string $tipo confirmacao|ciencia|desconhecimento|nao_realizada
     * @param string $justificativa Obrigatória para desconhecimento e operação não realizada (máx. 255)
     */
    public function buildManifestacaoDestinatario($chaveAcesso, $cnpjDestinatario, $tipo, $sequencia, $justificativa = '', $dataEvento = null) {
        $chave = preg_replace('/\D/', '', (string)$chaveAcesso);
        $map = [
            'confirmacao' => ['cod' => '210200', 'desc' => 'Confirmacao da Operacao'],
            'ciencia' => ['cod' => '210210', 'desc' => 'Ciencia da Operacao'],
            'desconhecimento' => ['cod' => '210220', 'desc' => 'Desconhecimento da Operacao'],
            'nao_realizada' => ['cod' => '210240', 'desc' => 'Operacao nao Realizada'],
        ];
        if (!isset($map[$tipo])) {
            throw new \InvalidArgumentException('Tipo de manifestação inválido.');
        }
        $cod = $map[$tipo]['cod'];
        $desc = $map[$tipo]['desc'];
        $just = (string)$justificativa;

        $opts = [
            'env_versao' => '1.00',
            'evento_versao' => '1.00',
            'ver_evento' => '1.00',
            'det_versao' => '1.00',
        ];

        return $this->buildEvento($cod, $chave, $cnpjDestinatario, $sequencia, $dataEvento, function ($detEvento, $doc) use ($desc, $cod, $just) {
            $this->addElement($doc, $detEvento, 'descEvento', $desc);
            if (in_array($cod, ['210220', '210240'], true)) {
                $this->addElement($doc, $detEvento, 'xJust', substr($just, 0, 255));
            }
        }, $opts);
    }

    // ── Blocos do XML NFe ────────────────────────────────────────────

    private function buildIde() {
        $ide = $this->dom->createElement('ide');
        $cuf = Configure::read('Fiscal.ufs.' . ($this->config['uf'] ?? 'SP')) ?? '35';

        $this->addEl($ide, 'cUF', (string)$cuf);
        $this->addEl($ide, 'cNF', str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT));
        $this->addEl($ide, 'natOp', $this->nota['natureza_operacao'] ?? 'VENDA');
        $this->addEl($ide, 'mod', $this->nota['modelo'] ?? '55');
        $this->addEl($ide, 'serie', (string)($this->nota['serie'] ?? 1));
        $this->addEl($ide, 'nNF', (string)($this->nota['numero'] ?? 0));
        $this->addEl($ide, 'dhEmi', $this->formatDate($this->nota['data_emissao'] ?? date('c')));
        if (!empty($this->nota['data_saida'])) {
            $this->addEl($ide, 'dhSaiEnt', $this->formatDate($this->nota['data_saida']));
        }
        $this->addEl($ide, 'tpNF', (string)($this->nota['tipo_operacao'] ?? 1));
        $this->addEl($ide, 'idDest', '1'); // Operação interna
        $this->addEl($ide, 'cMunFG', $this->config['codigo_municipio_ibge'] ?? '3550308');
        $this->addEl($ide, 'tpImp', '1'); // DANFE normal
        $this->addEl($ide, 'tpEmis', '1'); // Normal
        $this->addEl($ide, 'cDV', substr($this->nota['chave_acesso'] ?? '', -1));
        $this->addEl($ide, 'tpAmb', (string)($this->config['ambiente'] ?? 2));
        $this->addEl($ide, 'finNFe', (string)($this->nota['finalidade'] ?? 1));
        $this->addEl($ide, 'indFinal', '1'); // Consumidor final
        $this->addEl($ide, 'indPres', (string)($this->nota['presenca'] ?? 9));
        $this->addEl($ide, 'procEmi', '0'); // Aplicativo do contribuinte
        $this->addEl($ide, 'verProc', 'PGM-Fiscal-1.0');

        return $ide;
    }

    private function buildEmit() {
        $emit = $this->dom->createElement('emit');
        $cnpj = preg_replace('/\D/', '', $this->empresa['cnpj'] ?? '');

        $this->addEl($emit, 'CNPJ', str_pad($cnpj, 14, '0', STR_PAD_LEFT));
        $this->addEl($emit, 'xNome', substr($this->empresa['razaosocial'] ?? '', 0, 60));
        if (!empty($this->empresa['nomefantasia'])) {
            $this->addEl($emit, 'xFant', substr($this->empresa['nomefantasia'], 0, 60));
        }

        $enderEmit = $this->dom->createElement('enderEmit');
        $this->addEl($enderEmit, 'xLgr', substr($this->empresa['endereco'] ?? '', 0, 60));
        $this->addEl($enderEmit, 'nro', substr($this->empresa['numero'] ?? 'S/N', 0, 60));
        if (!empty($this->empresa['complemento'])) {
            $this->addEl($enderEmit, 'xCpl', substr($this->empresa['complemento'], 0, 60));
        }
        $this->addEl($enderEmit, 'xBairro', substr($this->empresa['bairro'] ?? '', 0, 60));
        $this->addEl($enderEmit, 'cMun', $this->config['codigo_municipio_ibge'] ?? '3550308');
        $this->addEl($enderEmit, 'xMun', substr($this->empresa['cidade'] ?? '', 0, 60));
        $this->addEl($enderEmit, 'UF', $this->config['uf'] ?? 'SP');
        $this->addEl($enderEmit, 'CEP', preg_replace('/\D/', '', $this->empresa['cep'] ?? ''));
        $this->addEl($enderEmit, 'cPais', '1058');
        $this->addEl($enderEmit, 'xPais', 'BRASIL');
        if (!empty($this->empresa['fone'])) {
            $this->addEl($enderEmit, 'fone', preg_replace('/\D/', '', $this->empresa['fone']));
        }
        $emit->appendChild($enderEmit);

        if (!empty($this->config['inscricao_estadual'])) {
            $this->addEl($emit, 'IE', preg_replace('/\D/', '', $this->config['inscricao_estadual']));
        }
        if (!empty($this->config['inscricao_municipal'])) {
            $this->addEl($emit, 'IM', $this->config['inscricao_municipal']);
        }
        if (!empty($this->config['cnae_fiscal'])) {
            $this->addEl($emit, 'CNAE', $this->config['cnae_fiscal']);
        }
        // CRT 1–3: Simples, SN excesso, Regime normal (Lucro presumido e real = 3). IBS/CBS: aguardar leiaute NT vigente.
        $this->addEl($emit, 'CRT', (string)($this->config['regime_tributario'] ?? 3));

        return $emit;
    }

    private function buildDest() {
        $dest = $this->dom->createElement('dest');
        $doc = preg_replace('/\D/', '', $this->destinatario['cnpj'] ?? $this->destinatario['cpf'] ?? '');

        if (strlen($doc) === 14) {
            $this->addEl($dest, 'CNPJ', $doc);
        } elseif (strlen($doc) === 11) {
            $this->addEl($dest, 'CPF', $doc);
        }
        $this->addEl($dest, 'xNome', substr($this->destinatario['razaosocial'] ?? $this->destinatario['nome'] ?? '', 0, 60));

        $enderDest = $this->dom->createElement('enderDest');
        $this->addEl($enderDest, 'xLgr', substr($this->destinatario['endereco'] ?? '', 0, 60));
        $this->addEl($enderDest, 'nro', substr($this->destinatario['numero'] ?? 'S/N', 0, 60));
        $this->addEl($enderDest, 'xBairro', substr($this->destinatario['bairro'] ?? '', 0, 60));
        $this->addEl($enderDest, 'cMun', $this->destinatario['codigo_municipio_ibge'] ?? '3550308');
        $this->addEl($enderDest, 'xMun', substr($this->destinatario['cidade'] ?? '', 0, 60));
        $this->addEl($enderDest, 'UF', $this->destinatario['uf'] ?? 'SP');
        $this->addEl($enderDest, 'CEP', preg_replace('/\D/', '', $this->destinatario['cep'] ?? ''));
        $this->addEl($enderDest, 'cPais', '1058');
        $this->addEl($enderDest, 'xPais', 'BRASIL');
        $dest->appendChild($enderDest);

        $this->addEl($dest, 'indIEDest', '9'); // Não contribuinte

        return $dest;
    }

    private function buildDet(array $item) {
        $det = $this->dom->createElement('det');
        $det->setAttribute('nItem', (string)($item['numero_item'] ?? 1));

        // prod
        $prod = $this->dom->createElement('prod');
        $this->addEl($prod, 'cProd', $item['codigo_produto'] ?? (string)($item['produto_id'] ?? $item['numero_item']));
        $this->addEl($prod, 'cEAN', 'SEM GTIN');
        $this->addEl($prod, 'xProd', substr($item['descricao'] ?? '', 0, 120));
        $this->addEl($prod, 'NCM', $item['ncm'] ?? '00000000');
        if (!empty($item['cest'])) {
            $this->addEl($prod, 'CEST', $item['cest']);
        }
        $this->addEl($prod, 'CFOP', $item['cfop'] ?? '5102');
        $this->addEl($prod, 'uCom', $item['unidade'] ?? 'UN');
        $this->addEl($prod, 'qCom', number_format((float)($item['quantidade'] ?? 1), 4, '.', ''));
        $this->addEl($prod, 'vUnCom', number_format((float)($item['valor_unitario'] ?? 0), 4, '.', ''));
        $this->addEl($prod, 'vProd', number_format((float)($item['valor_total'] ?? 0), 2, '.', ''));
        $this->addEl($prod, 'cEANTrib', 'SEM GTIN');
        $this->addEl($prod, 'uTrib', $item['unidade'] ?? 'UN');
        $this->addEl($prod, 'qTrib', number_format((float)($item['quantidade'] ?? 1), 4, '.', ''));
        $this->addEl($prod, 'vUnTrib', number_format((float)($item['valor_unitario'] ?? 0), 4, '.', ''));
        if ((float)($item['valor_desconto'] ?? 0) > 0) {
            $this->addEl($prod, 'vDesc', number_format((float)$item['valor_desconto'], 2, '.', ''));
        }
        $this->addEl($prod, 'indTot', '1');
        $det->appendChild($prod);

        // imposto
        $imposto = $this->dom->createElement('imposto');
        $impostos = $item['fiscal_notas_impostos'] ?? $item['impostos'] ?? [];
        $imposto->appendChild($this->buildImpostoICMS($item, $impostos));
        $imposto->appendChild($this->buildImpostoPIS($item, $impostos));
        $imposto->appendChild($this->buildImpostoCOFINS($item, $impostos));
        $det->appendChild($imposto);

        if (!empty($item['informacoes_adicionais'])) {
            $infAdProd = $this->dom->createElement('infAdProd', substr($item['informacoes_adicionais'], 0, 500));
            $det->appendChild($infAdProd);
        }

        return $det;
    }

    private function buildImpostoICMS(array $item, array $impostos) {
        $icmsGroup = $this->dom->createElement('ICMS');
        $cst = $item['icms_cst'] ?? '00';
        $tagName = 'ICMS' . $cst;
        // Simplificar para tags mais comuns
        if (!in_array($cst, ['00', '10', '20', '30', '40', '41', '50', '51', '60', '70', '90'])) {
            $tagName = 'ICMSSN' . $cst; // CSOSN para Simples Nacional
        }
        $icmsEl = $this->dom->createElement($tagName);
        $this->addEl($icmsEl, 'orig', $item['icms_origem'] ?? '0');
        $this->addEl($icmsEl, in_array($cst, ['101','102','103','201','202','203','300','400','500','900']) ? 'CSOSN' : 'CST', $cst);

        $icmsData = $this->findImposto($impostos, 'ICMS');
        if ($icmsData && (float)$icmsData['aliquota'] > 0) {
            $this->addEl($icmsEl, 'modBC', '3');
            $this->addEl($icmsEl, 'vBC', number_format((float)$icmsData['base_calculo'], 2, '.', ''));
            $this->addEl($icmsEl, 'pICMS', number_format((float)$icmsData['aliquota'], 2, '.', ''));
            $this->addEl($icmsEl, 'vICMS', number_format((float)$icmsData['valor'], 2, '.', ''));
        }
        $icmsGroup->appendChild($icmsEl);
        return $icmsGroup;
    }

    private function buildImpostoPIS(array $item, array $impostos) {
        $pisGroup = $this->dom->createElement('PIS');
        $cst = $item['pis_cst'] ?? '07';
        $pisData = $this->findImposto($impostos, 'PIS');

        if (in_array($cst, ['01', '02']) && $pisData) {
            $pisEl = $this->dom->createElement('PISAliq');
            $this->addEl($pisEl, 'CST', $cst);
            $this->addEl($pisEl, 'vBC', number_format((float)$pisData['base_calculo'], 2, '.', ''));
            $this->addEl($pisEl, 'pPIS', number_format((float)$pisData['aliquota'], 4, '.', ''));
            $this->addEl($pisEl, 'vPIS', number_format((float)$pisData['valor'], 2, '.', ''));
        } else {
            $pisEl = $this->dom->createElement('PISNT');
            $this->addEl($pisEl, 'CST', $cst);
        }
        $pisGroup->appendChild($pisEl);
        return $pisGroup;
    }

    private function buildImpostoCOFINS(array $item, array $impostos) {
        $cofinsGroup = $this->dom->createElement('COFINS');
        $cst = $item['cofins_cst'] ?? '07';
        $cofinsData = $this->findImposto($impostos, 'COFINS');

        if (in_array($cst, ['01', '02']) && $cofinsData) {
            $cofinsEl = $this->dom->createElement('COFINSAliq');
            $this->addEl($cofinsEl, 'CST', $cst);
            $this->addEl($cofinsEl, 'vBC', number_format((float)$cofinsData['base_calculo'], 2, '.', ''));
            $this->addEl($cofinsEl, 'pCOFINS', number_format((float)$cofinsData['aliquota'], 4, '.', ''));
            $this->addEl($cofinsEl, 'vCOFINS', number_format((float)$cofinsData['valor'], 2, '.', ''));
        } else {
            $cofinsEl = $this->dom->createElement('COFINSNT');
            $this->addEl($cofinsEl, 'CST', $cst);
        }
        $cofinsGroup->appendChild($cofinsEl);
        return $cofinsGroup;
    }

    private function buildTotal() {
        $total = $this->dom->createElement('total');
        $icmsTot = $this->dom->createElement('ICMSTot');

        $fields = [
            'vBC' => '0.00', 'vICMS' => 'valor_icms', 'vICMSDeson' => '0.00',
            'vFCPUFDest' => '0.00', 'vICMSUFDest' => '0.00', 'vICMSUFRemet' => '0.00',
            'vFCP' => '0.00', 'vBCST' => '0.00', 'vST' => 'valor_icms_st',
            'vFCPST' => '0.00', 'vFCPSTRet' => '0.00',
            'vProd' => 'valor_produtos', 'vFrete' => 'valor_frete',
            'vSeg' => 'valor_seguro', 'vDesc' => 'valor_desconto',
            'vII' => '0.00', 'vIPI' => 'valor_ipi', 'vIPIDevol' => '0.00',
            'vPIS' => 'valor_pis', 'vCOFINS' => 'valor_cofins',
            'vOutro' => 'valor_outras_despesas', 'vNF' => 'valor_total',
            'vTotTrib' => 'valor_total_impostos',
        ];
        foreach ($fields as $tag => $source) {
            $val = is_numeric($source) || $source === '0.00'
                ? $source
                : number_format((float)($this->nota[$source] ?? 0), 2, '.', '');
            $this->addEl($icmsTot, $tag, $val);
        }
        $total->appendChild($icmsTot);
        return $total;
    }

    private function buildTransp() {
        $transp = $this->dom->createElement('transp');
        $this->addEl($transp, 'modFrete', (string)($this->nota['frete_modalidade'] ?? 9));

        if (!empty($this->nota['transportadora_cnpj'])) {
            $transporta = $this->dom->createElement('transporta');
            $this->addEl($transporta, 'CNPJ', preg_replace('/\D/', '', $this->nota['transportadora_cnpj']));
            $this->addEl($transporta, 'xNome', substr($this->nota['transportadora_nome'] ?? '', 0, 60));
            $transp->appendChild($transporta);
        }

        if (!empty($this->nota['volumes_quantidade'])) {
            $vol = $this->dom->createElement('vol');
            $this->addEl($vol, 'qVol', (string)$this->nota['volumes_quantidade']);
            if (!empty($this->nota['volumes_especie'])) {
                $this->addEl($vol, 'esp', $this->nota['volumes_especie']);
            }
            if (!empty($this->nota['volumes_peso_bruto'])) {
                $this->addEl($vol, 'pesoB', number_format((float)$this->nota['volumes_peso_bruto'], 3, '.', ''));
            }
            if (!empty($this->nota['volumes_peso_liquido'])) {
                $this->addEl($vol, 'pesoL', number_format((float)$this->nota['volumes_peso_liquido'], 3, '.', ''));
            }
            $transp->appendChild($vol);
        }

        return $transp;
    }

    private function buildPag() {
        $pag = $this->dom->createElement('pag');
        $pagamentos = $this->nota['fiscal_notas_pagamentos'] ?? [];

        if (empty($pagamentos)) {
            $detPag = $this->dom->createElement('detPag');
            $this->addEl($detPag, 'tPag', '90');
            $this->addEl($detPag, 'vPag', '0.00');
            $pag->appendChild($detPag);
        } else {
            foreach ($pagamentos as $p) {
                $detPag = $this->dom->createElement('detPag');
                $this->addEl($detPag, 'tPag', $p['forma_pagamento'] ?? '01');
                $this->addEl($detPag, 'vPag', number_format((float)($p['valor'] ?? 0), 2, '.', ''));
                if (in_array($p['forma_pagamento'] ?? '', ['03', '04'])) {
                    $card = $this->dom->createElement('card');
                    $this->addEl($card, 'tpIntegra', '2');
                    if (!empty($p['bandeira_cartao'])) {
                        $this->addEl($card, 'tBand', $p['bandeira_cartao']);
                    }
                    if (!empty($p['cnpj_operadora'])) {
                        $this->addEl($card, 'CNPJ', preg_replace('/\D/', '', $p['cnpj_operadora']));
                    }
                    if (!empty($p['autorizacao'])) {
                        $this->addEl($card, 'cAut', $p['autorizacao']);
                    }
                    $detPag->appendChild($card);
                }
                $pag->appendChild($detPag);
            }
        }

        return $pag;
    }

    private function buildInfAdic() {
        $infAdic = $this->dom->createElement('infAdic');
        if (!empty($this->nota['informacoes_fisco'])) {
            $this->addEl($infAdic, 'infAdFisco', substr($this->nota['informacoes_fisco'], 0, 2000));
        }
        if (!empty($this->nota['informacoes_complementares'])) {
            $this->addEl($infAdic, 'infCpl', substr($this->nota['informacoes_complementares'], 0, 5000));
        }
        return $infAdic;
    }

    // ── Evento genérico ──────────────────────────────────────────────

    private function buildEvento($tpEvento, $chaveAcesso, $cnpj, $sequencia, $dataEvento, callable $detCallback, array $opts = []) {
        $envVersao = $opts['env_versao'] ?? self::VERSAO;
        $verEventoInf = $opts['ver_evento'] ?? self::VERSAO;
        $detVersao = $opts['det_versao'] ?? self::VERSAO;
        $eventoVersao = $opts['evento_versao'] ?? self::VERSAO;

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $envEvento = $doc->createElementNS(self::NAMESPACE_NFE, 'envEvento');
        $envEvento->setAttribute('versao', $envVersao);
        $doc->appendChild($envEvento);

        $this->addElement($doc, $envEvento, 'idLote', (string)random_int(1, 999999999999999));

        $evento = $doc->createElement('evento');
        $evento->setAttribute('versao', $eventoVersao);
        $envEvento->appendChild($evento);

        $infEvento = $doc->createElement('infEvento');
        $infEvento->setAttribute('Id', 'ID' . $tpEvento . $chaveAcesso . str_pad($sequencia, 2, '0', STR_PAD_LEFT));
        $evento->appendChild($infEvento);

        $cuf = substr($chaveAcesso, 0, 2);
        $this->addElement($doc, $infEvento, 'cOrgao', $cuf);
        $this->addElement($doc, $infEvento, 'tpAmb', (string)($this->config['ambiente'] ?? 2));
        $this->addElement($doc, $infEvento, 'CNPJ', preg_replace('/\D/', '', $cnpj));
        $this->addElement($doc, $infEvento, 'chNFe', $chaveAcesso);
        $this->addElement($doc, $infEvento, 'dhEvento', $this->formatDate($dataEvento ?? date('c')));
        $this->addElement($doc, $infEvento, 'tpEvento', $tpEvento);
        $this->addElement($doc, $infEvento, 'nSeqEvento', (string)$sequencia);
        $this->addElement($doc, $infEvento, 'verEvento', $verEventoInf);

        $detEvento = $doc->createElement('detEvento');
        $detEvento->setAttribute('versao', $detVersao);
        $infEvento->appendChild($detEvento);

        $detCallback($detEvento, $doc);

        return $doc->saveXML();
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function addEl(\DOMElement $parent, $tag, $value) {
        $el = $this->dom->createElement($tag, htmlspecialchars((string)$value, ENT_XML1));
        $parent->appendChild($el);
        return $el;
    }

    private function addElement(\DOMDocument $doc, \DOMElement $parent, $tag, $value) {
        $el = $doc->createElement($tag, htmlspecialchars((string)$value, ENT_XML1));
        $parent->appendChild($el);
        return $el;
    }

    private function formatDate($date) {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d\TH:i:sP');
        }
        return date('Y-m-d\TH:i:sP', is_numeric($date) ? $date : strtotime($date));
    }

    private function findImposto(array $impostos, $tipo) {
        foreach ($impostos as $imp) {
            $code = $imp['imposto'] ?? ($imp instanceof \ArrayAccess ? $imp['imposto'] : '');
            if ($code === $tipo) {
                return is_array($imp) ? $imp : $imp->toArray();
            }
        }
        return null;
    }
}
