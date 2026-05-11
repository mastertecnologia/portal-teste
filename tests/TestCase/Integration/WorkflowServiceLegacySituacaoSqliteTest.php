<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use App\Service\Ticket\WorkflowService;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Mapeamento workflow_states.codigo → tickets.situacao (auto-escalonamento / PATCH status).
 *
 * Excluído da suite principal (phpunit.xml dist exclui Integration/); correr:
 * ./vendor/bin/phpunit tests/TestCase/Integration/WorkflowServiceLegacySituacaoSqliteTest.php
 */
class WorkflowServiceLegacySituacaoSqliteTest extends TestCase {

	/** @var \Cake\Database\Connection */
	protected static $conn;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if (!extension_loaded('pdo_sqlite')) {
			self::markTestSkipped('Extensão pdo_sqlite necessária para este teste.');
		}
		$root = dirname(__DIR__, 3);
		require $root . '/vendor/autoload.php';
		require $root . '/config/paths.php';

		if (!defined('C_TicketSituacaoPendente')) {
			define('C_TicketSituacaoPendente', 11);
		}
		if (!defined('C_TicketSituacaoEmandamento')) {
			define('C_TicketSituacaoEmandamento', 22);
		}
		if (!defined('C_TicketSituacaoResolvido')) {
			define('C_TicketSituacaoResolvido', 33);
		}
		if (!defined('C_TicketSituacaoFechado')) {
			define('C_TicketSituacaoFechado', 44);
		}

		ConnectionManager::drop('default');
		ConnectionManager::config('default', [
			'className' => Connection::class,
			'driver' => Sqlite::class,
			'database' => ':memory:',
			'encoding' => 'utf8',
		]);
		self::$conn = ConnectionManager::get('default');
		self::$conn->execute('CREATE TABLE tickets (id INTEGER PRIMARY KEY)');
		self::$conn->execute('CREATE TABLE workflow_states (id INTEGER PRIMARY KEY, codigo VARCHAR(64), nome VARCHAR(128), is_final INTEGER NOT NULL DEFAULT 0, is_inicial INTEGER NOT NULL DEFAULT 0)');
		self::$conn->insert('workflow_states', [
			'id' => 2,
			'codigo' => 'emandamento',
			'nome' => 'Em andamento',
			'is_final' => 0,
			'is_inicial' => 0,
		]);
		self::$conn->insert('workflow_states', [
			'id' => 3,
			'codigo' => 'pendente',
			'nome' => 'Pendente',
			'is_final' => 0,
			'is_inicial' => 0,
		]);
		self::$conn->insert('workflow_states', [
			'id' => 4,
			'codigo' => 'aguardando_cliente',
			'nome' => 'Aguardando Cliente',
			'is_final' => 0,
			'is_inicial' => 0,
		]);
		TableRegistry::clear();
	}

	public function testLegacyPendenteMapsToConstant(): void {
		$tickets = TableRegistry::get('Tickets', ['table' => 'tickets']);
		$w = new WorkflowService($tickets);
		$this->assertSame((int)C_TicketSituacaoPendente, $w->legacySituacaoForWorkflowStateId(3));
	}

	public function testLegacyEmAndamentoMapsToConstant(): void {
		$tickets = TableRegistry::get('Tickets', ['table' => 'tickets']);
		$w = new WorkflowService($tickets);
		$this->assertSame((int)C_TicketSituacaoEmandamento, $w->legacySituacaoForWorkflowStateId(2));
	}

	public function testLegacyAguardandoClienteMapsToPendente(): void {
		$tickets = TableRegistry::get('Tickets', ['table' => 'tickets']);
		$w = new WorkflowService($tickets);
		$this->assertSame((int)C_TicketSituacaoPendente, $w->legacySituacaoForWorkflowStateId(4));
	}

	public function testLegacyUnknownIdReturnsNull(): void {
		$tickets = TableRegistry::get('Tickets', ['table' => 'tickets']);
		$w = new WorkflowService($tickets);
		$this->assertNull($w->legacySituacaoForWorkflowStateId(0));
		$this->assertNull($w->legacySituacaoForWorkflowStateId(999));
	}
}
