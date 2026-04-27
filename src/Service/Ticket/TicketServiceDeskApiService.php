<?php
namespace App\Service\Ticket;

use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Lógica compartilhada para API Timeline B, validação de geo, PDFs (mPDF).
 */
class TicketServiceDeskApiService {

	/**
	 * Contabilização em minutos inteiros: qualquer fraccção de minuto (segundos) sobe para o minuto seguinte.
	 * Ex.: 7 s → 1 min; 4 min 18 s (258 s) → 5 min; 0 s → 0.
	 */
	public static function billingSecondsFromRaw(int $sec): int {
		if ($sec <= 0) {
			return 0;
		}

		return (int)ceil($sec / 60) * 60;
	}

	/**
	 * Rótulos de data/intervalo a partir de linha em ticketshoras (para a aba Horas do Service Desk).
	 *
	 * @param \Cake\ORM\Table $thTable
	 * @param \Cake\Datasource\EntityInterface|object|null $h
	 * @return array{0:?string,1:?string} [workDateLabel, workTimeRangeLabel]
	 */
	public static function worklogLabelsFromTicketshorasRow($thTable, $h) {
		if ($h === null) {
			return [null, null];
		}
		$hiRaw = $h->get('horaini');
		$hfRaw = $h->get('horafin');
		$dataF = $h->get('data');
		$dateLabel = null;
		if ($dataF && is_object($dataF) && method_exists($dataF, 'format')) {
			$dateLabel = $dataF->format('d/m/Y');
		}
		$hi = null;
		$hf = null;
		try {
			if ($hiRaw instanceof \DateTimeInterface) {
				$hi = $hiRaw;
			} elseif (is_string($hiRaw) && $hiRaw !== '') {
				$hi = new Time($hiRaw);
			}
			if ($hfRaw instanceof \DateTimeInterface) {
				$hf = $hfRaw;
			} elseif (is_string($hfRaw) && $hfRaw !== '') {
				$hf = new Time($hfRaw);
			}
		} catch (\Throwable $e) {
			return [null, null];
		}
		if ($hi && $dateLabel === null) {
			$dateLabel = $hi->format('d/m/Y');
		}
		$range = ($hi && $hf) ? ($hi->format('H:i') . ' – ' . $hf->format('H:i')) : null;

		return [$dateLabel, $range];
	}

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
	 * Foto de perfil em disco, se existir (cache por id de utilizador).
	 * Caminhos relativos a WWW_ROOT: arquivos/usuarios/{id}.(jpg|jpeg|png|webp|gif).
	 *
	 * @param array<int, string|null> $cache
	 * @return string|null URL pública (ex. /arquivos/usuarios/5.png)
	 */
	public static function userAvatarPublicPath(int $userId, array &$cache): ?string {
		if ($userId < 1) {
			return null;
		}
		if (array_key_exists($userId, $cache)) {
			return $cache[$userId];
		}
		foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
			$rel = 'arquivos/usuarios/' . $userId . '.' . $ext;
			$fs = WWW_ROOT . str_replace(['/', '\\'], DS, $rel);
			if (is_file($fs) && is_readable($fs)) {
				$out = '/' . str_replace(DS, '/', $rel);
				$cache[$userId] = $out;

				return $out;
			}
		}
		$cache[$userId] = null;

		return null;
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
		$avatarCache = [];
		try {
			$hasTe = in_array('ticket_events', ConnectionManager::get('default')->getSchemaCollection()->listTables(), true);
		} catch (\Throwable $e) {
			$hasTe = false;
		}
		if ($hasTe) {
			$Te = TableRegistry::get('TicketEvents');
			$evs = $Te->find()
				->where(['TicketEvents.ticket_id' => $tid])
				->where(['OR' => [
					['TicketEvents.idempresa' => $idempresa],
					['TicketEvents.idempresa' => 0],
				]])
				->contain(['Users' => function ($q) {
					return $q->select(['id', 'name', 'username']);
				}])
				->order(['TicketEvents.created' => 'ASC', 'TicketEvents.id' => 'ASC'])
				->all();
			$thRowCache = [];
			foreach ($evs as $e) {
				$meta = $e->get('metadata');
				if (is_string($meta)) {
					$dec = json_decode($meta, true);
					$meta = is_array($dec) ? $dec : null;
				}
				if (is_array($meta) && !empty($meta['ticket_comentario_id'])) {
					$comCobertura[] = (int)$meta['ticket_comentario_id'];
				}
				$typeNorm = strtolower((string)$e->get('type'));
				$isWl = $typeNorm === 'worklog';
				$thFromMeta = 0;
				if (is_array($meta) && $isWl) {
					$thFromMeta = (int)($meta['ticketshoras_id'] ?? $meta['ticketshorasId'] ?? 0);
					if ($thFromMeta > 0) {
						$horasCobertura[] = $thFromMeta;
					}
				}
				$secondsSpent = (int)($e->get('seconds_spent') ?? 0);
				$hrowW = null;
				if ($isWl && $thFromMeta > 0) {
					if (!isset($thRowCache[$thFromMeta])) {
						$thRowCache[$thFromMeta] = $c->Ticketshoras->find()
							->where(['id' => $thFromMeta, 'idticket' => $tid])
							->first();
					}
					$hrowW = $thRowCache[$thFromMeta];
					// Se o evento aponta para ticketshoras mas seconds_spent ficou 0 (legado/erro de gravação),
					// recalcular a partir de horaini/horafin — a linha legada não é repete na timeline.
					if ($secondsSpent === 0) {
						$resolved = self::resolveSecondsFromTicketshorasRow($c->Ticketshoras, $hrowW);
						if ($resolved > 0) {
							$secondsSpent = $resolved;
						}
					}
				}
				$u = $e->user;
				$autor = $u ? (string)($u->name ?? $u->username ?? '') : '';
				$att = (string)($e->get('attachment') ?? '');
				$workDateLabel = null;
				$workTimeRangeLabel = null;
				if ($isWl && $hrowW) {
					[$workDateLabel, $workTimeRangeLabel] = self::worklogLabelsFromTicketshorasRow($c->Ticketshoras, $hrowW);
				}
				$secondsOut = $isWl ? self::billingSecondsFromRaw($secondsSpent) : $secondsSpent;
				$uidE = (int)($e->get('user_id') ?? 0);
				$avatarUrlE = $uidE > 0 ? self::userAvatarPublicPath($uidE, $avatarCache) : null;
				$rows[] = (object)[
					'id' => (int)$e->id,
					'source' => 'event',
					'type' => (string)$e->get('type'),
					'autor' => $autor,
					'userId' => (int)($e->get('user_id') ?? 0) ?: null,
					'avatarUrl' => $avatarUrlE,
					'description' => (string)($e->get('description') ?? ''),
					'secondsSpent' => $secondsOut,
					'billingType' => $e->get('billing_type'),
					'hourlyRate' => $e->get('hourly_rate'),
					'rating' => $e->get('rating'),
					'attachment' => $att !== '' ? $att : null,
					'metadata' => is_array($meta) ? $meta : null,
					'created' => $e->created ? $e->created->format('Y-m-d H:i:s') : null,
					'createdLabel' => $e->created ? $e->created->i18nFormat("dd/MM/yyyy HH:mm") : '',
					'workDateLabel' => $workDateLabel,
					'workTimeRangeLabel' => $workTimeRangeLabel,
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
			$avatarUrlC = $uid > 0 ? self::userAvatarPublicPath($uid, $avatarCache) : null;
			$rows[] = (object)[
				'id' => 'legacy_c_' . $cid,
				'source' => 'legacy',
				'type' => 'comment',
				'autor' => $u ? (string)($u->name ?? '') : '—',
				'userId' => $uid ?: null,
				'avatarUrl' => $avatarUrlC,
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
			$avatarUrlM = $uid > 0 ? self::userAvatarPublicPath($uid, $avatarCache) : null;
			$rows[] = (object)[
				'id' => 'legacy_m_' . (int)$m->id,
				'source' => 'legacy',
				'type' => 'mov',
				'autor' => $u ? (string)$u->name : '—',
				'userId' => $uid ?: null,
				'avatarUrl' => $avatarUrlM,
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
			$secH = self::resolveSecondsFromTicketshorasRow($th, $h);
			$secBill = self::billingSecondsFromRaw($secH);
			[$wDate, $wRange] = self::worklogLabelsFromTicketshorasRow($th, $h);
			$ini = is_object($h->horaini) && method_exists($h->horaini, 'getTimestamp') ? (int)$h->horaini->getTimestamp() : 0;
			$labelLinha = '';
			if ($wDate && $wRange) {
				$labelLinha = $wDate . ' · ' . $wRange;
			} elseif ($wDate) {
				$labelLinha = $wDate;
			} elseif ($wRange) {
				$labelLinha = $wRange;
			}
			$avatarUrlH = $uid > 0 ? self::userAvatarPublicPath($uid, $avatarCache) : null;
			$rows[] = (object)[
				'id' => 'legacy_h_' . (int)$h->id,
				'source' => 'legacy',
				'type' => 'worklog',
				'autor' => $u ? (string)$u->name : '—',
				'userId' => $uid ?: null,
				'avatarUrl' => $avatarUrlH,
				'description' => 'Registro de horas (legado)',
				'secondsSpent' => $secBill,
				'created' => $ini > 0 ? date('Y-m-d H:i:s', $ini) : null,
				'createdLabel' => $labelLinha,
				'workDateLabel' => $wDate,
				'workTimeRangeLabel' => $wRange,
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

	/**
	 * Duração em segundos a partir de horaini/horafin (tipos string ou DateTime, cruza meia-noite; clone antes de getMinutos para não mutar a entidade).
	 *
	 * @param \Cake\ORM\Table $thTable
	 * @param \Cake\Datasource\EntityInterface|object|null $hrow
	 */
	public static function resolveSecondsFromTicketshorasRow($thTable, $hrow): int {
		if ($hrow === null) {
			return 0;
		}
		$a = $hrow->get('horaini');
		$b = $hrow->get('horafin');
		if ($a === null || $b === null || $a === '' || $b === '') {
			return 0;
		}
		if (!($a instanceof \DateTimeInterface)) {
			try {
				$a = new Time($a);
			} catch (\Throwable $e) {
				return 0;
			}
		}
		if (!($b instanceof \DateTimeInterface)) {
			try {
				$b = new Time($b);
			} catch (\Throwable $e) {
				return 0;
			}
		}
		$diffSec = (int)($b->getTimestamp() - $a->getTimestamp());
		if ($diffSec > 0) {
			return $diffSec;
		}
		$min = 0;
		try {
			$ca = clone $a;
			$cb = clone $b;
			$min = (int)$thTable->getMinutos($ca, $cb);
		} catch (\Throwable $e) {
			$min = 0;
		}
		if ($min > 0) {
			return (int)($min * 60);
		}

		return 0;
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
