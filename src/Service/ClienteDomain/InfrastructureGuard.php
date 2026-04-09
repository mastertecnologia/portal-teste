<?php
namespace App\Service\ClienteDomain;

use Cake\Datasource\ConnectionManager;

/**
 * Verifica se a camada mínima de notificações internas existe (ambiente sem migrate = no-op seguro).
 *
 * Exige só portal_internal_notifications: o sino e a API JSON usam esta tabela. client_domain_events
 * é opcional para gravação de histórico (ClientEventRecorder trata falhas localmente).
 */
class InfrastructureGuard {

	public static function isReady(): bool {
		static $cached = null;
		if ($cached !== null) {
			return $cached;
		}
		try {
			$conn = ConnectionManager::get('default');
			$tables = $conn->getSchemaCollection()->listTables();
			$cached = in_array('portal_internal_notifications', $tables, true);
		} catch (\Throwable $e) {
			$cached = false;
		}

		return $cached;
	}
}
