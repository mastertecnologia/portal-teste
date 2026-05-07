<?php
use Migrations\AbstractMigration;

/**
 * RBAC: permitir relatório de SLA (Servicedesk::slaRelatorio) em servicedesk.view.
 *
 * Timestamp 20260507180500 (depois de WorkflowSlaPoliciesAtivo em 20260507180000)
 * para evitar dois ficheiros com o mesmo prefixo temporal.
 */
class RbacServicedeskSlaRelatorioPatch extends AbstractMigration {

	protected function _appendActions($code, $csv): void {
		$conn = $this->getAdapter()->getConnection();
		$stmt = $conn->prepare('SELECT id, action FROM rbac_permissions WHERE code = ? LIMIT 1');
		$stmt->execute([$code]);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$row) {
			return;
		}
		$action = (string)$row['action'];
		foreach (array_filter(array_map('trim', explode(',', $csv))) as $p) {
			$p = strtolower($p);
			if ($p === '') {
				continue;
			}
			if (stripos($action, $p) !== false) {
				continue;
			}
			$action .= ($action === '' ? '' : ',') . $p;
		}
		if ($action === $row['action']) {
			return;
		}
		$u = $conn->prepare('UPDATE rbac_permissions SET action = ? WHERE id = ?');
		$u->execute([$action, $row['id']]);
	}

	public function up(): void {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$this->_appendActions('servicedesk.view', 'slaRelatorio');
	}

	public function down(): void {
		// Patch aditivo.
	}
}
