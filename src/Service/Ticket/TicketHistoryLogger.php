<?php
namespace App\Service\Ticket;

use Cake\I18n\Time;
use Cake\ORM\Table;

/**
 * Grava eventos em ticket_histories (falha silenciosa se tabela indisponível).
 */
class TicketHistoryLogger {

	public static function log(
		Table $historiesTable,
		int $ticketId,
		?int $usuarioId,
		string $tipoEvento,
		?string $valorAnterior,
		?string $valorNovo,
		?string $descricao,
		string $origemEvento = 'usuario'
	): void {
		try {
			$row = $historiesTable->newEntity([
				'ticket_id' => $ticketId,
				'usuario_id' => $usuarioId,
				'tipo_evento' => $tipoEvento,
				'valor_anterior' => $valorAnterior,
				'valor_novo' => $valorNovo,
				'descricao' => $descricao,
				'origem_evento' => $origemEvento,
				'created' => Time::now(),
			]);
			$historiesTable->save($row);
		} catch (\Throwable $e) {
		}
	}
}
