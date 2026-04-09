<?php
use Migrations\AbstractMigration;

/**
 * Orcamentos::catalogoSugestoes — alinhar rbac_permissions ao catálogo (remoção da whitelist RBAC).
 */
class RbacOrcamentosCatalogoSugestoesActionsPatch extends AbstractMigration {

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
		$this->_appendActions('orcamentos.solicitar', 'catalogosugestoes');
		$this->_appendActions('orcamentos.portal.view', 'catalogosugestoes');
		$this->_appendActions('orcamentos.view', 'catalogosugestoes');
	}

	public function down() {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$conn = $this->getAdapter()->getConnection();
		foreach (['orcamentos.solicitar', 'orcamentos.portal.view', 'orcamentos.view'] as $code) {
			$stmt = $conn->prepare('SELECT id, action FROM rbac_permissions WHERE code = ? LIMIT 1');
			$stmt->execute([$code]);
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);
			if (!$row) {
				continue;
			}
			$action = str_replace(',catalogosugestoes', '', (string)$row['action']);
			$action = str_replace('catalogosugestoes,', '', $action);
			$action = str_replace('catalogosugestoes', '', $action);
			if ($action === $row['action']) {
				continue;
			}
			$u = $conn->prepare('UPDATE rbac_permissions SET action = ? WHERE id = ?');
			$u->execute([$action, $row['id']]);
		}
	}
}
