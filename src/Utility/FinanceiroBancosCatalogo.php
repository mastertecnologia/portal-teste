<?php
namespace App\Utility;

/**
 * Catálogo padrão de bancos brasileiros usado pelo módulo Financeiro > Bancos.
 *
 * Estrutura de cada item:
 * - codigo: código bancário FEBRABAN / COMPE
 * - nome: nome resumido para listagens
 * - nome_completo: nome expandido
 * - cnab: código CNAB padrão (quando aplicável)
 */
class FinanceiroBancosCatalogo
{
    /**
     * @var array<int,array<string,string>>
     */
    protected static $bancos = [
        [
            'codigo' => '25',
            'nome' => 'BANCO ALFA',
            'nome_completo' => 'Banco Alfa S.A.',
            'cnab' => '025',
        ],
        [
            'codigo' => '1',
            'nome' => 'BANCO DO BRASIL',
            'nome_completo' => 'Banco do Brasil S.A.',
            'cnab' => '001',
        ],
        [
            'codigo' => '756',
            'nome' => 'SICOOB',
            'nome_completo' => 'Banco Cooperativo do Brasil S.A. - SICOOB',
            'cnab' => '756',
        ],
        [
            'codigo' => '21',
            'nome' => 'BANESTES',
            'nome_completo' => 'Banco do Estado do Espírito Santo S.A. - BANESTES',
            'cnab' => '021',
        ],
        [
            'codigo' => '41',
            'nome' => 'BANRISUL',
            'nome_completo' => 'Banco do Estado do Rio Grande do Sul S.A. - BANRISUL',
            'cnab' => '041',
        ],
        [
            'codigo' => '237',
            'nome' => 'BRADESCO',
            'nome_completo' => 'Banco Bradesco S.A.',
            'cnab' => '237',
        ],
        [
            'codigo' => '70',
            'nome' => 'BRB',
            'nome_completo' => 'Banco de Brasília S.A. - BRB',
            'cnab' => '070',
        ],
        [
            'codigo' => '104',
            'nome' => 'CAIXA ECON. FEDERAL',
            'nome_completo' => 'Caixa Econômica Federal',
            'cnab' => '104',
        ],
        [
            'codigo' => '745',
            'nome' => 'CITIBANK',
            'nome_completo' => 'Banco Citibank S.A.',
            'cnab' => '745',
        ],
        [
            'codigo' => '399',
            'nome' => 'HSBC',
            'nome_completo' => 'HSBC Bank Brasil S.A. - Banco Múltiplo',
            'cnab' => '399',
        ],
        [
            'codigo' => '341',
            'nome' => 'ITAÚ',
            'nome_completo' => 'Itaú Unibanco S.A.',
            'cnab' => '341',
        ],
        [
            'codigo' => '389',
            'nome' => 'MERCANTIL DO BRASIL',
            'nome_completo' => 'Banco Mercantil do Brasil S.A.',
            'cnab' => '389',
        ],
        [
            'codigo' => '623',
            'nome' => 'PANAMERICANO',
            'nome_completo' => 'Banco Pan S.A.',
            'cnab' => '623',
        ],
        [
            'codigo' => '453',
            'nome' => 'RURAL',
            'nome_completo' => 'Banco Rural S.A.',
            'cnab' => '453',
        ],
        [
            'codigo' => '422',
            'nome' => 'SAFRA',
            'nome_completo' => 'Banco Safra S.A.',
            'cnab' => '422',
        ],
        [
            'codigo' => '353',
            'nome' => 'SANTANDER',
            'nome_completo' => 'Banco Santander (Brasil) S.A.',
            'cnab' => '353',
        ],
        [
            'codigo' => '748',
            'nome' => 'SICREDI',
            'nome_completo' => 'Banco Cooperativo Sicredi S.A.',
            'cnab' => '748',
        ],
    ];

    /**
     * Retorna o catálogo completo ordenado por código numérico.
     *
     * @return array<int,array<string,string>>
     */
    public static function todos(): array
    {
        $lista = static::$bancos;

        usort($lista, function (array $a, array $b): int {
            return (int)$a['codigo'] <=> (int)$b['codigo'];
        });

        return $lista;
    }

    /**
     * Busca banco exato pelo código informado.
     *
     * @param string|int|null $codigo
     * @return array<string,string>|null
     */
    public static function porCodigo($codigo): ?array
    {
        $codigoNormalizado = static::normalizarCodigo($codigo);
        if ($codigoNormalizado === '') {
            return null;
        }

        foreach (static::$bancos as $banco) {
            if (static::normalizarCodigo($banco['codigo']) === $codigoNormalizado) {
                return $banco;
            }
        }

        return null;
    }

    /**
     * Faz busca por código ou nome.
     *
     * @param string|null $termo
     * @return array<int,array<string,string>>
     */
    public static function buscar(?string $termo): array
    {
        $termo = trim((string)$termo);
        if ($termo === '') {
            return static::todos();
        }

        $termoUpper = static::normalizarTexto($termo);
        $resultado = [];

        foreach (static::$bancos as $banco) {
            $codigo = static::normalizarCodigo($banco['codigo']);
            $nome = static::normalizarTexto($banco['nome']);
            $nomeCompleto = static::normalizarTexto($banco['nome_completo']);
            $cnab = static::normalizarCodigo($banco['cnab']);

            if (
                strpos($codigo, static::normalizarCodigo($termo)) !== false ||
                strpos($cnab, static::normalizarCodigo($termo)) !== false ||
                strpos($nome, $termoUpper) !== false ||
                strpos($nomeCompleto, $termoUpper) !== false
            ) {
                $resultado[] = $banco;
            }
        }

        usort($resultado, function (array $a, array $b): int {
            return (int)$a['codigo'] <=> (int)$b['codigo'];
        });

        return $resultado;
    }

    /**
     * Retorna lista formatada para select.
     *
     * @return array<string,string>
     */
    public static function opcoesSelect(): array
    {
        $opcoes = [];

        foreach (static::todos() as $banco) {
            $opcoes[(string)$banco['codigo']] = static::formatarLabel($banco);
        }

        return $opcoes;
    }

    /**
     * Monta label amigável para select/listagem.
     *
     * @param array<string,string> $banco
     * @return string
     */
    public static function formatarLabel(array $banco): string
    {
        return $banco['codigo'] . ' - ' . $banco['nome'];
    }

    /**
     * @param string|int|null $codigo
     * @return string
     */
    protected static function normalizarCodigo($codigo): string
    {
        return preg_replace('/\D+/', '', (string)$codigo) ?? '';
    }

    /**
     * @param string $texto
     * @return string
     */
    protected static function normalizarTexto(string $texto): string
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

        return strtoupper(strtr($texto, $mapa));
    }
}
