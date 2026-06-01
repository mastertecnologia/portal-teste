<?php
/**
 * Módulo Licenciamento — rotas canônicas e cofre.
 *
 * LICENCAS_CANONICAL_ROUTES=1 — ativa redirecionamentos /licencas → /licencas-prototype
 * LIC_COFRE_CIPHER_KEY — chave para AES-256-GCM no secret_blob (ver .env.example)
 */
return [
	'canonical_routes' => filter_var(env('LICENCAS_CANONICAL_ROUTES', false), FILTER_VALIDATE_BOOLEAN),
];
