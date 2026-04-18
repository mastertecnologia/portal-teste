<?php
namespace App\Service\Financeiro;

use DateTimeInterface;

/**
 * Serviço simplificado para geração de remessas CNAB 240
 * e leitura básica de arquivos de retorno CNAB 240.
 *
 * Bancos suportados na geração:
 * - Banco do Brasil (001)
 * - Sicoob (756)
 *
 * Escopo:
 * - Header de arquivo
 * - Header de lote
 * - Segmento P
 * - Segmento Q
 * - Trailer de lote
 * - Trailer de arquivo
 *
 * Observações:
 * - Implementação pragmática para integração do módulo financeiro.
 * - Algumas posições de uso bancário específico são preenchidas com defaults seguros.
 * - O parser de retorno foca nos segmentos T e U, suficientes para baixa e rejeições.
 */
class CnabService
{
    public const LAYOUT_240 = '240';

    public const BANCO_BB = '001';
    public const BANCO_SICOOB = '756';

    /**
     * Mapeamento simplificado de códigos de ocorrência / rejeição.
     *
     * @var array<string,string>
     */
    protected $mensagensOcorrencia = [
        '00' => 'Registro confirmado com sucesso.',
        '01' => 'Título registrado aguardando processamento.',
        '02' => 'Entrada confirmada.',
        '03' => 'Entrada rejeitada pelo banco.',
        '04' => 'Transferência de carteira/entrada rejeitada.',
        '05' => 'Liquidação sem registro prévio.',
        '06' => 'Liquidação normal.',
        '07' => 'Liquidação parcial.',
        '08' => 'Baixa solicitada confirmada.',
        '09' => 'Baixa automática efetuada.',
        '10' => 'Baixa rejeitada.',
        '11' => 'Título em ser.',
        '12' => 'Abatimento concedido.',
        '13' => 'Abatimento cancelado.',
        '14' => 'Vencimento alterado.',
        '15' => 'Liquidação em cartório.',
        '16' => 'Título pago em cartório.',
        '17' => 'Alteração de dados rejeitada.',
        '18' => 'Instrução rejeitada.',
        '19' => 'Confirmação de recebimento de instrução.',
        '20' => 'Recebimento de instrução de protesto.',
        '21' => 'Recebimento de instrução de sustação/cancelamento.',
        '22' => 'Título com pagamento cancelado.',
        '23' => 'Entrada recusada por dados inválidos.',
        '24' => 'Título rejeitado por inconsistência cadastral.',
        '25' => 'Nosso número inválido.',
        '26' => 'Documento do pagador inválido.',
        '27' => 'Agência/conta do cedente inválida.',
        '28' => 'Carteira inválida.',
        '29' => 'Convênio inválido.',
        '30' => 'Valor do título inválido.',
        '31' => 'Data de vencimento inválida.',
        '32' => 'CEP do pagador inválido.',
        '33' => 'Pagador não encontrado.',
        '34' => 'Título já registrado anteriormente.',
        '35' => 'Título já baixado ou liquidado.',
        '36' => 'Título não localizado no banco.',
        '37' => 'Arquivo de retorno processado com ressalvas.',
        '38' => 'Arquivo rejeitado por layout inconsistente.',
        '39' => 'Movimento não permitido para o título.',
        '40' => 'Título rejeitado por falta de convênio/carteira.',
    ];

    /**
     * Gera arquivo CNAB 240 completo.
     *
     * @param array $banco
     * @param array $empresa
     * @param array $titulos
     * @param array $opcoes
     * @return string
     */
    public function gerarRemessa240(array $banco, array $empresa, array $titulos, array $opcoes = [])
    {
        $codigoBanco = $this->normalizarCodigoBanco($banco['codigo_banco'] ?? $banco['numero_banco'] ?? '');
        $sequencialArquivo = (int) ($opcoes['sequencial_arquivo'] ?? $banco['proxima_remessa'] ?? 1);
        $dataGeracao = $opcoes['data_geracao'] ?? date('Y-m-d');
        $horaGeracao = $opcoes['hora_geracao'] ?? date('H:i:s');
        $numeroLote = 1;

        $linhas = [];
        $linhas[] = $this->gerarHeaderArquivo240($codigoBanco, $banco, $empresa, $sequencialArquivo, $dataGeracao, $horaGeracao);
        $linhas[] = $this->gerarHeaderLote240($codigoBanco, $banco, $empresa, $numeroLote);

        $numeroRegistro = 1;
        $quantidadeRegistrosLote = 2; // header + trailer
        $valorTotalTitulos = 0.0;

        foreach ($titulos as $titulo) {
            $segmentoP = $this->gerarSegmentoP240($codigoBanco, $banco, $empresa, $titulo, $numeroLote, $numeroRegistro);
            $linhas[] = $segmentoP;
            $numeroRegistro++;
            $quantidadeRegistrosLote++;

            $segmentoQ = $this->gerarSegmentoQ240($codigoBanco, $banco, $empresa, $titulo, $numeroLote, $numeroRegistro);
            $linhas[] = $segmentoQ;
            $numeroRegistro++;
            $quantidadeRegistrosLote++;

            $valorTotalTitulos += (float) ($titulo['valor'] ?? 0);
        }

        $linhas[] = $this->gerarTrailerLote240(
            $codigoBanco,
            $numeroLote,
            $quantidadeRegistrosLote,
            count($titulos),
            $valorTotalTitulos
        );

        $linhas[] = $this->gerarTrailerArquivo240($codigoBanco, 1, count($linhas) + 1);

        return implode("\r\n", $linhas) . "\r\n";
    }

    /**
     * Header do arquivo CNAB 240.
     *
     * @param string $codigoBanco
     * @param array $banco
     * @param array $empresa
     * @param int $sequencialArquivo
     * @param string $dataGeracao
     * @param string $horaGeracao
     * @return string
     */
    public function gerarHeaderArquivo240(
        string $codigoBanco,
        array $banco,
        array $empresa,
        int $sequencialArquivo,
        string $dataGeracao,
        string $horaGeracao
    ) {
        $cnpj = $this->somenteDigitos($empresa['cnpj'] ?? '');
        $nomeEmpresa = $empresa['nome'] ?? $empresa['razaosocial'] ?? 'EMPRESA';
        $agencia = $this->somenteDigitos($banco['numero_agencia'] ?? $banco['agencia'] ?? '');
        $agenciaDv = $this->somenteAlnum($banco['digito_agencia'] ?? $banco['agencia_digito'] ?? '');
        $conta = $this->somenteDigitos($banco['numero_conta'] ?? $banco['conta'] ?? '');
        $contaDv = $this->somenteAlnum($banco['digito_conta'] ?? $banco['conta_digito'] ?? '');
        $convenio = $this->somenteAlnum($banco['convenio'] ?? '');

        $linha = '';
        $linha .= $this->padNum($codigoBanco, 3);
        $linha .= '0000';
        $linha .= '0';
        $linha .= $this->padSpaces('', 9);
        $linha .= '2';
        $linha .= $this->padNum($cnpj, 14);

        if ($codigoBanco === self::BANCO_BB) {
            $linha .= $this->padNum($convenio, 20);
        } else {
            $linha .= $this->padSpaces($convenio, 20);
        }

        $linha .= $this->padNum($agencia, 5);
        $linha .= $this->padAlnum($agenciaDv, 1);
        $linha .= $this->padNum($conta, 12);
        $linha .= $this->padAlnum($contaDv, 1);
        $linha .= $this->padSpaces('', 1);
        $linha .= $this->padText($nomeEmpresa, 30);
        $linha .= $this->padText($this->nomeBancoPorCodigo($codigoBanco), 30);
        $linha .= $this->padSpaces('', 10);
        $linha .= '1';
        $linha .= $this->formatarData($dataGeracao, 'dmY');
        $linha .= $this->formatarHora($horaGeracao, 'His');
        $linha .= $this->padNum((string) $sequencialArquivo, 6);
        $linha .= $this->padNum('089', 3);
        $linha .= '01600';
        $linha .= $this->padSpaces('', 20);
        $linha .= $this->padSpaces('', 20);
        $linha .= $this->padSpaces('', 29);

        return $this->finalizarLinha240($linha);
    }

    /**
     * Header do lote CNAB 240.
     *
     * @param string $codigoBanco
     * @param array $banco
     * @param array $empresa
     * @param int $numeroLote
     * @return string
     */
    public function gerarHeaderLote240(
        string $codigoBanco,
        array $banco,
        array $empresa,
        int $numeroLote = 1
    ) {
        $cnpj = $this->somenteDigitos($empresa['cnpj'] ?? '');
        $nomeEmpresa = $empresa['nome'] ?? $empresa['razaosocial'] ?? 'EMPRESA';
        $logradouro = $empresa['logradouro'] ?? $empresa['endereco'] ?? '';
        $numero = $empresa['numero'] ?? '';
        $bairro = $empresa['bairro'] ?? '';
        $cep = $this->somenteDigitos($empresa['cep'] ?? '');
        $cidade = $empresa['cidade'] ?? '';
        $uf = $empresa['uf'] ?? '';
        $agencia = $this->somenteDigitos($banco['numero_agencia'] ?? $banco['agencia'] ?? '');
        $agenciaDv = $this->somenteAlnum($banco['digito_agencia'] ?? $banco['agencia_digito'] ?? '');
        $conta = $this->somenteDigitos($banco['numero_conta'] ?? $banco['conta'] ?? '');
        $contaDv = $this->somenteAlnum($banco['digito_conta'] ?? $banco['conta_digito'] ?? '');
        $convenio = $this->somenteAlnum($banco['convenio'] ?? '');

        $linha = '';
        $linha .= $this->padNum($codigoBanco, 3);
        $linha .= $this->padNum((string) $numeroLote, 4);
        $linha .= '1';
        $linha .= 'R';
        $linha .= '01';
        $linha .= '  ';
        $linha .= '040';
        $linha .= ' ';
        $linha .= '2';
        $linha .= $this->padNum($cnpj, 15);
        $linha .= $this->padSpaces($convenio, 20);
        $linha .= $this->padNum($agencia, 5);
        $linha .= $this->padAlnum($agenciaDv, 1);
        $linha .= $this->padNum($conta, 12);
        $linha .= $this->padAlnum($contaDv, 1);
        $linha .= $this->padSpaces('', 1);
        $linha .= $this->padText($nomeEmpresa, 30);
        $linha .= $this->padText($logradouro, 40);
        $linha .= $this->padNum($numero, 5);
        $linha .= $this->padText('', 15);
        $linha .= $this->padText($bairro, 15);
        $linha .= $this->padNum(substr($cep, 0, 5), 5);
        $linha .= $this->padNum(substr($cep, 5, 3), 3);
        $linha .= $this->padText($cidade, 15);
        $linha .= $this->padText($uf, 2);
        $linha .= $this->padSpaces('', 8);

        return $this->finalizarLinha240($linha);
    }

    /**
     * Segmento P CNAB 240.
     *
     * @param string $codigoBanco
     * @param array $banco
     * @param array $empresa
     * @param array $titulo
     * @param int $numeroLote
     * @param int $numeroRegistro
     * @return string
     */
    public function gerarSegmentoP240(
        string $codigoBanco,
        array $banco,
        array $empresa,
        array $titulo,
        int $numeroLote,
        int $numeroRegistro
    ) {
        $agencia = $this->somenteDigitos($banco['numero_agencia'] ?? $banco['agencia'] ?? '');
        $agenciaDv = $this->somenteAlnum($banco['digito_agencia'] ?? $banco['agencia_digito'] ?? '');
        $conta = $this->somenteDigitos($banco['numero_conta'] ?? $banco['conta'] ?? '');
        $contaDv = $this->somenteAlnum($banco['digito_conta'] ?? $banco['conta_digito'] ?? '');
        $carteira = $this->somenteAlnum($banco['carteira'] ?? '');
        $nossoNumero = $this->somenteDigitos($titulo['nosso_numero'] ?? '');
        $numeroDocumento = $this->somenteAlnum($titulo['numero_documento'] ?? $titulo['id'] ?? '');
        $vencimento = $titulo['data_vencimento'] ?? date('Y-m-d');
        $valor = (float) ($titulo['valor'] ?? 0);
        $dataEmissao = $titulo['data_lancamento'] ?? date('Y-m-d');

        $linha = '';
        $linha .= $this->padNum($codigoBanco, 3);
        $linha .= $this->padNum((string) $numeroLote, 4);
        $linha .= '3';
        $linha .= $this->padNum((string) $numeroRegistro, 5);
        $linha .= 'P';
        $linha .= ' ';
        $linha .= '01';
        $linha .= $this->padNum($agencia, 5);
        $linha .= $this->padAlnum($agenciaDv, 1);
        $linha .= $this->padNum($conta, 12);
        $linha .= $this->padAlnum($contaDv, 1);
        $linha .= $this->padSpaces('', 1);

        if ($codigoBanco === self::BANCO_BB) {
            $linha .= $this->padNum($nossoNumero, 20);
        } else {
            $linha .= $this->padNum($carteira, 1);
            $linha .= $this->padNum($nossoNumero, 19);
        }

        $linha .= $this->padNum($carteira, 1);
        $linha .= '1';
        $linha .= '1';
        $linha .= '2';
        $linha .= $this->padNum($numeroDocumento, 15);
        $linha .= $this->formatarData($vencimento, 'dmY');
        $linha .= $this->padValor($valor, 15);
        $linha .= '00000';
        $linha .= '0';
        $linha .= '01';
        $linha .= 'N';
        $linha .= $this->formatarData($dataEmissao, 'dmY');
        $linha .= '2';
        $linha .= $this->formatarData($dataEmissao, 'dmY');
        $linha .= $this->padValor(0, 15);
        $linha .= $this->padValor(0, 15);
        $linha .= $this->padNum('0', 25);
        $linha .= '3';
        $linha .= $this->formatarData($vencimento, 'dmY');
        $linha .= $this->padValor(0, 15);
        $linha .= $this->padSpaces('', 10);
        $linha .= $this->padSpaces('', 1);

        return $this->finalizarLinha240($linha);
    }

    /**
     * Segmento Q CNAB 240.
     *
     * @param string $codigoBanco
     * @param array $banco
     * @param array $empresa
     * @param array $titulo
     * @param int $numeroLote
     * @param int $numeroRegistro
     * @return string
     */
    public function gerarSegmentoQ240(
        string $codigoBanco,
        array $banco,
        array $empresa,
        array $titulo,
        int $numeroLote,
        int $numeroRegistro
    ) {
        $pagadorNome = $titulo['pagador_nome'] ?? $titulo['cliente_nome'] ?? 'PAGADOR';
        $pagadorDocumento = $this->somenteDigitos(
            $titulo['pagador_documento'] ?? $titulo['cliente_documento'] ?? ''
        );
        $pagadorEndereco = $titulo['pagador_endereco'] ?? '';
        $pagadorBairro = $titulo['pagador_bairro'] ?? '';
        $pagadorCep = $this->somenteDigitos($titulo['pagador_cep'] ?? '');
        $pagadorCidade = $titulo['pagador_cidade'] ?? '';
        $pagadorUf = $titulo['pagador_uf'] ?? '';

        $tipoDocumento = strlen($pagadorDocumento) > 11 ? '2' : '1';

        $linha = '';
        $linha .= $this->padNum($codigoBanco, 3);
        $linha .= $this->padNum((string) $numeroLote, 4);
        $linha .= '3';
        $linha .= $this->padNum((string) $numeroRegistro, 5);
        $linha .= 'Q';
        $linha .= ' ';
        $linha .= '01';
        $linha .= $tipoDocumento;
        $linha .= $this->padNum($pagadorDocumento, 15);
        $linha .= $this->padText($pagadorNome, 40);
        $linha .= $this->padText($pagadorEndereco, 40);
        $linha .= $this->padText($pagadorBairro, 15);
        $linha .= $this->padNum(substr($pagadorCep, 0, 5), 5);
        $linha .= $this->padNum(substr($pagadorCep, 5, 3), 3);
        $linha .= $this->padText($pagadorCidade, 15);
        $linha .= $this->padText($pagadorUf, 2);
        $linha .= '0';
        $linha .= $this->padNum('', 15);
        $linha .= $this->padText('', 40);
        $linha .= $this->padSpaces('', 3);
        $linha .= $this->padText('', 20);
        $linha .= $this->padText('', 8);

        return $this->finalizarLinha240($linha);
    }

    /**
     * Trailer do lote CNAB 240.
     *
     * @param string $codigoBanco
     * @param int $numeroLote
     * @param int $quantidadeRegistros
     * @param int $quantidadeTitulos
     * @param float $valorTotalTitulos
     * @return string
     */
    public function gerarTrailerLote240(
        string $codigoBanco,
        int $numeroLote,
        int $quantidadeRegistros,
        int $quantidadeTitulos,
        float $valorTotalTitulos
    ) {
        $linha = '';
        $linha .= $this->padNum($codigoBanco, 3);
        $linha .= $this->padNum((string) $numeroLote, 4);
        $linha .= '5';
        $linha .= $this->padSpaces('', 9);
        $linha .= $this->padNum((string) $quantidadeRegistros, 6);
        $linha .= $this->padNum((string) $quantidadeTitulos, 6);
        $linha .= $this->padValor($valorTotalTitulos, 17);
        $linha .= $this->padValor(0, 17);
        $linha .= $this->padNum('0', 6);
        $linha .= $this->padValor(0, 17);
        $linha .= $this->padNum('0', 6);
        $linha .= $this->padValor(0, 17);
        $linha .= $this->padNum('0', 8);
        $linha .= $this->padSpaces('', 117);

        return $this->finalizarLinha240($linha);
    }

    /**
     * Trailer do arquivo CNAB 240.
     *
     * @param string $codigoBanco
     * @param int $quantidadeLotes
     * @param int $quantidadeRegistros
     * @return string
     */
    public function gerarTrailerArquivo240(
        string $codigoBanco,
        int $quantidadeLotes,
        int $quantidadeRegistros
    ) {
        $linha = '';
        $linha .= $this->padNum($codigoBanco, 3);
        $linha .= '9999';
        $linha .= '9';
        $linha .= $this->padSpaces('', 9);
        $linha .= $this->padNum((string) $quantidadeLotes, 6);
        $linha .= $this->padNum((string) $quantidadeRegistros, 6);
        $linha .= $this->padNum('0', 6);
        $linha .= $this->padSpaces('', 205);

        return $this->finalizarLinha240($linha);
    }

    /**
     * Lê retorno CNAB 240, identificando segmentos T e U.
     *
     * @param string $conteudo
     * @return array<int,array<string,mixed>>
     */
    public function lerRetorno240(string $conteudo)
    {
        $linhas = preg_split('/\r\n|\r|\n/', trim($conteudo));
        $eventos = [];
        $pendenteT = null;

        foreach ($linhas as $linha) {
            $linha = rtrim((string) $linha, "\r\n");
            if ($linha === '' || strlen($linha) < 20) {
                continue;
            }

            $tipoRegistro = substr($linha, 7, 1);
            if ($tipoRegistro !== '3') {
                continue;
            }

            $segmento = strtoupper(substr($linha, 13, 1));
            if ($segmento === 'T') {
                $pendenteT = $this->parseSegmentoT($linha);
                continue;
            }

            if ($segmento === 'U' && is_array($pendenteT)) {
                $segmentoU = $this->parseSegmentoU($linha);
                $codigo = $pendenteT['codigo_ocorrencia'] ?? '';
                $mensagem = $this->traduzirCodigoOcorrencia($codigo);

                $eventos[] = [
                    'nosso_numero' => $pendenteT['nosso_numero'],
                    'numero_documento' => $pendenteT['numero_documento'],
                    'codigo_ocorrencia' => $codigo,
                    'mensagem_ocorrencia' => $mensagem,
                    'valor_titulo' => $pendenteT['valor_titulo'],
                    'valor_pago' => $segmentoU['valor_pago'],
                    'data_ocorrencia' => $segmentoU['data_ocorrencia'],
                    'status_cobranca' => $this->statusCobrancaPorCodigo($codigo),
                    'liquidado' => $this->ocorrenciaIndicaLiquidacao($codigo),
                    'rejeitado' => $this->ocorrenciaIndicaRejeicao($codigo),
                    'raw_t' => $linha,
                    'raw_u' => $segmentoU['raw'],
                ];

                $pendenteT = null;
            }
        }

        return $eventos;
    }

    /**
     * Traduz código de ocorrência para mensagem legível.
     *
     * @param string|null $codigo
     * @return string
     */
    public function traduzirCodigoOcorrencia(?string $codigo)
    {
        $codigo = trim((string) $codigo);
        if ($codigo === '') {
            return 'Código de ocorrência não informado no retorno.';
        }

        return $this->mensagensOcorrencia[$codigo] ?? ('Ocorrência bancária código ' . $codigo . '.');
    }

    /**
     * Gera nosso número simples a partir de banco, título e data.
     *
     * @param array $banco
     * @param array $titulo
     * @return string
     */
    public function gerarNossoNumero(array $banco, array $titulo)
    {
        $codigoBanco = $this->normalizarCodigoBanco($banco['codigo_banco'] ?? $banco['numero_banco'] ?? '');
        $convenio = $this->somenteDigitos($banco['convenio'] ?? '');
        $id = (string) ($titulo['id'] ?? '0');
        $data = date('ymd');

        if ($codigoBanco === self::BANCO_BB) {
            $base = $this->padNum(substr($convenio, 0, 7), 7) . $this->padNum($id, 10);
            return substr($base, -17);
        }

        $base = $this->padNum(substr($convenio, 0, 6), 6) . $data . $this->padNum($id, 7);
        return substr($base, -17);
    }

    /**
     * Normaliza código do banco.
     *
     * @param string $codigo
     * @return string
     */
    protected function normalizarCodigoBanco(string $codigo)
    {
        return $this->padNum($this->somenteDigitos($codigo), 3);
    }

    /**
     * Define status de cobrança a partir da ocorrência retornada.
     *
     * @param string $codigo
     * @return string
     */
    protected function statusCobrancaPorCodigo(string $codigo)
    {
        if ($this->ocorrenciaIndicaLiquidacao($codigo)) {
            return 'liquidado';
        }

        if ($this->ocorrenciaIndicaRejeicao($codigo)) {
            return 'rejeitado';
        }

        if (in_array($codigo, ['00', '01', '02', '19', '20', '21'], true)) {
            return 'registrado';
        }

        if (in_array($codigo, ['08', '09'], true)) {
            return 'baixado';
        }

        return 'remetido';
    }

    /**
     * @param string $codigo
     * @return bool
     */
    protected function ocorrenciaIndicaLiquidacao(string $codigo)
    {
        return in_array($codigo, ['06', '07', '15', '16'], true);
    }

    /**
     * @param string $codigo
     * @return bool
     */
    protected function ocorrenciaIndicaRejeicao(string $codigo)
    {
        return in_array($codigo, ['03', '04', '10', '17', '18', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '38', '39', '40'], true);
    }

    /**
     * Faz parse de um segmento T CNAB 240.
     *
     * @param string $linha
     * @return array<string,mixed>
     */
    protected function parseSegmentoT(string $linha)
    {
        $nossoNumero = trim(substr($linha, 37, 20));
        $numeroDocumento = trim(substr($linha, 57, 15));
        $codigoOcorrencia = trim(substr($linha, 15, 2));
        $valorTitulo = $this->parseValor(substr($linha, 81, 15));

        return [
            'nosso_numero' => $this->somenteDigitos($nossoNumero),
            'numero_documento' => trim($numeroDocumento),
            'codigo_ocorrencia' => $codigoOcorrencia,
            'valor_titulo' => $valorTitulo,
            'raw' => $linha,
        ];
    }

    /**
     * Faz parse de um segmento U CNAB 240.
     *
     * @param string $linha
     * @return array<string,mixed>
     */
    protected function parseSegmentoU(string $linha)
    {
        $valorPago = $this->parseValor(substr($linha, 77, 15));
        $dataOcorrencia = $this->parseData(substr($linha, 137, 8));

        return [
            'valor_pago' => $valorPago,
            'data_ocorrencia' => $dataOcorrencia,
            'raw' => $linha,
        ];
    }

    /**
     * @param string $valor
     * @return float
     */
    protected function parseValor(string $valor)
    {
        $valor = $this->somenteDigitos($valor);
        if ($valor === '') {
            return 0.0;
        }

        return ((float) $valor) / 100;
    }

    /**
     * @param string $data
     * @return string|null
     */
    protected function parseData(string $data)
    {
        $data = $this->somenteDigitos($data);
        if (strlen($data) !== 8) {
            return null;
        }

        $dia = substr($data, 0, 2);
        $mes = substr($data, 2, 2);
        $ano = substr($data, 4, 4);

        if (!checkdate((int) $mes, (int) $dia, (int) $ano)) {
            return null;
        }

        return sprintf('%s-%s-%s', $ano, $mes, $dia);
    }

    /**
     * @param string $texto
     * @return string
     */
    protected function somenteDigitos(string $texto)
    {
        return preg_replace('/\D+/', '', $texto) ?: '';
    }

    /**
     * @param string $texto
     * @return string
     */
    protected function somenteAlnum(string $texto)
    {
        return preg_replace('/[^0-9A-Za-z]/', '', $texto) ?: '';
    }

    /**
     * @param string $valor
     * @param int $tamanho
     * @return string
     */
    protected function padNum(string $valor, int $tamanho)
    {
        $valor = $this->somenteDigitos($valor);
        return str_pad(substr($valor, -$tamanho), $tamanho, '0', STR_PAD_LEFT);
    }

    /**
     * @param string $valor
     * @param int $tamanho
     * @return string
     */
    protected function padAlnum(string $valor, int $tamanho)
    {
        $valor = strtoupper($this->somenteAlnum($valor));
        return str_pad(substr($valor, 0, $tamanho), $tamanho, ' ', STR_PAD_RIGHT);
    }

    /**
     * @param string $valor
     * @param int $tamanho
     * @return string
     */
    protected function padText(string $valor, int $tamanho)
    {
        $valor = strtoupper($this->removerAcentos(trim((string) $valor)));
        return str_pad(substr($valor, 0, $tamanho), $tamanho, ' ', STR_PAD_RIGHT);
    }

    /**
     * @param string $valor
     * @param int $tamanho
     * @return string
     */
    protected function padSpaces(string $valor, int $tamanho)
    {
        $valor = substr((string) $valor, 0, $tamanho);
        return str_pad($valor, $tamanho, ' ', STR_PAD_RIGHT);
    }

    /**
     * @param float $valor
     * @param int $tamanho
     * @return string
     */
    protected function padValor($valor, int $tamanho)
    {
        $centavos = (string) round(((float) $valor) * 100);
        return $this->padNum($centavos, $tamanho);
    }

    /**
     * @param string|DateTimeInterface $data
     * @param string $formato
     * @return string
     */
    protected function formatarData($data, string $formato = 'dmY')
    {
        if ($data instanceof DateTimeInterface) {
            return $data->format($formato);
        }

        $timestamp = strtotime((string) $data);
        if ($timestamp === false) {
            return str_repeat('0', strlen($formato));
        }

        return date($formato, $timestamp);
    }

    /**
     * @param string|DateTimeInterface $hora
     * @param string $formato
     * @return string
     */
    protected function formatarHora($hora, string $formato = 'His')
    {
        if ($hora instanceof DateTimeInterface) {
            return $hora->format($formato);
        }

        $timestamp = strtotime((string) $hora);
        if ($timestamp === false) {
            return str_repeat('0', strlen($formato));
        }

        return date($formato, $timestamp);
    }

    /**
     * @param string $codigoBanco
     * @return string
     */
    protected function nomeBancoPorCodigo(string $codigoBanco)
    {
        if ($codigoBanco === self::BANCO_BB) {
            return 'BANCO DO BRASIL';
        }

        if ($codigoBanco === self::BANCO_SICOOB) {
            return 'SICOOB';
        }

        return 'BANCO';
    }

    /**
     * Garante linha com exatamente 240 posições.
     *
     * @param string $linha
     * @return string
     */
    protected function finalizarLinha240(string $linha)
    {
        $linha = substr($linha, 0, 240);
        return str_pad($linha, 240, ' ', STR_PAD_RIGHT);
    }

    /**
     * Remove acentos para campos CNAB.
     *
     * @param string $texto
     * @return string
     */
    protected function removerAcentos(string $texto)
    {
        $mapa = [
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
            'á' => 'A', 'à' => 'A', 'ã' => 'A', 'â' => 'A', 'ä' => 'A',
            'é' => 'E', 'è' => 'E', 'ê' => 'E', 'ë' => 'E',
            'í' => 'I', 'ì' => 'I', 'î' => 'I', 'ï' => 'I',
            'ó' => 'O', 'ò' => 'O', 'õ' => 'O', 'ô' => 'O', 'ö' => 'O',
            'ú' => 'U', 'ù' => 'U', 'û' => 'U', 'ü' => 'U',
            'ç' => 'C',
        ];

        return strtr($texto, $mapa);
    }
}
