<?php
namespace App\Service\Cadastro\Provider;

/**
 * Dados cadastrais da empresa via Receita (agregador).
 * Fonte: ReceitaWS ou similar.
 */
class ReceitaProvider
{
    private $timeout = 15;
    private $urlBase = 'https://www.receitaws.com.br/v1/cnpj/';

    public function consultar(string $cnpj): array
    {
        $url = $this->urlBase . $cnpj;
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => 'Accept: application/json',
            ],
        ]);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            throw new \RuntimeException('Falha ao acessar serviço cadastral (Receita).', 502);
        }
        $data = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            throw new \RuntimeException('Retorno inválido do serviço cadastral.', 502);
        }
        if (!empty($data['status']) && strtoupper($data['status']) !== 'OK') {
            throw new \RuntimeException($data['message'] ?? 'CNPJ não encontrado ou indisponível.', 404);
        }
        return $data;
    }
}
