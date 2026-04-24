<?php
namespace App\Service\Ticket;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

/**
 * Lógica compartilhada para API Timeline B, validação de geo, PDFs (mPDF).
 */
class TicketServiceDeskApiService {

	public static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float {
		$earth = 6371.0;
		$dLat = deg2rad($lat2 - $lat1);
		$dLon = deg2rad($lon2 - $lon1);
		$a = sin($dLat / 2) * sin($dLat / 2)
			+ cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		return (float)($earth * $c);
	}

	/**
	 * @param \App\Controller\TicketsController $c
	 * @return object{rows: object[]}
	 */
	public static function buildTimelineRows($c, $ticket) {
		$tid = (int)$ticket->id;
		$idempresa = (int)($ticket->idempresa ?? 0);
		$rows = [];
		$comCobertura = [];
		$horasCobertura = [];
		try {
			$hasTe = in_array('ticket_events', ConnectionManager::get('default')->getSchemaCollection()->listTables(), true);
		} catch (\Throwable $e) {
			$hasTe = false;
		}
		if ($hasTe) {
			$Te = TableRegistry::get('TicketEvents');
			$evs = $Te->find()
				->where(['TicketEvents.ticket_id' => $tid, 'TicketEvents.idempresa' => $idempresa])
				->contain(['Users' => function ($q) {
					return $q->select(['id', 'name', 'username']);
				}])
				->order(['TicketEvents.created' => 'ASC', 'TicketEvents.id' => 'ASC'])
				->all();
			foreach ($evs as $e) {
				$meta = $e->get('metadata');
				if (is_string($meta)) {
					$dec = json_decode($meta, true);
					$meta = is_array($dec) ? $dec : null;
				}
				if (is_array($meta) && !empty($meta['ticket_comentario_id'])) {
					$comCobertura[] = (int)$meta['ticket_comentario_id'];
				}
				$isWl = (string)$e->get('type') === 'worklog';
				if (is_array($meta) && $isWl && !empty($meta['ticketshoras_id'])) {
					$horasCobertura[] = (int)$meta['ticketshoras_id'];
				}
				$u = $e->user;
				$autor = $u ? (string)($u->name ?? $u->username ?? '') : '';
				$att = (string)($e->get('attachment') ?? '');
				$rows[] = (object)[
					'id' => (int)$e->id,
					'source' => 'event',
					'type' => (string)$e->get('type'),
					'autor' => $autor,
					'userId' => (int)($e->get('user_id') ?? 0) ?: null,
					'description' => (string)($e->get('description') ?? ''),
					'secondsSpent' => (int)($e->get('seconds_spent') ?? 0),
					'billingType' => $e->get('billing_type'),
					'hourlyRate' => $e->get('hourly_rate'),
					'rating' => $e->get('rating'),
					'attachment' => $att !== '' ? $att : null,
					'metadata' => is_array($meta) ? $meta : null,
					'created' => $e->created ? $e->created->format('Y-m-d H:i:s') : null,
					'createdLabel' => $e->created ? $e->created->i18nFormat("dd/MM/yyyy HH:mm") : '',
					'isBilled' => (bool)($e->get('is_billed') ?? false),
				];
			}
		}

		// Comentários: por idticket (o ticket já foi validado em apiTimeline / pertença à empresa)
		$comRows = $c->Ticketcomentarios->find()
			->where(['idticket' => $tid])
			->order(['id' => 'ASC'])
			->all();
		foreach ($comRows as $row) {
			$cid = (int)($row->id ?? 0);
			if ($cid > 0 && in_array($cid, $comCobertura, true)) {
				continue;
			}
			$uid = (int)($row->idautor ?? 0);
			$u = $uid > 0 ? $c->Users->findById($uid)->select(['id', 'name', 'role'])->first() : null;
			$ts = $row->created ? strtotime((string)$row->created) : 0;
			$rows[] = (object)[
				'id' => 'legacy_c_' . $cid,
				'source' => 'legacy',
				'type' => 'comment',
				'autor' => $u ? (string)($u->name ?? '') : '—',
				'userId' => $uid ?: null,
				'description' => (string)($row->comentario ?? ''),
				'secondsSpent' => 0,
				'billingType' => null,
				'metadata' => null,
				'created' => $ts > 0 ? date('Y-m-d H:i:s', $ts) : null,
				'createdLabel' => $row->created ? date('d/m/Y H:i', $ts) : '',
			];
		}

		// Movimentações: igual à action edit (só idticket) — idempresa em ticketsmovs pode ser NULL em legado
		$movs = $c->Ticketsmovs->find()->where(['idticket' => $tid])->order(['id' => 'ASC'])->all();
		foreach ($movs as $m) {
			$uid = (int)($m->idusuario ?? 0);
			$u = $uid > 0 ? $c->Users->findById($uid)->select(['id', 'name'])->first() : null;
			$rawDt = (string)($m->datetime ?? '');
			$ts = self::parseBrDateTime($rawDt);
			$rows[] = (object)[
				'id' => 'legacy_m_' . (int)$m->id,
				'source' => 'legacy',
				'type' => 'mov',
				'autor' => $u ? (string)$u->name : '—',
				'userId' => $uid ?: null,
				'description' => trim((string)($m->observacao ?? '') . ' [movimento de situação]'),
				'secondsSpent' => 0,
				'created' => $ts > 0 ? date('Y-m-d H:i:s', $ts) : null,
				'createdLabel' => $rawDt,
			];
		}

		$th = $c->Ticketshoras;
		// Horas: igual à action edit (só idticket) — nalguns legados `idempresa` não está preenchido em ticketshoras
		$hs = $th->find()->where(['idticket' => $tid])->order(['id' => 'ASC'])->all();
		foreach ($hs as $h) {
			$hid = (int)($h->id ?? 0);
			if ($hid > 0 && in_array($hid, $horasCobertura, true)) {
				continue;
			}
			$uid = (int)($h->iduser ?? 0);
			$u = $uid > 0 ? $c->Users->findById($uid)->select(['id', 'name'])->first() : null;
			$min = 0;
			try {
				$min = (int)$th->getMinutos($h->horaini, $h->horafin);
			} catch (\Throwable $e) {
			}
			$ini = is_object($h->horaini) && method_exists($h->horaini, 'getTimestamp') ? (int)$h->horaini->getTimestamp() : 0;
			$rows[] = (object)[
				'id' => 'legacy_h_' . (int)$h->id,
				'source' => 'legacy',
				'type' => 'worklog',
				'autor' => $u ? (string)$u->name : '—',
				'userId' => $uid ?: null,
				'description' => 'Registro de horas (legado)',
				'secondsSpent' => (int)($min * 60),
				'created' => $ini > 0 ? date('Y-m-d H:i:s', $ini) : null,
				'createdLabel' => '',
			];
		}

		usort($rows, function ($a, $b) {
			$ta = !empty($a->created) ? strtotime($a->created) : 0;
			$tb = !empty($b->created) ? strtotime($b->created) : 0;
			if ($ta === $tb) {
				return 0;
			}

			return $ta <=> $tb;
		});

		return (object)['rows' => $rows];
	}

	public static function parseBrDateTime(string $s): int {
		$s = trim($s);
		if ($s === '') {
			return 0;
		}
		$dt = \DateTime::createFromFormat('d/m/Y H:i:s', $s, new \DateTimeZone('America/Sao_Paulo'));
		if ($dt) {
			return (int)$dt->getTimestamp();
		}
		$t = strtotime($s);

		return is_int($t) ? $t : 0;
	}

}
