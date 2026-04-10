<?php
namespace App\Utility;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Utilitário para checagem RBAC/ABAC (uso futuro nos controllers).
 *
 * Hoje o portal usa principalmente users.admin + users.role.
 * Quando rbac_users_roles e rbac_roles_permissions estiverem populados,
 * um Component pode chamar matchPermission() após carregar os códigos do usuário.
 */
class RbacChecker {

	const PERM_ORCAMENTOS_SOLICITAR = 'orcamentos.solicitar';

	/**
	 * Cliente (role=1): pode usar solicitar orçamento e API de catálogo.
	 * — Sem tabelas RBAC: mantém legado (só permissaoacesso).
	 * — Permissão orcamentos.solicitar ainda não existe na base: legado.
	 * — Com RBAC ativo e permissão cadastrada: o usuário deve ter pelo menos um papel e
	 *   uma permissão entre orcamentos.solicitar ou orcamentos.portal_cliente (catálogo amplo).
	 */
	public static function clientePodeSolicitarOrcamento($userId, $permissaoAcesso) {
		if ($permissaoAcesso === null || $permissaoAcesso === false) {
			return false;
		}
		if (is_string($permissaoAcesso)) {
			$permissaoAcesso = trim($permissaoAcesso);
		}
		if ($permissaoAcesso === '' || $permissaoAcesso === 0 || $permissaoAcesso === '0') {
			return false;
		}
		$userId = (int)$userId;
		if ($userId <= 0) {
			return false;
		}
		try {
			$conn = TableRegistry::get('RbacPermissions')->getConnection();
			$tables = $conn->getSchemaCollection()->listTables();
			if (!in_array('rbac_permissions', $tables, true)
				|| !in_array('rbac_roles_permissions', $tables, true)
				|| !in_array('rbac_users_roles', $tables, true)) {
				return true;
			}
			$perm = TableRegistry::get('RbacPermissions')->find()
				->select(['id'])
				->where(['code' => self::PERM_ORCAMENTOS_SOLICITAR])
				->first();
			if ($perm === null) {
				return true;
			}
			$roleIds = TableRegistry::get('RbacUsersRoles')->find()
				->select(['role_id'])
				->where(['user_id' => $userId])
				->extract('role_id')
				->toList();
			$roleIds = array_values(array_unique(array_map('intval', $roleIds)));
			if ($roleIds === []) {
				return false;
			}
			$permPortal = TableRegistry::get('RbacPermissions')->find()
				->select(['id'])
				->where(['code' => 'orcamentos.portal_cliente'])
				->first();
			$permIds = array_values(array_filter([
				(int)$perm->id,
				$permPortal ? (int)$permPortal->id : 0,
			], static function ($v) {
				return $v > 0;
			}));
			if ($permIds === []) {
				return false;
			}
			$n = TableRegistry::get('RbacRolesPermissions')->find()
				->where(['role_id IN' => $roleIds, 'permission_id IN' => $permIds])
				->count();

			return $n > 0;
		} catch (\Throwable $e) {
			return true;
		}
	}

	/**
	 * Utilizador tem o código de permissão RBAC (papéis diretos + grupos, com expand_legacy_aliases quando ativo).
	 * Sem tabelas ou sem papéis RBAC: false. Não aplica bypass de admin legado (o chamador decide).
	 *
	 * @param int $userId
	 * @param string $code ex.: config.manage
	 */
	public static function userHasPermissionCode($userId, $code) {
		$userId = (int)$userId;
		$code = trim((string)$code);
		if ($userId <= 0 || $code === '') {
			return false;
		}
		try {
			$permissionsTable = TableRegistry::get('RbacPermissions');
			$conn = $permissionsTable->getConnection();
			$tables = $conn->getSchemaCollection()->listTables();
			if (!in_array('rbac_permissions', $tables, true)
				|| !in_array('rbac_roles_permissions', $tables, true)
				|| !in_array('rbac_users_roles', $tables, true)) {
				return false;
			}
			$roleIds = RbacUserRolesResolver::effectiveRoleIds($userId);
			if ($roleIds === []) {
				return false;
			}
			$permIds = TableRegistry::get('RbacRolesPermissions')->find()
				->select(['permission_id'])
				->where(['role_id IN' => $roleIds])
				->extract('permission_id')
				->toList();
			$permIds = array_values(array_unique(array_map('intval', $permIds)));
			if ($permIds === []) {
				return false;
			}
			$cfg = Configure::read('Rbac');
			$expand = !is_array($cfg) || !array_key_exists('expand_legacy_aliases', $cfg) || $cfg['expand_legacy_aliases'];
			if ($expand) {
				$permIds = RbacPermissionResolver::expandPermissionIds($permIds);
			}
			if ($permIds === []) {
				return false;
			}
			$codes = $permissionsTable->find()
				->select(['code'])
				->where(['id IN' => $permIds])
				->extract('code')
				->toList();
			$set = array_flip(array_values(array_unique(array_filter(array_map('strval', $codes)))));

			return isset($set[$code]);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Tabelas mínimas RBAC presentes (permissions, roles_permissions, users_roles).
	 */
	public static function rbacCoreTablesExist(): bool {
		try {
			$permissionsTable = TableRegistry::get('RbacPermissions');
			$conn = $permissionsTable->getConnection();
			$tables = $conn->getSchemaCollection()->listTables();

			return in_array('rbac_permissions', $tables, true)
				&& in_array('rbac_roles_permissions', $tables, true)
				&& in_array('rbac_users_roles', $tables, true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Utilizador com modo RBAC ativo (não-off), tabelas presentes e pelo menos um papel efetivo.
	 */
	public static function utilizadorComPapeisRbacAtivos(int $userId): bool {
		if ($userId <= 0) {
			return false;
		}
		$cfg = Configure::read('Rbac');
		if (!is_array($cfg) || ($cfg['mode'] ?? 'off') === 'off') {
			return false;
		}
		if (!self::rbacCoreTablesExist()) {
			return false;
		}

		return RbacUserRolesResolver::effectiveRoleIds($userId) !== [];
	}

	/**
	 * Criar rascunho de entrada a partir do DF-e: exige fiscal.notas_entrada quando o utilizador já está sob RBAC com papéis.
	 * Caso contrário mantém comportamento legado/híbrido (sem papéis RBAC).
	 */
	public static function podeImportarDfeParaRascunhoEntrada(int $userId): bool {
		if (!self::utilizadorComPapeisRbacAtivos($userId)) {
			return true;
		}

		return self::userHasPermissionCode($userId, 'fiscal.notas_entrada');
	}

	/**
	 * Atalho do hub Config (sidebar + ConfigController): equipe admin com menu_filter_config
	 * exige config.manage salvo híbrido (sem papéis RBAC ainda) ou filtro desligado.
	 *
	 * @param mixed $admin truthy users.admin
	 * @param mixed $role users.role (0 = equipe)
	 * @param mixed $userId users.id
	 */
	public static function shouldShowConfigAdminHub($admin, $role, $userId): bool {
		if (empty($admin) || (int)$role !== 0) {
			return false;
		}
		$rb = Configure::read('Rbac');
		if (is_array($rb) && !empty($rb['bypass_legacy_super'])) {
			return true;
		}
		$strictMenu = is_array($rb) && !empty($rb['menu_filter_config']);
		if (!$strictMenu) {
			return true;
		}
		$uid = (int)$userId;
		if ($uid <= 0) {
			return false;
		}
		if (self::userHasPermissionCode($uid, 'config.manage')) {
			return true;
		}

		try {
			return RbacUserRolesResolver::effectiveRoleIds($uid) === [];
		} catch (\Throwable $e) {
			return true;
		}
	}

	/**
	 * Atalho direto ao catálogo Permissões (sidebar): equipe não-admin com algum código delegado em PermissoesController.
	 * Admins usam o hub Config; este ícone evita utilizadores só com RBAC granular ficarem sem entrada.
	 *
	 * @param mixed $admin truthy users.admin
	 * @param mixed $role users.role (0 = equipe)
	 * @param mixed $userId users.id
	 */
	public static function shouldShowPermissoesRbacShortcut($admin, $role, $userId): bool {
		if ((int)$role !== 0) {
			return false;
		}
		$uid = (int)$userId;
		if ($uid <= 0) {
			return false;
		}
		if (!empty($admin)) {
			return false;
		}
		$codes = [
			'permissoes.catalog.view',
			'permissoes.registry.sync',
			'permissoes.matrix.view',
			'permissoes.matrix.grant_super',
			'permissoes.users.list',
			'permissoes.users.assign_roles',
			'permissoes.users.effective',
			'permissoes.policies.manage',
			'permissoes.fields.manage',
			'permissoes.roles.edit',
			'permissoes.audit.view',
			'permissoes.groups.manage',
			'usuarios.roles.assign',
			'usuarios.groups.assign',
		];
		foreach ($codes as $code) {
			if (self::userHasPermissionCode($uid, $code)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Bloco da sidebar (equipe): com menu_filter_sidebar true, exige pelo menos um dos códigos OU híbrido sem papéis RBAC.
	 * Equipe com users.admin + bypass_legacy_super: menu completo. Utilizadores não equipe (role≠0) — sempre true.
	 *
	 * @param string|string[] $codes um código ou lista em OR
	 */
	public static function shouldShowSidebarGate($admin, $role, $userId, $codes): bool {
		if ((int)$role !== 0) {
			return true;
		}
		$rb = Configure::read('Rbac');
		// Equipe com users.admin + bypass_legacy_super: menu completo (paridade com RbacComponent).
		if (is_array($rb) && !empty($rb['bypass_legacy_super']) && !empty($admin)) {
			return true;
		}
		$list = is_array($codes) ? $codes : [$codes];
		$list = array_values(array_filter(array_map('trim', array_map('strval', $list)), static function ($c) {
			return $c !== '';
		}));
		if ($list === []) {
			return true;
		}
		if (!is_array($rb) || empty($rb['menu_filter_sidebar'])) {
			return true;
		}
		$uid = (int)$userId;
		if ($uid <= 0) {
			return false;
		}
		foreach ($list as $code) {
			if (self::userHasPermissionCode($uid, $code)) {
				return true;
			}
		}

		try {
			return RbacUserRolesResolver::effectiveRoleIds($uid) === [];
		} catch (\Throwable $e) {
			return true;
		}
	}

	/**
	 * Mapa chave (menu_sidebar_gates) => visível, para a view. Vazio se filtro lateral desligado ou gates não definidos.
	 *
	 * @return array<string,bool>
	 */
	public static function buildSidebarMenuGates($admin, $role, $userId): array {
		$out = [];
		$rb = Configure::read('Rbac');
		if (!is_array($rb) || empty($rb['menu_sidebar_gates']) || !is_array($rb['menu_sidebar_gates'])) {
			return $out;
		}
		foreach ($rb['menu_sidebar_gates'] as $gateKey => $codes) {
			$key = trim((string)$gateKey);
			if ($key === '') {
				continue;
			}
			try {
				$out[$key] = self::shouldShowSidebarGate($admin, $role, $userId, $codes);
			} catch (\Throwable $e) {
				$out[$key] = true;
			}
		}

		return $out;
	}

	/**
	 * Regras em rbac_field_permissions para uma chave de recurso (ex.: Clientes.field.valor_mensal).
	 * null = sem override (herdar visibilidade/edição da tela ou do controller).
	 * array{visible:bool, editable:bool} = forçar na UI (templates devem consultar antes de renderizar campo).
	 *
	 * Ordem: sort_order DESC, id DESC; primeiro modo aplicável vence. Linhas access_mode=inherit são ignoradas.
	 * hidden: sem a permissão ligada → oculto; com permissão → null (herda edição da rota).
	 * readonly: sem permissão → oculto; com permissão → visível e não editável.
	 *
	 * @param int $userId
	 * @param string $resourceKey
	 * @return array{visible:bool, editable:bool}|null
	 */
	public static function resourceFieldAccess($userId, string $resourceKey) {
		$userId = (int)$userId;
		$resourceKey = trim($resourceKey);
		if ($userId <= 0 || $resourceKey === '') {
			return null;
		}
		static $memo = [];
		$mk = $userId . "\0" . $resourceKey;
		if (array_key_exists($mk, $memo)) {
			return $memo[$mk];
		}
		try {
			$conn = TableRegistry::get('RbacPermissions')->getConnection();
			$tables = $conn->getSchemaCollection()->listTables();
			if (!in_array('rbac_field_permissions', $tables, true)) {
				return $memo[$mk] = null;
			}
			$tbl = TableRegistry::get('RbacFieldPermissions');
			$rows = $tbl->find()
				->contain(['RbacPermissions'])
				->where(['resource_key' => $resourceKey, 'active' => true])
				->order(['sort_order' => 'DESC', 'id' => 'DESC'])
				->all();
			foreach ($rows as $row) {
				$mode = (string)$row->access_mode;
				if ($mode === 'inherit') {
					continue;
				}
				$pid = (int)$row->rbac_permission_id;
				if ($pid <= 0 || empty($row->rbac_permission) || $row->rbac_permission->code === null || $row->rbac_permission->code === '') {
					continue;
				}
				$code = (string)$row->rbac_permission->code;
				$has = self::userHasPermissionCode($userId, $code);
				if ($mode === 'hidden') {
					if (!$has) {
						return $memo[$mk] = ['visible' => false, 'editable' => false];
					}

					return $memo[$mk] = null;
				}
				if ($mode === 'readonly') {
					if (!$has) {
						return $memo[$mk] = ['visible' => false, 'editable' => false];
					}

					return $memo[$mk] = ['visible' => true, 'editable' => false];
				}
			}

			return $memo[$mk] = null;
		} catch (\Exception $e) {
			return $memo[$mk] = null;
		}
	}

	/**
	 * Verifica se controller/ação batem com uma linha de permissão.
	 * action: "*" ou vazio = qualquer; várias ações podem vir separadas por vírgula (ex.: "index,view,exportar").
	 */
	public static function matchAction($controller, $action, array $permissionRow) {
		$c = strtolower(isset($permissionRow['controller']) ? $permissionRow['controller'] : '');
		$actionsRaw = strtolower(trim(isset($permissionRow['action']) ? (string)$permissionRow['action'] : '*'));
		$req = strtolower((string)$controller);
		$act = strtolower((string)$action);
		if ($req !== $c) {
			// URLs canónicas /cliente/contratos → PortalContratos; RBAC legado usa PortalAdvancedContracts
			if ($req === 'portalcontratos' && $c === 'portaladvancedcontracts') {
				// ok
			} elseif ($req === 'visitas' && $c === 'agenda' && ($actionsRaw === '' || $actionsRaw === '*')) {
				// Rotas /agenda/* → Visitas; catálogo antigo podia ter controller "Agenda" em agenda.alias
			} else {
				return false;
			}
		}
		if ($actionsRaw === '' || $actionsRaw === '*') {
			return true;
		}
		foreach (preg_split('/\s*,\s*/', $actionsRaw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
			if ($part === '*' || $part === $act) {
				return true;
			}
		}

		return false;
	}
}
