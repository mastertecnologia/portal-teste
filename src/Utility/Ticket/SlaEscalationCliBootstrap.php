<?php
namespace App\Utility\Ticket;

/**
 * Normaliza argv antes do Console do Cake validar opções (prod pode usar runner restrito).
 * Remove flags de diagnóstico e define CHECK_SLA_TICKET_ID quando aplicável.
 */
final class SlaEscalationCliBootstrap {

	private function __construct() {
	}

	public static function normalizeArgvIfCheckSlaEscalation(): void {
		if (PHP_SAPI !== 'cli' || empty($_SERVER['argv']) || !is_array($_SERVER['argv'])) {
			return;
		}
		$argv = $_SERVER['argv'];
		$cmdIdx = null;
		$aliases = [
			'CheckSlaEscalation',
			'check_sla_escalation',
			'check-sla-escalation',
		];
		foreach ($argv as $i => $tok) {
			if (!is_string($tok)) {
				continue;
			}
			$t = trim($tok);
			foreach ($aliases as $a) {
				if (strcasecmp($t, $a) === 0) {
					$cmdIdx = (int)$i;
					break 2;
				}
			}
		}
		if ($cmdIdx === null) {
			return;
		}
		$head = array_slice($argv, 0, $cmdIdx + 1);
		$tail = array_slice($argv, $cmdIdx + 1);
		$newTail = self::stripDiagnosticTicketSwitches($tail);
		if ($newTail === $tail) {
			return;
		}
		$_SERVER['argv'] = array_merge($head, $newTail);
		if (isset($_SERVER['argc'])) {
			$_SERVER['argc'] = count($_SERVER['argv']);
		}
		$GLOBALS['argv'] = $_SERVER['argv'];
		if (isset($GLOBALS['argc'])) {
			$GLOBALS['argc'] = count($_SERVER['argv']);
		}
	}

	/**
	 * @param array<int,string> $tail
	 *
	 * @return array<int,string>
	 */
	protected static function stripDiagnosticTicketSwitches(array $tail): array {
		$ticketId = null;
		$out = [];
		$n = count($tail);
		for ($i = 0; $i < $n; $i++) {
			$t = $tail[$i];
			if (!is_string($t)) {
				$out[] = $t;
				continue;
			}
			if ($t === '-t' || strcasecmp($t, '--ticket') === 0 || strcasecmp($t, '--ticket-id') === 0 || strcasecmp($t, '--ticket_id') === 0) {
				if (isset($tail[$i + 1]) && is_string($tail[$i + 1]) && ctype_digit(trim($tail[$i + 1]))) {
					$ticketId = (int)trim($tail[$i + 1]);
					$i++;

					continue;
				}
				$out[] = $t;

				continue;
			}
			if (preg_match('/^--ticket(?:-id|_id)?=(.+)$/i', $t, $m)) {
				$v = trim($m[1]);
				if ($v !== '' && ctype_digit($v)) {
					$ticketId = (int)$v;

					continue;
				}
			}
			if ($ticketId === null && ctype_digit(trim($t)) && trim($t) !== '') {
				$ticketId = (int)trim($t);

				continue;
			}
			$out[] = $t;
		}
		if ($ticketId !== null && $ticketId > 0) {
			putenv('CHECK_SLA_TICKET_ID=' . $ticketId);
			$_ENV['CHECK_SLA_TICKET_ID'] = (string)$ticketId;
			$_SERVER['CHECK_SLA_TICKET_ID'] = (string)$ticketId;
		}

		return $out;
	}
}
