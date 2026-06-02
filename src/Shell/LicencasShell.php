<?php
declare(strict_types=1);

namespace App\Shell;

use App\Service\Lic\LicPrototypeDataService;
use Cake\Console\Shell;
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
		$parser->addOption('idempresa', [
			'default' => '',
			'help' => 'Filtrar por idempresa (obrigatório em seed_demo).',
		]);
		$parser->addOption('dry-run', [
			'boolean' => true,
			'default' => false,
			'help' => 'seed_demo: apenas simula.',
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
	}

	/**
	 * Cake pode invocar seedDemo (camelCase) em vez de seed_demo.
	 */
	public function seedDemo() {
		return $this->seed_demo();
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
