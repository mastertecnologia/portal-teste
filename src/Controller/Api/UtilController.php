<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Cache\Cache;
use Cake\Http\Client;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;

/**
 * Proxy para BrasilAPI (CNPJ) e ViaCEP — com cache de 24h.
 */
class UtilController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * GET /api/util/cnpj/:cnpj
     */
    public function cnpj($cnpj)
    {
        $clean = preg_replace('/\D/', '', (string)$cnpj);
        if (strlen($clean) !== 14) {
            throw new BadRequestException('CNPJ inválido (precisa 14 dígitos)');
        }

        $cacheKey = 'cnpj_' . $clean;
        $cached = Cache::read($cacheKey, 'default');
        if ($cached !== false) {
            $this->set(['success' => true, 'data' => $cached, 'cached' => true]);
            $this->viewBuilder()->setOption('serialize', ['success', 'data', 'cached']);
            return;
        }

        $http = new Client(['timeout' => 8]);
        $response = $http->get("https://brasilapi.com.br/api/cnpj/v1/{$clean}");

        if (!$response->isOk()) {
            throw new NotFoundException('CNPJ não encontrado na base');
        }

        $raw = $response->getJson();

        $data = [
            'razao_social' => $raw['razao_social'] ?? $raw['nome_fantasia'] ?? '',
            'nome_fantasia' => $raw['nome_fantasia'] ?? '',
            'cnpj' => $cnpj,
            'telefone' => !empty($raw['ddd_telefone_1'])
                ? '(' . substr($raw['ddd_telefone_1'], 0, 2) . ') ' . substr($raw['ddd_telefone_1'], 2)
                : '',
            'email' => $raw['email'] ?? '',
            'cep' => !empty($raw['cep']) ? preg_replace('/(\d{5})(\d{3})/', '$1-$2', $raw['cep']) : '',
            'endereco' => trim(implode(', ', array_filter([
                $raw['logradouro'] ?? '',
                $raw['numero'] ?? '',
                $raw['bairro'] ?? '',
                $raw['municipio'] ?? '',
                $raw['uf'] ?? '',
            ]))),
            'situacao' => $raw['descricao_situacao_cadastral'] ?? '',
        ];

        Cache::write($cacheKey, $data, 'default');

        $this->set(['success' => true, 'data' => $data, 'cached' => false]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data', 'cached']);
    }

    /**
     * GET /api/util/cep/:cep
     */
    public function cep($cep)
    {
        $clean = preg_replace('/\D/', '', (string)$cep);
        if (strlen($clean) !== 8) {
            throw new BadRequestException('CEP inválido');
        }

        $cacheKey = 'cep_' . $clean;
        $cached = Cache::read($cacheKey, 'default');
        if ($cached !== false) {
            $this->set(['success' => true, 'data' => $cached, 'cached' => true]);
            $this->viewBuilder()->setOption('serialize', ['success', 'data', 'cached']);
            return;
        }

        $http = new Client(['timeout' => 5]);
        $response = $http->get("https://viacep.com.br/ws/{$clean}/json/");

        if (!$response->isOk()) {
            throw new NotFoundException('CEP não encontrado');
        }

        $raw = $response->getJson();
        if (!empty($raw['erro'])) {
            throw new NotFoundException('CEP não encontrado');
        }

        $data = [
            'cep' => $cep,
            'logradouro' => $raw['logradouro'] ?? '',
            'bairro' => $raw['bairro'] ?? '',
            'cidade' => $raw['localidade'] ?? '',
            'uf' => $raw['uf'] ?? '',
            'endereco_completo' => trim(implode(', ', array_filter([
                $raw['logradouro'] ?? '',
                $raw['bairro'] ?? '',
                $raw['localidade'] ?? '',
                $raw['uf'] ?? '',
            ]))),
        ];

        Cache::write($cacheKey, $data, 'default');

        $this->set(['success' => true, 'data' => $data]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }
}
