<?php
namespace App\Service\ClienteDomain;

/**
 * Ponto único de entrada para eventos do domínio cliente (retrocompatível: falhas não propagam).
 */
class ClienteDomainBridge {

	/**
	 * @param array $context idcliente?, idempresa, actor_user_id?, title, message, action_url?, entity_type?, entity_id?, metadata?
	 */
	public static function emit(string $eventType, array $context): void {
		try {
			if (!InfrastructureGuard::isReady()) {
				return;
			}
			$idcliente = (int)($context['idcliente'] ?? 0);
			$idempresa = (int)($context['idempresa'] ?? 0);
			$actorUserId = isset($context['actor_user_id']) ? (int)$context['actor_user_id'] : null;
			$title = (string)($context['title'] ?? '');
			$message = (string)($context['message'] ?? '');
			$actionUrl = isset($context['action_url']) ? (string)$context['action_url'] : null;
			$entityType = isset($context['entity_type']) ? (string)$context['entity_type'] : null;
			$entityId = $context['entity_id'] ?? null;
			$metadata = isset($context['metadata']) && is_array($context['metadata']) ? $context['metadata'] : [];

			if ($title === '') {
				$title = $eventType;
			}

			$notifType = PortalNotificationService::mapEventToNotifType($eventType);

			if ($idcliente > 0) {
				ClientEventRecorder::record($idcliente, $eventType, $message, $actorUserId, $metadata);
			}

			if ($idempresa > 0) {
				PortalNotificationService::notifyEmpresaStaff(
					$idempresa,
					$eventType,
					$notifType,
					$title,
					$message,
					$actionUrl,
					$entityType,
					$entityId,
					$metadata
				);
				$userIds = PortalNotificationService::staffUserIdsForEmpresa($idempresa);
				$subject = '[Portal PGM] ' . $title;
				$html = MailAutomationService::buildHtmlBody($title, $message);
				MailAutomationService::notifyUsersIfEnabled($userIds, $eventType, $subject, $html);
			}
		} catch (\Throwable $e) {
			\Cake\Log\Log::warning('ClienteDomainBridge::emit ' . $e->getMessage());
		}
	}
}
