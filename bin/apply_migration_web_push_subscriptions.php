<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$version = '20260520140000';
$class = 'WebPushSubscriptions';
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
if (in_array('web_push_subscriptions', $tables, true)) {
	echo "Tabela web_push_subscriptions já existe.\n";
} else {
	$pdo->exec(<<<'SQL'
CREATE TABLE web_push_subscriptions (
	id SERIAL PRIMARY KEY,
	user_id INTEGER NOT NULL,
	idempresa INTEGER NULL,
	endpoint TEXT NOT NULL,
	endpoint_hash VARCHAR(64) NOT NULL,
	p256dh VARCHAR(200) NOT NULL,
	auth VARCHAR(100) NOT NULL,
	user_agent VARCHAR(255) NULL,
	last_seen_at TIMESTAMP NULL,
	inativo INTEGER NOT NULL DEFAULT 0,
	created TIMESTAMP NULL,
	modified TIMESTAMP NULL
);
CREATE INDEX web_push_subscriptions_user_id ON web_push_subscriptions (user_id);
CREATE UNIQUE INDEX web_push_subscriptions_endpoint_hash ON web_push_subscriptions (endpoint_hash);
SQL);
	echo "Tabela criada.\n";
}
if (in_array('phinxlog', $tables, true)) {
	$st = $pdo->prepare('SELECT COUNT(*) FROM phinxlog WHERE version = :v');
	$st->execute(['v' => $version]);
	if ((int)$st->fetchColumn() === 0) {
		$now = date('Y-m-d H:i:s');
		$ins = $pdo->prepare('INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint) VALUES (:v, :n, :t, :t, false)');
		$ins->execute(['v' => $version, 'n' => $class, 't' => $now]);
		echo "phinxlog: {$version} {$class} registrado.\n";
	}
}
echo "OK\n";
