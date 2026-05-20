<?php
/**
 * Aplica migration 20260520120000_TicketCsatResponses quando bin/cake migrations
 * está indisponível. Idempotente.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$version = '20260520120000';
$class = 'TicketCsatResponses';

$envFile = $root . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile) && is_readable($envFile)) {
	foreach (preg_split('/\R/', (string)file_get_contents($envFile)) ?: [] as $line) {
		$line = trim($line);
		if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
			continue;
		}
		[$n, $v] = explode('=', $line, 2);
		putenv(trim($n) . '=' . trim($v, " \t\"'"));
	}
}

$pdo = new PDO(
	sprintf('pgsql:host=%s;port=%s;dbname=%s', getenv('DB_HOST') ?: 'localhost', getenv('DB_PORT') ?: '5432', getenv('DB_DATABASE') ?: 'pgm'),
	(string)getenv('DB_USERNAME'),
	(string)getenv('DB_PASSWORD'),
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
if (in_array('ticket_csat_responses', $tables, true)) {
	echo "Tabela ticket_csat_responses já existe.\n";
} else {
	$pdo->exec(<<<'SQL'
CREATE TABLE ticket_csat_responses (
	id SERIAL PRIMARY KEY,
	idempresa INTEGER NOT NULL,
	ticket_id INTEGER NOT NULL,
	idcliente INTEGER NULL,
	csat_score INTEGER NOT NULL,
	nps_score INTEGER NULL,
	comentario TEXT NULL,
	token VARCHAR(60) NOT NULL,
	responded_at TIMESTAMP NOT NULL,
	responded_ip VARCHAR(45) NULL,
	created TIMESTAMP NULL,
	modified TIMESTAMP NULL
);
CREATE UNIQUE INDEX ticket_csat_responses_ticket_id ON ticket_csat_responses (ticket_id);
CREATE INDEX ticket_csat_responses_empresa_at ON ticket_csat_responses (idempresa, responded_at);
CREATE UNIQUE INDEX ticket_csat_responses_token ON ticket_csat_responses (token);
SQL);
	echo "Tabela ticket_csat_responses criada.\n";
}

if (in_array('phinxlog', $tables, true)) {
	$st = $pdo->prepare('SELECT COUNT(*) FROM phinxlog WHERE version = :v');
	$st->execute(['v' => $version]);
	if ((int)$st->fetchColumn() === 0) {
		$now = date('Y-m-d H:i:s');
		$ins = $pdo->prepare('INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint) VALUES (:v, :n, :t, :t, false)');
		$ins->execute(['v' => $version, 'n' => $class, 't' => $now]);
		echo "phinxlog: registrado {$version} {$class}.\n";
	}
}

echo "OK\n";
