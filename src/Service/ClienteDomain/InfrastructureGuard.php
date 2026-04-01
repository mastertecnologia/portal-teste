<?php
namespace App\Service\ClienteDomain;

use Cake\Datasource\ConnectionManager;

/**
 * Verifica se as tabelas da camada portal foram migradas (ambiente sem migrate = no-op seguro).
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
			$cached = in_array('portal_internal_notifications', $tables, true)
				&& in_array('client_domain_events', $tables, true);
		} catch (\Throwable $e) {
			$cached = false;
		}

		return $cached;
	}
}
