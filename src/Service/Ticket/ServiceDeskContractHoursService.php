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

	/**
	 * Rótulo do plano para o resumo (ex.: «10 horas mensais», «1 hora mensal»).
	 */
	protected static function formatPlanoMensalLabel(float $horasMensais): string {
		$h = (float)$horasMensais;
		if ($h <= 0) {
			return '';
		}
		if (abs($h - round($h)) < 0.0001) {
			$n = (int)round($h);
			if ($n === 1) {
				return '1 hora mensal';
			}

			return $n . ' horas mensais';
		}

		$t = rtrim(rtrim(number_format($h, 2, ',', '.'), '0'), ',');

		return $t . ' horas mensais';
	}

	/**
	 * Formata horas decimais como HH:MM:SS (alinhado ao exibido na ficha de contrato).
	 */
	public static function hoursToHms(?float $h): ?string {
		if ($h === null) {
			return null;
		}
		$h = max(0.0, (float)$h);
		$sec = (int)round($h * 3600.0);
		$s = $sec % 60;
		$m = intdiv($sec, 60) % 60;
		$hr = intdiv($sec, 3600);

		return sprintf('%02d:%02d:%02d', $hr, $m, $s);
	}

	/**
	 * @param object      $c    linha contratos_horas
	 * @param array<string, mixed> $snap retorno de getSnapshot()
	 * @return array<string, mixed>
	 */
	public static function enrichContractHoursForApi($c, array $snap): array {
		$snap['hasContract'] = true;
		$id = (int)$c->get('id');
		$idcli = (int)$c->get('idcliente');
		$snap['contractId'] = $id;
		$snap['contractCode'] = 'CT-' . $idcli . '-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
		$snap['horasMensais'] = null;
		$hmRaw = $c->get('horas_mensais');
		if ($hmRaw !== null && $hmRaw !== '') {
			$hm = is_string($hmRaw) ? (float)str_replace(',', '.', $hmRaw) : (float)$hmRaw;
			if ($hm > 0) {
				$snap['horasMensais'] = $hm;
				$snap['plano'] = self::formatPlanoMensalLabel($hm);
			}
		}
		if (!isset($snap['plano']) || $snap['plano'] === null) {
			$th = $snap['totalHours'];
			if ($th !== null && $th > 0) {
				$n = (int)round($th);
				$snap['plano'] = $n . ' hora' . ($n === 1 ? '' : 's') . ' contratada' . ($n === 1 ? '' : 's');
			} else {
				$mt = $c->get('minutos_contratados');
				if ($mt !== null && $mt !== '' && (int)$mt > 0) {
					$nh = (int)round((int)$mt / 60.0);
					$nh = max(1, $nh);
					$snap['plano'] = $nh . ' hora' . ($nh === 1 ? '' : 's') . ' contratada' . ($nh === 1 ? '' : 's');
				} else {
					$snap['plano'] = null;
				}
			}
		}
		$snap['vigenciaTexto'] = null;
		$di = $c->get('data_inicio');
		$df = $c->get('data_fim');
		$fmtD = function ($d): ?string {
			if ($d === null || $d === '') {
				return null;
			}
			if ($d instanceof \DateTimeInterface) {
				return $d->format('d/m/Y');
			}
			$t = strtotime((string)$d);

			return $t ? date('d/m/Y', $t) : null;
		};
		$sdi = $fmtD($di);
		$sdf = $fmtD($df);
		if ($sdi !== null && $sdf !== null) {
			$snap['vigenciaTexto'] = $sdi . ' à ' . $sdf;
		} elseif ($sdi !== null) {
			$snap['vigenciaTexto'] = 'A partir de ' . $sdi;
		} elseif ($sdf !== null) {
			$snap['vigenciaTexto'] = 'Até ' . $sdf;
		}
		$snap['horasContratadasHms'] = self::hoursToHms($snap['totalHours'] ?? null);
		$snap['horasUtilizadasHms'] = self::hoursToHms($snap['usedHours'] ?? null);
		$snap['saldoHorasHms'] = self::hoursToHms($snap['balanceHours'] ?? null);
		$snap['alertaAviso'] = null;
		$snap['previsaoEsgotamento'] = null;
		$tot = $snap['totalHours'];
		$saldo = $snap['balanceHours'];
		if ($tot !== null && $tot > 0 && $saldo !== null && $saldo > 0) {
			$ratio = (float)$saldo / (float)$tot;
			if ($ratio <= 0.25) {
				$sh = self::hoursToHms($saldo);
				if ($sh !== null) {
					$snap['alertaAviso'] = 'Atenção: Restam apenas ' . $sh . ' de horas no período.';
				}
			}
		}

		return $snap;
	}
}
