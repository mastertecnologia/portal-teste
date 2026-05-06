<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Ticket\TicketPriorityKpi;
use PHPUnit\Framework\TestCase;

class TicketPriorityKpiTest extends TestCase {

	public function testMapCriticaVariantsToP1() {
		$this->assertSame('P1', TicketPriorityKpi::mapToPxBucket('critica'));
		$this->assertSame('P1', TicketPriorityKpi::mapToPxBucket('CRITICA'));
		$this->assertSame('P1', TicketPriorityKpi::mapToPxBucket('crítica'));
		$this->assertSame('P1', TicketPriorityKpi::mapToPxBucket('critico'));
	}

	public function testMapSemanticToP2P3P4() {
		$this->assertSame('P2', TicketPriorityKpi::mapToPxBucket('alta'));
		$this->assertSame('P3', TicketPriorityKpi::mapToPxBucket('media'));
		$this->assertSame('P4', TicketPriorityKpi::mapToPxBucket('baixa'));
	}

	public function testP1MatchOrConditionsShape() {
		$w = TicketPriorityKpi::p1MatchOrConditions('Tickets.prioridade');
		$this->assertArrayHasKey('OR', $w);
		$this->assertNotEmpty($w['OR']);
	}
}
