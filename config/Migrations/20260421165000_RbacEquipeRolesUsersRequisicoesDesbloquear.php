<?php
use Migrations\AbstractMigration;

/**
 * Requisições de acesso (bloqueados) e desbloquear — após novas linhas no catálogo.
 */
class RbacEquipeRolesUsersRequisicoesDesbloquear extends AbstractMigration {

	/** @var string[] */
	protected $_roleSlugsRequisicoes = ['super_admin', 'admin_equipe', 'operacao', 'financeiro', 'leitura'];

	/** @var string[] */
	protected $_roleSlugsDesbloquear = ['super_admin', 'admin_equipe'];

	/** @var string */
	protected $_codeRequisicoes = 'users.requisicoes_acesso';

	/** @var string */
	protected $_codeDesbloquear = 'users.desbloquear';

	public function up() {
		if (!$this->hasTable('rbac_roles_permissions') || !$this->hasTable('rbac_roles') || !$this->hasTable('rbac_permissions')) {
			return;
		}
		$this->_grantToRoles($this->_roleSlugsRequisicoes, [$this->_codeRequisicoes]);
		$this->_grantToRoles($this->_roleSlugsDesbloquear, [$this->_codeDesbloquear]);
	}

	public function down() {
		if (!$this->hasTable('rbac_roles_permissions') || !$this->hasTable('rbac_roles') || !$this->hasTable('rbac_permissions')) {
			return;
		}
		$this->_revokeFromRoles($this->_roleSlugsRequisicoes, [$this->_codeRequisicoes]);
		$this->_revokeFromRoles($this->_roleSlugsDesbloquear, [$this->_codeDesbloquear]);
	}

	/** @param string[] $slugs @param string[] $codes */
	protected function _grantToRoles(array $slugs, array $codes) {
		$slugsSql = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $slugs)) . "'";
		$codesSql = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $codes)) . "'";
		$this->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) ' .
			'SELECT r.id, p.id FROM rbac_roles r ' .
			'CROSS JOIN rbac_permissions p ' .
			"WHERE r.slug IN ({$slugsSql}) AND p.code IN ({$codesSql}) " .
			'AND NOT EXISTS (SELECT 1 FROM rbac_roles_permissions x WHERE x.role_id = r.id AND x.permission_id = p.id)'
		);
	}

	/** @param string[] $slugs @param string[] $codes */
	protected function _revokeFromRoles(array $slugs, array $codes) {
		$slugsSql = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $slugs)) . "'";
		$codesSql = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $codes)) . "'";
		$this->execute(
			'DELETE FROM rbac_roles_permissions WHERE EXISTS (' .
			'SELECT 1 FROM rbac_roles r INNER JOIN rbac_permissions p ON p.id = rbac_roles_permissions.permission_id ' .
			"WHERE r.slug IN ({$slugsSql}) AND p.code IN ({$codesSql}) AND rbac_roles_permissions.role_id = r.id)"
		);
	}
}
