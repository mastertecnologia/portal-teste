<?php
/**
 * Aplica migration 20260519120000_ApprovalRequests quando bin/cake migrations não está disponível.
 * Equivalente a: bin/cake migrations migrate (só esta versão).
 *
 * Uso: php bin/apply_migration_approval_requests.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$version = '20260519120000';
$class = 'ApprovalRequests';

$envFile = $root . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile) && is_readable($envFile)) {
	$raw = file_get_contents($envFile);
	if ($raw !== false && strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
		$raw = substr($raw, 3);
	}
	foreach (preg_split('/\R/', $raw) ?: [] as $line) {
		$line = trim($line);
		if ($line === '' || $line[0] === '#') {
			continue;
		}
		if (stripos($line, 'export ') === 0) {
			$line = trim(substr($line, 7));
		}
		if (strpos($line, '=') === false) {
			continue;
		}
		[$name, $value] = explode('=', $line, 2);
		$name = ltrim(trim($name), "\xEF\xBB\xBF");
		$value = trim($value, " \t\"'");
		if ($name !== '') {
			putenv("$name=$value");
		}
	}
}

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$db = getenv('DB_DATABASE') ?: 'pgm';
$user = getenv('DB_USERNAME') ?: '';
$pass = getenv('DB_PASSWORD') ?: '';

echo "approval_requests migration | {$db}@{$host}:{$port}\n";

$pdo = new PDO(
	sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db),
	$user,
	$pass,
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = $pdo->query(
	"SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
)->fetchAll(PDO::FETCH_COLUMN);

if (in_array('approval_requests', $tables, true)) {
	echo "Tabela approval_requests já existe — nada a criar.\n";
} else {
	$sql = <<<'SQL'
CREATE TABLE approval_requests (
	id SERIAL PRIMARY KEY,
	idempresa INTEGER NOT NULL,
	source_type VARCHAR(40) NOT NULL,
	source_id INTEGER NOT NULL,
	status VARCHAR(20) NOT NULL DEFAULT 'pending',
	title VARCHAR(255) NOT NULL,
	summary_json TEXT NULL,
	requested_by INTEGER NULL,
	requested_at TIMESTAMP NULL,
	assignee_role VARCHAR(40) NULL,
	sla_due_at TIMESTAMP NULL,
	decided_by INTEGER NULL,
	decided_at TIMESTAMP NULL,
	decision_note TEXT NULL,
	created TIMESTAMP NULL,
	modified TIMESTAMP NULL
);
CREATE INDEX approval_requests_idempresa_status ON approval_requests (idempresa, status);
CREATE UNIQUE INDEX approval_requests_source_type_source_id ON approval_requests (source_type, source_id);
CREATE INDEX approval_requests_requested_at ON approval_requests (requested_at);
SQL;
	$pdo->exec($sql);
	echo "Tabela approval_requests criada.\n";
}

if (in_array('phinxlog', $tables, true)) {
	$st = $pdo->prepare('SELECT COUNT(*) FROM phinxlog WHERE version = :v');
	$st->execute(['v' => $version]);
	if ((int)$st->fetchColumn() === 0) {
		$now = date('Y-m-d H:i:s');
		$ins = $pdo->prepare(
			'INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
			 VALUES (:v, :n, :t, :t, false)'
		);
		$ins->execute(['v' => $version, 'n' => $class, 't' => $now]);
		echo "phinxlog: registrado {$version} {$class}\n";
	} else {
		echo "phinxlog: versão {$version} já registrada.\n";
	}
} else {
	echo "phinxlog ausente — tabela criada sem registro de versão.\n";
}

echo "OK\n";
