<?php
namespace App\Service\PortalAdvanced;

use Cake\ORM\TableRegistry;

/**
 * Grava auditoria na tabela audit_logs (módulo avançado).
 * Falha silenciosa se a tabela não existir ou o save falhar.
 */
class AuditLogService {

	public static function isAvailable(): bool {
		try {
			$t = TableRegistry::get('AuditLogs');

			return $t->getSchema()->hasColumn('entity_type');
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @param array|null $oldData Dados serializáveis em JSON
	 * @param array|null $newData
	 */
	public static function log(
		?int $userId,
		string $entityType,
		int $entityId,
		string $action,
		?array $oldData = null,
		?array $newData = null,
		?string $ipAddress = null,
		?string $userAgent = null
	): bool {
		if (!self::isAvailable()) {
			return false;
		}
		try {
			$Audit = TableRegistry::get('AuditLogs');
			$e = $Audit->newEntity([
				'user_id' => $userId > 0 ? $userId : null,
				'entity_type' => $entityType,
				'entity_id' => $entityId,
				'action' => $action,
				'old_data' => $oldData,
				'new_data' => $newData,
				'ip_address' => $ipAddress !== null && $ipAddress !== '' ? substr($ipAddress, 0, 45) : null,
				'user_agent' => $userAgent !== null && $userAgent !== '' ? substr($userAgent, 0, 255) : null,
			]);

			return (bool)$Audit->save($e);
		} catch (\Throwable $e) {
			\Cake\Log\Log::warning('AuditLogService: ' . $e->getMessage());

			return false;
		}
	}
}
