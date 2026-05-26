<?php
namespace App\Test\TestCase\Service\Ticket;

use App\Service\Ticket\WorkflowService;
use Cake\TestSuite\TestCase;

class WorkflowServiceLegacyCodigoTest extends TestCase {

	public function testLegacyMappedCodigos(): void {
		$svc = new WorkflowService($this->getMockBuilder(\Cake\ORM\Table::class)->disableOriginalConstructor()->getMock());
		$this->assertTrue($svc->isLegacySituacaoMappedCodigo('emandamento'));
		$this->assertTrue($svc->isLegacySituacaoMappedCodigo('resolvido'));
		$this->assertFalse($svc->isLegacySituacaoMappedCodigo('aguardando_fornecedor'));
	}

}
