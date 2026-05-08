<?php
use Migrations\AbstractMigration;

/**
 * Deploy RBAC: servicedesk.view com literais slarelatorio e slaRelatorio (sem remover tokens;
 * não duplica por comparação exata após trim) + papel operacao recebe tickets/servicedesk/contratos.
 */
class RbacServicedeskOperacaoDeployPatch extends AbstractMigration {

	/**
	 * Acrescenta um token à lista action se ainda não existir o mesmo literal (após trim).
	 */
	protected function ensureExactActionToken(string $code, string $token): void {
		$conn = $this->getAdapter()->getConnection();
		$stmt = $conn->prepare('SELECT id, action FROM rbac_permissions WHERE code = ? LIMIT 1');
		$stmt->execute([$code]);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$row) {
			return;
		}
		$action = (string)$row['action'];
		$tok = trim($token);
		if ($tok === '') {
			return;
		}
		foreach (preg_split('/\s*,\s*/', $action, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
			if (trim((string)$part) === $tok) {
				return;
			}
		}
		$next = $action === '' ? $tok : ($action . ',' . $tok);
		$u = $conn->prepare('UPDATE rbac_permissions SET action = ? WHERE id = ?');
		$u->execute([$next, $row['id']]);
	}

	public function up(): void {
		if ($this->hasTable('rbac_permissions')) {
			$this->ensureExactActionToken('servicedesk.view', 'slarelatorio');
			$this->ensureExactActionToken('servicedesk.view', 'slaRelatorio');
		}

		if (
			!$this->hasTable('rbac_roles_permissions')
			|| !$this->hasTable('rbac_roles')
			|| !$this->hasTable('rbac_permissions')
		) {
			return;
		}
		$codes = ['servicedesk.tickets', 'tickets.api', 'erp.contracts.management'];
		$codesSql = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $codes)) . "'";
		$this->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) '
			. 'SELECT r.id, p.id FROM rbac_roles r '
			. 'CROSS JOIN rbac_permissions p '
			. "WHERE r.slug = 'operacao' AND p.code IN ({$codesSql}) "
			. 'AND NOT EXISTS (SELECT 1 FROM rbac_roles_permissions x WHERE x.role_id = r.id AND x.permission_id = p.id)'
		);
	}

	public function down(): void {
		// Patch aditivo.
	}
}
