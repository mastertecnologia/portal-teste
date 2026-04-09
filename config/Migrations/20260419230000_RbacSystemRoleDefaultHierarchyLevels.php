<?php
use Migrations\AbstractMigration;

/**
 * hierarchy_level inicial para papéis de sistema (slug) ainda com 0 — alinha anti-escalação.
 * Valores iguais a PermissoesController::_ensureDefaultRoles (novas instalações).
 */
class RbacSystemRoleDefaultHierarchyLevels extends AbstractMigration {

	/** @var array<string,int> slug => hierarchy_level */
	protected $_levels = [
		'super_admin' => 10000,
		'admin_equipe' => 8000,
		'operacao' => 5000,
		'financeiro' => 5000,
		'leitura' => 500,
		'cliente_portal' => 100,
	];

	public function up() {
		if (!$this->hasTable('rbac_roles')) {
			return;
		}
		$conn = $this->getAdapter()->getConnection();
		$stmt = $conn->prepare('UPDATE rbac_roles SET hierarchy_level = ? WHERE slug = ? AND hierarchy_level = 0');
		foreach ($this->_levels as $slug => $lvl) {
			$stmt->execute([(int)$lvl, $slug]);
		}
	}

	public function down() {
		if (!$this->hasTable('rbac_roles')) {
			return;
		}
		$conn = $this->getAdapter()->getConnection();
		$stmt = $conn->prepare('UPDATE rbac_roles SET hierarchy_level = 0 WHERE slug = ? AND hierarchy_level = ?');
		foreach ($this->_levels as $slug => $lvl) {
			$stmt->execute([$slug, (int)$lvl]);
		}
	}
}
