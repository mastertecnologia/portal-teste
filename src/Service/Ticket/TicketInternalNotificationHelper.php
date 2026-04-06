<?php
namespace App\Service\Ticket;

use App\Service\ClienteDomain\InfrastructureGuard;
use App\Service\ClienteDomain\PortalNotificationService;
use App\Utility\ClienteDomainEventType;
use Cake\Routing\Router;

/**
 * Espelha eventos já gravados em Notificacoes (legado) para portal_internal_notifications (sino).
 */
class TicketInternalNotificationHelper {

	/**
	 * @param object $ticket Entidade com id e idempresa
	 */
	public static function afterTicketAberto($ticket, int $actorRole, int $actorUserId): void {
		if (!InfrastructureGuard::isReady() || empty($ticket)) {
			return;
		}
		$idempresa = (int)($ticket->idempresa ?? 0);
		$tid = (int)($ticket->id ?? 0);
		if ($idempresa <= 0 || $tid <= 0) {
			return;
		}
		$staffIds = PortalNotificationService::staffUserIdsForEmpresa($idempresa);
		if ($actorRole === 0 && $actorUserId > 0) {
			$staffIds = array_values(array_filter($staffIds, function ($id) use ($actorUserId) {
				return (int)$id !== (int)$actorUserId;
			}));
		}
		if (empty($staffIds)) {
			return;
		}
		$actionUrl = Router::url(['controller' => 'Tickets', 'action' => 'edit', $tid], false);
		$msg = $actorRole === 1
			? 'Um cliente abriu um novo chamado.'
			: 'Novo ticket registrado na empresa.';
		PortalNotificationService::notifyUsers(
			$staffIds,
			ClienteDomainEventType::TICKET_ABERTO,
			'info',
			'Ticket #' . $tid,
			$msg,
			$actionUrl,
			'ticket',
			(string)$tid,
			['idticket' => $tid, 'idempresa' => $idempresa]
		);
	}

	/**
	 * @param object $ticket Entidade com id e idempresa
	 */
	public static function afterComentarioTicket($ticket, int $actorRole, int $actorUserId): void {
		if (!InfrastructureGuard::isReady() || empty($ticket)) {
			return;
		}
		$idempresa = (int)($ticket->idempresa ?? 0);
		$tid = (int)($ticket->id ?? 0);
		if ($idempresa <= 0 || $tid <= 0) {
			return;
		}
		$actionUrl = Router::url(['controller' => 'Tickets', 'action' => 'edit', $tid], false);
		if ($actorRole === 1) {
			PortalNotificationService::notifyEmpresaStaff(
				$idempresa,
				ClienteDomainEventType::TICKET_COMENTARIO,
				'info',
				'Novo comentário · Ticket #' . $tid,
				'O cliente adicionou um comentário.',
				$actionUrl,
				'ticket',
				(string)$tid,
				['idticket' => $tid]
			);

			return;
		}
		$staffIds = PortalNotificationService::staffUserIdsForEmpresa($idempresa);
		$staffIds = array_values(array_filter($staffIds, function ($id) use ($actorUserId) {
			return (int)$id !== (int)$actorUserId;
		}));
		if (empty($staffIds)) {
			return;
		}
		PortalNotificationService::notifyUsers(
			$staffIds,
			ClienteDomainEventType::TICKET_COMENTARIO,
			'info',
			'Comentário da equipe · Ticket #' . $tid,
			'Um técnico comentou no ticket.',
			$actionUrl,
			'ticket',
			(string)$tid,
			['idticket' => $tid]
		);
	}
}
