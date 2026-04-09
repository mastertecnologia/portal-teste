<?php
use Migrations\AbstractMigration;

/**
 * AJAX / POST sensíveis em Users: verificasenha, verificadadoscliente, permissaoacesso (catálogo RBAC).
 */
class RbacUsersVerificaPermissaoPortal extends AbstractMigration {

	/** @var string[] */
	protected $_slugSenha = ['super_admin', 'admin_equipe'];

	/** @var string[] */
	protected $_slugDadosCliente = ['super_admin', 'admin_equipe', 'operacao', 'financeiro', 'cliente_portal'];

	/** @var string[] */
	protected $_slugPermissaoPortal = ['super_admin', 'admin_equipe', 'operacao', 'financeiro'];

	public function up() {
		if (!$this->hasTable('rbac_roles_permissions') || !$this->hasTable('rbac_roles') || !$this->hasTable('rbac_permissions')) {
			return;
		}
		$this->_grant($this->_slugSenha, ['users.verificasenha']);
		$this->_grant($this->_slugDadosCliente, ['users.verificadadoscliente']);
		$this->_grant($this->_slugPermissaoPortal, ['users.permissao_portal_cliente']);
	}

	public function down() {
		if (!$this->hasTable('rbac_roles_permissions') || !$this->hasTable('rbac_roles') || !$this->hasTable('rbac_permissions')) {
			return;
		}
		$this->_revoke($this->_slugSenha, ['users.verificasenha']);
		$this->_revoke($this->_slugDadosCliente, ['users.verificadadoscliente']);
		$this->_revoke($this->_slugPermissaoPortal, ['users.permissao_portal_cliente']);
	}

	/** @param string[] $slugs @param string[] $codes */
	protected function _grant(array $slugs, array $codes) {
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
	protected function _revoke(array $slugs, array $codes) {
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
