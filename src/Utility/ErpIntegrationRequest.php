<?php
namespace App\Utility;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * Leitura segura de credenciais e cabeçalhos CORS para APIs ERP.
 */
final class ErpIntegrationRequest
{
    /**
     * Lê id empresa e token conforme ErpApi.header_only_credentials.
     *
     * @return array{0: string, 1: string, 2: ?string} empresa, token, mensagem de erro (se bloqueado)
     */
    public static function readEmpresaAndToken(ServerRequest $request): array
    {
        $only = (bool) Configure::read('ErpApi.header_only_credentials');
        $hEmp = trim((string) $request->getHeaderLine('empresa'));
        $hTok = trim((string) $request->getHeaderLine('token'));
        $qEmp = $request->getQuery('empresa');
        $qTok = $request->getQuery('token');
        $qEmp = is_scalar($qEmp) ? trim((string) $qEmp) : '';
        $qTok = is_scalar($qTok) ? trim((string) $qTok) : '';

        if ($only) {
            if ($qEmp !== '' || $qTok !== '') {
                return [
                    '',
                    '',
                    'Credenciais da API (empresa e token) devem ser enviadas apenas nos headers HTTP, não na query string. Remova token e empresa da URL.',
                ];
            }

            return [$hEmp, $hTok, null];
        }

        $emp = $hEmp !== '' ? $hEmp : $qEmp;
        $tok = $hTok !== '' ? $hTok : $qTok;

        return [$emp, $tok, null];
    }

    /**
     * Valor para Access-Control-Allow-Origin ou null se a origem não for permitida.
     */
    public static function accessControlAllowOriginValue(ServerRequest $request): ?string
    {
        $list = Configure::read('ErpApi.cors_allowed_origins');
        if (!is_array($list) || $list === []) {
            return '*';
        }

        $origin = $request->getHeaderLine('Origin');
        if ($origin === '') {
            return '*';
        }

        foreach ($list as $allowed) {
            if (!is_string($allowed) || $allowed === '') {
                continue;
            }
            if (hash_equals($allowed, $origin)) {
                return $origin;
            }
        }

        return null;
    }

    /**
     * Aplica cabeçalhos CORS às rotas de integração ERP.
     */
    public static function applyCorsHeaders(
        Response $response,
        ServerRequest $request
    ): Response {
        $allowOrigin = self::accessControlAllowOriginValue($request);
        if ($allowOrigin === null) {
            return $response;
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader(
                'Access-Control-Allow-Methods',
                'GET, POST, PUT, OPTIONS',
            )
            ->withHeader(
                'Access-Control-Allow-Headers',
                'Content-Type, empresa, token, situacao, id, cnpj, codigo',
            );

        return $response->withHeader('Access-Control-Max-Age', '86400');
    }
}
