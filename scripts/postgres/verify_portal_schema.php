<?php
/**
 * Compara tabelas existentes no PostgreSQL com o conjunto esperado pelo portal (legado + migrations).
 *
 * Uso (na raiz do projeto, com vendor e config/app_local.php):
 *   php scripts/postgres/verify_portal_schema.php
 *
 * Não altera o banco — apenas relatório em stdout (código saída 0 = ok, 1 = faltam tabelas).
 */
$root = dirname(__DIR__, 2);
chdir($root);
require $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use Cake\Datasource\ConnectionManager;

/**
 * Núcleo legado PGM (não há dump SQL completo no Git). Deve vir de backup/restauração.
 */
$LEGACY_TABLES = [
	'users', 'empresas', 'empresasusers', 'clientes', 'tickets', 'ticketsmovs', 'ticketcomentarios',
	'ticketsanexos', 'ticketshoras', 'ticketsclientes', 'ticketsmodulos', 'ticketsusers', 'ticketsservicos',
	'ordensservico', 'ordemservicositens', 'itensordem', 'orcamentosnovosdes', 'orcamentosnovosdesservicos',
	'orcamentosnovositens', 'orcamentosnovosdesmovs', 'visitas', 'listamembros', 'atividades', 'produtos',
	'problemas', 'areas', 'faturas', 'clicontratos', 'config', 'cidades', 'feriados', 'ordemmovs',
	'ordemparcelas', 'ordemhoras', 'cliacessos', 'tarefas',
];

/**
 * Legado opcional (só aviso; não falha o código de saída). Ex.: books só é loadModel em UsersController.
 */
$LEGACY_OPTIONAL_TABLES = [
	'books',
];

/**
 * Tabelas criadas ou preenchidas pelas migrations em config/Migrations (bin/cake migrations migrate).
 * Algumas dependem de legado (ex.: ALTER em tickets); outras são CREATE standalone em PostgreSQL.
 */
$MIGRATION_TABLES = [
	'phinxlog',
	'queues', 'queues_users', 'support_levels',
	'sla_policies', 'ticket_histories',
	'contratos_horas',
	'rbac_permissions', 'rbac_roles', 'rbac_roles_permissions', 'rbac_users_roles',
	'faturas_ordens_servico',
	'faturamento', 'faturamento_itens', 'financeiro_lancamentos', 'financeiro_lancamento_anexos',
	'portal_internal_notifications', 'portal_notification_preferences', 'client_domain_events', 'portal_mail_automation_logs',
	'contracts', 'contract_services', 'contract_documents', 'contract_consumptions',
	'attendance_histories', 'attendance_timeline', 'attendance_attachments',
	'invoices', 'invoice_items', 'invoice_payments', 'audit_logs',
	'contract_templates', 'contract_signatories', 'contract_autentique_logs', 'contract_renewals', 'contract_notifications',
	'faturas_escrita_fiscal',
];

$EXPECTED = array_values(array_unique(array_merge($LEGACY_TABLES, $MIGRATION_TABLES, $LEGACY_OPTIONAL_TABLES)));
sort($EXPECTED);

try {
	$c = ConnectionManager::get('default');
	$cn = get_class($c->getDriver());
	if (stripos($cn, 'postgres') === false) {
		fwrite(STDERR, "Este script é para PostgreSQL. Driver atual: {$cn}\n");
		exit(2);
	}
	$stmt = $c->execute("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public' ORDER BY tablename");
	$rows = $stmt->fetchAll('assoc');
} catch (\Throwable $e) {
	fwrite(STDERR, "Erro de conexão ou consulta: " . $e->getMessage() . "\n");
	exit(3);
}

$have = [];
foreach ($rows as $r) {
	$have[$r['tablename']] = true;
}

$missingLegacy = [];
foreach ($LEGACY_TABLES as $t) {
	if (empty($have[$t])) {
		$missingLegacy[] = $t;
	}
}

$missingLegacyOptional = [];
foreach ($LEGACY_OPTIONAL_TABLES as $t) {
	if (empty($have[$t])) {
		$missingLegacyOptional[] = $t;
	}
}

$missingMigration = [];
foreach ($MIGRATION_TABLES as $t) {
	if (empty($have[$t])) {
		$missingMigration[] = $t;
	}
}

$extra = [];
foreach (array_keys($have) as $t) {
	if (!in_array($t, $EXPECTED, true)) {
		$extra[] = $t;
	}
}
sort($extra);

echo "=== Verificação de schema (schema public) ===\n";
echo 'Tabelas no banco: ' . count($have) . "\n";
echo 'Lista esperada (legado + migrations + opcionais): ' . count($EXPECTED) . "\n\n";

if ($missingLegacy !== []) {
	echo ">>> CRÍTICO — tabelas LEGADAS ausentes (" . count($missingLegacy) . "):\n";
	foreach ($missingLegacy as $t) {
		echo "  - {$t}\n";
	}
	echo "\nO projeto não versiona o DDL completo do ERP legado. Restaure um backup (.dump / .sql) do banco antes de continuar.\n";
	echo "Junctions ticketsservicos/ticketsmodulos: ver config/schema/postgres_ticketsservicos_ticketsmodulos_books_minimal.sql\n\n";
}

if ($missingLegacyOptional !== []) {
	echo ">>> AVISO — tabelas legadas opcionais ausentes (" . count($missingLegacyOptional) . "):\n";
	foreach ($missingLegacyOptional as $t) {
		echo "  - {$t}\n";
	}
	echo "\n";
}

if ($missingMigration !== []) {
	echo ">>> Tabelas de MIGRATIONS ausentes (" . count($missingMigration) . ") — rode as migrations:\n";
	foreach ($missingMigration as $t) {
		echo "  - {$t}\n";
	}
	echo "\n  bin/cake migrations migrate\n\n";
}

if ($extra !== []) {
	echo "Tabelas extras (não na lista de verificação): " . count($extra) . " — ok se forem de outros módulos.\n";
	$show = array_slice($extra, 0, 40);
	foreach ($show as $t) {
		echo "  + {$t}\n";
	}
	if (count($extra) > 40) {
		echo "  ... e mais " . (count($extra) - 40) . ".\n";
	}
	echo "\n";
}

$ok = ($missingLegacy === [] && $missingMigration === []);
if ($ok) {
	echo "Nenhuma tabela obrigatória faltando na lista de verificação.\n";
	exit(0);
}

exit(1);
