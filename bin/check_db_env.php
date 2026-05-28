#!/usr/bin/env php
<?php
/**
 * Diagnóstico rápido: host/porta/user/database efetivos e teste TCP ao PostgreSQL.
 *
 * Uso: php bin/check_db_env.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/config/bootstrap.php';

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

$ds = (array)Configure::read('Datasources.default');
$host = (string)($ds['host'] ?? '?');
$port = (string)($ds['port'] ?? '5432');
$user = (string)($ds['username'] ?? '');
$db = (string)($ds['database'] ?? '');
$fromEnv = getenv('DB_HOST') ?: '(não definido em putenv)';

echo "DB_HOST (.env / putenv): {$fromEnv}\n";
echo "Datasources.default.host (efetivo): {$host}\n";
echo "port={$port} database={$db} username={$user}\n";

if ($host === 'localhost' || $host === '127.0.0.1') {
	echo "\nAVISO: host é loopback. Em produção PGM use DB_HOST=10.0.2.23 no .env\n";
}

$errno = 0;
$errstr = '';
$fp = @fsockopen($host, (int)$port, $errno, $errstr, 3);
if ($fp) {
	fclose($fp);
	echo "\nTCP {$host}:{$port} — OK (porta aceita conexão)\n";
} else {
	echo "\nTCP {$host}:{$port} — FALHOU ({$errno}) {$errstr}\n";
	echo "Verifique PostgreSQL, firewall e DB_HOST no .env\n";
	exit(1);
}

try {
	ConnectionManager::get('default')->connect();
	echo "PDO Cake (default) — OK\n";
	exit(0);
} catch (Throwable $e) {
	echo "PDO Cake — FALHOU: " . $e->getMessage() . "\n";
	exit(1);
}
