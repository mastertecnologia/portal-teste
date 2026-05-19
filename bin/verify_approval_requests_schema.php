<?php
/**
 * Valida tabela approval_requests vs migration 20260519120000.
 * Uso: php bin/verify_approval_requests_schema.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$envFile = $root . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile)) {
	foreach (preg_split('/\R/', (string)file_get_contents($envFile)) ?: [] as $line) {
		$line = trim($line);
		if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
			continue;
		}
		[$n, $v] = explode('=', $line, 2);
		if (trim($n) !== '') {
			putenv(trim($n) . '=' . trim($v, " \t\"'"));
		}
	}
}

$expected = [
	'id', 'idempresa', 'source_type', 'source_id', 'status', 'title', 'summary_json',
	'requested_by', 'requested_at', 'assignee_role', 'sla_due_at', 'decided_by',
	'decided_at', 'decision_note', 'created', 'modified',
];

$pdo = new PDO(
	sprintf('pgsql:host=%s;port=%s;dbname=%s', getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_DATABASE')),
	getenv('DB_USERNAME'),
	getenv('DB_PASSWORD'),
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query(
	"SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'approval_requests' ORDER BY ordinal_position"
)->fetchAll(PDO::FETCH_COLUMN);

if ($cols === []) {
	fwrite(STDERR, "ERRO: tabela approval_requests não existe.\n");
	exit(1);
}

$missing = array_diff($expected, $cols);
$extra = array_diff($cols, $expected);
$ok = $missing === [] && $extra === [];

echo 'Colunas: ' . implode(', ', $cols) . "\n";
if ($missing !== []) {
	echo 'Faltam: ' . implode(', ', $missing) . "\n";
}
if ($extra !== []) {
	echo 'Extras: ' . implode(', ', $extra) . "\n";
}

$st = $pdo->prepare('SELECT COUNT(*) FROM phinxlog WHERE version = :v');
$st->execute(['v' => '20260519120000']);
$phinx = (int)$st->fetchColumn();
echo 'phinxlog 20260519120000: ' . ($phinx > 0 ? 'OK' : 'AUSENTE') . "\n";

$indexes = $pdo->query(
	"SELECT indexname FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'approval_requests'"
)->fetchAll(PDO::FETCH_COLUMN);
echo 'Índices: ' . implode(', ', $indexes) . "\n";

exit($ok && $phinx > 0 ? 0 : 2);
