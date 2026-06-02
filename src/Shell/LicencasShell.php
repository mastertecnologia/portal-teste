<?php
declare(strict_types=1);

namespace App\Shell;

use App\Service\Lic\LicPrototypeDataService;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * Diagnóstico e seed de homologação do módulo Licenciamento (lic_*).
 *
 * Uso:
 *   bin/cake licencas stats
 *   bin/cake licencas stats --idempresa=1
 *   bin/cake licencas seed_demo --idempresa=1
 *   bin/cake licencas seed_demo --idempresa=1 --dry-run
 *   bin/cake licencas seed_demo --idempresa=1 --force
 *   bin/cake licencas uat_check --idempresa=1 --strict
 */
class LicencasShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription('Licenciamento: estatísticas e seed de homologação (ORM).');
		$parser->addSubcommand('stats', [
			'help' => 'Contagens lic_* e KPIs por empresa.',
		]);
		$parser->addSubcommand('seed_demo', [
			'help' => 'Seed de homologação (categoria, produtos, licenças, cofre, solicitação).',
		]);
		$parser->addSubcommand('uat_check', [
			'help' => 'Pré-vôo homologação: schema lic_*, RBAC e opcional KPIs por empresa.',
		]);
		$parser->addSubcommand('url_check', [
			'help' => 'Diagnóstico DNS/HTTPS: portal.pgm.inf.br vs Apache local (Linux).',
		]);
		$parser->addOption('idempresa', [
			'default' => '',
			'help' => 'Filtrar por idempresa (obrigatório em seed_demo).',
		]);
		$parser->addOption('dry-run', [
			'boolean' => true,
			'default' => false,
			'help' => 'seed_demo: apenas simula.',
		]);
		$parser->addOption('strict', [
			'boolean' => true,
			'default' => false,
			'help' => 'uat_check: sai com código 1 se houver falhas.',
		]);
		$parser->addOption('force', [
			'boolean' => true,
			'default' => false,
			'help' => 'seed_demo: executa mesmo se já existirem licenças na empresa.',
		]);

		return $parser;
	}


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

	public function main() {
		$this->out('Subcomandos:');
		$this->out('  stats [--idempresa=N]');
		$this->out('  seed_demo --idempresa=N [--dry-run] [--force]');
		$this->out('  uat_check [--idempresa=N] [--strict]');
		$this->out('  url_check [--host=portal.pgm.inf.br]');
	}

	/**
	 * Cake pode invocar seedDemo (camelCase) em vez de seed_demo.
	 */
	public function seedDemo() {
		return $this->seed_demo();
	}

	public function uatCheck() {
		return $this->uat_check();
	}

	public function urlCheck() {
		return $this->url_check();
	}



	public function stats() {
		$idempresa = $this->parseIdempresa(false);
		$aliases = [
			'LicCategorias',
			'LicCatalogoProdutos',
			'LicLicencas',
			'LicAssentos',
			'LicDispositivos',
			'LicCofreItens',
			'LicSolicitacoes',
			'LicAuditoriaEventos',
			'LicModuloConfig',
		];
		foreach ($aliases as $alias) {
			$tbl = $this->loadTable($alias);
			if ($tbl === null) {
				$this->err("{$alias}: indisponível (autoload ou migration).");

				continue;
			}
			$q = $tbl->find();
			if ($idempresa > 0 && $tbl->hasField('idempresa')) {
				$q->where([$alias . '.idempresa' => $idempresa]);
			}
			$this->out(sprintf('%s: %d', $alias, $q->count()));
		}
		if ($idempresa > 0) {
			$svc = new LicPrototypeDataService($idempresa);
			if (!$svc->tablesAvailable()) {
				$this->err('Schema lic_* não encontrado na BD.');

				return;
			}
			$kpi = $svc->dashboardKpis();
			$this->out('');
			$this->out('KPIs (LicPrototypeDataService):');
			foreach ($kpi as $k => $v) {
				$this->out("  {$k}: {$v}");
			}
		}
	}

	public function seed_demo() {
		$idempresa = $this->parseIdempresa(true);
		$dryRun = !empty($this->params['dry-run']);
		$force = !empty($this->params['force']);
		$svc = new LicPrototypeDataService($idempresa);
		if (!$svc->tablesAvailable()) {
			$this->err('Tabelas lic_* indisponíveis na BD. Execute bin/cake migrations migrate.');

			return;
		}
		$licTbl = $this->loadTable('LicLicencas');
		$cliTbl = $this->loadTable('Clientes');
		if ($licTbl === null || $cliTbl === null) {
			$this->err('Models LicLicencas/Clientes não carregaram. Execute: composer dump-autoload -o');

			return;
		}
		$existing = $licTbl->find()->where(['idempresa' => $idempresa])->count();
		if ($existing > 0 && !$force) {
			$this->err("Empresa #{$idempresa} já tem {$existing} licença(s). Use --force para acrescentar demo.");

			return;
		}
		$clientes = $cliTbl->find()
			->where(['Clientes.idempresa' => $idempresa, 'Clientes.inativo' => 0])
			->order(['Clientes.id' => 'ASC'])
			->limit(2)
			->all();
		if ($clientes->count() === 0) {
			$this->err("Nenhum cliente ativo para idempresa={$idempresa}.");

			return;
		}
		$plan = [
			'categoria' => ['codigo' => 'DEMO-SW', 'nome' => 'Software (demo)'],
			'produtos' => [
				['sku' => 'DEMO-M365', 'nome' => 'Microsoft 365 Business (demo)'],
				['sku' => 'DEMO-ADOBE', 'nome' => 'Adobe CC Teams (demo)'],
			],
		];
		$this->out(sprintf('seed_demo idempresa=%d dry_run=%s force=%s', $idempresa, $dryRun ? 'yes' : 'no', $force ? 'yes' : 'no'));
		if ($dryRun) {
			$this->out('Plano: 1 categoria, 2 produtos, até 2 licenças (1 por cliente), 1 item cofre, 1 solicitação.');
			$this->out('Clientes: ' . implode(', ', array_map(static function ($c) {
				return (string)$c->get('id');
			}, $clientes->toArray())));

			return;
		}
		$cat = $svc->saveCategoria([
			'codigo' => $plan['categoria']['codigo'],
			'nome' => $plan['categoria']['nome'],
			'iduser' => 0,
		], null);
		if (empty($cat['ok'])) {
			$this->err('Falha categoria: ' . json_encode($cat['errors'] ?? []));

			return;
		}
		$idcategoria = (int)$cat['id'];
		$this->out("Categoria #{$idcategoria}");
		$prodIds = [];
		foreach ($plan['produtos'] as $p) {
			$res = $svc->saveCatalogoProduto([
				'idcategoria' => $idcategoria,
				'sku' => $p['sku'],
				'nome' => $p['nome'],
				'ativo' => true,
				'iduser' => 0,
			], null);
			if (empty($res['ok'])) {
				$this->err('Falha produto: ' . json_encode($res['errors'] ?? []));
				continue;
			}
			$prodIds[] = (int)$res['id'];
			$this->out("Produto #{$res['id']} {$p['sku']}");
		}
		$i = 0;
		foreach ($clientes as $cli) {
			$idcliente = (int)$cli->get('id');
			$idcatalogo = $prodIds[$i % count($prodIds)] ?? null;
			$step1 = $svc->saveWizardStep(1, [
				'idcliente' => $idcliente,
				'idcatalogo' => $idcatalogo,
				'produto_label' => '',
				'modelo' => 'assinatura',
				'assentos' => 5,
				'iduser' => 0,
			], null);
			if (empty($step1['ok'])) {
				$this->err("Wizard 1 cliente {$idcliente}: " . json_encode($step1['errors'] ?? []));
				$i++;
				continue;
			}
			$licId = (int)$step1['id'];
			$hoje = date('Y-m-d');
			$fim = date('Y-m-d', strtotime('+1 year'));
			$svc->saveWizardStep(2, [
				'assentos' => 5,
				'inicio' => $hoje,
				'fim' => $fim,
				'valor_anual' => '12000.00',
				'iduser' => 0,
			], $licId);
			$svc->saveWizardStep(3, [
				'emails' => "demo{$idcliente}@example.invalid",
				'iduser' => 0,
			], $licId);
			$svc->saveWizardStep(4, ['status_final' => 'ativa', 'iduser' => 0], $licId);
			$this->out("Licença #{$licId} cliente #{$idcliente}");
			$i++;
		}
		$firstCli = (int)$clientes->first()->get('id');
		$cofre = $svc->saveCofreItem([
			'idcliente' => $firstCli,
			'titulo' => 'Credencial demo (homologação)',
			'nivel' => 'medio',
			'segredo' => 'demo-secret-change-me',
			'iduser' => 0,
		], null);
		if (!empty($cofre['ok'])) {
			$this->out('Cofre item #' . (int)$cofre['id']);
		}
		$svc->createSolicitacao([
			'idcliente' => $firstCli,
			'tipo' => 'nova_licenca',
			'produto' => 'Demo portal',
			'assentos' => 3,
			'observacao' => 'Seed homologação',
			'iduser' => 0,
		]);
		$this->out('Solicitação demo criada.');
		$svc->saveModuloConfig([
			'alerta_vencimento_dias' => 30,
			'notificar_email' => '',
			'cofre_exige_aprovacao' => false,
		], 0);
		$this->out('<success>Seed demo concluído.</success>');
	}



	/**
	 * Compara resposta HTTPS pública vs Apache local (detecta DNS no Windows).
	 *
	 * @return int
	 */
	public function url_check() {
		$host = trim((string)($this->params['host'] ?? 'portal.pgm.inf.br'));
		if ($host === '') {
			$host = 'portal.pgm.inf.br';
		}
		$base = trim((string)\Cake\Core\Configure::read('App.base'));
		if ($base === '' || $base === '/') {
			$base = '';
		}
		$path = rtrim($base, '/') . '/licencas-prototype';
		$url = 'https://' . $host . $path;

		$this->out('=== Licenciamento — url_check ===');
		$this->out('URL: ' . $url);
		$this->out('APP_BASE=' . ($base !== '' ? $base : '(raiz)'));

		$ip = @gethostbyname($host);
		if ($ip !== $host && $ip !== '') {
			$this->out('DNS ' . $host . ' → ' . $ip);
		} else {
			$this->err('[WARN] não resolveu DNS para ' . $host);
		}

		$localIps = [];
		if (function_exists('gethostname')) {
			$hn = gethostname();
			if ($hn) {
				$localIps[] = gethostbyname($hn);
			}
		}
		foreach (['127.0.0.1', '10.0.2.25'] as $lip) {
			if (!in_array($lip, $localIps, true)) {
				$localIps[] = $lip;
			}
		}
		$this->out('Teste local (--resolve 127.0.0.1):');
		$this->probeUrl($url, true);
		$this->out('Teste DNS público:');
		$code = $this->probeUrl($url, false);
		$this->out('');
		if ($code === 404) {
			$this->err('[FAIL] 404 na URL pública — ver docs/LICENCIAMENTO_DNS_PUBLICO.md');
			return 1;
		}
		if ($code >= 200 && $code < 400) {
			$this->out('<success>URL pública responde ' . $code . ' (esperado 302 sem sessão)</success>');
			return 0;
		}
		$this->err('[WARN] http_code=' . $code . ' — ver SSL ou proxy');
		return 0;
	}

	protected function probeUrl(string $url, bool $resolveLocal): void {
		$ch = curl_init($url);
		if ($ch === false) {
			$this->err('curl_init falhou');
			return;
		}
		$headers = [];
		curl_setopt_array($ch, [
			CURLOPT_NOBODY => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_HEADERFUNCTION => static function ($curl, $line) use (&$headers) {
				if (preg_match('/^([^:]+):\s*(.*)$/i', trim($line), $m)) {
					$headers[strtolower($m[1])] = trim($m[2]);
				}
				return strlen($line);
			},
		]);
		if ($resolveLocal) {
			$parsed = parse_url($url);
			$h = $parsed['host'] ?? '';
			$port = $parsed['port'] ?? 443;
			curl_setopt($ch, CURLOPT_RESOLVE, [$h . ':' . $port . ':127.0.0.1']);
		}
		curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err = curl_error($ch);
		curl_close($ch);
		$server = $headers['server'] ?? '(sem Server)';
		$location = $headers['location'] ?? '';
		$this->out('  http_code=' . $code . ' Server=' . $server);
		if ($location !== '') {
			$this->out('  Location=' . $location);
		}
		if ($err !== '') {
			$this->err('  curl: ' . $err);
		}
		if (stripos($server, 'Win64') !== false) {
			$this->err('  [FAIL] resposta do ERP Windows — DNS/proxy deve apontar para 10.0.2.25');
		}
		if (stripos($server, 'Debian') !== false && ($code === 302 || $code === 301)) {
			$this->out('  [OK] Apache Linux / Cake');
		}
	}

	/**
	 * Pré-vôo pós-deploy (schema, RBAC, cofre). Não altera dados.
	 *
	 * @return int
	 */
	public function uat_check() {
		$strict = !empty($this->params['strict']);
		$idempresa = $this->parseIdempresa(false);
		$errors = 0;
		$warns = 0;

		$this->out('=== Licenciamento — uat_check ===');

		$requiredTables = [
			'lic_categorias',
			'lic_catalogo_produtos',
			'lic_licencas',
			'lic_assentos',
			'lic_dispositivos',
			'lic_cofre_itens',
			'lic_solicitacoes',
			'lic_auditoria_eventos',
			'lic_modulo_config',
		];
		try {
			$conn = TableRegistry::getTableLocator()->get('Users')->getConnection();
			$tables = $conn->getSchemaCollection()->listTables();
		} catch (\Throwable $e) {
			$this->err('BD: ' . $e->getMessage());
			$errors++;

			return $strict ? 1 : 0;
		}
		foreach ($requiredTables as $t) {
			if (in_array($t, $tables, true)) {
				$this->out("[OK] tabela {$t}");
			} else {
				$this->err("[FAIL] tabela {$t} ausente — bin/cake migrations migrate");
				$errors++;
			}
		}

		$permCodes = [
			'licencas.view',
			'licencas.manage',
			'licencas.cofre.view',
			'licencas.cofre.secret',
		];
		$permIds = [];
		try {
			$permTbl = $this->loadTable('RbacPermissions');
			if ($permTbl === null) {
				$this->err('[FAIL] RbacPermissions indisponível');
				$errors++;
			} else {
				foreach ($permCodes as $code) {
					$row = $permTbl->find()->where(['code' => $code])->first();
					if ($row === null) {
						$this->err("[FAIL] permissão {$code} ausente — migrate RbacLicencas* ou sync_registry");
						$errors++;
						continue;
					}
					$permIds[$code] = (int)$row->get('id');
					$this->out("[OK] permissão {$code}");
				}
			}
		} catch (\Throwable $e) {
			$this->err('RBAC permissions: ' . $e->getMessage());
			$errors++;
		}

		$roleExpect = [
			'operacao' => ['licencas.view', 'licencas.manage', 'licencas.cofre.view'],
			'admin_equipe' => ['licencas.view', 'licencas.manage', 'licencas.cofre.view', 'licencas.cofre.secret'],
		];
		try {
			$rolesTbl = $this->loadTable('RbacRoles');
			$rpTbl = $this->loadTable('RbacRolesPermissions');
			if ($rolesTbl === null || $rpTbl === null) {
				$this->err('[FAIL] tabelas RBAC de papéis indisponíveis');
				$errors++;
			} elseif ($permIds !== []) {
				foreach ($roleExpect as $slug => $codes) {
					$role = $rolesTbl->find()->where(['slug' => $slug, 'active' => 1])->first();
					if ($role === null) {
						$this->err("[WARN] papel {$slug} não encontrado");
						$warns++;
						continue;
					}
					$rid = (int)$role->get('id');
					$linked = $rpTbl->find()
						->where(['role_id' => $rid])
						->extract('permission_id')
						->toList();
					foreach ($codes as $code) {
						$pid = $permIds[$code] ?? 0;
						if ($pid > 0 && in_array($pid, $linked, true)) {
							$this->out("[OK] {$slug} → {$code}");
						} else {
							$this->err("[FAIL] {$slug} sem {$code}");
							$errors++;
						}
					}
				}
			}
		} catch (\Throwable $e) {
			$this->err('RBAC roles: ' . $e->getMessage());
			$errors++;
		}

		$key = trim((string)env('LIC_COFRE_CIPHER_KEY', ''));
		if ($key === '') {
			$this->err('[WARN] LIC_COFRE_CIPHER_KEY vazio — cofre grava segredos em b64: (homologação)');
			$warns++;
		} else {
			$this->out('[OK] LIC_COFRE_CIPHER_KEY definido');
			if (!extension_loaded('openssl')) {
				$this->err('[FAIL] ext-openssl necessária para AES-256-GCM do cofre');
				$errors++;
			}
		}

		$canonical = Configure::read('Licencas.canonical_routes') ? '1' : '0';
		$this->out('LICENCAS_CANONICAL_ROUTES=' . $canonical . ' (menu ERP continua /licencas-prototype)');

		$mode = (string)Configure::read('Rbac.mode');
		$this->out('RBAC_MODE=' . ($mode !== '' ? $mode : '(default config)'));

		if ($idempresa > 0) {
			$svc = new LicPrototypeDataService($idempresa);
			if (!$svc->tablesAvailable()) {
				$this->err("[FAIL] schema lic_* inacessível para KPIs (idempresa={$idempresa})");
				$errors++;
			} else {
				$kpi = $svc->dashboardKpis();
				$this->out('');
				$this->out("KPIs idempresa={$idempresa}:");
				foreach ($kpi as $k => $v) {
					$this->out("  {$k}: {$v}");
				}
				if ((int)($kpi['licencas_ativas'] ?? 0) === 0) {
					$this->err('[WARN] nenhuma licença ativa — seed_demo ou wizard UAT');
					$warns++;
				}
			}
		}

		$this->out('');
		if ($errors > 0) {
			$this->err("Resumo: {$errors} falha(s), {$warns} aviso(s)");
			if ($strict) {
				return 1;
			}

			return 0;
		}
		$this->out("<success>Resumo: OK ({$warns} aviso(s))</success>");
		$this->out('Pós-UAT opcional: docs/LICENCIAMENTO_POS_UAT_OPCIONAL.md');

		return 0;
	}

	/**
	 * @return Table|null
	 */
	protected function loadTable(string $alias): ?Table {
		try {
			return TableRegistry::getTableLocator()->get($alias);
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function parseIdempresa(bool $required): int {
		$raw = trim((string)($this->params['idempresa'] ?? ''));
		if ($raw === '' && $required) {
			$this->abort('Informe --idempresa=N');
		}
		$id = (int)$raw;

		return $id > 0 ? $id : 0;
	}

	public function __call($name, $args) {
		$snake = Inflector::underscore($name);
		if ($snake !== $name && method_exists($this, $snake)) {
			return $this->{$snake}(...$args);
		}

		return parent::__call($name, $args);
	}
}
