<?php
use Migrations\AbstractMigration;

/**
 * Portal cliente: papel «cliente_portal», permissões no papel e vínculo aos usuários com permissaoacesso.
 *
 * Requer tabelas rbac_* (migration RbacPermissionsFoundation). Permissões devem existir em rbac_permissions
 * (rode em Permissões → Sincronizar catálogo antes, ou execute a migration outra vez após o sync).
 *
 * Ambiente: PostgreSQL (alinhado às migrations recentes do projeto).
 */
class ClientPortalRbacPapel extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('rbac_roles') || !$this->hasTable('rbac_users_roles')) {
			return;
		}

		$this->execute(<<<'SQL'
INSERT INTO rbac_roles (slug, name, description, is_system, active, sort_order, created, modified)
SELECT 'cliente_portal', 'Cliente portal', 'Usuário externo — portal comercial e chamados (ABAC por cliente).', TRUE, TRUE, 60, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_roles WHERE slug = 'cliente_portal')
SQL
		);

		if ($this->hasTable('rbac_roles_permissions') && $this->hasTable('rbac_permissions')) {
			$this->execute(<<<'SQL'
INSERT INTO rbac_roles_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM rbac_roles r
JOIN rbac_permissions p ON p.code IN (
	'portal.cliente_dashboard',
	'dashboard.view',
	'users.profile',
	'users.password',
	'users.twofactor',
	'users.twofactor_off',
	'orcamentos.portal_cliente',
	'orcamentos.solicitar',
	'tickets.portal_cliente',
	'clientes.portal_edit'
)
WHERE r.slug = 'cliente_portal'
ON CONFLICT DO NOTHING
SQL
			);
		}

		$paCond = 'u.permissaoacesso IS TRUE';
		$col = $this->fetchRow(
			"SELECT data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'permissaoacesso' LIMIT 1"
		);
		if (!empty($col['data_type']) && stripos((string)$col['data_type'], 'int') !== false) {
			$paCond = 'COALESCE(CAST(u.permissaoacesso AS INTEGER), 0) = 1';
		}

		$this->execute(sprintf(
			<<<'SQL'
INSERT INTO rbac_users_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
CROSS JOIN rbac_roles r
WHERE u.role = 1
  AND (%s)
  AND r.slug = 'cliente_portal'
  AND NOT EXISTS (SELECT 1 FROM rbac_users_roles x WHERE x.user_id = u.id)
ON CONFLICT DO NOTHING
SQL
			,
			$paCond
		));
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('rbac_roles')) {
			return;
		}
		$row = $this->fetchRow("SELECT id FROM rbac_roles WHERE slug = 'cliente_portal' LIMIT 1");
		if (empty($row['id'])) {
			return;
		}
		$roleId = (int)$row['id'];
		$this->execute(sprintf('DELETE FROM rbac_users_roles WHERE role_id = %d', $roleId));
		if ($this->hasTable('rbac_roles_permissions')) {
			$this->execute(sprintf('DELETE FROM rbac_roles_permissions WHERE role_id = %d', $roleId));
		}
	}
}
