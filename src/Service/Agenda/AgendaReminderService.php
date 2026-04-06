<?php
namespace App\Service\Agenda;

use App\Service\ClienteDomain\PortalNotificationService;
use App\Utility\ClienteDomainEventType;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * Dispara notificações in-app quando chega o horário do lembrete (visitas na agenda).
 */
class AgendaReminderService {

	protected static function labelsTipo(): array {
		return [
			0 => 'Visita',
			1 => 'Reunião',
			2 => 'Tarefa',
			3 => 'Lembrete',
		];
	}

	protected static function lembreteAntesEmTexto(int $minutos): string {
		if ($minutos <= 0) {
			return (string)$minutos . ' minutos';
		}
		if ($minutos < 60) {
			return $minutos === 1 ? '1 minuto' : $minutos . ' minutos';
		}
		if ($minutos % 1440 === 0) {
			$d = (int)($minutos / 1440);

			return $d === 1 ? '1 dia' : $d . ' dias';
		}
		if ($minutos % 60 === 0) {
			$h = (int)($minutos / 60);

			return $h === 1 ? '1 hora' : $h . ' horas';
		}

		return $minutos . ' minutos';
	}

	protected static function fingerprintInicio($visita): ?string {
		$d = $visita->get('data');
		$h = $visita->get('horaini');
		if ($d instanceof \DateTimeInterface && $h instanceof \DateTimeInterface) {
			return $d->format('Y-m-d') . ' ' . $h->format('H:i:s');
		}

		return null;
	}

	/**
	 * @return int quantidade de lembretes enviados
	 */
	public function dispatchDueReminders(): int {
		require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

		$Visitas = TableRegistry::get('Visitas');
		$now = FrozenTime::now();
		$from = $now->subDays(4);
		$until = $now->addYears(1);

		$rows = $Visitas->find()
			->contain(['Users', 'Clientes'])
			->where([
				'Visitas.lembrete_minutos IS NOT' => null,
				'Visitas.lembrete_minutos >' => 0,
				'Visitas.lembrete_notificado_em IS' => null,
				'Visitas.situacao IN' => [C_UserSituacaoAgendada, C_UserSituacaoPendende],
				'Visitas.data >=' => $from->format('Y-m-d'),
				'Visitas.data <=' => $until->format('Y-m-d'),
			])
			->all();

		$sent = 0;
		$labels = self::labelsTipo();
		foreach ($rows as $visita) {
			$fp = self::fingerprintInicio($visita);
			if ($fp === null) {
				continue;
			}
			$inicio = FrozenTime::createFromFormat('Y-m-d H:i:s', $fp);
			if ($inicio === false) {
				continue;
			}
			$min = (int)$visita->get('lembrete_minutos');
			if ($min <= 0) {
				continue;
			}
			$lembreteEm = $inicio->subMinutes($min);
			if ($now < $lembreteEm || $now >= $inicio) {
				continue;
			}

			$userIds = [];
			$autor = (int)$visita->get('idautor');
			if ($autor > 0) {
				$userIds[] = $autor;
			}
			foreach ((array)$visita->get('users') as $u) {
				$uid = (int)$u->get('id');
				if ($uid > 0) {
					$userIds[] = $uid;
				}
			}
			$userIds = array_values(array_unique(array_filter($userIds)));
			if (empty($userIds)) {
				continue;
			}

			$Users = TableRegistry::get('Users');
			$staff = $Users->find()
				->select(['id'])
				->where([
					'id IN' => $userIds,
					'role' => 0,
					'OR' => [['inativo' => 0], ['inativo IS' => null]],
				])
				->enableHydration(false)
				->toArray();
			$target = array_values(array_unique(array_column($staff, 'id')));
			if (empty($target)) {
				continue;
			}

			$tipo = (int)$visita->get('agenda_tipo');
			$tipoLabel = $labels[$tipo] ?? $labels[0];
			$tituloCustom = trim((string)($visita->get('agenda_titulo') ?? ''));
			$cli = '';
			$cl = $visita->get('cliente');
			if ($cl && is_object($cl)) {
				$cli = (string)($cl->get('razaosocial') ?: $cl->get('nome') ?: '');
			}
			$subject = $tituloCustom !== '' ? $tituloCustom : ($tipoLabel . ($cli !== '' ? ': ' . $cli : ''));
			$antesTxt = self::lembreteAntesEmTexto($min);
			$msg = sprintf(
				'Compromisso às %s de %s. Este aviso foi configurado para %s antes do horário de início.',
				$inicio->format('H:i'),
				$inicio->format('d/m/Y'),
				$antesTxt
			);
			$msg .= ' Tipo: ' . $tipoLabel . '.';
			if ($cli !== '') {
				$msg .= ' Cliente: ' . $cli . '.';
			}
			if ($tituloCustom !== '') {
				$msg .= ' Assunto: ' . $tituloCustom . '.';
			}
			$msg .= ' Abra a notificação para editar o agendamento.';

			$idempresa = (int)$visita->get('idempresa');
			$actionUrl = Router::url(['controller' => 'Visitas', 'action' => 'edit', $visita->get('id')]);
			$notifType = PortalNotificationService::mapEventToNotifType(ClienteDomainEventType::AGENDA_LEMBRETE);

			PortalNotificationService::notifyUsers(
				$target,
				ClienteDomainEventType::AGENDA_LEMBRETE,
				$notifType,
				'Lembrete de agenda · ' . $subject,
				$msg,
				$actionUrl,
				'visita',
				$visita->get('id'),
				['idvisita' => (int)$visita->get('id'), 'idempresa' => $idempresa]
			);

			$visita->set('lembrete_notificado_em', $now);
			$Visitas->save($visita, ['checkRules' => false, 'associated' => false]);
			$sent++;
		}

		return $sent;
	}
}
