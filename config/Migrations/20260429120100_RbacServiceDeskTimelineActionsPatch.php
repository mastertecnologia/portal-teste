<?php
use Migrations\AbstractMigration;

/**
 * RBAC: APIs Timeline B, geo, assinatura, PDFs (tickets).
 */
class RbacServiceDeskTimelineActionsPatch extends AbstractMigration {

	protected function _appendActions($code, $csv) {
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

	public function up() {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$this->_appendActions('tickets.view', 'apitimeline,apivalidategeolocation,apipdfticketos,apipdflaudo');
		$this->_appendActions('tickets.update', 'apiticketsignature,apiaddticketproduct,apiaddevidencephoto');
		$this->_appendActions('tickets.timer', 'apivalidategeolocation');
	}

	public function down() {
	}
}
