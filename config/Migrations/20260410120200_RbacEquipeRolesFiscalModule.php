<?php
use Migrations\AbstractMigration;

/**
 * Fase 1 (homologação): vincula permissões fiscal.* aos papéis operacionais.
 * Exclui `leitura` (só leitura não recebe emissão/config fiscal por defeito — ajustar na matriz RBAC se necessário).
 * Requer rbac_permissions com codes fiscal.* (migration RbacFiscalModulePermissions ou sync do registry).
 */
class RbacEquipeRolesFiscalModule extends AbstractMigration {

	/** @var string[] */
	protected $_roleSlugs = ['super_admin', 'admin_equipe', 'operacao', 'financeiro'];

	/** @var string[] */
	protected $_permCodes = [
		'fiscal.dashboard',
		'fiscal.notas',
		'fiscal.notas_entrada',
		'fiscal.certificados',
		'fiscal.config',
		'fiscal.relatorios',
	];

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('rbac_roles_permissions') || !$this->hasTable('rbac_roles') || !$this->hasTable('rbac_permissions')) {
			return;
		}
		$slugs = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $this->_roleSlugs)) . "'";
		$codes = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $this->_permCodes)) . "'";
		$this->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) ' .
			'SELECT r.id, p.id FROM rbac_roles r ' .
			'CROSS JOIN rbac_permissions p ' .
			"WHERE r.slug IN ({$slugs}) AND p.code IN ({$codes}) " .
			'AND NOT EXISTS (SELECT 1 FROM rbac_roles_permissions x WHERE x.role_id = r.id AND x.permission_id = p.id)'
		);
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('rbac_roles_permissions') || !$this->hasTable('rbac_roles') || !$this->hasTable('rbac_permissions')) {
			return;
		}
		$slugs = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $this->_roleSlugs)) . "'";
		$codes = "'" . implode("','", array_map(static function ($s) {
			return str_replace("'", "''", $s);
		}, $this->_permCodes)) . "'";
		$this->execute(
			'DELETE FROM rbac_roles_permissions WHERE EXISTS (' .
			'SELECT 1 FROM rbac_roles r INNER JOIN rbac_permissions p ON p.id = rbac_roles_permissions.permission_id ' .
			"WHERE r.slug IN ({$slugs}) AND p.code IN ({$codes}) AND rbac_roles_permissions.role_id = r.id)"
		);
	}
}
