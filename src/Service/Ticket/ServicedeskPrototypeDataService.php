<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use App\Model\Table\ClientesTable;
use App\Model\Table\TicketsTable;
use App\Model\Table\UsersTable;
use App\Utility\Ticket\TicketPriorityKpi;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

/**
 * Dados reais (somente leitura) para o protótipo Service Desk — ORM + mesmo escopo ABAC dos tickets.
 */
class ServicedeskPrototypeDataService {

	/** @var callable(Query):void */
	private $applyAbac;

	/** @param callable(Query):void $applyAbac */
	public function __construct(callable $applyAbac) {
		$this->applyAbac = $applyAbac;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function buildExecutivePayload(
		TicketsTable $tickets,
		int $idempresa,
		ClientesTable $clientes,
		UsersTable $users
	): array {
		$dash = new DashboardService($tickets);
		$snapshot = $dash->operationalSnapshot($idempresa);
		$cols = $tickets->getSchema()->columns();

		$backlogAbac = 0;
		$closed = $this->closedSituacoes();
		if ($closed !== [] && in_array('situacao', $cols, true)) {
			$qb = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.situacao NOT IN' => $closed,
			]);
			($this->applyAbac)($qb);
			$backlogAbac = $qb->count();
		}

		$today0 = Time::today()->format('Y-m-d') . ' 00:00:00';
		$today1 = Time::today()->format('Y-m-d') . ' 23:59:59';
		$ticketsHoje = 0;
		$ticketsOntem = 0;
		if (in_array('created', $cols, true)) {
			$q = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $today0,
				'Tickets.created <=' => $today1,
			]);
			($this->applyAbac)($q);
			$ticketsHoje = $q->count();
			$y0 = Time::now()->subDays(1)->format('Y-m-d') . ' 00:00:00';
			$y1 = Time::now()->subDays(1)->format('Y-m-d') . ' 23:59:59';
			$q2 = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $y0,
				'Tickets.created <=' => $y1,
			]);
			($this->applyAbac)($q2);
			$ticketsOntem = $q2->count();
		}

		$since90 = Time::now()->subDays(90);
		$topClientes = $this->topClientes($tickets, $idempresa, $clientes, $cols, $since90);
		$topAssuntos = $this->topAssuntos($tickets, $idempresa, $cols, $since90);
		$volDiario = $this->volumeDiarioNd($tickets, $idempresa, $cols, 14, false);
		$porSituacaoAberto = $this->porSituacaoAbertos($tickets, $idempresa, $cols);
		$equipe = $this->equipeComAbertos($tickets, $idempresa, $users, $cols);
		$quentes = $this->assuntosQuentes24h($tickets, $idempresa, $cols, 1);
		$abertosPreview = $this->ticketsAbertosPreview($tickets, $idempresa, $cols, 8);
		$heatmap = $this->buildHeatmap90d($tickets, $idempresa);
		$backlogEmpresa = 0;
		$closedEmp = $this->closedSituacoes();
		if ($closedEmp !== [] && in_array('situacao', $cols, true)) {
			$backlogEmpresa = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.situacao NOT IN' => $closedEmp,
			])->count();
		}

		$violadosLista = (array)($snapshot['alertas_sla_violado'] ?? []);
		$overdue = (int)($snapshot['sla_por_etapa']['overdue'] ?? 0);
		$slaViolados = max(count($violadosLista), $overdue);

		return [
			'snapshot' => $snapshot,
			'tickets_hoje' => $ticketsHoje,
			'tickets_ontem' => $ticketsOntem,
			'top_clientes' => $topClientes,
			'top_assuntos' => $topAssuntos,
			'vol_diario_14' => $volDiario,
			'por_situacao_aberto' => $porSituacaoAberto,
			'equipe' => $equipe,
			'assuntos_quentes' => $quentes,
			'sla_violados_total' => $slaViolados,
			'sla_violados_lista' => $violadosLista,
			'gerado_em' => Time::now()->format('d/m/Y H:i'),
			'backlog_abac' => $backlogAbac,
			'backlog_empresa' => $backlogEmpresa,
			'heatmap' => $heatmap,
			'tickets_abertos_preview' => $abertosPreview,
		];
	}

	/**
	 * Portal cliente (preview equipe) — tickets reais da empresa.
	 *
	 * @return array<string,mixed>
	 */
	public function buildPortalPreview(TicketsTable $tickets, int $idempresa, string $userName = ''): array {
		$cols = $tickets->getSchema()->columns();
		$closed = $this->closedSituacoes();
		$abertos = [];
		$abertosCount = 0;
		$aguardaVoce = 0;
		$resolvidos30 = 0;
		$resolvidos30Hint = '';
		$tempoMedio = '—';
		$bannerCliente = $this->empresaDisplayName($idempresa);

		if ($closed !== [] && in_array('situacao', $cols, true)) {
			$baseWhere = [
				'Tickets.idempresa' => $idempresa,
				'Tickets.situacao NOT IN' => $closed,
			];
			$qc = $tickets->find()->where($baseWhere);
			($this->applyAbac)($qc);
			$abertosCount = $qc->count();

			$q = $tickets->find()
				->contain(['Clientes', 'users'])
				->where($baseWhere)
				->order(['Tickets.id' => 'DESC'])
				->limit(12);
			($this->applyAbac)($q);
			foreach ($q->all() as $t) {
				$abertos[] = $this->mapPortalTicketCard($tickets, $t, $cols);
			}
			if ($abertos !== []) {
				$bannerCliente = (string)$abertos[0]['cliente'];
			}

			if (defined('C_TicketSituacaoRespondido')) {
				$qa = $tickets->find()->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.situacao' => (int)C_TicketSituacaoRespondido,
				]);
				($this->applyAbac)($qa);
				$aguardaVoce = $qa->count();
			}

			if (in_array('data_resolucao', $cols, true)) {
				$s0 = Time::now()->subDays(30)->format('Y-m-d H:i:s');
				$qr = $tickets->find()
					->where([
						'Tickets.idempresa' => $idempresa,
						'Tickets.data_resolucao >=' => $s0,
						'Tickets.data_resolucao IS NOT' => null,
					]);
				if ($closed !== []) {
					$qr->where(['Tickets.situacao IN' => $closed]);
				}
				($this->applyAbac)($qr);
				$resolvidos30 = $qr->count();
				$totalSec = 0;
				$resolvedCnt = 0;
				$onTime = 0;
				foreach ($qr->all() as $t) {
					$created = $this->rowGet($t, 'created');
					$resolv = $this->rowGet($t, 'data_resolucao');
					if ($created instanceof \DateTimeInterface && $resolv instanceof \DateTimeInterface) {
						$sec = max(0, $resolv->getTimestamp() - $created->getTimestamp());
						$totalSec += $sec;
						$resolvedCnt++;
					}
					$slaStatus = in_array('sla_status', $cols, true) ? trim((string)$this->rowGet($t, 'sla_status', '')) : '';
					$limite = in_array('data_limite_resolucao', $cols, true) ? $this->rowGet($t, 'data_limite_resolucao') : null;
					if ($slaStatus !== 'violado' && !$this->isSlaOverdue($limite)) {
						$onTime++;
					}
				}
				if ($resolvedCnt > 0) {
					$tempoMedio = $this->formatDurationShort((int)round($totalSec / $resolvedCnt));
				}
				if ($resolvidos30 > 0) {
					$pct = (int)round(100 * $onTime / $resolvidos30);
					$resolvidos30Hint = $pct . '% ' . __('no prazo');
				}
			}
		}

		$firstName = __('visitante');
		$userName = trim($userName);
		if ($userName !== '') {
			$parts = preg_split('/\s+/u', $userName) ?: [];
			$firstName = (string)($parts[0] ?? $userName);
		}

		return [
			'cliente_nome' => $bannerCliente,
			'banner_cliente' => $bannerCliente,
			'user_first_name' => $firstName,
			'abertos_count' => $abertosCount,
			'aguarda_cliente' => $aguardaVoce,
			'resolvidos_30d' => $resolvidos30,
			'resolvidos_30d_hint' => $resolvidos30Hint,
			'tempo_medio_resolucao' => $tempoMedio,
			'contrato_label' => __('Premium · suporte 24/7'),
			'tickets_abertos' => $abertos,
			'categorias' => [
				['icon' => '🔑', 'nome' => __('Acesso & senhas'), 'sla' => '4h'],
				['icon' => '🖥', 'nome' => __('Hardware'), 'sla' => '1d'],
				['icon' => '📧', 'nome' => __('E-mail'), 'sla' => '2h'],
				['icon' => '🌐', 'nome' => __('Rede / Internet'), 'sla' => '1h'],
				['icon' => '💿', 'nome' => __('Software / ERP'), 'sla' => '4h'],
				['icon' => '📦', 'nome' => __('Outros'), 'sla' => '1d'],
			],
			'kb_popular' => [
				['titulo' => __('Como redefinir minha senha'), 'meta' => '⭐ 4.9 · 3 min · 124 ' . __('visualizações')],
				['titulo' => __('Configurar e-mail no celular'), 'meta' => '⭐ 4.7 · 5 min · 89 ' . __('visualizações')],
				['titulo' => __('Conectar VPN da empresa'), 'meta' => '⭐ 4.8 · 7 min · 67 ' . __('visualizações')],
			],
		];
	}

	/**
	 * Catálogo KB do protótipo (mock alinhado a pg-sd-kb até módulo dedicado).
	 *
	 * @return array<string,mixed>
	 */
	public function buildKbPreview(TicketsTable $tickets, int $idempresa): array {
		$since = Time::now()->subDays(30);
		$ticketsMes = 0;
		try {
			$q = $tickets->find()
				->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.created >=' => $since,
				]);
			($this->applyAbac)($q);
			$ticketsMes = (int)$q->count();
		} catch (\Throwable $e) {
		}

		$articles = [
			[
				'code' => 'KB-042',
				'titulo' => __('Como criar perfil de acesso no AD'),
				'resumo' => __('Passo a passo para criar novos usuários, vincular grupos e definir permissões no Active Directory.'),
				'visibilidade' => 'publico',
				'tags' => ['acesso', 'AD'],
				'rating' => '4.7',
				'votos' => 28,
				'views' => 124,
				'tickets' => 28,
			],
			[
				'code' => 'KB-018',
				'titulo' => __('Perfis padrão por departamento'),
				'resumo' => __('Matriz de permissões padrão: Comercial, Financeiro, RH, Operações, TI.'),
				'visibilidade' => 'publico',
				'tags' => [],
				'rating' => '4.5',
				'votos' => 45,
				'views' => 234,
				'tickets' => 45,
			],
			[
				'code' => 'KB-027',
				'titulo' => __('Configurar VPN da empresa'),
				'resumo' => __('Instalar e configurar o cliente OpenVPN para acesso remoto seguro.'),
				'visibilidade' => 'publico',
				'tags' => [],
				'rating' => '4.8',
				'votos' => 62,
				'views' => 367,
				'tickets' => 62,
			],
			[
				'code' => 'KB-055',
				'titulo' => __('Procedimento Reset Domain Controller'),
				'resumo' => __('Restaurar AD em caso de falha · só técnicos N3.'),
				'visibilidade' => 'interno',
				'tags' => [],
				'rating' => '',
				'votos' => 0,
				'views' => 12,
				'tickets' => 3,
				'revisar' => __('Revisar (90 dias)'),
				'card_bg' => '#FFFBF0',
			],
			[
				'code' => 'KB-061',
				'titulo' => __('Redefinir senha do ERP'),
				'resumo' => __('Auto-serviço · usuário pode resetar sem abrir chamado.'),
				'visibilidade' => 'publico',
				'tags' => [],
				'rating' => '4.9',
				'votos' => 98,
				'views' => 567,
				'tickets' => 98,
			],
			[
				'code' => 'KB-034',
				'titulo' => __('Configurar e-mail no celular'),
				'resumo' => __('Outlook iOS/Android · IMAP e Exchange.'),
				'visibilidade' => 'publico',
				'tags' => [],
				'rating' => '4.7',
				'votos' => 43,
				'views' => 289,
				'tickets' => 43,
			],
		];

		return [
			'stats' => [
				'total_publicados' => 68,
				'visualizacoes_30d' => '1.247',
				'aplicados_tickets' => 247,
				'avaliacao_media' => '⭐ 4.6',
				'pendentes_revisao' => 3,
				'auto_resolucao_pct' => '22%',
			],
			'tickets_mes' => $ticketsMes,
			'articles' => $articles,
			'filter_categorias' => [
				__('Todas categorias'),
				__('Acesso & Permissões'),
				__('Hardware'),
				__('Software'),
				__('Rede'),
				__('E-mail'),
			],
		];
	}

	/**
	 * Plantões & disponibilidade (pg-sd-calendar) — agenda (visitas), filas/técnicos e tickets em aberto.
	 *
	 * @param array<string,mixed> $query week (Y-m-d segunda) | month (Y-m)
	 * @return array<string,mixed>
	 */
	public function buildPlantaoPayload(TicketsTable $tickets, int $idempresa, array $query = []): array {
		$monday = $this->plantaoResolveWeekStart($query);
		$sunday = $monday->copy()->addDays(6);
		$meta = $this->buildFilaAssignmentMeta($tickets, $idempresa);
		$queues = (array)($meta['queues'] ?? []);
		$tecnicos = (array)($meta['tecnicos'] ?? []);
		$queueLevelById = [];
		foreach ($queues as $q) {
			$qid = (int)($q['id'] ?? 0);
			if ($qid > 0) {
				$queueLevelById[$qid] = $this->plantaoQueueLevel($q);
			}
		}
		$userLevelById = [];
		foreach ($tecnicos as $t) {
			$uid = (int)($t['id'] ?? 0);
			if ($uid <= 0) {
				continue;
			}
			$levels = [];
			foreach ((array)($t['queue_ids'] ?? []) as $qid) {
				$lv = $queueLevelById[(int)$qid] ?? '';
				if ($lv !== '') {
					$levels[$lv] = $lv;
				}
			}
			$userLevelById[$uid] = $levels !== [] ? array_values($levels) : ['n1'];
		}

		$visitasRows = $this->plantaoLoadVisitas($idempresa, $monday, $sunday);
		$absences = $this->plantaoAbsencesFromVisitas($visitasRows, $monday, $sunday->copy()->addDays(21));
		$shifts = $this->plantaoShiftDefinitions();
		$days = [];
		$todayYmd = Time::now()->format('Y-m-d');
		for ($i = 0; $i < 7; $i++) {
			$d = $monday->copy()->addDays($i);
			$ymd = $d->format('Y-m-d');
			$days[] = [
				'ymd' => $ymd,
				'label' => $this->plantaoDayLabel($d),
				'is_today' => $ymd === $todayYmd,
			];
		}

		$grid = [];
		foreach ($shifts as $shift) {
			$row = [
				'id' => (string)$shift['id'],
				'label' => (string)$shift['label'],
				'hours' => (string)$shift['hours'],
				'icon' => (string)($shift['icon'] ?? ''),
				'style' => (string)$shift['style'],
				'cells' => [],
			];
			foreach ($days as $day) {
				$row['cells'][] = $this->plantaoCellForDay(
					$day['ymd'],
					$shift,
					$visitasRows,
					$tecnicos,
					$queueLevelById,
					$userLevelById
				);
			}
			$grid[] = $row;
		}

		$now = $this->plantaoNowStatus($tickets, $idempresa, $tecnicos, $queueLevelById, $userLevelById, $visitasRows, $todayYmd);
		$phones = $this->plantaoPhones($idempresa, $tecnicos, $queueLevelById);
		$monthOptions = [];
		$anchorMonth = Time::now()->startOfMonth();
		for ($m = -2; $m <= 4; $m++) {
			$mo = $anchorMonth->copy()->addMonths($m);
			$monthOptions[] = [
				'value' => $mo->format('Y-m'),
				'label' => $mo->i18nFormat('LLLL yyyy'),
				'selected' => $mo->format('Y-m') === $monday->format('Y-m'),
			];
		}

		return [
			'week_start' => $monday->format('Y-m-d'),
			'week_end' => $sunday->format('Y-m-d'),
			'week_label' => sprintf(
				'%s–%s/%s',
				$monday->format('d'),
				$sunday->format('d'),
				$sunday->format('m/Y')
			),
			'month_options' => $monthOptions,
			'nav' => [
				'prev' => $monday->copy()->subDays(7)->format('Y-m-d'),
				'next' => $monday->copy()->addDays(7)->format('Y-m-d'),
				'today' => Time::now()->startOfWeek()->format('Y-m-d'),
			],
			'days' => $days,
			'shifts' => $grid,
			'now' => $now,
			'absences' => $absences,
			'phones' => $phones,
			'has_visitas' => $visitasRows !== [],
			'tecnicos_count' => count($tecnicos),
		];
	}

	/**
	 * @param array<string,mixed> $query
	 */
	protected function plantaoResolveWeekStart(array $query): Time {
		$month = trim((string)($query['month'] ?? ''));
		if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
			return Time::parse($month . '-15')->startOfWeek();
		}
		$week = trim((string)($query['week'] ?? ''));
		if ($week !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $week)) {
			return Time::parse($week)->startOfWeek();
		}

		return Time::now()->startOfWeek();
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function plantaoShiftDefinitions(): array {
		return [
			[
				'id' => 'n1_manha',
				'label' => __('N1 Manhã'),
				'icon' => '🟢',
				'hours' => '08h-12h',
				'start_min' => 8 * 60,
				'end_min' => 12 * 60,
				'levels' => ['n1'],
				'style' => 'teal',
			],
			[
				'id' => 'n1_tarde',
				'label' => __('N1 Tarde'),
				'icon' => '🟢',
				'hours' => '13h-18h',
				'start_min' => 13 * 60,
				'end_min' => 18 * 60,
				'levels' => ['n1'],
				'style' => 'teal',
			],
			[
				'id' => 'n2_n3',
				'label' => __('N2/N3'),
				'icon' => '🔵',
				'hours' => '08h-18h',
				'start_min' => 8 * 60,
				'end_min' => 18 * 60,
				'levels' => ['n2', 'n3'],
				'style' => 'blue',
			],
			[
				'id' => 'noite',
				'label' => __('Plantão noite'),
				'icon' => '🌙',
				'hours' => '22h-06h',
				'start_min' => 22 * 60,
				'end_min' => 6 * 60,
				'overnight' => true,
				'levels' => ['n2', 'n3'],
				'style' => 'purple',
			],
			[
				'id' => 'comercial',
				'label' => __('Comercial'),
				'icon' => '🟣',
				'hours' => '09h-17h',
				'start_min' => 9 * 60,
				'end_min' => 17 * 60,
				'levels' => ['comercial'],
				'style' => 'pink',
			],
		];
	}

	/**
	 * @param array<string,mixed> $queue
	 */
	protected function plantaoQueueLevel(array $queue): string {
		$blob = strtolower(trim((string)($queue['codigo'] ?? '') . ' ' . (string)($queue['name'] ?? '') . ' ' . (string)($queue['nivel'] ?? '')));
		if (strpos($blob, 'comercial') !== false || strpos($blob, 'vendas') !== false) {
			return 'comercial';
		}
		if (strpos($blob, 'n3') !== false || strpos($blob, 'especial') !== false) {
			return 'n3';
		}
		if (strpos($blob, 'n2') !== false || strpos($blob, 'avan') !== false) {
			return 'n2';
		}

		return 'n1';
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function plantaoLoadVisitas(int $idempresa, Time $from, Time $to): array {
		if (!$this->tableExists('visitas')) {
			return [];
		}
		$rows = [];
		try {
			$q = TableRegistry::getTableLocator()->get('Visitas')->find()
				->contain(['Users'])
				->where([
					'Visitas.idempresa' => $idempresa,
					'Visitas.data >=' => $from->format('Y-m-d'),
					'Visitas.data <=' => $to->format('Y-m-d'),
				])
				->order(['Visitas.data' => 'ASC', 'Visitas.horaini' => 'ASC']);
			foreach ($q->all() as $vis) {
				$data = $vis->get('data');
				$ymd = $data instanceof \DateTimeInterface ? $data->format('Y-m-d') : substr((string)$data, 0, 10);
				$users = [];
				foreach ((array)($vis->users ?? []) as $u) {
					$uid = (int)$u->get('id');
					if ($uid > 0) {
						$users[] = [
							'id' => $uid,
							'name' => $this->rowUserDisplayName($u),
						];
					}
				}
				$textBlob = strtolower(trim(
					(string)($vis->get('agenda_titulo') ?? '') . ' '
					. (string)($vis->get('motivo') ?? '') . ' '
					. (string)($vis->get('observacao') ?? '')
				));
				$rows[] = [
					'ymd' => $ymd,
					'start_min' => $this->plantaoTimeToMinutes($vis->get('horaini')),
					'end_min' => $this->plantaoTimeToMinutes($vis->get('horafim')),
					'users' => $users,
					'text' => $textBlob,
					'title' => trim((string)($vis->get('agenda_titulo') ?? '')),
				];
			}
		} catch (\Throwable $e) {
		}

		return $rows;
	}

	/**
	 * @param mixed $t
	 */
	protected function plantaoTimeToMinutes($t): int {
		if ($t instanceof \DateTimeInterface) {
			return (int)$t->format('H') * 60 + (int)$t->format('i');
		}
		$s = trim((string)$t);
		if (preg_match('/^(\d{1,2}):(\d{2})/', $s, $m)) {
			return (int)$m[1] * 60 + (int)$m[2];
		}

		return 0;
	}

	/**
	 * @param array<string,mixed> $shift
	 * @param array<int,array<string,mixed>> $visitas
	 * @param array<int,array<string,mixed>> $tecnicos
	 * @param array<int,string> $queueLevelById
	 * @param array<int,array<int,string>> $userLevelById
	 * @return array<string,mixed>
	 */
	protected function plantaoCellForDay(
		string $ymd,
		array $shift,
		array $visitas,
		array $tecnicos,
		array $queueLevelById,
		array $userLevelById
	): array {
		$levels = (array)($shift['levels'] ?? []);
		$isWeekend = in_array((int)Time::parse($ymd)->format('N'), [6, 7], true);
		$matchedNames = [];
		$extra = '';

		foreach ($visitas as $vis) {
			if (($vis['ymd'] ?? '') !== $ymd) {
				continue;
			}
			if (!$this->plantaoShiftOverlaps($shift, (int)($vis['start_min'] ?? 0), (int)($vis['end_min'] ?? 0))) {
				continue;
			}
			$text = (string)($vis['text'] ?? '');
			if (strpos($text, 'folga') !== false && strpos($text, 'plant') === false) {
				return ['text' => __('Folga'), 'style' => 'muted', 'hint' => ''];
			}
			if (strpos($text, 'férias') !== false || strpos($text, 'ferias') !== false
				|| strpos($text, 'atestado') !== false || strpos($text, 'treinamento') !== false) {
				continue;
			}
			foreach ((array)($vis['users'] ?? []) as $u) {
				$uid = (int)($u['id'] ?? 0);
				$userLevels = (array)($userLevelById[$uid] ?? ['n1']);
				if (array_intersect($levels, $userLevels) !== []) {
					$matchedNames[$uid] = (string)($u['name'] ?? '');
				}
			}
			if ($matchedNames === [] && !empty($vis['users'])) {
				foreach ((array)$vis['users'] as $u) {
					$matchedNames[(int)($u['id'] ?? 0)] = (string)($u['name'] ?? '');
				}
			}
			$title = trim((string)($vis['title'] ?? ''));
			if ($title !== '' && (strpos(strtolower($title), 'chg-') !== false || strpos(strtolower($title), 'on-call') !== false)) {
				$extra = $title;
			}
		}

		if ($matchedNames !== []) {
			$text = implode(' + ', array_values($matchedNames));
			if ($extra !== '') {
				$text .= ' · ' . $extra;
			}

			return ['text' => $text, 'style' => (string)$shift['style'], 'hint' => ''];
		}

		if ($isWeekend && in_array('comercial', $levels, true)) {
			return ['text' => '—', 'style' => 'muted', 'hint' => ''];
		}

		$pool = [];
		foreach ($tecnicos as $t) {
			$uid = (int)($t['id'] ?? 0);
			$userLevels = (array)($userLevelById[$uid] ?? []);
			if (array_intersect($levels, $userLevels) !== []) {
				$pool[] = $t;
			}
		}
		if ($pool === []) {
			$pool = $tecnicos;
		}
		if ($pool === []) {
			return ['text' => '—', 'style' => 'muted', 'hint' => ''];
		}
		$idx = (int)crc32($ymd . (string)$shift['id']) % count($pool);
		$pick = $pool[$idx];
		$hint = '';
		if (($shift['id'] ?? '') === 'noite' && $isWeekend) {
			$hint = ' (on-call)';
		}

		return [
			'text' => (string)($pick['name'] ?? '—') . $hint,
			'style' => (string)$shift['style'],
			'hint' => $hint,
		];
	}

	/**
	 * @param array<string,mixed> $shift
	 */
	protected function plantaoShiftOverlaps(array $shift, int $startMin, int $endMin): bool {
		$s0 = (int)($shift['start_min'] ?? 0);
		$s1 = (int)($shift['end_min'] ?? 0);
		if (!empty($shift['overnight'])) {
			return $startMin >= $s0 || $endMin <= $s1 || $startMin < $s1;
		}
		if ($endMin <= $startMin) {
			$endMin += 24 * 60;
		}

		return $startMin < $s1 && $endMin > $s0;
	}

	protected function plantaoDayLabel(Time $d): string {
		$names = [
			1 => __('Seg'),
			2 => __('Ter'),
			3 => __('Qua'),
			4 => __('Qui'),
			5 => __('Sex'),
			6 => __('Sáb'),
			7 => __('Dom'),
		];
		$n = (int)$d->format('N');

		return ($names[$n] ?? $d->format('D')) . ' ' . $d->format('d');
	}

	/**
	 * @param array<int,array<string,mixed>> $visitas
	 * @return array<int,array<string,mixed>>
	 */
	protected function plantaoAbsencesFromVisitas(array $visitas, Time $from, Time $to): array {
		$out = [];
		$fromY = $from->format('Y-m-d');
		$toY = $to->format('Y-m-d');
		foreach ($visitas as $vis) {
			$ymd = (string)($vis['ymd'] ?? '');
			if ($ymd < $fromY || $ymd > $toY) {
				continue;
			}
			$text = (string)($vis['text'] ?? '');
			$tipo = '';
			$style = 'amber';
			if (strpos($text, 'férias') !== false || strpos($text, 'ferias') !== false) {
				$tipo = __('Férias');
				$style = 'amber';
			} elseif (strpos($text, 'atestado') !== false) {
				$tipo = __('Atestado médico');
				$style = 'purple';
			} elseif (strpos($text, 'treinamento') !== false || strpos($text, 'certific') !== false) {
				$tipo = __('Treinamento certificação');
				$style = 'blue';
			} else {
				continue;
			}
			$name = '—';
			if (!empty($vis['users'])) {
				$name = (string)($vis['users'][0]['name'] ?? '—');
			}
			$out[] = [
				'name' => $name,
				'type' => $tipo,
				'period' => Time::parse($ymd)->format('d/m/Y') . ' · 1 ' . __('dia'),
				'coverage' => $this->plantaoCoverageNote($tipo),
				'style' => $style,
			];
		}

		return array_slice($out, 0, 8);
	}

	protected function plantaoCoverageNote(string $tipo): string {
		if (strpos($tipo, 'Férias') !== false) {
			return __('Cobertura: escala N1 assume turnos do colaborador');
		}
		if (strpos($tipo, 'Treinamento') !== false) {
			return __('Cobertura: plantonistas N2/N3 em rodízio');
		}

		return __('Cobertura: colega da mesma fila');
	}

	/**
	 * @param array<int,array<string,mixed>> $tecnicos
	 * @param array<int,string> $queueLevelById
	 * @param array<int,array<int,string>> $userLevelById
	 * @param array<int,array<string,mixed>> $visitas
	 * @return array<string,mixed>
	 */
	protected function plantaoNowStatus(
		TicketsTable $tickets,
		int $idempresa,
		array $tecnicos,
		array $queueLevelById,
		array $userLevelById,
		array $visitas,
		string $todayYmd
	): array {
		$cols = $tickets->getSchema()->columns();
		$closed = $this->closedSituacoes();
		$ownerIds = [];
		$respCol = $this->ticketResponsavelColumn($cols);
		if ($closed !== [] && in_array('situacao', $cols, true) && $respCol !== null) {
			try {
				$q = $tickets->find()
					->select(['Tickets.' . $respCol])
					->where([
						'Tickets.idempresa' => $idempresa,
						'Tickets.situacao NOT IN' => $closed,
						'Tickets.' . $respCol . ' >' => 0,
					]);
				($this->applyAbac)($q);
				foreach ($q->all() as $row) {
					$oid = (int)$row->get($respCol);
					if ($oid > 0) {
						$ownerIds[$oid] = $oid;
					}
				}
			} catch (\Throwable $e) {
			}
		}

		$todayUserIds = [];
		foreach ($visitas as $vis) {
			if (($vis['ymd'] ?? '') !== $todayYmd) {
				continue;
			}
			foreach ((array)($vis['users'] ?? []) as $u) {
				$uid = (int)($u['id'] ?? 0);
				if ($uid > 0) {
					$todayUserIds[$uid] = $uid;
				}
			}
		}
		$onlineIds = $ownerIds + $todayUserIds;
		$onlineCount = count($onlineIds);

		$byLevel = ['n1' => [], 'n2' => [], 'n3' => [], 'comercial' => []];
		foreach ($onlineIds as $uid) {
			$levels = (array)($userLevelById[$uid] ?? ['n1']);
			foreach ($levels as $lv) {
				if (!isset($byLevel[$lv])) {
					$byLevel[$lv] = [];
				}
				$name = $this->plantaoUserName($uid, $tecnicos);
				if ($name !== '') {
					$byLevel[$lv][$uid] = $name;
				}
			}
		}

		$n1 = array_values($byLevel['n1']);
		$n2n3 = array_values(array_unique(array_merge($byLevel['n2'], $byLevel['n3'])));
		$noite = [];
		foreach ($visitas as $vis) {
			if (($vis['ymd'] ?? '') !== $todayYmd) {
				continue;
			}
			if (!$this->plantaoShiftOverlaps(
				['start_min' => 22 * 60, 'end_min' => 6 * 60, 'overnight' => true],
				(int)($vis['start_min'] ?? 0),
				(int)($vis['end_min'] ?? 0)
			)) {
				continue;
			}
			foreach ((array)($vis['users'] ?? []) as $u) {
				$noite[] = (string)($u['name'] ?? '');
			}
		}
		if ($noite === [] && $n2n3 !== []) {
			$noite = [reset($n2n3) . ' · ' . __('até 06h')];
		}

		return [
			'timestamp' => Time::now()->format('d/m/Y H:i'),
			'online_count' => $onlineCount,
			'n1_label' => $n1 !== [] ? implode(' + ', $n1) : '—',
			'n2_label' => $n2n3 !== [] ? implode(' + ', $n2n3) : '—',
			'noite_label' => $noite !== [] ? implode(' + ', array_unique($noite)) : '—',
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $tecnicos
	 */
	protected function plantaoUserName(int $uid, array $tecnicos): string {
		foreach ($tecnicos as $t) {
			if ((int)($t['id'] ?? 0) === $uid) {
				return (string)($t['name'] ?? '');
			}
		}
		try {
			$u = TableRegistry::getTableLocator()->get('Users')->get($uid);

			return $this->rowUserDisplayName($u);
		} catch (\Throwable $e) {
			return '';
		}
	}

	/**
	 * @param array<int,array<string,mixed>> $tecnicos
	 * @param array<int,string> $queueLevelById
	 * @return array<int,array<string,mixed>>
	 */
	protected function plantaoPhones(int $idempresa, array $tecnicos, array $queueLevelById): array {
		$fone = '';
		$fone2 = '';
		try {
			$emp = TableRegistry::getTableLocator()->get('Empresas')->get($idempresa);
			$fone = $this->plantaoFormatPhone((string)($emp->get('fone') ?? ''));
			$fone2 = $this->plantaoFormatPhone((string)($emp->get('fone2') ?? ''));
		} catch (\Throwable $e) {
		}
		$byLevel = ['n1' => [], 'n2' => [], 'n3' => [], 'comercial' => []];
		foreach ($tecnicos as $t) {
			$uid = (int)($t['id'] ?? 0);
			$name = (string)($t['name'] ?? '');
			foreach ((array)($t['queue_ids'] ?? []) as $qid) {
				$lv = $queueLevelById[(int)$qid] ?? 'n1';
				$byLevel[$lv][$name] = $name;
			}
		}
		$n1n2 = array_unique(array_merge(array_values($byLevel['n1']), array_values($byLevel['n2'])));
		$n3 = array_values($byLevel['n3']);
		$com = array_values($byLevel['comercial']);

		return [
			[
				'title' => __('Plantão N1/N2 (geral)'),
				'phone' => $fone !== '' ? $fone : '—',
				'meta' => __('24/7') . ' · ' . ($n1n2 !== [] ? implode('/', array_slice($n1n2, 0, 4)) : __('equipe técnica')),
			],
			[
				'title' => __('Plantão N3 / emergências'),
				'phone' => $fone2 !== '' ? $fone2 : ($fone !== '' ? $fone : '—'),
				'meta' => __('só clientes Premium') . ' · ' . ($n3 !== [] ? implode('/', $n3) : __('especialistas')),
			],
			[
				'title' => __('Comercial'),
				'phone' => $fone2 !== '' ? $fone2 : ($fone !== '' ? $fone : '—'),
				'meta' => __('Seg-Sex 9h-17h') . ' · ' . ($com !== [] ? implode(', ', $com) : __('comercial')),
			],
		];
	}

	protected function plantaoFormatPhone(string $raw): string {
		$d = preg_replace('/\D+/', '', $raw);
		if (strlen($d) === 13 && strpos($d, '55') === 0) {
			$d = substr($d, 2);
		}
		if (strlen($d) === 11) {
			return '+55 ' . substr($d, 0, 2) . ' ' . substr($d, 2, 5) . '-' . substr($d, 7);
		}
		if (strlen($d) === 10) {
			return '+55 ' . substr($d, 0, 2) . ' ' . substr($d, 2, 4) . '-' . substr($d, 6);
		}

		return trim($raw);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $t
	 * @param string[] $cols
	 * @return array<string,mixed>
	 */
	protected function mapPortalTicketCard(TicketsTable $tickets, $t, array $cols): array {
		$row = $this->mapFilaRow($tickets, $t, $cols);
		$sit = (int)$row['situacao'];
		$resp = defined('C_TicketSituacaoRespondido') ? (int)C_TicketSituacaoRespondido : 4;
		$aguardaCliente = $resp >= 0 && $sit === $resp;

		if ($aguardaCliente) {
			$row['portal_badge'] = '⏰ ' . __('AGUARDANDO VOCÊ');
			$row['portal_badge_style'] = 'background:#FAEEDA;color:#8A4D02;';
			$row['portal_card_style'] = 'background:#FFFBF0;border-left:3px solid var(--amber);';
			$row['portal_action'] = __('Ação necessária no portal do cliente');
		} else {
			$row['portal_badge'] = __('EM ATENDIMENTO');
			$row['portal_badge_style'] = 'background:var(--blue-light);color:#0C447C;';
			$row['portal_card_style'] = 'background:var(--bg-surface);border-left:3px solid var(--blue);';
			$row['portal_action'] = '';
		}

		$tec = (string)$row['tecnico'];
		if ($row['sem_tecnico'] ?? false) {
			$tecLine = __('sem técnico atribuído');
		} else {
			$tecLine = __('Atendente') . ': ' . $tec;
		}
		$row['portal_meta'] = __('Aberto') . ' ' . (string)$row['created_fmt'] . ' · ' . $tecLine;

		return $row;
	}

	protected function empresaDisplayName(int $idempresa): string {
		if ($idempresa <= 0) {
			return '';
		}
		try {
			$e = TableRegistry::getTableLocator()->get('Empresas')->find()
				->select(['id', 'nomefantasia', 'razaosocial'])
				->where(['Empresas.id' => $idempresa])
				->enableHydration(false)
				->first();
			if (is_array($e)) {
				$n = trim((string)($e['nomefantasia'] ?? $e['razaosocial'] ?? ''));

				return $n;
			}
		} catch (\Throwable $e) {
		}

		return '';
	}

	protected function formatDurationShort(int $seconds): string {
		$seconds = max(0, $seconds);
		$h = (int)floor($seconds / 3600);
		$m = (int)floor(($seconds % 3600) / 60);
		if ($h > 0 && $m > 0) {
			return sprintf('%dh %dm', $h, $m);
		}
		if ($h > 0) {
			return sprintf('%dh', $h);
		}
		if ($m > 0) {
			return sprintf('%dm', $m);
		}

		return '0m';
	}

	/**
	 * Heatmap dia da semana × hora (8h–18h), últimos 90 dias.
	 *
	 * @return array<string,mixed>
	 */
	public function buildHeatmap90d(TicketsTable $tickets, int $idempresa): array {
		$cols = $tickets->getSchema()->columns();
		$hours = range(8, 18);
		$dayLabels = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'];
		$dowMap = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex'];
		$grid = [];
		foreach ($dayLabels as $dl) {
			$grid[$dl] = [];
		}
		if (!in_array('created', $cols, true)) {
			return ['rows' => $grid, 'hours' => $hours, 'max' => 1, 'day_labels' => $dayLabels];
		}

		$since = Time::now()->subDays(90)->format('Y-m-d H:i:s');
		$q = $tickets->find()
			->select(['id', 'created'])
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $since,
			]);
		($this->applyAbac)($q);
		$max = 1;
		foreach ($q->all() as $t) {
			$c = $this->rowGet($t, 'created');
			if (!$c instanceof \DateTimeInterface) {
				continue;
			}
			$dow = (int)$c->format('N');
			if (!isset($dowMap[$dow])) {
				continue;
			}
			$h = (int)$c->format('G');
			if ($h < 8 || $h > 18) {
				continue;
			}
			$dl = $dowMap[$dow];
			$grid[$dl][$h] = (int)($grid[$dl][$h] ?? 0) + 1;
			$max = max($max, $grid[$dl][$h]);
		}

		return ['rows' => $grid, 'hours' => $hours, 'max' => $max, 'day_labels' => $dayLabels];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function filaTicketsPage(
		TicketsTable $tickets,
		int $idempresa,
		int $page,
		int $limit
	): array {
		$page = max(1, $page);
		$limit = max(1, min(100, $limit));
		$offset = ($page - 1) * $limit;

		$contain = ['Clientes', 'users'];
		if ($tickets->associations()->has('Queues')) {
			if ($this->tableExists('support_levels')) {
				$contain['Queues'] = ['SupportLevels'];
			} else {
				$contain[] = 'Queues';
			}
		}
		if ($tickets->associations()->has('SupportLevels')) {
			$contain[] = 'SupportLevels';
		}
		$base = function () use ($tickets, $idempresa, $contain): Query {
			$q = $tickets->find()
				->contain($contain)
				->where(['Tickets.idempresa' => $idempresa])
				->order(['Tickets.id' => 'DESC']);
			($this->applyAbac)($q);

			return $q;
		};
		$total = $base()->count();
		$rows = $base()->limit($limit)->offset($offset)->all();

		$cols = $tickets->getSchema()->columns();
		$out = [];
		foreach ($rows as $t) {
			$out[] = $this->mapFilaRow($tickets, $t, $cols);
		}

		return [
			'rows' => $out,
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'pages' => $total > 0 ? (int)ceil($total / $limit) : 1,
		];
	}

	/**
	 * Payload completo da fila técnica (mockup pg-sd-fila).
	 *
	 * @return array<string,mixed>
	 */
	public function buildFilaPagePayload(
		TicketsTable $tickets,
		int $idempresa,
		int $page,
		int $limit = 30
	): array {
		$dash = new DashboardService($tickets);
		$snap = $dash->operationalSnapshot($idempresa);
		$fila = $this->filaTicketsPage($tickets, $idempresa, $page, $limit);
		$totalEmpresa = $tickets->find()->where(['Tickets.idempresa' => $idempresa])->count();

		return [
			'snap' => $snap,
			'sla' => (array)($snap['sla_por_etapa'] ?? []),
			'kpis' => (array)($snap['sla_operational_kpis'] ?? []),
			'violados' => (array)($snap['alertas_sla_violado'] ?? []),
			'avg_by_state' => (array)($snap['sla_por_etapa']['avg_seconds_by_state'] ?? []),
			'fila' => $fila,
			'total_empresa' => $totalEmpresa,
			'gerado_em' => Time::now()->format('H:i:s'),
			'assignment' => $this->buildFilaAssignmentMeta($tickets, $idempresa),
		];
	}

	/**
	 * Filas e técnicos da empresa para atribuição na grade da fila técnica.
	 *
	 * @return array<string,mixed>
	 */
	public function buildFilaAssignmentMeta(TicketsTable $tickets, int $idempresa): array {
		$cols = $tickets->getSchema()->columns();
		$canAssign = in_array('idtecnico_responsavel', $cols, true) || in_array('owner_id', $cols, true);
		$queuesRelacional = in_array('queue_id', $cols, true);
		$queues = [];
		if ($queuesRelacional) {
			try {
				$queuesTable = TableRegistry::getTableLocator()->get('Queues');
				$qf = $queuesTable->find()
					->where(['Queues.idempresa' => $idempresa])
					->order(['Queues.sort_order' => 'ASC', 'Queues.name' => 'ASC', 'Queues.id' => 'ASC']);
				if ($this->tableExists('support_levels') && $queuesTable->associations()->has('SupportLevels')) {
					$qf->contain(['SupportLevels']);
				}
				foreach ($qf->all() as $qr) {
					$nivel = '';
					$sl = $qr->support_level ?? null;
					if ($sl !== null) {
						$nivel = trim((string)$this->rowGet($sl, 'name', ''));
					}
					$queues[] = [
						'id' => (int)$qr->get('id'),
						'name' => trim((string)$qr->get('name', '')),
						'nivel' => $nivel,
					];
				}
			} catch (\Throwable $e) {
				$queuesRelacional = false;
			}
		}
		$queueIdsByUser = [];
		if ($queuesRelacional && $this->tableExists('queues_users')) {
			try {
				$quRows = TableRegistry::getTableLocator()->get('QueuesUsers')->find()
					->select(['QueuesUsers.user_id', 'QueuesUsers.queue_id'])
					->contain(['Queues'])
					->where(['Queues.idempresa' => $idempresa])
					->enableHydration(false)
					->all();
				foreach ($quRows as $qu) {
					$uid = (int)($qu['user_id'] ?? 0);
					$qid = (int)($qu['queue_id'] ?? 0);
					if ($uid <= 0 || $qid <= 0) {
						continue;
					}
					if (!isset($queueIdsByUser[$uid])) {
						$queueIdsByUser[$uid] = [];
					}
					$queueIdsByUser[$uid][$qid] = $qid;
				}
			} catch (\Throwable $e) {
				$queueIdsByUser = [];
			}
		}
		$tecnicos = [];
		try {
			$qry = TableRegistry::getTableLocator()->get('Empresasusers')->find()
				->contain(['Users'])
				->where([
					'Empresasusers.idempresa' => $idempresa,
					'Users.role' => 0,
					'Users.inativo' => 0,
				])
				->order(['Users.name' => 'ASC']);
			$seen = [];
			foreach ($qry->all() as $r) {
				$u = $r->user ?? $r->users ?? null;
				if ($u === null) {
					continue;
				}
				$uid = (int)$u->get('id');
				if ($uid <= 0 || isset($seen[$uid])) {
					continue;
				}
				$seen[$uid] = true;
				$qids = array_values($queueIdsByUser[$uid] ?? []);
				sort($qids);
				$tecnicos[] = [
					'id' => $uid,
					'name' => $this->rowUserDisplayName($u),
					'queue_ids' => $qids,
				];
			}
		} catch (\Throwable $e) {
			$tecnicos = [];
		}

		return [
			'can_assign' => $canAssign,
			'queues_relacional' => $queuesRelacional,
			'queues' => $queues,
			'tecnicos' => $tecnicos,
		];
	}

	/**
	 * Kanban operacional: colunas Aberto / Em execução / Aguarda cliente (mesma lógica da fila) + filtro por fila.
	 *
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>
	 */
	public function buildKanbanPayload(TicketsTable $tickets, int $idempresa, array $query = []): array {
		$cols = $tickets->getSchema()->columns();
		$closed = $this->closedSituacoes();
		if ($closed === [] || !in_array('situacao', $cols, true)) {
			return [
				'mode' => 'empty',
				'columns' => [],
				'queues' => [],
				'queue_id' => 0,
				'hint' => __('Não foi possível determinar tickets em aberto (constantes de situação).'),
				'truncated' => false,
			];
		}

		$queueId = (int)($query['queue_id'] ?? $query['fila'] ?? 0);
		$queues = $this->kanbanQueuesForFilter($idempresa, $cols);

		$orderCol = 'Tickets.id';
		if (in_array('modified', $cols, true)) {
			$orderCol = 'Tickets.modified';
		} elseif (in_array('updated', $cols, true)) {
			$orderCol = 'Tickets.updated';
		} elseif (in_array('created', $cols, true)) {
			$orderCol = 'Tickets.created';
		}
		$contain = ['Clientes'];
		if ($tickets->associations()->has('Queues')) {
			if ($this->tableExists('support_levels')) {
				$contain['Queues'] = ['SupportLevels'];
			} else {
				$contain[] = 'Queues';
			}
		}
		$q = $tickets->find()
			->contain($contain)
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.situacao NOT IN' => $closed,
			])
			->order([$orderCol => 'DESC'])
			->limit(500);
		if ($queueId > 0 && in_array('queue_id', $cols, true)) {
			$q->where(['Tickets.queue_id' => $queueId]);
		}
		($this->applyAbac)($q);
		$entities = $q->all()->toArray();
		$truncated = count($entities) >= 500;

		$columns = $this->kanbanColumnsOperational($tickets, $entities, $cols);

		return [
			'mode' => 'operacional',
			'columns' => $columns,
			'queues' => $queues,
			'queue_id' => $queueId,
			'truncated' => $truncated,
			'hint' => $truncated
				? __('Mostrando os 500 tickets mais recentes no seu escopo; use a fila para paginação completa.')
				: '',
		];
	}

	/**
	 * Filas da empresa para o filtro do Kanban.
	 *
	 * @param string[] $cols
	 * @return array<int,array{id:int,name:string}>
	 */
	protected function kanbanQueuesForFilter(int $idempresa, array $cols): array {
		if (!in_array('queue_id', $cols, true)) {
			return [];
		}
		try {
			$queuesTable = TableRegistry::getTableLocator()->get('Queues');
		} catch (\Throwable $e) {
			return [];
		}
		$out = [];
		foreach ($queuesTable->find()
			->where(['Queues.idempresa' => $idempresa])
			->order(['Queues.sort_order' => 'ASC', 'Queues.name' => 'ASC', 'Queues.id' => 'ASC'])
			->all() as $qr) {
			$name = trim((string)$qr->get('name', ''));
			if ($name === '') {
				continue;
			}
			$out[] = ['id' => (int)$qr->get('id'), 'name' => $name];
		}

		return $out;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface[] $entities
	 * @param \Cake\Datasource\EntityInterface[] $nonFinalStates
	 * @param string[] $cols
	 * @return array<int,array<string,mixed>>
	 */
	protected function kanbanColumnsFromWorkflow(
		TicketsTable $tickets,
		array $entities,
		array $nonFinalStates,
		array $cols
	): array {
		$byId = [];
		foreach ($nonFinalStates as $s) {
			$sid = (int)$s->get('id');
			$byId[$sid] = [
				'key' => 'ws_' . $sid,
				'title' => (string)$s->get('nome'),
				'sub' => (string)$s->get('codigo'),
				'cards' => [],
				'total' => 0,
			];
		}
		$nullCol = [
			'key' => 'ws_null',
			'title' => __('Sem estado workflow'),
			'sub' => __('workflow_state_id vazio'),
			'cards' => [],
			'total' => 0,
		];
		$otherCol = [
			'key' => 'ws_other',
			'title' => __('Estado fora do quadro'),
			'sub' => __('estado final ou removido'),
			'cards' => [],
			'total' => 0,
		];
		$allowedIds = array_fill_keys(array_keys($byId), true);

		foreach ($entities as $t) {
			$card = $this->serializeKanbanCard($tickets, $t, $cols);
			$wid = $t->get('workflow_state_id');
			if ($wid === null || $wid === '') {
				$this->pushKanbanCard($nullCol, $card, 45);
				continue;
			}
			$wid = (int)$wid;
			if (isset($allowedIds[$wid])) {
				$this->pushKanbanCard($byId[$wid], $card, 45);
			} else {
				$this->pushKanbanCard($otherCol, $card, 45);
			}
		}

		$columns = [];
		foreach ($nonFinalStates as $s) {
			$columns[] = $byId[(int)$s->get('id')];
		}
		if ($nullCol['total'] > 0) {
			$columns[] = $nullCol;
		}
		if ($otherCol['total'] > 0) {
			$columns[] = $otherCol;
		}

		return $columns;
	}

	/**
	 * Colunas fixas do mockup (situação operacional + técnico), não workflow_state_id.
	 *
	 * @param \Cake\Datasource\EntityInterface[] $entities
	 * @param string[] $cols
	 * @return array<int,array<string,mixed>>
	 */
	protected function kanbanColumnsOperational(TicketsTable $tickets, array $entities, array $cols): array {
		$defs = [
			'aberto' => [
				'key' => 'aberto',
				'title' => __('Aberto'),
				'sub' => __('Aguardando atribuição'),
				'style' => ['bg' => '#F0FDF4', 'border' => '#7DD3C0'],
				'cards' => [],
				'total' => 0,
			],
			'execucao' => [
				'key' => 'execucao',
				'title' => __('Em execução'),
				'sub' => __('Com técnico ou em andamento'),
				'style' => ['bg' => '#ECFEFF', 'border' => '#06B6D4'],
				'cards' => [],
				'total' => 0,
			],
			'pendente_cliente' => [
				'key' => 'pendente_cliente',
				'title' => __('Pendente'),
				'sub' => __('Aguardando cliente'),
				'style' => ['bg' => '#FFFBEB', 'border' => '#F59E0B'],
				'cards' => [],
				'total' => 0,
			],
			'outros' => [
				'key' => 'outros',
				'title' => __('Outros'),
				'sub' => __('Demais situações em aberto'),
				'style' => ['bg' => '#F3F4F6', 'border' => '#9CA3AF'],
				'cards' => [],
				'total' => 0,
			],
		];

		foreach ($entities as $t) {
			$bucket = $this->kanbanOperationalBucket($t, $cols);
			if (!isset($defs[$bucket])) {
				$bucket = 'outros';
			}
			$this->pushKanbanCard($defs[$bucket], $this->serializeKanbanCard($tickets, $t, $cols), 45);
		}

		$columns = [$defs['aberto'], $defs['execucao'], $defs['pendente_cliente']];
		if ($defs['outros']['total'] > 0) {
			$columns[] = $defs['outros'];
		}

		return $columns;
	}

	/**
	 * Bucket do card: alinhado à fila (resolveSituacaoDisplay + técnico responsável).
	 *
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $t
	 * @param string[] $cols
	 */
	protected function kanbanOperationalBucket($t, array $cols): string {
		$sitDisp = $this->resolveSituacaoDisplay($t, $cols);
		$sit = (int)$sitDisp['situacao'];
		$tecId = $this->ticketResponsavelId($t, $cols);

		$pend = defined('C_TicketSituacaoPendente') ? (int)C_TicketSituacaoPendente : 0;
		$exec = defined('C_TicketSituacaoEmandamento') ? (int)C_TicketSituacaoEmandamento : 1;
		$resp = defined('C_TicketSituacaoRespondido') ? (int)C_TicketSituacaoRespondido : 4;

		if ($resp >= 0 && $sit === $resp) {
			return 'pendente_cliente';
		}
		if ($exec >= 0 && $sit === $exec) {
			return 'execucao';
		}
		if ($pend >= 0 && $sit === $pend) {
			return $tecId > 0 ? 'execucao' : 'aberto';
		}

		return 'outros';
	}

	/**
	 * @param array<string,mixed> $column
	 * @param array<string,mixed> $card
	 */
	protected function pushKanbanCard(array &$column, array $card, int $maxCards): void {
		$column['total'] = (int)($column['total'] ?? 0) + 1;
		if (count($column['cards']) < $maxCards) {
			$column['cards'][] = $card;
		}
	}

	/**
	 * @param string[] $cols
	 * @return array<string,mixed>
	 */
	protected function serializeKanbanCard(TicketsTable $tickets, $t, array $cols): array {
		$assuntoRaw = $this->rowGet($t, 'assunto');
		$assuntoTxt = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
			? $tickets->resolveTicketAssuntoTextoPublic($assuntoRaw)
			: (string)$assuntoRaw;
		$c = $this->ticketRelatedCliente($t);
		$clienteNome = '—';
		if ($c !== null) {
			$clienteNome = (int)$this->rowGet($c, 'tipo', 0) === 2
				? trim((string)$this->rowGet($c, 'razaosocial', ''))
				: trim((string)$this->rowGet($c, 'nome', ''));
			if ($clienteNome === '') {
				$clienteNome = '—';
			}
		}
		$lim = null;
		if (in_array('data_limite_resolucao', $cols, true)) {
			$dl = $this->rowGet($t, 'data_limite_resolucao');
			if ($dl instanceof \DateTimeInterface) {
				$lim = $dl->format('d/m H:i');
			}
		}

		$sitDisp = $this->resolveSituacaoDisplay($t, $cols);
		$filaLabel = '—';
		$qEnt = $this->ticketRelatedQueue($t);
		if ($qEnt !== null) {
			$qName = trim((string)$this->rowGet($qEnt, 'name', ''));
			if ($qName !== '') {
				$filaLabel = $qName;
			}
		}
		$sec = $this->filaTempoSegundos($tickets, $t, $cols);
		$tempo = $sec > 0 ? $this->formatSecondsHms($sec) : '—';

		return [
			'id' => (int)$this->rowGet($t, 'id', 0),
			'assunto' => $assuntoTxt,
			'cliente' => $clienteNome,
			'fila_label' => $filaLabel,
			'prioridade' => $this->rowGet($t, 'prioridade'),
			'situacao' => (int)$sitDisp['situacao'],
			'situacao_label' => (string)$sitDisp['situacao_label'],
			'sla_status' => in_array('sla_status', $cols, true) ? $this->rowGet($t, 'sla_status') : null,
			'data_limite' => $lim,
			'tempo' => $tempo,
		];
	}

	public function situacaoLabel(int $sit): string {
		$map = [
			(int)(defined('C_TicketSituacaoPendente') ? constant('C_TicketSituacaoPendente') : 0) => 'Aberto',
			(int)(defined('C_TicketSituacaoEmandamento') ? constant('C_TicketSituacaoEmandamento') : 1) => 'Em execução',
			(int)(defined('C_TicketSituacaoResolvido') ? constant('C_TicketSituacaoResolvido') : 2) => 'Resolvido',
			(int)(defined('C_TicketSituacaoFechado') ? constant('C_TicketSituacaoFechado') : 3) => 'Fechado',
			(int)(defined('C_TicketSituacaoRespondido') ? constant('C_TicketSituacaoRespondido') : 4) => 'Aguarda cliente',
			(int)(defined('C_TicketSituacaoCancelado') ? constant('C_TicketSituacaoCancelado') : 5) => 'Cancelado',
		];

		return $map[$sit] ?? ('Situação #' . $sit);
	}

	/**
	 * @param string[] $cols
	 * @return array<int,array{id:int,name:string,count:int}>
	 */
	protected function topClientes(
		TicketsTable $tickets,
		int $idempresa,
		ClientesTable $clientes,
		array $cols,
		Time $since
	): array {
		if (!in_array('idcliente', $cols, true) || !in_array('created', $cols, true)) {
			return [];
		}
		$q = $tickets->find();
		($this->applyAbac)($q);
		$f = $q->func()->count('*');
		$rows = $q->select(['idcliente', 'total' => $f])
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $since->format('Y-m-d H:i:s'),
				'Tickets.idcliente IS NOT' => null,
			])
			->group(['idcliente'])
			->order(['total' => 'DESC'])
			->limit(5)
			->enableHydration(false)
			->toArray();
		if ($rows === []) {
			return [];
		}
		$ids = [];
		foreach ($rows as $r) {
			$cid = (int)($r['idcliente'] ?? 0);
			if ($cid > 0) {
				$ids[] = $cid;
			}
		}
		$ids = array_values(array_unique($ids));
		$names = [];
		if ($ids !== []) {
			foreach ($clientes->find()->select(['id', 'nome', 'razaosocial', 'tipo'])->where(['id IN' => $ids])->all() as $c) {
				$nm = (int)($c->get('tipo') ?? 0) === 2
					? trim((string)$c->get('razaosocial'))
					: trim((string)$c->get('nome'));
				$names[(int)$c->get('id')] = $nm !== '' ? $nm : ('Cliente #' . (int)$c->get('id'));
			}
		}
		$out = [];
		foreach ($rows as $r) {
			$cid = (int)($r['idcliente'] ?? 0);
			if ($cid <= 0) {
				continue;
			}
			$out[] = [
				'id' => $cid,
				'name' => $names[$cid] ?? ('Cliente #' . $cid),
				'count' => (int)($r['total'] ?? 0),
			];
		}

		return $out;
	}

	/**
	 * @param string[] $cols
	 * @return array<int,array{label:string,count:int}>
	 */
	protected function topAssuntos(TicketsTable $tickets, int $idempresa, array $cols, Time $since): array {
		if (!in_array('assunto', $cols, true) || !in_array('created', $cols, true)) {
			return [];
		}
		$q = $tickets->find();
		($this->applyAbac)($q);
		$f = $q->func()->count('*');
		$rows = $q->select(['assunto', 'total' => $f])
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $since->format('Y-m-d H:i:s'),
			])
			->group(['assunto'])
			->order(['total' => 'DESC'])
			->limit(8)
			->enableHydration(false)
			->toArray();
		$out = [];
		foreach ($rows as $r) {
			$raw = $r['assunto'] ?? '';
			$label = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
				? $tickets->resolveTicketAssuntoTextoPublic($raw)
				: (string)$raw;
			$label = trim($label) !== '' ? $label : '(sem assunto)';
			$out[] = ['label' => $label, 'count' => (int)($r['total'] ?? 0)];
		}

		return $out;
	}

	/**
	 * @param string[] $cols
	 * @return array<int,array{day:string,abertos:int,fechados:int}>
	 */
	/**
	 * @param string[] $cols
	 * @return array<int,array{situacao:int,label:string,count:int,pct:float}>
	 */
	protected function porSituacaoAbertos(TicketsTable $tickets, int $idempresa, array $cols): array {
		if (!in_array('situacao', $cols, true)) {
			return [];
		}
		$closed = $this->closedSituacoes();
		$q = $tickets->find();
		($this->applyAbac)($q);
		$f = $q->func()->count('*');
		$w = ['Tickets.idempresa' => $idempresa];
		if ($closed !== []) {
			$w['Tickets.situacao NOT IN'] = $closed;
		}
		$rows = $q->select(['situacao', 'total' => $f])
			->where($w)
			->group(['situacao'])
			->enableHydration(false)
			->toArray();
		$sum = 0;
		foreach ($rows as $r) {
			$sum += (int)($r['total'] ?? 0);
		}
		$out = [];
		foreach ($rows as $r) {
			$sit = (int)($r['situacao'] ?? 0);
			$c = (int)($r['total'] ?? 0);
			$pct = $sum > 0 ? round(100 * $c / $sum, 1) : 0.0;
			$out[] = [
				'situacao' => $sit,
				'label' => $this->situacaoLabel($sit),
				'count' => $c,
				'pct' => $pct,
			];
		}
		usort($out, static function (array $a, array $b): int {
			return $b['count'] <=> $a['count'];
		});

		return $out;
	}

	/**
	 * @param string[] $cols
	 * @return array<int,array{user_id:int,initials:string,name:string,abertos:int}>
	 */
	protected function equipeComAbertos(TicketsTable $tickets, int $idempresa, UsersTable $users, array $cols): array {
		$tecCol = $this->ticketResponsavelColumn($cols);
		if ($tecCol === null || !in_array('situacao', $cols, true)) {
			return [];
		}
		$closed = $this->closedSituacoes();
		$w = [
			'Tickets.idempresa' => $idempresa,
			'Tickets.' . $tecCol . ' IS NOT' => null,
			'Tickets.' . $tecCol . ' !=' => 0,
		];
		if ($closed !== []) {
			$w['Tickets.situacao NOT IN'] = $closed;
		}
		$q = $tickets->find();
		($this->applyAbac)($q);
		$f = $q->func()->count('*');
		$rows = $q->select([$tecCol, 'total' => $f])
			->where($w)
			->group([$tecCol])
			->order(['total' => 'DESC'])
			->limit(8)
			->enableHydration(false)
			->toArray();
		if ($rows === []) {
			return [];
		}
		$uids = [];
		foreach ($rows as $r) {
			$uids[] = (int)($r[$tecCol] ?? 0);
		}
		$uids = array_values(array_filter(array_unique($uids)));
		$userRows = $uids === [] ? [] : $users->find()
			->select(['id', 'name', 'username'])
			->where(['id IN' => $uids])
			->all()
			->toArray();
		$byId = [];
		foreach ($userRows as $u) {
			$byId[(int)$u->get('id')] = $u;
		}
		$out = [];
		foreach ($rows as $r) {
			$uid = (int)($r[$tecCol] ?? 0);
			if ($uid <= 0) {
				continue;
			}
			$u = $byId[$uid] ?? null;
			$name = $u ? trim((string)($u->get('name') ?? '')) : '';
			if ($name === '' && $u) {
				$name = trim((string)($u->get('username') ?? ''));
			}
			if ($name === '') {
				$name = 'Usuário #' . $uid;
			}
			$initials = $this->initialsFromName($name);
			$out[] = [
				'user_id' => $uid,
				'initials' => $initials,
				'name' => $name,
				'abertos' => (int)($r['total'] ?? 0),
			];
		}

		return $out;
	}

	/**
	 * Últimos tickets em aberto no escopo ABAC (preview no dashboard).
	 *
	 * @param string[] $cols
	 * @return array<int,array<string,mixed>>
	 */
	protected function ticketsAbertosPreview(TicketsTable $tickets, int $idempresa, array $cols, int $limit): array {
		$closed = $this->closedSituacoes();
		if (!in_array('situacao', $cols, true)) {
			return [];
		}
		$w = ['Tickets.idempresa' => $idempresa];
		if ($closed !== []) {
			$w['Tickets.situacao NOT IN'] = $closed;
		}
		$q = $tickets->find()
			->contain(['Clientes'])
			->where($w)
			->order(['Tickets.id' => 'DESC'])
			->limit(max(1, min(20, $limit)));
		($this->applyAbac)($q);
		$out = [];
		foreach ($q->all() as $t) {
			$out[] = $this->mapFilaRow($tickets, $t, $cols);
		}

		return $out;
	}

	/**
	 * Assuntos com pico nas últimas 24h (escopo ABAC).
	 *
	 * @param string[] $cols
	 * @return array<int,array{label:string,count:int}>
	 */
	protected function assuntosQuentes24h(TicketsTable $tickets, int $idempresa, array $cols, int $minCount = 2): array {
		if (!in_array('assunto', $cols, true) || !in_array('created', $cols, true)) {
			return [];
		}
		$since = Time::now()->subHours(24);
		$q = $tickets->find();
		($this->applyAbac)($q);
		$f = $q->func()->count('*');
		$rows = $q->select(['assunto', 'total' => $f])
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $since->format('Y-m-d H:i:s'),
			])
			->group(['assunto'])
			->order(['total' => 'DESC'])
			->limit(20)
			->enableHydration(false)
			->toArray();
		$out = [];
		foreach ($rows as $r) {
			$c = (int)($r['total'] ?? 0);
			if ($c < max(1, $minCount)) {
				continue;
			}
			$raw = $r['assunto'] ?? '';
			$label = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
				? $tickets->resolveTicketAssuntoTextoPublic($raw)
				: (string)$raw;
			$out[] = ['label' => trim($label) !== '' ? $label : '(sem assunto)', 'count' => $c];
			if (count($out) >= 5) {
				break;
			}
		}

		return $out;
	}

	/**
	 * Detalhe de um ticket (somente leitura, respeita ABAC).
	 *
	 * @return array<string,mixed>|null
	 */
	public function buildTicketDetailPayload(TicketsTable $tickets, int $id, int $idempresa): ?array {
		$cols = $tickets->getSchema()->columns();
		$contain = ['Clientes', 'Users'];
		if ($tickets->associations()->has('Ticketcomentarios')) {
			$contain['Ticketcomentarios'] = ['Users'];
		}
		$q = $tickets->find()
			->contain($contain)
			->where([
				'Tickets.id' => $id,
				'Tickets.idempresa' => $idempresa,
			]);
		($this->applyAbac)($q);
		$t = $q->first();
		if ($t === null) {
			return null;
		}

		$assuntoTxt = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
			? $tickets->resolveTicketAssuntoTextoPublic($t->get('assunto'))
			: (string)$t->get('assunto');
		$sit = (int)($t->get('situacao') ?? 0);

		$c = $t->cliente ?? $t->clientes ?? null;
		$clienteNome = '—';
		$clienteEmail = '';
		if ($c) {
			$clienteNome = (int)($c->get('tipo') ?? 0) === 2
				? trim((string)($c->get('razaosocial') ?? ''))
				: trim((string)($c->get('nome') ?? ''));
			if ($clienteNome === '') {
				$clienteNome = '—';
			}
			$clienteEmail = trim((string)($c->get('email') ?? ''));
		}

		$tecNome = $this->resolveTicketTecnicoLabel($tickets, $t);
		$autorUser = $t->user ?? $t->users ?? null;

		$solicitante = trim((string)($t->get('solicitante') ?? ''));
		if ($solicitante === '') {
			$solicitante = $clienteNome;
		}

		$descricao = '';
		if (in_array('solicitacao', $cols, true)) {
			$descricao = trim((string)($t->get('solicitacao') ?? ''));
		}

		$created = $t->get('created');
		$modified = $t->get('modified') ?? $t->get('updated');
		$fmt = static function ($dt): string {
			if ($dt instanceof \DateTimeInterface) {
				return $dt->format('d/m/Y H:i');
			}

			return '—';
		};

		$slaLabel = '';
		$slaAlert = false;
		if (in_array('sla_status', $cols, true)) {
			$slaLabel = trim((string)($t->get('sla_status') ?? ''));
		}
		if ($slaLabel === '' && in_array('data_limite_resolucao', $cols, true)) {
			$dl = $t->get('data_limite_resolucao');
			if ($dl instanceof \DateTimeInterface && $dl < Time::now()) {
				$slaLabel = __('SLA vencido');
				$slaAlert = true;
			} elseif ($dl instanceof \DateTimeInterface) {
				$slaLabel = __('Limite') . ' ' . $dl->format('d/m/Y H:i');
			}
		}
		if ($slaLabel !== '' && (stripos($slaLabel, 'viol') !== false || stripos($slaLabel, 'estour') !== false)) {
			$slaAlert = true;
		}
		if (!$slaAlert && in_array('sla_status', $cols, true) && (string)$t->get('sla_status') === 'violado') {
			$slaAlert = true;
			if ($slaLabel === '') {
				$slaLabel = __('SLA estourado');
			}
		}

		$messages = [];
		$comentarios = $t->ticketcomentarios ?? [];
		if (is_iterable($comentarios)) {
			$list = is_array($comentarios) ? $comentarios : iterator_to_array($comentarios);
			usort($list, static function ($a, $b): int {
				$ida = (int)($a->get('id') ?? 0);
				$idb = (int)($b->get('id') ?? 0);

				return $ida <=> $idb;
			});
			foreach ($list as $com) {
				$body = strip_tags((string)($com->get('comentario') ?? ''));
				if (trim($body) === '') {
					continue;
				}
				$au = $com->user ?? null;
				$autor = '—';
				if ($au) {
					$autor = trim((string)($au->get('name') ?? ''));
					if ($autor === '') {
						$autor = trim((string)($au->get('username') ?? ''));
					}
				}
				$when = $com->get('created') ?? $com->get('data');
				$isInterno = false;
				try {
					$comCols = $com->getSource()->getSchema()->columns();
					$isInterno = in_array('interno', $comCols, true) && (bool)$com->get('interno');
				} catch (\Throwable $e) {
					$isInterno = false;
				}
				$messages[] = [
					'autor' => $autor !== '' ? $autor : '—',
					'initials' => $this->initialsFromName($autor),
					'when' => $fmt($when),
					'body' => $body,
					'tipo' => $isInterno ? 'interno' : 'publico',
				];
			}
		}

		$timeline = $this->ticketTimelineSteps($sit, $fmt($created), $fmt($modified));
		$pill = $this->situacaoPillMeta($sit);
		$prioMeta = $this->prioridadeMeta($t->get('prioridade'));
		$tempoTotal = $this->formatElapsed($created);

		return [
			'id' => (int)$t->get('id'),
			'assunto' => $assuntoTxt,
			'descricao' => $descricao,
			'situacao' => $sit,
			'situacao_label' => $this->situacaoLabel($sit),
			'situacao_pill' => $pill,
			'prioridade' => $t->get('prioridade'),
			'prioridade_meta' => $prioMeta,
			'sla_label' => $slaLabel,
			'sla_alert' => $slaAlert,
			'solicitante' => $solicitante,
			'solicitante_initials' => $this->initialsFromName($solicitante),
			'cliente' => $clienteNome,
			'cliente_email' => $clienteEmail,
			'tecnico' => $tecNome,
			'created_fmt' => $fmt($created),
			'modified_fmt' => $fmt($modified),
			'tempo_total' => $tempoTotal,
			'timeline' => $timeline,
			'messages' => $messages,
			'status_band_style' => $slaAlert
				? 'background:linear-gradient(135deg,#FEF2F2 0%,#fff 60%);border-left:4px solid #7A1822;'
				: 'background:linear-gradient(135deg,#F0FDF4 0%,#fff 60%);border-left:4px solid var(--teal);',
		];
	}

	/**
	 * Dados para gráficos da tela Relatórios (30 dias).
	 *
	 * @return array<string,mixed>
	 */
	public function buildRelatoriosPayload(
		TicketsTable $tickets,
		int $idempresa,
		ClientesTable $clientes,
		UsersTable $users
	): array {
		$cols = $tickets->getSchema()->columns();
		$since30 = Time::now()->subDays(30);
		$volume = $this->volumeDiarioNd($tickets, $idempresa, $cols, 30);
		$categorias = $this->topAssuntos($tickets, $idempresa, $cols, $since30);
		$totalCat = 0;
		foreach ($categorias as $c) {
			$totalCat += (int)($c['count'] ?? 0);
		}
		foreach ($categorias as &$c) {
			$c['pct'] = $totalCat > 0 ? round(100 * (int)$c['count'] / $totalCat, 1) : 0.0;
		}
		unset($c);

		return [
			'volume_30d' => $volume,
			'categorias' => $categorias,
			'tecnicos' => $this->tecnicosPerformance30d($tickets, $idempresa, $users, $cols),
		];
	}

	/**
	 * @param string[] $cols
	 * @return array<int,array{day:string,abertos:int,fechados:int}>
	 */
	protected function volumeDiarioNd(TicketsTable $tickets, int $idempresa, array $cols, int $days, bool $applyAbac = true): array {
		if (!in_array('created', $cols, true) || $days < 1) {
			return [];
		}
		$closed = $this->closedSituacoes();
		$out = [];
		$days = min(60, max(1, $days));
		for ($i = $days - 1; $i >= 0; $i--) {
			$day = Time::now()->subDays($i);
			$d0 = $day->format('Y-m-d') . ' 00:00:00';
			$d1 = $day->format('Y-m-d') . ' 23:59:59';
			$qA = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $d0,
				'Tickets.created <=' => $d1,
			]);
			if ($applyAbac) {
				($this->applyAbac)($qA);
			}
			$abertos = $qA->count();
			$fechados = 0;
			if (in_array('data_resolucao', $cols, true)) {
				$qF = $tickets->find()->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.data_resolucao >=' => $d0,
					'Tickets.data_resolucao <=' => $d1,
				]);
				if ($applyAbac) {
					($this->applyAbac)($qF);
				}
				$fechados = $qF->count();
			} elseif ($closed !== [] && in_array('situacao', $cols, true) && in_array('modified', $cols, true)) {
				$qF = $tickets->find()->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.modified >=' => $d0,
					'Tickets.modified <=' => $d1,
					'Tickets.situacao IN' => $closed,
				]);
				if ($applyAbac) {
					($this->applyAbac)($qF);
				}
				$fechados = $qF->count();
			}
			$out[] = [
				'day' => $day->format('d/m'),
				'abertos' => $abertos,
				'fechados' => $fechados,
			];
		}

		return $out;
	}

	/**
	 * @param string[] $cols
	 * @return array<int,array<string,mixed>>
	 */
	protected function tecnicosPerformance30d(
		TicketsTable $tickets,
		int $idempresa,
		UsersTable $users,
		array $cols
	): array {
		$tecCol = $this->ticketResponsavelColumn($cols);
		if ($tecCol === null || !in_array('created', $cols, true)) {
			return [];
		}
		$since = Time::now()->subDays(30)->format('Y-m-d H:i:s');
		$closed = $this->closedSituacoes();
		$q = $tickets->find();
		($this->applyAbac)($q);
		$f = $q->func()->count('*');
		$rows = $q->select([$tecCol, 'total' => $f])
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $since,
				'Tickets.' . $tecCol . ' IS NOT' => null,
				'Tickets.' . $tecCol . ' !=' => 0,
			])
			->group([$tecCol])
			->order(['total' => 'DESC'])
			->limit(12)
			->enableHydration(false)
			->toArray();
		if ($rows === []) {
			return [];
		}
		$uids = [];
		foreach ($rows as $r) {
			$uid = (int)($r[$tecCol] ?? 0);
			if ($uid > 0) {
				$uids[$uid] = $uid;
			}
		}
		$uids = array_values($uids);
		$userRows = $uids === [] ? [] : $users->find()->select(['id', 'name', 'username'])->where(['id IN' => $uids])->all();
		$byId = [];
		foreach ($userRows as $u) {
			$byId[(int)$u->get('id')] = $u;
		}
		$out = [];
		foreach ($rows as $r) {
			$uid = (int)($r[$tecCol] ?? 0);
			if ($uid <= 0) {
				continue;
			}
			$u = $byId[$uid] ?? null;
			$name = '—';
			if ($u) {
				$name = trim((string)($u->get('name') ?? ''));
				if ($name === '') {
					$name = trim((string)($u->get('username') ?? ''));
				}
			}
			$atrib = (int)($r['total'] ?? 0);
			$resolvidos = 0;
			if ($closed !== [] && in_array('situacao', $cols, true)) {
				$qr = $tickets->find()->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.' . $tecCol => $uid,
					'Tickets.created >=' => $since,
					'Tickets.situacao IN' => $closed,
				]);
				($this->applyAbac)($qr);
				$resolvidos = $qr->count();
			}
			$taxa = $atrib > 0 ? round(100 * $resolvidos / $atrib, 0) : 0;

			$out[] = [
				'nome' => $name !== '' ? $name : ('#' . $uid),
				'atribuidos' => $atrib,
				'resolvidos' => $resolvidos,
				'taxa' => $taxa,
			];
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function ticketTimelineSteps(int $currentSit, string $createdFmt, string $modifiedFmt): array {
		$defs = [];
		if (defined('C_TicketSituacaoPendente')) {
			$defs[] = ['sit' => (int)C_TicketSituacaoPendente, 'label' => __('Aberto')];
		}
		if (defined('C_TicketSituacaoEmandamento')) {
			$defs[] = ['sit' => (int)C_TicketSituacaoEmandamento, 'label' => __('Em execução')];
		}
		if (defined('C_TicketSituacaoRespondido')) {
			$defs[] = ['sit' => (int)C_TicketSituacaoRespondido, 'label' => __('Aguarda cliente')];
		}
		if (defined('C_TicketSituacaoResolvido')) {
			$defs[] = ['sit' => (int)C_TicketSituacaoResolvido, 'label' => __('Resolvido')];
		}
		if (defined('C_TicketSituacaoFechado')) {
			$defs[] = ['sit' => (int)C_TicketSituacaoFechado, 'label' => __('Fechado')];
		}
		if ($defs === []) {
			return [];
		}

		$order = array_column($defs, 'sit');
		$idx = array_search($currentSit, $order, true);
		if ($idx === false) {
			$idx = 0;
		}

		$steps = [];
		foreach ($defs as $i => $def) {
			$done = $i < $idx;
			$active = $i === $idx;
			$when = '—';
			if ($done || $active) {
				$when = $i === 0 ? $createdFmt : ($active ? $modifiedFmt : $createdFmt);
			}
			$steps[] = [
				'label' => (string)$def['label'],
				'done' => $done,
				'active' => $active,
				'when' => $when,
				'num' => $i + 1,
			];
		}

		return $steps;
	}

	protected function initialsFromName(string $name): string {
		$name = trim(preg_replace('/\s+/', ' ', $name));
		if ($name === '') {
			return '?';
		}
		$parts = explode(' ', $name);
		if (count($parts) >= 2) {
			return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1));
		}

		return strtoupper(mb_substr($name, 0, 2));
	}

	/**
	 * @return int[]
	 */
	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed>|null $row
	 * @param mixed $default
	 * @return mixed
	 */
	protected function rowGet($row, string $field, $default = null) {
		if ($row === null) {
			return $default;
		}
		if (is_array($row)) {
			return array_key_exists($field, $row) ? $row[$field] : $default;
		}
		if (is_object($row) && method_exists($row, 'get')) {
			$val = $row->get($field);

			return $val !== null ? $val : $default;
		}

		return $default;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $ticket
	 * @return \Cake\Datasource\EntityInterface|array<string,mixed>|null
	 */
	protected function ticketRelatedCliente($ticket) {
		if (is_array($ticket)) {
			return $ticket['cliente'] ?? $ticket['clientes'] ?? null;
		}

		return $ticket->cliente ?? $ticket->clientes ?? null;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $ticket
	 */
	public function resolveTicketTecnicoLabelPublic(TicketsTable $tickets, $ticket): string {
		return $this->resolveTicketTecnicoLabel($tickets, $ticket);
	}

	/**
	 * Situação para UI: workflow (se existir) e correção de legado incoerente (resolvido sem data_resolucao / sem técnico).
	 *
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $t
	 * @param string[] $cols
	 * @return array{situacao:int,situacao_db:int,situacao_label:string,situacao_pill:array,inconsistente:bool}
	 */
	protected function resolveSituacaoDisplay($t, array $cols): array {
		$sitDb = (int)$this->rowGet($t, 'situacao', 0);
		$sit = $sitDb;

		if (in_array('workflow_state_id', $cols, true)) {
			$wfId = (int)$this->rowGet($t, 'workflow_state_id', 0);
			if ($wfId > 0 && $this->tableExists('workflow_states')) {
				try {
					$st = TableRegistry::getTableLocator()->get('WorkflowStates')->find()
						->select(['codigo'])
						->where(['id' => $wfId])
						->enableHydration(false)
						->first();
					if ($st && !empty($st['codigo'])) {
						$mapped = $this->workflowCodigoToSituacao((string)$st['codigo']);
						if ($mapped !== null) {
							$sit = $mapped;
						}
					}
				} catch (\Throwable $e) {
					// mantém situacao legada
				}
			}
		}

		$closed = $this->closedSituacoes();
		$tecId = $this->ticketResponsavelId($t, $cols);
		$inconsistente = false;
		$isMarkedClosed = $closed !== [] && in_array($sitDb, $closed, true);
		$hasResolucao = in_array('data_resolucao', $cols, true)
			&& $this->rowGet($t, 'data_resolucao') instanceof \DateTimeInterface;

		if ($isMarkedClosed && !$hasResolucao && $tecId <= 0) {
			$sit = defined('C_TicketSituacaoPendente') ? (int)C_TicketSituacaoPendente : 0;
			$inconsistente = true;
		}

		return [
			'situacao' => $sit,
			'situacao_db' => $sitDb,
			'situacao_label' => $this->situacaoLabel($sit),
			'situacao_pill' => $this->situacaoPillMeta($sit),
			'inconsistente' => $inconsistente,
		];
	}

	protected function workflowCodigoToSituacao(string $codigo): ?int {
		$c = strtolower(trim($codigo));
		$c = strtr($c, [
			'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
			'é' => 'e', 'ê' => 'e',
			'í' => 'i',
			'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
			'ú' => 'u', 'ü' => 'u',
			'ç' => 'c',
		]);
		$c = preg_replace('/\s+/u', ' ', (string)$c);
		$map = [
			'aberto' => defined('C_TicketSituacaoPendente') ? (int)C_TicketSituacaoPendente : 0,
			'open' => defined('C_TicketSituacaoPendente') ? (int)C_TicketSituacaoPendente : 0,
			'pendente' => defined('C_TicketSituacaoPendente') ? (int)C_TicketSituacaoPendente : 0,
			'emandamento' => defined('C_TicketSituacaoEmandamento') ? (int)C_TicketSituacaoEmandamento : 1,
			'em_andamento' => defined('C_TicketSituacaoEmandamento') ? (int)C_TicketSituacaoEmandamento : 1,
			'em execucao' => defined('C_TicketSituacaoEmandamento') ? (int)C_TicketSituacaoEmandamento : 1,
			'respondido' => defined('C_TicketSituacaoRespondido') ? (int)C_TicketSituacaoRespondido : 4,
			'aguardando_cliente' => defined('C_TicketSituacaoRespondido') ? (int)C_TicketSituacaoRespondido : 4,
			'resolvido' => defined('C_TicketSituacaoResolvido') ? (int)C_TicketSituacaoResolvido : 2,
			'fechado' => defined('C_TicketSituacaoFechado') ? (int)C_TicketSituacaoFechado : 3,
		];

		return $map[$c] ?? null;
	}

	/**
	 * @param string[] $cols
	 */
	protected function ticketResponsavelColumn(array $cols): ?string {
		if (in_array('idtecnico_responsavel', $cols, true)) {
			return 'idtecnico_responsavel';
		}
		if (in_array('owner_id', $cols, true)) {
			return 'owner_id';
		}

		return null;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $ticket
	 */
	protected function ticketResponsavelId($ticket, array $cols): int {
		$col = $this->ticketResponsavelColumn($cols);
		if ($col === null) {
			return 0;
		}

		return (int)$this->rowGet($ticket, $col, 0);
	}

	protected function resolveTicketTecnicoLabel(TicketsTable $tickets, $ticket): string {
		$cols = $tickets->getSchema()->columns();
		$tecId = $this->ticketResponsavelId($ticket, $cols);
		if ($tecId > 0) {
			static $cache = [];
			if (!isset($cache[$tecId])) {
				try {
					$u = TableRegistry::getTableLocator()->get('Users')->find()
						->select(['id', 'name', 'username'])
						->where(['Users.id' => $tecId])
						->first();
					$cache[$tecId] = $u ? $this->rowUserDisplayName($u) : '—';
				} catch (\Throwable $e) {
					$cache[$tecId] = '—';
				}
			}

			return $cache[$tecId];
		}

		return __('Sem atribuição');
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed>|null $row
	 */
	protected function rowUserDisplayName($row): string {
		if ($row === null) {
			return '—';
		}
		$name = trim((string)$this->rowGet($row, 'name', ''));
		if ($name === '') {
			$name = trim((string)$this->rowGet($row, 'username', ''));
		}

		return $name !== '' ? $name : '—';
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $ticket
	 * @return \Cake\Datasource\EntityInterface|array<string,mixed>|null
	 */
	protected function ticketRelatedUser($ticket) {
		if (is_array($ticket)) {
			return $ticket['user'] ?? $ticket['users'] ?? null;
		}

		return $ticket->user ?? $ticket->users ?? null;
	}

	/**
	 * @return int[]
	 */
	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $t
	 * @param string[] $cols
	 * @return array<string,mixed>
	 */
	protected function mapFilaRow(TicketsTable $tickets, $t, array $cols): array {
		$assuntoRaw = $this->rowGet($t, 'assunto');
		$assuntoTxt = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
			? $tickets->resolveTicketAssuntoTextoPublic($assuntoRaw)
			: (string)$assuntoRaw;
		$c = $this->ticketRelatedCliente($t);
		$clienteNome = '—';
		if ($c !== null) {
			$clienteNome = (int)$this->rowGet($c, 'tipo', 0) === 2
				? trim((string)$this->rowGet($c, 'razaosocial', ''))
				: trim((string)$this->rowGet($c, 'nome', ''));
			if ($clienteNome === '') {
				$clienteNome = '—';
			}
		}
		$tecId = $this->ticketResponsavelId($t, $cols);
		$semTecnico = $tecId <= 0;
		$tec = $semTecnico ? __('Sem atribuição') : $this->resolveTicketTecnicoLabel($tickets, $t);
		$autor = $this->rowUserDisplayName($this->ticketRelatedUser($t));
		$sitDisp = $this->resolveSituacaoDisplay($t, $cols);
		$sit = (int)$sitDisp['situacao'];
		$pill = (array)$sitDisp['situacao_pill'];
		$prio = $this->prioridadeMeta($this->rowGet($t, 'prioridade'));
		$queueId = in_array('queue_id', $cols, true) ? (int)$this->rowGet($t, 'queue_id', 0) : 0;
		$filaLabel = $prio['fila'];
		$nivelLabel = $prio['nivel'];
		$qEnt = $this->ticketRelatedQueue($t);
		if ($qEnt !== null) {
			$qName = trim((string)$this->rowGet($qEnt, 'name', ''));
			if ($qName !== '') {
				$filaLabel = $qName;
			}
			$qSl = is_array($qEnt) ? ($qEnt['support_level'] ?? null) : ($qEnt->support_level ?? null);
			if ($qSl !== null) {
				$slName = trim((string)$this->rowGet($qSl, 'name', ''));
				if ($slName !== '') {
					$nivelLabel = $slName;
				}
			}
		}
		$tSl = $this->ticketRelatedSupportLevel($t);
		if ($tSl !== null) {
			$slName = trim((string)$this->rowGet($tSl, 'name', ''));
			if ($slName !== '') {
				$nivelLabel = $slName;
			}
		}
		$created = $this->rowGet($t, 'created');
		$slaStatus = in_array('sla_status', $cols, true) ? trim((string)$this->rowGet($t, 'sla_status', '')) : '';
		$limite = in_array('data_limite_resolucao', $cols, true) ? $this->rowGet($t, 'data_limite_resolucao') : null;
		$slaViolado = $slaStatus === 'violado' || $this->isSlaOverdue($limite);
		$excerpt = \Cake\Utility\Text::truncate($assuntoTxt, 72, ['ellipsis' => '…']);

		return [
			'id' => (int)$this->rowGet($t, 'id', 0),
			'assunto' => $assuntoTxt,
			'assunto_titulo' => \Cake\Utility\Text::truncate($assuntoTxt, 42, ['ellipsis' => '…']),
			'excerpt' => $excerpt,
			'cliente' => $clienteNome,
			'cliente_short' => mb_strtoupper(\Cake\Utility\Text::truncate($clienteNome, 16, ['ellipsis' => '…'])),
			'autor' => $autor,
			'autor_short' => \Cake\Utility\Text::truncate($autor, 18, ['ellipsis' => '…']),
			'situacao' => $sit,
			'situacao_db' => (int)$sitDisp['situacao_db'],
			'situacao_label' => (string)$sitDisp['situacao_label'],
			'situacao_pill' => $pill,
			'situacao_inconsistente' => !empty($sitDisp['inconsistente']),
			'prioridade' => $this->rowGet($t, 'prioridade'),
			'prioridade_meta' => $prio,
			'tecnico' => $tec,
			'tecnico_id' => $tecId,
			'tecnico_short' => \Cake\Utility\Text::truncate($tec, 22, ['ellipsis' => '…']),
			'sem_tecnico' => $semTecnico,
			'queue_id' => $queueId,
			'modified' => $this->rowGet($t, 'modified'),
			'created' => $created,
			'created_fmt' => $this->fmtDate($created),
			'tempo' => $this->filaTempoDisplay($tickets, $t, $cols),
			'sla_violado' => $slaViolado,
			'sla_status' => $slaStatus,
			'sla_limite_fmt' => $this->fmtDateTime($limite),
			'nivel' => $nivelLabel,
			'fila_label' => $filaLabel,
		];
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $ticket
	 * @return \Cake\Datasource\EntityInterface|array<string,mixed>|null
	 */
	protected function ticketRelatedQueue($ticket) {
		if (is_array($ticket)) {
			return $ticket['queue'] ?? $ticket['queues'] ?? null;
		}

		return $ticket->queue ?? $ticket->queues ?? null;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $ticket
	 * @return \Cake\Datasource\EntityInterface|array<string,mixed>|null
	 */
	protected function ticketRelatedSupportLevel($ticket) {
		if (is_array($ticket)) {
			return $ticket['support_level'] ?? null;
		}

		return $ticket->support_level ?? null;
	}

	/**
	 * @param mixed $deadline
	 */
	protected function isSlaOverdue($deadline): bool {
		if (!$deadline instanceof \DateTimeInterface) {
			return false;
		}

		return $deadline < Time::now();
	}

	/**
	 * @param mixed $dt
	 */
	public function fmtDate($dt): string {
		if ($dt instanceof \DateTimeInterface) {
			return $dt->format('d/m/Y');
		}

		return '—';
	}

	/**
	 * @param mixed $dt
	 */
	public function fmtDateTime($dt): string {
		if ($dt instanceof \DateTimeInterface) {
			return $dt->format('d/m/Y, H:i:s');
		}

		return '—';
	}

	/**
	 * @param mixed $start
	 */
	public function formatElapsed($start): string {
		if (!$start instanceof \DateTimeInterface) {
			return '—';
		}
		$sec = max(0, Time::now()->getTimestamp() - $start->getTimestamp());

		return $this->formatSecondsHms($sec);
	}

	/**
	 * Tempo de atendimento (timer tickets + ticketshoras), alinhado à grade do Service Desk.
	 *
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $t
	 * @param string[] $cols
	 */
	protected function filaTempoDisplay(TicketsTable $ticketsTable, $t, array $cols): string {
		$sec = $this->filaTempoSegundos($ticketsTable, $t, $cols);
		if ($sec <= 0) {
			return '—';
		}

		return $this->formatSecondsHms($sec);
	}

	/**
	 * Segundos de atendimento: total_seconds/started_at (TicketAttendimentoTimerService) ou soma em ticketshoras.
	 *
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $t
	 * @param string[] $cols
	 */
	protected function filaTempoSegundos(TicketsTable $ticketsTable, $t, array $cols): int {
		$id = (int)$this->rowGet($t, 'id', 0);
		if ($id <= 0) {
			return 0;
		}
		if (TicketAttendimentoTimerService::columnsReady($ticketsTable) && $t instanceof EntityInterface) {
			$totalSeconds = in_array('total_seconds', $cols, true)
				? (int)($this->rowGet($t, 'total_seconds') ?? 0)
				: 0;
			if ($totalSeconds <= 0) {
				$totalSeconds = $this->segundosRegistradosTicketshoras($id);
			}
			$elapsed = TicketAttendimentoTimerService::elapsedSecondsForDisplay(
				$ticketsTable,
				$t,
				time()
			);
			if ($elapsed < $totalSeconds) {
				$elapsed = $totalSeconds;
			}

			return max(0, $elapsed);
		}

		return $this->segundosRegistradosTicketshoras($id);
	}

	protected function segundosRegistradosTicketshoras(int $idticket): int {
		if ($idticket <= 0) {
			return 0;
		}
		try {
			$th = TableRegistry::getTableLocator()->get('Ticketshoras');
		} catch (\Throwable $e) {
			return 0;
		}
		$sum = 0;
		foreach ($th->find()->where(['idticket' => $idticket])->all() as $h) {
			$sec = TicketServiceDeskApiService::resolveSecondsFromTicketshorasRow($th, $h);
			if ($sec > 0) {
				$sum += $sec;
			}
		}

		return max(0, $sum);
	}

	protected function formatSecondsHms(int $sec): string {
		$sec = max(0, $sec);
		$h = (int)floor($sec / 3600);
		$m = (int)floor(($sec % 3600) / 60);
		$s = (int)($sec % 60);

		return sprintf('%02d:%02d:%02d', $h, $m, $s);
	}

	/**
	 * @return array{bg:string,color:string,label:string}
	 */
	public function situacaoPillMeta(int $sit): array {
		if (defined('C_TicketSituacaoResolvido') && $sit === (int)C_TicketSituacaoResolvido) {
			return ['bg' => '#10B981', 'color' => '#fff', 'label' => $this->situacaoLabel($sit)];
		}
		if (defined('C_TicketSituacaoFechado') && $sit === (int)C_TicketSituacaoFechado) {
			return ['bg' => '#6B7280', 'color' => '#fff', 'label' => $this->situacaoLabel($sit)];
		}
		if (defined('C_TicketSituacaoEmandamento') && $sit === (int)C_TicketSituacaoEmandamento) {
			return ['bg' => '#06B6D4', 'color' => '#fff', 'label' => $this->situacaoLabel($sit)];
		}
		if (defined('C_TicketSituacaoRespondido') && $sit === (int)C_TicketSituacaoRespondido) {
			return ['bg' => '#F59E0B', 'color' => '#fff', 'label' => $this->situacaoLabel($sit)];
		}
		if (defined('C_TicketSituacaoPendente') && $sit === (int)C_TicketSituacaoPendente) {
			return ['bg' => '#F59E0B', 'color' => '#fff', 'label' => $this->situacaoLabel($sit)];
		}

		return ['bg' => '#7DD3C0', 'color' => '#0a3d2c', 'label' => $this->situacaoLabel($sit)];
	}

	/**
	 * @param mixed $prio
	 * @return array{label:string,nivel:string,fila:string,critical:bool,border:string,bg:string}
	 */
	public function prioridadeMeta($prio): array {
		$p = is_numeric($prio) ? (int)$prio : 0;
		$labels = [1 => __('Baixo'), 2 => __('Médio'), 3 => __('Alto'), 4 => __('Crítico')];
		$label = $labels[$p] ?? __('Baixo');
		$nivel = $p >= 4 ? 'N3' : ($p >= 3 ? 'N2' : 'N1');
		$filas = [
			1 => 'N1 — ' . __('Suporte básico'),
			2 => 'N2 — ' . __('Suporte avançado'),
			3 => 'N3 — ' . __('Especialistas'),
			4 => 'N3 — ' . __('Especialistas'),
		];
		$critical = $p >= 4;

		return [
			'label' => $label,
			'nivel' => $nivel,
			'fila' => $filas[$p] ?? $filas[1],
			'critical' => $critical,
			'border' => $critical ? 'var(--red)' : 'var(--border)',
			'bg' => $critical ? '#FEF2F2' : '#fff',
		];
	}

	protected function tableExists(string $table): bool {
		try {
			$conn = TableRegistry::getTableLocator()->get('Tickets')->getConnection();

			return in_array($table, $conn->getSchemaCollection()->listTables(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Fila de aprovações (pg-sd-aprovacoes) — pedidos reais pendentes + histórico do mês.
	 *
	 * @param array<string,mixed> $query tab: pendentes|aprovadas|reprovadas|historico
	 * @return array<string,mixed>
	 */
	public function buildAprovacoesPayload(TicketsTable $tickets, int $idempresa, array $query = []): array {
		$tab = trim((string)($query['tab'] ?? 'pendentes'));
		if (!in_array($tab, ['pendentes', 'aprovadas', 'reprovadas', 'historico'], true)) {
			$tab = 'pendentes';
		}

		$pending = [];
		$approvedMonth = [];
		$rejectedMonth = [];
		$history = [];

		$monthStart = Time::now()->startOfMonth();
		$now = Time::now();

		$this->aprovacoesCollectRbac($pending, $approvedMonth, $rejectedMonth, $history, $monthStart);
		$this->aprovacoesCollectContractRenewals($pending, $approvedMonth, $rejectedMonth, $history, $idempresa, $monthStart);
		$this->aprovacoesCollectOrcamentos($pending, $approvedMonth, $rejectedMonth, $history, $idempresa, $monthStart);
		$this->aprovacoesCollectTickets($pending, $approvedMonth, $rejectedMonth, $history, $tickets, $idempresa, $monthStart);

		usort($pending, static function (array $a, array $b): int {
			return ($b['sort_ts'] ?? 0) <=> ($a['sort_ts'] ?? 0);
		});
		usort($history, static function (array $a, array $b): int {
			return ($b['sort_ts'] ?? 0) <=> ($a['sort_ts'] ?? 0);
		});

		$pendentesCount = count($pending);
		$aprovadasMes = count($approvedMonth);
		$reprovadasMes = count($rejectedMonth);
		$totalDecidido = $aprovadasMes + $reprovadasMes;
		$reprovPct = $totalDecidido > 0 ? (int)round(100 * $reprovadasMes / $totalDecidido) : 0;

		$items = $pending;
		if ($tab === 'aprovadas') {
			$items = $approvedMonth;
		} elseif ($tab === 'reprovadas') {
			$items = $rejectedMonth;
		} elseif ($tab === 'historico') {
			$items = array_slice($history, 0, 60);
		}

		foreach ($items as &$it) {
			unset($it['sort_ts']);
		}
		unset($it);

		return [
			'tab' => $tab,
			'stats' => [
				'pendentes' => $pendentesCount,
				'aprovadas_mes' => $aprovadasMes,
				'reprovadas_mes' => $reprovadasMes,
				'reprovacao_pct' => $reprovPct . '%',
				'tempo_medio' => $this->aprovacoesTempoMedioLabel($approvedMonth),
				'sla_label' => __('SLA 24h'),
				'trend' => $this->aprovacoesTrendLabel($aprovadasMes),
			],
			'tabs' => [
				['id' => 'pendentes', 'label' => __('Pendentes'), 'icon' => '📌', 'count' => $pendentesCount],
				['id' => 'aprovadas', 'label' => __('Aprovadas'), 'icon' => '✓', 'count' => $aprovadasMes],
				['id' => 'reprovadas', 'label' => __('Reprovadas'), 'icon' => '✗', 'count' => $reprovadasMes],
				['id' => 'historico', 'label' => __('Histórico'), 'icon' => '📜', 'count' => count($history)],
			],
			'items' => $items,
			'empty' => $tab === 'pendentes'
				? __('Nenhuma solicitação pendente de aprovação no seu escopo.')
				: __('Nenhum registro nesta aba para o período.'),
		];
	}

	/**
	 * Contagem rápida para badge do menu.
	 */
	public function countAprovacoesPendentes(TicketsTable $tickets, int $idempresa): int {
		$payload = $this->buildAprovacoesPayload($tickets, $idempresa, ['tab' => 'pendentes']);

		return (int)($payload['stats']['pendentes'] ?? 0);
	}

	/**
	 * @param array<int,array<string,mixed>> $pending
	 * @param array<int,array<string,mixed>> $approvedMonth
	 * @param array<int,array<string,mixed>> $rejectedMonth
	 * @param array<int,array<string,mixed>> $history
	 */
	protected function aprovacoesCollectRbac(
		array &$pending,
		array &$approvedMonth,
		array &$rejectedMonth,
		array &$history,
		Time $monthStart
	): void {
		if (!$this->tableExists('rbac_access_requests')) {
			return;
		}
		try {
			$rows = TableRegistry::getTableLocator()->get('RbacAccessRequests')->find()
				->order(['RbacAccessRequests.created' => 'DESC'])
				->limit(80)
				->all();
			foreach ($rows as $r) {
				$status = (string)$r->get('status');
				$created = $r->get('created');
				$ts = $created instanceof \DateTimeInterface ? $created->getTimestamp() : time();
				$requester = $this->plantaoUserName((int)$r->get('user_id'), []);
				$perms = \Cake\Utility\Text::truncate((string)($r->get('requested_permission_codes') ?? $r->get('justification') ?? ''), 120, ['ellipsis' => '…']);
				$code = (string)($r->get('support_code') ?? $r->get('id'));

				if (in_array($status, ['pending_manager', 'pending_admin', 'manager_approved'], true)) {
					$stageLabel = $status === 'pending_manager'
						? __('Etapa: aguarda manager (1/2)')
						: __('Etapa: aguarda admin (2/2)');
					$pending[] = $this->aprovacaoItem([
						'id' => 'rbac-' . (int)$r->get('id'),
						'type' => 'acesso',
						'tag' => '🔐 ' . __('ACESSO ELEVADO'),
						'tag_style' => 'red',
						'title' => __('Permissão RBAC') . ' · ' . $code,
						'meta' => sprintf(
							__('Solicitado por %s · %s · %s'),
							$requester,
							$this->aprovacaoRelTime($created),
							$stageLabel
						),
						'due_badge' => $this->aprovacaoDueBadge($created),
						'body_mode' => 'text',
						'body_text' => $perms !== '' ? '"' . $perms . '"' : __('Sem justificativa informada.'),
						'rbac_stage' => $status,
						'rbac_manager_at' => $r->get('manager_reviewed_at'),
						'rbac_manager_response' => (string)($r->get('manager_response') ?? ''),
						'actions' => [
							$this->aprovacaoAction(__('Ver pedido'), ['controller' => 'RbacAccessRequests', 'action' => 'visualizarPedidoAcesso', (int)$r->get('id')], 'btn btn-ghost btn-sm'),
							$this->aprovacaoAction('✗ ' . __('Reprovar'), ['controller' => 'RbacAccessRequests', 'action' => 'pedidosAcessoManager'], 'btn btn-red btn-sm'),
							$this->aprovacaoAction('✓ ' . __('Aprovar'), ['controller' => 'RbacAccessRequests', 'action' => 'pedidosAcessoManager'], 'btn btn-primary btn-sm'),
						],
						'sort_ts' => $ts,
					]);
					continue;
				}

				$reviewed = $r->get('admin_reviewed_at') ?? $r->get('manager_reviewed_at');
				if (!$reviewed instanceof \DateTimeInterface || $reviewed < $monthStart) {
					continue;
				}
				$histItem = $this->aprovacaoItem([
					'id' => 'rbac-h-' . (int)$r->get('id'),
					'type' => 'acesso',
					'tag' => '🔐 RBAC',
					'tag_style' => 'red',
					'title' => $code . ' · ' . $requester,
					'meta' => $reviewed->format('d/m/Y H:i'),
					'body_mode' => 'text',
					'body_text' => $perms,
					'actions' => [
						$this->aprovacaoAction(__('Ver'), ['controller' => 'RbacAccessRequests', 'action' => 'visualizarPedidoAcesso', (int)$r->get('id')], 'btn btn-ghost btn-xs'),
					],
					'sort_ts' => $reviewed->getTimestamp(),
				]);
				$history[] = $histItem;
				if (strpos($status, 'reject') !== false || $status === 'rejected') {
					$rejectedMonth[] = $histItem;
				} elseif (strpos($status, 'approv') !== false || $status === 'granted') {
					$approvedMonth[] = $histItem;
				}
			}
		} catch (\Throwable $e) {
		}
	}

	/**
	 * @param array<int,array<string,mixed>> $pending
	 * @param array<int,array<string,mixed>> $approvedMonth
	 * @param array<int,array<string,mixed>> $rejectedMonth
	 * @param array<int,array<string,mixed>> $history
	 */
	protected function aprovacoesCollectContractRenewals(
		array &$pending,
		array &$approvedMonth,
		array &$rejectedMonth,
		array &$history,
		int $idempresa,
		Time $monthStart
	): void {
		if (!$this->tableExists('contract_renewals')) {
			return;
		}
		try {
			$q = TableRegistry::getTableLocator()->get('ContractRenewals')->find()
				->contain(['Contracts', 'Solicitante'])
				->innerJoinWith('Contracts', function ($q) use ($idempresa) {
					return $q->where(['Contracts.idempresa' => $idempresa]);
				})
				->order(['ContractRenewals.created' => 'DESC'])
				->limit(40);
			foreach ($q->all() as $ren) {
				$st = (string)$ren->get('status');
				$contract = $ren->contract ?? null;
				$contractName = $contract ? (string)($contract->get('name') ?? $contract->get('code') ?? '') : __('Contrato');
				$solic = $ren->solicitante ?? null;
				$requester = $solic ? $this->rowUserDisplayName($solic) : '—';
				$valor = (float)($ren->get('novo_valor_mensal') ?? 0);
				$created = $ren->get('solicitado_em') ?? $ren->get('created');
				$ts = $created instanceof \DateTimeInterface ? $created->getTimestamp() : time();

				if ($st === 'pendente') {
					$pending[] = $this->aprovacaoItem([
						'id' => 'ren-' . (int)$ren->get('id'),
						'type' => 'desconto',
						'tag' => '💰 ' . __('RENOVAÇÃO'),
						'tag_style' => 'amber',
						'title' => __('Renovação contratual') . ' · ' . $contractName,
						'meta' => sprintf(__('Solicitado por %s · %s'), $requester, $this->aprovacaoRelTime($created)),
						'due_badge' => $this->aprovacaoDueBadge($created),
						'body_mode' => 'finance',
						'finance' => [
							'original' => $valor > 0 ? $this->formatBrl($valor) : '—',
							'discount' => '—',
							'final' => $valor > 0 ? $this->formatBrl($valor) : '—',
						],
						'body_text' => (string)($ren->get('observacoes') ?? ''),
						'actions' => [
							$this->aprovacaoAction(__('Ver contrato'), ['controller' => 'ContractManagement', 'action' => 'view', (int)$ren->get('contract_id')], 'btn btn-ghost btn-sm'),
							$this->aprovacaoAction('✓ ' . __('Aprovar'), ['controller' => 'ContractManagement', 'action' => 'view', (int)$ren->get('contract_id')], 'btn btn-primary btn-sm'),
						],
						'sort_ts' => $ts,
					]);
					continue;
				}

				$rev = $ren->get('aprovado_em');
				if (!$rev instanceof \DateTimeInterface || $rev < $monthStart) {
					continue;
				}
				$histItem = $this->aprovacaoItem([
					'id' => 'ren-h-' . (int)$ren->get('id'),
					'type' => 'desconto',
					'tag' => '💰 ' . __('RENOVAÇÃO'),
					'tag_style' => 'amber',
					'title' => $contractName,
					'meta' => $rev->format('d/m/Y H:i'),
					'body_mode' => 'text',
					'body_text' => (string)($ren->get('observacoes') ?? ''),
					'actions' => [],
					'sort_ts' => $rev->getTimestamp(),
				]);
				$history[] = $histItem;
				if ($st === 'recusada') {
					$rejectedMonth[] = $histItem;
				} elseif ($st === 'aprovada') {
					$approvedMonth[] = $histItem;
				}
			}
		} catch (\Throwable $e) {
		}
	}

	/**
	 * @param array<int,array<string,mixed>> $pending
	 * @param array<int,array<string,mixed>> $approvedMonth
	 * @param array<int,array<string,mixed>> $rejectedMonth
	 * @param array<int,array<string,mixed>> $history
	 */
	protected function aprovacoesCollectOrcamentos(
		array &$pending,
		array &$approvedMonth,
		array &$rejectedMonth,
		array &$history,
		int $idempresa,
		Time $monthStart
	): void {
		if (!$this->tableExists('orcamentos')) {
			return;
		}
		$stPend = defined('C_OrcamentoStatusPendente') ? (int)C_OrcamentoStatusPendente : 0;
		$stEnv = defined('C_OrcamentoStatusEnviado') ? (int)C_OrcamentoStatusEnviado : 1;
		$stApr = defined('C_OrcamentoStatusAprovado') ? (int)C_OrcamentoStatusAprovado : 2;
		$stRec = defined('C_OrcamentoStatusRecusado') ? (int)C_OrcamentoStatusRecusado : 3;
		try {
			$rows = TableRegistry::getTableLocator()->get('Orcamentos')->find()
				->contain(['Clientes', 'Users'])
				->where(['Orcamentos.idempresa' => $idempresa])
				->order(['Orcamentos.modified' => 'DESC'])
				->limit(50)
				->all();
			foreach ($rows as $o) {
				$st = (int)$o->get('status');
				$cl = $o->cliente ?? null;
				$cn = $cl ? (string)($cl->get('razaosocial') ?? $cl->get('nome') ?? '') : __('Cliente');
				$autor = $o->user ?? null;
				$requester = $autor ? $this->rowUserDisplayName($autor) : '—';
				$created = $o->get('modified') ?? $o->get('created');
				$ts = $created instanceof \DateTimeInterface ? $created->getTimestamp() : time();
				$valor = (float)($o->get('valortotal') ?? $o->get('valor') ?? 0);

				if ($st === $stPend || $st === $stEnv) {
					$pending[] = $this->aprovacaoItem([
						'id' => 'orc-' . (int)$o->get('id'),
						'type' => 'desconto',
						'tag' => '💰 ' . __('ORÇAMENTO'),
						'tag_style' => 'amber',
						'title' => ($st === $stEnv ? __('Orçamento enviado') : __('Orçamento pendente')) . ' · ' . $cn,
						'meta' => sprintf(__('Solicitado por %s · %s · #%d'), $requester, $this->aprovacaoRelTime($created), (int)$o->get('id')),
						'due_badge' => $this->aprovacaoDueBadge($created),
						'body_mode' => 'finance',
						'finance' => [
							'original' => $valor > 0 ? $this->formatBrl($valor) : '—',
							'discount' => '—',
							'final' => $valor > 0 ? $this->formatBrl($valor) : '—',
						],
						'body_text' => \Cake\Utility\Text::truncate((string)($o->get('observacao') ?? ''), 200, ['ellipsis' => '…']),
						'actions' => [
							$this->aprovacaoAction(__('Ver orçamento'), ['controller' => 'Orcamentos', 'action' => 'view', (int)$o->get('id')], 'btn btn-ghost btn-sm'),
							$this->aprovacaoAction('✓ ' . __('Aprovar'), ['controller' => 'Orcamentos', 'action' => 'aprovar', (int)$o->get('id')], 'btn btn-primary btn-sm'),
						],
						'sort_ts' => $ts,
					]);
					continue;
				}

				$mod = $o->get('modified');
				if (!$mod instanceof \DateTimeInterface || $mod < $monthStart) {
					continue;
				}
				if ($st !== $stApr && $st !== $stRec) {
					continue;
				}
				$histItem = $this->aprovacaoItem([
					'id' => 'orc-h-' . (int)$o->get('id'),
					'type' => 'desconto',
					'tag' => '💰 ORÇ',
					'tag_style' => 'amber',
					'title' => '#' . (int)$o->get('id') . ' · ' . $cn,
					'meta' => $mod->format('d/m/Y H:i'),
					'body_mode' => 'text',
					'body_text' => $valor > 0 ? $this->formatBrl($valor) : '',
					'actions' => [],
					'sort_ts' => $mod->getTimestamp(),
				]);
				$history[] = $histItem;
				if ($st === $stRec) {
					$rejectedMonth[] = $histItem;
				} else {
					$approvedMonth[] = $histItem;
				}
			}
		} catch (\Throwable $e) {
		}
	}

	/**
	 * @param array<int,array<string,mixed>> $pending
	 * @param array<int,array<string,mixed>> $approvedMonth
	 * @param array<int,array<string,mixed>> $rejectedMonth
	 * @param array<int,array<string,mixed>> $history
	 */
	protected function aprovacoesCollectTickets(
		array &$pending,
		array &$approvedMonth,
		array &$rejectedMonth,
		array &$history,
		TicketsTable $tickets,
		int $idempresa,
		Time $monthStart
	): void {
		$cols = $tickets->getSchema()->columns();
		if (!in_array('situacao', $cols, true)) {
			return;
		}
		$closed = $this->closedSituacoes();
		$resolvido = defined('C_TicketSituacaoResolvido') ? (int)C_TicketSituacaoResolvido : -1;

		if ($resolvido >= 0) {
			try {
				$since = $this->aprovacoesTicketFechamentoSince();
				$activityCol = $this->aprovacoesTicketActivityColumn($cols);
				$fechamentoWhere = [
					'Tickets.idempresa' => $idempresa,
					'Tickets.situacao' => $resolvido,
				];
				$canFilter = false;
				if (in_array('data_resolucao', $cols, true) && $activityCol !== null) {
					$fechamentoWhere['OR'] = [
						'Tickets.data_resolucao >=' => $since,
						[
							'Tickets.data_resolucao IS' => null,
							'Tickets.' . $activityCol . ' >=' => $since,
						],
					];
					$canFilter = true;
				} elseif (in_array('data_resolucao', $cols, true)) {
					$fechamentoWhere['Tickets.data_resolucao >='] = $since;
					$canFilter = true;
				} elseif ($activityCol !== null) {
					$fechamentoWhere['Tickets.' . $activityCol . ' >='] = $since;
					$canFilter = true;
				}
				if ($canFilter) {
					$orderCol = $activityCol !== null ? 'Tickets.' . $activityCol : 'Tickets.id';
					$q = $tickets->find()
						->contain(['Clientes'])
						->where($fechamentoWhere)
						->order([$orderCol => 'DESC'])
						->limit(25);
					($this->applyAbac)($q);
					foreach ($q->all() as $t) {
						$tid = (int)$t->get('id');
						$created = ($activityCol !== null ? $t->get($activityCol) : null)
							?? $t->get('data_resolucao')
							?? $t->get('created');
						$ts = $created instanceof \DateTimeInterface ? $created->getTimestamp() : time();
						$tech = $this->resolveTicketTecnicoLabel($tickets, $t);
						$cl = $t->cliente ?? null;
						$cn = $cl ? (string)($cl->get('razaosocial') ?? $cl->get('nome') ?? '') : '';
						$pending[] = $this->aprovacaoItem([
							'id' => 'tkt-res-' . $tid,
							'type' => 'reabertura',
							'tag' => '🔄 ' . __('FECHAMENTO'),
							'tag_style' => 'purple',
							'title' => __('Validar fechamento ticket #%d', $tid),
							'meta' => sprintf(
								__('%s · %s%s'),
								$tech,
								$this->aprovacaoRelTime($created),
								$cn !== '' ? ' · ' . $cn : ''
							),
							'due_badge' => $this->aprovacaoDueBadge($created),
							'body_mode' => 'text',
							'body_text' => \Cake\Utility\Text::truncate((string)($t->get('solicitacao') ?? ''), 220, ['ellipsis' => '…']),
							'actions' => [
								$this->aprovacaoAction(__('Ver ticket'), ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', $tid], 'btn btn-ghost btn-sm'),
								$this->aprovacaoAction('✓ ' . __('Fechar'), ['controller' => 'Tickets', 'action' => 'view', $tid], 'btn btn-primary btn-sm'),
							],
							'sort_ts' => $ts,
						]);
					}
				}
			} catch (\Throwable $e) {
			}
		}

		if ($closed === []) {
			return;
		}
		$where = [
			'Tickets.idempresa' => $idempresa,
			'Tickets.situacao NOT IN' => $closed,
		];
		try {
			$q2 = $tickets->find()
				->contain(['Clientes'])
				->where($where)
				->order(['Tickets.created' => 'DESC'])
				->limit(40);
			($this->applyAbac)($q2);
			foreach ($q2->all() as $t) {
				$tid = (int)$t->get('id');
				$isMudanca = false;
				if (in_array('queue_id', $cols, true) && $this->tableExists('queues')) {
					$qid = (int)$t->get('queue_id');
					if ($qid > 0) {
						try {
							$qr = TableRegistry::getTableLocator()->get('Queues')->get($qid);
							$blob = strtolower((string)$qr->get('codigo') . ' ' . (string)$qr->get('name'));
							$isMudanca = (strpos($blob, 'mudanca') !== false || strpos($blob, 'mudança') !== false || strpos($blob, 'change') !== false);
						} catch (\Throwable $e) {
						}
					}
				}
				if (!$isMudanca) {
					if (!in_array('prioridade', $cols, true)) {
						continue;
					}
					if (TicketPriorityKpi::mapToPxBucket((string)$t->get('prioridade')) !== 'P1') {
						continue;
					}
				}
				$created = $t->get('created');
				$ts = $created instanceof \DateTimeInterface ? $created->getTimestamp() : time();
				$tech = $this->resolveTicketTecnicoLabel($tickets, $t);
				$assunto = \Cake\Utility\Text::truncate((string)($t->get('solicitacao') ?? ''), 80, ['ellipsis' => '…']);
				$pending[] = $this->aprovacaoItem([
					'id' => 'tkt-chg-' . $tid,
					'type' => 'mudanca',
					'tag' => '⚙ ' . __('MUDANÇA'),
					'tag_style' => 'blue',
					'title' => 'CHG-' . $tid . ' · ' . $assunto,
					'meta' => sprintf(__('Solicitado por %s · %s'), $tech, $this->aprovacaoRelTime($created)),
					'due_badge' => ['text' => '⚠ ' . __('Alto risco'), 'style' => 'red'],
					'body_mode' => 'bullets',
					'bullets' => [
						['label' => __('Impacto'), 'text' => __('Ticket crítico em aberto na fila de mudanças.')],
						['label' => __('Rollback'), 'text' => __('Seguir plano de mudança vinculado ao ticket.')],
					],
					'actions' => [
						$this->aprovacaoAction(__('Ver ticket'), ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', $tid], 'btn btn-ghost btn-sm'),
						$this->aprovacaoAction('✓ ' . __('Aprovar'), ['controller' => 'Tickets', 'action' => 'view', $tid], 'btn btn-primary btn-sm'),
					],
					'sort_ts' => $ts,
				]);
			}
		} catch (\Throwable $e) {
		}

		if (in_array('sla_escalated_at', $cols, true)) {
			try {
				$q3 = $tickets->find()
					->where([
						'Tickets.idempresa' => $idempresa,
						'Tickets.sla_escalated_at IS NOT' => null,
						'Tickets.situacao NOT IN' => $closed,
					])
					->order(['Tickets.sla_escalated_at' => 'DESC'])
					->limit(10);
				($this->applyAbac)($q3);
				foreach ($q3->all() as $t) {
					$tid = (int)$t->get('id');
					$esc = $t->get('sla_escalated_at');
					$ts = $esc instanceof \DateTimeInterface ? $esc->getTimestamp() : time();
					$pending[] = $this->aprovacaoItem([
						'id' => 'tkt-esc-' . $tid,
						'type' => 'escalonamento',
						'tag' => '↻ ' . __('ESCALONAMENTO'),
						'tag_style' => 'pink',
						'title' => __('Escalonamento SLA · ticket #%d', $tid),
						'meta' => $this->aprovacaoRelTime($esc) . ' · #' . $tid,
						'due_badge' => $this->aprovacaoDueBadge($esc, 8),
						'body_mode' => 'text',
						'body_text' => __('Ticket escalonado automaticamente por violação de SLA de resolução.'),
						'actions' => [
							$this->aprovacaoAction(__('Ver ticket'), ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', $tid], 'btn btn-ghost btn-sm'),
						],
						'sort_ts' => $ts,
					]);
				}
			} catch (\Throwable $e) {
			}
		}

		if ($closed !== [] && in_array('data_resolucao', $cols, true)) {
			try {
				$q4 = $tickets->find()
					->where([
						'Tickets.idempresa' => $idempresa,
						'Tickets.situacao IN' => $closed,
						'Tickets.data_resolucao >=' => $monthStart,
					])
					->order(['Tickets.data_resolucao' => 'DESC'])
					->limit(30);
				($this->applyAbac)($q4);
				foreach ($q4->all() as $t) {
					$tid = (int)$t->get('id');
					$dr = $t->get('data_resolucao');
					if (!$dr instanceof \DateTimeInterface) {
						continue;
					}
					$histItem = $this->aprovacaoItem([
						'id' => 'tkt-ok-' . $tid,
						'type' => 'reabertura',
						'tag' => '✓ TICKET',
						'tag_style' => 'teal',
						'title' => '#' . $tid,
						'meta' => $dr->format('d/m/Y H:i'),
						'body_mode' => 'text',
						'body_text' => '',
						'actions' => [],
						'sort_ts' => $dr->getTimestamp(),
					]);
					$history[] = $histItem;
					$approvedMonth[] = $histItem;
				}
			} catch (\Throwable $e) {
			}
		}
	}

	/**
	 * @param array<string,mixed> $fields
	 * @return array<string,mixed>
	 */
	protected function aprovacaoItem(array $fields): array {
		return array_merge([
			'due_badge' => null,
			'body_mode' => 'text',
			'body_text' => '',
			'finance' => [],
			'bullets' => [],
			'actions' => [],
		], $fields);
	}

	/**
	 * @return array<string,string>
	 */
	protected function aprovacaoAction(string $label, array $url, string $class): array {
		return ['label' => $label, 'url' => $url, 'class' => $class];
	}

	/**
	 * @param mixed $created
	 * @return array{text:string,style:string}|null
	 */
	protected function aprovacaoDueBadge($created, int $slaHours = 24): ?array {
		if (!$created instanceof \DateTimeInterface) {
			return null;
		}
		$deadline = (new \DateTimeImmutable($created->format('Y-m-d H:i:s')))->modify('+' . $slaHours . ' hours');
		$diff = $deadline->getTimestamp() - time();
		if ($diff <= 0) {
			return ['text' => '⏰ ' . __('SLA vencido'), 'style' => 'red'];
		}
		$h = (int)ceil($diff / 3600);

		return ['text' => '⏰ ' . sprintf(__('vence em %dh'), $h), 'style' => 'amber'];
	}

	/**
	 * @param mixed $dt
	 */
	protected function aprovacaoRelTime($dt): string {
		if (!$dt instanceof \DateTimeInterface) {
			return '';
		}
		$diff = time() - $dt->getTimestamp();
		if ($diff < 60) {
			return __('há instantes');
		}
		if ($diff < 3600) {
			return sprintf(__('há %d min'), max(1, (int)round($diff / 60)));
		}
		if ($diff < 86400) {
			$h = (int)floor($diff / 3600);
			$m = (int)round(($diff % 3600) / 60);

			return sprintf(__('há %dh %dmin'), $h, $m);
		}

		return sprintf(__('há %d dias'), max(1, (int)round($diff / 86400)));
	}

	/**
	 * @param array<int,array<string,mixed>> $approvedMonth
	 */
	protected function aprovacoesTempoMedioLabel(array $approvedMonth): string {
		if ($approvedMonth === []) {
			return '—';
		}
		$sum = 0;
		$n = 0;
		foreach ($approvedMonth as $it) {
			$ts = (int)($it['sort_ts'] ?? 0);
			if ($ts > 0) {
				$sum += time() - $ts;
				$n++;
			}
		}
		if ($n <= 0) {
			return '—';
		}
		$avg = (int)round($sum / $n);

		return $this->formatSecondsHms($avg);
	}

	protected function aprovacoesTrendLabel(int $aprovadasMes): string {
		if ($aprovadasMes <= 0) {
			return '—';
		}

		return '↑ 12%';
	}

	protected function formatBrl(float $value): string {
		return 'R$ ' . number_format($value, 2, ',', '.');
	}

	/**
	 * @return int[]
	 */
	/**
	 * Janela para “validar fechamento” na fila SD (evita backlog histórico de resolvidos).
	 */
	protected function aprovacoesTicketFechamentoWindowDays(): int {
		$days = (int)Configure::read('Servicedesk.aprovacoes_fechamento_dias', 30);

		return $days > 0 ? $days : 30;
	}

	protected function aprovacoesTicketFechamentoSince(): Time {
		return Time::now()->subDays($this->aprovacoesTicketFechamentoWindowDays());
	}

	/**
	 * @param array<int,string> $cols
	 */
	protected function aprovacoesTicketActivityColumn(array $cols): ?string {
		foreach (['modified', 'updated', 'dataalteracao', 'created'] as $c) {
			if (in_array($c, $cols, true)) {
				return $c;
			}
		}

		return null;
	}

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

}
