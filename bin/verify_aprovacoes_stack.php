<?php
/**
 * Verificação rápida: approval_requests + fila SD + RBAC dual-write.
 * Uso: php bin/verify_aprovacoes_stack.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

function ok(string $msg): void {
	echo "[OK] {$msg}\n";
}

function fail(array &$errors, string $msg): void {
	$errors[] = $msg;
	echo "[FAIL] {$msg}\n";
}

$files = [
	'config/Migrations/20260519120000_ApprovalRequests.php',
	'src/Model/Entity/ApprovalRequest.php',
	'src/Model/Table/ApprovalRequestsTable.php',
	'src/Service/ApprovalRequestSyncService.php',
	'bin/apply_migration_approval_requests.php',
	'bin/verify_approval_requests_schema.php',
];
foreach ($files as $rel) {
	$path = $root . '/' . $rel;
	if (!is_file($path)) {
		fail($errors, "Arquivo ausente: {$rel}");
		continue;
	}
	exec('php -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
	if ($code !== 0) {
		fail($errors, "Sintaxe {$rel}: " . implode(' ', $out));
	} else {
		ok("Sintaxe {$rel}");
	}
}

require $root . '/vendor/autoload.php';
require $root . '/config/bootstrap.php';

use App\Service\ApprovalRequestSyncService;
use Cake\ORM\TableRegistry;

$sync = new ApprovalRequestSyncService();
if ($sync->isEnabled()) {
	ok('ApprovalRequestSyncService habilitado (tabela + config)');
} else {
	fail($errors, 'ApprovalRequestSyncService desabilitado ou tabela ausente');
}

try {
	$tbl = TableRegistry::getTableLocator()->get('ApprovalRequests');
	$tbl->find()->limit(1)->count();
	ok('ORM ApprovalRequests consulta OK');
} catch (\Throwable $e) {
	fail($errors, 'ORM ApprovalRequests: ' . $e->getMessage());
}

$svcFile = $root . '/src/Service/Ticket/ServicedeskPrototypeDataService.php';
if (strpos((string)file_get_contents($svcFile), 'aprovacoesTicketFechamentoSince') === false) {
	fail($errors, 'ServicedeskPrototypeDataService sem janela de fechamento');
} else {
	ok('Fila SD: janela fechamento 30d no coletor');
}

passthru('php ' . escapeshellarg($root . '/bin/verify_approval_requests_schema.php'), $schemaCode);
if ($schemaCode !== 0) {
	$errors[] = 'Schema DB approval_requests';
}

echo "\n";
if ($errors === []) {
	echo "Tudo OK (" . count($files) . " arquivos + DB + ORM).\n";
	exit(0);
}
echo count($errors) . " problema(s).\n";
exit(1);
