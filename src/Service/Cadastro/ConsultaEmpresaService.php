<?php
namespace App\Service\Cadastro;

use App\Service\Cadastro\Provider\ReceitaProvider;
use App\Service\Cadastro\Provider\SintegraIeProvider;
use App\Service\Cadastro\Provider\SpeedioProvider;
use App\Service\Cadastro\Provider\InscricaoMunicipalProvider;
use App\Utility\CnpjUtil;
use Cake\Cache\Cache;
use Cake\Log\Log;

/**
 * Orquestra consulta consolidada por CNPJ: dados cadastrais, IE, IM.
 * RN01–RN10: validação, ordem das consultas, origem, falhas não bloqueiam cadastro.
 */
class ConsultaEmpresaService
{
    private $receita;
    private $speedio;
    private $ieProvider;
    private $imProvider;
    private $resolveIdCidade;
    private $cacheTtl = 86400; // 24h

    /**
     * @param callable|null $resolveIdCidade function(string $municipio, string $uf): ?int
     */
    public function __construct(
        ?ReceitaProvider $receita = null,
        ?SpeedioProvider $speedio = null,
        ?SintegraIeProvider $ieProvider = null,
        ?InscricaoMunicipalProvider $imProvider = null,
        ?callable $resolveIdCidade = null
    ) {
        $this->receita = $receita ?? new ReceitaProvider();
        $this->speedio = $speedio ?? new SpeedioProvider();
        $this->ieProvider = $ieProvider ?? new SintegraIeProvider();
        $this->imProvider = $imProvider ?? new InscricaoMunicipalProvider();
        $this->resolveIdCidade = $resolveIdCidade;
    }

    /**
     * @param string $cnpj CNPJ com ou sem máscara
     * @param array $opcoes [ 'consultar_ie' => bool, 'consultar_im' => bool, 'usar_cache' => bool ]
     * @return array Resposta padronizada (sucesso, mensagem, dados, origem, status_consultas, avisos)
     */
    public function consultar(string $cnpj, array $opcoes = []): array
    {
        $cnpjLimpo = CnpjUtil::limpar($cnpj);

        if (!CnpjUtil::validar($cnpjLimpo)) {
            return $this->erro('CNPJ_INVALIDO', 'CNPJ inválido');
        }

        $consultarIe = $opcoes['consultar_ie'] ?? true;
        $consultarIm = $opcoes['consultar_im'] ?? true;
        $usarCache = $opcoes['usar_cache'] ?? true;

        $cacheKey = 'empresa_' . $cnpjLimpo;
        if ($usarCache) {
            $cached = Cache::read($cacheKey, 'default');
            if (is_array($cached)) {
                return $cached;
            }
        }

        $resultado = [
            'sucesso' => true,
            'mensagem' => 'Consulta realizada com sucesso',
            'codigo_erro' => null,
            'dados' => null,
            'origem' => [],
            'status_consultas' => [],
            'avisos' => [],
        ];

        $origemCadastro = 'RECEITA';
        try {
            $raw = null;
            if ($this->speedio->isConfigurado()) {
                try {
                    $raw = $this->speedio->consultar($cnpjLimpo);
                    $origemCadastro = 'SPEEDIO';
                } catch (\Throwable $e) {
                    $this->logFalha($cnpjLimpo, 'SPEEDIO', $e);
                    $raw = $this->receita->consultar($cnpjLimpo);
                    $origemCadastro = 'RECEITA';
                    $resultado['avisos'][] = 'Dados cadastrais obtidos pela Receita (Speedio indisponível).';
                }
            } else {
                $raw = $this->receita->consultar($cnpjLimpo);
                $resultado['avisos'][] = 'Dados cadastrais: Receita. Para usar Speedio, configure SPEEDIO_USERNAME e SPEEDIO_TOKEN no servidor (.env ou config/app_local.php).';
            }
            $resultado['dados'] = $this->mapearCadastro($raw);
            $resultado['origem']['dados_cadastrais'] = $origemCadastro;
            $resultado['status_consultas']['dados_cadastrais'] = StatusConsulta::SUCESSO;
        } catch (\Throwable $e) {
            $this->logFalha($cnpjLimpo, $origemCadastro, $e);
            return $this->erro(
                'ERRO_SERVICO_EXTERNO',
                'Falha ao consultar serviço cadastral',
                [
                    'avisos' => ['Tente novamente mais tarde ou faça o preenchimento manual'],
                    'origem' => ['dados_cadastrais' => $origemCadastro],
                    'status_consultas' => ['dados_cadastrais' => $this->statusFromException($e)],
                ]
            );
        }

        $uf = $resultado['dados']['endereco']['uf'] ?? null;

        if ($consultarIe && $uf) {
            try {
                if (!$this->ieProvider->isConfigurado()) {
                    $resultado['dados']['inscricao_estadual'] = $this->ieVazio('NAO_EXECUTADO');
                    $resultado['status_consultas']['inscricao_estadual'] = StatusConsulta::NAO_EXECUTADO;
                    $resultado['avisos'][] = 'Inscrição Estadual (IE): configure SINTEGRA_API_KEY no servidor para consulta automática (SEFAZ/SINTEGRA).';
                } else {
                    $ie = $this->ieProvider->consultar($cnpjLimpo, $uf);
                    if ($ie !== null) {
                        $resultado['dados']['inscricao_estadual'] = [
                            'numero' => $ie['numero'],
                            'situacao' => $ie['situacao'] ?? null,
                            'indicador' => $ie['indicador'] ?? null,
                        ];
                        $resultado['origem']['inscricao_estadual'] = 'SEFAZ_' . $uf;
                        $resultado['status_consultas']['inscricao_estadual'] = StatusConsulta::SUCESSO;
                    } else {
                        $resultado['dados']['inscricao_estadual'] = $this->ieVazio('NAO_LOCALIZADA');
                        $resultado['status_consultas']['inscricao_estadual'] = StatusConsulta::SEM_RESULTADO;
                        $resultado['avisos'][] = 'Inscrição estadual não localizada para o CNPJ informado';
                    }
                }
            } catch (\Throwable $e) {
                $this->logFalha($cnpjLimpo, 'SEFAZ_' . $uf, $e);
                $resultado['dados']['inscricao_estadual'] = $this->ieVazio('NAO_LOCALIZADA');
                $resultado['status_consultas']['inscricao_estadual'] = $this->statusFromException($e);
                $resultado['avisos'][] = 'Não foi possível consultar a inscrição estadual';
            }
        } elseif ($consultarIe && !$uf) {
            $resultado['dados']['inscricao_estadual'] = $this->ieVazio('NAO_LOCALIZADA');
            $resultado['status_consultas']['inscricao_estadual'] = StatusConsulta::NAO_EXECUTADO;
            $resultado['avisos'][] = 'UF não obtida; consulta de IE não executada';
        }

        if ($consultarIm && ($resultado['dados']['endereco']['municipio'] ?? null) && $uf) {
            try {
                $im = $this->imProvider->consultar(
                    $cnpjLimpo,
                    $resultado['dados']['endereco']['municipio'],
                    $uf
                );
                if ($im !== null) {
                    $resultado['dados']['inscricao_municipal'] = [
                        'numero' => $im['numero'] ?? null,
                        'situacao' => $im['situacao'] ?? null,
                    ];
                    $resultado['origem']['inscricao_municipal'] = 'MUNICIPIO';
                    $resultado['status_consultas']['inscricao_municipal'] = StatusConsulta::SUCESSO;
                } else {
                    $resultado['dados']['inscricao_municipal'] = ['numero' => null, 'situacao' => 'SEM_INTEGRACAO'];
                    $resultado['status_consultas']['inscricao_municipal'] = StatusConsulta::NAO_IMPLEMENTADO;
                    $resultado['avisos'][] = 'Inscrição Municipal (IM): sem integração disponível no momento; preencha manualmente se necessário.';
                }
            } catch (\Throwable $e) {
                $this->logFalha($cnpjLimpo, 'MUNICIPIO', $e);
                $resultado['dados']['inscricao_municipal'] = ['numero' => null, 'situacao' => 'NAO_LOCALIZADA'];
                $resultado['status_consultas']['inscricao_municipal'] = $this->statusFromException($e);
                $resultado['avisos'][] = 'Não foi possível consultar a inscrição municipal';
            }
        } elseif ($consultarIm) {
            $resultado['dados']['inscricao_municipal'] = ['numero' => null, 'situacao' => 'SEM_INTEGRACAO'];
            $resultado['status_consultas']['inscricao_municipal'] = StatusConsulta::NAO_IMPLEMENTADO;
        }

        if (count($resultado['avisos']) > 0) {
            $resultado['mensagem'] = 'Consulta realizada parcialmente';
        }

        if ($usarCache) {
            Cache::write($cacheKey, $resultado, 'default');
        }

        return $resultado;
    }

    private function erro(string $codigo, string $mensagem, array $extra = []): array
    {
        return array_merge([
            'sucesso' => false,
            'mensagem' => $mensagem,
            'codigo_erro' => $codigo,
            'dados' => null,
            'origem' => null,
            'status_consultas' => null,
            'avisos' => [],
        ], $extra);
    }

    private function mapearCadastro(array $raw): array
    {
        $cep = isset($raw['cep']) ? preg_replace('/\D+/', '', $raw['cep']) : '';
        if (strlen($cep) >= 8) {
            $cep = substr($cep, 0, 5) . substr($cep, 5, 3);
        }
        $cnaePrincipal = null;
        if (!empty($raw['atividade_principal'][0])) {
            $a = $raw['atividade_principal'][0];
            $cnaePrincipal = [
                'codigo' => isset($a['code']) ? preg_replace('/\D+/', '', $a['code']) : null,
                'descricao' => $a['text'] ?? null,
            ];
        }
        $cnaesSec = [];
        foreach ($raw['atividades_secundarias'] ?? [] as $a) {
            $cnaesSec[] = [
                'codigo' => isset($a['code']) ? preg_replace('/\D+/', '', $a['code']) : null,
                'descricao' => $a['text'] ?? null,
            ];
        }
        $municipio = $raw['municipio'] ?? '';
        $uf = !empty($raw['uf']) ? strtoupper(trim($raw['uf'])) : null;
        $idcidade = null;
        if ($this->resolveIdCidade && $municipio && $uf) {
            $idcidade = ($this->resolveIdCidade)($municipio, $uf);
        }
        return [
            'cnpj' => preg_replace('/\D+/', '', $raw['cnpj'] ?? ''),
            'razao_social' => $raw['nome'] ?? null,
            'nome_fantasia' => $raw['fantasia'] ?? null,
            'situacao_cadastral' => $raw['situacao'] ?? null,
            'data_abertura' => isset($raw['abertura']) ? $this->normalizarData($raw['abertura']) : null,
            'natureza_juridica' => $raw['natureza_juridica'] ?? null,
            'cnae_principal' => $cnaePrincipal,
            'cnaes_secundarios' => $cnaesSec,
            'endereco' => [
                'logradouro' => $raw['logradouro'] ?? null,
                'numero' => $raw['numero'] ?? null,
                'complemento' => $raw['complemento'] ?? null,
                'bairro' => $raw['bairro'] ?? null,
                'municipio' => $municipio,
                'uf' => $uf,
                'cep' => $cep,
            ],
            'contato' => [
                'email' => isset($raw['email']) ? trim(strtolower($raw['email'])) : null,
                'telefone' => $raw['telefone'] ?? null,
            ],
            'idcidade' => $idcidade,
            'qsa' => $raw['qsa'] ?? [],
        ];
    }

    private function normalizarData(?string $data): ?string
    {
        if ($data === null || $data === '') {
            return null;
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        return $data;
    }

    private function ieVazio(string $situacao): array
    {
        return [
            'numero' => null,
            'situacao' => $situacao,
            'indicador' => null,
        ];
    }

    private function statusFromException(\Throwable $e): string
    {
        $msg = strtolower($e->getMessage());
        if (strpos($msg, 'timeout') !== false) {
            return StatusConsulta::ERRO_TIMEOUT;
        }
        if (strpos($msg, '401') !== false || strpos($msg, 'autentic') !== false) {
            return StatusConsulta::ERRO_AUTENTICACAO;
        }
        return StatusConsulta::ERRO;
    }

    /**
     * Log de falha em consulta externa. Não incluir credenciais (token/senha) na mensagem.
     */
    private function logFalha(string $cnpj, string $servico, \Throwable $e): void
    {
        $msg = $e->getMessage();
        if (stripos($msg, 'token') !== false || stripos($msg, 'password') !== false || stripos($msg, 'senha') !== false) {
            $msg = '[mensagem redigida por segurança]';
        }
        Log::write('error', json_encode([
            'cnpj' => $cnpj,
            'servico' => $servico,
            'mensagem' => $msg,
            'data_hora' => date('c'),
        ]), ['scope' => ['cadastro_empresa']]);
    }
}
