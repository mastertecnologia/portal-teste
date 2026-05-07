<?php
declare(strict_types=1);
namespace App\Test\TestCase\Service\Ticket;

use App\Model\Table\TicketsTable;
use App\Service\Ticket\SlaRecalculationService;
use App\Service\Ticket\SlaService;
use App\Service\Ticket\TicketSlaCycleService;
use App\Service\Ticket\TicketSlaFlowService;
use App\Service\Ticket\WorkflowSlaService;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Lacunas técnicas: overdue idempotente, retomada↔ciclo, resolução de política de escalação, fluxo SLA.
 */
class TicketSlaTechnicalFixesTest extends TestCase {

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

		if (!defined('C_TicketSituacaoResolvido')) {
			define('C_TicketSituacaoResolvido', 3);
		}
		if (!defined('C_TicketSituacaoFechado')) {
			define('C_TicketSituacaoFechado', 4);
		}
		if (!defined('C_TicketSituacaoCancelado')) {
			define('C_TicketSituacaoCancelado', 5);
		}

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
		self::$conn->execute('DELETE FROM ticket_sla_events');
		self::$conn->execute('DELETE FROM ticket_sla_cycles');
		self::$conn->execute('DELETE FROM tickets');
		self::$conn->execute('DELETE FROM workflow_sla_policies');
	}

	protected static function _createSchema(): void {
		self::$conn->execute(
			'CREATE TABLE tickets (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
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
				data_primeira_resposta TEXT,
				data_resolucao TEXT,
				created TEXT,
				sla_status TEXT,
				sla_percentual_consumido REAL,
				sla_resolucao_pausado INTEGER DEFAULT 0,
				sla_resolucao_minutos INTEGER,
				sla_resposta_minutos INTEGER,
				data_limite_resposta TEXT,
				data_limite_resolucao TEXT
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
				notify_technician INTEGER NOT NULL DEFAULT 0,
				created_at TEXT NULL,
				updated_at TEXT NULL
			)'
		);
		self::$conn->execute(
			'CREATE TABLE ticket_sla_cycles (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				ticket_id INTEGER NOT NULL,
				idempresa INTEGER NOT NULL,
				cycle_number INTEGER NOT NULL DEFAULT 1,
				workflow_state_id INTEGER NULL,
				workflow_sla_policy_id INTEGER NULL,
				idcliente INTEGER NULL,
				contract_id INTEGER NULL,
				contract_service_id INTEGER NULL,
				problema_id INTEGER NULL,
				queue_id INTEGER NULL,
				support_level_id INTEGER NULL,
				sla_resposta_minutos INTEGER NULL,
				sla_resolucao_minutos INTEGER NULL,
				data_limite_resposta TEXT NULL,
				data_limite_resolucao TEXT NULL,
				deadline_at TEXT NULL,
				total_paused_seconds INTEGER NOT NULL DEFAULT 0,
				started_at TEXT NOT NULL,
				ended_at TEXT NULL,
				metadata TEXT NULL,
				created_at TEXT NULL,
				updated_at TEXT NULL
			)'
		);
		self::$conn->execute(
			'CREATE TABLE ticket_sla_events (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				ticket_id INTEGER NOT NULL,
				idempresa INTEGER NOT NULL,
				ticket_sla_cycle_id INTEGER NULL,
				event_type VARCHAR(64) NOT NULL,
				source VARCHAR(32) NULL,
				workflow_sla_policy_id INTEGER NULL,
				payload TEXT NULL,
				created_by_user_id INTEGER NULL,
				created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
			)'
		);
	}

	protected function ticketsTable(): TicketsTable {
		return TableRegistry::getTableLocator()->get('Tickets', [
			'className' => TicketsTable::class,
			'table' => 'tickets',
		]);
	}

	public function testMarkOverdueEmitsAtMostOneSlaMarkedOverduePerCycle(): void {
		$tickets = $this->ticketsTable();
		$e = $tickets->newEntity([
			'idempresa' => 1,
			'workflow_state_id' => 10,
			'created' => Time::now()->format('Y-m-d H:i:s'),
		], ['validate' => false]);
		$tickets->save($e, ['checkRules' => false]);
		$tid = (int)$e->get('id');

		$cycleSvc = new TicketSlaCycleService($tickets);
		$cycleSvc->startCycle($e, [
			'user_id' => null,
			'workflow_sla_policy_id' => null,
			'metadata' => ['trigger' => 'test'],
		]);

		$this->assertTrue($cycleSvc->markOverdue($tid, 1, null, null, ['t' => 1]));
		$this->assertTrue($cycleSvc->markOverdue($tid, 1, null, null, ['t' => 2]));

		$ev = TableRegistry::get('TicketSlaEvents');
		$n = $ev->find()->where([
			'ticket_id' => $tid,
			'event_type' => TicketSlaCycleService::EVENT_SLA_MARKED_OVERDUE,
		])->count();
		$this->assertSame(1, $n);
	}

	public function testResumeSyncCopiesDeadlinesToCycleAndLogsSlaResumed(): void {
		$tickets = $this->ticketsTable();
		$row = $tickets->newEntity([
			'idempresa' => 1,
			'workflow_state_id' => 10,
			'created' => Time::now()->format('Y-m-d H:i:s'),
			'data_limite_resposta' => '2020-01-01 10:00:00',
			'data_limite_resolucao' => '2020-01-02 10:00:00',
		], ['validate' => false]);
		$tickets->save($row, ['checkRules' => false]);
		$tid = (int)$row->get('id');

		$cycleSvc = new TicketSlaCycleService($tickets);
		$cycleSvc->startCycle($row, ['metadata' => ['trigger' => 't']]);
		$cycles = TableRegistry::get('TicketSlaCycles');
		$c = $cycles->find()->where(['ticket_id' => $tid])->order(['id' => 'DESC'])->first();
		$this->assertNotNull($c);
		$cycleSvc->pauseCycle($tid, 1, null);
		$this->assertTrue($cycleSvc->resumeCycle($tid, 1, null));

		$row->set('data_limite_resposta', '2020-01-01 12:00:00');
		$row->set('data_limite_resolucao', '2020-01-03 10:00:00');
		$this->assertTrue($cycleSvc->syncOpenCycleAfterResumeFromTicket($row, 99));

		$c = $cycles->get((int)$c->get('id'));
		$this->assertSame('2020-01-03 10:00:00', (string)$c->get('data_limite_resolucao'));
		$this->assertSame('2020-01-03 10:00:00', (string)$c->get('deadline_at'));
		$this->assertGreaterThanOrEqual(0, (int)$c->get('total_paused_seconds'));

		$ev = TableRegistry::get('TicketSlaEvents');
		$n = $ev->find()->where([
			'ticket_id' => $tid,
			'event_type' => TicketSlaCycleService::EVENT_SLA_RESUMED,
		])->count();
		$this->assertSame(1, $n);
	}

	public function testResolveEscalationPolicyPrefersContractScopedRow(): void {
		self::$conn->execute(
			'INSERT INTO workflow_sla_policies (id, empresa_id, workflow_state_id, contract_id, resposta_minutos, resolucao_minutos, pausa_sla, is_final, auto_escalar, escalate_after_minutos, escalate_to_queue_id)
			VALUES (1, 1, 100, NULL, 1, 1, 0, 0, 1, 999999, 1)'
		);
		self::$conn->execute(
			'INSERT INTO workflow_sla_policies (id, empresa_id, workflow_state_id, contract_id, resposta_minutos, resolucao_minutos, pausa_sla, is_final, auto_escalar, escalate_after_minutos, escalate_to_queue_id)
			VALUES (2, 1, 100, 7, 2, 2, 0, 0, 1, 0, 2)'
		);

		$tickets = $this->ticketsTable();
		$svc = new WorkflowSlaService($tickets, new SlaService($tickets));
		$ticket = new \Cake\ORM\Entity([
			'idempresa' => 1,
			'workflow_state_id' => 100,
			'contract_id' => 7,
		]);
		$m = new ReflectionMethod(WorkflowSlaService::class, 'resolveEscalationPolicy');
		$m->setAccessible(true);
		/** @var \Cake\Datasource\EntityInterface $p */
		$p = $m->invoke($svc, $ticket, 1, 100);
		$this->assertNotNull($p);
		$this->assertSame(2, (int)$p->get('id'));
		$this->assertSame(0, (int)$p->get('escalate_after_minutos'));
	}

	public function testResolveEscalationPolicyPrefersResolverOverNarrowLegacyFirstRow(): void {
		// findPolicy(empresa, estado) = menor id entre linhas da empresa — pode ser política só-contrato
		// que o ticket sem contrato não deveria usar; o resolver deve devolver a linha "empresa genérica".
		self::$conn->execute(
			'INSERT INTO workflow_sla_policies (id, empresa_id, workflow_state_id, contract_id, resposta_minutos, resolucao_minutos, pausa_sla, is_final, auto_escalar, escalate_after_minutos, escalate_to_queue_id)
			VALUES (1, 1, 300, 7, 1, 1, 0, 0, 1, 0, 81)'
		);
		self::$conn->execute(
			'INSERT INTO workflow_sla_policies (id, empresa_id, workflow_state_id, contract_id, resposta_minutos, resolucao_minutos, pausa_sla, is_final, auto_escalar, escalate_after_minutos, escalate_to_queue_id)
			VALUES (2, 1, 300, NULL, 2, 2, 0, 0, 1, 0, 82)'
		);

		$tickets = $this->ticketsTable();
		$svc = new WorkflowSlaService($tickets, new SlaService($tickets));
		$ticket = new \Cake\ORM\Entity([
			'idempresa' => 1,
			'workflow_state_id' => 300,
		]);
		$m = new ReflectionMethod(WorkflowSlaService::class, 'resolveEscalationPolicy');
		$m->setAccessible(true);
		$p = $m->invoke($svc, $ticket, 1, 300);
		$this->assertNotNull($p);
		$this->assertSame(2, (int)$p->get('id'));
		$this->assertSame(82, (int)$p->get('escalate_to_queue_id'));

		$m2 = new ReflectionMethod(WorkflowSlaService::class, 'findPolicy');
		$m2->setAccessible(true);
		$legacyFirst = $m2->invoke($svc, 1, 300);
		$this->assertNotNull($legacyFirst);
		$this->assertSame(1, (int)$legacyFirst->get('id'), 'sanitidade: findPolicy legado continua a escolher o menor id');
	}

	public function testAfterSaveTicketSlaFlowDoesNotStartSecondOpenCycleOnUnchangedContext(): void {
		$tickets = $this->ticketsTable();
		$row = $tickets->newEntity([
			'idempresa' => 1,
			'workflow_state_id' => 10,
			'queue_id' => 5,
			'situacao' => 1,
			'created' => Time::now()->format('Y-m-d H:i:s'),
		], ['validate' => false]);
		$tickets->save($row, ['checkRules' => false]);
		$tid = (int)$row->get('id');

		$flow = new TicketSlaFlowService($tickets);
		$opts = new \ArrayObject([
			'_slaWasNew' => true,
			'_slaPrev' => null,
		]);
		$row = $tickets->get($tid);
		$flow->afterTicketSaved($row, $opts);

		$cycles = TableRegistry::get('TicketSlaCycles');
		$this->assertSame(1, $cycles->find()->where(['ticket_id' => $tid, 'ended_at IS' => null])->count());

		$prev = $tickets->get($tid, [
			'fields' => ['id', 'idempresa', 'workflow_state_id', 'queue_id', 'idtecnico_responsavel', 'owner_id', 'problema_id', 'idproblema', 'contract_id', 'contract_service_id', 'data_primeira_resposta', 'situacao'],
		]);
		$row2 = $tickets->get($tid);
		$row2->set('sla_status', 'em_risco');
		$tickets->save($row2, ['fields' => ['sla_status']]);

		$opts2 = new \ArrayObject([
			'_slaWasNew' => false,
			'_slaPrev' => $prev,
		]);
		$row2 = $tickets->get($tid);
		$flow->afterTicketSaved($row2, $opts2);

		$this->assertSame(1, $cycles->find()->where(['ticket_id' => $tid, 'ended_at IS' => null])->count());
	}

	public function testSlaRecalculateDoesNotDuplicateOverdueEventOnSecondRun(): void {
		$tickets = $this->ticketsTable();
		$past = (new Time('-2 days'))->format('Y-m-d H:i:s');
		$row = $tickets->newEntity([
			'idempresa' => 1,
			'workflow_state_id' => 10,
			'situacao' => 1,
			'created' => (new Time('-5 days'))->format('Y-m-d H:i:s'),
			'sla_resolucao_minutos' => 60,
			'sla_resposta_minutos' => 60,
			'data_limite_resolucao' => $past,
			'sla_status' => 'dentro_sla',
			'sla_percentual_consumido' => 0,
		], ['validate' => false]);
		$tickets->save($row, ['checkRules' => false]);
		$tid = (int)$row->get('id');

		$cycleSvc = new TicketSlaCycleService($tickets);
		$cycleSvc->startCycle($row, ['metadata' => ['trigger' => 't']]);

		$recalc = new SlaRecalculationService($tickets);
		$row = $tickets->get($tid);
		$this->assertTrue($recalc->recalculateOne($row, $tickets->getSchema()->columns()));
		$row = $tickets->get($tid);
		$this->assertTrue($recalc->recalculateOne($row, $tickets->getSchema()->columns()));

		$ev = TableRegistry::get('TicketSlaEvents');
		$n = $ev->find()->where([
			'ticket_id' => $tid,
			'event_type' => TicketSlaCycleService::EVENT_SLA_MARKED_OVERDUE,
		])->count();
		$this->assertSame(1, $n);
	}
}
