<?php
use Migrations\AbstractMigration;

/**
 * tickets.view: incluir apiindex,apiview para alinhar catálogo ao piloto rbac_api_enforced_actions (UI React).
 */
class RbacTicketsViewApiReadJson extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$conn = $this->getAdapter()->getConnection();
		$stmt = $conn->prepare('SELECT id, action FROM rbac_permissions WHERE code = ? LIMIT 1');
		$stmt->execute(['tickets.view']);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$row || empty($row['action'])) {
			return;
		}
		if (stripos($row['action'], 'apiindex') !== false) {
			return;
		}
		$newAction = $row['action'] . ',apiindex,apiview';
		$u = $conn->prepare('UPDATE rbac_permissions SET action = ? WHERE id = ?');
		$u->execute([$newAction, $row['id']]);
	}

	public function down() {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$conn = $this->getAdapter()->getConnection();
		$stmt = $conn->prepare('SELECT id, action FROM rbac_permissions WHERE code = ? LIMIT 1');
		$stmt->execute(['tickets.view']);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$row || empty($row['action'])) {
			return;
		}
		$newAction = str_replace(',apiindex,apiview', '', $row['action']);
		if ($newAction === $row['action']) {
			return;
		}
		$u = $conn->prepare('UPDATE rbac_permissions SET action = ? WHERE id = ?');
		$u->execute([$newAction, $row['id']]);
	}
}
