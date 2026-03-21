<?php
namespace App\Service\Ticket;

/**
 * Máquina de estados enterprise (aberto, triagem, em_atendimento, …) — transição gradual a partir de situacao legada.
 * Expanda aqui validações; mantenha TicketsController enxuto.
 */
class TicketWorkflowService {

	/** Status canônicos alvo (string); mapear para tickets.situacao em camada futura. */
	public const STATUS_ABERTO = 'aberto';
	public const STATUS_TRIAGEM = 'triagem';
	public const STATUS_EM_ATENDIMENTO = 'em_atendimento';
	public const STATUS_AGUARDANDO_CLIENTE = 'aguardando_cliente';
	public const STATUS_RESOLVIDO = 'resolvido';
	public const STATUS_FECHADO = 'fechado';
	public const STATUS_CANCELADO = 'cancelado';

	/**
	 * @param string $from Status enterprise atual
	 * @param string $to   Status desejado
	 */
	public function canTransition(string $from, string $to): bool {
		$allowed = [
			self::STATUS_ABERTO => [self::STATUS_TRIAGEM, self::STATUS_CANCELADO],
			self::STATUS_TRIAGEM => [self::STATUS_EM_ATENDIMENTO, self::STATUS_CANCELADO],
			self::STATUS_EM_ATENDIMENTO => [
				self::STATUS_AGUARDANDO_CLIENTE,
				self::STATUS_RESOLVIDO,
				self::STATUS_CANCELADO,
			],
			self::STATUS_AGUARDANDO_CLIENTE => [self::STATUS_EM_ATENDIMENTO, self::STATUS_RESOLVIDO, self::STATUS_FECHADO],
			self::STATUS_RESOLVIDO => [self::STATUS_FECHADO, self::STATUS_EM_ATENDIMENTO],
			self::STATUS_FECHADO => [],
			self::STATUS_CANCELADO => [],
		];

		return in_array($to, $allowed[$from] ?? [], true);
	}
}
