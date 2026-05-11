<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility\Ticket;

use App\Utility\Ticket\TicketAssignmentJson;
use Cake\TestSuite\TestCase;

/**
 * Invariantes documentadas para atribuição Service Desk (payload JSON + regra empresa/fila).
 */
class TicketAssignmentJsonTest extends TestCase {

	public function testDestinoSemVinculoPayloadShape(): void {
		$p = TicketAssignmentJson::destinoSemVinculoFilaPayload();
		$this->assertFalse($p['ok']);
		$this->assertSame('destino_sem_vinculo_fila', $p['error']);
		$this->assertArrayHasKey('message', $p);
		$this->assertStringContainsString('Filas → Técnicos', (string)$p['message']);
		$this->assertArrayNotHasKey('queue_name', $p);
	}

	public function testDestinoSemVinculoIncludesQueueNameWhenProvided(): void {
		$p = TicketAssignmentJson::destinoSemVinculoFilaPayload('N2 Suporte');
		$this->assertSame('N2 Suporte', $p['queue_name']);
	}

	public function testQueueBelongsToTicketEmpresaPositive(): void {
		$q = new \stdClass();
		$q->idempresa = 5;
		$this->assertTrue(TicketAssignmentJson::queueBelongsToTicketEmpresa($q, 5));
	}

	public function testQueueBelongsToTicketEmpresaNegative(): void {
		$q = new \stdClass();
		$q->idempresa = 3;
		$this->assertFalse(TicketAssignmentJson::queueBelongsToTicketEmpresa($q, 5));
		$this->assertFalse(TicketAssignmentJson::queueBelongsToTicketEmpresa(null, 5));
	}

	/**
	 * Documentação: apiPatchAssignment / apiTransferirTicket não devem voltar destino_nivel_incompativel
	 * (escopo de regressão — busca no fonte).
	 */
	public function testTicketsControllerHasNoDestinoNivelIncompativel(): void {
		$path = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'TicketsController.php';
		$this->assertFileExists($path);
		$src = (string)file_get_contents($path);
		$this->assertStringNotContainsString('destino_nivel_incompativel', $src);
	}
}
