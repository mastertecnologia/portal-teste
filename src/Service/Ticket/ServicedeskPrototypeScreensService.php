<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use App\Model\Table\TicketsTable;
use App\Utility\Ticket\TicketPriorityKpi;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

/**
 * Payloads das telas do protótipo SD (dados reais, somente leitura).
 */
class ServicedeskPrototypeScreensService {

	/** @var callable(Query):void */
	private $applyAbac;

	/** @var ServicedeskPrototypeDataService */
	private $core;

	/** @param callable(Query):void $applyAbac */
	public function __construct(callable $applyAbac) {
		$this->applyAbac = $applyAbac;
		$this->core = new ServicedeskPrototypeDataService($applyAbac);
	}

	/**
	 * @param array<string,mixed> $ctx tickets, idempresa, userId, userName, query
	 * @return array<string,mixed>
	 */
	public function build(string $page, array $ctx): array {
		switch ($page) {
			case 'meus':
				return $this->withRefPage($this->screenMeus($ctx), 'meus');
			case 'grupo':
				return $this->withRefPage($this->screenGrupo($ctx), 'grupo');
			case 'aprovacoes':
				return $this->withRefPage($this->screenAprovacoes($ctx), 'aprovacoes');
			case 'cmdb':
				return $this->withRefPage($this->screenCmdb($ctx), 'cmdb');
			case 'problemas':
				return $this->withRefPage($this->screenProblemas($ctx), 'problemas');
			case 'mudancas':
				return $this->withRefPage($this->screenMudancas($ctx), 'mudancas');
			case 'contratos':
				return $this->withRefPage($this->screenContratos($ctx), 'contratos');
			case 'fat':
				return $this->withRefPage($this->screenFaturamento($ctx), 'fat');
			case 'kb':
				return $this->withRefPage($this->screenKb($ctx), 'kb');
			case 'portal':
				return $this->withRefPage($this->screenPortal($ctx), 'portal');
			case 'calendar':
				return $this->withRefPage($this->screenCalendar($ctx), 'calendar');
			case 'csat':
				return $this->withRefPage($this->screenCsat($ctx), 'csat');
			case 'relatorios':
				return $this->withRefPage($this->screenRelatorios($ctx), 'relatorios');
			case 'config':
				return $this->withRefPage($this->screenConfig($ctx), 'config');
			case 'perm':
				return $this->withRefPage($this->screenPerm($ctx), 'perm');
			case 'integracoes':
				return $this->withRefPage($this->screenIntegracoes($ctx), 'integracoes');
			case 'templates':
				return $this->withRefPage($this->screenTemplates($ctx), 'templates');
			case 'portal-novo':
				return $this->withRefPage($this->screenPortalNovo($ctx), 'portal_novo');
			case 'detalhe-kb':
				return $this->withRefPage($this->screenDetalheKb($ctx), 'detalhe-kb');
			case 'detalhe-fatura':
				return $this->withRefPage($this->screenDetalheFatura($ctx), 'detalhe-fatura');
			case 'automacoes-editor':
				return $this->withRefPage($this->screenAutomacoesEditor($ctx), 'automacoes-editor');
			default:
				return [
					'title' => $page,
					'subtitle' => '',
					'kpis' => [],
					'rows' => [],
					'items' => [],
					'empty' => __('Tela não configurada.'),
				];
		}
	}

	/**
	 * Contagens para badges do menu.
	 *
	 * @return array<string,int>
	 */
	/**
	 * @param array<string,mixed> $screen
	 * @return array<string,mixed>
	 */
	protected function withRefPage(array $screen, string $refPage): array {
		$screen['ref_page'] = $refPage;

		return $screen;
	}

	public function navBadges(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];

		return [
			'aprovacoes' => $this->core->countAprovacoesPendentes($tickets, $idempresa),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenMeus(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$userId = (int)$ctx['userId'];
		$userName = (string)($ctx['userName'] ?? '');
		/** @var UsersTable $users */
		$users = TableRegistry::getTableLocator()->get('Users');

		$metrics = new ServicedeskExecutiveMetricsService($this->applyAbac);
		$extras = $metrics->buildMeusExtras($tickets, $users, $idempresa, $userId, $userName);
		$tabCounts = (array)($extras['tabCounts'] ?? []);
		$tabRows = (array)($extras['tabRows'] ?? []);
		$slaViolIds = (array)($extras['sla_viol_ids'] ?? []);
		$nearSla = (int)($extras['near_sla'] ?? 0);
		$nivel = (string)($extras['nivel_label'] ?? '');
		$horasMes = $extras['horas_mes'] ?? null;
		$horasFat = $extras['horas_faturaveis'] ?? null;
		$csatUser = $extras['csat_user'] ?? null;

		$ativos = (int)($tabCounts['ativos'] ?? 0);
		$slaViol = count($slaViolIds);
		$resolvidosMes = 0;
		$cols = $tickets->getSchema()->columns();
		$tecField = in_array('idtecnico_responsavel', $cols, true) ? 'idtecnico_responsavel' : null;
		if ($tecField === null && in_array('owner_id', $cols, true)) {
			$tecField = 'owner_id';
		}
		$resolvidosMesAnt = 0;
		if ($tecField !== null && in_array('data_resolucao', $cols, true)) {
			$m0 = Time::now()->startOfMonth()->format('Y-m-d H:i:s');
			$m1 = Time::now()->endOfMonth()->format('Y-m-d H:i:s');
			$qr = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.' . $tecField => $userId,
				'Tickets.data_resolucao >=' => $m0,
				'Tickets.data_resolucao <=' => $m1,
			]);
			($this->applyAbac)($qr);
			$resolvidosMes = $qr->count();

			$p0 = Time::now()->subMonths(1)->startOfMonth()->format('Y-m-d H:i:s');
			$p1 = Time::now()->subMonths(1)->endOfMonth()->format('Y-m-d H:i:s');
			$qp = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.' . $tecField => $userId,
				'Tickets.data_resolucao >=' => $p0,
				'Tickets.data_resolucao <=' => $p1,
			]);
			($this->applyAbac)($qp);
			$resolvidosMesAnt = $qp->count();
		}

		$resolvidosHint = '';
		if ($resolvidosMesAnt > 0) {
			$pct = (int)round(100 * ($resolvidosMes - $resolvidosMesAnt) / $resolvidosMesAnt);
			if ($pct > 0) {
				$resolvidosHint = sprintf('↑ %d%% %s', $pct, __('vs anterior'));
			} elseif ($pct < 0) {
				$resolvidosHint = sprintf('↓ %d%% %s', abs($pct), __('vs anterior'));
			} else {
				$resolvidosHint = __('igual ao mês anterior');
			}
		} elseif ($resolvidosMes > 0) {
			$resolvidosHint = __('↑ vs anterior');
		}

		$tabKeys = ['ativos', 'aguarda', 'resolvidos_hoje', 'observados', 'favoritos'];
		$activeTab = trim((string)($ctx['query']['tab'] ?? 'ativos'));
		if (!in_array($activeTab, $tabKeys, true)) {
			$activeTab = 'ativos';
		}

		$csatHint = __('90 dias');
		$csatHintColor = 'var(--teal-dark)';
		if ($csatUser !== null && $tecField !== null) {
			$sinceCsat = Time::now()->subDays(90)->format('Y-m-d H:i:s');
			$teamCsat = [];
			try {
				$qu = TableRegistry::getTableLocator()->get('QueuesUsers')->find()
					->select(['QueuesUsers.user_id'])
					->contain(['Queues'])
					->where(['Queues.idempresa' => $idempresa])
					->group(['QueuesUsers.user_id'])
					->enableHydration(false)
					->toArray();
				foreach ($qu as $row) {
					$uid = (int)($row['user_id'] ?? 0);
					if ($uid <= 0) {
						continue;
					}
					$avg = $this->core->tecnicoCsatScore($tickets, $idempresa, $tecField, $uid, $sinceCsat, $cols);
					if ($avg !== null) {
						$teamCsat[$uid] = $avg;
					}
				}
			} catch (\Throwable $e) {
				$teamCsat = [];
			}
			if ($teamCsat !== [] && ($teamCsat[$userId] ?? null) === max($teamCsat)) {
				$csatHint = __('topo do ranking');
			}
		}

		$slaHint = $slaViolIds !== [] ? '#' . implode(' #', array_slice($slaViolIds, 0, 4)) : '';
		$subtitle = $userName !== '' ? sprintf(__('Visão pessoal · %s'), $userName) : __('Atribuídos a você');
		if ($nivel !== '') {
			$subtitle .= ' · ' . $nivel;
		}

		return [
			'title' => __('🎯 Meus tickets'),
			'subtitle' => $subtitle,
			'kpis' => [
				['lbl' => __('Atribuídos a mim'), 'val' => (string)$ativos, 'hint' => __('ativos agora'), 'border' => 'var(--teal)'],
				['lbl' => __('SLA estourado'), 'val' => (string)$slaViol, 'hint' => $slaHint, 'alert' => $slaViol > 0, 'bg' => '#F8D8DA', 'border' => 'var(--red)', 'val_color' => '#7A1822'],
				['lbl' => __('Próx. limite (4h)'), 'val' => (string)$nearSla, 'hint' => __('prioridade'), 'bg' => '#FAEEDA', 'border' => 'var(--amber)', 'val_color' => '#8A4D02'],
				['lbl' => __('Resolvidos mês'), 'val' => (string)$resolvidosMes, 'hint' => $resolvidosHint, 'hint_color' => 'var(--teal-dark)', 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
				['lbl' => __('Meu CSAT'), 'val' => $csatUser !== null ? '⭐ ' . number_format((float)$csatUser, 1, ',', '.') : '—', 'hint' => $csatHint, 'hint_color' => $csatHintColor, 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
				['lbl' => __('Horas mês'), 'val' => $horasMes !== null ? number_format((float)$horasMes, 0, ',', '.') . 'h' : '—', 'hint' => $horasFat !== null ? number_format((float)$horasFat, 0, ',', '.') . 'h ' . __('faturáveis') : __('apontamentos'), 'border' => 'var(--teal-mid)'],
			],
			'rows' => (array)($tabRows[$activeTab] ?? []),
			'tab_counts' => $tabCounts,
			'tab_rows' => $tabRows,
			'active_tab' => $activeTab,
			'notificacoes' => (array)($extras['notificacoes'] ?? []),
			'compromissos' => (array)($extras['compromissos'] ?? []),
			'items' => [],
			'mode' => 'tickets',
			'empty' => __('Nenhum ticket atribuído a você no escopo atual.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenGrupo(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$userId = (int)$ctx['userId'];
		$queueId = isset($ctx['query']['queue_id']) && $ctx['query']['queue_id'] !== ''
			? (int)$ctx['query']['queue_id'] : null;

		$queues = [];
		$queueIds = [];
		try {
			$qt = TableRegistry::getTableLocator()->get('Queues');
			$qlist = $qt->find('list', ['keyField' => 'id', 'valueField' => 'name'])
				->where(['idempresa' => $idempresa])
				->order(['name' => 'ASC'])
				->toArray();
			$queues = $qlist;
			if ($queueId !== null && isset($qlist[$queueId])) {
				$queueIds = [$queueId];
			} else {
				try {
					$qu = TableRegistry::getTableLocator()->get('QueuesUsers');
					$linked = $qu->find()
						->select(['queue_id'])
						->where(['user_id' => $userId])
						->extract('queue_id')
						->toList();
					$queueIds = array_values(array_filter(array_map('intval', $linked)));
				} catch (\Throwable $e) {
					$queueIds = [];
				}
				if ($queueIds === []) {
					$queueIds = array_keys($qlist);
				}
			}
		} catch (\Throwable $e) {
			$queues = [];
		}

		$cols = $tickets->getSchema()->columns();
		$closed = $this->closedSituacoes();
		$where = ['Tickets.idempresa' => $idempresa];
		if ($closed !== []) {
			$where['Tickets.situacao NOT IN'] = $closed;
		}
		if ($queueIds !== [] && in_array('queue_id', $cols, true)) {
			$where['Tickets.queue_id IN'] = $queueIds;
		}

		$q = $tickets->find()->where($where);
		($this->applyAbac)($q);
		$total = $q->count();

		$semTec = 0;
		if (in_array('idtecnico_responsavel', $cols, true)) {
			$qs = $tickets->find()->where($where + [
				'OR' => [
					['idtecnico_responsavel IS' => null],
					['idtecnico_responsavel' => 0],
				],
			]);
			($this->applyAbac)($qs);
			$semTec = $qs->count();
		}

		$qName = $queueId !== null && isset($queues[$queueId]) ? (string)$queues[$queueId] : __('Todas as filas visíveis');
		$grupo = $this->core->buildGrupoPayload($tickets, $idempresa, $userId, $queueIds, $qName);

		return [
			'title' => sprintf(__('👥 Tickets do meu grupo · %s'), $grupo['queue_name'] ?? $qName),
			'subtitle' => __('Visão de fila do grupo · você pode pegar (claim) ou re-atribuir tickets'),
			'grupo' => $grupo,
			'kpis' => [
				['lbl' => __('Abertos no grupo'), 'val' => (string)($grupo['stats']['total'] ?? $total), 'hint' => ''],
				['lbl' => __('Sem técnico'), 'val' => (string)($grupo['stats']['sem_tec'] ?? $semTec), 'hint' => __('disponíveis para claim')],
			],
			'rows' => (array)($grupo['rows'] ?? []),
			'items' => [],
			'mode' => 'tickets',
			'queues' => $queues,
			'queue_id' => $queueId,
			'empty' => __('Nenhum ticket aberto nesta fila.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenAprovacoes(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$aprov = $this->core->buildAprovacoesPayload($tickets, $idempresa, $query);

		return [
			'title' => '📝 ' . __('Fila de aprovações'),
			'subtitle' => __('Estornos · escalonamentos · mudanças · descontos · pendentes do gerente'),
			'aprovacoes' => $aprov,
			'kpis' => [],
			'rows' => [],
			'items' => [],
			'mode' => 'aprovacoes',
			'links' => [
				['label' => '← ' . __('Voltar'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'fila']],
				['label' => '📊 ' . __('Relatório aprovações'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'relatorios']],
			],
			'empty' => (string)($aprov['empty'] ?? ''),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenCmdb(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$cmdb = $this->core->buildCmdbPayload($tickets, $idempresa, $query);
		$total = (int)($cmdb['total'] ?? 0);

		return [
			'title' => __('CMDB · Configuration Items'),
			'subtitle' => sprintf(__('Inventário de ativos e relacionamentos · %d CIs cadastrados · base ITIL v4'), $total),
			'cmdb' => $cmdb,
			'kpis' => (array)($cmdb['kpis'] ?? []),
			'rows' => (array)($cmdb['rows'] ?? []),
			'items' => (array)($cmdb['rows'] ?? []),
			'mode' => 'cmdb',
			'empty' => __('Nenhum ativo encontrado.'),
			'links' => [
				['label' => __('Módulo Ativos'), 'url' => ['controller' => 'Ativos', 'action' => 'index']],
			],
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenProblemas(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$prob = $this->core->buildProblemasPayload($tickets, $idempresa);
		$active = (int)($prob['active_count'] ?? 0);

		return [
			'title' => __('Gestão de Problemas'),
			'subtitle' => __('Raiz causa de incidentes recorrentes · análise RCA · %d problemas ativos', $active),
			'problemas' => $prob,
			'kpis' => (array)($prob['kpis'] ?? []),
			'rows' => (array)($prob['rows'] ?? []),
			'items' => [],
			'mode' => 'problemas',
			'empty' => __('Nenhum cluster recorrente nos últimos 90 dias.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenMudancas(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$mud = $this->core->buildMudancasPayload($tickets, $idempresa);

		return [
			'title' => __('Gestão de Mudanças (Change Management)'),
			'subtitle' => __('CAB · janelas de manutenção · análise de risco · rollback plans'),
			'mudancas' => $mud,
			'kpis' => (array)($mud['kpis'] ?? []),
			'rows' => [],
			'items' => [],
			'mode' => 'mudancas',
			'empty' => __('Nenhuma mudança programada.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenContratos(array $ctx): array {
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$contr = $this->core->buildContratosPayload($idempresa, $query);

		return [
			'title' => __('Contratos & SLA por cliente'),
			'subtitle' => __('Gestão de contratos · horas mensais · valor recorrente · renovações'),
			'contratos' => $contr,
			'kpis' => (array)($contr['kpis'] ?? []),
			'rows' => (array)($contr['rows'] ?? []),
			'items' => [],
			'mode' => 'contratos',
			'empty' => __('Nenhum contrato encontrado.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenFaturamento(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$fat = $this->core->buildFatPayload($tickets, $idempresa);

		return [
			'title' => __('Faturamento de tickets'),
			'subtitle' => __('Horas apontadas · serviços extras · convertidos em faturas para o financeiro'),
			'fat' => $fat,
			'kpis' => (array)($fat['kpis'] ?? []),
			'rows' => (array)($fat['rows'] ?? []),
			'items' => [],
			'mode' => 'tickets',
			'empty' => __('Nenhum ticket resolvido pendente de faturamento.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenKb(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$kb = $this->core->buildKbPreview($tickets, $idempresa);
		$stats = (array)($kb['stats'] ?? []);
		$total = (int)($stats['total_publicados'] ?? count((array)($kb['articles'] ?? [])));
		$ticketsMes = (int)($kb['tickets_mes'] ?? 0);
		$aplicados = (int)($stats['aplicados_tickets'] ?? $ticketsMes);
		if ($ticketsMes > 0) {
			$aplicados = $ticketsMes;
		}

		return [
			'title' => '📚 ' . __('Base de conhecimento'),
			'subtitle' => sprintf(
				__('%d artigos · público + interno · usados em %d tickets este mês'),
				$total,
				$aplicados
			),
			'kb' => $kb,
			'kpis' => [],
			'rows' => [],
			'items' => [],
			'mode' => 'kb',
			'links' => [
				['label' => '← ' . __('Service Desk'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'fila']],
				['label' => '+ ' . __('Novo artigo'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'detalhe-kb'], 'class' => 'btn btn-primary btn-sm'],
			],
			'empty' => '',
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenPortal(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$userName = trim((string)($ctx['userName'] ?? ''));
		$portal = $this->core->buildPortalPreview($tickets, $idempresa, $userName);
		$cn = (string)($portal['banner_cliente'] ?? $portal['cliente_nome'] ?? __('Cliente'));

		return [
			'title' => __('Portal do cliente'),
			'subtitle' => '',
			'portal' => $portal,
			'kpis' => [],
			'rows' => [],
			'items' => [],
			'mode' => 'portal',
			'links' => [
				['label' => __('Service Desk (cliente)'), 'url' => ['controller' => 'Servicedesk', 'action' => 'index']],
				['label' => __('Abrir chamado (equipa)'), 'url' => ['controller' => 'Servicedesk', 'action' => 'add']],
			],
			'empty' => '',
			'portal_hero' => sprintf(__('Olá! Preview do portal · %s'), $cn),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenPortalNovo(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$query = array_merge((array)($ctx['query'] ?? []), ['userName' => (string)($ctx['userName'] ?? '')]);
		$novo = $this->core->buildPortalNovoPayload($tickets, $idempresa, $query);

		return [
			'title' => __('+ Abrir novo chamado'),
			'subtitle' => __('Descreva sua necessidade · resposta em até 2h conforme SLA do seu contrato'),
			'portal_novo' => $novo,
			'kpis' => [],
			'rows' => [],
			'items' => [],
			'mode' => 'portal_novo',
			'links' => [
				['label' => __('Abrir chamado (equipa)'), 'url' => ['controller' => 'Servicedesk', 'action' => 'add']],
			],
			'empty' => '',
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenCalendar(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$plantao = $this->core->buildPlantaoPayload($tickets, $idempresa, $query);
		$hint = '';
		if (empty($plantao['has_visitas'])) {
			$hint = __('Sem compromissos na agenda no período — escala sugerida pelas filas e técnicos cadastrados.');
		}

		return [
			'title' => '📅 ' . __('Plantões & Disponibilidade'),
			'subtitle' => __('Escala de plantão · disponibilidade da equipe · agendamentos · cobertura 24/7'),
			'plantao' => $plantao,
			'kpis' => [],
			'rows' => [],
			'items' => [],
			'mode' => 'plantao',
			'links' => [
				['label' => '← ' . __('Voltar'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'fila']],
				[
					'label' => '+ ' . __('Adicionar plantão'),
					'url' => ['controller' => 'Visitas', 'action' => 'calendario'],
					'class' => 'btn btn-primary btn-sm',
				],
			],
			'empty' => $hint,
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenCsat(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$csat = $this->core->buildCsatPayload($tickets, $idempresa, $query);
		$total = (int)($csat['total_respostas'] ?? 0);
		$taxa = (int)($csat['taxa_resposta_pct'] ?? 0);

		return [
			'title' => __('CSAT & NPS · Satisfação'),
			'subtitle' => $total > 0
				? sprintf(__('%d respostas · taxa de resposta %d%% · últimos %d dias'), $total, $taxa, (int)($csat['period_days'] ?? 30))
				: __('Aguardando primeiras respostas (envie o link CSAT após fechar o ticket)'),
			'csat' => $csat,
			'kpis' => [],
			'rows' => [],
			'items' => [],
			'mode' => 'csat',
			'links' => [
				['label' => __('Histórico completo'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'csatHistorico']],
				['label' => __('Exportar CSV'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'csatExportCsv']],
				['label' => __('Painel operacional'), 'url' => ['controller' => 'Tickets', 'action' => 'operacional']],
			],
			'empty' => __('Nenhuma resposta CSAT no período.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenRelatorios(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$metrics = new ServicedeskExecutiveMetricsService($this->applyAbac);

		return [
			'title' => __('Relatórios & Métricas'),
			'subtitle' => __('KPIs operacionais · SLA · produtividade · satisfação CSAT'),
			'layout' => 'relatorios',
			'kpis' => $metrics->buildRelatoriosKpis($idempresa, $tickets),
			'rows' => [],
			'items' => [],
			'mode' => 'info',
			'links' => [
				['label' => __('Relatório SLA completo'), 'url' => '/servicedesk/sla-relatorio'],
				['label' => __('Painel operacional React'), 'url' => ['controller' => 'Servicedesk', 'action' => 'operacional']],
			],
			'empty' => '',
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenConfig(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$config = $this->core->buildConfigPayload($tickets, $idempresa);
		$tab = trim((string)($query['tab'] ?? 'sla'));
		$validTabs = ['sla', 'filas', 'auto', 'templ', 'horario', 'sat'];
		if (!in_array($tab, $validTabs, true)) {
			$tab = 'sla';
		}
		$items = [];
		try {
			if ($this->tableExists('workflow_states')) {
				$ws = TableRegistry::getTableLocator()->get('WorkflowStates');
				foreach ($ws->find()->order(['id' => 'ASC'])->all() as $s) {
					$items[] = [
						'id' => (int)$s->get('id'),
						'col1' => (string)$s->get('nome'),
						'col2' => (string)$s->get('codigo'),
						'col3' => !empty($s->get('is_inicial')) ? 'Sim' : 'Não',
						'col4' => !empty($s->get('is_final')) ? 'Final' : 'Ativo',
						'link' => ['controller' => 'Servicedesk', 'action' => 'workflowSlaAdmin'],
					];
				}
			}
		} catch (\Throwable $e) {
		}

		return [
			'title' => __('SLA & Configurações'),
			'subtitle' => __('Políticas de SLA · filas · automações · templates'),
			'config' => $config,
			'active_tab' => $tab,
			'kpis' => [
				['lbl' => __('Políticas SLA'), 'val' => (string)count((array)($config['sla_policies'] ?? [])), 'hint' => __('ativas'), 'border' => 'var(--teal)'],
				['lbl' => __('Filas'), 'val' => (string)count((array)($config['queues'] ?? [])), 'hint' => __('atendimento'), 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
				['lbl' => __('Automações'), 'val' => (string)count((array)($config['automacoes'] ?? [])), 'hint' => __('regras'), 'border' => '#6B5B95', 'val_color' => '#3D2D63'],
				['lbl' => __('Templates'), 'val' => (string)($config['templates_count'] ?? 8), 'hint' => __('ativos'), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
			],
			'rows' => [],
			'items' => $items,
			'mode' => 'info',
			'links' => [
				['label' => __('Admin Workflow SLA'), 'url' => ['controller' => 'Servicedesk', 'action' => 'workflowSlaAdmin']],
			],
			'empty' => '',
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenPerm(array $ctx): array {
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$perm = $this->core->buildPermPayload($idempresa, $query);
		$nUsers = count((array)($perm['usuarios'] ?? []));

		return [
			'title' => __('Permissões & Usuários'),
			'subtitle' => __('Controle granular de acesso · perfis · grupos · auditoria LGPD'),
			'perm' => $perm,
			'kpis' => [
				['lbl' => __('Usuários'), 'val' => (string)$nUsers, 'hint' => __('filtrados'), 'border' => 'var(--teal)'],
				['lbl' => __('Perfis'), 'val' => (string)($perm['roles_count'] ?? 0), 'hint' => __('RBAC'), 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
				['lbl' => __('Grupos'), 'val' => (string)($perm['groups_count'] ?? 0), 'hint' => __('filas'), 'border' => '#6B5B95', 'val_color' => '#3D2D63'],
				['lbl' => __('Eventos log · 30d'), 'val' => (string)($perm['log_eventos_30d'] ?? 0), 'hint' => __('auditoria'), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
			],
			'rows' => [],
			'items' => [],
			'mode' => 'info',
			'empty' => '',
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenIntegracoes(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$integ = $this->core->buildIntegracoesPayload($tickets, $idempresa);

		return [
			'title' => __('Integrações'),
			'subtitle' => __('Conecte o Service Desk com e-mail, mensageria, telefonia, monitoramento e mais'),
			'integracoes' => $integ,
			'kpis' => (array)($integ['kpis'] ?? []),
			'rows' => [],
			'items' => [],
			'mode' => 'info',
			'empty' => '',
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenTemplates(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$tpl = $this->core->buildTemplatesPayload($tickets, $idempresa, $query);
		$stats = (array)($tpl['stats'] ?? []);

		return [
			'title' => __('Templates & Formulários'),
			'subtitle' => __('Modelos de resposta · formulários de categoria · variáveis dinâmicas'),
			'templates' => $tpl,
			'kpis' => [
				['lbl' => __('Templates ativos'), 'val' => (string)($stats['ativos'] ?? 0), 'border' => 'var(--teal)'],
				['lbl' => __('Mais usado'), 'val' => (string)($stats['mais_usado'] ?? '—'), 'hint' => ((int)($stats['mais_usado_count'] ?? 0)) . ' ' . __('usos/mês'), 'border' => 'var(--blue)', 'val_color' => '#0C447C', 'val_size' => '18px'],
				['lbl' => __('Economia tempo'), 'val' => (string)($stats['economia_tempo'] ?? '2.4h/dia'), 'hint' => __('vs digitar manual'), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
				['lbl' => __('Formulários ativos'), 'val' => (string)($stats['formularios'] ?? 0), 'border' => '#6B5B95', 'val_color' => '#3D2D63'],
			],
			'rows' => [],
			'items' => [],
			'mode' => 'info',
			'empty' => '',
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenDetalheKb(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$code = trim((string)($query['code'] ?? 'KB-042'));
		$article = $this->core->buildDetalheKbPayload($tickets, $idempresa, $code);

		return [
			'title' => (string)($article['titulo'] ?? __('Artigo KB')),
			'subtitle' => '',
			'kb_article' => $article,
			'kpis' => [],
			'rows' => [],
			'items' => [],
			'mode' => 'info',
			'empty' => '',
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenDetalheFatura(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$ticketId = isset($query['ticket_id']) && $query['ticket_id'] !== '' ? (int)$query['ticket_id'] : null;
		$fatura = $this->core->buildDetalheFaturaPayload($tickets, $idempresa, $ticketId);

		return [
			'title' => (string)($fatura['numero'] ?? __('Fatura')),
			'subtitle' => '',
			'fatura' => $fatura,
			'kpis' => [],
			'rows' => [],
			'items' => [],
			'mode' => 'info',
			'empty' => '',
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenAutomacoesEditor(array $ctx): array {
		/** @var TicketsTable $tickets */
		$tickets = $ctx['tickets'];
		$idempresa = (int)$ctx['idempresa'];
		$query = (array)($ctx['query'] ?? []);
		$ruleKey = trim((string)($query['rule'] ?? 'roteamento'));
		$editor = $this->core->buildAutomacoesEditorPayload($tickets, $idempresa, $ruleKey);

		return [
			'title' => __('Editor de Automações'),
			'subtitle' => (string)($editor['subtitle'] ?? ''),
			'automacoes_editor' => $editor,
			'kpis' => [],
			'rows' => [],
			'items' => [],
			'mode' => 'info',
			'empty' => '',
		];
	}

	/**
	 * @param array<string,mixed> $extraWhere
	 * @return array<int,array<string,mixed>>
	 */
	protected function fetchTicketRows(
		TicketsTable $tickets,
		int $idempresa,
		array $extraWhere,
		int $limit
	): array {
		$where = array_merge(['Tickets.idempresa' => $idempresa], $extraWhere);
		$orderCol = $this->orderColumn($tickets);
		$q = $tickets->find()
			->contain(['Clientes', 'users'])
			->where($where)
			->order([$orderCol => 'DESC'])
			->limit($limit);
		($this->applyAbac)($q);
		$out = [];
		foreach ($q->all() as $t) {
			$assuntoTxt = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
				? $tickets->resolveTicketAssuntoTextoPublic($t->get('assunto'))
				: (string)$t->get('assunto');
			$c = $t->cliente ?? $t->clientes ?? null;
			$clienteNome = '—';
			if ($c) {
				$clienteNome = (int)($c->get('tipo') ?? 0) === 2
					? (string)($c->get('razaosocial') ?? '')
					: (string)($c->get('nome') ?? '');
			}
			$out[] = [
				'id' => (int)$t->get('id'),
				'assunto' => $assuntoTxt,
				'cliente' => $clienteNome !== '' ? $clienteNome : '—',
				'situacao' => (int)($t->get('situacao') ?? 0),
				'situacao_label' => $this->core->situacaoLabel((int)($t->get('situacao') ?? 0)),
				'prioridade' => $t->get('prioridade'),
				'tecnico' => $this->core->resolveTicketTecnicoLabelPublic($tickets, $t),
				'sla_status' => $t->get('sla_status'),
			];
		}

		return $out;
	}

	protected function orderColumn(TicketsTable $tickets): string {
		$cols = $tickets->getSchema()->columns();
		if (in_array('modified', $cols, true)) {
			return 'Tickets.modified';
		}
		if (in_array('updated', $cols, true)) {
			return 'Tickets.updated';
		}
		if (in_array('created', $cols, true)) {
			return 'Tickets.created';
		}

		return 'Tickets.id';
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
}
