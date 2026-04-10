<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Gerador de arquivo SPED Fiscal (EFD-ICMS/IPI) — Registros básicos.
 *
 * Gera o arquivo texto no layout definido pelo Guia Prático EFD-ICMS/IPI.
 * Blocos implementados: 0 (Abertura), C (Documentos Fiscais), E (Apuração ICMS), H (Inventário — stub), 9 (Controle/Encerramento).
 *
 * NOTA: O SPED Fiscal tem centenas de registros. Esta implementação cobre os registros
 * mais comuns para notas fiscais de saída/entrada. Registros avançados devem ser adicionados
 * conforme necessidade.
 */
class FiscalSpedGenerator {

    /** @var array Dados da empresa */
    private $empresa;

    /** @var array Config fiscal da empresa */
    private $configFiscal;

    /** @var string Período início (Y-m-d) */
    private $periodoInicio;

    /** @var string Período fim (Y-m-d) */
    private $periodoFim;

    /** @var array Notas fiscais do período */
    private $notas;

    /** @var array Linhas do arquivo SPED */
    private $linhas = [];

    /** @var array<string, int> Contadores de registros por bloco */
    private $contadores = [];

    public function __construct(array $empresa, array $configFiscal, string $periodoInicio, string $periodoFim) {
        $this->empresa = $empresa;
        $this->configFiscal = $configFiscal;
        $this->periodoInicio = $periodoInicio;
        $this->periodoFim = $periodoFim;
    }

    /**
     * Gera o conteúdo do arquivo SPED Fiscal.
     *
     * @return string Conteúdo do arquivo texto SPED
     */
    public function gerar(): string {
        $this->linhas = [];
        $this->contadores = [];
        $this->carregarNotas();

        $this->bloco0();
        $this->blocoC();
        $this->blocoE();
        $this->blocoH();
        $this->bloco9();

        return implode("\r\n", $this->linhas) . "\r\n";
    }

    private function carregarNotas() {
        $FiscalNotas = TableRegistry::getTableLocator()->get('FiscalNotas');
        $idempresa = $this->empresa['id'] ?? 0;

        $this->notas = $FiscalNotas->find()
            ->contain([
                'FiscalNotasItens' => ['FiscalNotasImpostos'],
                'FiscalNotasPagamentos',
                'Clientes',
            ])
            ->where([
                'FiscalNotas.idempresa' => $idempresa,
                'FiscalNotas.status' => 'autorizada',
                'FiscalNotas.data_emissao >=' => $this->periodoInicio,
                'FiscalNotas.data_emissao <=' => $this->periodoFim . ' 23:59:59',
            ])
            ->order(['FiscalNotas.data_emissao' => 'ASC'])
            ->toArray();
    }

    private function addLinha($reg, $campos) {
        $line = '|' . $reg . '|' . implode('|', $campos) . '|';
        $this->linhas[] = $line;
        $bloco = substr($reg, 0, 1);
        if (!isset($this->contadores[$bloco])) {
            $this->contadores[$bloco] = 0;
        }
        $this->contadores[$bloco]++;
    }

    // ── Bloco 0 — Abertura ──────────────────────────────

    private function bloco0() {
        $cnpj = preg_replace('/\D/', '', (string)($this->empresa['cnpj'] ?? ''));
        $ie = preg_replace('/\D/', '', (string)($this->empresa['ie'] ?? ''));
        $nome = strtoupper(mb_substr((string)($this->empresa['razaosocial'] ?? $this->empresa['nome'] ?? ''), 0, 100));
        $uf = strtoupper((string)($this->configFiscal['uf'] ?? 'SP'));
        $codMun = (string)($this->configFiscal['codigo_municipio'] ?? '');
        $cep = preg_replace('/\D/', '', (string)($this->empresa['cep'] ?? ''));

        $dtIni = date('dmY', strtotime($this->periodoInicio));
        $dtFin = date('dmY', strtotime($this->periodoFim));

        // 0000 — Abertura
        $this->addLinha('0000', [
            '015',       // COD_VER layout 015
            '0',         // COD_FIN (0=remessa original)
            $dtIni,
            $dtFin,
            $nome,
            $cnpj,
            '',          // CPF
            $uf,
            $ie,
            $codMun,
            '',          // IM
            '',          // SUFRAMA
            '0',         // IND_PERFIL (A)
            '1',         // IND_ATIV (1=Industrial/equiparado)
        ]);

        // 0001 — Abertura bloco 0
        $this->addLinha('0001', ['0']);

        // 0005 — Dados complementares
        $this->addLinha('0005', [
            $this->empresa['fantasia'] ?? '',
            $cep,
            $this->empresa['endereco'] ?? '',
            $this->empresa['numero'] ?? '',
            $this->empresa['complemento'] ?? '',
            $this->empresa['bairro'] ?? '',
            $this->empresa['telefone'] ?? '',
            '',  // FAX
            $this->empresa['email'] ?? '',
        ]);

        // 0100 — Contabilista (stub)
        $this->addLinha('0100', [
            '', '', '', '', '', '', '', '', '', '', '', '', '',
        ]);

        // 0990 — Encerramento bloco 0 (contado no bloco 9)
        $this->addLinha('0990', ['']); // qtd preenchida no bloco9
    }

    // ── Bloco C — Documentos Fiscais ──────────────────

    private function blocoC() {
        // C001 — Abertura
        $this->addLinha('C001', [empty($this->notas) ? '1' : '0']);

        foreach ($this->notas as $nota) {
            $notaArr = $nota->toArray();
            $dtEmissao = $nota->data_emissao ? $nota->data_emissao->format('dmY') : '';
            $dtEntSai = $dtEmissao;
            $modelo = (string)($nota->modelo ?? '55');
            $tipoOp = (int)($nota->tipo_operacao ?? 1);
            $chave = (string)($nota->chave_acesso ?? '');

            $cli = $nota->cliente ?? null;
            $cnpjDest = $cli ? preg_replace('/\D/', '', (string)($cli->cnpj ?? $cli->cpf ?? '')) : '';

            // C100 — Nota fiscal (modelo 55/65)
            $this->addLinha('C100', [
                $tipoOp === 0 ? '0' : '1',  // IND_OPER (0=entrada, 1=saída)
                '1',                          // IND_EMIT (1=própria)
                strlen($cnpjDest) === 14 ? '0' : '1',  // COD_PART type
                $modelo,
                '00',                         // COD_SIT (00=regular)
                (string)($nota->serie ?? ''),
                (string)($nota->numero ?? ''),
                $chave,
                $dtEmissao,
                $dtEntSai,
                number_format((float)($nota->valor_total ?? 0), 2, ',', ''),
                '0',                          // IND_PGTO
                number_format((float)($nota->valor_desconto ?? 0), 2, ',', ''),
                '0,00',                       // VL_ABAT
                number_format((float)($nota->valor_total ?? 0), 2, ',', ''),
                '9',                          // IND_FRT
                '0,00',                       // VL_FRT
                '0,00',                       // VL_SEG
                '0,00',                       // VL_OUT_DA
                number_format((float)($nota->valor_icms_base ?? 0), 2, ',', ''),
                number_format((float)($nota->valor_icms ?? 0), 2, ',', ''),
                '0,00',                       // VL_BC_ICMS_ST
                '0,00',                       // VL_ICMS_ST
                '0,00',                       // VL_IPI
                number_format((float)($nota->valor_pis ?? 0), 2, ',', ''),
                number_format((float)($nota->valor_cofins ?? 0), 2, ',', ''),
                '0,00',                       // VL_PIS_ST
                '0,00',                       // VL_COFINS_ST
            ]);

            // C170 — Itens
            $itens = $nota->fiscal_notas_itens ?? [];
            foreach ($itens as $item) {
                $this->addLinha('C170', [
                    (string)($item->numero_item ?? ''),
                    (string)($item->codigo_produto ?? $item->descricao ?? ''),
                    mb_substr((string)($item->descricao ?? ''), 0, 100),
                    number_format((float)($item->quantidade ?? 0), 5, ',', ''),
                    (string)($item->unidade ?? 'UN'),
                    number_format((float)($item->valor_total ?? 0), 2, ',', ''),
                    '0,00',                     // VL_DESC
                    '0',                        // IND_MOV
                    (string)($item->cst_icms ?? ''),
                    (string)($item->cfop ?? ''),
                    '',                         // COD_NAT
                    number_format((float)($item->valor_icms_base ?? 0), 2, ',', ''),
                    number_format((float)($item->aliquota_icms ?? 0), 2, ',', ''),
                    number_format((float)($item->valor_icms ?? 0), 2, ',', ''),
                    '0,00',                     // VL_BC_ICMS_ST
                    '0,00',                     // ALIQ_ST
                    '0,00',                     // VL_ICMS_ST
                    '',                         // IND_APUR
                    (string)($item->cst_ipi ?? ''),
                    '',                         // COD_ENQ
                    '0,00',                     // VL_BC_IPI
                    '0,00',                     // ALIQ_IPI
                    '0,00',                     // VL_IPI
                    (string)($item->cst_pis ?? ''),
                    number_format((float)($item->valor_pis_base ?? 0), 2, ',', ''),
                    number_format((float)($item->aliquota_pis ?? 0), 2, ',', ''),
                    number_format((float)($item->valor_pis ?? 0), 2, ',', ''),
                    (string)($item->cst_cofins ?? ''),
                    number_format((float)($item->valor_cofins_base ?? 0), 2, ',', ''),
                    number_format((float)($item->aliquota_cofins ?? 0), 2, ',', ''),
                    number_format((float)($item->valor_cofins ?? 0), 2, ',', ''),
                    '',                         // COD_CTA
                ]);
            }
        }

        // C990 — Encerramento bloco C
        $this->addLinha('C990', ['']);
    }

    // ── Bloco E — Apuração ICMS ──────────────────────

    private function blocoE() {
        $this->addLinha('E001', [empty($this->notas) ? '1' : '0']);

        $dtIni = date('dmY', strtotime($this->periodoInicio));
        $dtFin = date('dmY', strtotime($this->periodoFim));

        // E100 — Período de apuração
        $this->addLinha('E100', [$dtIni, $dtFin]);

        // E110 — Apuração ICMS (totais)
        $totalIcms = 0;
        $totalBase = 0;
        foreach ($this->notas as $nota) {
            if ((int)($nota->tipo_operacao ?? 1) === 1) {
                $totalIcms += (float)($nota->valor_icms ?? 0);
                $totalBase += (float)($nota->valor_icms_base ?? 0);
            }
        }
        $fmt = function ($v) {
            return number_format((float)$v, 2, ',', '');
        };
        $this->addLinha('E110', [
            $fmt($totalIcms),  // VL_TOT_DEBITOS
            '0,00',            // VL_AJ_DEBITOS
            $fmt($totalIcms),  // VL_TOT_AJ_DEBITOS
            '0,00',            // VL_ESTORNOS_CRED
            '0,00',            // VL_TOT_CREDITOS
            '0,00',            // VL_AJ_CREDITOS
            '0,00',            // VL_TOT_AJ_CREDITOS
            '0,00',            // VL_ESTORNOS_DEB
            $fmt($totalIcms),  // VL_SLD_CREDOR_ANT
            $fmt($totalIcms),  // VL_SLD_APURADO
            '0,00',            // VL_TOT_DED
            $fmt($totalIcms),  // VL_ICMS_RECOLHER
            '0,00',            // VL_SLD_CREDOR_TRANSPORTAR
            '0,00',            // DEB_ESP
        ]);

        $this->addLinha('E990', ['']);
    }

    // ── Bloco H — Inventário (stub) ──────────────────

    private function blocoH() {
        $this->addLinha('H001', ['1']); // sem dados
        $this->addLinha('H990', ['']);
    }

    // ── Bloco 9 — Controle e Encerramento ────────────

    private function bloco9() {
        $this->addLinha('9001', ['0']);

        // 9900 — Registros do arquivo
        foreach ($this->contadores as $bloco => $count) {
            // +2 para o próprio 9900 e 9990/9999
        }
        $totalLinhas = count($this->linhas) + 4; // 9001 + 9900 entries + 9990 + 9999

        $this->addLinha('9900', ['9900', (string)count($this->contadores)]);
        $this->addLinha('9900', ['9999', '1']);

        $this->addLinha('9990', [(string)(count($this->linhas) - ($this->contadores['9'] ?? 0) + 3)]);
        $this->addLinha('9999', [(string)(count($this->linhas) + 1)]);
    }
}
