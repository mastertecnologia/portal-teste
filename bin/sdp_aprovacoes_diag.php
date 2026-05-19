<?php
/**
 * Contagem de itens da Fila de aprovações (protótipo SD) — mesmos critérios de
 * ServicedeskPrototypeDataService::buildAprovacoesPayload().
 *
 * Uso (raiz do projeto, com .env ou variáveis DB_*):
 *   php bin/sdp_aprovacoes_diag.php
 *   php bin/sdp_aprovacoes_diag.php --empresa=2
 *   php bin/sdp_aprovacoes_diag.php --empresa=2 --samples
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$envFile = $root . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile) && is_readable($envFile)) {
	$raw = file_get_contents($envFile);
	if ($raw !== false && strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
		$raw = substr($raw, 3);
	}
	foreach (preg_split('/\R/', $raw) ?: [] as $line) {
		$line = trim($line);
		if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
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
		}
	}
}

$idempresa = null;
$samples = false;
foreach (array_slice($argv, 1) as $arg) {
	if (preg_match('/^--empresa=(\d+)$/', $arg, $m)) {
		$idempresa = (int)$m[1];
	} elseif ($arg === '--samples') {
		$samples = true;
	}
}

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$db = getenv('DB_DATABASE') ?: 'pgm';
$user = getenv('DB_USERNAME') ?: '';
$pass = getenv('DB_PASSWORD') ?: '';

$pdo = new PDO(
	sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db),
	$user,
	$pass,
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = $pdo->query(
	"SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
)->fetchAll(PDO::FETCH_COLUMN);
$tableExists = static function (string $t) use ($tables): bool {
	return in_array($t, $tables, true);
};

$pgmDir = $root . '/vendor/PGMPackages/';
foreach (['Utilities.php', 'UserConstants.php', 'TicketConstants.php'] as $f) {
	$p = $pgmDir . $f;
	if (is_file($p)) {
		require_once $p;
	}
}

$sitResolvido = defined('C_TicketSituacaoResolvido') ? (int)C_TicketSituacaoResolvido : null;
$sitFechado = defined('C_TicketSituacaoFechado') ? (int)C_TicketSituacaoFechado : null;
$sitCancelado = defined('C_TicketSituacaoCancelado') ? (int)C_TicketSituacaoCancelado : null;

if ($sitResolvido === null && $tableExists('workflow_states')) {
	$row = $pdo->query(
		"SELECT legacy_situacao FROM workflow_states WHERE legacy_situacao IS NOT NULL AND lower(coalesce(code,'') || ' ' || coalesce(name,'')) LIKE '%resolv%' LIMIT 1"
	)->fetch(PDO::FETCH_ASSOC);
	if ($row && $row['legacy_situacao'] !== null) {
		$sitResolvido = (int)$row['legacy_situacao'];
	}
}

$closed = array_values(array_unique(array_filter(
	[$sitResolvido, $sitFechado, $sitCancelado],
	static function ($v) {
		return $v !== null;
	}
)));

$monthStart = (new DateTimeImmutable('first day of this month'))->format('Y-m-d 00:00:00');
$fechamentoDias = (int)(getenv('SERVICEDESK_APROVACOES_FECHAMENTO_DIAS') ?: 30);
if ($fechamentoDias <= 0) {
	$fechamentoDias = 30;
}
$fechamentoSince = (new DateTimeImmutable("-{$fechamentoDias} days"))->format('Y-m-d H:i:s');

echo "=== Fila de aprovações — diagnóstico PostgreSQL ===\n";
echo 'DB: ' . $db . '@' . $host . ':' . $port . "\n";
echo 'Mês corrente (histórico): >= ' . $monthStart . "\n";
if ($idempresa !== null) {
	echo 'Filtro empresa (tickets/orçamentos/renovações): idempresa=' . $idempresa . "\n";
} else {
	echo "Filtro empresa: (todas — RBAC é global)\n";
}
echo 'Constantes ticket: resolvido=' . ($sitResolvido !== null ? (string)$sitResolvido : '?')
	. ' fechado=' . ($sitFechado !== null ? (string)$sitFechado : '?')
	. ' cancelado=' . ($sitCancelado !== null ? (string)$sitCancelado : '?') . "\n";
echo "Janela fechamento SD: últimos {$fechamentoDias} dias (desde {$fechamentoSince})\n\n";

$pending = [];
$approvedMonth = [];
$rejectedMonth = [];

// --- RBAC (sem filtro empresa; limite UI 80) ---
if ($tableExists('rbac_access_requests')) {
	$pending['rbac_acesso'] = (int)$pdo->query(
		"SELECT COUNT(*) FROM rbac_access_requests WHERE status IN ('pending_manager','pending_admin','manager_approved')"
	)->fetchColumn();

	$st = $pdo->prepare(
		"SELECT COUNT(*) FROM rbac_access_requests WHERE (status ILIKE '%approv%' OR status = 'granted')
		 AND COALESCE(admin_reviewed_at, manager_reviewed_at) >= :m"
	);
	$st->execute(['m' => $monthStart]);
	$rbacApr = (int)$st->fetchColumn();
	$st = $pdo->prepare(
		"SELECT COUNT(*) FROM rbac_access_requests WHERE (status ILIKE '%reject%' OR status = 'rejected')
		 AND COALESCE(admin_reviewed_at, manager_reviewed_at) >= :m"
	);
	$st->execute(['m' => $monthStart]);
	$rbacRej = (int)$st->fetchColumn();
	$approvedMonth['rbac_acesso'] = $rbacApr;
	$rejectedMonth['rbac_acesso'] = $rbacRej;

	if ($samples) {
		echo "--- RBAC pendentes (amostra) ---\n";
		foreach ($pdo->query(
			"SELECT id, user_id, status, support_code, created FROM rbac_access_requests
			 WHERE status IN ('pending_manager','pending_admin','manager_approved')
			 ORDER BY created DESC LIMIT 8"
		) as $r) {
			echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
		}
	}
} else {
	echo "(tabela rbac_access_requests ausente)\n";
}

// --- Renovações ---
if ($tableExists('contract_renewals') && $tableExists('contracts')) {
	$empJoin = $idempresa !== null ? ' AND c.idempresa = ' . (int)$idempresa : '';
	$pending['renovacao'] = (int)$pdo->query(
		"SELECT COUNT(*) FROM contract_renewals r INNER JOIN contracts c ON c.id = r.contract_id
		 WHERE r.status = 'pendente'" . $empJoin
	)->fetchColumn();
	$st = $pdo->prepare(
		"SELECT COUNT(*) FROM contract_renewals r INNER JOIN contracts c ON c.id = r.contract_id
		 WHERE r.status = 'aprovada' AND r.aprovado_em >= :m" . $empJoin
	);
	$st->execute(['m' => $monthStart]);
	$approvedMonth['renovacao'] = (int)$st->fetchColumn();
	$st = $pdo->prepare(
		"SELECT COUNT(*) FROM contract_renewals r INNER JOIN contracts c ON c.id = r.contract_id
		 WHERE r.status = 'recusada' AND r.aprovado_em >= :m" . $empJoin
	);
	$st->execute(['m' => $monthStart]);
	$rejectedMonth['renovacao'] = (int)$st->fetchColumn();
} else {
	echo "(contract_renewals/contracts ausente)\n";
}

// --- Orçamentos ---
if ($tableExists('orcamentos')) {
	$empW = $idempresa !== null ? ' AND idempresa = ' . (int)$idempresa : '';
	$pending['orcamento'] = (int)$pdo->query(
		"SELECT COUNT(*) FROM orcamentos WHERE status IN (0, 1)" . $empW
	)->fetchColumn();
	$st = $pdo->prepare(
		"SELECT COUNT(*) FROM orcamentos WHERE status = 2 AND modified >= :m" . $empW
	);
	$st->execute(['m' => $monthStart]);
	$approvedMonth['orcamento'] = (int)$st->fetchColumn();
	$st = $pdo->prepare(
		"SELECT COUNT(*) FROM orcamentos WHERE status = 3 AND modified >= :m" . $empW
	);
	$st->execute(['m' => $monthStart]);
	$rejectedMonth['orcamento'] = (int)$st->fetchColumn();
}

// --- Tickets ---
if ($tableExists('tickets')) {
	$tCols = $pdo->query(
		"SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'tickets'"
	)->fetchAll(PDO::FETCH_COLUMN);
	$empW = $idempresa !== null ? ' AND t.idempresa = ' . (int)$idempresa : '';

	if ($sitResolvido !== null && in_array('situacao', $tCols, true)) {
		$activityCol = null;
		foreach (['modified', 'updated', 'dataalteracao', 'created'] as $c) {
			if (in_array($c, $tCols, true)) {
				$activityCol = $c;
				break;
			}
		}
		$hasDr = in_array('data_resolucao', $tCols, true);
		if ($hasDr && $activityCol !== null) {
			$st = $pdo->prepare(
				"SELECT COUNT(*) FROM tickets t WHERE t.situacao = :sit{$empW}
				 AND (t.data_resolucao >= :since OR (t.data_resolucao IS NULL AND t.{$activityCol} >= :since2))"
			);
			$st->execute(['sit' => $sitResolvido, 'since' => $fechamentoSince, 'since2' => $fechamentoSince]);
			$pending['ticket_fechamento'] = (int)$st->fetchColumn();
		} elseif ($activityCol !== null) {
			$st = $pdo->prepare(
				"SELECT COUNT(*) FROM tickets t WHERE t.situacao = :sit AND t.{$activityCol} >= :since{$empW}"
			);
			$st->execute(['sit' => $sitResolvido, 'since' => $fechamentoSince]);
			$pending['ticket_fechamento'] = (int)$st->fetchColumn();
		}
		$pending['ticket_fechamento_sem_janela'] = (int)$pdo->query(
			"SELECT COUNT(*) FROM tickets t WHERE t.situacao = {$sitResolvido}" . $empW
		)->fetchColumn();
	}

	$notClosedSql = '';
	if ($closed !== []) {
		$in = implode(',', array_map('intval', $closed));
		$notClosedSql = " AND t.situacao NOT IN ({$in})";
	}

	// Mudança: fila mudança/change OU P1 (aproximação SQL)
	if ($tableExists('queues') && in_array('queue_id', $tCols, true)) {
		$sqlMud = "SELECT COUNT(*) FROM tickets t
			LEFT JOIN queues q ON q.id = t.queue_id
			WHERE 1=1{$empW}{$notClosedSql}
			AND (
				(lower(coalesce(q.codigo,'') || ' ' || coalesce(q.name,'')) LIKE '%mudanca%'
				 OR lower(coalesce(q.codigo,'') || ' ' || coalesce(q.name,'')) LIKE '%mudança%'
				 OR lower(coalesce(q.codigo,'') || ' ' || coalesce(q.name,'')) LIKE '%change%')";
		if (in_array('prioridade', $tCols, true)) {
			$sqlMud .= "
				OR lower(trim(coalesce(t.prioridade::text,''))) IN ('p1','1','critica','critico')";
		}
		$sqlMud .= ')';
		$pending['ticket_mudanca'] = (int)$pdo->query($sqlMud)->fetchColumn();
	}

	if (in_array('sla_escalated_at', $tCols, true)) {
		$pending['ticket_escalonamento'] = (int)$pdo->query(
			"SELECT COUNT(*) FROM tickets t WHERE t.sla_escalated_at IS NOT NULL{$empW}{$notClosedSql}"
		)->fetchColumn();
	}

	if ($closed !== [] && in_array('data_resolucao', $tCols, true)) {
		$in = implode(',', array_map('intval', $closed));
		$st = $pdo->prepare(
			"SELECT COUNT(*) FROM tickets t WHERE t.situacao IN ({$in}) AND t.data_resolucao >= :m{$empW}"
		);
		$st->execute(['m' => $monthStart]);
		$approvedMonth['ticket_fechado_mes'] = (int)$st->fetchColumn();
	}
}

// Nota: a UI aplica limite (25–80) e ABAC; totais SQL podem ser > cards visíveis na tela.

if ($tableExists('approval_requests')) {
	$pending['approval_requests_pending'] = (int)$pdo->query(
		"SELECT COUNT(*) FROM approval_requests WHERE status = 'pending'" .
		($idempresa !== null ? ' AND idempresa = ' . (int)$idempresa : '')
	)->fetchColumn();
}

$labels = [
	'rbac_acesso' => 'RBAC — acesso elevado',
	'renovacao' => 'Renovação contratual',
	'orcamento' => 'Orçamento (status 0/1)',
	'ticket_fechamento' => 'Ticket — fechamento (resolvido, janela SD)',
	'ticket_fechamento_sem_janela' => 'Ticket — resolvido (sem janela, ref.)',
	'ticket_mudanca' => 'Ticket — mudança (fila change/P1)',
	'ticket_escalonamento' => 'Ticket — escalonamento SLA',
	'approval_requests_pending' => 'approval_requests (pending)',
];

echo "\n--- PENDENTES (critério fila SD) ---\n";
$sumPending = 0;
$sumKeys = ['rbac_acesso', 'renovacao', 'orcamento', 'ticket_fechamento', 'ticket_mudanca', 'ticket_escalonamento', 'approval_requests_pending'];
foreach ($labels as $key => $label) {
	$n = (int)($pending[$key] ?? 0);
	if (!isset($pending[$key])) {
		echo "  " . str_pad($label, 28) . "     — (tabela/coluna ausente)\n";
		continue;
	}
	if (in_array($key, $sumKeys, true)) {
		$sumPending += $n;
	}
	printf("  %-28s %5d\n", $label, $n);
}
printf("  %-28s %5d  ← soma (sem deduplicar tickets)\n", 'TOTAL', $sumPending);
echo "\n  Nota: na UI, RBAC/orçamentos/renovações têm LIMIT; tickets passam por ABAC do utilizador logado.\n";

echo "\n--- DECISÕES NO MÊS (histórico KPI) ---\n";
foreach (array_merge($approvedMonth, $rejectedMonth) ? array_unique(array_merge(array_keys($approvedMonth), array_keys($rejectedMonth))) : [] as $key) {
	$a = (int)($approvedMonth[$key] ?? 0);
	$r = (int)($rejectedMonth[$key] ?? 0);
	if ($a === 0 && $r === 0) {
		continue;
	}
	$lbl = $labels[$key] ?? $key;
	printf("  %-28s aprovadas=%d  reprovadas=%d\n", $lbl, $a, $r);
}

if ($idempresa === null && $tableExists('tickets')) {
	echo "\n--- PENDENTES POR EMPRESA (tickets resolvido + orçamento) ---\n";
	if ($sitResolvido !== null) {
		foreach ($pdo->query(
			"SELECT idempresa, COUNT(*) AS n FROM tickets WHERE situacao = {$sitResolvido} GROUP BY idempresa ORDER BY n DESC LIMIT 12"
		) as $r) {
			echo '  tickets resolvido  idempresa=' . $r['idempresa'] . '  n=' . $r['n'] . "\n";
		}
	}
	if ($tableExists('orcamentos')) {
		foreach ($pdo->query(
			'SELECT idempresa, COUNT(*) AS n FROM orcamentos WHERE status IN (0,1) GROUP BY idempresa ORDER BY n DESC LIMIT 12'
		) as $r) {
			echo '  orçamentos pend/env  idempresa=' . $r['idempresa'] . '  n=' . $r['n'] . "\n";
		}
	}
}

echo "\nConcluído.\n";
