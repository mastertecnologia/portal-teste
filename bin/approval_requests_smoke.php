<?php
/**
 * Smoke: ORM ApprovalRequests + ApprovalRequestSyncService (sem gravar RBAC real).
 * Uso: php bin/approval_requests_smoke.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/config/bootstrap.php';

use App\Service\ApprovalRequestSyncService;
use Cake\ORM\TableRegistry;

$svc = new ApprovalRequestSyncService();
echo 'isEnabled=' . ($svc->isEnabled() ? 'yes' : 'no') . "\n";

$tbl = TableRegistry::getTableLocator()->get('ApprovalRequests');
$count = $tbl->find()->count();
echo 'approval_requests rows=' . $count . "\n";

$schema = $tbl->getSchema()->columns();
$need = ['idempresa', 'source_type', 'source_id', 'status', 'title'];
$miss = array_diff($need, $schema);
if ($miss !== []) {
	fwrite(STDERR, 'Schema ORM faltando: ' . implode(', ', $miss) . "\n");
	exit(2);
}

echo "ORM ApprovalRequests: OK\n";
exit(0);
