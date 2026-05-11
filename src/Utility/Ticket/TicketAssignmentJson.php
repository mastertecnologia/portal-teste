<?php
declare(strict_types=1);

namespace App\Utility\Ticket;

/**
 * Respostas JSON estáveis para atribuição/transferência de tickets (Service Desk).
 * Mantém texto acionável alinhado à UI React.
 */
final class TicketAssignmentJson {

	/**
	 * Payload para erro de vínculo técnico ↔ fila (queues_users).
	 *
	 * @param string|null $queueName Nome amigável da fila, se conhecido
	 * @return array<string, mixed>
	 */
	public static function destinoSemVinculoFilaPayload(?string $queueName = null): array {
		$out = [
			'ok' => false,
			'error' => 'destino_sem_vinculo_fila',
			'message' => 'O técnico selecionado não está vinculado à fila escolhida. Cadastre o técnico na fila em Filas → Técnicos e tente novamente.',
		];
		$qn = $queueName !== null ? trim($queueName) : '';
		if ($qn !== '') {
			$out['queue_name'] = $qn;
		}

		return $out;
	}

	/**
	 * Invariante: fila resolvida para o ticket deve pertencer à mesma empresa do ticket.
	 *
	 * @param object|array|null $queue Entidade Queue ou objeto com idempresa
	 */
	public static function queueBelongsToTicketEmpresa($queue, int $ticketEmpresaId): bool {
		if ($queue === null) {
			return false;
		}
		$eid = 0;
		if (is_array($queue)) {
			$eid = (int)($queue['idempresa'] ?? 0);
		} elseif (is_object($queue)) {
			$eid = (int)($queue->idempresa ?? 0);
		}

		return $eid > 0 && $eid === $ticketEmpresaId;
	}
}
