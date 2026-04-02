<?php
namespace App\Service\PortalAdvanced;

use App\Service\ClienteDomain\PortalNotificationService;

/**
 * Notificações in-app para staff sobre eventos do módulo avançado (faturas, contratos, etc.).
 * Reutiliza PortalNotificationService / portal_internal_notifications.
 */
class AdvancedNotificationService {

	/**
	 * @param string $eventType Chave estável (ex.: portal_advanced.invoice_generated)
	 */
	public static function notifyEmpresaStaff(
		int $idempresa,
		string $eventType,
		string $title,
		string $message,
		?string $actionUrl = null,
		?string $entityType = null,
		$entityId = null,
		array $metadata = []
	): void {
		if ($idempresa <= 0) {
			return;
		}
		PortalNotificationService::notifyEmpresaStaff(
			$idempresa,
			$eventType,
			'info',
			$title,
			$message,
			$actionUrl,
			$entityType,
			$entityId,
			$metadata
		);
	}
}
