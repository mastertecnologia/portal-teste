<?php
declare(strict_types=1);
namespace App\Test\TestCase\Service\Ticket;

use App\Model\Table\WorkflowSlaPoliciesTable;
use App\Service\Ticket\SlaPolicyResolverService;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use PHPUnit\Framework\TestCase;

/**
 * SlaPolicyResolverService com SQLite (:memory:) e esquema mínimo de workflow_sla_policies.
 */
class SlaPolicyResolverServiceTest extends TestCase {

	/** @var \Cake\Database\Connection */
	protected static $conn;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if (!extension_loaded('pdo_sqlite')) {
			self::markTestSkipped('pdo_sqlite necessário.');
		}
		$root = dirname(__DIR__, 4);
		require $root . '/vendor/autoload.php';
		require $root . '/config/paths.php';

		ConnectionManager::drop('default');
		ConnectionManager::config('default', [
			'className' => Connection::class,
			'driver' => Sqlite::class,
			'database' => ':memory:',
			'encoding' => 'utf8',
		]);
		self::$conn = ConnectionManager::get('default');
		self::_createSchema();
		TableRegistry::clear();
	}

	public static function tearDownAfterClass(): void {
		TableRegistry::clear();
		ConnectionManager::drop('default');
		parent::tearDownAfterClass();
	}

	protected function setUp(): void {
		parent::setUp();
		TableRegistry::clear();
		self::$conn->execute('DELETE FROM workflow_sla_policies');
	}

	protected function policies(): WorkflowSlaPoliciesTable {
		return TableRegistry::getTableLocator()->get('WorkflowSlaPolicies');
	}

	protected function savePolicy(array $data): int {
		$e = $this->policies()->newEntity($data, ['validate' => false]);
		$this->policies()->save($e, ['checkRules' => false]);
		if ($e->getErrors()) {
			$this->fail('savePolicy: ' . json_encode($e->getErrors(), JSON_UNESCAPED_UNICODE));
		}

		return (int)$e->get('id');
	}

	protected function ticket(array $fields): \Cake\Datasource\EntityInterface {
		return new \Cake\ORM\Entity($fields);
	}

	public function testTier1PreferredOverTier2(): void {
		$id1 = $this->savePolicy([
			'empresa_id' => 10,
			'workflow_state_id' => 100,
			'contract_id' => 1,
			'contract_service_id' => 2,
			'problema_id' => 3,
			'queue_id' => 50,
			'support_level_id' => null,
			'scope_priority' => 100,
			'resposta_minutos' => 10,
			'resolucao_minutos' => 60,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);
		$id2 = $this->savePolicy([
			'empresa_id' => 10,
			'workflow_state_id' => 100,
			'contract_id' => 1,
			'contract_service_id' => 2,
			'problema_id' => 3,
			'queue_id' => null,
			'support_level_id' => null,
			'scope_priority' => 1,
			'resposta_minutos' => 20,
			'resolucao_minutos' => 120,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$p = $svc->resolveForTicket($this->ticket([
			'idempresa' => 10,
			'workflow_state_id' => 100,
			'contract_id' => 1,
			'contract_service_id' => 2,
			'problema_id' => 3,
			'queue_id' => 50,
		]));
		$this->assertNotNull($p);
		$this->assertSame($id1, (int)$p->get('id'));
		$this->assertLessThan($id2, $id1);
		$this->assertSame(10, (int)$p->get('resposta_minutos'));
	}

	public function testTier5ClientOverTier6Empresa(): void {
		$idEmp = $this->savePolicy([
			'empresa_id' => 10,
			'workflow_state_id' => 200,
			'resposta_minutos' => 1,
			'resolucao_minutos' => 1,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);
		$idCli = $this->savePolicy([
			'empresa_id' => 10,
			'workflow_state_id' => 200,
			'idcliente' => 77,
			'resposta_minutos' => 9,
			'resolucao_minutos' => 9,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$p = $svc->resolveForTicket($this->ticket([
			'idempresa' => 10,
			'workflow_state_id' => 200,
			'idcliente' => 77,
		]));
		$this->assertSame($idCli, (int)$p->get('id'));
		$this->assertNotSame($idEmp, (int)$p->get('id'));
	}

	public function testTier6EmpresaOverTier7Global(): void {
		$idGlob = $this->savePolicy([
			'empresa_id' => null,
			'workflow_state_id' => 300,
			'resposta_minutos' => 100,
			'resolucao_minutos' => 100,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);
		$idEmp = $this->savePolicy([
			'empresa_id' => 20,
			'workflow_state_id' => 300,
			'resposta_minutos' => 50,
			'resolucao_minutos' => 50,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$p = $svc->resolveForTicket($this->ticket([
			'idempresa' => 20,
			'workflow_state_id' => 300,
		]));
		$this->assertSame($idEmp, (int)$p->get('id'));

		$p2 = $svc->resolveForTicket($this->ticket([
			'idempresa' => 99,
			'workflow_state_id' => 300,
		]));
		$this->assertSame($idGlob, (int)$p2->get('id'));
	}

	public function testWithinTierEmpresaSpecificBeatsGlobal(): void {
		$g = $this->savePolicy([
			'empresa_id' => null,
			'workflow_state_id' => 400,
			'contract_id' => 1,
			'resposta_minutos' => 1,
			'resolucao_minutos' => 1,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);
		$e = $this->savePolicy([
			'empresa_id' => 30,
			'workflow_state_id' => 400,
			'contract_id' => 1,
			'resposta_minutos' => 2,
			'resolucao_minutos' => 2,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$p = $svc->resolveForTicket($this->ticket([
			'idempresa' => 30,
			'workflow_state_id' => 400,
			'contract_id' => 1,
		]));
		$this->assertSame($e, (int)$p->get('id'));
		$this->assertNotSame($g, (int)$p->get('id'));
	}

	public function testScopePriorityWithinSameTier(): void {
		$highPri = $this->savePolicy([
			'empresa_id' => 40,
			'workflow_state_id' => 500,
			'contract_id' => 9,
			'scope_priority' => 50,
			'resposta_minutos' => 1,
			'resolucao_minutos' => 1,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);
		$lowPri = $this->savePolicy([
			'empresa_id' => 40,
			'workflow_state_id' => 500,
			'contract_id' => 9,
			'scope_priority' => 10,
			'resposta_minutos' => 2,
			'resolucao_minutos' => 2,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$p = $svc->resolveForTicket($this->ticket([
			'idempresa' => 40,
			'workflow_state_id' => 500,
			'contract_id' => 9,
		]));
		$this->assertSame($lowPri, (int)$p->get('id'));
		$this->assertSame(2, (int)$p->get('resposta_minutos'));
		$this->assertNotSame($highPri, (int)$p->get('id'));
	}

	public function testLegacyFallbackWhenNoScopedRowMatches(): void {
		$this->savePolicy([
			'empresa_id' => 1,
			'workflow_state_id' => 600,
			'idcliente' => 999,
			'resposta_minutos' => 7,
			'resolucao_minutos' => 70,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);
		$legacyId = $this->savePolicy([
			'empresa_id' => 1,
			'workflow_state_id' => 600,
			'resposta_minutos' => 3,
			'resolucao_minutos' => 30,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$p = $svc->resolveForTicket($this->ticket([
			'idempresa' => 1,
			'workflow_state_id' => 600,
			'idcliente' => 5,
		]));
		$this->assertSame($legacyId, (int)$p->get('id'));
		$this->assertSame(3, (int)$p->get('resposta_minutos'));
	}

	public function testTier3OverTier4(): void {
		$id4 = $this->savePolicy([
			'empresa_id' => 1,
			'workflow_state_id' => 700,
			'contract_id' => 5,
			'resposta_minutos' => 40,
			'resolucao_minutos' => 400,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);
		$id3 = $this->savePolicy([
			'empresa_id' => 1,
			'workflow_state_id' => 700,
			'contract_id' => 5,
			'contract_service_id' => 8,
			'resposta_minutos' => 30,
			'resolucao_minutos' => 300,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$p = $svc->resolveForTicket($this->ticket([
			'idempresa' => 1,
			'workflow_state_id' => 700,
			'contract_id' => 5,
			'contract_service_id' => 8,
		]));
		$this->assertSame($id3, (int)$p->get('id'));
		$this->assertSame(30, (int)$p->get('resposta_minutos'));
		$this->assertGreaterThan($id4, $id3);
	}

	public function testTier2OverTier3(): void {
		$id3 = $this->savePolicy([
			'empresa_id' => 1,
			'workflow_state_id' => 650,
			'contract_id' => 3,
			'contract_service_id' => 4,
			'resposta_minutos' => 300,
			'resolucao_minutos' => 3000,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);
		$id2 = $this->savePolicy([
			'empresa_id' => 1,
			'workflow_state_id' => 650,
			'contract_id' => 3,
			'contract_service_id' => 4,
			'problema_id' => 6,
			'resposta_minutos' => 200,
			'resolucao_minutos' => 2000,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$p = $svc->resolveForTicket($this->ticket([
			'idempresa' => 1,
			'workflow_state_id' => 650,
			'contract_id' => 3,
			'contract_service_id' => 4,
			'problema_id' => 6,
		]));
		$this->assertSame($id2, (int)$p->get('id'));
		$this->assertSame(200, (int)$p->get('resposta_minutos'));
		$this->assertNotSame($id3, (int)$p->get('id'));
	}

	public function testProblemaIdFromTicketUsesIdproblemaAlias(): void {
		$id = $this->savePolicy([
			'empresa_id' => 1,
			'workflow_state_id' => 660,
			'contract_id' => 1,
			'contract_service_id' => 1,
			'problema_id' => 42,
			'resposta_minutos' => 1,
			'resolucao_minutos' => 1,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$p = $svc->resolveForTicket($this->ticket([
			'idempresa' => 1,
			'workflow_state_id' => 660,
			'contract_id' => 1,
			'contract_service_id' => 1,
			'idproblema' => 42,
		]));
		$this->assertSame($id, (int)$p->get('id'));
	}

	public function testLevelInsteadOfQueueInTier1(): void {
		$id = $this->savePolicy([
			'empresa_id' => 1,
			'workflow_state_id' => 800,
			'contract_id' => 1,
			'contract_service_id' => 2,
			'problema_id' => 3,
			'support_level_id' => 9,
			'resposta_minutos' => 11,
			'resolucao_minutos' => 0,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$p = $svc->resolveForTicket($this->ticket([
			'idempresa' => 1,
			'workflow_state_id' => 800,
			'contract_id' => 1,
			'contract_service_id' => 2,
			'problema_id' => 3,
			'support_level_id' => 9,
		]));
		$this->assertSame($id, (int)$p->get('id'));
		$this->assertSame(11, (int)$p->get('resposta_minutos'));
	}

	public function testLegacyResolveDirectly(): void {
		$this->savePolicy([
			'empresa_id' => 2,
			'workflow_state_id' => 900,
			'resposta_minutos' => 5,
			'resolucao_minutos' => 50,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);
		$idG = $this->savePolicy([
			'empresa_id' => null,
			'workflow_state_id' => 900,
			'resposta_minutos' => 8,
			'resolucao_minutos' => 80,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
		]);

		$svc = new SlaPolicyResolverService($this->policies(), null);
		$e = $svc->legacyResolve(2, 900);
		$this->assertSame(5, (int)$e->get('resposta_minutos'));
		$g = $svc->legacyResolve(404, 900);
		$this->assertSame($idG, (int)$g->get('id'));
		$this->assertSame(8, (int)$g->get('resposta_minutos'));
	}

	protected static function _createSchema(): void {
		self::$conn->execute(
			'CREATE TABLE workflow_sla_policies (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				empresa_id INTEGER NULL,
				workflow_state_id INTEGER NOT NULL,
				idcliente INTEGER NULL,
				contract_id INTEGER NULL,
				contract_service_id INTEGER NULL,
				problema_id INTEGER NULL,
				queue_id INTEGER NULL,
				support_level_id INTEGER NULL,
				scope_priority INTEGER NULL,
				resposta_minutos INTEGER NULL,
				resolucao_minutos INTEGER NULL,
				pausa_sla INTEGER NOT NULL DEFAULT 0,
				is_final INTEGER NOT NULL DEFAULT 0,
				auto_escalar INTEGER NOT NULL DEFAULT 0,
				escalate_to_state_id INTEGER NULL,
				escalate_after_minutos INTEGER NULL,
				created_at TEXT NULL,
				updated_at TEXT NULL
			)'
		);
	}
}
