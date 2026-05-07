<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Relatório operacional de SLA (filtros + KPIs + quebras por fila/técnico/consumo de horas).
 * Todas as leituras usam Query ORM; expressões SQL são apenas funções de agregação em SELECT (PostgreSQL).
 */
final class SlaReportingService {

	/**
	 * @param array<string, mixed> $queryParams $_GET normalizado
	 * @return array<string, mixed>
	 */
	public static function parseFilters(array $queryParams, int $idempresa): array {
		$month = trim((string)($queryParams['mes_ref'] ?? ''));
		$dtIni = null;
		$dtFim = null;
		if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
			$dtIni = new \DateTimeImmutable($month . '-01 00:00:00');
			$dtFim = $dtIni->modify('last day of this month')->setTime(23, 59, 59);
		} else {
			$iniStr = trim((string)($queryParams['periodo_ini'] ?? ''));
			$fimStr = trim((string)($queryParams['periodo_fim'] ?? ''));
			if ($iniStr !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $iniStr) === 1) {
				$dtIni = new \DateTimeImmutable($iniStr . ' 00:00:00');
			}
			if ($fimStr !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fimStr) === 1) {
				$dtFim = new \DateTimeImmutable($fimStr . ' 23:59:59');
			}
		}
		$periodoPadrao = false;
		if ($dtIni === null && $dtFim === null) {
			$periodoPadrao = true;
			$dtFim = new \DateTimeImmutable('today 23:59:59');
			$dtIni = (new \DateTimeImmutable('today'))->modify('-60 days')->setTime(0, 0, 0);
		} elseif ($dtIni === null) {
			$dtIni = $dtFim->modify('-60 days')->setTime(0, 0, 0);
		} elseif ($dtFim === null) {
			$dtFim = new \DateTimeImmutable('today 23:59:59');
		}
		if ($dtIni > $dtFim) {
			$t = $dtIni;
			$dtIni = $dtFim;
			$dtFim = $t;
		}

		$idCliente = self::intOrNull($queryParams['idcliente'] ?? null);
		$idTecnico = self::intOrNull($queryParams['idtecnico'] ?? null);
		$idQueue = self::intOrNull($queryParams['queue_id'] ?? null);
		$idTicket = self::intOrNull($queryParams['idticket'] ?? null);
		$idProblema = self::intOrNull($queryParams['problema_id'] ?? null);
		$clicontratoId = self::intOrNull($queryParams['idclicontrato'] ?? null);
		$contratoHorasId = self::intOrNull($queryParams['id_contrato_horas'] ?? null);
		$ticketContractId = self::intOrNull($queryParams['ticket_contract_id'] ?? null);

		$situacaoIn = [];
		if (!empty($queryParams['situacao']) && is_array($queryParams['situacao'])) {
			foreach ($queryParams['situacao'] as $s) {
				$n = self::intOrNull($s);
				if ($n !== null) {
					$situacaoIn[] = $n;
				}
			}
			$situacaoIn = array_values(array_unique($situacaoIn));
		}

		$estourado = !empty($queryParams['sla_estourado']) && (string)$queryParams['sla_estourado'] === '1';
		$pausado = !empty($queryParams['sla_pausado']) && (string)$queryParams['sla_pausado'] === '1';

		return [
			'idempresa' => $idempresa,
			'created_start' => $dtIni->format('Y-m-d H:i:s'),
			'created_end' => $dtFim->format('Y-m-d H:i:s'),
			'period_date_start' => $dtIni->format('Y-m-d'),
			'period_date_end' => $dtFim->format('Y-m-d'),
			'periodo_label' => $dtIni->format('d/m/Y') . ' — ' . $dtFim->format('d/m/Y'),
			'periodo_padrao' => $periodoPadrao,
			'mes_ref' => $month,
			'idcliente' => $idCliente,
			'idtecnico' => $idTecnico,
			'queue_id' => $idQueue,
			'idticket' => $idTicket,
			'problema_id' => $idProblema,
			'idclicontrato' => $clicontratoId,
			'id_contrato_horas' => $contratoHorasId,
			'ticket_contract_id' => $ticketContractId,
			'situacao_in' => $situacaoIn !== [] ? $situacaoIn : null,
			'sla_estourado_only' => $estourado,
			'sla_pausado_only' => $pausado,
		];
	}

	/**
	 * @param mixed $v
	 */
	private static function intOrNull($v): ?int {
		if ($v === null || $v === '') {
			return null;
		}
		if (is_numeric($v)) {
			$n = (int)$v;

			return $n > 0 ? $n : null;
		}

		return null;
	}

	/**
	 * @param string[] $cols
	 */
	public static function applyTicketFilters(Query $q, array $f, array $cols): void {
		$q->where([
			'Tickets.idempresa' => (int)$f['idempresa'],
			'Tickets.created >=' => $f['created_start'],
			'Tickets.created <=' => $f['created_end'],
		]);

		if (!empty($f['idticket'])) {
			$q->where(['Tickets.id' => (int)$f['idticket']]);
		}
		if (!empty($f['idcliente'])) {
			$q->where(['Tickets.idcliente' => (int)$f['idcliente']]);
		}
		if (!empty($f['queue_id']) && in_array('queue_id', $cols, true)) {
			$q->where(['Tickets.queue_id' => (int)$f['queue_id']]);
		}
		if (!empty($f['idtecnico']) && in_array('idtecnico_responsavel', $cols, true)) {
			$q->where(['Tickets.idtecnico_responsavel' => (int)$f['idtecnico']]);
		}

		$probCol = null;
		if (!empty($f['problema_id'])) {
			if (in_array('problema_id', $cols, true)) {
				$probCol = 'problema_id';
			} elseif (in_array('idproblema', $cols, true)) {
				$probCol = 'idproblema';
			}
			if ($probCol !== null) {
				$q->where(['Tickets.' . $probCol => (int)$f['problema_id']]);
			}
		}

		if (!empty($f['ticket_contract_id']) && in_array('contract_id', $cols, true)) {
			$q->where(['Tickets.contract_id' => (int)$f['ticket_contract_id']]);
		}

		if (!empty($f['idclicontrato'])) {
			$row = self::loadClicontratoScoped((int)$f['idclicontrato'], (int)$f['idempresa']);
			if ($row) {
				$q->where(['Tickets.idcliente' => (int)$row->get('idcliente')]);
			} else {
				$q->where(['Tickets.id' => 0]);
			}
		}

		if (!empty($f['id_contrato_horas'])) {
			$row = self::loadContratoHorasScoped((int)$f['id_contrato_horas'], (int)$f['idempresa']);
			if ($row) {
				$q->where(['Tickets.idcliente' => (int)$row->get('idcliente')]);
			} else {
				$q->where(['Tickets.id' => 0]);
			}
		}

		if (!empty($f['situacao_in']) && in_array('situacao', $cols, true)) {
			$q->where(['Tickets.situacao IN' => array_values($f['situacao_in'])]);
		}

		if (!empty($f['sla_estourado_only']) && in_array('sla_status', $cols, true)) {
			$q->where(['Tickets.sla_status' => 'violado']);
		}

		if (!empty($f['sla_pausado_only'])) {
			$pausedConds = [];
			if (in_array('sla_resolucao_pausado', $cols, true)) {
				$pausedConds[] = ['Tickets.sla_resolucao_pausado' => true];
			}
			if (in_array('sla_resposta_pausado', $cols, true)) {
				$pausedConds[] = ['Tickets.sla_resposta_pausado' => true];
			}
			if ($pausedConds !== []) {
				$q->where(['OR' => $pausedConds]);
			}
		}
	}

	private static function loadClicontratoScoped(int $id, int $idempresa) {
		try {
			$t = TableRegistry::get('Clicontratos');
		} catch (\Throwable $e) {
			return null;
		}
		$q = $t->find()->where(['Clicontratos.id' => $id]);
		if (in_array('idempresa', $t->getSchema()->columns(), true)) {
			$q->where(['Clicontratos.idempresa' => $idempresa]);
		}

		return $q->first();
	}

	private static function loadContratoHorasScoped(int $id, int $idempresa) {
		try {
			$t = TableRegistry::get('ContratosHoras');
		} catch (\Throwable $e) {
			return null;
		}
		$q = $t->find()->where(['ContratosHoras.id' => $id]);
		if (in_array('idempresa', $t->getSchema()->columns(), true)) {
			$q->where(['ContratosHoras.idempresa' => $idempresa]);
		}

		return $q->first();
	}

	/**
	 * @param callable(): Query $newBaseQuery Factory: Tickets->find() + ABAC + sem applyTicketFilters ainda — o serviço aplica no clone.
	 * @param string[] $cols
	 * @param array<string, mixed> $f
	 * @return array<string, mixed>
	 */
	public static function buildReport(callable $newBaseQuery, Table $Tickets, array $cols, array $f): array {
		$mk = function () use ($newBaseQuery, $f, $cols): Query {
			$q = $newBaseQuery();
			self::applyTicketFilters($q, $f, $cols);

			return $q;
		};

		$total = $mk()->count();

		$hasSlaCol = in_array('sla_status', $cols, true);
		$tracked = 0;
		$violados = 0;
		$cumpridos = 0;
		if ($hasSlaCol) {
			$qTr = $mk();
			$qTr->where(function ($exp, $q) {
				return $exp->isNotNull('Tickets.sla_status');
			});
			$tracked = $qTr->count();

			$violados = $mk()->where(['Tickets.sla_status' => 'violado'])->count();

			$qOk = $mk();
			$qOk->where(function ($exp, $q) {
				return $exp->and_(
					$exp->isNotNull('Tickets.sla_status'),
					$exp->notEq('Tickets.sla_status', 'violado')
				);
			});
			$cumpridos = $qOk->count();
		}

		$avgRespMin = null;
		if (in_array('data_primeira_resposta', $cols, true)) {
			$qA = $mk();
			$qA->select([
				'v' => $qA->newExpr(
					'AVG(EXTRACT(EPOCH FROM ("Tickets"."data_primeira_resposta" - "Tickets"."created")) / 60.0)'
				),
			])
				->where(function ($exp) {
					return $exp->isNotNull('Tickets.data_primeira_resposta');
				})
				->enableHydration(false);
			$row = $qA->first();
			if ($row && isset($row['v']) && $row['v'] !== null && is_numeric($row['v'])) {
				$avgRespMin = round((float)$row['v'], 1);
			}
		}

		$avgResMin = null;
		$hasResEnd = in_array('data_resolucao', $cols, true) || in_array('data_fechamento', $cols, true);
		if ($hasResEnd) {
			$endExpr = self::resolutionTimestampExpr($cols);
			if ($endExpr !== null) {
				$qR = $mk();
				$qR->select([
					'v' => $qR->newExpr(
						'AVG(EXTRACT(EPOCH FROM (' . $endExpr . ' - "Tickets"."created")) / 60.0)'
					),
				])
					->where(function ($exp) use ($cols) {
						$ors = [];
						if (in_array('data_resolucao', $cols, true)) {
							$ors[] = $exp->isNotNull('Tickets.data_resolucao');
						}
						if (in_array('data_fechamento', $cols, true)) {
							$ors[] = $exp->isNotNull('Tickets.data_fechamento');
						}

						return $exp->or_($ors);
					})
					->enableHydration(false);
				$rowR = $qR->first();
				if ($rowR && isset($rowR['v']) && $rowR['v'] !== null && is_numeric($rowR['v'])) {
					$avgResMin = round((float)$rowR['v'], 1);
				}
			}
		}

		$avgPausedMin = null;
		if (in_array('tempo_total_pausado', $cols, true)) {
			$qP = $mk();
			$qP->select([
				'v' => $qP->newExpr('AVG("Tickets"."tempo_total_pausado")'),
			])
				->where(function ($exp) {
					return $exp->isNotNull('Tickets.tempo_total_pausado');
				})
				->enableHydration(false);
			$rowP = $qP->first();
			if ($rowP && isset($rowP['v']) && $rowP['v'] !== null && is_numeric($rowP['v'])) {
				$avgPausedMin = round(((float)$rowP['v']) / 60.0, 1);
			}
		}

		$byQueue = self::groupAvgForQueue($mk, $cols);
		$byTech = self::groupAvgForTechnician($mk, $cols);

		$horasWarn = null;
		$contractConsumption = self::contractHoursConsumption($mk, $Tickets, $f, $horasWarn);

		$sample = self::sampleTickets($mk, $Tickets, $cols, 80);

		return [
			'total_tickets' => $total,
			'sla_tracked' => $tracked,
			'sla_cumprido' => $cumpridos,
			'sla_estourado' => $violados,
			'has_sla_status_column' => $hasSlaCol,
			'avg_resposta_minutes' => $avgRespMin,
			'avg_resolucao_minutes' => $avgResMin,
			'avg_pausado_minutes' => $avgPausedMin,
			'by_queue' => $byQueue,
			'by_technician' => $byTech,
			'contract_hours_consumption' => $contractConsumption,
			'horas_warn' => $horasWarn,
			'sample_tickets' => $sample,
		];
	}

	/**
	 * @param string[] $cols
	 */
	private static function resolutionTimestampExpr(array $cols): ?string {
		$hasR = in_array('data_resolucao', $cols, true);
		$hasF = in_array('data_fechamento', $cols, true);
		if ($hasR && $hasF) {
			return 'COALESCE("Tickets"."data_resolucao", "Tickets"."data_fechamento")';
		}
		if ($hasR) {
			return '"Tickets"."data_resolucao"';
		}
		if ($hasF) {
			return '"Tickets"."data_fechamento"';
		}

		return null;
	}

	/**
	 * @param callable(): Query $mk
	 * @param string[] $cols
	 * @return list<array{queue_id: int|null, queue_name: string, ticket_count: int, avg_resolucao_minutes: ?float, avg_atendimento_hours: ?float}>
	 */
	private static function groupAvgForQueue(callable $mk, array $cols): array {
		if (!in_array('queue_id', $cols, true)) {
			return [];
		}
		$endExpr = self::resolutionTimestampExpr($cols);
		$q = $mk();
		$select = [
			'queue_id' => 'Tickets.queue_id',
			'ticket_count' => $q->func()->count('*'),
		];
		if ($endExpr !== null) {
			$select['avg_resolucao_minutes'] = $q->newExpr(
				'AVG(EXTRACT(EPOCH FROM (' . $endExpr . ' - "Tickets"."created")) / 60.0)'
			);
		}
		if (in_array('tempo_total_atendimento', $cols, true)) {
			$select['avg_atendimento_sec'] = $q->newExpr('AVG("Tickets"."tempo_total_atendimento")');
		}
		$q->select($select)->group(['Tickets.queue_id'])->enableHydration(false);
		$rows = $q->toArray();

		$queues = TableRegistry::getTableLocator()->exists('Queues')
			? TableRegistry::getTableLocator()->get('Queues') : null;

		$out = [];
		foreach ($rows as $r) {
			$qid = $r['queue_id'] !== null ? (int)$r['queue_id'] : null;
			$qname = 'Sem fila';
			if ($qid !== null && $queues) {
				$qr = $queues->find()->select(['name'])->where(['id' => $qid])->first();
				$qname = $qr ? (string)$qr->get('name') : ('Fila #' . $qid);
			}
			$out[] = [
				'queue_id' => $qid,
				'queue_name' => $qname,
				'ticket_count' => (int)$r['ticket_count'],
				'avg_resolucao_minutes' => isset($r['avg_resolucao_minutes']) && is_numeric($r['avg_resolucao_minutes'])
					? round((float)$r['avg_resolucao_minutes'], 1) : null,
				'avg_atendimento_hours' => isset($r['avg_atendimento_sec']) && is_numeric($r['avg_atendimento_sec'])
					? round(((float)$r['avg_atendimento_sec']) / 3600.0, 2) : null,
			];
		}
		usort($out, static function ($a, $b) {
			return ($b['ticket_count'] ?? 0) <=> ($a['ticket_count'] ?? 0);
		});

		return $out;
	}

	/**
	 * @param callable(): Query $mk
	 * @param string[] $cols
	 * @return list<array{user_id: int|null, user_name: string, ticket_count: int, avg_resolucao_minutes: ?float, avg_atendimento_hours: ?float}>
	 */
	private static function groupAvgForTechnician(callable $mk, array $cols): array {
		if (!in_array('idtecnico_responsavel', $cols, true)) {
			return [];
		}
		$endExpr = self::resolutionTimestampExpr($cols);
		$q = $mk();
		$select = [
			'user_id' => 'Tickets.idtecnico_responsavel',
			'ticket_count' => $q->func()->count('*'),
		];
		if ($endExpr !== null) {
			$select['avg_resolucao_minutes'] = $q->newExpr(
				'AVG(EXTRACT(EPOCH FROM (' . $endExpr . ' - "Tickets"."created")) / 60.0)'
			);
		}
		if (in_array('tempo_total_atendimento', $cols, true)) {
			$select['avg_atendimento_sec'] = $q->newExpr('AVG("Tickets"."tempo_total_atendimento")');
		}
		$q->select($select)->group(['Tickets.idtecnico_responsavel'])->enableHydration(false);
		$rows = $q->toArray();

		$users = TableRegistry::getTableLocator()->get('Users');
		$out = [];
		foreach ($rows as $r) {
			$uid = $r['user_id'] !== null ? (int)$r['user_id'] : null;
			$uname = 'Sem técnico';
			if ($uid !== null && $uid > 0) {
				$u = $users->find()->select(['name'])->where(['id' => $uid])->first();
				$uname = $u ? (string)$u->name : ('#' . $uid);
			}
			$out[] = [
				'user_id' => $uid,
				'user_name' => $uname,
				'ticket_count' => (int)$r['ticket_count'],
				'avg_resolucao_minutes' => isset($r['avg_resolucao_minutes']) && is_numeric($r['avg_resolucao_minutes'])
					? round((float)$r['avg_resolucao_minutes'], 1) : null,
				'avg_atendimento_hours' => isset($r['avg_atendimento_sec']) && is_numeric($r['avg_atendimento_sec'])
					? round(((float)$r['avg_atendimento_sec']) / 3600.0, 2) : null,
			];
		}
		usort($out, static function ($a, $b) {
			return ($b['ticket_count'] ?? 0) <=> ($a['ticket_count'] ?? 0);
		});

		return $out;
	}

	/**
	 * Soma minutos de {@see TicketshorasTable} para tickets do conjunto filtrado (data da lançamento no período).
	 *
	 * @param callable(): Query $mk
	 * @param array<string, mixed> $f
	 * @param string|null $warn
	 * @return list<array{label: string, cliente_id: int, contract_hours_id: ?int, minutes: int, hours: float}>
	 */
	private static function contractHoursConsumption(callable $mk, Table $Tickets, array $f, ?string &$warn): array {
		$warn = null;
		if (!TableRegistry::getTableLocator()->exists('Ticketshoras')) {
			return [];
		}
		/** @var \App\Model\Table\TicketshorasTable $th */
		$th = TableRegistry::getTableLocator()->get('Ticketshoras');

		$idQ = $mk();
		$idQ->select(['id' => 'Tickets.id'])->enableHydration(false)->limit(6000);
		$ids = [];
		foreach ($idQ->all() as $r) {
			$ids[] = (int)$r['id'];
		}
		if ($ids === []) {
			return [];
		}
		if (count($ids) >= 6000) {
			$warn = 'Consumo de horas: limite de 6000 tickets no conjunto filtrado; totos parciais.';
		}

		$d0 = $f['period_date_start'] ?? '2000-01-01';
		$d1 = $f['period_date_end'] ?? '2099-12-31';

		$rows = $th->find()
			->contain(['Tickets'])
			->where([
				'Ticketshoras.idticket IN' => $ids,
				'Ticketshoras.data >=' => $d0,
				'Ticketshoras.data <=' => $d1,
			])
			->all();

		$byCliente = [];
		foreach ($rows as $row) {
			$ticket = $row->ticket ?? null;
			if ($ticket === null) {
				continue;
			}
			$cid = (int)$ticket->get('idcliente');
			$min = $th->getMinutos($row->get('horaini'), $row->get('horafin'));
			$byCliente[$cid] = ($byCliente[$cid] ?? 0) + (int)$min;
		}

		if ($byCliente === []) {
			return [];
		}

		$idempresa = (int)$f['idempresa'];
		$chTable = TableRegistry::getTableLocator()->exists('ContratosHoras')
			? TableRegistry::getTableLocator()->get('ContratosHoras') : null;

		$out = [];
		foreach ($byCliente as $cid => $minTotal) {
			$chId = null;
			$label = 'Cliente #' . $cid;
			if ($chTable !== null) {
				$qch = $chTable->find()->where(['idcliente' => $cid]);
				if (in_array('idempresa', $chTable->getSchema()->columns(), true)) {
					$qch->where(['idempresa' => $idempresa]);
				}
				$ch = $qch->order(['id' => 'DESC'])->first();
				if ($ch) {
					$chId = (int)$ch->get('id');
					$label = 'Horas #' . $chId . ' (cliente ' . $cid . ')';
				}
			}
			$out[] = [
				'label' => $label,
				'cliente_id' => $cid,
				'contract_hours_id' => $chId,
				'minutes' => (int)$minTotal,
				'hours' => round($minTotal / 60.0, 2),
			];
		}
		usort($out, static function ($a, $b) {
			return ($b['minutes'] ?? 0) <=> ($a['minutes'] ?? 0);
		});

		return $out;
	}

	/**
	 * @param callable(): Query $mk
	 * @param string[] $cols
	 * @return list<array<string, mixed>>
	 */
	private static function sampleTickets(callable $mk, Table $Tickets, array $cols, int $limit): array {
		$q = $mk();
		$contain = ['Clientes'];
		if (in_array('queue_id', $cols, true) && $Tickets->associations()->has('Queues')) {
			$contain[] = 'Queues';
		}
		$q->contain($contain);
		$q->order(['Tickets.id' => 'DESC'])->limit($limit);
		$out = [];
		$users = TableRegistry::getTableLocator()->get('Users');
		foreach ($q->all() as $t) {
			$tec = null;
			$tid = in_array('idtecnico_responsavel', $cols, true) ? $t->get('idtecnico_responsavel') : null;
			if ($tid) {
				$u = $users->find()->select(['name'])->where(['id' => (int)$tid])->first();
				$tec = $u ? (string)$u->name : null;
			}
			$cliNome = '';
			if (!empty($t->cliente)) {
				$c = $t->cliente;
				$cliNome = trim((string)($c->get('razaosocial') ?: $c->get('nome') ?: ''));
			}
			$out[] = [
				'id' => (int)$t->get('id'),
				'cliente' => $cliNome,
				'situacao' => in_array('situacao', $cols, true) ? $t->get('situacao') : null,
				'sla_status' => in_array('sla_status', $cols, true) ? $t->get('sla_status') : null,
				'queue' => !empty($t->queue) ? (string)$t->queue->get('name') : null,
				'tecnico' => $tec,
				'created' => $t->get('created') ? $t->get('created')->format('d/m/Y H:i') : null,
				'data_limite_resolucao' => in_array('data_limite_resolucao', $cols, true) && $t->get('data_limite_resolucao')
					? $t->get('data_limite_resolucao')->format('d/m/Y H:i') : null,
			];
		}

		return $out;
	}
}
