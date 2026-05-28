#!/usr/bin/env php
<?php
/**
 * Diagnóstico: variáveis DB_* (.env), overrides em app_local.php, TCP e PDO.
 * Não usa bootstrap completo do Cake (evita Router em CLI).
 *
 * Uso: php bin/check_db_env.php
 *      php bin/check_db_env.php --cake   (testa também ConnectionManager via bin/cake)
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$useCake = in_array('--cake', $argv ?? [], true);

/**
 * @return void
 */
function pgmCheckDbLoadDotEnv(string $root): void
{
	$envFile = $root . DIRECTORY_SEPARATOR . '.env';
	if (!is_file($envFile) || !is_readable($envFile)) {
		return;
	}
	$raw = file_get_contents($envFile);
	if ($raw === false) {
		return;
	}
	if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
		$raw = substr($raw, 3);
	}
	foreach (preg_split('/\R/', $raw) ?: [] as $line) {
		$line = trim($line);
		if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
			continue;
		}
		if (stripos($line, 'export ') === 0) {
			$line = trim(substr($line, 7));
		}
		[$name, $value] = explode('=', $line, 2);
		$name = ltrim(trim($name), "\xEF\xBB\xBF");
		$value = trim($value, " \t\"'");
		if ($name !== '') {
			putenv("$name=$value");
			$_ENV[$name] = $value;
			$_SERVER[$name] = $value;
		}
	}
}

/**
 * Mesma ordem que Cake: app.php (via env) + merge app_local.php (local ganha).
 *
 * @return array{host:string,port:string,username:string,password:string,database:string}
 */
function pgmCheckDbResolveConfig(string $root): array
{
	$cfg = [
		'host' => getenv('DB_HOST') ?: '10.0.2.23',
		'port' => getenv('DB_PORT') ?: '5432',
		'username' => getenv('DB_USERNAME') ?: 'postgres',
		'password' => getenv('DB_PASSWORD') ?: '',
		'database' => getenv('DB_DATABASE') ?: 'pgm',
	];
	$localFile = $root . '/config/app_local.php';
	if (!is_file($localFile)) {
		return $cfg;
	}
	$local = include $localFile;
	if (!is_array($local) || !isset($local['Datasources']['default']) || !is_array($local['Datasources']['default'])) {
		return $cfg;
	}
	foreach (['host', 'port', 'username', 'password', 'database'] as $key) {
		if (isset($local['Datasources']['default'][$key]) && $local['Datasources']['default'][$key] !== '') {
			$cfg[$key] = (string)$local['Datasources']['default'][$key];
		}
	}

	return $cfg;
}

pgmCheckDbLoadDotEnv($root);
$cfg = pgmCheckDbResolveConfig($root);

echo "=== Diagnóstico PostgreSQL (PGM Portal) ===\n\n";
echo "DB_HOST (.env): " . (getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? getenv('DB_HOST') : '(não definido)') . "\n";
echo "Host efetivo (env + app_local.php): {$cfg['host']}\n";
echo "port={$cfg['port']} database={$cfg['database']} username={$cfg['username']}\n";

if (in_array($cfg['host'], ['localhost', '127.0.0.1', '::1'], true)) {
	echo "\nAVISO: host é loopback. Produção PGM: DB_HOST=10.0.2.23 no .env e sem host localhost em config/app_local.php\n";
}

$errno = 0;
$errstr = '';
$fp = @fsockopen($cfg['host'], (int)$cfg['port'], $errno, $errstr, 5);
if ($fp) {
	fclose($fp);
	echo "\nTCP {$cfg['host']}:{$cfg['port']} — OK\n";
} else {
	echo "\nTCP {$cfg['host']}:{$cfg['port']} — FALHOU ({$errno}) {$errstr}\n";
	echo "Verifique PostgreSQL em 10.0.2.23, firewall e pg_hba.conf (portal 10.0.2.25).\n";
	exit(1);
}

try {
	$pdo = new PDO(
		sprintf('pgsql:host=%s;port=%s;dbname=%s', $cfg['host'], $cfg['port'], $cfg['database']),
		$cfg['username'],
		$cfg['password'],
		[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
	);
	$pdo->query('SELECT 1');
	echo "PDO direto — OK\n";
} catch (Throwable $e) {
	echo "PDO direto — FALHOU: " . $e->getMessage() . "\n";
	exit(1);
}

if ($useCake) {
	require $root . '/vendor/autoload.php';
	require $root . '/config/bootstrap.php';
	try {
		\Cake\Datasource\ConnectionManager::get('default')->connect();
		echo "Cake ConnectionManager — OK\n";
	} catch (Throwable $e) {
		echo "Cake ConnectionManager — FALHOU: " . $e->getMessage() . "\n";
		exit(1);
	}
}

echo "\nConexão ao banco está OK com a configuração acima.\n";
if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
	echo "Dica: este teste rodou como root. No browser o PHP-FPM usa outro usuário.\n";
	echo "Confirme com: sudo -u www-data php bin/check_db_env.php\n";
}
echo "Se o site ainda der erro, limpe cache: bin/cake cache clear_all\n";
exit(0);
