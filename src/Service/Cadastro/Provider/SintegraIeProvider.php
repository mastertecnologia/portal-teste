<?php
namespace App\Service\Cadastro\Provider;

use Cake\Core\Configure;

/**
 * Inscrição Estadual via SINTEGRA/SEFAZ (SintegraPI).
 * Retorno: ['numero' => string|null, 'situacao' => string, 'indicador' => string|null].
 */
class SintegraIeProvider
{
    private $timeout = 15;
    private $urlBase = 'https://api.sintegrapi.com.br/consultas/v2/sintegra/';

    public function consultar(string $cnpj, string $uf): ?array
    {
        $apiKey = env('SINTEGRA_API_KEY', Configure::read('Sintegra.apiKey'));
        if (empty($apiKey)) {
            return null;
        }
        $url = $this->urlBase . $cnpj . '?uf=' . rawurlencode($uf);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => [
                    'Accept: application/json',
                    'x-api-key: ' . $apiKey,
                    'cache: 25',
                ],
            ],
        ]);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            throw new \RuntimeException('Falha ao acessar serviço de IE (SEFAZ/SINTEGRA).', 502);
        }
        $data = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            throw new \RuntimeException('Retorno inválido do serviço de IE.', 502);
        }
        if (!empty($data['error']) || empty($data['success'])) {
            return null;
        }
        $inscricoes = $data['inscricoes_estaduais'] ?? [];
        $uf = strtoupper($uf);
        foreach ($inscricoes as $item) {
            if (isset($item['uf']) && strtoupper($item['uf']) === $uf && !empty($item['inscricao_estadual'])) {
                $numero = preg_replace('/\D+/', '', $item['inscricao_estadual']);
                $situacao = $item['situacao_pj'] ?? ($item['ativa'] ? 'HABILITADO' : 'INATIVO');
                $indicador = $this->mapearIndicador($item['tipo_ie'] ?? null);
                return [
                    'numero' => $numero,
                    'situacao' => $situacao,
                    'indicador' => $indicador,
                ];
            }
        }
        if (!empty($inscricoes)) {
            $first = $inscricoes[0];
            $numero = preg_replace('/\D+/', '', $first['inscricao_estadual'] ?? '');
            if ($numero !== '') {
                return [
                    'numero' => $numero,
                    'situacao' => $first['situacao_pj'] ?? null,
                    'indicador' => $this->mapearIndicador($first['tipo_ie'] ?? null),
                ];
            }
        }
        return null;
    }

    public function isConfigurado(): bool
    {
        $apiKey = env('SINTEGRA_API_KEY', Configure::read('Sintegra.apiKey'));
        return !empty($apiKey);
    }

    private function mapearIndicador(?string $tipoIe): ?string
    {
        if ($tipoIe === null || $tipoIe === '') {
            return null;
        }
        $t = strtoupper($tipoIe);
        if (strpos($t, 'ISENTO') !== false || strpos($t, 'PRODUTOR RURAL') !== false) {
            return 'ISENTO';
        }
        if (strpos($t, 'NAO CONTRIBUINTE') !== false || strpos($t, 'NÃO CONTRIBUINTE') !== false) {
            return 'NAO_CONTRIBUINTE';
        }
        if (strpos($t, 'CONTRIBUINTE') !== false || strpos($t, 'NORMAL') !== false || strpos($t, 'SUBSTITUTO') !== false) {
            return 'CONTRIBUINTE_ICMS';
        }
        return null;
    }
}
