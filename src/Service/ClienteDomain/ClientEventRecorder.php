<?php
namespace App\Service\ClienteDomain;

use Cake\ORM\TableRegistry;

class ClientEventRecorder {

	public static function record(
		int $idcliente,
		string $eventType,
		string $description,
		?int $actorUserId = null,
		array $metadata = []
	): void {
		if (!InfrastructureGuard::isReady()) {
			return;
		}
		try {
			$Events = TableRegistry::get('ClientDomainEvents');
			$row = $Events->newEntity([
				'idcliente' => $idcliente,
				'event_type' => $eventType,
				'description' => $description,
				'actor_user_id' => $actorUserId,
				'metadata_json' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
			]);
			$Events->save($row);
		} catch (\Throwable $e) {
			\Cake\Log\Log::warning('ClientEventRecorder: ' . $e->getMessage());
		}
	}
}
