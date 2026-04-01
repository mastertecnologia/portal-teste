<?php
namespace App\Service\ClienteDomain;

use App\Utility\ClienteDomainEventType;
use Cake\ORM\TableRegistry;

class PortalNotificationService {

	/**
	 * Notifica usuários staff (role 0) da empresa sobre um evento de domínio cliente.
	 */
	public static function notifyEmpresaStaff(
		int $idempresa,
		string $eventType,
		string $notifType,
		string $title,
		string $message,
		?string $actionUrl = null,
		?string $entityType = null,
		$entityId = null,
		array $metadata = []
	): void {
		if (!InfrastructureGuard::isReady() || $idempresa <= 0) {
			return;
		}
		try {
			$userIds = self::_staffUserIdsForEmpresa($idempresa);
			if (empty($userIds)) {
				return;
			}
			$Notif = TableRegistry::get('PortalInternalNotifications');
			$metaJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
			$entityIdStr = $entityId !== null && $entityId !== '' ? (string)$entityId : null;

			foreach ($userIds as $uid) {
				if (!self::_userWantsInApp((int)$uid, $eventType)) {
					continue;
				}
				$e = $Notif->newEntity([
					'user_id' => (int)$uid,
					'type' => $notifType,
					'title' => $title,
					'message' => $message,
					'entity_type' => $entityType,
					'entity_id' => $entityIdStr,
					'action_url' => $actionUrl,
					'is_read' => 0,
					'metadata_json' => $metaJson,
				]);
				$Notif->save($e);
			}
		} catch (\Throwable $e) {
			\Cake\Log\Log::warning('PortalNotificationService: ' . $e->getMessage());
		}
	}

	public static function staffUserIdsForEmpresa(int $idempresa): array {
		return self::_staffUserIdsForEmpresa($idempresa);
	}

	protected static function _staffUserIdsForEmpresa(int $idempresa): array {
		$Eu = TableRegistry::get('Empresasusers');
		$rows = $Eu->find()
			->select(['iduser'])
			->where(['idempresa' => $idempresa])
			->enableHydration(false)
			->toArray();
		$ids = array_unique(array_filter(array_column($rows, 'iduser')));
		if (empty($ids)) {
			return [];
		}
		$Users = TableRegistry::get('Users');
		$active = $Users->find()
			->select(['id'])
			->where(['id IN' => $ids, 'role' => 0, 'inativo' => 0])
			->enableHydration(false)
			->toArray();

		return array_values(array_unique(array_column($active, 'id')));
	}

	protected static function _userWantsInApp(int $userId, string $eventType): bool {
		try {
			$Prefs = TableRegistry::get('PortalNotificationPreferences');
			$p = $Prefs->find()
				->where(['user_id' => $userId, 'event_type' => $eventType])
				->first();
			if ($p === null) {
				return true;
			}

			return (int)$p->send_in_app === 1;
		} catch (\Throwable $e) {
			return true;
		}
	}

	public static function mapEventToNotifType(string $eventType): string {
		if (strpos($eventType, 'erp.') === 0) {
			return 'error';
		}
		if ($eventType === ClienteDomainEventType::CLIENTE_INATIVADO) {
			return 'warning';
		}
		if ($eventType === ClienteDomainEventType::CONTRATO_VENCENDO) {
			return 'warning';
		}
		if ($eventType === ClienteDomainEventType::CONTRATO_VENCIDO) {
			return 'error';
		}
		if (in_array($eventType, [
			ClienteDomainEventType::CLIENTE_CRIADO,
			ClienteDomainEventType::CLIENTE_ATIVADO,
			ClienteDomainEventType::CONTRATO_CRIADO,
		], true)) {
			return 'success';
		}

		return 'info';
	}
}
