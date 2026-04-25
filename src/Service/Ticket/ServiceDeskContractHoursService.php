<?php
namespace App\Service\Ticket;

use Cake\ORM\TableRegistry;

/**
 * Inventário e leitura coerente de saldo/percentagem em `contratos_horas`, alinhado a
 * {@see \App\Controller\TicketsController::subtrairHorasContrato} e ao texto exibido em ficha.
 *
 * Não cria tabela duplicada; usa a mesma prioridade de colunas do manual existente.
 */
class ServiceDeskContractHoursService {

	/**
	 * @param int $idcliente
	 * @param int $idempresa
	 * @return \Cake\Datasource\EntityInterface|object|null
	 */
	public static function findContractForClient($idcliente, $idempresa) {
		$t = TableRegistry::getTableLocator()->get('ContratosHoras');
		$q = $t->find()->where(['idcliente' => (int)$idcliente, 'idempresa' => (int)$idempresa]);
		$c = $q->first();
		if ($c) {
			return $c;
		}

		return $t->find()->where(['idcliente' => (int)$idcliente])->first();
	}

	/**
	 * @param object $c contratos_horas row
	 * @return array{
	 *   totalHours:?float,usedHours:?float,balanceHours:?float,percentUsed:?float,mode:?string,label:string
	 * }
	 */
	public static function getSnapshot($c): array {
		$out = [
			'totalHours' => null,
			'usedHours' => null,
			'balanceHours' => null,
			'percentUsed' => null,
			'mode' => null,
			'label' => '—',
		];
		if ($c === null) {
			return $out;
		}
		$g = function ($k) use ($c) {
			$v = $c->get($k);
			if ($v === null || $v === '') {
				return null;
			}
			if (is_string($v)) {
				return (float)str_replace(',', '.', $v);
			}

			return (float)$v;
		};
		$gInt = function ($k) use ($c) {
			$v = $c->get($k);
			if ($v === null || $v === '') {
				return null;
			}

			return (int)$v;
		};

		if ($c->get('horas_contratadas') !== null && $c->get('saldo') !== null) {
			$hc = $g('horas_contratadas');
			$saldo = $g('saldo');
			$out['totalHours'] = $hc;
			$out['balanceHours'] = max(0, $saldo);
			$out['usedHours'] = max(0, $hc - $out['balanceHours']);
			$out['mode'] = 'horas_saldo';
		} elseif ($c->get('horas_contratadas') !== null && $c->get('saldo_horas') !== null) {
			$hc = $g('horas_contratadas');
			$saldo = $g('saldo_horas');
			$out['totalHours'] = $hc;
			$out['balanceHours'] = max(0, $saldo);
			$out['usedHours'] = max(0, $hc - $out['balanceHours']);
			$out['mode'] = 'horas_saldo_horas';
		} elseif ($c->get('horas_contratadas') !== null && $c->get('horas_consumidas') !== null) {
			$hc = $g('horas_contratadas');
			$hcon = $g('horas_consumidas');
			$out['totalHours'] = $hc;
			$out['usedHours'] = max(0, $hcon);
			$out['balanceHours'] = max(0, $hc - $hcon);
			$out['mode'] = 'horas_consumidas';
		} elseif ($c->get('minutos_contratados') !== null && $c->get('minutos_consumidos') !== null) {
			$mt = $gInt('minutos_contratados') ?? 0;
			$mc = $gInt('minutos_consumidos') ?? 0;
			$out['totalHours'] = $mt / 60.0;
			$out['usedHours'] = max(0, $mc / 60.0);
			$out['balanceHours'] = max(0, ($mt - $mc) / 60.0);
			$out['mode'] = 'minutos';
		} elseif ($c->get('saldo_minutos') !== null) {
			$sm = $gInt('saldo_minutos') ?? 0;
			$out['balanceHours'] = max(0, $sm / 60.0);
			$out['mode'] = 'saldo_minutos';
		}
		$th = $out['totalHours'];
		$uh = $out['usedHours'];
		if ($th !== null && $th > 0) {
			if ($uh !== null) {
				$out['percentUsed'] = min(199.0, (100.0 * $uh) / $th);
			} elseif ($out['balanceHours'] !== null) {
				$out['usedHours'] = max(0, $th - (float)$out['balanceHours']);
				$out['percentUsed'] = min(199.0, (100.0 * $out['usedHours']) / $th);
			}
		}
		$out['label'] = self::formatLabel($out);

		return $out;
	}

	protected static function formatLabel(array $s): string {
		if ($s['totalHours'] !== null && $s['balanceHours'] !== null) {
			return number_format($s['totalHours'], 2, ',', '.') . ' h contratadas; saldo: '
				. number_format((float)$s['balanceHours'], 2, ',', '.') . ' h';
		}
		if ($s['balanceHours'] !== null && ($s['mode'] ?? '') === 'saldo_minutos') {
			return 'Saldo: ' . number_format((float)$s['balanceHours'], 1, ',', '.') . ' h';
		}
		if ($s['usedHours'] !== null && $s['totalHours'] !== null) {
			return number_format($s['totalHours'], 2, ',', '.') . ' h contratadas; '
				. number_format($s['usedHours'], 2, ',', '.') . ' h usadas';
		}

		return '—';
	}
}
