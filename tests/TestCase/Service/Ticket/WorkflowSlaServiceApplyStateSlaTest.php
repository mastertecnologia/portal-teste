<?php
declare(strict_types=1);
namespace App\Test\TestCase\Service\Ticket;

use App\Model\Table\TicketsTable;
use App\Service\Ticket\SlaService;
use App\Service\Ticket\WorkflowSlaService;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * applyStateSla alinhado ao SlaPolicyResolverService (mesma cadeia que escalateIfDue).
 */
class WorkflowSlaServiceApplyStateSlaTest extends TestCase {

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
		Configure::write('Workflow.workflowEnabled', true);
		Configure::write('Workflow.workflowSlaEnabled', true);
		Configure::delete('Workflow.enabledEmpresas');
		TableRegistry::clear();
		self::$conn->execute('DELETE FROM tickets');
		self::$conn->execute('DELETE FROM workflow_sla_policies');
	}

	protected function tearDown(): void {
		Configure::delete('Workflow.workflowEnabled');
		Configure::delete('Workflow.workflowSlaEnabled');
		parent::tearDown();
	}

	protected static function _createSchema(): void {
		self::$conn->execute(
			'CREATE TABLE tickets (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				prioridade TEXT,
				tipo_ticket TEXT,
				workflow_state_id INTEGER,
				queue_id INTEGER,
				idtecnico_responsavel INTEGER,
				owner_id INTEGER,
				problema_id INTEGER,
				idproblema INTEGER,
				contract_id INTEGER,
				contract_service_id INTEGER,
				idcliente INTEGER,
				support_level_id INTEGER,
				situacao INTEGER DEFAULT 1,
				sla_resolucao_pausado INTEGER DEFAULT 0,
				sla_resposta_pausado INTEGER DEFAULT 0,
				sla_resolucao_minutos INTEGER,
				sla_resposta_minutos INTEGER,
				data_limite_resposta TEXT,
				data_limite_resolucao TEXT,
				paused_at TEXT,
				sla_status TEXT
			)'
		);
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
				escalate_to_queue_id INTEGER NULL,
				escalate_to_support_level_id INTEGER NULL,
				notify_manager INTEGER NOT NULL DEFAULT 0,
				notify_customer INTEGER NOT NULL DEFAULT 0,
				notify_technician INTEGER NOT NULL DEFAULT 0
			)'
		);
	}

	protected function ticketsTable(): TicketsTable {
		return TableRegistry::getTableLocator()->get('Tickets', [
			'className' => TicketsTable::class,
			'table' => 'tickets',
		]);
	}

	protected function workflowPolicies() {
		return TableRegistry::getTableLocator()->get('WorkflowSlaPolicies', ['table' => 'workflow_sla_policies']);
	}

	/**
	 * @param array<string, mixed> $defaults
	 */
	protected function insertPolicy(array $defaults): int {
		$table = $this->workflowPolicies();
		$row = array_merge([
			'workflow_state_id' => 1,
			'pausa_sla' => false,
			'is_final' => false,
			'auto_escalar' => false,
			'resposta_minutos' => 5,
			'resolucao_minutos' => 50,
		], $defaults);
		$e = $table->newEntity($row, ['validate' => false]);
		$table->save($e, ['checkRules' => false]);
		if ($e->getErrors()) {
			self::fail(json_encode($e->getErrors()));
		}

		return (int)$e->get('id');
	}

	protected function wf(int $empresa, int $state, array $extra = []): WorkflowSlaService {
		$tickets = $this->ticketsTable();

		return new WorkflowSlaService($tickets, new SlaService($tickets));
	}

	public function testApplyStateSlaUsesGlobalPolicyForForeignEmpresa(): void {
		$idG = $this->insertPolicy([
			'empresa_id' => null,
			'workflow_state_id' => 700,
			'resolucao_minutos' => 801,
			'resposta_minutos' => 1,
		]);
		$t = new Entity([
			'idempresa' => 404,
			'workflow_state_id' => 700,
			'situacao' => 1,
		]);
		$wf = $this->wf(404, 700);
		$wf->applyStateSla($t, 404, 700);
		$this->assertSame(801, (int)$t->get('sla_resolucao_minutos'));
		$m = new ReflectionMethod(WorkflowSlaService::class, 'resolveEscalationPolicy');
		$m->setAccessible(true);
		$p = $m->invoke($wf, $t, 404, 700);
		$this->assertNotNull($p);
		$this->assertSame($idG, (int)$p->get('id'));
	}

	public function testApplyStateSlaUsesEmpresaTierPolicy(): void {
		$this->insertPolicy([
			'empresa_id' => null,
			'workflow_state_id' => 710,
			'resolucao_minutos' => 11,
		]);
		$idE = $this->insertPolicy([
			'empresa_id' => 3,
			'workflow_state_id' => 710,
			'resolucao_minutos' => 22,
			'resposta_minutos' => 2,
		]);
		$t = new Entity([
			'idempresa' => 3,
			'workflow_state_id' => 710,
			'situacao' => 1,
		]);
		$wf = $this->wf(3, 710);
		$wf->applyStateSla($t, 3, 710);
		$this->assertSame(22, (int)$t->get('sla_resolucao_minutos'));
		$m = new ReflectionMethod(WorkflowSlaService::class, 'resolveEscalationPolicy');
		$m->setAccessible(true);
		$this->assertSame($idE, (int)$m->invoke($wf, $t, 3, 710)->get('id'));
	}

	public function testApplyStateSlaUsesClientTierPolicy(): void {
		$this->insertPolicy([
			'empresa_id' => 4,
			'workflow_state_id' => 720,
			'resolucao_minutos' => 40,
		]);
		$idC = $this->insertPolicy([
			'empresa_id' => 4,
			'workflow_state_id' => 720,
			'idcliente' => 88,
			'resolucao_minutos' => 88,
			'resposta_minutos' => 8,
		]);
		$t = new Entity([
			'idempresa' => 4,
			'workflow_state_id' => 720,
			'idcliente' => 88,
			'situacao' => 1,
		]);
		$wf = $this->wf(4, 720);
		$wf->applyStateSla($t, 4, 720);
		$this->assertSame(88, (int)$t->get('sla_resolucao_minutos'));
		$m = new ReflectionMethod(WorkflowSlaService::class, 'resolveEscalationPolicy');
		$m->setAccessible(true);
		$this->assertSame($idC, (int)$m->invoke($wf, $t, 4, 720)->get('id'));
	}

	public function testApplyStateSlaUsesContractTierPolicy(): void {
		$this->insertPolicy([
			'empresa_id' => 5,
			'workflow_state_id' => 730,
			'resolucao_minutos' => 100,
		]);
		$idK = $this->insertPolicy([
			'empresa_id' => 5,
			'workflow_state_id' => 730,
			'contract_id' => 9,
			'resolucao_minutos' => 91,
		]);
		$t = new Entity([
			'idempresa' => 5,
			'workflow_state_id' => 730,
			'contract_id' => 9,
			'situacao' => 1,
		]);
		$wf = $this->wf(5, 730);
		$wf->applyStateSla($t, 5, 730);
		$this->assertSame(91, (int)$t->get('sla_resolucao_minutos'));
		$m = new ReflectionMethod(WorkflowSlaService::class, 'resolveEscalationPolicy');
		$m->setAccessible(true);
		$this->assertSame($idK, (int)$m->invoke($wf, $t, 5, 730)->get('id'));
	}

	public function testApplyStateSlaUsesMostSpecificContractServiceProblemTier1(): void {
		$this->insertPolicy([
			'empresa_id' => 6,
			'workflow_state_id' => 740,
			'contract_id' => 1,
			'contract_service_id' => 2,
			'problema_id' => 3,
			'resolucao_minutos' => 2000,
		]);
		$idT1 = $this->insertPolicy([
			'empresa_id' => 6,
			'workflow_state_id' => 740,
			'contract_id' => 1,
			'contract_service_id' => 2,
			'problema_id' => 3,
			'queue_id' => 50,
			'resolucao_minutos' => 111,
			'resposta_minutos' => 11,
		]);
		$t = new Entity([
			'idempresa' => 6,
			'workflow_state_id' => 740,
			'contract_id' => 1,
			'contract_service_id' => 2,
			'problema_id' => 3,
			'queue_id' => 50,
			'situacao' => 1,
		]);
		$wf = $this->wf(6, 740);
		$wf->applyStateSla($t, 6, 740);
		$this->assertSame(111, (int)$t->get('sla_resolucao_minutos'));
		$this->assertSame(11, (int)$t->get('sla_resposta_minutos'));
		$this->assertNotEmpty($t->get('data_limite_resolucao'));
		$m = new ReflectionMethod(WorkflowSlaService::class, 'resolveEscalationPolicy');
		$m->setAccessible(true);
		$this->assertSame($idT1, (int)$m->invoke($wf, $t, 6, 740)->get('id'));
	}

	public function testApplyStateSlaAndEscalateShareResolveEscalationPolicy(): void {
		$this->insertPolicy([
			'empresa_id' => 7,
			'workflow_state_id' => 750,
			'contract_id' => 5,
			'resolucao_minutos' => 1,
			'auto_escalar' => true,
			'escalate_after_minutos' => 0,
			'escalate_to_queue_id' => 99,
			'is_final' => false,
			'pausa_sla' => false,
		]);
		$idScoped = $this->insertPolicy([
			'empresa_id' => 7,
			'workflow_state_id' => 750,
			'contract_id' => 5,
			'contract_service_id' => 8,
			'problema_id' => 9,
			'resolucao_minutos' => 2,
			'auto_escalar' => true,
			'escalate_after_minutos' => 0,
			'escalate_to_queue_id' => 100,
			'is_final' => false,
			'pausa_sla' => false,
		]);
		$tickets = $this->ticketsTable();
		$row = $tickets->newEntity([
			'idempresa' => 7,
			'workflow_state_id' => 750,
			'contract_id' => 5,
			'contract_service_id' => 8,
			'problema_id' => 9,
			'queue_id' => 1,
			'situacao' => 1,
			'data_limite_resolucao' => (new Time('-1 day'))->format('Y-m-d H:i:s'),
		], ['validate' => false]);
		$tickets->save($row, ['checkRules' => false]);
		$ticket = $tickets->get((int)$row->get('id'));

		$wf = new WorkflowSlaService($tickets, new SlaService($tickets));
		$m = new ReflectionMethod(WorkflowSlaService::class, 'resolveEscalationPolicy');
		$m->setAccessible(true);
		$polEsc = $m->invoke($wf, $ticket, 7, 750);
		$this->assertSame($idScoped, (int)$polEsc->get('id'));

		$ticket2 = $tickets->get((int)$row->get('id'));
		$ticket2->set('data_limite_resposta', null);
		$ticket2->set('sla_resolucao_minutos', null);
		$ticket2->set('sla_resposta_minutos', null);
		$ticket2->set('data_limite_resolucao', null);
		$wf->applyStateSla($ticket2, 7, 750);
		$m2 = new ReflectionMethod(WorkflowSlaService::class, 'resolveEscalationPolicy');
		$m2->setAccessible(true);
		$polApply = $m2->invoke($wf, $ticket2, 7, 750);
		$this->assertSame((int)$polEsc->get('id'), (int)$polApply->get('id'));
		$this->assertSame(2, (int)$ticket2->get('sla_resolucao_minutos'));
	}

	public function testApplyStateSlaFallsBackWhenNoWorkflowPolicy(): void {
		$t = new Entity([
			'idempresa' => 1,
			'workflow_state_id' => 999,
			'situacao' => 1,
		]);
		$wf = $this->wf(1, 999);
		$out = $wf->applyStateSla($t, 1, 999);
		$this->assertIsArray($out);
	}
}
