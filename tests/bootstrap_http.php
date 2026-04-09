<?php
/**
 * Bootstrap completo da app + SQLite em memória para IntegrationTestTrait (stack HTTP).
 * Usar apenas com: phpunit --bootstrap tests/bootstrap_http.php --testsuite rbac-http
 * URLs nos testes seguem App.base (ex.: APP_BASE=/portal no .env). Suites rbac-http: Permissoes, Areas, Empresasusers, Problemas, Feriados, ContratosHoras, Normasempresa, Financeiro, Faturamento, Clientes, Prefaturamento, Bancosenhas, Empresas, Orcamentos, Produtos, Visitas, Ordensservico.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
if (!is_file($root . '/vendor/autoload.php')) {
	throw new RuntimeException('Execute composer install antes de phpunit.');
}

define('PGM_HTTP_TEST_DATASOURCE', true);

require $root . '/vendor/autoload.php';
require $root . '/config/bootstrap.php';
