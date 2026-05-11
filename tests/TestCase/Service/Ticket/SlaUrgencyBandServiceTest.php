<?php
declare(strict_types=1);
namespace App\Test\TestCase\Service\Ticket;

use App\Service\Ticket\SlaUrgencyBandService;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Banda de urgência na listagem técnica (payload SLA).
 */
class SlaUrgencyBandServiceTest extends TestCase {

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
		self::$conn->execute(
			'CREATE TABLE tickets (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL DEFAULT 1,
				situacao INTEGER NOT NULL DEFAULT 11,
				created TEXT,
				sla_status TEXT,
				sla_percentual_consumido REAL,
				sla_resolucao_pausado INTEGER NOT NULL DEFAULT 0,
				sla_resposta_pausado INTEGER NOT NULL DEFAULT 0,
				sla_resolucao_minutos INTEGER,
				sla_resposta_minutos INTEGER,
				data_limite_resolucao TEXT,
				data_limite_resposta TEXT,
				data_primeira_resposta TEXT,
				data_resolucao TEXT
			)'
		);
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
		self::$conn->execute('DELETE FROM tickets');
		Time::setTestNow(new Time('2026-05-11 14:00:00'));
	}

	protected function tearDown(): void {
		Time::setTestNow(null);
		parent::tearDown();
	}

	/**
	 * @param array<string, mixed> $row
	 * @return \Cake\ORM\Entity
	 */
	protected function entityFromRow(array $row): Entity {
		$e = new Entity($row + ['idempresa' => 1, 'situacao' => (int)C_TicketSituacaoPendente]);
		$e->setSource('Tickets');

		return $e;
	}

	/** @return string[] */
	protected function cols(): array {
		return [
			'sla_status',
			'sla_percentual_consumido',
			'sla_resolucao_pausado',
			'sla_resposta_pausado',
			'sla_resolucao_minutos',
			'sla_resposta_minutos',
			'data_limite_resolucao',
			'data_limite_resposta',
			'data_primeira_resposta',
			'data_resolucao',
			'created',
			'situacao',
		];
	}

	public function testPausedBand(): void {
		$svc = new SlaUrgencyBandService();
		$t = $this->entityFromRow([
			'sla_status' => 'em_risco',
			'sla_percentual_consumido' => 85,
			'sla_resolucao_pausado' => true,
			'created' => '2026-05-11 10:00:00',
			'sla_resolucao_minutos' => 120,
			'data_limite_resolucao' => '2026-05-11 16:00:00',
		]);
		$out = $svc->buildListFragment($t, $this->cols());
		$this->assertSame('paused', $out['sla_urgency_band']);
		$this->assertTrue((bool)$out['sla_resolucao_pausado']);
	}

	public function testViolatedBand(): void {
		$svc = new SlaUrgencyBandService();
		$t = $this->entityFromRow([
			'sla_status' => 'violado',
			'sla_resolucao_pausado' => false,
			'created' => '2026-05-11 08:00:00',
			'sla_resolucao_minutos' => 60,
			'data_limite_resolucao' => '2026-05-11 12:30:00',
		]);
		$out = $svc->buildListFragment($t, $this->cols());
		$this->assertSame('violated', $out['sla_urgency_band']);
	}

	public function testAttentionFromHighConsumption(): void {
		$svc = new SlaUrgencyBandService();
		$t = $this->entityFromRow([
			'sla_status' => 'em_risco',
			'sla_resolucao_pausado' => false,
			'created' => '2026-05-11 12:55:00',
			'sla_resolucao_minutos' => 80,
			'data_limite_resolucao' => '2026-05-11 20:00:00',
		]);
		$out = $svc->buildListFragment($t, $this->cols());
		$this->assertSame('attention', $out['sla_urgency_band']);
	}

	public function testCriticalNearDeadline(): void {
		$svc = new SlaUrgencyBandService();
		$t = $this->entityFromRow([
			'sla_status' => 'dentro_sla',
			'sla_resolucao_pausado' => false,
			'created' => '2026-05-11 13:00:00',
			'sla_resolucao_minutos' => 120,
			'data_limite_resolucao' => '2026-05-11 14:10:00',
		]);
		$out = $svc->buildListFragment($t, $this->cols());
		$this->assertSame('critical', $out['sla_urgency_band']);
	}
}
