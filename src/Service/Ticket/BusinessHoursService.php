<?php
namespace App\Service\Ticket;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Prazos em horário comercial: Seg–Sex, 08:00–12:00 e 13:00–18:00 (America/Sao_Paulo).
 * Feriados: tabela `holidays` (idempresa NULL = global) por data.
 */
class BusinessHoursService {

	public const TIMEZONE = 'America/Sao_Paulo';
	public const MORNING_END_MIN = 12 * 60;
	public const LUNCH_END_MIN = 13 * 60;
	public const DAY_END_MIN = 18 * 60;
	public const DAY_START_MIN = 8 * 60;

	/** @var \DateTimeZone */
	protected $tz;

	public function __construct() {
		$this->tz = new \DateTimeZone(self::TIMEZONE);
	}

	/**
	 * Soma minutos de política de SLA (apenas em janelas úteis).
	 */
	public function addBusinessMinutes(\DateTimeInterface $start, int $policyMinutes, ?int $idempresa): Time {
		if ($policyMinutes <= 0) {
			return $this->toCakeTime($this->toImmutable($start));
		}
		$cur = $this->toImmutable($start);
		$remaining = $policyMinutes;
		$guard = 0;
		while ($remaining > 0 && $guard < 200000) {
			$guard++;
			$cur = $this->alignToWorkday($cur, $idempresa);
			$m = $this->minuteOfDay($cur);
			if ($m < self::DAY_START_MIN) {
				$cur = $this->setMinuteOfDay($cur, self::DAY_START_MIN);
				continue;
			}
			if ($m >= self::MORNING_END_MIN && $m < self::LUNCH_END_MIN) {
				$cur = $this->setMinuteOfDay($cur, self::LUNCH_END_MIN);
				continue;
			}
			if ($m >= self::DAY_END_MIN) {
				$cur = $this->jumpToNextBusinessMorning($cur, $idempresa);
				continue;
			}
			$segmentEnd = $m < self::MORNING_END_MIN ? self::MORNING_END_MIN : self::DAY_END_MIN;
			$untilSegmentEnd = $segmentEnd - $m;
			$chunk = min($remaining, $untilSegmentEnd);
			$cur = $cur->modify("+{$chunk} minutes");
			$remaining -= $chunk;
		}
		if ($guard >= 200000) {
			return $this->toCakeTime($this->toImmutable($start)->modify('+' . $policyMinutes . ' minutes'));
		}

		return $this->toCakeTime($cur);
	}

	/**
	 * Se fim de semana/feriado, vai para o próximo dia útil às 08:00.
	 */
	protected function alignToWorkday(\DateTimeImmutable $cur, ?int $idempresa): \DateTimeImmutable {
		$guard = 0;
		while ($guard++ < 500) {
			$d = (int)$cur->format('N');
			if ($d < 6 && !$this->isHolidayDate($cur, $idempresa)) {
				return $cur;
			}
			$cur = $cur->modify('+1 day')->setTime(0, 0, 0);
		}

		return $cur;
	}

	/**
	 * Próxima manhã útil 08:00 a partir de um instante (ex.: após 18:00 do mesmo dia).
	 */
	protected function jumpToNextBusinessMorning(\DateTimeImmutable $cur, ?int $idempresa): \DateTimeImmutable {
		$next = $cur->modify('+1 day')->setTime(0, 0, 0);
		$next = $this->alignToWorkday($next, $idempresa);

		return $this->setMinuteOfDay($next, self::DAY_START_MIN);
	}

	/**
	 * Classificação de faturamento: holiday | extra | commercial.
	 */
	public function classifyBilling(\DateTimeInterface $at, ?int $idempresa): string {
		$im = $this->toImmutable($at);
		$dow = (int)$im->format('N');
		if ($dow >= 6) {
			return 'holiday';
		}
		if ($this->isHolidayDate($im, $idempresa)) {
			return 'holiday';
		}
		$m = $this->minuteOfDay($im);
		if ($m < self::DAY_START_MIN || $m >= self::DAY_END_MIN) {
			return 'extra';
		}
		if ($m >= self::MORNING_END_MIN && $m < self::LUNCH_END_MIN) {
			return 'extra';
		}
		if ($m >= self::DAY_START_MIN && $m < self::MORNING_END_MIN) {
			return 'commercial';
		}
		if ($m >= self::LUNCH_END_MIN && $m < self::DAY_END_MIN) {
			return 'commercial';
		}

		return 'extra';
	}

	public function isHolidayDate(\DateTimeInterface $d, ?int $idempresa): bool {
		if (!$this->_holidaysTableExists()) {
			return false;
		}
		/** @var \Cake\ORM\Table $H */
		$H = TableRegistry::get('Holidays');
		$dateStr = $d->format('Y-m-d');
		$q = $H->find()->where(['holiday_date' => $dateStr]);
		if ($idempresa !== null && $idempresa > 0) {
			$q->andWhere(['OR' => [['idempresa' => $idempresa], ['idempresa IS' => null]]]);
		}

		return $q->count() > 0;
	}

	protected function _holidaysTableExists(): bool {
		try {
			$c = \Cake\Datasource\ConnectionManager::get('default')->getSchemaCollection();
			$list = $c->listTables();

			return in_array('holidays', $list, true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function minuteOfDay(\DateTimeInterface $d): int {
		$im = $this->toImmutable($d);

		return (int)$im->format('H') * 60 + (int)$im->format('i');
	}

	protected function setMinuteOfDay(\DateTimeImmutable $cur, int $min): \DateTimeImmutable {
		$h = (int)floor($min / 60);
		$i = $min % 60;

		return $cur->setTime($h, $i, 0);
	}

	protected function toImmutable(\DateTimeInterface $d): \DateTimeImmutable {
		if ($d instanceof \DateTimeImmutable) {
			$t = $d;
		} else {
			$tz = $d->getTimezone() ?: $this->tz;
			$t = new \DateTimeImmutable($d->format('Y-m-d H:i:s'), $tz);
		}
		if ($t->getTimezone()->getName() !== $this->tz->getName()) {
			$t = $t->setTimezone($this->tz);
		}

		return $t;
	}

	protected function toCakeTime(\DateTimeInterface $d): Time {
		$im = $this->toImmutable($d);
		$s = $im->format('Y-m-d H:i:s');
		$t = new Time($s, $this->tz);
		$t->setTimezone($this->tz->getName());

		return $t;
	}
}
