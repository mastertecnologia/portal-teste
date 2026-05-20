<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$version = '20260520160000';
$class = 'PrototypeStatusHistory';
$envFile = $root . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile)) {
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
	(string)getenv('DB_USERNAME'), (string)getenv('DB_PASSWORD'),
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
if (in_array('prototype_status_history', $tables, true)) {
	echo "Tabela já existe.\n";
} else {
	$pdo->exec(<<<'SQL'
CREATE TABLE prototype_status_history (
	id SERIAL PRIMARY KEY,
	idempresa INTEGER NULL,
	source_type VARCHAR(40) NOT NULL,
	source_id INTEGER NOT NULL,
	status_from VARCHAR(40) NULL,
	status_to VARCHAR(40) NOT NULL,
	actor_user_id INTEGER NULL,
	actor_name VARCHAR(120) NULL,
	actor_ip VARCHAR(45) NULL,
	note TEXT NULL,
	created TIMESTAMP NOT NULL
);
CREATE INDEX prototype_status_history_source ON prototype_status_history (source_type, source_id);
CREATE INDEX prototype_status_history_created ON prototype_status_history (created);
SQL);
	echo "Tabela prototype_status_history criada.\n";
}
if (in_array('phinxlog', $tables, true)) {
	$st = $pdo->prepare('SELECT COUNT(*) FROM phinxlog WHERE version = :v');
	$st->execute(['v' => $version]);
	if ((int)$st->fetchColumn() === 0) {
		$now = date('Y-m-d H:i:s');
		$ins = $pdo->prepare('INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint) VALUES (:v, :n, :t, :t, false)');
		$ins->execute(['v' => $version, 'n' => $class, 't' => $now]);
		echo "phinxlog registrado.\n";
	}
}
echo "OK\n";
