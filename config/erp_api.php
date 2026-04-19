<?php
/**
 * APIs de integração Portal ↔ ERP (SOAP/HTTP).
 *
 * Variáveis de ambiente (opcional; ver `.env.example`):
 * - ERP_API_HEADER_ONLY: 1/true — empresa e token só em headers (rejeita credenciais na query).
 * - ERP_API_CORS_ORIGINS: lista separada por vírgulas de origens permitidas (ex.: https://erp.exemplo:85).
 *   Vazio = comportamento legado (Access-Control-Allow-Origin: *).
 */
$originsRaw = env('ERP_API_CORS_ORIGINS', '');
$origins = [];
if ($originsRaw !== '' && $originsRaw !== null) {
    foreach (preg_split('/\s*,\s*/', (string) $originsRaw) as $o) {
        $o = trim($o);
        if ($o !== '') {
            $origins[] = $o;
        }
    }
}

return [
    'ErpApi' => [
        'header_only_credentials' => filter_var(
            env('ERP_API_HEADER_ONLY', false),
            FILTER_VALIDATE_BOOLEAN,
        ),
        'cors_allowed_origins' => $origins,
    ],
];
