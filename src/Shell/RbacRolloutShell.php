<?php
namespace App\Shell;

use App\Utility\RbacChecker;
use App\Utility\RbacPermissionResolver;
use App\Utility\RbacUserRolesResolver;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * Fase 8 — diagnóstico de rollout RBAC (rbac_users_roles + herdados via grupos Fase 3).
 *
 * Uso:
 *   bin/cake rbac_rollout stats
 *   bin/cake rbac_rollout stats --csv > rollout_stats.csv
 *   bin/cake rbac_rollout stats --include_inactive
 *   bin/cake rbac_rollout unassigned_equipe
 *   bin/cake rbac_rollout unassigned_equipe --limit=50
 *   bin/cake rbac_rollout unassigned_equipe --csv > sem_papel_equipe.csv
 *   bin/cake rbac_rollout unassigned_portal --limit=50
 *   bin/cake rbac_rollout unassigned_portal --csv > sem_papel_portal.csv
 *   bin/cake rbac_rollout assign_equipe --role_slug=operacao
 *   bin/cake rbac_rollout assign_equipe --role_slug=operacao --dry-run
 *   bin/cake rbac_rollout assign_portal --role_slug=cliente_portal
 *   bin/cake rbac_rollout assign_portal --role_slug=cliente_portal --dry-run
 *   bin/cake rbac_rollout audit_recent --limit=30
 *   bin/cake rbac_rollout audit_purge --days=90
 *   bin/cake rbac_rollout audit_purge --days=90 --dry-run
 *   bin/cake rbac_rollout list_roles
 *   bin/cake rbac_rollout list_roles --all
 *   bin/cake rbac_rollout list_roles --csv
 *   bin/cake rbac_rollout user_effective --user_id=42
 *   bin/cake rbac_rollout user_effective --user_id=42 --csv
 *   bin/cake rbac_rollout who_has --code=clientes.view
 *   bin/cake rbac_rollout who_has --code=dashboard.view --filter_role=0 --scan_limit=5000
 *   bin/cake rbac_rollout menu_gates_check
 *   bin/cake rbac_rollout menu_gates_check --strict
 *   bin/cake rbac_rollout role_stats
 *   bin/cake rbac_rollout role_stats --csv
 *   bin/cake rbac_rollout checklist
 *   bin/cake rbac_rollout enforce_readiness
 *   bin/cake rbac_rollout enforce_readiness --strict
 *   bin/cake rbac_rollout enforce_readiness --csv
 *   bin/cake rbac_rollout pre_deploy
 *   bin/cake rbac_rollout report
 *   bin/cake rbac_rollout sync_registry
 */
class RbacRolloutShell extends Shell {

	/**
	 * O dispatcher converte o subcomando com Inflector::camelize (ex.: menu_gates_check → MenuGatesCheck)
	 * e chama esse método; aqui os handlers estão em snake_case. Expõe ambos os formatos.
	 */
	public function hasMethod($name) {
		if (parent::hasMethod($name)) {
			return true;
		}
		$snake = Inflector::underscore($name);
		if ($snake !== $name && parent::hasMethod($snake)) {
			return true;
		}

		return false;
	}

	public function __call($name, $args) {
		$snake = Inflector::underscore($name);
		if ($snake !== $name && parent::hasMethod($snake)) {
			return $this->{$snake}(...$args);
		}

		throw new \BadMethodCallException(sprintf('Unknown method `%s`', $name));
	}

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription('Diagnóstico RBAC: equipe/portal com ou sem papéis (direto e/ou via rbac_user_groups + rbac_group_roles).');
		$parser->addOption('include_inactive', [
			'boolean' => true,
			'default' => false,
			'help' => 'Incluir utilizadores com users.inativo=1 nas contagens/listagem.',
		]);
		$parser->addOption('limit', [
			'short' => 'l',
			'default' => '100',
			'help' => 'Máximo de linhas (unassigned_*, audit_recent, assign_equipe ou assign_portal).',
		]);
		$parser->addOption('role_slug', [
			'default' => '',
			'help' => 'assign_equipe / assign_portal: slug em rbac_roles (ex.: operacao, leitura; portal: cliente_portal).',
		]);
		$parser->addOption('dry-run', [
			'boolean' => true,
			'default' => false,
			'help' => 'assign_equipe / assign_portal: apenas listar utilizadores que receberiam o papel. audit_purge: só contar linhas a apagar.',
		]);
		$parser->addOption('days', [
			'default' => '',
			'help' => 'audit_purge: apagar registos com created anterior a N dias (ou use Rbac.audit_retention_days).',
		]);
		$parser->addOption('csv', [
			'boolean' => true,
			'default' => false,
			'help' => 'stats / unassigned_* / list_roles / role_stats / user_effective / who_has / menu_gates_check / enforce_readiness: CSV quando aplicável.',
		]);
		$parser->addOption('all', [
			'boolean' => true,
			'default' => false,
			'help' => 'list_roles / role_stats: incluir papéis inativos (active=0).',
		]);
		$parser->addOption('user_id', [
			'default' => '',
			'help' => 'user_effective: id em users (obrigatório).',
		]);
		$parser->addOption('full', [
			'boolean' => true,
			'default' => false,
			'help' => 'user_effective: listar até 25000 códigos de permissão (default ~400).',
		]);
		$parser->addOption('code', [
			'default' => '',
			'help' => 'who_has: código canónico em rbac_permissions (ex.: clientes.view).',
		]);
		$parser->addOption('filter_role', [
			'default' => '',
			'help' => 'who_has: restringir users.role — 0 equipe, 1 portal, vazio todos.',
		]);
		$parser->addOption('scan_limit', [
			'default' => '3000',
			'help' => 'who_has: máximo de utilizadores a percorrer (ordem id ASC).',
		]);
		$parser->addOption('strict', [
			'boolean' => true,
			'default' => false,
			'help' => 'menu_gates_check: sair com código 1 se faltar código em rbac_permissions. enforce_readiness: sair com 1 se houver AVISO (enforce+block sem backfill). pre_deploy corre ambos em strict (ignora --csv na invocação interna).',
		]);

		return $parser;
	}

	public function main() {
		$this->out('Comandos: stats | enforce_readiness | pre_deploy | checklist | sync_registry | list_roles | role_stats | user_effective | who_has | menu_gates_check | unassigned_equipe | unassigned_portal | assign_equipe | assign_portal | audit_recent | audit_purge | report');
		$this->out('Ex.: bin/cake rbac_rollout stats');
		$this->out('    bin/cake rbac_rollout stats --csv');
		$this->out('    bin/cake rbac_rollout unassigned_equipe --limit=50');
		$this->out('    bin/cake rbac_rollout unassigned_equipe --csv');
		$this->out('    bin/cake rbac_rollout unassigned_portal --limit=50');
		$this->out('    bin/cake rbac_rollout unassigned_portal --csv');
		$this->out('    bin/cake rbac_rollout assign_equipe --role_slug=operacao --dry-run');
		$this->out('    bin/cake rbac_rollout assign_portal --role_slug=cliente_portal --dry-run');
		$this->out('    bin/cake rbac_rollout list_roles');
		$this->out('    bin/cake rbac_rollout user_effective --user_id=42');
		$this->out('    bin/cake rbac_rollout who_has --code=clientes.view');
		$this->out('    bin/cake rbac_rollout menu_gates_check');
		$this->out('    bin/cake rbac_rollout role_stats');
		$this->out('    bin/cake rbac_rollout audit_recent --limit=30');
		$this->out('    bin/cake rbac_rollout audit_purge --days=90 [--dry-run]');
		$this->out('    bin/cake rbac_rollout checklist');
		$this->out('    bin/cake rbac_rollout enforce_readiness [--strict] [--csv]');
		$this->out('    bin/cake rbac_rollout pre_deploy');
		$this->out('    bin/cake rbac_rollout report');
		$this->out('    bin/cake rbac_rollout sync_registry');
	}

	/**
	 * Linhas do playbook (sem I/O); usado por checklist() e testes.
	 *
	 * @return string[]
	 */
	public static function playbookChecklistLines() {
		return [
			'=== Playbook rollout RBAC (Fase 8 + verificações) ===',
			'',
			'1) Schema',
			'   bin/cake migrations migrate',
			'',
			'2) Catálogo na base',
			'   Permissões → Sincronizar catálogo (após alterar config/permissions_registry.php ou migrations de permissões).',
			'   bin/cake rbac_rollout sync_registry — mesmo efeito (CLI; útil em produção).',
			'',
			'3) Config efectiva + env',
			'   bin/cake rbac_rollout report',
			'',
			'4) Códigos do menu lateral vs rbac_permissions',
			'   bin/cake rbac_rollout menu_gates_check --strict',
			'   bin/cake rbac_rollout pre_deploy — CI: passo 4 strict + enforce_readiness strict (saída legível)',
			'',
			'5) Papéis × permissões (matriz)',
			'   bin/cake rbac_rollout role_stats',
			'   UI: Permissões → Matriz (coluna efetiva por utilizador: ?user_id=N ou filtro no ecrã).',
			'',
			'6) Quem falta papel RBAC',
			'   bin/cake rbac_rollout stats',
			'   bin/cake rbac_rollout unassigned_equipe --csv',
			'   bin/cake rbac_rollout unassigned_portal --csv',
			'',
			'7) Atribuição (piloto; usar slugs de list_roles)',
			'   bin/cake rbac_rollout list_roles',
			'   bin/cake rbac_rollout assign_equipe --role_slug=operacao --dry-run',
			'   bin/cake rbac_rollout assign_equipe --role_slug=leitura --limit=5000 (backfill em massa após validar dry-run)',
			'   bin/cake rbac_rollout assign_portal --role_slug=cliente_portal --dry-run',
			'',
			'8) Validar um utilizador',
			'   bin/cake rbac_rollout user_effective --user_id=N',
			'   bin/cake rbac_rollout who_has --code=modulo.recurso.acao',
			'',
			'9) Piloto warn → enforce',
			'   RBAC_MODE=warn (+ opcional RBAC_LOG_UNASSIGNED_USERS=1); depois RBAC_MODE=enforce.',
			'   bin/cake rbac_rollout enforce_readiness [--strict] [--csv] — antes de RBAC_ENFORCE_BLOCK_WITHOUT_ROLES=1 (CI: --strict / --csv).',
			'   RBAC_ENFORCE_BLOCK_WITHOUT_ROLES=1 só após backfill aceitável (ver enforce_block_without_roles_equipe_only).',
			'',
			'10) Menu por permissão (Fase 6b–6f)',
			'   RBAC_MENU_FILTER_SIDEBAR=1 quando os papéis tiverem os códigos usados em menu_sidebar_gates.',
			'',
			'11) PHPUnit (regressão)',
			'   composer test-rbac ou composer rbac-verify-noninteractive — rbac + rbac-integration (SQLite ORM) + rbac-http (Permissoes, Areas, Empresasusers, Problemas, Feriados, ContratosHoras, Normasempresa, Financeiro, Faturamento, Clientes, Prefaturamento, Bancosenhas, Empresas, Orcamentos, Produtos, Visitas, Ordensservico; bootstrap tests/bootstrap_http.php).',
			'   bin/rbac_verify_noninteractive.ps1 | .sh — idem; RBAC_RUN_PRE_DEPLOY=1 acrescenta bin/cake rbac_rollout pre_deploy (requer PostgreSQL + catálogo sincronizado).',
			'',
			'Checklist manual alargado: docs/TEST_CHECKLIST_RBAC.md',
		];
	}

	/**
	 * Playbook operacional (Fase 8 + verificações); ver também docs/TEST_CHECKLIST_RBAC.md.
	 */
	public function checklist() {
		foreach (static::playbookChecklistLines() as $line) {
			$this->out($line);
		}
	}

	/**
	 * Importa permissões em falta a partir de config/permissions_registry.php (paridade com Permissoes::adminSyncRegistry).
	 */
	public function sync_registry() {
		if (!$this->_rbacTablesOk() || !$this->_rbacRolesTableOk()) {
			$this->err('Tabelas rbac_permissions / rbac_users_roles / rbac_roles indisponíveis.');
			exit(1);
		}
		TableRegistry::get('RbacRoles')->ensureDefaultSystemRoles();
		$result = TableRegistry::get('RbacPermissions')->syncMissingFromRegistry();
		foreach (array_slice($result['errors'], 0, 25) as $errLine) {
			$this->err($errLine);
		}
		$this->out(sprintf('--- sync_registry: %d permissão(ões) nova(s) ---', $result['inserted']));
		if ($result['errors'] !== [] && $result['inserted'] === 0) {
			exit(1);
		}
	}

	/**
	 * Resumo do runtime RBAC (config efectiva): modo, listas, flags de rollout e auditoria.
	 */
	public function report() {
		$rb = Configure::read('Rbac');
		if (!is_array($rb)) {
			$this->out('--- RBAC report ---');
			$this->err('Configure::read("Rbac") não é array — ver config/rbac.php');

			return;
		}
		$this->out('--- RBAC report (config/rbac.php + RBAC_MODE env) ---');
		$this->out('mode: ' . (!empty($rb['mode']) ? (string)$rb['mode'] : 'off'));
		$rm = function_exists('env') ? env('RBAC_MODE') : null;
		$this->out('  env RBAC_MODE: ' . ($rm !== null && trim((string)$rm) !== '' ? trim((string)$rm) : '—'));
		$this->out('bypass_legacy_super: ' . (!empty($rb['bypass_legacy_super']) ? 'true' : 'false'));
		$skip = !empty($rb['skip_action_prefixes']) && is_array($rb['skip_action_prefixes'])
			? implode(', ', $rb['skip_action_prefixes']) : '—';
		$this->out('skip_action_prefixes: ' . $skip);
		$enf = !empty($rb['rbac_api_enforced_actions']) && is_array($rb['rbac_api_enforced_actions'])
			? $rb['rbac_api_enforced_actions'] : [];
		$this->out('rbac_api_enforced_actions: ' . count($enf) . ' entrada(s)');
		foreach ($enf as $line) {
			$this->out('  · ' . (string)$line);
		}
		$wl = !empty($rb['whitelist']) && is_array($rb['whitelist']) ? $rb['whitelist'] : [];
		$this->out('whitelist: ' . count($wl) . ' entrada(s)');
		foreach ($wl as $line) {
			$this->out('  · ' . (string)$line);
		}
		$gates = !empty($rb['menu_sidebar_gates']) && is_array($rb['menu_sidebar_gates']) ? $rb['menu_sidebar_gates'] : [];
		$this->out('menu_sidebar_gates: ' . count($gates) . ' chave(s) (Fase 6b–6f; ativo se menu_filter_sidebar=true)');
		foreach ($gates as $gk => $codes) {
			$codesStr = is_array($codes) ? implode(' |OR| ', $codes) : (string)$codes;
			$this->out(sprintf('  · %s → %s', (string)$gk, $codesStr));
		}
		$this->out('warn_flash: ' . (!empty($rb['warn_flash']) ? 'true' : 'false'));
		$wf = function_exists('env') ? env('RBAC_WARN_FLASH') : null;
		$this->out('  env RBAC_WARN_FLASH: ' . ($wf !== null && trim((string)$wf) !== '' ? trim((string)$wf) : '—'));
		$this->out('expand_legacy_aliases: ' . (!isset($rb['expand_legacy_aliases']) || !empty($rb['expand_legacy_aliases']) ? 'true' : 'false'));
		$this->out('expand_group_roles: ' . (!empty($rb['expand_group_roles']) ? 'true' : 'false'));
		$this->out('log_unassigned_rbac_users: ' . (!empty($rb['log_unassigned_rbac_users']) ? 'true' : 'false'));
		$elu = function_exists('env') ? env('RBAC_LOG_UNASSIGNED_USERS') : null;
		$this->out('  env RBAC_LOG_UNASSIGNED_USERS: ' . ($elu !== null && trim((string)$elu) !== '' ? trim((string)$elu) : '—'));
		$this->out('enforce_block_without_roles: ' . (!empty($rb['enforce_block_without_roles']) ? 'true' : 'false'));
		$efb = function_exists('env') ? env('RBAC_ENFORCE_BLOCK_WITHOUT_ROLES') : null;
		$this->out('  env RBAC_ENFORCE_BLOCK_WITHOUT_ROLES: ' . ($efb !== null && trim((string)$efb) !== '' ? trim((string)$efb) : '—'));
		$eqOnly = !array_key_exists('enforce_block_without_roles_equipe_only', $rb) || !empty($rb['enforce_block_without_roles_equipe_only']);
		$this->out('enforce_block_without_roles_equipe_only: ' . ($eqOnly ? 'true' : 'false'));
		$this->out('evaluate_permission_policies: ' . (!empty($rb['evaluate_permission_policies']) ? 'true' : 'false'));
		$epol = function_exists('env') ? env('RBAC_EVALUATE_POLICIES') : null;
		$this->out('  env RBAC_EVALUATE_POLICIES: ' . ($epol !== null && trim((string)$epol) !== '' ? trim((string)$epol) : '—'));
		$this->out('menu_filter_config: ' . (!empty($rb['menu_filter_config']) ? 'true' : 'false'));
		$efc = function_exists('env') ? env('RBAC_MENU_FILTER_CONFIG') : null;
		$this->out('  env RBAC_MENU_FILTER_CONFIG: ' . ($efc !== null && trim((string)$efc) !== '' ? trim((string)$efc) : '—'));
		$this->out('menu_filter_sidebar: ' . (!empty($rb['menu_filter_sidebar']) ? 'true' : 'false'));
		$efs = function_exists('env') ? env('RBAC_MENU_FILTER_SIDEBAR') : null;
		$this->out('  env RBAC_MENU_FILTER_SIDEBAR: ' . ($efs !== null && trim((string)$efs) !== '' ? trim((string)$efs) : '—'));
		$aud = array_key_exists('audit_decisions_db', $rb) ? $rb['audit_decisions_db'] : false;
		$this->out('audit_decisions_db: ' . var_export($aud, true));
		$ead = function_exists('env') ? env('RBAC_AUDIT_DECISIONS_DB') : null;
		$this->out('  env RBAC_AUDIT_DECISIONS_DB: ' . ($ead !== null && trim((string)$ead) !== '' ? trim((string)$ead) : '—'));
		$ret = array_key_exists('audit_retention_days', $rb) ? $rb['audit_retention_days'] : null;
		$this->out('audit_retention_days: ' . ($ret === null ? 'null' : var_export($ret, true)));
		$this->out('Consistência menu: rbac_rollout menu_gates_check [--strict] [--csv]');
		$this->out('Papéis × permissões: rbac_rollout role_stats [--csv] [--all]');
		$this->out('Antes de enforce + block sem papéis: rbac_rollout enforce_readiness [--strict] [--csv]');
		$this->out('CI (menu strict + enforce readiness strict): rbac_rollout pre_deploy');
		$this->out('Playbook: rbac_rollout checklist');
	}

	public function audit_recent() {
		if (!$this->_auditTableExists()) {
			$this->err('Tabela rbac_audit_authorizations ausente (migration Fase 3).');

			return;
		}
		$limit = isset($this->params['limit']) ? (int)$this->params['limit'] : 50;
		if ($limit < 1) {
			$limit = 50;
		}
		if ($limit > 500) {
			$limit = 500;
		}
		$rows = TableRegistry::get('RbacAuditAuthorizations')->find()
			->order(['id' => 'DESC'])
			->limit($limit)
			->all();
		$this->out(sprintf('--- rbac_audit_authorizations (últimas %d) ---', $limit));
		if ($rows->count() === 0) {
			$this->out('Nenhum registo. Ative Rbac.audit_decisions_db em config/rbac.php.');

			return;
		}
		foreach ($rows as $r) {
			$g = !empty($r->granted) ? 'ALLOW' : 'DENY';
			$this->out(sprintf(
				'id=%d %s user=%d %s::%s code=%s ctx=%s',
				(int)$r->id,
				$g,
				(int)$r->user_id,
				(string)$r->controller,
				(string)$r->action,
				$r->permission_code !== null ? (string)$r->permission_code : '—',
				$r->context_json !== null ? substr((string)$r->context_json, 0, 120) : '—'
			));
		}
	}

	/**
	 * Apaga linhas em rbac_audit_authorizations mais antigas que N dias (coluna created).
	 * Use --dry-run para apenas contar. Dias: --days ou Rbac.audit_retention_days em config.
	 */
	public function audit_purge() {
		if (!$this->_auditTableExists()) {
			$this->err('Tabela rbac_audit_authorizations ausente (migration Fase 3).');

			return;
		}
		$daysOpt = isset($this->params['days']) ? trim((string)$this->params['days']) : '';
		$cfg = Configure::read('Rbac.audit_retention_days');
		$days = 0;
		if ($daysOpt !== '') {
			$days = (int)$daysOpt;
		} elseif (is_numeric($cfg) && (int)$cfg > 0) {
			$days = (int)$cfg;
		}
		if ($days < 1) {
			$this->err('audit_purge: indique --days=N (N>=1) ou defina Rbac.audit_retention_days em config/rbac.php.');

			return;
		}
		$cutoff = FrozenTime::now()->subDays($days);
		$table = TableRegistry::get('RbacAuditAuthorizations');
		$count = $table->find()
			->where(['created <' => $cutoff])
			->count();
		$dry = !empty($this->params['dry-run']);
		$this->out(sprintf(
			'--- audit_purge: created < %s (%d dia(s) atrás) — %d linha(s) ---',
			$cutoff->format('Y-m-d H:i:s'),
			$days,
			$count
		));
		if ($count === 0) {
			return;
		}
		if ($dry) {
			$this->out('Dry-run: nada foi apagado.');

			return;
		}
		$deleted = $table->deleteAll(['created <' => $cutoff]);
		$this->out(sprintf('Apagadas %d linha(s).', (int)$deleted));
	}

	/**
	 * Contagens por users.role (0 equipe, 1 portal): total, com papel RBAC efetivo, sem.
	 * Pré-condição: _rbacTablesOk() true.
	 *
	 * @param bool $includeInactive se false, aplica _activeUserConditions()
	 * @return array<int, array{total:int, with:int, without:int}>
	 */
	protected function _rolloutStatsByRole($includeInactive) {
		$users = TableRegistry::get('Users');
		$assignedIds = $this->_effectiveRbacUserIds();
		$byRole = [];
		foreach ([0, 1] as $role) {
			$q = $users->find()->where(['role' => $role]);
			if (!$includeInactive) {
				$q->where($this->_activeUserConditions());
			}
			$total = $q->count();
			$with = 0;
			if ($assignedIds !== []) {
				$qw = $users->find()->where(['role' => $role, 'id IN' => $assignedIds]);
				if (!$includeInactive) {
					$qw->where($this->_activeUserConditions());
				}
				$with = $qw->count();
			}
			$without = max(0, $total - $with);
			$byRole[$role] = ['total' => $total, 'with' => $with, 'without' => $without];
		}

		return $byRole;
	}

	public function stats() {
		if (!$this->_rbacTablesOk()) {
			$this->err('Tabelas rbac_permissions / rbac_users_roles indisponíveis.');

			return;
		}

		$includeInactive = !empty($this->params['include_inactive']);
		$byRole = $this->_rolloutStatsByRole($includeInactive);

		if (!empty($this->params['csv'])) {
			$this->out($this->_csvLine([
				'include_inactive',
				'equipe_total',
				'equipe_com_papel',
				'equipe_sem_papel',
				'portal_total',
				'portal_com_papel',
				'portal_sem_papel',
			]));
			$this->out($this->_csvLine([
				$includeInactive ? '1' : '0',
				(string)$byRole[0]['total'],
				(string)$byRole[0]['with'],
				(string)$byRole[0]['without'],
				(string)$byRole[1]['total'],
				(string)$byRole[1]['with'],
				(string)$byRole[1]['without'],
			]));

			return;
		}

		$this->out('--- RBAC rollout (stats) ---');
		if ($includeInactive) {
			$this->out('Filtro users.inativo: nenhum (inclui inativos).');
		} else {
			$this->out('Filtro users.inativo: apenas ativos (0 ou NULL).');
		}

		foreach ([0 => 'equipe (role=0)', 1 => 'portal (role=1)'] as $role => $label) {
			$s = $byRole[$role];
			$this->out(sprintf(
				'%s: total=%d | com papéis RBAC (direto ou grupo)=%d | sem=%d',
				$label,
				$s['total'],
				$s['with'],
				$s['without']
			));
		}
	}

	/**
	 * Antes de RBAC_MODE=enforce com enforce_block_without_roles: resume config e avisa se ainda há utilizadores sem papel.
	 * Com --strict, exit(1) se config/tabelas inválidas ou se emitir AVISO de bloqueio sem backfill (como menu_gates_check --strict).
	 * Com --csv, uma linha de cabeçalho + uma de dados (readiness_ok, contagens; útil em CI).
	 */
	public function enforce_readiness() {
		$strict = !empty($this->params['strict']);
		$useCsv = !empty($this->params['csv']);
		$rb = Configure::read('Rbac');
		$configOk = is_array($rb);
		$mode = '';
		$block = false;
		$equipeOnly = true;
		if ($configOk) {
			$mode = !empty($rb['mode']) ? (string)$rb['mode'] : 'off';
			$block = !empty($rb['enforce_block_without_roles']);
			$equipeOnly = !array_key_exists('enforce_block_without_roles_equipe_only', $rb)
				|| !empty($rb['enforce_block_without_roles_equipe_only']);
		}
		$tablesOk = $configOk && $this->_rbacTablesOk();
		$byRole = null;
		$readinessFailed = false;
		if ($configOk && $tablesOk) {
			$byRole = $this->_rolloutStatsByRole(false);
			if ($mode === 'enforce' && $block && $equipeOnly && $byRole[0]['without'] > 0) {
				$readinessFailed = true;
			}
			if ($mode === 'enforce' && $block && !$equipeOnly
				&& ($byRole[0]['without'] > 0 || $byRole[1]['without'] > 0)) {
				$readinessFailed = true;
			}
		}
		$readinessOk = $configOk && $tablesOk && !$readinessFailed;

		if ($useCsv) {
			$this->out($this->_csvLine([
				'readiness_ok',
				'mode',
				'enforce_block_without_roles',
				'enforce_equipe_only',
				'equipe_sem_papel_ativos',
				'portal_sem_papel_ativos',
				'config_ok',
				'tables_ok',
			]));
			$eq = $byRole !== null ? (string)(int)$byRole[0]['without'] : '';
			$po = $byRole !== null ? (string)(int)$byRole[1]['without'] : '';
			$this->out($this->_csvLine([
				$readinessOk ? '1' : '0',
				$configOk ? $mode : '',
				$configOk ? ($block ? '1' : '0') : '',
				$configOk ? ($equipeOnly ? '1' : '0') : '',
				$eq,
				$po,
				$configOk ? '1' : '0',
				$tablesOk ? '1' : '0',
			]));
		} else {
			$this->out('--- enforce_readiness (Fase 8)' . ($strict ? ' [strict]' : '') . ' ---');
			if (!$configOk) {
				$this->err('Configure::read("Rbac") inválido — ver config/rbac.php');
			} else {
				$this->out('mode: ' . $mode);
				$this->out('enforce_block_without_roles: ' . ($block ? 'true' : 'false'));
				$this->out('enforce_block_without_roles_equipe_only: ' . ($equipeOnly ? 'true' : 'false'));
				if ($mode !== 'enforce') {
					$this->out('Nota: bloqueio "sem papéis" (enforce_block_without_roles) só actua com mode=enforce.');
				}
				if (!$tablesOk) {
					$this->err('Tabelas rbac indisponíveis — contagens omitidas.');
				} else {
					$this->out(sprintf(
						'Ativos: equipe sem papel RBAC=%d | portal sem papel=%d',
						(int)$byRole[0]['without'],
						(int)$byRole[1]['without']
					));
					if ($mode === 'enforce' && $block && $equipeOnly && $byRole[0]['without'] > 0) {
						$this->err(sprintf(
							'AVISO: %d utilizador(es) de equipe ativa sem rbac_users_roles serão bloqueados (híbrido desligado para equipe).',
							(int)$byRole[0]['without']
						));
					}
					if ($mode === 'enforce' && $block && !$equipeOnly
						&& ($byRole[0]['without'] > 0 || $byRole[1]['without'] > 0)) {
						$this->err(sprintf(
							'AVISO: enforce_block_without_roles sem equipe_only — equipe sem papel=%d, portal sem papel=%d (ambos podem ser bloqueados).',
							(int)$byRole[0]['without'],
							(int)$byRole[1]['without']
						));
					}
				}
			}
		}
		if ($strict && !$readinessOk) {
			exit(1);
		}
	}

	/**
	 * CI / pré-deploy: corre menu_gates_check em --strict e a seguir enforce_readiness em --strict.
	 * Força saída legível (ignora --csv nestas sub-chamadas). exit(1) se qualquer verificação falhar.
	 */
	public function pre_deploy() {
		$this->out('--- rbac_rollout pre_deploy (menu_gates_check --strict → enforce_readiness --strict) ---');
		$savedParams = $this->params;
		$this->params['strict'] = true;
		$this->params['csv'] = false;
		$this->menu_gates_check();
		$this->enforce_readiness();
		$this->params = $savedParams;
		$this->out('pre_deploy: concluído com sucesso.');
	}

	public function assign_equipe() {
		$this->_assignUnassignedUsersToRbacRole(
			0,
			'assign_equipe',
			'Obrigatório: --role_slug=operacao (slug em rbac_roles, ex.: super_admin, operacao, leitura).'
		);
	}

	/**
	 * Atribui um papel RBAC a utilizadores portal (users.role=1) sem papéis efetivos (mesma lógica que assign_equipe).
	 */
	public function assign_portal() {
		$this->_assignUnassignedUsersToRbacRole(
			1,
			'assign_portal',
			'Obrigatório: --role_slug=cliente_portal (slug de papel portal ativo em rbac_roles).'
		);
	}

	/**
	 * @param int $usersRole 0 equipe, 1 portal
	 * @param string $logPrefix etiqueta no cabeçalho (ex.: assign_equipe)
	 * @param string $missingSlugMessage erro se --role_slug vazio
	 */
	protected function _assignUnassignedUsersToRbacRole($usersRole, $logPrefix, $missingSlugMessage) {
		if (!$this->_rbacTablesOk()) {
			$this->err('Tabelas rbac_* indisponíveis.');

			return;
		}
		$slug = isset($this->params['role_slug']) ? trim((string)$this->params['role_slug']) : '';
		if ($slug === '') {
			$this->err($missingSlugMessage);

			return;
		}
		$role = TableRegistry::get('RbacRoles')->find()
			->where(['slug' => $slug, 'active' => true])
			->first();
		if (empty($role)) {
			$this->err(sprintf('Papel não encontrado ou inativo: slug=%s', $slug));

			return;
		}
		$dry = !empty($this->param('dry-run')) || !empty($this->params['dry_run']);
		$limit = isset($this->params['limit']) ? (int)$this->params['limit'] : 500;
		if ($limit < 1) {
			$limit = 500;
		}
		if ($limit > 10000) {
			$limit = 10000;
		}

		$includeInactive = !empty($this->params['include_inactive']);
		$assignedIds = $this->_effectiveRbacUserIds();
		$users = TableRegistry::get('Users');
		$q = $users->find()
			->select(['id', 'username', 'name'])
			->where(['role' => $usersRole])
			->order(['id' => 'ASC'])
			->limit($limit);
		if (!$includeInactive) {
			$q->where($this->_activeUserConditions());
		}
		if ($assignedIds !== []) {
			$q->where(['id NOT IN' => $assignedIds]);
		}
		$rows = $q->toArray();
		$this->out(sprintf(
			'--- %s: users.role=%d | papel id=%d slug=%s | dry_run=%s | até %d utilizadores ---',
			$logPrefix,
			$usersRole,
			(int)$role->id,
			$slug,
			$dry ? 'yes' : 'no',
			$limit
		));
		if ($rows === []) {
			$this->out('Nenhum utilizador elegível (todos já têm papéis RBAC efetivos ou lista vazia).');

			return;
		}
		$ur = TableRegistry::get('RbacUsersRoles');
		$n = 0;
		foreach ($rows as $u) {
			$uid = (int)$u->id;
			if ($uid <= 0) {
				continue;
			}
			if ($dry) {
				$this->out(sprintf('would assign user_id=%d %s', $uid, (string)$u->username));
				$n++;

				continue;
			}
			$exists = $ur->find()->where(['user_id' => $uid, 'role_id' => (int)$role->id])->first();
			if ($exists) {
				continue;
			}
			$e = $ur->newEntity(['user_id' => $uid, 'role_id' => (int)$role->id]);
			if ($ur->save($e)) {
				$this->out(sprintf('assigned user_id=%d -> role %s', $uid, $slug));
				$n++;
			} else {
				$this->err(sprintf('falha user_id=%d', $uid));
			}
		}
		$this->out(sprintf('Total: %d linha(s) processada(s).', $n));
	}

	public function unassigned_equipe() {
		if (!$this->_rbacTablesOk()) {
			$this->err('Tabelas rbac_* indisponíveis.');

			return;
		}

		$limit = isset($this->params['limit']) ? (int)$this->params['limit'] : 100;
		if ($limit < 1) {
			$limit = 100;
		}
		if ($limit > 5000) {
			$limit = 5000;
		}

		$includeInactive = !empty($this->params['include_inactive']);
		$assignedIds = $this->_effectiveRbacUserIds();

		$users = TableRegistry::get('Users');
		$q = $users->find()
			->select(['id', 'username', 'name', 'inativo'])
			->where(['role' => 0])
			->order(['id' => 'ASC'])
			->limit($limit);

		if (!$includeInactive) {
			$q->where($this->_activeUserConditions());
		}
		if ($assignedIds !== []) {
			$q->where(['id NOT IN' => $assignedIds]);
		}

		$rows = $q->toArray();
		$useCsv = !empty($this->params['csv']);
		if ($useCsv) {
			$this->out($this->_csvLine(['id', 'username', 'name', 'inativo']));
			foreach ($rows as $u) {
				$this->out($this->_csvLine([
					(string)(int)$u->id,
					(string)$u->username,
					(string)$u->name,
					isset($u->inativo) ? (string)$u->inativo : '',
				]));
			}

			return;
		}
		$this->out(sprintf('--- Equipe sem papéis RBAC efetivos (direto nem grupo; até %d linhas) ---', $limit));
		if ($rows === []) {
			$this->out('Nenhum registo (ou todos já têm papel).');

			return;
		}
		foreach ($rows as $u) {
			$this->out(sprintf(
				'id=%d username=%s name=%s inativo=%s',
				(int)$u->id,
				(string)$u->username,
				(string)$u->name,
				isset($u->inativo) ? (string)$u->inativo : ''
			));
		}
	}

	/**
	 * Portal (role=1) sem papéis RBAC efetivos — útil antes de enforce em rotas de cliente ou após migrações.
	 */
	public function unassigned_portal() {
		if (!$this->_rbacTablesOk()) {
			$this->err('Tabelas rbac_* indisponíveis.');

			return;
		}

		$limit = isset($this->params['limit']) ? (int)$this->params['limit'] : 100;
		if ($limit < 1) {
			$limit = 100;
		}
		if ($limit > 5000) {
			$limit = 5000;
		}

		$includeInactive = !empty($this->params['include_inactive']);
		$assignedIds = $this->_effectiveRbacUserIds();

		$users = TableRegistry::get('Users');
		$q = $users->find()
			->select(['id', 'username', 'name', 'inativo', 'idcliente'])
			->where(['role' => 1])
			->order(['id' => 'ASC'])
			->limit($limit);

		if (!$includeInactive) {
			$q->where($this->_activeUserConditions());
		}
		if ($assignedIds !== []) {
			$q->where(['id NOT IN' => $assignedIds]);
		}

		$rows = $q->toArray();
		$useCsv = !empty($this->params['csv']);
		if ($useCsv) {
			$this->out($this->_csvLine(['id', 'username', 'name', 'idcliente', 'inativo']));
			foreach ($rows as $u) {
				$this->out($this->_csvLine([
					(string)(int)$u->id,
					(string)$u->username,
					(string)$u->name,
					isset($u->idcliente) ? (string)$u->idcliente : '',
					isset($u->inativo) ? (string)$u->inativo : '',
				]));
			}

			return;
		}
		$this->out(sprintf('--- Portal (role=1) sem papéis RBAC efetivos (até %d linhas) ---', $limit));
		if ($rows === []) {
			$this->out('Nenhum registo (ou todos já têm papel).');

			return;
		}
		foreach ($rows as $u) {
			$this->out(sprintf(
				'id=%d username=%s name=%s idcliente=%s inativo=%s',
				(int)$u->id,
				(string)$u->username,
				(string)$u->name,
				isset($u->idcliente) ? (string)$u->idcliente : '',
				isset($u->inativo) ? (string)$u->inativo : ''
			));
		}
	}

	/**
	 * Lista papéis em rbac_roles (slugs para usar em assign_equipe / assign_portal).
	 */
	public function list_roles() {
		if (!$this->_rbacRolesTableOk()) {
			$this->err('Tabela rbac_roles indisponível.');

			return;
		}
		$includeInactive = !empty($this->params['all']);
		$useCsv = !empty($this->params['csv']);
		$q = TableRegistry::get('RbacRoles')->find()
			->select(['id', 'slug', 'name', 'active', 'is_system', 'sort_order'])
			->order(['sort_order' => 'ASC', 'id' => 'ASC']);
		if (!$includeInactive) {
			$q->where(['active' => true]);
		}
		$rows = $q->toArray();

		if ($useCsv) {
			$this->out($this->_csvLine([
				'id',
				'slug',
				'name',
				'active',
				'is_system',
				'sort_order',
			]));
			foreach ($rows as $r) {
				$this->out($this->_csvLine([
					(string)(int)$r->id,
					(string)$r->slug,
					(string)$r->name,
					(string)(int)!empty($r->active),
					(string)(int)!empty($r->is_system),
					(string)(int)($r->sort_order ?? 0),
				]));
			}

			return;
		}

		$this->out(sprintf(
			'--- rbac_roles (%s) — usar slug em --role_slug ---',
			$includeInactive ? 'todos' : 'ativos'
		));
		if ($rows === []) {
			$this->out('Nenhum registo.');

			return;
		}
		foreach ($rows as $r) {
			$this->out(sprintf(
				'id=%d slug=%s name=%s active=%s system=%s sort=%s',
				(int)$r->id,
				(string)$r->slug,
				(string)$r->name,
				!empty($r->active) ? '1' : '0',
				!empty($r->is_system) ? '1' : '0',
				(string)(int)($r->sort_order ?? 0)
			));
		}
	}

	/**
	 * Relatório CLI: papéis efetivos + permissões (pós-alias), alinhado ao painel «Efetivo».
	 */
	public function user_effective() {
		if (!$this->_rbacTablesOk() || !$this->_rbacRolesTableOk()) {
			$this->err('Tabelas rbac_* / rbac_roles indisponíveis.');

			return;
		}
		$uid = isset($this->params['user_id']) ? (int)trim((string)$this->params['user_id']) : 0;
		if ($uid < 1) {
			$this->err('Obrigatório: --user_id=N (users.id).');

			return;
		}
		$u = TableRegistry::get('Users')->find()
			->select(['id', 'username', 'name', 'role', 'admin', 'inativo', 'idcliente'])
			->where(['id' => $uid])
			->first();
		if ($u === null) {
			$this->err(sprintf('Utilizador id=%d não encontrado.', $uid));

			return;
		}

		$roleIds = RbacUserRolesResolver::effectiveRoleIds($uid);
		$permIdsRaw = [];
		if ($roleIds !== []) {
			$permIdsRaw = TableRegistry::get('RbacRolesPermissions')->find()
				->select(['permission_id'])
				->where(['role_id IN' => $roleIds])
				->extract('permission_id')
				->toList();
			$permIdsRaw = array_values(array_unique(array_map('intval', $permIdsRaw)));
		}
		$nRaw = count($permIdsRaw);

		$cfg = Configure::read('Rbac');
		$expandAliases = !is_array($cfg) || !array_key_exists('expand_legacy_aliases', $cfg) || $cfg['expand_legacy_aliases'];
		$permIdsExpanded = ($expandAliases && $permIdsRaw !== [])
			? RbacPermissionResolver::expandPermissionIds($permIdsRaw)
			: $permIdsRaw;
		$nExp = count($permIdsExpanded);

		$codes = [];
		if ($permIdsExpanded !== []) {
			$codes = TableRegistry::get('RbacPermissions')->find()
				->select(['code'])
				->where(['id IN' => $permIdsExpanded])
				->order(['code' => 'ASC'])
				->extract('code')
				->toList();
			$codes = array_values(array_unique(array_filter(array_map('strval', $codes))));
		}
		$totalCodes = count($codes);

		$maxCodes = !empty($this->params['full']) ? 25000 : 400;
		$shown = $totalCodes > $maxCodes ? array_slice($codes, 0, $maxCodes) : $codes;
		$useCsv = !empty($this->params['csv']);

		if ($useCsv) {
			$this->out($this->_csvLine([
				'user_id',
				'username',
				'name',
				'users_role',
				'admin',
				'inativo',
				'idcliente',
				'effective_role_ids',
				'n_perm_ids_raw',
				'n_perm_ids_expanded',
				'n_codes',
			]));
			$this->out($this->_csvLine([
				(string)$uid,
				(string)$u->username,
				(string)$u->name,
				(string)(int)$u->role,
				(string)(int)!empty($u->admin),
				isset($u->inativo) ? (string)$u->inativo : '',
				isset($u->idcliente) && $u->idcliente !== null ? (string)$u->idcliente : '',
				implode('|', $roleIds),
				(string)$nRaw,
				(string)$nExp,
				(string)$totalCodes,
			]));
			$this->out($this->_csvLine(['code']));
			foreach ($shown as $c) {
				$this->out($this->_csvLine([$c]));
			}
			if ($totalCodes > count($shown)) {
				$this->err(sprintf('CSV: listados %d de %d códigos (repetir com --full).', count($shown), $totalCodes));
			}

			return;
		}

		$this->out(sprintf('--- user_effective user_id=%d ---', $uid));
		$this->out(sprintf(
			'users: id=%d username=%s name=%s role=%d admin=%s inativo=%s idcliente=%s',
			(int)$u->id,
			(string)$u->username,
			(string)$u->name,
			(int)$u->role,
			!empty($u->admin) ? '1' : '0',
			isset($u->inativo) ? (string)$u->inativo : '—',
			isset($u->idcliente) && $u->idcliente !== null ? (string)$u->idcliente : '—'
		));
		if ($roleIds === []) {
			$this->out('papéis efetivos: (nenhum) — híbrido / sem rbac_users_roles nem grupos com papéis.');
		} else {
			$this->out('papéis efetivos (id): ' . implode(', ', $roleIds));
			$roles = TableRegistry::get('RbacRoles')->find()
				->where(['id IN' => $roleIds])
				->order(['id' => 'ASC'])
				->all();
			foreach ($roles as $r) {
				$this->out(sprintf('  role id=%d slug=%s name=%s', (int)$r->id, (string)$r->slug, (string)$r->name));
			}
		}
		$this->out(sprintf(
			'ligações papel→permissão (ids únicos): %d | após expand_legacy_aliases=%s: %d | códigos únicos: %d',
			$nRaw,
			$expandAliases ? 'true' : 'false',
			$nExp,
			$totalCodes
		));
		if ($shown === []) {
			$this->out('permissões (código): (nenhuma)');

			return;
		}
		$this->out(sprintf('permissões (código), até %d de %d:', count($shown), $totalCodes));
		foreach ($shown as $c) {
			$this->out('  ' . $c);
		}
		if ($totalCodes > count($shown)) {
			$this->out(sprintf('… %d códigos omitidos (use --full).', $totalCodes - count($shown)));
		}
	}

	/**
	 * Lista utilizadores para os quais RbacChecker::userHasPermissionCode confirma o código (aliases incluídos).
	 */
	public function who_has() {
		if (!$this->_rbacTablesOk()) {
			$this->err('Tabelas rbac_* indisponíveis.');

			return;
		}
		$code = trim((string)($this->params['code'] ?? ''));
		if ($code === '') {
			$this->err('Obrigatório: --code=modulo.recurso.acao (ver rbac_permissions.code).');

			return;
		}
		$perm = TableRegistry::get('RbacPermissions')->find()
			->select(['id', 'code'])
			->where(['code' => $code])
			->first();
		if ($perm === null) {
			$this->err(sprintf('Código não encontrado no catálogo: %s', $code));

			return;
		}

		$scanLimit = isset($this->params['scan_limit']) ? (int)$this->params['scan_limit'] : 3000;
		if ($scanLimit < 1) {
			$scanLimit = 3000;
		}
		if ($scanLimit > 20000) {
			$scanLimit = 20000;
		}

		$filterRole = isset($this->params['filter_role']) ? trim((string)$this->params['filter_role']) : '';
		$includeInactive = !empty($this->params['include_inactive']);
		$useCsv = !empty($this->params['csv']);

		$q = TableRegistry::get('Users')->find()
			->select(['id', 'username', 'name', 'role', 'inativo'])
			->order(['id' => 'ASC'])
			->limit($scanLimit);
		if ($filterRole === '0' || $filterRole === '1') {
			$q->where(['role' => (int)$filterRole]);
		}
		if (!$includeInactive) {
			$q->where($this->_activeUserConditions());
		}
		$rows = $q->toArray();

		$matches = [];
		foreach ($rows as $u) {
			$uid = (int)$u->id;
			if ($uid < 1) {
				continue;
			}
			if (RbacChecker::userHasPermissionCode($uid, $code)) {
				$matches[] = $u;
			}
		}

		if ($useCsv) {
			$this->out($this->_csvLine(['permission_code', 'user_id', 'username', 'name', 'users_role', 'inativo']));
			foreach ($matches as $u) {
				$this->out($this->_csvLine([
					$code,
					(string)(int)$u->id,
					(string)$u->username,
					(string)$u->name,
					(string)(int)$u->role,
					isset($u->inativo) ? (string)$u->inativo : '',
				]));
			}
			$this->out(sprintf(
				'# who_has: %d com permissão em %d users percorridos (scan_limit=%d; use --scan_limit para ampliar).',
				count($matches),
				count($rows),
				$scanLimit
			));

			return;
		}

		$this->out(sprintf(
			'--- who_has code=%s (permission id=%d) | percorridos %d users (scan_limit=%d)%s%s ---',
			$code,
			(int)$perm->id,
			count($rows),
			$scanLimit,
			$filterRole === '0' ? ' | só equipe (role=0)' : ($filterRole === '1' ? ' | só portal (role=1)' : ''),
			$includeInactive ? ' | inclui inativos' : ''
		));
		if ($matches === []) {
			$this->out('Nenhum utilizador na amostra com esta permissão (via RBAC efetivo + expand_legacy_aliases).');

			return;
		}
		foreach ($matches as $u) {
			$this->out(sprintf(
				'id=%d username=%s name=%s role=%d inativo=%s',
				(int)$u->id,
				(string)$u->username,
				(string)$u->name,
				(int)$u->role,
				isset($u->inativo) ? (string)$u->inativo : ''
			));
		}
		$this->out(sprintf('Total na amostra: %d utilizador(es).', count($matches)));
	}

	/**
	 * Verifica se cada código em Rbac.menu_sidebar_gates existe em rbac_permissions (pós-sync catálogo).
	 */
	public function menu_gates_check() {
		$strict = !empty($this->params['strict']);
		$useCsv = !empty($this->params['csv']);
		$rb = Configure::read('Rbac');
		if (!is_array($rb) || empty($rb['menu_sidebar_gates']) || !is_array($rb['menu_sidebar_gates'])) {
			$this->err('Rbac.menu_sidebar_gates ausente ou inválido em config/rbac.php.');
			if ($strict) {
				exit(1);
			}

			return;
		}
		if (!$this->_rbacTablesOk()) {
			$this->err('Tabelas rbac_permissions / rbac_users_roles indisponíveis.');
			if ($strict) {
				exit(1);
			}

			return;
		}

		$codeToGates = [];
		foreach ($rb['menu_sidebar_gates'] as $gateKey => $codesVal) {
			$key = trim((string)$gateKey);
			if ($key === '') {
				continue;
			}
			$list = is_array($codesVal) ? $codesVal : [$codesVal];
			foreach ($list as $one) {
				$c = trim((string)$one);
				if ($c === '') {
					continue;
				}
				if (!isset($codeToGates[$c])) {
					$codeToGates[$c] = [];
				}
				$codeToGates[$c][] = $key;
			}
		}
		foreach ($codeToGates as $c => $gates) {
			$codeToGates[$c] = array_values(array_unique($gates));
		}

		$uniqueCodes = array_keys($codeToGates);
		if ($uniqueCodes === []) {
			$this->out('--- menu_gates_check ---');
			$this->out('Nenhum código em menu_sidebar_gates.');

			return;
		}

		$existing = TableRegistry::get('RbacPermissions')->find()
			->select(['code'])
			->where(['code IN' => $uniqueCodes])
			->extract('code')
			->toList();
		$existSet = array_flip(array_map('strval', $existing));

		$missing = [];
		foreach ($uniqueCodes as $c) {
			if (!isset($existSet[$c])) {
				$missing[] = $c;
			}
		}

		if ($useCsv) {
			$this->out($this->_csvLine(['gate_key', 'code', 'status']));
			foreach ($codeToGates as $code => $gates) {
				$st = in_array($code, $missing, true) ? 'missing' : 'ok';
				foreach ($gates as $gk) {
					$this->out($this->_csvLine([$gk, $code, $st]));
				}
			}
			$this->out(sprintf(
				'# menu_gates_check: %d códigos únicos, %d ausentes em rbac_permissions',
				count($uniqueCodes),
				count($missing)
			));
			if ($strict && $missing !== []) {
				exit(1);
			}

			return;
		}

		$this->out('--- menu_gates_check (Rbac.menu_sidebar_gates × rbac_permissions) ---');
		$this->out(sprintf('Códigos únicos referenciados: %d', count($uniqueCodes)));
		if ($missing === []) {
			$this->out('Todos os códigos existem na base (rbac_permissions).');
			return;
		}
		$this->out(sprintf('Ausentes na base (%d) — Sincronizar catálogo ou migrations:', count($missing)));
		sort($missing);
		foreach ($missing as $c) {
			$gk = implode(', ', $codeToGates[$c]);
			$this->out(sprintf('  · %s  [gates: %s]', $c, $gk));
		}
		if ($strict) {
			exit(1);
		}
	}

	/**
	 * Conta ligações rbac_roles_permissions por papel (matriz papel × permissão).
	 */
	public function role_stats() {
		$tables = $this->_listRbacSchemaTables();
		if ($tables === null) {
			$this->err('Não foi possível ler o schema da base.');

			return;
		}
		if (!in_array('rbac_roles', $tables, true) || !in_array('rbac_roles_permissions', $tables, true)) {
			$this->err('Tabelas rbac_roles ou rbac_roles_permissions ausentes.');

			return;
		}

		$includeInactive = !empty($this->params['all']);
		$useCsv = !empty($this->params['csv']);
		$q = TableRegistry::get('RbacRoles')->find()
			->select(['id', 'slug', 'name', 'active', 'sort_order'])
			->order(['sort_order' => 'ASC', 'id' => 'ASC']);
		if (!$includeInactive) {
			$q->where(['active' => true]);
		}
		$roles = $q->toArray();
		$rrp = TableRegistry::get('RbacRolesPermissions');

		$lines = [];
		foreach ($roles as $r) {
			$n = $rrp->find()->where(['role_id' => (int)$r->id])->count();
			$lines[] = [
				'id' => (int)$r->id,
				'slug' => (string)$r->slug,
				'name' => (string)$r->name,
				'active' => !empty($r->active),
				'sort_order' => (int)($r->sort_order ?? 0),
				'n_permissions' => (int)$n,
			];
		}

		if ($useCsv) {
			$this->out($this->_csvLine(['role_id', 'slug', 'name', 'active', 'sort_order', 'n_permissions']));
			foreach ($lines as $row) {
				$this->out($this->_csvLine([
					(string)$row['id'],
					$row['slug'],
					$row['name'],
					$row['active'] ? '1' : '0',
					(string)$row['sort_order'],
					(string)$row['n_permissions'],
				]));
			}
			$this->out(sprintf('# role_stats: %d papel(is)', count($lines)));

			return;
		}

		$this->out(sprintf(
			'--- role_stats (%s) ---',
			$includeInactive ? 'todos os papéis' : 'só active=1'
		));
		foreach ($lines as $row) {
			$this->out(sprintf(
				'id=%d slug=%s name=%s active=%s sort=%d n_permissions=%d',
				$row['id'],
				$row['slug'],
				$row['name'],
				$row['active'] ? '1' : '0',
				$row['sort_order'],
				$row['n_permissions']
			));
		}
	}

	/**
	 * Utilizadores com rbac_users_roles OU com grupo que tem rbac_group_roles (alinha ao RbacComponent).
	 *
	 * @return int[]
	 */
	protected function _effectiveRbacUserIds() {
		$raw = TableRegistry::get('RbacUsersRoles')->find()
			->select(['user_id'])
			->all()
			->extract('user_id')
			->toList();

		$ids = array_values(array_unique(array_map('intval', $raw)));

		if (!$this->_groupTablesExist()) {
			return $ids;
		}
		try {
			$gWithRoles = TableRegistry::get('RbacGroupRoles')->find()
				->select(['group_id'])
				->all()
				->extract('group_id')
				->toList();
			$gWithRoles = array_values(array_unique(array_map('intval', $gWithRoles)));
			if ($gWithRoles === []) {
				return $ids;
			}
			$fromGroups = TableRegistry::get('RbacUserGroups')->find()
				->select(['user_id'])
				->where(['group_id IN' => $gWithRoles])
				->all()
				->extract('user_id')
				->toList();
			$fromGroups = array_values(array_unique(array_map('intval', $fromGroups)));
			if ($fromGroups !== []) {
				$ids = array_values(array_unique(array_merge($ids, $fromGroups)));
			}
		} catch (\Exception $e) {
		}

		return $ids;
	}

	protected function _groupTablesExist() {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_user_groups', $tables, true)
				&& in_array('rbac_group_roles', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function _activeUserConditions() {
		return [
			'OR' => [
				['inativo' => 0],
				['inativo IS' => null],
			],
		];
	}

	/**
	 * Uma linha CSV (RFC-style via fputcsv); valores como string.
	 */
	protected function _csvLine(array $fields): string {
		$fp = fopen('php://temp', 'r+');
		if ($fp === false) {
			return '';
		}
		fputcsv($fp, $fields);
		rewind($fp);
		$line = stream_get_contents($fp);
		fclose($fp);

		return rtrim((string)$line, "\r\n");
	}

	protected function _rbacTablesOk() {
		try {
			$conn = TableRegistry::get('RbacPermissions')->getConnection();
			$tables = $conn->getSchemaCollection()->listTables();

			return in_array('rbac_permissions', $tables, true)
				&& in_array('rbac_users_roles', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function _rbacRolesTableOk() {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_roles', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @return string[]|null null se falhar a leitura do schema
	 */
	protected function _listRbacSchemaTables() {
		try {
			return TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();
		} catch (\Exception $e) {
			return null;
		}
	}

	protected function _auditTableExists() {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_audit_authorizations', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}
}
