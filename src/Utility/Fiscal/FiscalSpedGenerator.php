<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Gerador de arquivo SPED Fiscal (EFD-ICMS/IPI) — Registros básicos.
 *
 * Gera o arquivo texto no layout definido pelo Guia Prático EFD-ICMS/IPI.
 * Blocos implementados: 0 (0150…0460), B/D/G/K/1 (abertura+encerramento sem movimento), C (C100/C110/C170/C190), E (E100/E110/E111), H, 9.
 * Encerramentos 0990/C990/E990/H990/K990 com QTD_LIN do bloco; bloco 9 com 9900/9990/9999 por contagem real das linhas.
 * Registro 0000: COD_VER via Fiscal.sped.cod_ver_layout / FISCAL_SPED_COD_VER (3 dígitos; padrão 015).
 * Registro 0100: dados em fiscal_empresas_config (sped_contabilista_*) e Fiscal.sped.registro_0100_modo (omitir_sem_dados | sempre_stub).
 * Bloco H: H001=1 sem inventário; com sped_inventario_declarar + data + MOT_INV + JSON de itens válido → H001=0, H005, H010(s).
 * Bloco K: K001/K990 sem movimento (K001 IND_MOV=1) quando não há RCPE; registros K010/K100/… ficam fora até necessidade explícita.
 * Bloco C: C100 + C110 (NF-e 55 → 0450); C170 (COD_NAT → 0400) / C190 (COD_OBS → 0460, opcional via sped_0460_c190_json); COD_ITEM no 0200; 0150/COD_PART (exceto 65);
 *   NFC-e (65) dispensa ST/IPI/PIS/COFINS no mestre C100; C190 agrupa CST/CFOP/alíquota com VL_OPR coerente aos itens.
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
    protected $notas;

    /** @var array Linhas do arquivo SPED */
    private $linhas = [];

    /** @var array<string, int> Contadores de registros por bloco */
    private $contadores = [];

    /** @var array<int, string> id do cliente → COD_PART (exclui NFC-e) */
    private $codPartPorClienteId = [];

    /** @var array<string, string> código UNID (até 6) → DESCR (registro 0190) */
    private $mapaUnidades0190 = [];

    /**
     * @var array<string, array{descr: string, unid: string, ncm: string, cest: string, aliq: float, tipo_item: string}>
     */
    private $mapaItens0200 = [];

    /** @var array<string, string> COD_INF (6) → texto completo (registro 0450) */
    private $mapa0450Informacoes = [];

    /** @var array<string, string> chave interna da nota → COD_INF */
    private $codInf0450PorChaveNota = [];

    /** @var array<string, string> COD_NAT (≤10) → DESCR_NAT (registro 0400) */
    private $mapa0400Naturezas = [];

    /** @var array<string, string> chave interna da nota → COD_NAT */
    private $codNat0400PorChaveNota = [];

    /** @var array<string, string> COD_OBS (≤6) → texto (registro 0460) */
    private $mapa0460TextoPorCod = [];

    /** @var array<string, string> chave CST|CFOP|ALIQ → COD_OBS para C190 */
    private $mapaC190CodObsPorChaveBucket = [];

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
        $this->codPartPorClienteId = [];
        $this->mapaUnidades0190 = [];
        $this->mapaItens0200 = [];
        $this->mapa0450Informacoes = [];
        $this->codInf0450PorChaveNota = [];
        $this->mapa0400Naturezas = [];
        $this->codNat0400PorChaveNota = [];
        $this->mapa0460TextoPorCod = [];
        $this->mapaC190CodObsPorChaveBucket = [];
        $this->carregarNotas();
        $this->prepararMapaParticipantesCodPart();
        $this->prepararMapaItens0200e0190();
        $this->prepararMapa0400Naturezas();
        $this->prepararMapa0450Informacoes();
        $this->prepararMapa0460C190();

        $this->bloco0();
        $this->blocoB();
        $this->blocoC();
        $this->blocoD();
        $this->blocoE();
        $this->blocoG();
        $this->blocoH();
        $this->blocoK();
        $this->bloco1();
        $this->bloco9();

        return implode("\r\n", $this->linhas) . "\r\n";
    }

    protected function carregarNotas() {
        $FiscalNotas = TableRegistry::getTableLocator()->get('FiscalNotas');
        $idempresa = $this->empresa['id'] ?? 0;

        $this->notas = $FiscalNotas->find()
            ->contain([
                'FiscalNotasItens' => ['FiscalNotasImpostos'],
                'FiscalNotasPagamentos',
                'FiscalNaturezaOperacao',
                'Clientes' => ['Cidades'],
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

    /**
     * Linhas já em $this->linhas cujo código de registro começa com o caractere indicado
     * (Bloco 0 → "0", B → "B", C → "C", D → "D", E → "E", G → "G", H → "H", K → "K", bloco 1 → "1").
     */
    private function contarLinhasRegistroPrefixo(string $primeiroChar): int {
        $n = 0;
        foreach ($this->linhas as $line) {
            $parts = explode('|', $line);
            if (count($parts) < 2) {
                continue;
            }
            $reg = $parts[1];
            if ($reg === '') {
                continue;
            }
            if (($reg[0] ?? '') === $primeiroChar) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * Modo do registro 0100: omitir_sem_dados | sempre_stub.
     */
    private function registro0100Modo(): string {
        $m = Configure::read('Fiscal.sped.registro_0100_modo');
        if (!is_string($m) || $m === '') {
            return 'omitir_sem_dados';
        }

        return in_array($m, ['omitir_sem_dados', 'sempre_stub'], true) ? $m : 'omitir_sem_dados';
    }

    /**
     * Campos do registro 0100 (13 posições após o REG), ou null para não emitir a linha.
     *
     * @return array<int, string>|null
     */
    private function montarCamposRegistro0100(): ?array {
        if ($this->registro0100Modo() === 'sempre_stub') {
            return array_fill(0, 13, '');
        }

        $cf = $this->configFiscal;
        $nome = strtoupper(mb_substr(trim((string)($cf['sped_contabilista_nome'] ?? '')), 0, 100));
        $cpf = preg_replace('/\D/', '', (string)($cf['sped_contabilista_cpf'] ?? ''));
        $crc = mb_substr(trim((string)($cf['sped_contabilista_crc'] ?? '')), 0, 15);
        $email = trim((string)($cf['sped_contabilista_email'] ?? ''));

        $codMunRaw = preg_replace('/\D/', '', (string)($cf['sped_contabilista_cod_municipio'] ?? ''));
        $codMun = '';
        if ($codMunRaw !== '') {
            $codMun = strlen($codMunRaw) >= 7 ? substr($codMunRaw, -7) : str_pad($codMunRaw, 7, '0', STR_PAD_LEFT);
        }

        $dadosMinimos = ($nome !== '' && strlen($cpf) === 11 && $crc !== '' && $email !== '' && strlen($codMun) === 7);
        if (!$dadosMinimos) {
            return null;
        }

        $cnpj = preg_replace('/\D/', '', (string)($cf['sped_contabilista_cnpj'] ?? ''));
        if (strlen($cnpj) > 14) {
            $cnpj = substr($cnpj, 0, 14);
        }

        $cep = preg_replace('/\D/', '', (string)($cf['sped_contabilista_cep'] ?? ''));
        $cep = substr($cep, 0, 8);

        $fone = preg_replace('/\D/', '', (string)($cf['sped_contabilista_fone'] ?? ''));
        $fone = substr($fone, 0, 11);
        $fax = preg_replace('/\D/', '', (string)($cf['sped_contabilista_fax'] ?? ''));
        $fax = substr($fax, 0, 11);

        return [
            $nome,
            $cpf,
            $crc,
            $cnpj,
            $cep,
            mb_substr(trim((string)($cf['sped_contabilista_logradouro'] ?? '')), 0, 60),
            mb_substr(trim((string)($cf['sped_contabilista_numero'] ?? '')), 0, 10),
            mb_substr(trim((string)($cf['sped_contabilista_complemento'] ?? '')), 0, 60),
            mb_substr(trim((string)($cf['sped_contabilista_bairro'] ?? '')), 0, 60),
            $fone,
            $fax,
            mb_substr($email, 0, 255),
            $codMun,
        ];
    }

    private function prepararMapaParticipantesCodPart(): void {
        $this->codPartPorClienteId = [];
        $idsUnicos = [];
        foreach ($this->notas as $nota) {
            if ((string)($nota->modelo ?? '') === '65') {
                continue;
            }
            $cid = (int)($nota->idcliente ?? 0);
            if ($cid > 0) {
                $idsUnicos[$cid] = true;
            }
        }
        $ids = array_keys($idsUnicos);
        sort($ids, SORT_NUMERIC);
        $seq = 1;
        foreach ($ids as $id) {
            $this->codPartPorClienteId[$id] = 'C' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
            $seq++;
        }
    }

    /**
     * Cliente da primeira nota (não NFC-e) com o id informado.
     *
     * @return \Cake\Datasource\EntityInterface|object|null
     */
    private function clienteRepresentativoPorId(int $idCliente) {
        foreach ($this->notas as $nota) {
            if ((string)($nota->modelo ?? '') === '65') {
                continue;
            }
            if ((int)($nota->idcliente ?? 0) !== $idCliente) {
                continue;
            }
            $c = $nota->cliente ?? null;
            if ($c !== null) {
                return $c;
            }
        }

        return null;
    }

    private function codigoMunicipioIbgeCliente($cliente): string {
        $cidade = $cliente->cidade ?? null;
        if ($cidade === null) {
            return '';
        }
        $raw = preg_replace('/\D/', '', (string)($cidade->codibge ?? ''));
        if ($raw === '') {
            return '';
        }

        return strlen($raw) >= 7 ? substr($raw, -7) : str_pad($raw, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Registro 0150 — cadastro de participante (layout básico).
     *
     * @param \Cake\Datasource\EntityInterface|object $cliente
     * @return array<int, string>
     */
    private function montarCampos0150($cliente, string $codPart): array {
        $tipo = (int)($cliente->tipo ?? 2);
        $nomeRaw = $tipo === 1
            ? (string)($cliente->nome ?? '')
            : (string)($cliente->razaosocial ?? '');
        if (trim($nomeRaw) === '') {
            $nomeRaw = (string)($cliente->razaosocial ?? $cliente->nome ?? '');
        }
        $nome = strtoupper(mb_substr(trim($nomeRaw), 0, 100));

        $cnpj = preg_replace('/\D/', '', (string)($cliente->cnpj ?? ''));
        $cpf = preg_replace('/\D/', '', (string)($cliente->cpf ?? ''));
        $cnpjOut = '';
        $cpfOut = '';
        if ($tipo === 2) {
            if (strlen($cnpj) > 14) {
                $cnpj = substr($cnpj, 0, 14);
            }
            $cnpjOut = $cnpj;
        } elseif (strlen($cpf) === 11) {
            $cpfOut = $cpf;
        }

        $ie = strtoupper(preg_replace('/\s+/', '', (string)($cliente->inscricaoestadual ?? '')));
        $ie = mb_substr($ie, 0, 14);

        return [
            $codPart,
            $nome,
            '105',
            $cnpjOut,
            $cpfOut,
            $ie,
            $this->codigoMunicipioIbgeCliente($cliente),
            '',
            mb_substr(trim((string)($cliente->endereco ?? '')), 0, 60),
            mb_substr(trim((string)($cliente->numero ?? '')), 0, 10),
            mb_substr(trim((string)($cliente->complemento ?? '')), 0, 60),
            mb_substr(trim((string)($cliente->bairro ?? '')), 0, 60),
        ];
    }

    private function emitirRegistros0150(): void {
        foreach ($this->codPartPorClienteId as $idCliente => $codPart) {
            $cli = $this->clienteRepresentativoPorId((int)$idCliente);
            if ($cli === null) {
                continue;
            }
            $this->addLinha('0150', $this->montarCampos0150($cli, $codPart));
        }
    }

    /**
     * Monta mapas de unidades (0190) e itens (0200) a partir dos itens das notas do período.
     */
    private function prepararMapaItens0200e0190(): void {
        $this->mapaUnidades0190 = [];
        $this->mapaItens0200 = [];

        foreach ($this->notas as $nota) {
            foreach ($nota->fiscal_notas_itens ?? [] as $item) {
                $un = $this->unidadeInventarioSped($item);
                if (!isset($this->mapaUnidades0190[$un])) {
                    $this->mapaUnidades0190[$un] = $this->descricaoUnidadeSped($un);
                }

                $cod = $this->codigoItemSped($item);
                $m = $this->metricasItemSpedImpostos($item);
                $ncm = $this->ncmOitoDigitos($item);
                $cest = $this->cestSeteDigitos($item);
                $tipo = $this->tipoItemSpedPadrao();
                $aliq = (float)$m['aliq_icms'];
                $descr = trim((string)($item->descricao ?? ''));
                if ($descr === '') {
                    $descr = $cod;
                }
                $descr = mb_substr($descr, 0, 500);

                if (!isset($this->mapaItens0200[$cod])) {
                    $this->mapaItens0200[$cod] = [
                        'descr' => $descr,
                        'unid' => $un,
                        'ncm' => $ncm,
                        'cest' => $cest,
                        'aliq' => $aliq,
                        'tipo_item' => $tipo,
                    ];
                } else {
                    $ex = &$this->mapaItens0200[$cod];
                    if ($ex['ncm'] === '' && $ncm !== '') {
                        $ex['ncm'] = $ncm;
                    }
                    if ($ex['cest'] === '' && $cest !== '') {
                        $ex['cest'] = $cest;
                    }
                    if ($aliq > $ex['aliq']) {
                        $ex['aliq'] = $aliq;
                    }
                }
            }
        }
        ksort($this->mapaUnidades0190, SORT_STRING);
        ksort($this->mapaItens0200, SORT_STRING);
    }

    private function emitirRegistros0190(): void {
        foreach ($this->mapaUnidades0190 as $unid => $descr) {
            $this->addLinha('0190', [$unid, $descr]);
        }
    }

    private function emitirRegistros0200(): void {
        foreach ($this->mapaItens0200 as $codItem => $row) {
            $aliq = (float)($row['aliq'] ?? 0);
            $aliqFmt = $aliq > 0.0 ? $this->fmtSped($aliq, 2) : '';
            $this->addLinha('0200', [
                $codItem,
                $row['descr'],
                '',
                '',
                $row['unid'],
                $row['tipo_item'],
                $row['ncm'],
                '',
                '',
                '',
                $aliqFmt,
                $row['cest'],
            ]);
        }
    }

    private function chaveInternaNotaSped($nota): string {
        $id = (int)($nota->id ?? 0);
        if ($id > 0) {
            return 'ID:' . $id;
        }

        return 'CH:' . (string)($nota->chave_acesso ?? '');
    }

    /**
     * COD_NAT alfanumérico até 10 posições (registro 0400 / campo 12 do C170).
     */
    private function normalizarCodNatSped(string $raw): string {
        $s = strtoupper(preg_replace('/[^A-Z0-9]/', '', $raw));

        return mb_substr($s, 0, 10);
    }

    /**
     * Tabela 0400 + vínculo por nota (COD_NAT no C170).
     */
    private function prepararMapa0400Naturezas(): void {
        $this->mapa0400Naturezas = [];
        $this->codNat0400PorChaveNota = [];
        foreach ($this->notas as $nota) {
            $par = $this->resolverParCodDescr0400($nota);
            if ($par === null) {
                continue;
            }
            [$cod, $descr] = $par;
            $chaveNota = $this->chaveInternaNotaSped($nota);
            $n = 0;
            while (isset($this->mapa0400Naturezas[$cod]) && $this->mapa0400Naturezas[$cod] !== $descr) {
                $n++;
                $cod = 'T' . strtoupper(substr(sha1($descr . '|' . $cod . '|' . $n . '|' . $chaveNota), 0, 9));
                if ($n > 100) {
                    break;
                }
            }
            $this->mapa0400Naturezas[$cod] = $descr;
            $this->codNat0400PorChaveNota[$chaveNota] = $cod;
        }
        ksort($this->mapa0400Naturezas, SORT_STRING);
    }

    /**
     * @return array{0: string, 1: string}|null [cod_nat, descr_nat]
     */
    private function resolverParCodDescr0400($nota): ?array {
        $natText = trim((string)($nota->natureza_operacao ?? ''));
        $idNat = (int)($nota->natureza_operacao_id ?? 0);
        $ent = $nota->fiscal_natureza_operacao ?? null;

        $descr = '';
        if ($ent !== null) {
            $descr = trim((string)($ent->descricao ?? ''));
        }
        if ($descr === '' && $natText !== '') {
            $descr = $natText;
        }
        if ($descr === '') {
            return null;
        }
        $descr = mb_substr($descr, 0, 255);

        $cod = '';
        if ($ent !== null) {
            $codigoEnt = trim((string)($ent->codigo ?? ''));
            if ($codigoEnt !== '') {
                $cod = $this->normalizarCodNatSped($codigoEnt);
            }
        }
        if ($cod === '' && $idNat > 0) {
            $cod = 'N' . str_pad((string)$idNat, 9, '0', STR_PAD_LEFT);
        }
        if ($cod === '') {
            $base = $natText !== '' ? $natText : $descr;

            return ['T' . strtoupper(substr(sha1($base), 0, 9)), $descr];
        }

        return [$cod, $descr];
    }

    private function emitirRegistros0400(): void {
        foreach ($this->mapa0400Naturezas as $codNat => $descrNat) {
            $this->addLinha('0400', [$codNat, $descrNat]);
        }
    }

    private function codNat0400ParaNota($nota): string {
        $k = $this->chaveInternaNotaSped($nota);

        return $this->codNat0400PorChaveNota[$k] ?? '';
    }

    /**
     * Tabela 0450 + vínculo por nota (NF-e 55) para registro C110.
     */
    private function prepararMapa0450Informacoes(): void {
        $this->mapa0450Informacoes = [];
        $this->codInf0450PorChaveNota = [];
        $textoParaCod = [];
        $seq = 0;
        foreach ($this->notas as $nota) {
            if ((string)($nota->modelo ?? '') !== '55') {
                continue;
            }
            $txt = trim((string)($nota->informacoes_complementares ?? ''));
            if ($txt === '') {
                continue;
            }
            $norm = mb_substr($txt, 0, 4000);
            if (!isset($textoParaCod[$norm])) {
                $seq++;
                $cod = 'Z' . str_pad((string)$seq, 5, '0', STR_PAD_LEFT);
                $textoParaCod[$norm] = $cod;
                $this->mapa0450Informacoes[$cod] = $norm;
            }
            $this->codInf0450PorChaveNota[$this->chaveInternaNotaSped($nota)] = $textoParaCod[$norm];
        }
        ksort($this->mapa0450Informacoes, SORT_STRING);
    }

    private function emitirRegistros0450(): void {
        foreach ($this->mapa0450Informacoes as $codInf => $txt) {
            $this->addLinha('0450', [$codInf, $txt]);
        }
    }

    private function codInf0450ParaNota($nota): string {
        $k = $this->chaveInternaNotaSped($nota);

        return $this->codInf0450PorChaveNota[$k] ?? '';
    }

    /**
     * COD_OBS alfanumérico até 6 posições (0460 / campo 12 do C190).
     */
    private function normalizarCodObsSped0460(string $raw): string {
        $s = strtoupper(preg_replace('/[^A-Z0-9]/', '', $raw));

        return mb_substr($s, 0, 6);
    }

    private function cfopQuatroDigitosFromString(string $raw): string {
        $cfopRaw = preg_replace('/\D/', '', $raw);
        if ($cfopRaw === '') {
            return '0000';
        }

        return str_pad(substr($cfopRaw, 0, 4), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Tabela 0460 + vínculos opcionais CST+CFOP+alíquota → COD_OBS no C190 (fiscal_empresas_config.sped_0460_c190_json).
     */
    private function prepararMapa0460C190(): void {
        $this->mapa0460TextoPorCod = [];
        $this->mapaC190CodObsPorChaveBucket = [];
        $raw = trim((string)($this->configFiscal['sped_0460_c190_json'] ?? ''));
        if ($raw === '') {
            return;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return;
        }
        $listaObs = $data['observacoes'] ?? $data['0460'] ?? [];
        if (!is_array($listaObs)) {
            $listaObs = [];
        }
        foreach ($listaObs as $row) {
            if (!is_array($row)) {
                continue;
            }
            $codRaw = trim((string)($row['cod_obs'] ?? $row['COD_OBS'] ?? ''));
            $txt = trim((string)($row['txt'] ?? $row['TXT'] ?? $row['texto'] ?? $row['descr'] ?? ''));
            $cod = $this->normalizarCodObsSped0460($codRaw);
            if ($cod === '' || $txt === '') {
                continue;
            }
            $this->mapa0460TextoPorCod[$cod] = mb_substr($txt, 0, 4000);
        }
        $vinc = $data['c190'] ?? $data['vinculos'] ?? [];
        if (!is_array($vinc)) {
            $vinc = [];
        }
        foreach ($vinc as $row) {
            if (!is_array($row)) {
                continue;
            }
            $codObs = $this->normalizarCodObsSped0460(trim((string)($row['cod_obs'] ?? $row['COD_OBS'] ?? '')));
            if ($codObs === '' || !isset($this->mapa0460TextoPorCod[$codObs])) {
                continue;
            }
            $cst = $this->cstTresDigitos((string)($row['cst'] ?? $row['CST_ICMS'] ?? ''));
            $cfop = $this->cfopQuatroDigitosFromString((string)($row['cfop'] ?? $row['CFOP'] ?? ''));
            $aliq = (float)($row['aliq_icms'] ?? $row['ALIQ_ICMS'] ?? $row['aliq'] ?? 0);
            $chave = $cst . '|' . $cfop . '|' . $this->fmtSped($aliq, 2);
            $this->mapaC190CodObsPorChaveBucket[$chave] = $codObs;
        }
        ksort($this->mapa0460TextoPorCod, SORT_STRING);
    }

    private function emitirRegistros0460(): void {
        foreach ($this->mapa0460TextoPorCod as $codObs => $txt) {
            $this->addLinha('0460', [$codObs, $txt]);
        }
    }

    private function codObs0460ParaChaveBucket(string $chaveBucket): string {
        return $this->mapaC190CodObsPorChaveBucket[$chaveBucket] ?? '';
    }

    // ── Bloco 0 — Abertura ──────────────────────────────

    private function bloco0() {
        $cnpj = preg_replace('/\D/', '', (string)($this->empresa['cnpj'] ?? ''));
        $ie = preg_replace('/\D/', '', (string)($this->empresa['ie'] ?? ''));
        $nome = strtoupper(mb_substr((string)($this->empresa['razaosocial'] ?? $this->empresa['nome'] ?? ''), 0, 100));
        $uf = strtoupper((string)($this->configFiscal['uf'] ?? 'SP'));
        $codMun = (string)($this->configFiscal['codigo_municipio_ibge'] ?? $this->configFiscal['codigo_municipio'] ?? '');
        $cep = preg_replace('/\D/', '', (string)($this->empresa['cep'] ?? ''));

        $dtIni = date('dmY', strtotime($this->periodoInicio));
        $dtFin = date('dmY', strtotime($this->periodoFim));

        $codVer = trim((string)(Configure::read('Fiscal.sped.cod_ver_layout') ?? '015'));
        if (!preg_match('/^\d{3}$/', $codVer)) {
            $codVer = '015';
        }

        // 0000 — Abertura
        $this->addLinha('0000', [
            $codVer,
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

        // 0100 — Contabilista (cadastro em fiscal_empresas_config ou modo sempre_stub)
        $campos0100 = $this->montarCamposRegistro0100();
        if ($campos0100 !== null) {
            $this->addLinha('0100', $campos0100);
        }

        $this->emitirRegistros0150();
        $this->emitirRegistros0190();
        $this->emitirRegistros0200();
        $this->emitirRegistros0400();
        $this->emitirRegistros0450();
        $this->emitirRegistros0460();

        // 0990 — Encerramento bloco 0 (QTD_LIN inclui a própria linha 0990)
        $qtdLin0 = $this->contarLinhasRegistroPrefixo('0') + 1;
        $this->addLinha('0990', [(string)$qtdLin0]);
    }

    /**
     * Bloco B — documentos fiscais de serviços (ICMS IPI): sem dados nesta versão do gerador.
     * B001 IND_DAD=1 conforme Guia Prático (apenas abertura e encerramento).
     */
    private function blocoB() {
        $this->addLinha('B001', ['1']);
        $qtdLinB = $this->contarLinhasRegistroPrefixo('B') + 1;
        $this->addLinha('B990', [(string)$qtdLinB]);
    }

    /**
     * Bloco D — documentos fiscais II: sem dados nesta versão.
     */
    private function blocoD() {
        $this->addLinha('D001', ['1']);
        $qtdLinD = $this->contarLinhasRegistroPrefixo('D') + 1;
        $this->addLinha('D990', [(string)$qtdLinD]);
    }

    /**
     * Bloco G — CIAP / controle do crédito de ICMS no ativo permanente: sem dados nesta versão.
     */
    private function blocoG() {
        $this->addLinha('G001', ['1']);
        $qtdLinG = $this->contarLinhasRegistroPrefixo('G') + 1;
        $this->addLinha('G990', [(string)$qtdLinG]);
    }

    /**
     * Bloco K — controle da produção e do estoque (RCPE): sem dados nesta versão (K001 IND_MOV=1).
     */
    private function blocoK() {
        $this->addLinha('K001', ['1']);
        $qtdLinK = $this->contarLinhasRegistroPrefixo('K') + 1;
        $this->addLinha('K990', [(string)$qtdLinK]);
    }

    /**
     * Bloco 1 — informações complementares: sem dados nesta versão (1001 IND_MOV=1).
     */
    private function bloco1() {
        $this->addLinha('1001', ['1']);
        $qtdLin1 = $this->contarLinhasRegistroPrefixo('1') + 1;
        $this->addLinha('1990', [(string)$qtdLin1]);
    }

    // ── Bloco C — Documentos Fiscais ──────────────────

    private function fmtSped($valor, int $decimais = 2): string {
        return number_format((float)$valor, $decimais, ',', '');
    }

    private function primeiroImposto($item, string $tipo) {
        foreach ($item->fiscal_notas_impostos ?? [] as $imp) {
            if (($imp->imposto ?? '') === $tipo) {
                return $imp;
            }
        }

        return null;
    }

    private function cstTresDigitos($v): string {
        $s = preg_replace('/\D/', '', (string)$v);
        if ($s === '') {
            return '';
        }

        return str_pad(substr($s, 0, 3), 3, '0', STR_PAD_LEFT);
    }

    private function cfopQuatroDigitos($item): string {
        $cfopRaw = preg_replace('/\D/', '', (string)($item->cfop ?? ''));
        if ($cfopRaw === '') {
            return '0000';
        }

        return str_pad(substr($cfopRaw, 0, 4), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Métricas de impostos por item (alinhadas a C170/C190/C100).
     *
     * @return array{bc_icms: float, vl_icms: float, bc_st: float, vl_st: float, aliq_icms: float, aliq_st: float, vl_ipi: float, vl_pis: float, vl_cofins: float, vl_item_liq: float, cst: string, cfop4: string}
     */
    private function metricasItemSpedImpostos($item): array {
        $icms = $this->primeiroImposto($item, 'ICMS');
        $fcp = $this->primeiroImposto($item, 'FCP');
        $bcIcms = $icms ? (float)$icms->base_calculo : 0.0;
        $vlIcms = ($icms ? (float)$icms->valor : 0.0) + ($fcp ? (float)$fcp->valor : 0.0);
        $aliqIcms = $icms ? (float)$icms->aliquota : 0.0;

        $st = $this->primeiroImposto($item, 'ICMS_ST');
        $fcpSt = $this->primeiroImposto($item, 'FCP_ST');
        $bcSt = $st ? (float)$st->base_calculo : 0.0;
        $vlSt = ($st ? (float)$st->valor : 0.0) + ($fcpSt ? (float)$fcpSt->valor : 0.0);
        $aliqSt = $st ? (float)$st->aliquota : 0.0;

        $ipi = $this->primeiroImposto($item, 'IPI');
        $pis = $this->primeiroImposto($item, 'PIS');
        $cof = $this->primeiroImposto($item, 'COFINS');

        return [
            'bc_icms' => $bcIcms,
            'vl_icms' => $vlIcms,
            'bc_st' => $bcSt,
            'vl_st' => $vlSt,
            'aliq_icms' => $aliqIcms,
            'aliq_st' => $aliqSt,
            'vl_ipi' => $ipi ? (float)$ipi->valor : 0.0,
            'vl_pis' => $pis ? (float)$pis->valor : 0.0,
            'vl_cofins' => $cof ? (float)$cof->valor : 0.0,
            'vl_item_liq' => (float)($item->valor_total ?? 0) - (float)($item->valor_desconto ?? 0),
            'cst' => $this->cstTresDigitos($item->icms_cst ?? $item->cst_icms ?? ''),
            'cfop4' => $this->cfopQuatroDigitos($item),
        ];
    }

    /**
     * Totais do documento (C100) a partir dos itens; FCP somado ao ICMS, FCP_ST ao ICMS_ST.
     *
     * @return array{bc_icms: float, vl_icms: float, bc_st: float, vl_st: float, vl_ipi: float, vl_pis: float, vl_cofins: float}
     */
    private function agregarTotaisNotaParaC100($nota): array {
        $out = [
            'bc_icms' => 0.0,
            'vl_icms' => 0.0,
            'bc_st' => 0.0,
            'vl_st' => 0.0,
            'vl_ipi' => 0.0,
            'vl_pis' => 0.0,
            'vl_cofins' => 0.0,
        ];
        foreach ($nota->fiscal_notas_itens ?? [] as $item) {
            $m = $this->metricasItemSpedImpostos($item);
            $out['bc_icms'] += $m['bc_icms'];
            $out['vl_icms'] += $m['vl_icms'];
            $out['bc_st'] += $m['bc_st'];
            $out['vl_st'] += $m['vl_st'];
            $out['vl_ipi'] += $m['vl_ipi'];
            $out['vl_pis'] += $m['vl_pis'];
            $out['vl_cofins'] += $m['vl_cofins'];
        }

        $nItens = count($nota->fiscal_notas_itens ?? []);
        if ($nItens === 0) {
            $out['vl_icms'] = (float)($nota->valor_icms ?? 0);
            $out['vl_st'] = (float)($nota->valor_icms_st ?? 0);
            $out['vl_ipi'] = (float)($nota->valor_ipi ?? 0);
            $out['vl_pis'] = (float)($nota->valor_pis ?? 0);
            $out['vl_cofins'] = (float)($nota->valor_cofins ?? 0);

            return $out;
        }
        if ($out['vl_icms'] <= 0 && (float)($nota->valor_icms ?? 0) > 0) {
            $out['vl_icms'] = (float)$nota->valor_icms;
        }
        if ($out['vl_st'] <= 0 && (float)($nota->valor_icms_st ?? 0) > 0) {
            $out['vl_st'] = (float)$nota->valor_icms_st;
        }
        if ($out['vl_ipi'] <= 0 && (float)($nota->valor_ipi ?? 0) > 0) {
            $out['vl_ipi'] = (float)$nota->valor_ipi;
        }
        if ($out['vl_pis'] <= 0 && (float)($nota->valor_pis ?? 0) > 0) {
            $out['vl_pis'] = (float)$nota->valor_pis;
        }
        if ($out['vl_cofins'] <= 0 && (float)($nota->valor_cofins ?? 0) > 0) {
            $out['vl_cofins'] = (float)$nota->valor_cofins;
        }

        return $out;
    }

    private function indFrtSped($nota): string {
        $m = (int)($nota->frete_modalidade ?? 9);
        if ($m < 0 || $m > 9) {
            return '9';
        }

        return (string)$m;
    }

    private function indPgtoSped($nota): string {
        $pags = $nota->fiscal_notas_pagamentos ?? [];

        return count($pags) > 1 ? '1' : '0';
    }

    /**
     * NFC-e modelo 65: campos dispensados no C100 (guia EFD — totais ST/IPI/PIS/COFINS no mestre).
     *
     * @param array<int, string> $campos
     */
    private function limparC100ParaNfce65(array &$campos): void {
        $campos[2] = '';
        for ($i = 21; $i <= 27; $i++) {
            $campos[$i] = '';
        }
    }

    /**
     * Código do item (C170 / 0200 / H010) — até 60 caracteres, estável no período.
     */
    private function codigoItemSped($item): string {
        $c = trim((string)($item->codigo_produto ?? ''));
        if ($c !== '') {
            return mb_substr($c, 0, 60);
        }
        $d = trim(preg_replace('/\s+/', ' ', (string)($item->descricao ?? '')));
        if ($d !== '') {
            return mb_substr($d, 0, 60);
        }
        $id = $item->id ?? null;
        if ($id !== null && $id !== '') {
            return mb_substr('ITEM' . (string)$id, 0, 60);
        }

        return mb_substr('ITEM' . (string)($item->numero_item ?? '0'), 0, 60);
    }

    private function unidadeInventarioSped($item): string {
        $u = strtoupper(trim((string)($item->unidade ?? 'UN')));
        $u = preg_replace('/\s+/', '', $u);
        if ($u === '') {
            $u = 'UN';
        }

        return mb_substr($u, 0, 6);
    }

    private function descricaoUnidadeSped(string $un): string {
        $u = strtoupper($un);
        $map = [
            'UN' => 'UNIDADE',
            'KG' => 'QUILOGRAMA',
            'G' => 'GRAMA',
            'L' => 'LITRO',
            'M' => 'METRO',
            'M2' => 'METRO QUADRADO',
            'M3' => 'METRO CUBICO',
            'PC' => 'PECA',
            'CX' => 'CAIXA',
            'PCT' => 'PACOTE',
        ];

        return $map[$u] ?? $u;
    }

    private function ncmOitoDigitos($item): string {
        $raw = preg_replace('/\D/', '', (string)($item->ncm ?? ''));
        if ($raw === '') {
            return '';
        }

        return str_pad(substr($raw, 0, 8), 8, '0', STR_PAD_LEFT);
    }

    private function cestSeteDigitos($item): string {
        $raw = preg_replace('/\D/', '', (string)($item->cest ?? ''));
        if ($raw === '') {
            return '';
        }

        return str_pad(substr($raw, 0, 7), 7, '0', STR_PAD_LEFT);
    }

    private function tipoItemSpedPadrao(): string {
        $t = trim((string)(Configure::read('Fiscal.sped.tipo_item_padrao') ?? '00'));
        $ok = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '99'];

        return in_array($t, $ok, true) ? $t : '00';
    }

    /**
     * @return array<int, string>
     */
    private function montarCamposC100($nota): array {
        $tot = $this->agregarTotaisNotaParaC100($nota);
        $tipoOp = (int)($nota->tipo_operacao ?? 1);
        $modelo = (string)($nota->modelo ?? '55');
        $dtDoc = ($nota->data_emissao ?? null) instanceof \DateTimeInterface
            ? $nota->data_emissao->format('dmY') : '';
        $dtEs = $dtDoc;
        $ds = $nota->data_saida ?? null;
        if ($ds instanceof \DateTimeInterface) {
            $dtEs = $ds->format('dmY');
        }

        $vlMerc = (float)($nota->valor_produtos ?? 0);
        if ($vlMerc <= 0) {
            $vlMerc = (float)($nota->valor_total ?? 0) - (float)($nota->valor_icms_st ?? 0) - (float)($nota->valor_ipi ?? 0);
            if ($vlMerc < 0) {
                $vlMerc = (float)($nota->valor_total ?? 0);
            }
        }

        $codPart = '';
        if ($modelo !== '65' && (int)($nota->idcliente ?? 0) > 0) {
            $cid = (int)$nota->idcliente;
            $codPart = $this->codPartPorClienteId[$cid] ?? '';
        }

        $campos = [
            $tipoOp === 0 ? '0' : '1',
            '1',
            $codPart,
            $modelo,
            '00',
            (string)($nota->serie ?? ''),
            (string)($nota->numero ?? ''),
            (string)($nota->chave_acesso ?? ''),
            $dtDoc,
            $dtEs,
            $this->fmtSped($nota->valor_total ?? 0),
            $this->indPgtoSped($nota),
            $this->fmtSped($nota->valor_desconto ?? 0),
            $this->fmtSped(0),
            $this->fmtSped($vlMerc),
            $this->indFrtSped($nota),
            $this->fmtSped($nota->valor_frete ?? 0),
            $this->fmtSped($nota->valor_seguro ?? 0),
            $this->fmtSped($nota->valor_outras_despesas ?? 0),
            $this->fmtSped($tot['bc_icms']),
            $this->fmtSped($tot['vl_icms']),
            $this->fmtSped($tot['bc_st']),
            $this->fmtSped($tot['vl_st']),
            $this->fmtSped($tot['vl_ipi']),
            $this->fmtSped($tot['vl_pis']),
            $this->fmtSped($tot['vl_cofins']),
            $this->fmtSped(0),
            $this->fmtSped(0),
        ];
        if ($modelo === '65') {
            $this->limparC100ParaNfce65($campos);
        }

        return $campos;
    }

    /**
     * @return array<int, string>
     */
    private function montarCamposC170($nota, $item): array {
        $m = $this->metricasItemSpedImpostos($item);
        $ipi = $this->primeiroImposto($item, 'IPI');
        $pis = $this->primeiroImposto($item, 'PIS');
        $cof = $this->primeiroImposto($item, 'COFINS');

        $cstIcms = $m['cst'];
        $cstIpi = $this->cstTresDigitos($item->ipi_cst ?? $item->cst_ipi ?? '');
        $cstPis = $this->cstTresDigitos($item->pis_cst ?? $item->cst_pis ?? '');
        $cstCof = $this->cstTresDigitos($item->cofins_cst ?? $item->cst_cofins ?? '');

        return [
            (string)($item->numero_item ?? ''),
            $this->codigoItemSped($item),
            mb_substr((string)($item->descricao ?? ''), 0, 100),
            number_format((float)($item->quantidade ?? 0), 5, ',', ''),
            (string)($item->unidade ?? 'UN'),
            $this->fmtSped($item->valor_total ?? 0),
            $this->fmtSped($item->valor_desconto ?? 0),
            '0',
            $cstIcms,
            $m['cfop4'],
            $this->codNat0400ParaNota($nota),
            $this->fmtSped($m['bc_icms']),
            $this->fmtSped($m['aliq_icms'], 2),
            $this->fmtSped($m['vl_icms']),
            $this->fmtSped($m['bc_st']),
            $this->fmtSped($m['aliq_st'], 2),
            $this->fmtSped($m['vl_st']),
            '',
            $cstIpi,
            '',
            $ipi ? $this->fmtSped($ipi->base_calculo) : $this->fmtSped(0),
            $ipi ? $this->fmtSped($ipi->aliquota, 2) : $this->fmtSped(0),
            $ipi ? $this->fmtSped($ipi->valor) : $this->fmtSped(0),
            $cstPis,
            $pis ? $this->fmtSped($pis->base_calculo) : $this->fmtSped(0),
            $pis ? $this->fmtSped($pis->aliquota, 4) : $this->fmtSped(0, 4),
            $pis ? $this->fmtSped($pis->valor) : $this->fmtSped(0),
            $cstCof,
            $cof ? $this->fmtSped($cof->base_calculo) : $this->fmtSped(0),
            $cof ? $this->fmtSped($cof->aliquota, 4) : $this->fmtSped(0, 4),
            $cof ? $this->fmtSped($cof->valor) : $this->fmtSped(0),
            '',
        ];
    }

    /**
     * Registros C190 por documento (CST + CFOP + ALIQ_ICMS).
     *
     * @return list<array<int, string>>
     */
    private function montarLinhasC190($nota): array {
        $itens = $nota->fiscal_notas_itens ?? [];
        if ($itens === []) {
            return [];
        }

        $metrics = [];
        $totMerc = 0.0;
        foreach ($itens as $item) {
            $m = $this->metricasItemSpedImpostos($item);
            $metrics[] = $m;
            $totMerc += $m['vl_item_liq'];
        }

        $acc = (float)($nota->valor_frete ?? 0) + (float)($nota->valor_seguro ?? 0) + (float)($nota->valor_outras_despesas ?? 0);

        $buckets = [];
        foreach ($metrics as $m) {
            $k = $m['cst'] . '|' . $m['cfop4'] . '|' . $this->fmtSped($m['aliq_icms'], 2);
            if (!isset($buckets[$k])) {
                $buckets[$k] = [
                    'cst' => $m['cst'] !== '' ? $m['cst'] : '000',
                    'cfop' => $m['cfop4'],
                    'aliq' => $m['aliq_icms'],
                    'vl_merc' => 0.0,
                    'bc_icms' => 0.0,
                    'vl_icms' => 0.0,
                    'bc_st' => 0.0,
                    'vl_st' => 0.0,
                    'vl_ipi' => 0.0,
                ];
            }
            $buckets[$k]['vl_merc'] += $m['vl_item_liq'];
            $buckets[$k]['bc_icms'] += $m['bc_icms'];
            $buckets[$k]['vl_icms'] += $m['vl_icms'];
            $buckets[$k]['bc_st'] += $m['bc_st'];
            $buckets[$k]['vl_st'] += $m['vl_st'];
            $buckets[$k]['vl_ipi'] += $m['vl_ipi'];
        }
        ksort($buckets);

        $nB = max(1, count($buckets));
        $linhas = [];
        foreach ($buckets as $chaveBucket => $b) {
            $ratio = $totMerc > 0 ? ($b['vl_merc'] / $totMerc) : (1.0 / $nB);
            $vlOpr = $b['vl_merc'] + $acc * $ratio + $b['vl_st'] + $b['vl_ipi'];
            $aliqFmt = $this->fmtSped($b['aliq'], 2);
            if ((float)$b['aliq'] <= 0.0 && $b['bc_icms'] <= 0.0 && $b['vl_icms'] <= 0.0) {
                $aliqFmt = '';
            }
            $linhas[] = [
                $b['cst'],
                $b['cfop'],
                $aliqFmt,
                $this->fmtSped($vlOpr),
                $this->fmtSped($b['bc_icms']),
                $this->fmtSped($b['vl_icms']),
                $this->fmtSped($b['bc_st']),
                $this->fmtSped($b['vl_st']),
                $this->fmtSped(0),
                $this->fmtSped($b['vl_ipi']),
                $this->codObs0460ParaChaveBucket((string)$chaveBucket),
            ];
        }

        return $linhas;
    }

    private function blocoC() {
        $this->addLinha('C001', [empty($this->notas) ? '1' : '0']);

        foreach ($this->notas as $nota) {
            $this->addLinha('C100', $this->montarCamposC100($nota));

            $codInf0450 = $this->codInf0450ParaNota($nota);
            if ($codInf0450 !== '') {
                $this->addLinha('C110', [$codInf0450, '']);
            }

            foreach ($nota->fiscal_notas_itens ?? [] as $item) {
                $this->addLinha('C170', $this->montarCamposC170($nota, $item));
            }
            foreach ($this->montarLinhasC190($nota) as $camposC190) {
                $this->addLinha('C190', $camposC190);
            }
        }

        $qtdLinC = $this->contarLinhasRegistroPrefixo('C') + 1;
        $this->addLinha('C990', [(string)$qtdLinC]);
    }

    // ── Bloco E — Apuração ICMS ──────────────────────

    /**
     * Ajustes E111 a partir de fiscal_empresas_config.sped_e111_ajustes_json (opcional).
     *
     * @return list<array{cod: string, descr: string, vl: float, campo: string}>
     */
    private function parseE111AjustesSped(): array {
        $raw = trim((string)($this->configFiscal['sped_e111_ajustes_json'] ?? ''));
        if ($raw === '') {
            return [];
        }
        $arr = json_decode($raw, true);
        if (!is_array($arr)) {
            return [];
        }
        $allowed = [
            'VL_AJ_DEBITOS' => true,
            'VL_TOT_AJ_DEBITOS' => true,
            'VL_ESTORNOS_CRED' => true,
            'VL_AJ_CREDITOS' => true,
            'VL_TOT_AJ_CREDITOS' => true,
            'VL_ESTORNOS_DEB' => true,
            'VL_TOT_DED' => true,
            'DEB_ESP' => true,
        ];
        $out = [];
        foreach ($arr as $row) {
            if (!is_array($row)) {
                continue;
            }
            $codRaw = strtoupper(trim((string)($row['cod_aj_apur'] ?? $row['COD_AJ_APUR'] ?? '')));
            $cod = str_pad(mb_substr($codRaw, 0, 8), 8, ' ', STR_PAD_RIGHT);
            $vl = (float)($row['vl_aj_apur'] ?? $row['VL_AJ_APUR'] ?? 0);
            $campo = strtoupper(trim((string)($row['e110_campo'] ?? $row['E110_CAMPO'] ?? '')));
            $descr = mb_substr(trim((string)($row['descr_compl_aj'] ?? $row['DESCR_COMPL_AJ'] ?? '')), 0, 4000);
            if ($codRaw === '' || !isset($allowed[$campo]) || abs($vl) < 1e-9) {
                continue;
            }
            $out[] = ['cod' => $cod, 'descr' => $descr, 'vl' => $vl, 'campo' => $campo];
        }

        return $out;
    }

    private function blocoE() {
        $this->addLinha('E001', [empty($this->notas) ? '1' : '0']);

        $dtIni = date('dmY', strtotime($this->periodoInicio));
        $dtFin = date('dmY', strtotime($this->periodoFim));

        $this->addLinha('E100', [$dtIni, $dtFin]);

        $deb = 0.0;
        $cred = 0.0;
        foreach ($this->notas as $nota) {
            $tot = $this->agregarTotaisNotaParaC100($nota);
            $vl = (float)$tot['vl_icms'];
            if ((int)($nota->tipo_operacao ?? 1) === 1) {
                $deb += $vl;
            } else {
                $cred += $vl;
            }
        }

        $e110 = [
            'VL_TOT_DEBITOS' => $deb,
            'VL_AJ_DEBITOS' => 0.0,
            'VL_TOT_AJ_DEBITOS' => 0.0,
            'VL_ESTORNOS_CRED' => 0.0,
            'VL_TOT_CREDITOS' => $cred,
            'VL_AJ_CREDITOS' => 0.0,
            'VL_TOT_AJ_CREDITOS' => 0.0,
            'VL_ESTORNOS_DEB' => 0.0,
            'VL_SLD_CREDOR_ANT' => 0.0,
            'VL_TOT_DED' => 0.0,
            'DEB_ESP' => 0.0,
        ];

        $linhasE111 = $this->parseE111AjustesSped();
        foreach ($linhasE111 as $adj) {
            $e110[$adj['campo']] += $adj['vl'];
        }

        $apur = $e110['VL_TOT_DEBITOS']
            + $e110['VL_AJ_DEBITOS']
            + $e110['VL_TOT_AJ_DEBITOS']
            - $e110['VL_ESTORNOS_CRED']
            - $e110['VL_TOT_CREDITOS']
            - $e110['VL_AJ_CREDITOS']
            - $e110['VL_TOT_AJ_CREDITOS']
            + $e110['VL_ESTORNOS_DEB']
            - $e110['VL_SLD_CREDOR_ANT']
            - $e110['VL_TOT_DED']
            - $e110['DEB_ESP'];

        $fmt = function ($v) {
            return number_format((float)$v, 2, ',', '');
        };

        $this->addLinha('E110', [
            $fmt($e110['VL_TOT_DEBITOS']),
            $fmt($e110['VL_AJ_DEBITOS']),
            $fmt($e110['VL_TOT_AJ_DEBITOS']),
            $fmt($e110['VL_ESTORNOS_CRED']),
            $fmt($e110['VL_TOT_CREDITOS']),
            $fmt($e110['VL_AJ_CREDITOS']),
            $fmt($e110['VL_TOT_AJ_CREDITOS']),
            $fmt($e110['VL_ESTORNOS_DEB']),
            $fmt($e110['VL_SLD_CREDOR_ANT']),
            $fmt($apur),
            $fmt($e110['VL_TOT_DED']),
            $fmt(max(0.0, $apur)),
            $fmt(max(0.0, -$apur)),
            $fmt($e110['DEB_ESP']),
        ]);

        foreach ($linhasE111 as $adj) {
            $this->addLinha('E111', [
                $adj['cod'],
                $adj['descr'],
                $fmt($adj['vl']),
            ]);
        }

        $qtdLinE = $this->contarLinhasRegistroPrefixo('E') + 1;
        $this->addLinha('E990', [(string)$qtdLinE]);
    }

    // ── Bloco H — Inventário ─────────────────────────

    private function blocoH() {
        $inv = $this->parseInventarioSped();
        if ($inv === null) {
            $this->addLinha('H001', ['1']);
        } else {
            $this->addLinha('H001', ['0']);
            $this->addLinha('H005', [$inv['dt_inv'], $inv['vl_inv_fmt'], $inv['mot_inv']]);
            foreach ($inv['linhas_h010'] as $camposH010) {
                $this->addLinha('H010', $camposH010);
            }
        }

        $qtdLinH = $this->contarLinhasRegistroPrefixo('H') + 1;
        $this->addLinha('H990', [(string)$qtdLinH]);
    }

    /**
     * Inventário a declarar no SPED (H005/H010) a partir de fiscal_empresas_config.
     *
     * @return array{dt_inv: string, vl_inv_fmt: string, mot_inv: string, linhas_h010: list<array<int, string>>}|null
     */
    private function parseInventarioSped(): ?array {
        $cf = $this->configFiscal;
        $decl = $cf['sped_inventario_declarar'] ?? false;
        if ($decl === false || $decl === null || $decl === '' || $decl === 0 || $decl === '0') {
            return null;
        }

        $dtRaw = $cf['sped_inventario_dt_inv'] ?? null;
        if ($dtRaw instanceof \DateTimeInterface) {
            $dtInv = $dtRaw->format('dmY');
        } elseif (is_string($dtRaw) && $dtRaw !== '') {
            $ts = strtotime($dtRaw);
            $dtInv = $ts ? date('dmY', $ts) : '';
        } else {
            $dtInv = '';
        }
        if (strlen($dtInv) !== 8) {
            return null;
        }

        $motRaw = trim((string)($cf['sped_inventario_mot_inv'] ?? ''));
        if ($motRaw === '') {
            return null;
        }
        $motDigits = preg_replace('/\D/', '', $motRaw);
        $motInv = str_pad(substr($motDigits !== '' ? $motDigits : $motRaw, 0, 2), 2, '0', STR_PAD_LEFT);
        if (strlen($motInv) !== 2 || !in_array($motInv, ['01', '02', '03', '04', '05', '06'], true)) {
            return null;
        }

        $json = trim((string)($cf['sped_inventario_itens_json'] ?? ''));
        if ($json === '') {
            return null;
        }
        $arr = json_decode($json, true);
        if (!is_array($arr) || $arr === []) {
            return null;
        }

        $maxItens = (int)(Configure::read('Fiscal.sped.inventario_max_itens') ?? 5000);
        if ($maxItens < 1) {
            $maxItens = 5000;
        }

        $linhasH010 = [];
        $somaVlItem = 0.0;
        $n = 0;
        foreach ($arr as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ($n >= $maxItens) {
                break;
            }
            $codItem = mb_substr(trim((string)($row['cod_item'] ?? $row['COD_ITEM'] ?? '')), 0, 60);
            $unid = mb_substr(trim((string)($row['unid'] ?? $row['UNID'] ?? 'UN')), 0, 6);
            if ($codItem === '' || $unid === '') {
                continue;
            }

            $qtd = (float)($row['qtd'] ?? $row['QTD'] ?? 0);
            $vlUnit = (float)($row['vl_unit'] ?? $row['VL_UNIT'] ?? 0);
            if (array_key_exists('vl_item', $row) || array_key_exists('VL_ITEM', $row)) {
                $vlItem = (float)($row['vl_item'] ?? $row['VL_ITEM'] ?? 0);
            } else {
                $vlItem = $qtd * $vlUnit;
            }

            $indProp = (string)($row['ind_prop'] ?? $row['IND_PROP'] ?? '0');
            if (!in_array($indProp, ['0', '1', '2'], true)) {
                $indProp = '0';
            }
            $codPart = mb_substr(trim((string)($row['cod_part'] ?? $row['COD_PART'] ?? '')), 0, 60);
            if (in_array($indProp, ['1', '2'], true) && $codPart === '') {
                continue;
            }

            $txtCompl = mb_substr(trim((string)($row['txt_compl'] ?? $row['TXT_COMPL'] ?? '')), 0, 255);
            $codCta = mb_substr(trim((string)($row['cod_cta'] ?? $row['COD_CTA'] ?? '')), 0, 60);
            $vlItemIr = '';
            if (array_key_exists('vl_item_ir', $row) || array_key_exists('VL_ITEM_IR', $row)) {
                $vir = $row['vl_item_ir'] ?? $row['VL_ITEM_IR'] ?? null;
                if ($vir !== null && $vir !== '') {
                    $vlItemIr = number_format((float)$vir, 2, ',', '');
                }
            }

            $somaVlItem += $vlItem;
            $linhasH010[] = [
                $codItem,
                $unid,
                number_format($qtd, 3, ',', ''),
                number_format($vlUnit, 6, ',', ''),
                number_format($vlItem, 2, ',', ''),
                $indProp,
                $codPart,
                $txtCompl,
                $codCta,
                $vlItemIr,
            ];
            $n++;
        }

        if ($linhasH010 === []) {
            return null;
        }

        return [
            'dt_inv' => $dtInv,
            'vl_inv_fmt' => number_format($somaVlItem, 2, ',', ''),
            'mot_inv' => $motInv,
            'linhas_h010' => $linhasH010,
        ];
    }

    // ── Bloco 9 — Controle e Encerramento ────────────

    /**
     * Monta 9001, 9900 (por tipo de registro), 9990 e 9999 conforme EFD-ICMS/IPI:
     * contagens por código de registro nas linhas já geradas (todos os blocos antes do 9, excl. o 9) + linhas do próprio bloco 9.
     */
    private function bloco9() {
        $linhasAntes = $this->linhas;
        $freq = [];
        foreach ($linhasAntes as $line) {
            $parts = explode('|', $line);
            if (count($parts) < 3) {
                continue;
            }
            $reg = $parts[1];
            if ($reg === '') {
                continue;
            }
            $freq[$reg] = ($freq[$reg] ?? 0) + 1;
        }

        $extra = ['9001', '9900', '9990', '9999'];
        $allTypes = array_unique(array_merge(array_keys($freq), $extra));
        sort($allTypes, SORT_STRING);

        $nLinhas9900 = count($allTypes);

        $finalFreq = $freq;
        $finalFreq['9001'] = ($finalFreq['9001'] ?? 0) + 1;
        $finalFreq['9900'] = $nLinhas9900;
        $finalFreq['9990'] = ($finalFreq['9990'] ?? 0) + 1;
        $finalFreq['9999'] = ($finalFreq['9999'] ?? 0) + 1;

        $this->addLinha('9001', ['0']);

        foreach ($allTypes as $regType) {
            $this->addLinha('9900', [$regType, (string)($finalFreq[$regType] ?? 0)]);
        }

        $qtdLinBloco9 = 1 + $nLinhas9900 + 1;
        $this->addLinha('9990', [(string)$qtdLinBloco9]);

        $qtdLinArquivo = count($linhasAntes) + $qtdLinBloco9 + 1;
        $this->addLinha('9999', [(string)$qtdLinArquivo]);
    }
}
