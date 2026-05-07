<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility\Ticket;

use App\Utility\Ticket\SlaEscalationBatch;
use PHPUnit\Framework\TestCase;

/**
 * Garante utilitário de batch de escalação e filtro de situações fechadas.
 */
class SlaEscalationBatchTest extends TestCase {

	public function testClosedSituacoesReturnsUniqueInts(): void {
		$list = SlaEscalationBatch::closedSituacoes();
		$this->assertIsArray($list);
		$uniq = array_unique($list);
		$this->assertSameSize($list, $uniq);
		foreach ($list as $v) {
			$this->assertIsInt($v);
		}
	}
}
