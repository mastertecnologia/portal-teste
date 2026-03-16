<?php
namespace App\Service\Cadastro\Provider;

use Cake\Core\Configure;

/**
 * Dados cadastrais via API Speedio (https://docs.speedio.com.br).
 * Autenticação: usuário + token (ou usuário + senha) via Basic Auth.
 *
 * SEGURANÇA: Nunca logar, exibir ou incluir em mensagens de erro o token/senha.
 * Usar apenas variáveis de ambiente ou Configure (app_local.php não versionado).
 *
 * Env: SPEEDIO_USERNAME e SPEEDIO_TOKEN (ou SPEEDIO_PASSWORD).
 * Configure: Speedio.username e Speedio.token (ou Speedio.password).
 */
class SpeedioProvider
{
    private $timeout = 15;
    private $urlBase = 'https://api-get-leads.speedio.com.br/search_enriched_leads/cnpj';

    public function consultar(string $cnpj): array
    {
        $username = env('SPEEDIO_USERNAME', Configure::read('Speedio.username'));
        $token = env('SPEEDIO_TOKEN', Configure::read('Speedio.token'));
        $password = env('SPEEDIO_PASSWORD', Configure::read('Speedio.password'));
        $secret = ($token !== null && $token !== '') ? $token : $password;
        if (empty($secret)) {
            throw new \RuntimeException('Speedio não configurado. Defina SPEEDIO_TOKEN ou SPEEDIO_PASSWORD.', 500);
        }

        $url = $this->urlBase . '?cnpjs=' . rawurlencode(json_encode([$cnpj]));
        $authHeader = $this->buildAuthHeader($username, $secret);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => [
                    $authHeader,
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            throw new \RuntimeException('Falha ao acessar serviço cadastral (Speedio).', 502);
        }

        $data = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new \RuntimeException('Retorno inválido do serviço cadastral (Speedio).', 502);
        }

        if (empty($data) || !isset($data[0])) {
            throw new \RuntimeException('CNPJ não encontrado na Speedio.', 404);
        }

        $item = $data[0];
        return $this->normalizarParaReceita($item);
    }

    public function isConfigurado(): bool
    {
        $token = env('SPEEDIO_TOKEN', Configure::read('Speedio.token'));
        $password = env('SPEEDIO_PASSWORD', Configure::read('Speedio.password'));
        $secret = ($token !== null && $token !== '') ? $token : $password;
        return !empty($secret);
    }

    /**
     * Token no formato JWT (três partes separadas por ponto) usa Bearer; senão Basic (user:secret).
     */
    private function buildAuthHeader(?string $username, string $secret): string
    {
        if (preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', trim($secret))) {
            return 'Authorization: Bearer ' . $secret;
        }
        $user = ($username !== null && $username !== '') ? $username : 'token';
        return 'Authorization: Basic ' . base64_encode($user . ':' . $secret);
    }

    /**
     * Converte resposta Speedio para formato esperado por mapearCadastro (Receita).
     */
    private function normalizarParaReceita(array $s): array
    {
        $loc = $s['location'] ?? [];
        $logradouro = trim(($loc['tipo_logradouro'] ?? '') . ' ' . ($loc['nome_rua'] ?? ''));
        if ($logradouro === '') {
            $logradouro = $loc['nome_rua'] ?? null;
        }
        $cep = isset($loc['cep']) ? preg_replace('/\D+/', '', $loc['cep']) : '';
        $municipio = $loc['city'] ?? '';
        $uf = !empty($loc['uf']) ? strtoupper(trim($loc['uf'])) : null;

        $email = null;
        if (!empty($s['generic_emails']['emails_validados'])) {
            $email = is_array($s['generic_emails']['emails_validados'])
                ? (reset($s['generic_emails']['emails_validados']) ?: null)
                : $s['generic_emails']['emails_validados'];
        }
        if ($email === null && !empty($s['generic_emails']['emails_contador'])) {
            $arr = $s['generic_emails']['emails_contador'];
            $email = is_array($arr) ? reset($arr) : $arr;
        }

        $telefone = null;
        if (!empty($s['telefones']['telefones_validados'])) {
            $t = $s['telefones']['telefones_validados'];
            $telefone = is_array($t) ? reset($t) : $t;
        }
        if ($telefone === null && !empty($s['telefones']['telefones_contador'])) {
            $t = $s['telefones']['telefones_contador'];
            $telefone = is_array($t) ? reset($t) : $t;
        }

        $qsa = [];
        foreach ($s['qsa'] ?? [] as $socio) {
            $qsa[] = [
                'nome' => $socio['name'] ?? '',
                'qual' => $socio['position'] ?? '',
            ];
        }

        $cnaePrincipal = null;
        if (!empty($s['cnae_primario'])) {
            $c = $s['cnae_primario'];
            $cnaePrincipal = [
                'code' => $c['cnae'] ?? null,
                'text' => $c['cnae_desc'] ?? null,
            ];
        }
        $atividadesSecundarias = [];
        foreach ($s['cnae_secundario'] ?? [] as $c) {
            $atividadesSecundarias[] = [
                'code' => $c['cnae'] ?? null,
                'text' => $c['cnae_desc'] ?? null,
            ];
        }

        return [
            'cnpj' => preg_replace('/\D+/', '', $s['cnpj'] ?? ''),
            'nome' => $s['razao_social'] ?? null,
            'fantasia' => $s['nome_fantasia'] ?? null,
            'situacao' => 'ATIVA',
            'abertura' => $s['data_abertura'] ?? null,
            'natureza_juridica' => $s['natureza_juridica'] ?? null,
            'atividade_principal' => $cnaePrincipal ? [$cnaePrincipal] : [],
            'atividades_secundarias' => $atividadesSecundarias,
            'logradouro' => $logradouro,
            'numero' => $loc['numero'] ?? null,
            'complemento' => null,
            'bairro' => $loc['bairro'] ?? null,
            'municipio' => $municipio,
            'uf' => $uf,
            'cep' => $cep,
            'email' => $email,
            'telefone' => $telefone,
            'qsa' => $qsa,
        ];
    }

}
