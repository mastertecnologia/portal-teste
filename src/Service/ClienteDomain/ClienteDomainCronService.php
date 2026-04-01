<?php
namespace App\Service\ClienteDomain;

use App\Utility\ClienteDomainEventType;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * Alertas agendáveis (contratos) sem alterar APIs ou fluxos web existentes.
 */
class ClienteDomainCronService {

	/**
	 * Emite eventos de contrato vencido / vencendo (dedupe por cliente + contrato + tipo).
	 *
	 * @return array{infra_ready:bool,candidatos:int,vencendo:int,vencido:int,skipped:int,fora_janela:int,erro:?string}
	 */
	public static function runContractExpiryAlerts(int $daysAhead = 30, int $dedupeDays = 7): array {
		$stats = [
			'infra_ready' => false,
			'candidatos' => 0,
			'vencendo' => 0,
			'vencido' => 0,
			'skipped' => 0,
			'fora_janela' => 0,
			'erro' => null,
		];
		if (!InfrastructureGuard::isReady()) {
			return $stats;
		}
		$stats['infra_ready'] = true;

		try {
			$C = TableRegistry::get('Clicontratos');
			$rows = $C->find()
				->select(['id', 'idcliente', 'idempresa', 'descricao', 'dtvalidade', 'dtcancelamento'])
				->where(['dtvalidade IS NOT' => null])
				->enableHydration(true)
				->toArray();
		} catch (\Throwable $e) {
			\Cake\Log\Log::warning('ClienteDomainCronService: ' . $e->getMessage());
			$stats['erro'] = $e->getMessage();

			return $stats;
		}

		$stats['candidatos'] = count($rows);

		$today = new \DateTimeImmutable('today');
		$todayStr = $today->format('Y-m-d');
		$limitStr = $today->add(new \DateInterval('P' . max(1, $daysAhead) . 'D'))->format('Y-m-d');

		foreach ($rows as $ct) {
			if (!empty($ct->dtcancelamento)) {
				$stats['skipped']++;

				continue;
			}
			$dvDay = self::_dateToYmd($ct->dtvalidade);
			if ($dvDay === null) {
				$stats['skipped']++;

				continue;
			}
			$idcliente = (int)$ct->idcliente;
			$idempresa = (int)$ct->idempresa;
			if ($idcliente <= 0 || $idempresa <= 0) {
				$stats['skipped']++;

				continue;
			}

			$cid = (int)$ct->id;
			$desc = trim((string)($ct->descricao ?? ''));
			$meta = ['clicontrato_id' => $cid, 'source' => 'cron'];

			if ($dvDay < $todayStr) {
				if (self::_recentlyEmitted($idcliente, ClienteDomainEventType::CONTRATO_VENCIDO, $cid, $dedupeDays)) {
					$stats['skipped']++;

					continue;
				}
				ClienteDomainBridge::emit(ClienteDomainEventType::CONTRATO_VENCIDO, [
					'idcliente' => $idcliente,
					'idempresa' => $idempresa,
					'actor_user_id' => null,
					'title' => \__('Contrato vencido'),
					'message' => $desc !== '' ? \__('Item: {0} — validade {1}', $desc, $dvDay) : \__('Validade {0}', $dvDay),
					'action_url' => Router::url(['controller' => 'Clicontratos', 'action' => 'edit', $cid]),
					'entity_type' => 'Clicontrato',
					'entity_id' => $cid,
					'metadata' => $meta,
				]);
				$stats['vencido']++;
			} elseif ($dvDay <= $limitStr) {
				if (self::_recentlyEmitted($idcliente, ClienteDomainEventType::CONTRATO_VENCENDO, $cid, $dedupeDays)) {
					$stats['skipped']++;

					continue;
				}
				ClienteDomainBridge::emit(ClienteDomainEventType::CONTRATO_VENCENDO, [
					'idcliente' => $idcliente,
					'idempresa' => $idempresa,
					'actor_user_id' => null,
					'title' => \__('Contrato prestes a vencer'),
					'message' => $desc !== '' ? \__('Item: {0} — validade {1}', $desc, $dvDay) : \__('Validade {0}', $dvDay),
					'action_url' => Router::url(['controller' => 'Clicontratos', 'action' => 'edit', $cid]),
					'entity_type' => 'Clicontrato',
					'entity_id' => $cid,
					'metadata' => $meta,
				]);
				$stats['vencendo']++;
			} else {
				$stats['fora_janela']++;
			}
		}

		return $stats;
	}

	protected static function _dateToYmd($raw): ?string {
		if ($raw === null || $raw === '') {
			return null;
		}
		if ($raw instanceof \DateTimeInterface) {
			return $raw->format('Y-m-d');
		}
		if (is_string($raw)) {
			$t = strtotime($raw);

			return $t ? date('Y-m-d', $t) : null;
		}

		return null;
	}

	protected static function _recentlyEmitted(int $idcliente, string $eventType, int $clicontratoId, int $dedupeDays): bool {
		try {
			$Events = TableRegistry::get('ClientDomainEvents');
			$since = (new \DateTimeImmutable())->modify('-' . max(1, $dedupeDays) . ' days')->format('Y-m-d H:i:s');
			$needle = '"clicontrato_id":' . $clicontratoId;

			return $Events->find()
				->where([
					'idcliente' => $idcliente,
					'event_type' => $eventType,
					'created >=' => $since,
				])
				->where(['metadata_json LIKE' => '%' . $needle . '%'])
				->count() > 0;
		} catch (\Throwable $e) {
			return false;
		}
	}
}
