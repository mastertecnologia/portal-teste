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
				$portal = $this->screenPortal($ctx);
				$portal['title'] = __('+ Abrir novo chamado');
				$portal['subtitle'] = __('Descreva sua necessidade · resposta em até 2h conforme SLA do seu contrato');
				$portal['links'] = [
					['label' => __('Abrir chamado (equipa)'), 'url' => ['controller' => 'Servicedesk', 'action' => 'add']],
				];

				return $this->withRefPage($portal, 'portal_novo');
			case 'detalhe-kb':
				return $this->withRefPage($this->screenKb($ctx), 'kb');
			case 'detalhe-fatura':
				return $this->withRefPage($this->screenFaturamento($ctx), 'fat');
			case 'automacoes-editor':
				return $this->withRefPage($this->screenConfig($ctx), 'config');
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
		$cols = $tickets->getSchema()->columns();
		$closed = $this->closedSituacoes();

		$tecField = in_array('idtecnico_responsavel', $cols, true) ? 'idtecnico_responsavel' : null;
		if ($tecField === null && in_array('owner_id', $cols, true)) {
			$tecField = 'owner_id';
		}

		$ativos = 0;
		$slaViol = 0;
		$aguarda = 0;
		$resolvidosMes = 0;

		if ($tecField !== null && $closed !== []) {
			$base = [
				'Tickets.idempresa' => $idempresa,
				'Tickets.' . $tecField => $userId,
				'Tickets.situacao NOT IN' => $closed,
			];
			$q = $tickets->find()->where($base);
			($this->applyAbac)($q);
			$ativos = $q->count();

			if (in_array('sla_status', $cols, true)) {
				$qv = clone $q;
				$slaViol = $qv->where(['Tickets.sla_status' => 'violado'])->count();
			}
			if (defined('C_TicketSituacaoRespondido')) {
				$qa = $tickets->find()->where($base + ['Tickets.situacao' => (int)C_TicketSituacaoRespondido]);
				($this->applyAbac)($qa);
				$aguarda = $qa->count();
			}
		}

		if ($tecField !== null && defined('C_TicketSituacaoResolvido') && in_array('data_resolucao', $cols, true)) {
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
		}

		$rows = [];
		if ($tecField !== null) {
			$rows = $this->fetchTicketRows($tickets, $idempresa, [
				'Tickets.' . $tecField => $userId,
				'Tickets.situacao NOT IN' => $closed,
			], 50);
		}

		return [
			'title' => __('🎯 Meus tickets'),
			'subtitle' => $userName !== '' ? sprintf(__('Visão pessoal · %s'), $userName) : __('Atribuídos a você'),
			'kpis' => [
				['lbl' => __('Atribuídos a mim'), 'val' => (string)$ativos, 'hint' => __('ativos agora'), 'border' => 'var(--teal)'],
				['lbl' => __('SLA estourado'), 'val' => (string)$slaViol, 'hint' => '', 'alert' => $slaViol > 0, 'bg' => '#F8D8DA', 'border' => 'var(--red)', 'val_color' => '#7A1822'],
				['lbl' => __('Próx. limite (4h)'), 'val' => '—', 'hint' => __('prioridade'), 'bg' => '#FAEEDA', 'border' => 'var(--amber)', 'val_color' => '#8A4D02'],
				['lbl' => __('Resolvidos mês'), 'val' => (string)$resolvidosMes, 'hint' => '', 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
				['lbl' => __('Meu CSAT'), 'val' => '—', 'hint' => __('módulo CSAT'), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
				['lbl' => __('Horas mês'), 'val' => '—', 'hint' => __('apontamentos'), 'border' => 'var(--teal-mid)'],
			],
			'rows' => $rows,
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

		return [
			'title' => __('Meu grupo'),
			'subtitle' => sprintf(__('Fila: %s · %d tickets abertos'), $qName, $total),
			'kpis' => [
				['lbl' => __('Abertos no grupo'), 'val' => (string)$total, 'hint' => ''],
				['lbl' => __('Sem técnico'), 'val' => (string)$semTec, 'hint' => __('disponíveis para claim')],
			],
			'rows' => $this->fetchTicketRows($tickets, $idempresa, $where, 60),
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
		$idempresa = (int)$ctx['idempresa'];
		$items = [];
		$total = 0;
		$comIncidente = 0;
		$typeKpis = [];
		$tids = [];
		try {
			$assets = TableRegistry::getTableLocator()->get('Assets');
			$cols = $assets->getSchema()->columns();
			$where = ['Assets.idempresa' => $idempresa];
			if (in_array('inativo', $cols, true)) {
				$where['Assets.inativo'] = 0;
			}
			$q = $assets->find()
				->contain(['Clientes'])
				->where($where)
				->order(['Assets.id' => 'DESC'])
				->limit(100);
			$total = $assets->find()->where($where)->count();

			$ticketAssetIds = [];
			if ($this->tableExists('ticket_assets')) {
				$ta = TableRegistry::getTableLocator()->get('TicketAssets');
				$tCols = $ta->getSchema()->columns();
				if (in_array('asset_id', $tCols, true)) {
					/** @var TicketsTable $tickets */
					$tickets = $ctx['tickets'];
					$closed = $this->closedSituacoes();
					$tq = $tickets->find()->select(['Tickets.id'])->where(['Tickets.idempresa' => $idempresa]);
					if ($closed !== []) {
						$tq->where(['Tickets.situacao NOT IN' => $closed]);
					}
					($this->applyAbac)($tq);
					$tids = $tq->extract('id')->toList();
					if ($tids !== []) {
						$ticketAssetIds = $ta->find()
							->select(['asset_id'])
							->where(['ticket_id IN' => $tids])
							->group(['asset_id'])
							->extract('asset_id')
							->toList();
					}
				}
			}
			$comIncidente = count(array_unique(array_map('intval', $ticketAssetIds)));

			$ticketCountByAsset = [];
			if (!empty($tids) && isset($ta)) {
				$fCnt = $ta->find()->func()->count('*');
				foreach ($ta->find()
					->select(['asset_id', 'cnt' => $fCnt])
					->where(['ticket_id IN' => $tids])
					->group(['asset_id'])
					->enableHydration(false)
					->toArray() as $tar) {
					$aid = (int)($tar['asset_id'] ?? 0);
					if ($aid > 0) {
						$ticketCountByAsset[$aid] = (int)($tar['cnt'] ?? 0);
					}
				}
			}

			$byTipo = [];
			foreach ($q->all() as $a) {
				$c = $a->cliente ?? null;
				$cliente = '—';
				if ($c) {
					$cliente = (int)($c->get('tipo') ?? 0) === 2
						? (string)($c->get('razaosocial') ?? '')
						: (string)($c->get('nome') ?? '');
				}
				$tipo = trim((string)($a->get('tipo') ?? $a->get('categoria') ?? ''));
				if ($tipo === '') {
					$tipo = __('Outros');
				}
				$byTipo[$tipo] = (int)($byTipo[$tipo] ?? 0) + 1;
				$aid = (int)$a->get('id');
				$nome = trim((string)($a->get('descricao') ?? ''));
				$items[] = [
					'id' => $aid,
					'tag' => 'CI-' . str_pad((string)$aid, 4, '0', STR_PAD_LEFT),
					'nome' => $nome !== '' ? $nome : ('CI #' . $aid),
					'tipo' => $tipo,
					'cliente' => $cliente,
					'host' => (string)($a->get('hostname') ?? $a->get('identificador') ?? '—'),
					'tickets' => (int)($ticketCountByAsset[$aid] ?? 0),
					'link' => ['controller' => 'Ativos', 'action' => 'view', $aid],
				];
			}
			$typeKpis = [];
			arsort($byTipo);
			foreach (array_slice($byTipo, 0, 4, true) as $tipoNome => $cnt) {
				$typeKpis[] = ['lbl' => $tipoNome, 'val' => (string)$cnt, 'hint' => ''];
			}
		} catch (\Throwable $e) {
			return [
				'title' => __('CMDB · Ativos'),
				'subtitle' => __('Módulo de ativos indisponível'),
				'kpis' => [],
				'rows' => [],
				'items' => [],
				'mode' => 'items',
				'empty' => $e->getMessage(),
			];
		}

		return [
			'title' => __('CMDB · Ativos'),
			'subtitle' => sprintf(__('%d ativos cadastrados na empresa'), $total),
			'layout' => 'cmdb',
			'kpis' => array_merge([
				['lbl' => __('Total CIs'), 'val' => (string)$total, 'hint' => ''],
				['lbl' => __('Com ticket aberto'), 'val' => (string)$comIncidente, 'hint' => '', 'alert' => $comIncidente > 0],
			], $typeKpis),
			'rows' => [],
			'items' => $items,
			'mode' => 'items',
			'item_headers' => [__('Ativo'), __('Cliente'), __('Tipo'), __('Host/ID')],
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
		$items = [];
		$problemaCol = in_array('problema_id', $tickets->getSchema()->columns(), true)
			? 'problema_id'
			: (in_array('idproblema', $tickets->getSchema()->columns(), true) ? 'idproblema' : null);

		try {
			$prob = TableRegistry::getTableLocator()->get('Problemas');
			$probs = $prob->find()->order(['id' => 'ASC'])->limit(200)->toArray();
			foreach ($probs as $p) {
				$pid = (int)$p->get('id');
				$label = trim((string)($p->get('descricao') ?? $p->get('nome') ?? ('#' . $pid)));
				if ($label === '') {
					$label = 'Problema #' . $pid;
				}
				$cnt = 0;
				if ($problemaCol !== null) {
					$q = $tickets->find()->where([
						'Tickets.idempresa' => $idempresa,
						'Tickets.' . $problemaCol => $pid,
					]);
					($this->applyAbac)($q);
					$cnt = $q->count();
				}
				$items[] = [
					'id' => $pid,
					'col1' => $label,
					'col2' => (string)$cnt,
					'col3' => '—',
					'col4' => '—',
					'link' => ['controller' => 'Problemas', 'action' => 'index'],
				];
			}
		} catch (\Throwable $e) {
			$items = [];
		}

		return [
			'title' => __('Problemas'),
			'subtitle' => __('Catálogo de problemas / tipos vinculados a tickets'),
			'kpis' => [
				['lbl' => __('Registos'), 'val' => (string)count($items), 'hint' => __('tabela problemas')],
			],
			'rows' => [],
			'items' => $items,
			'mode' => 'items',
			'item_headers' => [__('Problema'), __('Tickets'), '', ''],
			'empty' => __('Nenhum problema cadastrado.'),
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
		$cols = $tickets->getSchema()->columns();
		$where = ['Tickets.idempresa' => $idempresa];
		$closed = $this->closedSituacoes();
		if ($closed !== []) {
			$where['Tickets.situacao NOT IN'] = $closed;
		}
		if (in_array('prioridade', $cols, true)) {
			$where = array_merge($where, TicketPriorityKpi::p1MatchOrConditions('Tickets.prioridade'));
		}
		$rows = $this->fetchTicketRows($tickets, $idempresa, $where, 40);

		return [
			'title' => __('Mudanças'),
			'subtitle' => __('Tickets P1/críticos em aberto (não há módulo CAB separado no portal)'),
			'kpis' => [
				['lbl' => __('Críticos abertos'), 'val' => (string)count($rows), 'hint' => __('proxy para mudanças')],
			],
			'rows' => $rows,
			'items' => [],
			'mode' => 'tickets',
			'empty' => __('Nenhum ticket crítico em aberto.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenContratos(array $ctx): array {
		$idempresa = (int)$ctx['idempresa'];
		$items = [];
		try {
			$cc = TableRegistry::getTableLocator()->get('Clicontratos');
			$where = [];
			if (in_array('idempresa', $cc->getSchema()->columns(), true)) {
				$where['Clicontratos.idempresa'] = $idempresa;
			}
			$rows = $cc->find()
				->contain(['Clientes'])
				->where($where)
				->order(['Clicontratos.id' => 'DESC'])
				->limit(80)
				->all();
			foreach ($rows as $r) {
				$cl = $r->cliente ?? null;
				$cn = $cl ? (string)($cl->get('razaosocial') ?? $cl->get('nome') ?? '') : '—';
				$items[] = [
					'id' => (int)$r->get('id'),
					'col1' => 'CL #' . (int)$r->get('id'),
					'col2' => $cn,
					'col3' => \Cake\Utility\Text::truncate((string)($r->get('descricao') ?? ''), 60, ['ellipsis' => '…']),
					'col4' => (string)($r->get('situacao') ?? '—'),
					'link' => ['controller' => 'Clientes', 'action' => 'index'],
				];
			}
		} catch (\Throwable $e) {
			$items = [];
		}

		$slaCount = 0;
		try {
			if ($this->tableExists('workflow_sla_policies')) {
				$slaCount = TableRegistry::getTableLocator()->get('WorkflowSlaPolicies')
					->find()
					->count();
			}
		} catch (\Throwable $e) {
		}

		return [
			'title' => __('Contratos SLA'),
			'subtitle' => __('Contratos de cliente (clicontratos) e políticas de workflow SLA'),
			'kpis' => [
				['lbl' => __('Contratos listados'), 'val' => (string)count($items), 'hint' => ''],
				['lbl' => __('Políticas SLA'), 'val' => (string)$slaCount, 'hint' => __('workflow_sla_policies')],
			],
			'rows' => [],
			'items' => $items,
			'mode' => 'items',
			'item_headers' => [__('Contrato'), __('Cliente'), __('Descrição'), __('Situação')],
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
		$cols = $tickets->getSchema()->columns();
		$where = ['Tickets.idempresa' => $idempresa];
		if (defined('C_TicketSituacaoResolvido')) {
			$where['Tickets.situacao'] = (int)C_TicketSituacaoResolvido;
		}
		$rows = $this->fetchTicketRows($tickets, $idempresa, $where, 50);
		$faturas = 0;
		try {
			if ($this->tableExists('faturas')) {
				$ft = TableRegistry::getTableLocator()->get('Faturas');
				$fw = [];
				if (in_array('idempresa', $ft->getSchema()->columns(), true)) {
					$fw['idempresa'] = $idempresa;
				}
				$faturas = $fw === [] ? $ft->find()->count() : $ft->find()->where($fw)->count();
			}
		} catch (\Throwable $e) {
		}

		return [
			'title' => __('Faturamento'),
			'subtitle' => __('Tickets resolvidos (candidatos a faturar) e volume de faturas no portal'),
			'kpis' => [
				['lbl' => __('Tickets resolvidos'), 'val' => (string)count($rows), 'hint' => __('a faturar')],
				['lbl' => __('Faturas (módulo)'), 'val' => (string)$faturas, 'hint' => ''],
			],
			'rows' => $rows,
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
		$fechados = 0;
		if (defined('C_TicketSituacaoFechado') && in_array('situacao', $tickets->getSchema()->columns(), true)) {
			$q = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.situacao' => (int)C_TicketSituacaoFechado,
			]);
			($this->applyAbac)($q);
			$fechados = $q->count();
		}

		$totalRespostas = 0;
		$csatSoma = 0;
		$promotores = 0;
		$detratores = 0;
		$neutros = 0;
		$respostasNps = 0;
		$ultimos = [];
		if ($this->tableExists('ticket_csat_responses')) {
			try {
				$tbl = TableRegistry::getTableLocator()->get('TicketCsatResponses');
				$rows = $tbl->find()
					->where(['TicketCsatResponses.idempresa' => $idempresa])
					->order(['TicketCsatResponses.responded_at' => 'DESC'])
					->limit(200)
					->all();
				foreach ($rows as $r) {
					$totalRespostas++;
					$csatSoma += (int)$r->get('csat_score');
					$nps = $r->get('nps_score');
					if ($nps !== null && $nps !== '') {
						$respostasNps++;
						$n = (int)$nps;
						if ($n >= 9) {
							$promotores++;
						} elseif ($n <= 6) {
							$detratores++;
						} else {
							$neutros++;
						}
					}
					if (count($ultimos) < 8) {
						$ultimos[] = [
							'ticket_id' => (int)$r->get('ticket_id'),
							'csat' => (int)$r->get('csat_score'),
							'nps' => $nps !== null ? (int)$nps : null,
							'comentario' => (string)($r->get('comentario') ?? ''),
							'data' => $r->get('responded_at'),
						];
					}
				}
			} catch (\Throwable $e) {
			}
		}

		$csatMedia = $totalRespostas > 0 ? round($csatSoma / $totalRespostas, 2) : null;
		$npsScore = $respostasNps > 0
			? round((($promotores - $detratores) / $respostasNps) * 100)
			: null;

		$kpis = [
			['lbl' => __('Tickets fechados'), 'val' => (string)$fechados, 'hint' => __('total empresa')],
			['lbl' => __('Respostas CSAT'), 'val' => (string)$totalRespostas, 'hint' => __('formulário pós-fechamento')],
			['lbl' => __('CSAT médio'), 'val' => $csatMedia !== null ? number_format($csatMedia, 2, ',', '.') . ' ⭐' : '—', 'hint' => __('escala 1-5')],
			['lbl' => __('NPS'), 'val' => $npsScore !== null ? (string)$npsScore : '—', 'hint' => sprintf(__('%d resp.'), $respostasNps)],
		];

		return [
			'title' => __('CSAT & NPS'),
			'subtitle' => $totalRespostas > 0
				? sprintf(__('%d respostas registradas'), $totalRespostas)
				: __('Aguardando primeiras respostas (envie o link CSAT após fechar o ticket)'),
			'kpis' => $kpis,
			'rows' => [],
			'items' => [],
			'mode' => 'info',
			'empty' => '',
			'csat_ultimos' => $ultimos,
			'csat_breakdown' => ['promotores' => $promotores, 'neutros' => $neutros, 'detratores' => $detratores, 'total_nps' => $respostasNps],
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
		$dash = new DashboardService($tickets);
		$snap = $dash->operationalSnapshot($idempresa);
		$kpis = (array)($snap['sla_operational_kpis'] ?? []);

		$violados = count((array)($snap['alertas_sla_violado'] ?? []));

		return [
			'title' => __('Relatórios & Métricas'),
			'subtitle' => __('Snapshot operacional · ' . Time::now()->format('d/m/Y H:i')),
			'layout' => 'relatorios',
			'kpis' => [
				['lbl' => __('Backlog'), 'val' => (string)($snap['backlog'] ?? 0), 'hint' => __('abertos')],
				['lbl' => __('Resolvidos hoje'), 'val' => (string)($snap['resolvidos_hoje'] ?? 0), 'hint' => ''],
				['lbl' => __('P1 abertos'), 'val' => (string)($snap['p1_abertos'] ?? 0), 'hint' => '', 'alert' => ((int)($snap['p1_abertos'] ?? 0)) > 0],
				['lbl' => __('Sem técnico'), 'val' => (string)($kpis['sem_tecnico'] ?? 0), 'hint' => ''],
				['lbl' => __('SLA violado'), 'val' => (string)$violados, 'hint' => '', 'alert' => $violados > 0],
				['lbl' => __('Aguarda cliente'), 'val' => (string)($kpis['aguardando_cliente'] ?? 0), 'hint' => ''],
			],
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
			'title' => __('SLA & Config'),
			'subtitle' => __('Estados de workflow cadastrados'),
			'kpis' => [
				['lbl' => __('Estados'), 'val' => (string)count($items), 'hint' => ''],
			],
			'rows' => [],
			'items' => $items,
			'mode' => 'items',
			'item_headers' => [__('Nome'), __('Código'), __('Inicial'), __('Tipo')],
			'links' => [
				['label' => __('Admin Workflow SLA'), 'url' => ['controller' => 'Servicedesk', 'action' => 'workflowSlaAdmin']],
			],
			'empty' => __('Nenhum estado de workflow.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenPerm(array $ctx): array {
		$items = [];
		$count = 0;
		try {
			if ($this->tableExists('rbac_permissions')) {
				$rp = TableRegistry::getTableLocator()->get('RbacPermissions');
				$rows = $rp->find()
					->where(['OR' => [
						['controller' => 'Servicedesk'],
						['controller' => 'Tickets'],
						['code LIKE' => 'servicedesk.%'],
					]])
					->order(['code' => 'ASC'])
					->limit(80)
					->all();
				foreach ($rows as $p) {
					$items[] = [
						'id' => (int)$p->get('id'),
						'col1' => (string)$p->get('code'),
						'col2' => (string)$p->get('name'),
						'col3' => (string)$p->get('controller'),
						'col4' => (string)$p->get('action'),
						'link' => ['controller' => 'Users', 'action' => 'index'],
					];
				}
				$count = count($items);
			}
		} catch (\Throwable $e) {
		}

		return [
			'title' => __('Permissões'),
			'subtitle' => __('Permissões RBAC relacionadas a Service Desk / Tickets'),
			'kpis' => [
				['lbl' => __('Permissões'), 'val' => (string)$count, 'hint' => ''],
			],
			'rows' => [],
			'items' => $items,
			'mode' => 'items',
			'item_headers' => [__('Código'), __('Nome'), __('Controller'), __('Action')],
			'empty' => __('RBAC não disponível ou sem permissões mapeadas.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenIntegracoes(array $ctx): array {
		$items = [];
		try {
			if ($this->tableExists('config')) {
				$cfg = TableRegistry::getTableLocator()->get('Config');
				$keys = ['email', 'smtp', 'ticket', 'servicedesk', 'erp', 'url'];
				foreach ($cfg->find()->limit(200)->all() as $row) {
					$nome = strtolower((string)($row->get('nome') ?? $row->get('chave') ?? ''));
					$match = false;
					foreach ($keys as $k) {
						if ($nome !== '' && strpos($nome, $k) !== false) {
							$match = true;
							break;
						}
					}
					if (!$match) {
						continue;
					}
					$val = (string)($row->get('valor') ?? $row->get('value') ?? '');
					if (strlen($val) > 60) {
						$val = substr($val, 0, 57) . '…';
					}
					$items[] = [
						'id' => (int)($row->get('id') ?? 0),
						'col1' => (string)($row->get('nome') ?? $row->get('chave') ?? ''),
						'col2' => $val !== '' ? $val : '—',
						'col3' => '—',
						'col4' => '—',
						'link' => null,
					];
					if (count($items) >= 30) {
						break;
					}
				}
			}
		} catch (\Throwable $e) {
		}

		return [
			'title' => __('Integrações'),
			'subtitle' => __('Parâmetros de configuração relacionados a e-mail, tickets e ERP'),
			'kpis' => [
				['lbl' => __('Parâmetros'), 'val' => (string)count($items), 'hint' => __('filtrados')],
			],
			'rows' => [],
			'items' => $items,
			'mode' => 'items',
			'item_headers' => [__('Chave'), __('Valor'), '', ''],
			'empty' => __('Nenhum parâmetro de integração encontrado em config.'),
		];
	}

	/**
	 * @param array<string,mixed> $ctx
	 * @return array<string,mixed>
	 */
	protected function screenTemplates(array $ctx): array {
		$items = [];
		try {
			if ($this->tableExists('ticketcomentarios')) {
				$tc = TableRegistry::getTableLocator()->get('Ticketcomentarios');
				$rows = $tc->find()
					->select(['comentario'])
					->order(['id' => 'DESC'])
					->limit(100)
					->all();
				$seen = [];
				foreach ($rows as $r) {
					$txt = trim(strip_tags((string)($r->get('comentario') ?? '')));
					if ($txt === '' || strlen($txt) < 20 || isset($seen[$txt])) {
						continue;
					}
					$seen[$txt] = true;
					$items[] = [
						'id' => count($items),
						'col1' => \Cake\Utility\Text::truncate($txt, 100, ['ellipsis' => '…']),
						'col2' => (string)strlen($txt) . ' chars',
						'col3' => '—',
						'col4' => '—',
						'link' => null,
					];
					if (count($items) >= 25) {
						break;
					}
				}
			}
		} catch (\Throwable $e) {
		}

		return [
			'title' => __('Templates de resposta'),
			'subtitle' => __('Trechos reais de comentários de tickets (amostra para reutilização)'),
			'kpis' => [
				['lbl' => __('Amostras'), 'val' => (string)count($items), 'hint' => ''],
			],
			'rows' => [],
			'items' => $items,
			'mode' => 'items',
			'item_headers' => [__('Texto'), __('Tamanho'), '', ''],
			'empty' => __('Sem comentários longos para sugerir como template.'),
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
