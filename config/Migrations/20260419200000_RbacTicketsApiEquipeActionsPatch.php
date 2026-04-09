<?php
use Migrations\AbstractMigration;

/**
 * Alinha rbac_permissions (tickets.*) às actions api* de equipe cobertas por rbac_api_enforced_actions.
 */
class RbacTicketsApiEquipeActionsPatch extends AbstractMigration {

	/**
	 * @param string $code rbac_permissions.code
	 * @param string $csv sufixos a acrescentar (minúsculo, como no match RBAC)
	 */
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
		$this->_appendActions('tickets.view', 'apicomments,apidashboardoperacional,apitecnicoslista');
		$this->_appendActions('tickets.update', 'apisaveticket,apianexoupload');
		$this->_appendActions('tickets.delete', 'apianexodelete');
		$this->_appendActions('tickets.assign', 'apistartticket');
		$this->_appendActions('tickets.timer', 'apitimer');
	}

	public function down() {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$conn = $this->getAdapter()->getConnection();
		$map = [
			'tickets.view' => [',apicomments', ',apidashboardoperacional', ',apitecnicoslista'],
			'tickets.update' => [',apisaveticket', ',apianexoupload'],
			'tickets.delete' => [',apianexodelete'],
			'tickets.assign' => [',apistartticket'],
			'tickets.timer' => [',apitimer'],
		];
		foreach ($map as $code => $fragments) {
			$stmt = $conn->prepare('SELECT id, action FROM rbac_permissions WHERE code = ? LIMIT 1');
			$stmt->execute([$code]);
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);
			if (!$row) {
				continue;
			}
			$action = (string)$row['action'];
			foreach ($fragments as $f) {
				$action = str_replace($f, '', $action);
			}
			if ($action === $row['action']) {
				continue;
			}
			$u = $conn->prepare('UPDATE rbac_permissions SET action = ? WHERE id = ?');
			$u->execute([$action, $row['id']]);
		}
	}
}
