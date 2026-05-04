<?php
namespace App\Service\Ticket;

use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Agregados para dashboard operacional (somente leitura).
 */
class DashboardService {

	/** @var Table */
	protected $tickets;

	public function __construct(Table $ticketsTable) {
		$this->tickets = $ticketsTable;
	}

	/**
	 * @return array Snapshot JSON-safe
	 */
	public function operationalSnapshot(int $idempresa): array {
		$cols = $this->tickets->getSchema()->columns();
		$todayStart = Time::today()->format('Y-m-d H:i:s');

		$base = ['Tickets.idempresa' => $idempresa];

		$closed = $this->closedSituacoes();

		$backlog = 0;
		if ($closed !== []) {
			$backlog = $this->tickets->find()
				->where($base + ['situacao NOT IN' => $closed])
				->count();
		}

		$resolvidosHoje = 0;
		if (in_array('data_resolucao', $cols, true)) {
			$d0 = Time::today()->format('Y-m-d') . ' 00:00:00';
			$d1 = Time::today()->format('Y-m-d') . ' 23:59:59';
			$resolvidosHoje = $this->tickets->find()
				->where($base + [
					'data_resolucao >=' => $d0,
					'data_resolucao <=' => $d1,
				])
				->count();
		} elseif ($closed !== []) {
			$resolvidosHoje = $this->tickets->find()
				->where($base + ['modified >=' => $todayStart, 'situacao IN' => $closed])
				->count();
		}

		$byPrioridade = $this->countGrouped('prioridade', $base, $cols);

		$bySla = $this->countGrouped('sla_status', $base, $cols);

		$bySituacao = [];
		$q = $this->tickets->find();
		$f = $q->func()->count('*');
		$rows = $q->select(['situacao', 'total' => $f])
			->where($base)
			->group('situacao')
			->hydrate(false)
			->toArray();
		foreach ($rows as $r) {
			$bySituacao[(string)(int)$r['situacao']] = (int)$r['total'];
		}

		$byFila = [];
		if (in_array('queue_id', $cols, true)) {
			$q = $this->tickets->find();
			$f = $q->func()->count('*');
			$rows = $q->select(['queue_id', 'total' => $f])
				->where($base)
				->group('queue_id')
				->hydrate(false)
				->toArray();
			foreach ($rows as $r) {
				$k = $r['queue_id'] !== null ? (string)(int)$r['queue_id'] : '(sem fila)';
				$byFila[$k] = (int)$r['total'];
			}
		}

		$p1Abertos = 0;
		if (in_array('prioridade', $cols, true) && $closed !== []) {
			$p1Abertos = $this->tickets->find()
				->where($base + [
					'prioridade' => 'P1',
					'situacao NOT IN' => $closed,
				])
				->count();
		}

		$criticos = $this->loadCriticalViolations($base, $cols);
		$slaByState = $this->stateSlaSnapshot($base, $cols);
		$avgByState = $this->averageSecondsByWorkflowState($base, $cols);

		return [
			'empresa_id' => $idempresa,
			'gerado_em' => Time::now()->format('c'),
			'colunas_sla_ativas' => in_array('sla_status', $cols, true),
			'backlog' => $backlog,
			'resolvidos_hoje' => $resolvidosHoje,
			'p1_abertos' => $p1Abertos,
			'por_prioridade' => $byPrioridade,
			'por_sla_status' => $bySla,
			'por_situacao' => $bySituacao,
			'por_fila_id' => $byFila,
			'alertas_sla_violado' => $criticos,
			'sla_por_etapa' => [
				'overdue' => (int)($slaByState['overdue_count'] ?? 0),
				'near_due' => (int)($slaByState['near_due_count'] ?? 0),
				'paused' => (int)($slaByState['paused_count'] ?? 0),
				'avg_seconds_by_state' => $avgByState,
			],
			'alertas_sla_state' => [
				'overdue' => (array)($slaByState['overdue_list'] ?? []),
				'near_due' => (array)($slaByState['near_due_list'] ?? []),
				'paused' => (array)($slaByState['paused_list'] ?? []),
			],
		];
	}

	/**
	 * @param string[] $base
	 * @param string[] $cols
	 * @return array<string,int>
	 */
	protected function countGrouped(string $column, array $base, array $cols): array {
		$out = [];
		if (!in_array($column, $cols, true)) {
			return $out;
		}
		$q = $this->tickets->find();
		$f = $q->func()->count('*');
		$rows = $q->select([$column, 'total' => $f])
			->where($base)
			->group($column)
			->hydrate(false)
			->toArray();
		foreach ($rows as $r) {
			$v = $r[$column];
			$k = $v !== null && $v !== '' ? (string)$v : '(vazio)';
			$out[$k] = (int)$r['total'];
		}

		return $out;
	}

	/**
	 * @param string[] $base
	 * @param string[] $cols
	 * @return array<int,array<string,mixed>>
	 */
	protected function loadCriticalViolations(array $base, array $cols): array {
		if (!in_array('sla_status', $cols, true)) {
			return [];
		}
		$qCrit = $this->tickets->find()
			->where($base + ['sla_status' => 'violado'])
			->limit(25);
		if (in_array('data_limite_resolucao', $cols, true)) {
			$qCrit->order(['data_limite_resolucao' => 'ASC', 'Tickets.id' => 'DESC']);
		} else {
			$qCrit->order(['Tickets.id' => 'DESC']);
		}
		if ($this->tickets->associations()->get('Queues') !== null) {
			$qCrit->contain(['Queues']);
		}
		$list = [];
		foreach ($qCrit->all() as $t) {
			$lim = in_array('data_limite_resolucao', $cols, true) ? $t->get('data_limite_resolucao') : null;
			$limStr = null;
			if ($lim instanceof \DateTimeInterface) {
				$limStr = $lim->format('c');
			}
			$list[] = [
				'id' => (int)$t->id,
				'prioridade' => in_array('prioridade', $cols, true) ? $t->get('prioridade') : null,
				'sla_status' => $t->get('sla_status'),
				'sla_percentual_consumido' => in_array('sla_percentual_consumido', $cols, true) ? $t->get('sla_percentual_consumido') : null,
				'data_limite_resolucao' => $limStr,
				'queue_id' => in_array('queue_id', $cols, true) ? $t->get('queue_id') : null,
				'fila_nome' => !empty($t->queue) ? (string)$t->queue->name : null,
			];
		}

		return $list;
	}

	/**
	 * @return int[]
	 */
	protected function closedSituacoes(): array {
		if (!defined('C_TicketSituacaoResolvido') || !defined('C_TicketSituacaoFechado')) {
			return [];
		}
		$out = [(int)C_TicketSituacaoResolvido, (int)C_TicketSituacaoFechado];
		if (defined('C_TicketSituacaoCancelado')) {
			$out[] = (int)C_TicketSituacaoCancelado;
		}

		return $out;
	}

	/**
	 * @param string[] $base
	 * @param string[] $cols
	 * @return array<string,mixed>
	 */
	protected function stateSlaSnapshot(array $base, array $cols): array {
		$needed = ['data_limite_resolucao', 'sla_resolucao_pausado', 'sla_resposta_pausado'];
		foreach ($needed as $col) {
			if (!in_array($col, $cols, true)) {
				return [
					'overdue_count' => 0,
					'near_due_count' => 0,
					'paused_count' => 0,
					'overdue_list' => [],
					'near_due_list' => [],
					'paused_list' => [],
				];
			}
		}

		$now = Time::now();
		$soon = $now->addMinutes(30);
		$closed = $this->closedSituacoes();
		$listFields = ['id', 'workflow_state_id', 'data_limite_resolucao', 'sla_resolucao_pausado', 'sla_resposta_pausado'];
		if (in_array('prioridade', $cols, true)) {
			$listFields[] = 'prioridade';
		}

		$activeBase = $base + ['data_limite_resolucao IS NOT' => null];
		if ($closed !== []) {
			$activeBase['situacao NOT IN'] = $closed;
		}
		$overdueQ = $this->tickets->find()->select($listFields)->where($activeBase + [
			'data_limite_resolucao <' => $now,
			'sla_resolucao_pausado' => false,
			'sla_resposta_pausado' => false,
		])->order(['data_limite_resolucao' => 'ASC'])->limit(30);
		$nearQ = $this->tickets->find()->select($listFields)->where($activeBase + [
			'data_limite_resolucao >=' => $now,
			'data_limite_resolucao <=' => $soon,
			'sla_resolucao_pausado' => false,
			'sla_resposta_pausado' => false,
		])->order(['data_limite_resolucao' => 'ASC'])->limit(30);
		$pausedQ = $this->tickets->find()->select($listFields)->where($activeBase + [
			'OR' => [
				['sla_resolucao_pausado' => true],
				['sla_resposta_pausado' => true],
			],
		])->order(['modified' => 'DESC', 'id' => 'DESC'])->limit(30);

		return [
			'overdue_count' => $this->tickets->find()->where($activeBase + [
				'data_limite_resolucao <' => $now,
				'sla_resolucao_pausado' => false,
				'sla_resposta_pausado' => false,
			])->count(),
			'near_due_count' => $this->tickets->find()->where($activeBase + [
				'data_limite_resolucao >=' => $now,
				'data_limite_resolucao <=' => $soon,
				'sla_resolucao_pausado' => false,
				'sla_resposta_pausado' => false,
			])->count(),
			'paused_count' => $this->tickets->find()->where($activeBase + [
				'OR' => [
					['sla_resolucao_pausado' => true],
					['sla_resposta_pausado' => true],
				],
			])->count(),
			'overdue_list' => $this->normalizeSlaAlertRows($overdueQ->all()->toArray()),
			'near_due_list' => $this->normalizeSlaAlertRows($nearQ->all()->toArray()),
			'paused_list' => $this->normalizeSlaAlertRows($pausedQ->all()->toArray()),
		];
	}

	/**
	 * @param array<int,\Cake\Datasource\EntityInterface> $rows
	 * @return array<int,array<string,mixed>>
	 */
	protected function normalizeSlaAlertRows(array $rows): array {
		$out = [];
		foreach ($rows as $t) {
			$lim = $t->get('data_limite_resolucao');
			$limIso = null;
			if ($lim instanceof \DateTimeInterface) {
				$limIso = $lim->format('c');
			}
			$out[] = [
				'id' => (int)$t->get('id'),
				'workflow_state_id' => (int)($t->get('workflow_state_id') ?? 0),
				'prioridade' => $t->get('prioridade'),
				'data_limite_resolucao' => $limIso,
				'sla_resolucao_pausado' => (bool)$t->get('sla_resolucao_pausado'),
				'sla_resposta_pausado' => (bool)$t->get('sla_resposta_pausado'),
			];
		}
		return $out;
	}

	/**
	 * @param string[] $base
	 * @param string[] $cols
	 * @return array<int,array<string,mixed>>
	 */
	protected function averageSecondsByWorkflowState(array $base, array $cols): array {
		if (!in_array('workflow_state_id', $cols, true) || !in_array('total_seconds', $cols, true)) {
			return [];
		}
		$q = $this->tickets->find();
		$avg = $q->func()->avg('total_seconds');
		$rows = $q->select([
			'workflow_state_id',
			'avg_seconds' => $avg,
		])
			->where($base + ['workflow_state_id IS NOT' => null])
			->group('workflow_state_id')
			->hydrate(false)
			->toArray();
		if ($rows === []) {
			return [];
		}

		$states = $this->workflowStateLabels();
		$out = [];
		foreach ($rows as $r) {
			$stateId = (int)($r['workflow_state_id'] ?? 0);
			if ($stateId <= 0) {
				continue;
			}
			$out[] = [
				'workflow_state_id' => $stateId,
				'label' => $states[$stateId] ?? ('Estado #' . $stateId),
				'avg_seconds' => (int)round((float)($r['avg_seconds'] ?? 0)),
			];
		}
		usort($out, function (array $a, array $b): int {
			return $b['avg_seconds'] <=> $a['avg_seconds'];
		});
		return $out;
	}

	/**
	 * @return array<int,string>
	 */
	protected function workflowStateLabels(): array {
		try {
			$table = TableRegistry::get('WorkflowStates');
		} catch (\Throwable $e) {
			return [];
		}
		$out = [];
		try {
			foreach ($table->find()->select(['id', 'nome'])->all() as $row) {
				$out[(int)$row->id] = (string)$row->nome;
			}
		} catch (\Throwable $e) {
			return [];
		}
		return $out;
	}
}
