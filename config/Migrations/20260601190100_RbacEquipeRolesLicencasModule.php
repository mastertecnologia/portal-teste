<?php
use Migrations\AbstractMigration;

/**
 * Vincula licencas.* aos papéis operacionais (homologação/produção).
 * cofre.secret apenas super_admin e admin_equipe.
 */
class RbacEquipeRolesLicencasModule extends AbstractMigration {

	/** @var string[] */
	protected $_rolesViewManage = ['super_admin', 'admin_equipe', 'operacao'];

	/** @var string[] */
	protected $_rolesCofreSecret = ['super_admin', 'admin_equipe'];

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('rbac_roles_permissions') || !$this->hasTable('rbac_roles') || !$this->hasTable('rbac_permissions')) {
			return;
		}
		$this->linkRolesPerms($this->_rolesViewManage, [
			'licencas.view',
			'licencas.manage',
			'licencas.cofre.view',
		]);
		$this->linkRolesPerms($this->_rolesCofreSecret, ['licencas.cofre.secret']);
	}

	/**
	 * @param string[] $roleSlugs
	 * @param string[] $permCodes
	 */
	protected function linkRolesPerms(array $roleSlugs, array $permCodes): void {
		$slugs = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $roleSlugs)) . "'";
		$codes = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $permCodes)) . "'";
		$this->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) '
			. 'SELECT r.id, p.id FROM rbac_roles r '
			. 'CROSS JOIN rbac_permissions p '
			. "WHERE r.slug IN ({$slugs}) AND p.code IN ({$codes}) "
			. 'AND NOT EXISTS (SELECT 1 FROM rbac_roles_permissions x WHERE x.role_id = r.id AND x.permission_id = p.id)'
		);
	}

	public function down() {
		// Aditivo.
	}
}
