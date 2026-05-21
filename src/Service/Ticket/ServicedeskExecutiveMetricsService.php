<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use App\Model\Table\TicketsTable;
use App\Model\Table\UsersTable;
use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * KPIs executivos do Service Desk (FCR, margem, meus tickets).
 */
class ServicedeskExecutiveMetricsService {

	/** @var callable(\Cake\ORM\Query):void */
	private $applyAbac;

	/** @param callable(\Cake\ORM\Query):void $applyAbac */
	public function __construct(callable $applyAbac) {
		$this->applyAbac = $applyAbac;
	}

	public function computeFcrPct(int $idempresa, int $days = 30, int $offsetDays = 0): ?float {
		if (!$this->tableExists('tickets')) {
			return null;
		}
		$tickets = TableRegistry::getTableLocator()->get('Tickets');
		$cols = $tickets->getSchema()->columns();
		$closed = $this->closedSituacoes();
		if ($closed === [] || !in_array('situacao', $cols, true)) {
			return null;
		}
		$days = max(1, $days);
		$offsetDays = max(0, $offsetDays);
		$since = Time::now()->subDays($days + $offsetDays)->format('Y-m-d H:i:s');
		$until = $offsetDays > 0 ? Time::now()->subDays($offsetDays)->format('Y-m-d H:i:s') : null;
		$resolvedIds = [];
		$w = [
			'Tickets.idempresa' => $idempresa,
			'Tickets.situacao IN' => $closed,
		];
		if (in_array('data_resolucao', $cols, true)) {
			$w['Tickets.data_resolucao >='] = $since;
			if ($until !== null) {
				$w['Tickets.data_resolucao <'] = $until;
			}
		} elseif (in_array('modified', $cols, true)) {
			$w['Tickets.modified >='] = $since;
			if ($until !== null) {
				$w['Tickets.modified <'] = $until;
			}
		} else {
			return null;
		}
		$q = $tickets->find()->select(['id'])->where($w)->limit(500);
		($this->applyAbac)($q);
		foreach ($q->all() as $t) {
			$resolvedIds[] = (int)$t->get('id');
		}
		if ($resolvedIds === []) {
			return null;
		}
		$fcr = 0;
		foreach ($resolvedIds as $tid) {
			if ($this->ticketIsFirstContactResolved($tid)) {
				$fcr++;
			}
		}

		return round(100 * $fcr / count($resolvedIds), 0);
	}

	protected function ticketIsFirstContactResolved(int $ticketId): bool {
		$events = $this->ticketStatusEvents($ticketId);
		if ($events === []) {
			return true;
		}
		$wasClosed = false;
		foreach ($events as $ev) {
			$to = mb_strtolower((string)($ev['to'] ?? ''));
			$from = mb_strtolower((string)($ev['from'] ?? ''));
			if ($this->isClosedStatusLabel($to)) {
				$wasClosed = true;
				continue;
			}
			if ($wasClosed && $this->isOpenStatusLabel($to) && !$this->isClosedStatusLabel($from)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return array<int,array{from:?string,to:string,at:string}>
	 */
	protected function ticketStatusEvents(int $ticketId): array {
		$out = [];
		if ($this->tableExists('prototype_status_history')) {
			try {
				$tbl = TableRegistry::getTableLocator()->get('PrototypeStatusHistory');
				$rows = $tbl->find()
					->where(['source_type' => 'ticket', 'source_id' => $ticketId])
					->order(['created' => 'ASC'])
					->all();
				foreach ($rows as $r) {
					$out[] = [
						'from' => $r->get('status_from') !== null ? (string)$r->get('status_from') : null,
						'to' => (string)$r->get('status_to'),
						'at' => (string)$r->get('created'),
					];
				}
			} catch (\Throwable $e) {
			}
		}
		if ($out === [] && $this->tableExists('ticket_histories')) {
			try {
				$tbl = TableRegistry::getTableLocator()->get('TicketHistories');
				$rows = $tbl->find()
					->where(['ticket_id' => $ticketId, 'tipo_evento' => 'situacao'])
					->order(['created' => 'ASC'])
					->all();
				foreach ($rows as $r) {
					$out[] = [
						'from' => $r->get('valor_anterior') !== null ? (string)$r->get('valor_anterior') : null,
						'to' => (string)($r->get('valor_novo') ?? ''),
						'at' => (string)$r->get('created'),
					];
				}
			} catch (\Throwable $e) {
			}
		}
		if ($out === [] && $this->tableExists('ticketsmovs')) {
			try {
				$tbl = TableRegistry::getTableLocator()->get('Ticketsmovs');
				$rows = $tbl->find()
					->where(['idticket' => $ticketId])
					->order(['id' => 'ASC'])
					->all();
				foreach ($rows as $r) {
					$obs = (string)($r->get('observacao') ?? $r->get('obs') ?? '');
					if ($obs === '') {
						continue;
					}
					$out[] = [
						'from' => null,
						'to' => $obs,
						'at' => (string)($r->get('data') ?? $r->get('created') ?? ''),
					];
				}
			} catch (\Throwable $e) {
			}
		}

		return $out;
	}

	protected function isClosedStatusLabel(string $label): bool {
		return (bool)preg_match('/resolv|fechad|closed|cancel/i', $label);
	}

	protected function isOpenStatusLabel(string $label): bool {
		return (bool)preg_match('/abert|execu|andamento|pendente|respond|reabr/i', $label);
	}

	/**
	 * @param array<string,mixed> $financeiro
	 * @return array<string,mixed>
	 */
	public function enrichFinanceiro(array $financeiro, int $idempresa): array {
		$horas = (float)($financeiro['horas_mes'] ?? 0);
		$receita = isset($financeiro['receita_mes']) ? (float)$financeiro['receita_mes'] : null;
		$custoHora = (float)(Configure::read('Servicedesk.custo_hora_tecnico') ?: 85.0);
		$custoMes = $horas > 0 ? round($horas * $custoHora, 2) : null;
		$fechadosMes = $this->countTicketsFechadosMes($idempresa);

		if ($receita !== null && $receita > 0 && $custoMes !== null) {
			$financeiro['margem_pct'] = (int)max(0, min(100, round((($receita - $custoMes) / $receita) * 100)));
		}
		if ($custoMes !== null && $fechadosMes > 0) {
			$financeiro['custo_medio'] = round($custoMes / $fechadosMes, 0);
		} elseif ($receita !== null && $receita > 0 && $fechadosMes > 0) {
			$financeiro['custo_medio'] = round($receita / $fechadosMes, 0);
		}
		$financeiro['horas_cobertas'] = $horas > 0 ? round($horas * 0.65, 1) : null;
		$financeiro['tickets_fechados_mes'] = $fechadosMes;
		$financeiro['custo_mes'] = $custoMes;

		return $financeiro;
	}

	protected function countTicketsFechadosMes(int $idempresa): int {
		try {
			$tickets = TableRegistry::getTableLocator()->get('Tickets');
			$cols = $tickets->getSchema()->columns();
			$closed = $this->closedSituacoes();
			if ($closed === []) {
				return 0;
			}
			$m0 = Time::now()->startOfMonth()->format('Y-m-d H:i:s');
			$m1 = Time::now()->endOfMonth()->format('Y-m-d H:i:s');
			$w = ['Tickets.idempresa' => $idempresa, 'Tickets.situacao IN' => $closed];
			if (in_array('data_resolucao', $cols, true)) {
				$w['Tickets.data_resolucao >='] = $m0;
				$w['Tickets.data_resolucao <='] = $m1;
			} elseif (in_array('modified', $cols, true)) {
				$w['Tickets.modified >='] = $m0;
				$w['Tickets.modified <='] = $m1;
			}
			$q = $tickets->find()->where($w);
			($this->applyAbac)($q);

			return $q->count();
		} catch (\Throwable $e) {
			return 0;
		}
	}

	/**
	 * @return array<string,mixed>
	 */
	public function buildMeusExtras(
		TicketsTable $tickets,
		UsersTable $users,
		int $idempresa,
		int $userId,
		string $userName
	): array {
		$cols = $tickets->getSchema()->columns();
		$closed = $this->closedSituacoes();
		$tecField = in_array('idtecnico_responsavel', $cols, true) ? 'idtecnico_responsavel' : null;
		if ($tecField === null && in_array('owner_id', $cols, true)) {
			$tecField = 'owner_id';
		}
		$core = new ServicedeskPrototypeDataService($this->applyAbac);

		$tabCounts = [
			'ativos' => 0,
			'aguarda' => 0,
			'resolvidos_hoje' => 0,
			'observados' => 0,
			'favoritos' => 0,
		];
		$tabRows = [
			'ativos' => [],
			'aguarda' => [],
			'resolvidos_hoje' => [],
			'observados' => [],
			'favoritos' => [],
		];

		if ($tecField === null || $closed === []) {
			return [
				'tabCounts' => $tabCounts,
				'tabRows' => $tabRows,
				'notificacoes' => [],
				'compromissos' => [],
				'nivel_label' => '',
				'sla_viol_ids' => [],
				'near_sla' => 0,
				'horas_mes' => null,
				'horas_faturaveis' => null,
				'csat_user' => null,
			];
		}

		$baseMine = [
			'Tickets.idempresa' => $idempresa,
			'Tickets.' . $tecField => $userId,
		];

		$qAtivos = $tickets->find()->where($baseMine + ['Tickets.situacao NOT IN' => $closed]);
		($this->applyAbac)($qAtivos);
		$tabCounts['ativos'] = $qAtivos->count();
		$tabRows['ativos'] = $this->fetchMeusCards($tickets, $core, $baseMine + ['Tickets.situacao NOT IN' => $closed], 50);

		if (defined('C_TicketSituacaoRespondido')) {
			$wAg = $baseMine + ['Tickets.situacao' => (int)C_TicketSituacaoRespondido];
			$qa = $tickets->find()->where($wAg);
			($this->applyAbac)($qa);
			$tabCounts['aguarda'] = $qa->count();
			$tabRows['aguarda'] = $this->fetchMeusCards($tickets, $core, $wAg, 30);
		}

		$today0 = Time::today()->format('Y-m-d') . ' 00:00:00';
		$today1 = Time::today()->format('Y-m-d') . ' 23:59:59';
		if (in_array('data_resolucao', $cols, true)) {
			$wRh = $baseMine + [
				'Tickets.data_resolucao >=' => $today0,
				'Tickets.data_resolucao <=' => $today1,
			];
			$qr = $tickets->find()->where($wRh);
			($this->applyAbac)($qr);
			$tabCounts['resolvidos_hoje'] = $qr->count();
			$tabRows['resolvidos_hoje'] = $this->fetchMeusCards($tickets, $core, $wRh, 30);
		}

		$obsIds = $this->observedTicketIds($userId, $idempresa, $closed);
		$tabCounts['observados'] = count($obsIds);
		if ($obsIds !== []) {
			$tabRows['observados'] = $this->fetchMeusCards($tickets, $core, [
				'Tickets.idempresa' => $idempresa,
				'Tickets.id IN' => $obsIds,
				'Tickets.situacao NOT IN' => $closed,
			], 30);
		}

		$favIds = $this->favoriteTicketIds($userId, $idempresa);
		$tabCounts['favoritos'] = count($favIds);
		if ($favIds !== []) {
			$tabRows['favoritos'] = $this->fetchMeusCards($tickets, $core, [
				'Tickets.idempresa' => $idempresa,
				'Tickets.id IN' => $favIds,
			], 30);
		}

		$slaViolIds = [];
		$nearSla = 0;
		if (in_array('sla_status', $cols, true)) {
			$qv = $tickets->find()->select(['id'])->where($baseMine + [
				'Tickets.situacao NOT IN' => $closed,
				'Tickets.sla_status' => 'violado',
			]);
			($this->applyAbac)($qv);
			foreach ($qv->all() as $t) {
				$slaViolIds[] = (int)$t->get('id');
			}
		}
		if (in_array('data_limite_resolucao', $cols, true)) {
			$lim = Time::now()->addHours(4)->format('Y-m-d H:i:s');
			$qn = $tickets->find()->where($baseMine + [
				'Tickets.situacao NOT IN' => $closed,
				'Tickets.data_limite_resolucao <=' => $lim,
				'Tickets.data_limite_resolucao >=' => Time::now()->format('Y-m-d H:i:s'),
			]);
			($this->applyAbac)($qn);
			$nearSla = $qn->count();
		}

		$horas = $this->userHorasMes($userId, $idempresa);
		$sinceCsat = Time::now()->subDays(90)->format('Y-m-d H:i:s');
		$csatUser = $core->tecnicoCsatScore($tickets, $idempresa, $tecField, $userId, $sinceCsat, $cols);

		return [
			'tabCounts' => $tabCounts,
			'tabRows' => $tabRows,
			'notificacoes' => $this->userNotifications($userId, $idempresa, 4),
			'compromissos' => $this->userCompromissos($idempresa),
			'nivel_label' => $this->userNivelLabel($userId, $idempresa),
			'sla_viol_ids' => $slaViolIds,
			'near_sla' => $nearSla,
			'horas_mes' => $horas['total'] ?? null,
			'horas_faturaveis' => $horas['faturaveis'] ?? null,
			'csat_user' => $csatUser,
		];
	}

	/**
	 * @param array<string,mixed> $where
	 * @return array<int,array<string,mixed>>
	 */
	protected function fetchMeusCards(TicketsTable $tickets, ServicedeskPrototypeDataService $core, array $where, int $limit): array {
		$q = $tickets->find()->contain(['Clientes'])->where($where)->order(['Tickets.modified' => 'DESC'])->limit($limit);
		($this->applyAbac)($q);
		$out = [];
		foreach ($q->all() as $t) {
			$id = (int)$t->get('id');
			$assunto = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
				? $tickets->resolveTicketAssuntoTextoPublic($t->get('assunto'))
				: (string)$t->get('assunto');
			$c = $t->clientes ?? null;
			$cli = '—';
			if ($c) {
				$cli = (int)($c->get('tipo') ?? 0) === 2
					? (string)($c->get('razaosocial') ?? '')
					: (string)($c->get('nome') ?? '');
			}
			$sit = (int)($t->get('situacao') ?? 0);
			$sitLabel = $core->situacaoLabel($sit);
			$sla = (string)($t->get('sla_status') ?? '');
			$viol = $sla === 'violado';
			$created = $t->get('created');
			$createdFmt = $created instanceof \DateTimeInterface ? $created->format('d/m') : '—';
			$prio = strtoupper(trim((string)($t->get('prioridade') ?? '')));
			$out[] = [
				'id' => $id,
				'assunto' => $assunto,
				'cliente' => $cli,
				'situacao_label' => $sitLabel,
				'sla_status' => $sla,
				'viol' => $viol,
				'icon' => $viol ? '🚨' : ($sit === (int)(defined('C_TicketSituacaoResolvido') ? C_TicketSituacaoResolvido : 0) ? '✓' : '🔵'),
				'border' => $viol ? 'var(--red)' : 'var(--border-light)',
				'bg' => $viol ? '#FEF2F2' : 'var(--bg-surface)',
				'title_color' => $viol ? '#7A1822' : '',
				'badge' => $viol ? __('SLA ESTOURADO') : $sitLabel,
				'badge_color' => $viol ? '#7A1822' : 'var(--teal-dark)',
				'meta' => sprintf('%s · %s · %s', $cli, __('aberto'), $createdFmt) . ' · ' . $sitLabel,
				'tags' => $prio !== '' && $prio !== '—' ? [$prio] : [],
				'action_label' => $viol ? __('Escalonar') : ($sit === (int)(defined('C_TicketSituacaoRespondido') ? C_TicketSituacaoRespondido : -1) ? '' : __('Atualizar status')),
				'action_class' => $viol ? 'btn btn-red btn-sm' : 'btn btn-amber btn-sm',
			];
		}

		return $out;
	}

	/**
	 * @param int[] $closed
	 * @return int[]
	 */
	protected function observedTicketIds(int $userId, int $idempresa, array $closed): array {
		if (!$this->tableExists('ticketcomentarios')) {
			return [];
		}
		try {
			$tc = TableRegistry::getTableLocator()->get('Ticketcomentarios');
			$since = Time::now()->subDays(90)->format('Y-m-d H:i:s');
			$authorCol = in_array('idautor', $tc->getSchema()->columns(), true) ? 'idautor' : 'idusuario';
			$tids = $tc->find()
				->select(['idticket'])
				->where([
					$tc->getAlias() . '.' . $authorCol => $userId,
					$tc->getAlias() . '.created >=' => $since,
				])
				->distinct(['idticket'])
				->limit(80)
				->extract('idticket')
				->toList();
			$tids = array_values(array_filter(array_map('intval', $tids)));
			if ($tids === []) {
				return [];
			}
			$tickets = TableRegistry::getTableLocator()->get('Tickets');
			$cols = $tickets->getSchema()->columns();
			$tecField = in_array('idtecnico_responsavel', $cols, true) ? 'idtecnico_responsavel' : 'owner_id';
			$q = $tickets->find()->select(['id'])->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.id IN' => $tids,
				'Tickets.situacao NOT IN' => $closed,
				'Tickets.' . $tecField . ' !=' => $userId,
			]);
			($this->applyAbac)($q);

			return $q->extract('id')->map(function ($v) {
				return (int)$v;
			})->toList();
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * @return int[]
	 */
	protected function favoriteTicketIds(int $userId, int $idempresa): array {
		$ids = [];
		if ($this->tableExists('ticket_histories')) {
			try {
				$th = TableRegistry::getTableLocator()->get('TicketHistories');
				$since = Time::now()->subDays(30)->format('Y-m-d H:i:s');
				$rows = $th->find()
					->select(['ticket_id'])
					->where(['usuario_id' => $userId, 'created >=' => $since])
					->group(['ticket_id'])
					->order(['created' => 'DESC'])
					->limit(20)
					->all();
				foreach ($rows as $r) {
					$ids[] = (int)$r->get('ticket_id');
				}
			} catch (\Throwable $e) {
			}
		}
		if ($ids === [] && $this->tableExists('ticketcomentarios')) {
			try {
				$tc = TableRegistry::getTableLocator()->get('Ticketcomentarios');
				$authorCol = in_array('idautor', $tc->getSchema()->columns(), true) ? 'idautor' : 'idusuario';
				$ids = $tc->find()
					->select(['idticket'])
					->where([$authorCol => $userId])
					->order(['created' => 'DESC'])
					->limit(15)
					->extract('idticket')
					->map(function ($v) {
						return (int)$v;
					})
					->toList();
			} catch (\Throwable $e) {
			}
		}
		$ids = array_values(array_unique(array_filter($ids)));
		if ($ids === []) {
			return [];
		}
		try {
			$tickets = TableRegistry::getTableLocator()->get('Tickets');
			$q = $tickets->find()->select(['id'])->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.id IN' => array_slice($ids, 0, 20),
			]);
			($this->applyAbac)($q);

			return $q->extract('id')->map(function ($v) {
				return (int)$v;
			})->toList();
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * @return array{total:?float,faturaveis:?float}
	 */
	protected function userHorasMes(int $userId, int $idempresa): array {
		if (!$this->tableExists('ticketshoras')) {
			return ['total' => null, 'faturaveis' => null];
		}
		try {
			$th = TableRegistry::getTableLocator()->get('Ticketshoras');
			$tCols = $th->getSchema()->columns();
			$userCol = null;
			foreach (['idusuario', 'iduser', 'idtecnico', 'usuario_id'] as $c) {
				if (in_array($c, $tCols, true)) {
					$userCol = $c;
					break;
				}
			}
			$where = ['idempresa' => $idempresa];
			if ($userCol !== null) {
				$where[$userCol] = $userId;
			}
			if (in_array('data', $tCols, true)) {
				$where['data >='] = Time::now()->startOfMonth()->format('Y-m-d');
				$where['data <='] = Time::now()->endOfMonth()->format('Y-m-d');
			}
			$sec = 0;
			foreach ($th->find()->where($where)->limit(3000)->all() as $h) {
				$sec += TicketServiceDeskApiService::resolveSecondsFromTicketshorasRow($th, $h);
			}
			if ($sec <= 0) {
				return ['total' => null, 'faturaveis' => null];
			}
			$total = round($sec / 3600, 1);

			return ['total' => $total, 'faturaveis' => round($total * 0.62, 1)];
		} catch (\Throwable $e) {
			return ['total' => null, 'faturaveis' => null];
		}
	}

	protected function userCsatMedia(int $userId, int $idempresa): ?float {
		if (!$this->tableExists('ticket_csat_responses') || !$this->tableExists('tickets')) {
			return null;
		}
		try {
			$tickets = TableRegistry::getTableLocator()->get('Tickets');
			$cols = $tickets->getSchema()->columns();
			$tecField = in_array('idtecnico_responsavel', $cols, true) ? 'idtecnico_responsavel' : null;
			if ($tecField === null && in_array('owner_id', $cols, true)) {
				$tecField = 'owner_id';
			}
			if ($tecField === null) {
				return null;
			}
			$core = new ServicedeskPrototypeDataService($this->applyAbac);
			$since = Time::now()->subDays(90)->format('Y-m-d H:i:s');

			return $core->tecnicoCsatScore($tickets, $idempresa, $tecField, $userId, $since, $cols);
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function userNivelLabel(int $userId, int $idempresa): string {
		try {
			if ($this->tableExists('queues_users')) {
				$qu = TableRegistry::getTableLocator()->get('QueuesUsers');
				$qid = $qu->find()->select(['queue_id'])->where(['user_id' => $userId])->first();
				if ($qid && $this->tableExists('queues')) {
					$q = TableRegistry::getTableLocator()->get('Queues')->get((int)$qid->get('queue_id'));

					return (string)($q->get('name') ?? '');
				}
			}
		} catch (\Throwable $e) {
		}

		return '';
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function userNotifications(int $userId, int $idempresa, int $limit): array {
		$out = [];
		if (!$this->tableExists('prototype_status_history')) {
			return $out;
		}
		try {
			$tbl = TableRegistry::getTableLocator()->get('PrototypeStatusHistory');
			$desde = Time::now()->subDays(7)->format('Y-m-d H:i:s');
			$rows = $tbl->find()
				->where(['idempresa' => $idempresa, 'created >=' => $desde, 'source_type' => 'ticket'])
				->order(['created' => 'DESC'])
				->limit($limit)
				->all();
			foreach ($rows as $r) {
				$sid = (int)$r->get('source_id');
				$out[] = [
					'icon' => '💬',
					'title' => sprintf(__('Ticket #%d · %s → %s'), $sid, (string)$r->get('status_from'), (string)$r->get('status_to')),
					'sub' => (string)($r->get('actor_name') ?? ''),
					'bg' => stripos((string)$r->get('status_to'), 'viol') !== false ? '#FEF2F2' : 'var(--bg-surface)',
					'url' => null,
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function userCompromissos(int $idempresa): array {
		if (!$this->tableExists('visitas')) {
			return [];
		}
		try {
			$from = Time::now()->format('Y-m-d');
			$to = Time::now()->addDays(14)->format('Y-m-d');
			$rows = TableRegistry::getTableLocator()->get('Visitas')->find()
				->where([
					'idempresa' => $idempresa,
					'data >=' => $from,
					'data <=' => $to,
				])
				->order(['data' => 'ASC', 'horaini' => 'ASC'])
				->limit(3)
				->all();
			$out = [];
			$borders = ['var(--teal)', 'var(--blue)', '#D946A0'];
			$i = 0;
			foreach ($rows as $vis) {
				$data = $vis->get('data');
				$ymd = $data instanceof \DateTimeInterface ? $data->format('Y-m-d') : substr((string)$data, 0, 10);
				$when = $ymd;
				if ($ymd === Time::today()->format('Y-m-d')) {
					$hi = trim((string)($vis->get('horaini') ?? ''));
					$when = $hi !== '' ? __('Hoje') . ' ' . substr($hi, 0, 5) : __('Hoje');
				} elseif ($ymd === Time::now()->addDay()->format('Y-m-d')) {
					$when = __('Amanhã') . ' ' . substr(trim((string)($vis->get('horaini') ?? '')), 0, 5);
				}
				$title = trim((string)($vis->get('agenda_titulo') ?? $vis->get('motivo') ?? __('Visita técnica')));
				$out[] = [
					'when' => $when,
					'hint' => trim((string)($vis->get('motivo') ?? '')),
					'title' => $title,
					'border' => $borders[$i % count($borders)],
				];
				$i++;
			}

			return $out;
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * @return array<string,mixed>
	 */
	public function buildRelatoriosKpis(int $idempresa, TicketsTable $tickets): array {
		$dash = new DashboardService($tickets);
		$snap = $dash->operationalSnapshot($idempresa);
		$satisfacao = (new ServicedeskPrototypeDataService($this->applyAbac))->fetchSatisfactionSnapshot($idempresa);
		$cols = $tickets->getSchema()->columns();
		$primeiraResp = $this->computeAvgFirstResponseFmt($idempresa, $tickets, $cols);
		$since30 = Time::now()->subDays(30);
		$abertos30 = 0;
		if (in_array('created', $cols, true)) {
			$q = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $since30->format('Y-m-d H:i:s'),
			]);
			($this->applyAbac)($q);
			$abertos30 = $q->count();
		}
		$resolvidos30 = 0;
		$closed = $this->closedSituacoes();
		if ($closed !== [] && in_array('data_resolucao', $cols, true)) {
			$qr = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.data_resolucao >=' => $since30->format('Y-m-d H:i:s'),
			]);
			($this->applyAbac)($qr);
			$resolvidos30 = $qr->count();
		}
		$taxa = $abertos30 > 0 ? round(100 * $resolvidos30 / $abertos30, 0) : 0;
		$violados = count((array)($snap['alertas_sla_violado'] ?? []));
		$violPct = $abertos30 > 0 ? round(100 * $violados / $abertos30, 1) : 0;
		$csatMedia = $satisfacao['csat_media'] ?? null;
		$csatN = (int)($satisfacao['csat_respostas'] ?? 0);

		return [
			['lbl' => __('Tickets abertos · 30d'), 'val' => (string)$abertos30, 'hint' => __('período'), 'border' => 'var(--teal)'],
			['lbl' => __('Tickets resolvidos'), 'val' => (string)$resolvidos30, 'hint' => $taxa . '% ' . __('taxa resolução'), 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
			['lbl' => __('Tempo médio 1ª resposta'), 'val' => $primeiraResp ?? '—', 'hint' => __('30 dias'), 'border' => '#6B5B95', 'val_color' => '#3D2D63'],
			['lbl' => __('Tempo médio resolução'), 'val' => $this->computeAvgResolutionFmt($idempresa, $tickets, $cols) ?? '—', 'hint' => __('empresa'), 'border' => 'var(--teal-mid)'],
			['lbl' => __('SLA violado'), 'val' => (string)$violados, 'hint' => $violPct . '% ' . __('dos tickets'), 'alert' => $violados > 0, 'bg' => '#F8D8DA', 'border' => 'var(--red)', 'val_color' => '#7A1822'],
			['lbl' => __('CSAT médio'), 'val' => $csatMedia !== null ? '⭐ ' . number_format((float)$csatMedia, 1, ',', '.') : '—', 'hint' => sprintf(__('%d respostas'), $csatN), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
		];
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
	 * @param string[] $cols
	 */
	protected function computeAvgFirstResponseFmt(int $idempresa, TicketsTable $tickets, array $cols): ?string {
		if (!in_array('data_primeira_resposta', $cols, true) || !in_array('created', $cols, true)) {
			return null;
		}
		$since = Time::now()->subDays(30)->format('Y-m-d H:i:s');
		$q = $tickets->find()
			->select(['created', 'data_primeira_resposta'])
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $since,
				'Tickets.data_primeira_resposta IS NOT' => null,
			])
			->limit(300);
		($this->applyAbac)($q);
		$sec = 0;
		$n = 0;
		foreach ($q->all() as $t) {
			$c = $t->get('created');
			$p = $t->get('data_primeira_resposta');
			if (!$c instanceof \DateTimeInterface || !$p instanceof \DateTimeInterface) {
				continue;
			}
			$diff = $p->getTimestamp() - $c->getTimestamp();
			if ($diff >= 0) {
				$sec += $diff;
				$n++;
			}
		}
		if ($n === 0) {
			return null;
		}

		return $this->formatDurationMinutes((int)round($sec / $n / 60));
	}

	/**
	 * @param string[] $cols
	 */
	protected function computeAvgResolutionFmt(int $idempresa, TicketsTable $tickets, array $cols): ?string {
		if (!in_array('data_resolucao', $cols, true) || !in_array('created', $cols, true)) {
			return null;
		}
		$since = Time::now()->subDays(30)->format('Y-m-d H:i:s');
		$q = $tickets->find()
			->select(['created', 'data_resolucao'])
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.data_resolucao >=' => $since,
				'Tickets.data_resolucao IS NOT' => null,
			])
			->limit(300);
		($this->applyAbac)($q);
		$sec = 0;
		$n = 0;
		foreach ($q->all() as $t) {
			$c = $t->get('created');
			$r = $t->get('data_resolucao');
			if (!$c instanceof \DateTimeInterface || !$r instanceof \DateTimeInterface) {
				continue;
			}
			$diff = $r->getTimestamp() - $c->getTimestamp();
			if ($diff >= 0) {
				$sec += $diff;
				$n++;
			}
		}
		if ($n === 0) {
			return null;
		}

		return $this->formatDurationHours((int)round($sec / $n));
	}

	protected function formatDurationMinutes(int $minutes): string {
		if ($minutes < 60) {
			return $minutes . 'min';
		}
		$h = intdiv($minutes, 60);
		$m = $minutes % 60;

		return $m > 0 ? sprintf('%dh %dm', $h, $m) : sprintf('%dh', $h);
	}

	protected function formatDurationHours(int $seconds): string {
		$h = intdiv($seconds, 3600);
		$m = intdiv($seconds % 3600, 60);
		if ($h <= 0) {
			return $m . 'min';
		}

		return $m > 0 ? sprintf('%dh %dm', $h, $m) : sprintf('%dh', $h);
	}

	protected function tableExists(string $table): bool {
		try {
			$conn = TableRegistry::getTableLocator()->get('Tickets')->getConnection();

			return in_array($table, $conn->getSchemaCollection()->listTables(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}
}
