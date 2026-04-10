<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;

/**
 * Gerador de DANFE (Documento Auxiliar da Nota Fiscal Eletrônica) em PDF.
 *
 * Utiliza mPDF (já presente no projeto) para gerar o PDF do DANFE.
 * Layout simplificado conforme Manual de Orientação do DANFE.
 */
class FiscalDanfe {

    /** @var array Dados da nota fiscal */
    private $nota;

    /** @var array Dados da empresa emitente */
    private $empresa;

    /** @var array Dados do destinatário */
    private $destinatario;

    /** @var array Configuração fiscal */
    private $config;

    public function __construct(array $nota, array $empresa, array $destinatario, array $config) {
        $this->nota = $nota;
        $this->empresa = $empresa;
        $this->destinatario = $destinatario;
        $this->config = $config;
    }

    /**
     * Gera o PDF do DANFE.
     *
     * @param string|null $outputPath Caminho para salvar. NULL = retorna string PDF.
     * @return string Conteúdo do PDF
     */
    public function generate($outputPath = null) {
        $html = $this->buildHtml();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'default_font_size' => 8,
            'default_font' => 'Arial',
        ]);

        $mpdf->SetTitle('DANFE - NFe ' . ($this->nota['numero'] ?? ''));
        $mpdf->SetAuthor('PGM Portal - Módulo Fiscal');
        $mpdf->WriteHTML($html);

        if ($outputPath) {
            $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);
            return $outputPath;
        }

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Retorna o PDF para download direto.
     */
    public function download($filename = null) {
        $html = $this->buildHtml();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'default_font_size' => 8,
            'default_font' => 'Arial',
        ]);

        $nome = $filename ?? 'DANFE_' . ($this->nota['numero'] ?? 'rascunho') . '.pdf';
        $mpdf->WriteHTML($html);
        $mpdf->Output($nome, \Mpdf\Output\Destination::DOWNLOAD);
    }

    /**
     * Gera o HTML do DANFE.
     */
    private function buildHtml() {
        $chave = $this->nota['chave_acesso'] ?? '';
        $chaveFormatada = FiscalValidator::formatarChaveAcesso($chave);
        $protocolo = $this->nota['protocolo_autorizacao'] ?? '';
        $numero = str_pad($this->nota['numero'] ?? 0, 9, '0', STR_PAD_LEFT);
        $serie = str_pad($this->nota['serie'] ?? 1, 3, '0', STR_PAD_LEFT);
        $cnpjEmit = FiscalValidator::formatarCnpj($this->empresa['cnpj'] ?? '');
        $ieEmit = $this->config['inscricao_estadual'] ?? '';
        $status = strtoupper($this->nota['status'] ?? 'RASCUNHO');

        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 8pt; }
            .danfe-border { border: 1px solid #000; padding: 3px; margin-bottom: 2px; }
            .danfe-header { display: flex; border: 1px solid #000; }
            table { width: 100%; border-collapse: collapse; }
            td, th { border: 1px solid #000; padding: 2px 4px; font-size: 7pt; vertical-align: top; }
            th { background: #f0f0f0; font-weight: bold; text-align: left; }
            .label { font-size: 6pt; color: #333; display: block; margin-bottom: 1px; }
            .value { font-size: 8pt; font-weight: bold; }
            .center { text-align: center; }
            .right { text-align: right; }
            .titulo { font-size: 12pt; font-weight: bold; text-align: center; }
            .chave { font-size: 8pt; font-family: monospace; letter-spacing: 1px; }
            .watermark { color: #ccc; font-size: 40pt; text-align: center; transform: rotate(-30deg); position: absolute; top: 40%; left: 15%; }
        </style>';

        // Marca d'água para rascunho/homologação
        if ($this->nota['status'] === 'rascunho' || (int)($this->config['ambiente'] ?? 2) === 2) {
            $label = $this->nota['status'] === 'rascunho' ? 'RASCUNHO' : 'HOMOLOGAÇÃO — SEM VALOR FISCAL';
            $html .= '<div class="watermark">' . $label . '</div>';
        }

        // ── Cabeçalho ────────────────────────────────────────────────
        $html .= '<table>
            <tr>
                <td rowspan="4" style="width: 35%; text-align: center;">
                    <span class="value" style="font-size: 10pt;">' . h($this->empresa['razaosocial'] ?? '') . '</span><br/>
                    <span style="font-size: 7pt;">' . h($this->empresa['nomefantasia'] ?? '') . '</span><br/>
                    <span style="font-size: 7pt;">'
                        . h($this->empresa['endereco'] ?? '') . ', ' . h($this->empresa['numero'] ?? '')
                        . ' — ' . h($this->empresa['bairro'] ?? '') . '<br/>'
                        . h($this->empresa['cidade'] ?? '') . '/' . h($this->config['uf'] ?? '')
                        . ' — CEP: ' . h($this->empresa['cep'] ?? '')
                    . '</span><br/>
                    <span style="font-size: 7pt;">Fone: ' . h($this->empresa['fone'] ?? '') . '</span>
                </td>
                <td class="center" style="width: 30%;">
                    <span class="titulo">DANFE</span><br/>
                    <span style="font-size: 7pt;">DOCUMENTO AUXILIAR DA<br/>NOTA FISCAL ELETRÔNICA</span>
                </td>
                <td style="width: 35%;">
                    <span class="label">CHAVE DE ACESSO</span>
                    <span class="chave">' . h($chaveFormatada) . '</span>
                </td>
            </tr>
            <tr>
                <td class="center">
                    <span class="label">ENTRADA/SAÍDA</span>
                    <span class="value">' . ($this->nota['tipo_operacao'] == 0 ? 'ENTRADA' : 'SAÍDA') . '</span>
                </td>
                <td>
                    <span class="label">PROTOCOLO DE AUTORIZAÇÃO</span>
                    <span class="value">' . h($protocolo) . '</span>
                </td>
            </tr>
            <tr>
                <td class="center">
                    <span class="label">Nº</span>
                    <span class="value" style="font-size: 11pt;">' . $numero . '</span><br/>
                    <span class="label">SÉRIE</span>
                    <span class="value">' . $serie . '</span>
                </td>
                <td>
                    <span class="label">NATUREZA DA OPERAÇÃO</span>
                    <span class="value">' . h($this->nota['natureza_operacao'] ?? '') . '</span>
                </td>
            </tr>
            <tr>
                <td class="center">
                    <span class="label">STATUS</span>
                    <span class="value">' . $status . '</span>
                </td>
                <td>
                    <span class="label">INSCRIÇÃO ESTADUAL</span>
                    <span class="value">' . h($ieEmit) . '</span><br/>
                    <span class="label">CNPJ</span>
                    <span class="value">' . h($cnpjEmit) . '</span>
                </td>
            </tr>
        </table>';

        // ── Destinatário ─────────────────────────────────────────────
        $docDest = $this->destinatario['cnpj'] ?? $this->destinatario['cpf'] ?? '';
        $html .= '<table>
            <tr>
                <td colspan="4" style="background: #f0f0f0; font-weight: bold;">DESTINATÁRIO / REMETENTE</td>
            </tr>
            <tr>
                <td style="width: 50%;">
                    <span class="label">NOME / RAZÃO SOCIAL</span>
                    <span class="value">' . h($this->destinatario['razaosocial'] ?? $this->destinatario['nome'] ?? '') . '</span>
                </td>
                <td style="width: 25%;">
                    <span class="label">CNPJ/CPF</span>
                    <span class="value">' . h($docDest) . '</span>
                </td>
                <td style="width: 25%;">
                    <span class="label">DATA EMISSÃO</span>
                    <span class="value">' . $this->formatDate($this->nota['data_emissao'] ?? '') . '</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">ENDEREÇO</span>
                    <span class="value">' . h(($this->destinatario['endereco'] ?? '') . ', ' . ($this->destinatario['numero'] ?? '')) . '</span>
                </td>
                <td>
                    <span class="label">BAIRRO</span>
                    <span class="value">' . h($this->destinatario['bairro'] ?? '') . '</span>
                </td>
                <td>
                    <span class="label">DATA SAÍDA/ENTRADA</span>
                    <span class="value">' . $this->formatDate($this->nota['data_saida'] ?? '') . '</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">MUNICÍPIO</span>
                    <span class="value">' . h($this->destinatario['cidade'] ?? '') . '</span>
                </td>
                <td>
                    <span class="label">UF</span>
                    <span class="value">' . h($this->destinatario['uf'] ?? '') . '</span>
                </td>
                <td>
                    <span class="label">CEP</span>
                    <span class="value">' . h($this->destinatario['cep'] ?? '') . '</span>
                </td>
            </tr>
        </table>';

        // ── Itens ────────────────────────────────────────────────────
        $html .= '<table>
            <tr>
                <th style="width:6%;">Cód.</th>
                <th style="width:30%;">Descrição</th>
                <th style="width:8%;">NCM</th>
                <th style="width:6%;">CFOP</th>
                <th style="width:5%;">UN</th>
                <th style="width:8%;" class="right">Qtde</th>
                <th style="width:9%;" class="right">Vlr Unit</th>
                <th style="width:9%;" class="right">Vlr Total</th>
                <th style="width:5%;" class="right">BC ICMS</th>
                <th style="width:5%;" class="right">Vlr ICMS</th>
                <th style="width:5%;" class="right">Vlr IPI</th>
                <th style="width:4%;">CST</th>
            </tr>';

        $itens = $this->nota['fiscal_notas_itens'] ?? [];
        foreach ($itens as $item) {
            $impostos = $item['fiscal_notas_impostos'] ?? [];
            $icmsData = $this->findImposto($impostos, 'ICMS');
            $ipiData = $this->findImposto($impostos, 'IPI');

            $html .= '<tr>
                <td>' . h($item['codigo_produto'] ?? '') . '</td>
                <td>' . h($item['descricao'] ?? '') . '</td>
                <td>' . h($item['ncm'] ?? '') . '</td>
                <td>' . h($item['cfop'] ?? '') . '</td>
                <td>' . h($item['unidade'] ?? '') . '</td>
                <td class="right">' . number_format((float)($item['quantidade'] ?? 0), 4, ',', '.') . '</td>
                <td class="right">' . number_format((float)($item['valor_unitario'] ?? 0), 4, ',', '.') . '</td>
                <td class="right">' . number_format((float)($item['valor_total'] ?? 0), 2, ',', '.') . '</td>
                <td class="right">' . ($icmsData ? number_format((float)$icmsData['base_calculo'], 2, ',', '.') : '') . '</td>
                <td class="right">' . ($icmsData ? number_format((float)$icmsData['valor'], 2, ',', '.') : '') . '</td>
                <td class="right">' . ($ipiData ? number_format((float)$ipiData['valor'], 2, ',', '.') : '') . '</td>
                <td class="center">' . h($item['icms_cst'] ?? '') . '</td>
            </tr>';
        }

        $html .= '</table>';

        // ── Totais ───────────────────────────────────────────────────
        $html .= '<table>
            <tr>
                <td colspan="8" style="background: #f0f0f0; font-weight: bold;">CÁLCULO DO IMPOSTO</td>
            </tr>
            <tr>
                <td><span class="label">BASE CÁLC. ICMS</span><span class="value right">' . $this->fmtBrl($this->nota['valor_icms'] ?? 0) . '</span></td>
                <td><span class="label">VALOR ICMS</span><span class="value right">' . $this->fmtBrl($this->nota['valor_icms'] ?? 0) . '</span></td>
                <td><span class="label">VALOR ICMS-ST</span><span class="value right">' . $this->fmtBrl($this->nota['valor_icms_st'] ?? 0) . '</span></td>
                <td><span class="label">VALOR IPI</span><span class="value right">' . $this->fmtBrl($this->nota['valor_ipi'] ?? 0) . '</span></td>
                <td><span class="label">VALOR PIS</span><span class="value right">' . $this->fmtBrl($this->nota['valor_pis'] ?? 0) . '</span></td>
                <td><span class="label">VALOR COFINS</span><span class="value right">' . $this->fmtBrl($this->nota['valor_cofins'] ?? 0) . '</span></td>
                <td><span class="label">VALOR FRETE</span><span class="value right">' . $this->fmtBrl($this->nota['valor_frete'] ?? 0) . '</span></td>
                <td><span class="label">VALOR TOTAL</span><span class="value right" style="font-size: 10pt;">' . $this->fmtBrl($this->nota['valor_total'] ?? 0) . '</span></td>
            </tr>
        </table>';

        // ── Informações adicionais ───────────────────────────────────
        if (!empty($this->nota['informacoes_complementares'])) {
            $html .= '<table>
                <tr><td style="background: #f0f0f0; font-weight: bold;">INFORMAÇÕES COMPLEMENTARES</td></tr>
                <tr><td style="font-size: 7pt; min-height: 40px;">' . nl2br(h($this->nota['informacoes_complementares'])) . '</td></tr>
            </table>';
        }

        return $html;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function fmtBrl($valor) {
        return number_format((float)$valor, 2, ',', '.');
    }

    private function formatDate($date) {
        if (empty($date)) return '';
        if ($date instanceof \DateTimeInterface) {
            return $date->format('d/m/Y');
        }
        $ts = is_numeric($date) ? $date : strtotime($date);
        return $ts ? date('d/m/Y', $ts) : '';
    }

    private function findImposto($impostos, $tipo) {
        foreach ($impostos as $imp) {
            $code = is_array($imp) ? ($imp['imposto'] ?? '') : ($imp->imposto ?? '');
            if ($code === $tipo) {
                return is_array($imp) ? $imp : $imp->toArray();
            }
        }
        return null;
    }
}
