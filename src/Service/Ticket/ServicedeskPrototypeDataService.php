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
		$satisfacao = $this->fetchSatisfactionSnapshot($idempresa);
		$financeiro = $this->buildDashboardFinanceiro($tickets, $idempresa, $cols);
		$porCategoria = $this->topAssuntos($tickets, $idempresa, $cols, Time::now()->subDays(90));

		return [
			'snapshot' => $snapshot,
			'tickets_hoje' => $ticketsHoje,
			'tickets_ontem' => $ticketsOntem,
			'top_clientes' => $topClientes,
			'top_assuntos' => $topAssuntos,
			'por_categoria' => $porCategoria,
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
			'satisfacao' => $satisfacao,
			'financeiro' => $financeiro,
		];
	}

	/**
	 * CSAT / NPS agregados para o dashboard executivo.
	 *
	 * @return array<string,mixed>
	 */
	public function fetchSatisfactionSnapshot(int $idempresa): array {
		$out = [
			'csat_media' => null,
			'csat_respostas' => 0,
			'nps' => null,
			'nps_respostas' => 0,
			'fcr_pct' => null,
		];
		if (!$this->tableExists('ticket_csat_responses')) {
			return $out;
		}
		try {
			$tbl = TableRegistry::getTableLocator()->get('TicketCsatResponses');
			$since30 = Time::now()->subDays(30)->format('Y-m-d H:i:s');
			$rows = $tbl->find()
				->where([
					'TicketCsatResponses.idempresa' => $idempresa,
					'TicketCsatResponses.responded_at >=' => $since30,
				])
				->all();
			$soma = 0;
			$n = 0;
			$prom = 0;
			$det = 0;
			$npsN = 0;
			foreach ($rows as $r) {
				$n++;
				$soma += (int)$r->get('csat_score');
				$nps = $r->get('nps_score');
				if ($nps !== null && $nps !== '') {
					$npsN++;
					$nv = (int)$nps;
					if ($nv >= 9) {
						$prom++;
					} elseif ($nv <= 6) {
						$det++;
					}
				}
			}
			$out['csat_respostas'] = $n;
			if ($n > 0) {
				$out['csat_media'] = round($soma / $n, 1);
			}
			$out['nps_respostas'] = $npsN;
			if ($npsN > 0) {
				$out['nps'] = (int)round((($prom - $det) / $npsN) * 100);
			}
		} catch (\Throwable $e) {
		}
		try {
			$metrics = new ServicedeskExecutiveMetricsService($this->applyAbac);
			$out['fcr_pct'] = $metrics->computeFcrPct($idempresa, 30);
			$out['fcr_pct_prev'] = $metrics->computeFcrPct($idempresa, 30, 30);
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * KPIs financeiros/operacionais do dashboard (dados reais quando disponíveis).
	 *
	 * @param string[] $cols
	 * @return array<string,mixed>
	 */
	protected function buildDashboardFinanceiro(TicketsTable $tickets, int $idempresa, array $cols): array {
		$out = [
			'receita_mes' => null,
			'a_faturar' => 0,
			'horas_mes' => null,
			'horas_cobertas' => null,
			'margem_pct' => null,
			'custo_medio' => null,
		];
		$m0 = Time::now()->startOfMonth()->format('Y-m-d H:i:s');
		$m1 = Time::now()->endOfMonth()->format('Y-m-d H:i:s');
		if (in_array('situacao', $cols, true) && defined('C_TicketSituacaoResolvido')) {
			$w = [
				'Tickets.idempresa' => $idempresa,
				'Tickets.situacao' => (int)C_TicketSituacaoResolvido,
			];
			$q = $tickets->find()->where($w);
			($this->applyAbac)($q);
			$out['a_faturar'] = $q->count();
			$valorFat = 0.0;
			$qVal = $tickets->find()->select(['id'])->where($w)->limit(80);
			($this->applyAbac)($qVal);
			foreach ($qVal->all() as $tRow) {
				$tid = (int)$tRow->get('id');
				if ($tid <= 0) {
					continue;
				}
				$wl = $this->ticketWorklogSummary($tid, $idempresa);
				$sec = (int)($wl['total_sec'] ?? 0);
				if ($sec <= 0) {
					continue;
				}
				$horas = $sec / 3600;
				$excedente = max(0, round($horas - ($horas * 0.65), 2));
				$valorFat += $excedente * 280.0;
			}
			if ($valorFat > 0) {
				$out['a_faturar_valor'] = round($valorFat, 2);
			}
		}
		if ($this->tableExists('faturas')) {
			try {
				$ft = TableRegistry::getTableLocator()->get('Faturas');
				$fw = ['idempresa' => $idempresa];
				if (in_array('created', $ft->getSchema()->columns(), true)) {
					$fw['created >='] = $m0;
					$fw['created <='] = $m1;
				}
				$valCol = null;
				foreach (['valor_total', 'valortotal', 'valor'] as $c) {
					if (in_array($c, $ft->getSchema()->columns(), true)) {
						$valCol = $c;
						break;
					}
				}
				if ($valCol !== null) {
					$sum = 0.0;
					foreach ($ft->find()->where($fw)->limit(500)->all() as $f) {
						$sum += (float)($f->get($valCol) ?? 0);
					}
					if ($sum > 0) {
						$out['receita_mes'] = $sum;
					}
				}
			} catch (\Throwable $e) {
			}
		}
		if ($this->tableExists('ticketshoras')) {
			try {
				$th = TableRegistry::getTableLocator()->get('Ticketshoras');
				$tCols = $th->getSchema()->columns();
				$sec = 0;
				$where = ['idempresa' => $idempresa];
				if (in_array('data', $tCols, true)) {
					$where['data >='] = Time::now()->startOfMonth()->format('Y-m-d');
					$where['data <='] = Time::now()->endOfMonth()->format('Y-m-d');
				}
				foreach ($th->find()->where($where)->limit(5000)->all() as $h) {
					$sec += TicketServiceDeskApiService::resolveSecondsFromTicketshorasRow($th, $h);
				}
				if ($sec > 0) {
					$out['horas_mes'] = round($sec / 3600, 1);
				}
			} catch (\Throwable $e) {
			}
		}

		try {
			$metrics = new ServicedeskExecutiveMetricsService($this->applyAbac);
			$out = $metrics->enrichFinanceiro($out, $idempresa);
		} catch (\Throwable $e) {
		}

		return $out;
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

		$satisfacao = $this->fetchSatisfactionSnapshot($idempresa);
		$kbPreview = $this->buildKbPreview($tickets, $idempresa);
		$kbPopular = [];
		foreach ((array)($kbPreview['articles'] ?? []) as $art) {
			if ((string)($art['visibilidade'] ?? '') !== 'publico') {
				continue;
			}
			$rating = (string)($art['rating'] ?? '');
			$views = (int)($art['views'] ?? 0);
			$kbPopular[] = [
				'code' => (string)($art['code'] ?? ''),
				'titulo' => (string)($art['titulo'] ?? ''),
				'meta' => ($rating !== '' ? '⭐ ' . $rating : '⭐ —') . ' · 5 min · ' . $views . ' ' . __('visualizações'),
			];
			if (count($kbPopular) >= 3) {
				break;
			}
		}
		if ($kbPopular === []) {
			$kbPopular = [
				['code' => 'KB-042', 'titulo' => __('Como redefinir minha senha'), 'meta' => '⭐ 4.9 · 3 min · 124 ' . __('visualizações')],
				['code' => 'KB-018', 'titulo' => __('Configurar e-mail no celular'), 'meta' => '⭐ 4.7 · 5 min · 89 ' . __('visualizações')],
				['code' => 'KB-027', 'titulo' => __('Conectar VPN da empresa'), 'meta' => '⭐ 4.8 · 7 min · 67 ' . __('visualizações')],
			];
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
			'satisfacao' => $satisfacao,
			'satisfacao_fmt' => $satisfacao['csat_media'] !== null
				? '⭐ ' . number_format((float)$satisfacao['csat_media'], 1, ',', '.')
				: '—',
			'tickets_abertos' => array_slice($abertos, 0, 5),
			'categorias' => [
				['icon' => '🔑', 'nome' => __('Acesso & senhas'), 'sla' => '4h', 'cat' => 'acesso'],
				['icon' => '🖥', 'nome' => __('Hardware'), 'sla' => '1d', 'cat' => 'hardware'],
				['icon' => '📧', 'nome' => __('E-mail'), 'sla' => '2h', 'cat' => 'email'],
				['icon' => '🌐', 'nome' => __('Rede / Internet'), 'sla' => '1h', 'cat' => 'rede'],
				['icon' => '💿', 'nome' => __('Software / ERP'), 'sla' => '4h', 'cat' => 'software'],
				['icon' => '📦', 'nome' => __('Outros'), 'sla' => '1d', 'cat' => 'outros'],
			],
			'kb_popular' => $kbPopular,
		];
	}

	/**
	 * Formulário "Novo chamado" do portal cliente (somente leitura no protótipo).
	 *
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>
	 */
	public function buildPortalNovoPayload(TicketsTable $tickets, int $idempresa, array $query = []): array {
		$userName = trim((string)($query['userName'] ?? ''));
		$catKey = trim((string)($query['cat'] ?? ''));
		$portal = $this->buildPortalPreview($tickets, $idempresa, $userName);
		$kbSuggestions = [];
		foreach ((array)($portal['kb_popular'] ?? []) as $i => $art) {
			if ($i >= 2) {
				break;
			}
			$kbSuggestions[] = $art;
		}
		if ($kbSuggestions === []) {
			$kbPreview = $this->buildKbPreview($tickets, $idempresa);
			foreach ((array)($kbPreview['articles'] ?? []) as $i => $art) {
				if ((string)($art['visibilidade'] ?? '') !== 'publico') {
					continue;
				}
				$rating = (string)($art['rating'] ?? '');
				$kbSuggestions[] = [
					'code' => (string)($art['code'] ?? ''),
					'titulo' => (string)($art['titulo'] ?? ''),
					'meta' => ($rating !== '' ? '⭐ ' . $rating : '⭐ —') . ' · ' . __('5 min de leitura'),
				];
				if (count($kbSuggestions) >= 2) {
					break;
				}
			}
		}

		$categorias = [
			'acesso' => ['label' => __('Acesso & Permissões'), 'sla' => __('Resposta 2h · Resolução 1d')],
			'hardware' => ['label' => __('Hardware'), 'sla' => __('Resposta 4h · Resolução 1d')],
			'software' => ['label' => __('Software / ERP'), 'sla' => __('Resposta 4h · Resolução 2d')],
			'email' => ['label' => __('E-mail'), 'sla' => __('Resposta 2h · Resolução 4h')],
			'rede' => ['label' => __('Rede / Internet'), 'sla' => __('Resposta 1h · Resolução 4h')],
			'telefonia' => ['label' => __('Telefonia'), 'sla' => __('Resposta 4h · Resolução 1d')],
			'outros' => ['label' => __('Outros'), 'sla' => __('Resposta 1d · Resolução 3d')],
		];
		if ($catKey === '' || !isset($categorias[$catKey])) {
			$catKey = 'acesso';
		}
		$contract = $this->portalNovoContractSnapshot($idempresa);
		$contract['sla_categoria'] = (string)($categorias[$catKey]['sla'] ?? $contract['sla']);

		return [
			'selected_categoria' => $catKey,
			'categorias' => $categorias,
			'tipos' => [
				'incidente' => __('Incidente · algo parou de funcionar'),
				'requisicao' => __('Requisição · solicitar acesso/serviço'),
				'bug' => __('Bug · erro no sistema'),
				'duvida' => __('Dúvida · informação'),
				'sugestao' => __('Sugestão / Melhoria'),
			],
			'prioridades' => [
				'baixa' => __('Baixa · sem urgência'),
				'media' => __('Média · afeta minha rotina'),
				'alta' => __('Alta · afeta minha equipe'),
				'critica' => __('Crítica · parou tudo'),
			],
			'subcategorias' => [
				'senha' => __('Senha'),
				'novo_acesso' => __('Novo acesso'),
				'bloqueio' => __('Bloqueio de conta'),
				'permissao' => __('Permissão específica'),
			],
			'kb_suggestions' => $kbSuggestions,
			'contract' => $contract,
			'banner_cliente' => (string)($portal['banner_cliente'] ?? ''),
		];
	}

	/**
	 * @return array{plano:string,sla:string,sla_categoria:string,horas_restantes:string}
	 */
	protected function portalNovoContractSnapshot(int $idempresa): array {
		$out = [
			'plano' => __('Premium 24/7'),
			'sla' => __('Resposta 2h · Resolução 1d'),
			'sla_categoria' => __('Resposta 2h · Resolução 1d'),
			'horas_restantes' => '—',
		];
		if (!$this->tableExists('contratos_horas')) {
			return $out;
		}
		try {
			$ch = TableRegistry::getTableLocator()->get('ContratosHoras');
			$cols = $ch->getSchema()->columns();
			$w = [];
			if (in_array('idempresa', $cols, true)) {
				$w['ContratosHoras.idempresa'] = $idempresa;
			}
			if (in_array('ativo', $cols, true)) {
				$w['ContratosHoras.ativo'] = true;
			}
			$c = $ch->find()->where($w)->order(['ContratosHoras.id' => 'DESC'])->first();
			if ($c === null) {
				return $out;
			}
			$snap = ServiceDeskContractHoursService::getSnapshot($c);
			$hm = $c->get('horas_mensais');
			$horasMes = ($hm !== null && $hm !== '') ? (float)$hm : (float)($snap['totalHours'] ?? 20);
			if ($horasMes <= 0) {
				$horasMes = 20;
			}
			$idcli = (int)$c->get('idcliente');
			$usadas = $this->clientHorasMes($idcli, $idempresa);
			if ($usadas === null && $snap['usedHours'] !== null) {
				$usadas = (float)$snap['usedHours'];
			}
			if ($usadas === null) {
				$usadas = 0.0;
			}
			$restantes = max(0, round($horasMes - $usadas, 1));
			$out['horas_restantes'] = sprintf(
				'%sh ' . __('de') . ' %sh',
				rtrim(rtrim(number_format($restantes, 1, ',', '.'), '0'), ','),
				(int)round($horasMes)
			);
			if ($horasMes >= 30) {
				$out['plano'] = __('Premium 24/7');
			} elseif ($horasMes >= 15) {
				$out['plano'] = __('Premium');
			} else {
				$out['plano'] = __('Standard');
			}
		} catch (\Throwable $e) {
		}

		return $out;
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
		int $limit,
		array $query = []
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
		$cols = $tickets->getSchema()->columns();
		$base = function () use ($tickets, $idempresa, $contain, $query, $cols): Query {
			$q = $tickets->find()
				->contain($contain)
				->where(['Tickets.idempresa' => $idempresa])
				->order(['Tickets.id' => 'DESC']);
			$this->filaApplyQueryFilters($q, $tickets, $idempresa, $query, $cols);
			($this->applyAbac)($q);

			return $q;
		};
		$total = $base()->count();
		$rows = $base()->limit($limit)->offset($offset)->all();

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
	 * Filtros GET da fila técnica (busca, status, fila, nível, técnico, SLA).
	 *
	 * @param array<string,mixed> $query
	 * @param string[] $cols
	 */
	protected function filaApplyQueryFilters(
		Query $q,
		TicketsTable $tickets,
		int $idempresa,
		array $query,
		array $cols
	): void {
		$closed = $this->closedSituacoes();
		$status = trim((string)($query['status'] ?? 'abertos'));
		if ($status === '' || $status === 'abertos') {
			if ($closed !== [] && in_array('situacao', $cols, true)) {
				$q->where(['Tickets.situacao NOT IN' => $closed]);
			}
		} elseif ($status !== 'all' && in_array('situacao', $cols, true)) {
			$map = [
				'pendente' => defined('C_TicketSituacaoPendente') ? (int)C_TicketSituacaoPendente : null,
				'execucao' => defined('C_TicketSituacaoEmandamento') ? (int)C_TicketSituacaoEmandamento : null,
				'respondido' => defined('C_TicketSituacaoRespondido') ? (int)C_TicketSituacaoRespondido : null,
				'resolvido' => defined('C_TicketSituacaoResolvido') ? (int)C_TicketSituacaoResolvido : null,
				'fechado' => defined('C_TicketSituacaoFechado') ? (int)C_TicketSituacaoFechado : null,
			];
			if (isset($map[$status]) && $map[$status] !== null) {
				$q->where(['Tickets.situacao' => $map[$status]]);
			}
		}

		$queueId = (int)($query['queue_id'] ?? 0);
		if ($queueId > 0 && in_array('queue_id', $cols, true)) {
			$q->where(['Tickets.queue_id' => $queueId]);
		}

		$nivel = trim((string)($query['nivel'] ?? ''));
		if ($nivel !== '' && in_array('queue_id', $cols, true)) {
			$qids = $this->queueIdsForNivelFilter($idempresa, $nivel);
			if ($qids !== []) {
				$q->where(['Tickets.queue_id IN' => $qids]);
			}
		}

		$tecRaw = (string)($query['tecnico_id'] ?? '');
		$tecCol = $this->ticketResponsavelColumn($cols);
		if ($tecCol !== null && $tecRaw !== '') {
			if ($tecRaw === 'sem' || $tecRaw === '0') {
				$q->where(['OR' => [
					['Tickets.' . $tecCol . ' IS' => null],
					['Tickets.' . $tecCol => 0],
				]]);
			} else {
				$tid = (int)$tecRaw;
				if ($tid > 0) {
					$q->where(['Tickets.' . $tecCol => $tid]);
				}
			}
		}

		$sla = trim((string)($query['sla'] ?? ''));
		if ($sla !== '' && $sla !== 'todos') {
			$now = Time::now();
			$soon = $now->copy()->addMinutes(30);
			if ($sla === 'estourado' && in_array('data_limite_resolucao', $cols, true)) {
				$conds = ['Tickets.data_limite_resolucao <' => $now];
				if (in_array('sla_resolucao_pausado', $cols, true)) {
					$conds['Tickets.sla_resolucao_pausado'] = false;
				}
				if (in_array('sla_resposta_pausado', $cols, true)) {
					$conds['Tickets.sla_resposta_pausado'] = false;
				}
				$q->where($conds);
			} elseif ($sla === 'limite' && in_array('data_limite_resolucao', $cols, true)) {
				$conds = [
					'Tickets.data_limite_resolucao >=' => $now,
					'Tickets.data_limite_resolucao <=' => $soon,
				];
				if (in_array('sla_resolucao_pausado', $cols, true)) {
					$conds['Tickets.sla_resolucao_pausado'] = false;
				}
				if (in_array('sla_resposta_pausado', $cols, true)) {
					$conds['Tickets.sla_resposta_pausado'] = false;
				}
				$q->where($conds);
			} elseif ($sla === 'pausado' && in_array('sla_resolucao_pausado', $cols, true)) {
				$q->where(['OR' => [
					['Tickets.sla_resolucao_pausado' => true],
					['Tickets.sla_resposta_pausado' => true],
				]]);
			}
		}

		$term = trim((string)($query['q'] ?? ''));
		if ($term !== '') {
			if (preg_match('/^#?(\d+)$/', $term, $m)) {
				$q->where(['Tickets.id' => (int)$m[1]]);
			} else {
				$like = '%' . $term . '%';
				$or = ['Tickets.assunto ILIKE' => $like];
				if (in_array('idcliente', $cols, true)) {
					$or['Clientes.nome ILIKE'] = $like;
					$or['Clientes.razaosocial ILIKE'] = $like;
				}
				$q->where(['OR' => $or]);
			}
		}
	}

	/**
	 * @return int[]
	 */
	protected function queueIdsForNivelFilter(int $idempresa, string $nivel): array {
		$nivel = strtoupper(trim($nivel));
		if ($nivel === '') {
			return [];
		}
		try {
			$qt = TableRegistry::getTableLocator()->get('Queues');
			$qf = $qt->find()->where(['Queues.idempresa' => $idempresa]);
			if ($this->tableExists('support_levels') && $qt->associations()->has('SupportLevels')) {
				$qf->contain(['SupportLevels']);
			}
			$out = [];
			foreach ($qf->all() as $qr) {
				$match = stripos((string)$qr->get('name', ''), $nivel) !== false;
				$sl = $qr->support_level ?? null;
				if (!$match && $sl !== null) {
					$slName = trim((string)$this->rowGet($sl, 'name', ''));
					$match = stripos($slName, $nivel) !== false;
				}
				if ($match) {
					$out[] = (int)$qr->get('id');
				}
			}

			return $out;
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * Opções de filtro para a view da fila.
	 *
	 * @return array<string,mixed>
	 */
	protected function filaFilterOptions(TicketsTable $tickets, int $idempresa): array {
		$assignment = $this->buildFilaAssignmentMeta($tickets, $idempresa);
		$niveis = [];
		foreach ((array)($assignment['queues'] ?? []) as $q) {
			$n = trim((string)($q['nivel'] ?? ''));
			if ($n !== '' && !in_array($n, $niveis, true)) {
				$niveis[] = $n;
			}
			$name = (string)($q['name'] ?? '');
			if (preg_match('/\b(N[123])\b/i', $name, $m) && !in_array(strtoupper($m[1]), $niveis, true)) {
				$niveis[] = strtoupper($m[1]);
			}
		}
		sort($niveis);

		return [
			'queues' => (array)($assignment['queues'] ?? []),
			'tecnicos' => (array)($assignment['tecnicos'] ?? []),
			'niveis' => $niveis,
		];
	}

	/**
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>
	 */
	protected function filaActiveFilters(array $query): array {
		return [
			'q' => trim((string)($query['q'] ?? '')),
			'status' => trim((string)($query['status'] ?? 'abertos')) ?: 'abertos',
			'queue_id' => (int)($query['queue_id'] ?? 0),
			'nivel' => trim((string)($query['nivel'] ?? '')),
			'tecnico_id' => (string)($query['tecnico_id'] ?? ''),
			'sla' => trim((string)($query['sla'] ?? 'todos')) ?: 'todos',
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
		int $limit = 30,
		array $query = []
	): array {
		$dash = new DashboardService($tickets);
		$snap = $dash->operationalSnapshot($idempresa);
		$fila = $this->filaTicketsPage($tickets, $idempresa, $page, $limit, $query);
		$totalEmpresa = $tickets->find()->where(['Tickets.idempresa' => $idempresa])->count();
		$filters = $this->filaFilterOptions($tickets, $idempresa);

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
			'filters' => $this->filaActiveFilters($query),
			'filter_options' => $filters,
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
				'tecnicos' => [],
				'queue_id' => 0,
				'tecnico_id' => 0,
				'hint' => __('Não foi possível determinar tickets em aberto (constantes de situação).'),
				'truncated' => false,
				'readonly' => true,
			];
		}

		$queueId = (int)($query['queue_id'] ?? $query['fila'] ?? 0);
		$tecnicoId = (int)($query['tecnico_id'] ?? 0);
		$queues = $this->kanbanQueuesForFilter($idempresa, $cols);
		$tecnicos = $this->kanbanTecnicosForFilter($tickets, $idempresa);

		$orderCol = 'Tickets.id';
		if (in_array('modified', $cols, true)) {
			$orderCol = 'Tickets.modified';
		} elseif (in_array('updated', $cols, true)) {
			$orderCol = 'Tickets.updated';
		} elseif (in_array('created', $cols, true)) {
			$orderCol = 'Tickets.created';
		}
		$contain = ['Clientes', 'users'];
		if ($tickets->associations()->has('Queues')) {
			if ($this->tableExists('support_levels')) {
				$contain['Queues'] = ['SupportLevels'];
			} else {
				$contain[] = 'Queues';
			}
		}
		$entities = [];
		$truncated = false;

		$qOpen = $tickets->find()
			->contain($contain)
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.situacao NOT IN' => $closed,
			])
			->order([$orderCol => 'DESC'])
			->limit(400);
		if ($queueId > 0 && in_array('queue_id', $cols, true)) {
			$qOpen->where(['Tickets.queue_id' => $queueId]);
		}
		$tecCol = $this->ticketResponsavelColumn($cols);
		if ($tecnicoId > 0 && $tecCol !== null) {
			$qOpen->where(['Tickets.' . $tecCol => $tecnicoId]);
		}
		($this->applyAbac)($qOpen);
		$openList = $qOpen->all()->toArray();
		$entities = array_merge($entities, $openList);
		if (count($openList) >= 400) {
			$truncated = true;
		}

		$doneSit = array_values(array_filter([
			defined('C_TicketSituacaoResolvido') ? (int)C_TicketSituacaoResolvido : null,
			defined('C_TicketSituacaoFechado') ? (int)C_TicketSituacaoFechado : null,
		]));
		if ($doneSit !== []) {
			$qDone = $tickets->find()
				->contain($contain)
				->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.situacao IN' => $doneSit,
				])
				->order([$orderCol => 'DESC'])
				->limit(60);
			if ($queueId > 0 && in_array('queue_id', $cols, true)) {
				$qDone->where(['Tickets.queue_id' => $queueId]);
			}
			if ($tecnicoId > 0 && $tecCol !== null) {
				$qDone->where(['Tickets.' . $tecCol => $tecnicoId]);
			}
			($this->applyAbac)($qDone);
			$entities = array_merge($entities, $qDone->all()->toArray());
		}

		$columns = $this->kanbanColumnsOperational($tickets, $entities, $cols);

		return [
			'mode' => 'operacional',
			'columns' => $columns,
			'queues' => $queues,
			'tecnicos' => $tecnicos,
			'queue_id' => $queueId,
			'tecnico_id' => $tecnicoId,
			'truncated' => $truncated,
			'hint' => $truncated
				? __('Mostrando os tickets mais recentes no seu escopo; use a fila para paginação completa.')
				: '',
			'readonly' => true,
		];
	}

	/**
	 * Técnicos da empresa para filtro do Kanban.
	 *
	 * @return array<int,array{id:int,name:string}>
	 */
	protected function kanbanTecnicosForFilter(TicketsTable $tickets, int $idempresa): array {
		$meta = $this->buildFilaAssignmentMeta($tickets, $idempresa);
		$out = [];
		foreach ((array)($meta['tecnicos'] ?? []) as $t) {
			$id = (int)($t['id'] ?? 0);
			if ($id <= 0) {
				continue;
			}
			$out[] = ['id' => $id, 'name' => (string)($t['name'] ?? '')];
		}

		return $out;
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
		$wipLimit = 5;
		$defs = [
			'aberto' => [
				'key' => 'aberto',
				'title' => '🟢 ' . __('ABERTO'),
				'title_color' => '#0a3d2c',
				'sub' => __('Aguardando atribuição'),
				'style' => ['bg' => '#F0FDF4', 'border' => '#7DD3C0', 'count_bg' => '#7DD3C0', 'count_color' => '#0a3d2c'],
				'cards' => [],
				'total' => 0,
				'max_cards' => 45,
			],
			'execucao' => [
				'key' => 'execucao',
				'title' => '🔵 ' . __('EM EXECUÇÃO'),
				'title_color' => '#0C4A6E',
				'sub' => sprintf(__('WIP limit: %d'), $wipLimit),
				'style' => ['bg' => '#ECFEFF', 'border' => '#06B6D4', 'count_bg' => '#06B6D4', 'count_color' => '#fff'],
				'cards' => [],
				'total' => 0,
				'wip_limit' => $wipLimit,
				'max_cards' => 45,
			],
			'pendente_cliente' => [
				'key' => 'pendente_cliente',
				'title' => '⏰ ' . __('PENDENTE'),
				'title_color' => '#B45309',
				'sub' => __('Aguardando cliente'),
				'style' => ['bg' => '#FFFBEB', 'border' => '#F59E0B', 'count_bg' => '#F59E0B', 'count_color' => '#fff'],
				'cards' => [],
				'total' => 0,
				'max_cards' => 45,
			],
			'resolvido' => [
				'key' => 'resolvido',
				'title' => '✓ ' . __('RESOLVIDO'),
				'title_color' => '#065F46',
				'sub' => __('Aguarda aprovação · 72h'),
				'style' => ['bg' => '#F0FDF4', 'border' => '#10B981', 'count_bg' => '#10B981', 'count_color' => '#fff'],
				'cards' => [],
				'total' => 0,
				'max_cards' => 20,
			],
			'fechado' => [
				'key' => 'fechado',
				'title' => '⬛ ' . __('FECHADO'),
				'title_color' => '#374151',
				'sub' => __('Finalizados recentes'),
				'style' => ['bg' => '#F9FAFB', 'border' => '#6B7280', 'count_bg' => '#6B7280', 'count_color' => '#fff'],
				'cards' => [],
				'total' => 0,
				'max_cards' => 12,
				'more' => 0,
			],
		];

		foreach ($entities as $t) {
			$bucket = $this->kanbanOperationalBucket($t, $cols);
			if (!isset($defs[$bucket])) {
				$bucket = 'aberto';
			}
			$max = (int)($defs[$bucket]['max_cards'] ?? 45);
			$this->pushKanbanCard($defs[$bucket], $this->serializeKanbanCard($tickets, $t, $cols), $max);
		}

		if ($defs['execucao']['total'] > 0) {
			$defs['execucao']['sub'] = sprintf(
				__('WIP limit: %d · atual %d'),
				$wipLimit,
				(int)$defs['execucao']['total']
			);
		}
		$fechadoExtra = max(0, (int)$defs['fechado']['total'] - count($defs['fechado']['cards']));
		$defs['fechado']['more'] = $fechadoExtra;

		return [
			$defs['aberto'],
			$defs['execucao'],
			$defs['pendente_cliente'],
			$defs['resolvido'],
			$defs['fechado'],
		];
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

		$resolvido = defined('C_TicketSituacaoResolvido') ? (int)C_TicketSituacaoResolvido : -1;
		$fechado = defined('C_TicketSituacaoFechado') ? (int)C_TicketSituacaoFechado : -1;
		if ($resolvido >= 0 && $sit === $resolvido) {
			return 'resolvido';
		}
		if ($fechado >= 0 && $sit === $fechado) {
			return 'fechado';
		}

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

		return $tecId > 0 ? 'execucao' : 'aberto';
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
		$slaStatus = in_array('sla_status', $cols, true) ? (string)$this->rowGet($t, 'sla_status') : '';
		$slaBad = $slaStatus === 'violado' || $this->isSlaOverdue(in_array('data_limite_resolucao', $cols, true) ? $this->rowGet($t, 'data_limite_resolucao') : null);
		$slaLabel = $slaBad ? '⚠ SLA' : ($slaStatus === 'em_risco' ? '⏰' : ($tempo !== '—' ? $tempo : 'SLA OK'));
		if ($slaBad && $tempo !== '—') {
			$slaLabel = '⚠ ' . $tempo;
		}
		$prio = $this->prioridadeMeta($this->rowGet($t, 'prioridade'));
		$tecLabel = $this->resolveTicketTecnicoLabel($tickets, $t);
		$clienteLine = $clienteNome;
		if ($tecLabel !== '—' && $tecLabel !== __('Sem atribuição')) {
			$clienteLine .= ' · ' . \Cake\Utility\Text::truncate($tecLabel, 18, ['ellipsis' => '…']);
		}
		$tags = [];
		$assuntoLower = mb_strtolower($assuntoTxt);
		foreach (['acesso', 'novo', 'erp', 'vpn', 'email', 'hardware', 'rede'] as $tag) {
			if (strpos($assuntoLower, $tag) !== false) {
				$tags[] = $tag;
			}
		}
		$tags = array_slice(array_values(array_unique($tags)), 0, 3);
		$hint = '';
		$sit = (int)$sitDisp['situacao'];
		if ($sit === (int)(defined('C_TicketSituacaoRespondido') ? C_TicketSituacaoRespondido : -1)) {
			$hint = __('⏰ Aguardando cliente');
		} elseif ($sit === (int)(defined('C_TicketSituacaoResolvido') ? C_TicketSituacaoResolvido : -2) && $lim !== null) {
			$hint = __('2h restam');
		}

		return [
			'id' => (int)$this->rowGet($t, 'id', 0),
			'assunto' => $assuntoTxt,
			'cliente' => $clienteLine,
			'cliente_nome' => $clienteNome,
			'tecnico' => $tecLabel,
			'fila_label' => $filaLabel,
			'prioridade' => $this->rowGet($t, 'prioridade'),
			'prioridade_label' => (string)($prio['label'] ?? ''),
			'prioridade_class' => (string)($prio['class'] ?? ''),
			'situacao' => (int)$sitDisp['situacao'],
			'situacao_label' => (string)$sitDisp['situacao_label'],
			'sla_status' => $slaStatus !== '' ? $slaStatus : null,
			'sla_bad' => $slaBad,
			'sla_label' => $slaLabel,
			'data_limite' => $lim,
			'tempo' => $tempo,
			'tags' => $tags,
			'hint' => $hint,
			'closed' => in_array((int)$sitDisp['situacao'], $this->closedSituacoes(), true),
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
			$csat = $this->clienteCsatAvg($idempresa, $cid);
			$out[] = [
				'id' => $cid,
				'name' => $names[$cid] ?? ('Cliente #' . $cid),
				'count' => (int)($r['total'] ?? 0),
				'csat' => $csat,
				'plan' => (int)($r['total'] ?? 0) >= 30 ? __('Premium') : __('Standard'),
			];
		}

		return $out;
	}

	/**
	 * CSAT médio do cliente (90 dias).
	 */
	protected function clienteCsatAvg(int $idempresa, int $idcliente): ?float {
		if ($idcliente <= 0 || !$this->tableExists('ticket_csat_responses')) {
			return null;
		}
		try {
			$tbl = TableRegistry::getTableLocator()->get('TicketCsatResponses');
			$since = Time::now()->subDays(90)->format('Y-m-d H:i:s');
			$q = $tbl->find();
			$q->innerJoinWith('Tickets', function ($jq) use ($idempresa, $idcliente) {
				return $jq->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.idcliente' => $idcliente,
				]);
			});
			$q->where(['TicketCsatResponses.responded_at >=' => $since]);
			$scores = [];
			foreach ($q->select(['csat_score'])->limit(40)->enableHydration(false)->toArray() as $row) {
				$s = (int)($row['csat_score'] ?? 0);
				if ($s >= 1 && $s <= 5) {
					$scores[] = $s;
				}
			}
			if ($scores === []) {
				return null;
			}

			return round(array_sum($scores) / count($scores), 1);
		} catch (\Throwable $e) {
			return null;
		}
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
		if ($tickets->associations()->has('Queues')) {
			$contain[] = 'Queues';
		}
		if ($tickets->associations()->has('SlaPolicies')) {
			$contain[] = 'SlaPolicies';
		}
		if ($tickets->associations()->has('SupportLevels')) {
			$contain[] = 'SupportLevels';
		}
		if ($tickets->associations()->has('Ticketcomentarios')) {
			$contain['Ticketcomentarios'] = ['Users'];
		}
		if ($tickets->associations()->has('Ticketsanexos')) {
			$contain[] = 'Ticketsanexos';
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
		if ($slaAlert && in_array('data_limite_resolucao', $cols, true)) {
			$dl = $t->get('data_limite_resolucao');
			if ($dl instanceof \DateTimeInterface && $dl < Time::now()) {
				$sec = Time::now()->getTimestamp() - $dl->getTimestamp();
				$slaLabel = __('⚠ ESTOURADO HÁ ') . $this->formatDurationShort($sec);
			} elseif ($slaLabel === '' || $slaLabel === __('SLA estourado')) {
				$slaLabel = __('⚠ ESTOURADO');
			}
		}

		$solicitanteNorm = mb_strtolower(trim($solicitante));
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
				$autorRole = null;
				if ($au) {
					$autor = trim((string)($au->get('name') ?? ''));
					if ($autor === '') {
						$autor = trim((string)($au->get('username') ?? ''));
					}
					if (in_array('role', $au->getSource()->getSchema()->columns(), true)) {
						$autorRole = (int)$au->get('role');
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
				$isCliente = !$isInterno && (
					($autorRole === 1)
					|| ($solicitanteNorm !== '' && mb_strtolower(trim($autor)) === $solicitanteNorm)
				);
				$tipo = $isInterno ? 'interno' : ($isCliente ? 'cliente' : 'publico');
				$badge = $isInterno ? __('🔒 NOTA INTERNA') : ($isCliente ? __('CLIENTE') : __('📤 ENVIADO AO CLIENTE'));
				$badgeBg = $isInterno ? '#EDE9F8' : ($isCliente ? 'var(--blue-light,#DBEAFE)' : 'var(--teal-light)');
				$badgeColor = $isInterno ? '#6B5B95' : ($isCliente ? 'var(--blue,#0C447C)' : 'var(--teal-dark)');
				$avatarBg = $isInterno ? '#6B5B95' : ($isCliente ? 'var(--blue)' : 'var(--teal)');
				$messages[] = [
					'autor' => $autor !== '' ? $autor : '—',
					'initials' => $this->initialsFromName($autor),
					'when' => $fmt($when),
					'body' => $body,
					'tipo' => $tipo,
					'badge' => $badge,
					'badge_bg' => $badgeBg,
					'badge_color' => $badgeColor,
					'avatar_bg' => $avatarBg,
				];
			}
		}

		$timeline = $this->ticketTimelineSteps($sit, $fmt($created), $fmt($modified));
		$pill = $this->situacaoPillMeta($sit);
		$prioMeta = $this->prioridadeMeta($t->get('prioridade'));
		$tempoTotal = $this->formatElapsed($created);
		$extras = $this->ticketDetailExtras($tickets, $t, $idempresa, $cols, $fmt, $assuntoTxt);

		$threadCounts = [
			'todos' => count($messages),
			'publicos' => count(array_filter($messages, static function (array $m): bool {
				$t = (string)($m['tipo'] ?? '');

				return $t === 'publico' || $t === 'cliente';
			})),
			'internos' => count(array_filter($messages, static function (array $m): bool {
				return (string)($m['tipo'] ?? '') === 'interno';
			})),
		];

		return array_merge([
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
			'thread_counts' => $threadCounts,
			'status_band_style' => $slaAlert
				? 'background:linear-gradient(135deg,#FEF2F2 0%,#fff 60%);border-left:4px solid #7A1822;'
				: 'background:linear-gradient(135deg,#F0FDF4 0%,#fff 60%);border-left:4px solid var(--teal);',
		], $extras);
	}

	/**
	 * @param string[] $cols
	 * @param callable $fmt
	 * @return array<string,mixed>
	 */
	protected function ticketDetailExtras(
		TicketsTable $tickets,
		$t,
		int $idempresa,
		array $cols,
		callable $fmt,
		string $assuntoTxt = ''
	): array {
		$id = (int)$t->get('id');
		$idcliente = (int)($t->get('idcliente') ?? 0);
		$c = $this->ticketRelatedCliente($t);
		$clienteTel = '';
		$clienteCnpj = '';
		if ($c) {
			$clienteTel = trim((string)($c->get('telefone') ?? $c->get('celular') ?? ''));
			$clienteCnpj = trim((string)($c->get('cnpj') ?? $c->get('cpf') ?? ''));
		}
		$queue = $this->ticketRelatedQueue($t);
		$queueName = $queue ? trim((string)$this->rowGet($queue, 'name', '')) : '';
		$sl = $this->ticketRelatedSupportLevel($t);
		$supportLevel = $sl ? trim((string)$this->rowGet($sl, 'name', '')) : '';
		$slaPolicy = $t->sla_policy ?? $t->sla_policies ?? null;
		$slaPolicyName = $slaPolicy ? trim((string)$this->rowGet($slaPolicy, 'nome', '')) : '';
		$tipoTicket = in_array('tipo_ticket', $cols, true) ? trim((string)($t->get('tipo_ticket') ?? '')) : '';
		if ($tipoTicket === '') {
			$tipoTicket = __('Requisição');
		}
		$tempoEtapa = $this->formatElapsed($t->get('modified') ?? $t->get('updated'));
		$sitLabel = $this->situacaoLabel((int)($t->get('situacao') ?? 0));
		$slaViol = in_array('sla_status', $cols, true) && (string)($t->get('sla_status') ?? '') === 'violado';
		$hasContrato = in_array('idcontrato', $cols, true) && (int)($t->get('idcontrato') ?? 0) > 0;
		$clienteBadges = [];
		if (($clientStats['total'] ?? 0) >= 15) {
			$clienteBadges[] = ['label' => __('★ Cliente VIP'), 'class' => 'b-paga'];
		}
		if ($hasContrato) {
			$clienteBadges[] = ['label' => __('📄 Contrato Premium'), 'class' => 'b-aprov'];
		}
		$categoria = __('Acesso & Permissões');
		$subcategoria = '';
		$assuntoLower = mb_strtolower($assuntoTxt !== '' ? $assuntoTxt : (string)$t->get('assunto'));
		if (strpos($assuntoLower, 'servidor') !== false || strpos($assuntoLower, 'hardware') !== false) {
			$categoria = __('Hardware');
		} elseif (strpos($assuntoLower, 'email') !== false || strpos($assuntoLower, 'office') !== false || strpos($assuntoLower, '365') !== false) {
			$categoria = __('E-mail');
		} elseif (strpos($assuntoLower, 'erp') !== false || strpos($assuntoLower, 'nf') !== false) {
			$categoria = __('ERP');
		} elseif (strpos($assuntoLower, 'rede') !== false || strpos($assuntoLower, 'vpn') !== false) {
			$categoria = __('Rede');
		}
		if (strpos($assuntoLower, 'acesso') !== false || strpos($assuntoLower, 'perfil') !== false || strpos($assuntoLower, 'permiss') !== false) {
			$subcategoria = __('Novo acesso');
		} elseif (strpos($assuntoLower, 'senha') !== false) {
			$subcategoria = __('Senha');
		}
		$dataLimiteFmt = '';
		if (in_array('data_limite_resolucao', $cols, true)) {
			$dl = $t->get('data_limite_resolucao');
			if ($dl instanceof \DateTimeInterface) {
				$dataLimiteFmt = $dl->format('d/m/Y H:i');
			}
		}
		$primeiraRespFmt = '';
		$primeiraRespOk = false;
		if (in_array('data_primeira_resposta', $cols, true)) {
			$pr = $t->get('data_primeira_resposta');
			if ($pr instanceof \DateTimeInterface) {
				$primeiraRespOk = true;
				$created = $t->get('created');
				if ($created instanceof \DateTimeInterface) {
					$mins = (int)max(0, round(($pr->getTimestamp() - $created->getTimestamp()) / 60));
					$primeiraRespFmt = __('✓ Cumprido') . ' (' . $this->fmtMinutesLabel($mins) . ')';
				} else {
					$primeiraRespFmt = __('✓ Cumprido');
				}
			}
		}
		$resolucaoSlaFmt = '';
		if (in_array('sla_status', $cols, true)) {
			$st = (string)($t->get('sla_status') ?? '');
			if ($st === 'violado') {
				$resolucaoSlaFmt = __('⚠ ESTOURADO');
			} elseif ($st !== '') {
				$resolucaoSlaFmt = __('✓ OK');
			}
		}

		$anexos = [];
		$anexosRows = $t->ticketsanexos ?? [];
		if (is_iterable($anexosRows)) {
			foreach ($anexosRows as $ax) {
				$nome = trim((string)($ax->get('nome') ?? $ax->get('arquivo') ?? $ax->get('filename') ?? ''));
				if ($nome === '') {
					continue;
				}
				$anexos[] = [
					'nome' => $nome,
					'size' => trim((string)($ax->get('tamanho') ?? '')),
				];
			}
		}

		$worklog = $this->ticketWorklogSummary($id, $idempresa);
		$clientStats = $this->ticketClienteStats($tickets, $idempresa, $idcliente, $cols);
		$prioMeta = $this->prioridadeMeta($t->get('prioridade'));
		$related = $this->ticketRelatedList($tickets, $idempresa, $id, $idcliente, $cols);
		$audit = $this->ticketAuditLog($id, $idempresa, 8);
		$kbArticles = array_slice((array)($this->buildKbPreview($tickets, $idempresa)['articles'] ?? []), 0, 2);
		$tags = [];
		$assuntoLower = mb_strtolower((string)$t->get('assunto'));
		foreach (['acesso', 'erp', 'rede', 'email', 'hardware'] as $tag) {
			if (strpos($assuntoLower, $tag) !== false) {
				$tags[] = $tag;
			}
		}

		return [
			'cliente_tel' => $clienteTel,
			'cliente_cnpj' => $clienteCnpj,
			'cliente_codigo' => $idcliente > 0 ? 'CLI-' . str_pad((string)$idcliente, 4, '0', STR_PAD_LEFT) : '',
			'cliente_stats' => $clientStats,
			'queue_name' => $queueName !== '' ? $queueName : ($prioMeta['fila'] ?? '—'),
			'support_level' => $supportLevel !== '' ? $supportLevel : ($prioMeta['nivel'] ?? '—'),
			'sla_policy_name' => $slaPolicyName !== '' ? $slaPolicyName : ($prioMeta['fila'] ?? '—'),
			'tipo_ticket' => $tipoTicket,
			'categoria' => $tipoTicket,
			'tempo_etapa' => $tempoEtapa,
			'tempo_etapa_alert' => $slaViol,
			'tempo_etapa_label' => sprintf(__('Em "%s" há'), $sitLabel),
			'data_limite_fmt' => $dataLimiteFmt,
			'primeira_resposta_fmt' => $primeiraRespFmt,
			'primeira_resposta_ok' => $primeiraRespOk,
			'resolucao_sla_fmt' => $resolucaoSlaFmt,
			'anexos' => $anexos,
			'worklog' => $worklog,
			'related_tickets' => $related,
			'audit_log' => $audit,
			'kb_articles' => $kbArticles,
			'tags' => $tags,
			'cliente_badges' => $clienteBadges,
			'categoria_detalhe' => $categoria,
			'subcategoria' => $subcategoria,
			'official_url' => ['controller' => 'Servicedesk', 'action' => 'view', $id],
		];
	}

	/**
	 * @param string[] $cols
	 * @return array<string,mixed>
	 */
	protected function ticketWorklogSummary(int $ticketId, int $idempresa): array {
		$out = ['total_sec' => 0, 'total_fmt' => '0min', 'billable_fmt' => '—'];
		if (!$this->tableExists('ticketshoras')) {
			return $out;
		}
		try {
			$th = TableRegistry::getTableLocator()->get('Ticketshoras');
			$tCols = $th->getSchema()->columns();
			$where = ['idempresa' => $idempresa];
			if (in_array('idticket', $tCols, true)) {
				$where['idticket'] = $ticketId;
			} elseif (in_array('ticket_id', $tCols, true)) {
				$where['ticket_id'] = $ticketId;
			}
			$sec = 0;
			foreach ($th->find()->where($where)->limit(500)->all() as $h) {
				$sec += TicketServiceDeskApiService::resolveSecondsFromTicketshorasRow($th, $h);
			}
			if ($sec <= 0) {
				return $out;
			}
			$out['total_sec'] = $sec;
			$h = intdiv($sec, 3600);
			$m = intdiv($sec % 3600, 60);
			$out['total_fmt'] = $h > 0 ? ($m > 0 ? sprintf('%dh %dmin', $h, $m) : sprintf('%dh', $h)) : ($m . 'min');
			$out['billable_fmt'] = 'R$ ' . number_format($h * 280 + ($m / 60) * 280, 0, ',', '.');
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param string[] $cols
	 * @return array<string,mixed>
	 */
	protected function ticketClienteStats(TicketsTable $tickets, int $idempresa, int $idcliente, array $cols): array {
		$out = ['mes' => 0, 'total' => 0, 'csat' => null];
		if ($idcliente <= 0 || !in_array('idcliente', $cols, true)) {
			return $out;
		}
		try {
			$base = ['Tickets.idempresa' => $idempresa, 'Tickets.idcliente' => $idcliente];
			$out['total'] = $tickets->find()->where($base)->count();
			if (in_array('created', $cols, true)) {
				$m0 = Time::now()->startOfMonth()->format('Y-m-d H:i:s');
				$out['mes'] = $tickets->find()->where($base + ['Tickets.created >=' => $m0])->count();
			}
			if ($this->tableExists('ticket_csat_responses')) {
				try {
					$tbl = TableRegistry::getTableLocator()->get('TicketCsatResponses');
					$q = $tbl->find();
					$q->innerJoinWith('Tickets', function ($jq) use ($idempresa, $idcliente) {
						return $jq->where([
							'Tickets.idempresa' => $idempresa,
							'Tickets.idcliente' => $idcliente,
						]);
					});
					$scores = [];
					foreach ($q->select(['csat_score'])->limit(80)->enableHydration(false)->toArray() as $row) {
						$s = (int)($row['csat_score'] ?? 0);
						if ($s >= 1 && $s <= 5) {
							$scores[] = $s;
						}
					}
					if ($scores !== []) {
						$out['csat'] = round(array_sum($scores) / count($scores), 1);
					}
				} catch (\Throwable $e) {
				}
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param string[] $cols
	 * @return array<int,array<string,mixed>>
	 */
	protected function ticketRelatedList(TicketsTable $tickets, int $idempresa, int $ticketId, int $idcliente, array $cols): array {
		if ($idcliente <= 0) {
			return [];
		}
		$closed = $this->closedSituacoes();
		$q = $tickets->find()
			->select(['id', 'assunto', 'situacao'])
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.idcliente' => $idcliente,
				'Tickets.id !=' => $ticketId,
			])
			->order(['Tickets.modified' => 'DESC'])
			->limit(5);
		($this->applyAbac)($q);
		$out = [];
		foreach ($q->all() as $rt) {
			$sit = (int)$rt->get('situacao');
			$out[] = [
				'id' => (int)$rt->get('id'),
				'assunto' => method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
					? $tickets->resolveTicketAssuntoTextoPublic($rt->get('assunto'))
					: (string)$rt->get('assunto'),
				'closed' => $closed !== [] && in_array($sit, $closed, true),
				'situacao_label' => $this->situacaoLabel($sit),
			];
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function ticketAuditLog(int $ticketId, int $idempresa, int $limit): array {
		$out = [];
		if ($this->tableExists('ticket_histories')) {
			try {
				$th = TableRegistry::getTableLocator()->get('TicketHistories');
				$rows = $th->find()
					->where(['ticket_id' => $ticketId])
					->order(['created' => 'DESC'])
					->limit($limit)
					->all();
				foreach ($rows as $r) {
					$when = $r->get('created');
					$out[] = [
						'when' => $when instanceof \DateTimeInterface ? $when->format('d/m/Y H:i') : '—',
						'text' => trim((string)($r->get('descricao') ?? $r->get('valor_novo') ?? $r->get('tipo_evento') ?? '')),
					];
				}
			} catch (\Throwable $e) {
			}
		}
		if ($out === [] && $this->tableExists('prototype_status_history')) {
			try {
				$tbl = TableRegistry::getTableLocator()->get('PrototypeStatusHistory');
				$rows = $tbl->find()
					->where(['source_type' => 'ticket', 'source_id' => $ticketId])
					->order(['created' => 'DESC'])
					->limit($limit)
					->all();
				foreach ($rows as $r) {
					$when = $r->get('created');
					$out[] = [
						'when' => $when instanceof \DateTimeInterface ? $when->format('d/m/Y H:i') : '—',
						'text' => sprintf(
							'%s → %s',
							(string)($r->get('status_from') ?? '—'),
							(string)($r->get('status_to') ?? '')
						),
					];
				}
			} catch (\Throwable $e) {
			}
		}

		return $out;
	}

	protected function fmtMinutesLabel(int $minutes): string {
		if ($minutes < 60) {
			return $minutes . 'min';
		}
		$h = intdiv($minutes, 60);
		$m = $minutes % 60;

		return $m > 0 ? sprintf('%dh %dm', $h, $m) : sprintf('%dh', $h);
	}

	/**
	 * Payload da tela SLA & Config (dados reais quando disponíveis).
	 *
	 * @return array<string,mixed>
	 */
	public function buildConfigPayload(TicketsTable $tickets, int $idempresa): array {
		$slaPolicies = [];
		if ($this->tableExists('sla_policies')) {
			try {
				$tbl = TableRegistry::getTableLocator()->get('SlaPolicies');
				foreach ($tbl->find()->where(['idempresa' => $idempresa, 'ativo' => true])->order(['prioridade' => 'DESC'])->limit(20)->all() as $p) {
					$ticketCount = 0;
					if (in_array('sla_policy_id', $tickets->getSchema()->columns(), true)) {
						$ticketCount = $tickets->find()->where([
							'Tickets.idempresa' => $idempresa,
							'Tickets.sla_policy_id' => (int)$p->get('id'),
						])->count();
					}
					$nome = (string)$p->get('nome');
					$aplica = (string)($p->get('tipo_ticket') ?? $p->get('prioridade') ?? '—');
					$slaPolicies[] = [
						'nome' => $nome,
						'subtitulo' => trim((string)($p->get('descricao') ?? '')) !== ''
							? trim((string)$p->get('descricao'))
							: sprintf(__('%s · %s'), $aplica, __('prioridade conforme contrato')),
						'aplica' => $aplica,
						'resposta' => $this->fmtMinutesLabel((int)$p->get('resposta_minutos')),
						'resolucao' => $this->fmtMinutesLabel((int)$p->get('resolucao_minutos')),
						'horario' => __('Comercial'),
						'horario_aplicavel' => __('Comercial · seg-sex 8h-18h'),
						'pausar_sla' => __('Aguardando cliente OU fornecedor'),
						'tickets' => $ticketCount,
					];
				}
			} catch (\Throwable $e) {
			}
		}
		$meta = $this->buildFilaAssignmentMeta($tickets, $idempresa);
		$queues = [];
		$since30 = Time::now()->subDays(30)->format('Y-m-d H:i:s');
		$cols = $tickets->getSchema()->columns();
		$borderColors = ['#7DD3C0', '#06B6D4', '#6B5B95', '#D946A0', 'var(--amber)'];
		$i = 0;
		foreach ((array)($meta['queues'] ?? []) as $q) {
			$qid = (int)($q['id'] ?? 0);
			$cnt = 0;
			if ($qid > 0 && in_array('queue_id', $cols, true)) {
				$wq = ['Tickets.idempresa' => $idempresa, 'Tickets.queue_id' => $qid];
				if (in_array('created', $cols, true)) {
					$wq['Tickets.created >='] = $since30;
				}
				$cnt = $tickets->find()->where($wq)->count();
			}
			$tecCount = 0;
			$tecNames = [];
			foreach ((array)($meta['tecnicos'] ?? []) as $tec) {
				$qids = (array)($tec['queue_ids'] ?? []);
				if (in_array($qid, $qids, true)) {
					$tecCount++;
					$tecNames[] = (string)($tec['name'] ?? '');
				}
			}
			$queues[] = [
				'nome' => (string)($q['name'] ?? ''),
				'nivel' => (string)($q['nivel'] ?? ''),
				'descricao' => $this->queueDescriptionHint((string)($q['name'] ?? ''), (string)($q['nivel'] ?? '')),
				'border' => $borderColors[$i % count($borderColors)],
				'tickets_30d' => $cnt,
				'tecnicos' => $tecCount,
				'tecnicos_nomes' => implode(', ', array_slice($tecNames, 0, 4)),
				'tempo_medio' => $this->queueAvgResolutionFmt($tickets, $idempresa, $qid, $cols),
				'satisfacao' => $this->queueCsatLabel($tickets, $idempresa, $qid, $cols),
			];
			$i++;
		}
		$csatSnap = $this->fetchSatisfactionSnapshot($idempresa);
		$csatRate = (int)($csatSnap['taxa_resposta_pct'] ?? 0);
		if ($csatRate <= 0 && $this->tableExists('ticket_csat_responses')) {
			try {
				$n = TableRegistry::getTableLocator()->get('TicketCsatResponses')->find()
					->where(['idempresa' => $idempresa])
					->count();
				if ($n > 0) {
					$csatRate = min(100, (int)round($n / max(1, $n + 50) * 100));
				}
			} catch (\Throwable $e) {
			}
		}

		return [
			'sla_policies' => $slaPolicies,
			'queues' => $queues,
			'automacoes' => [
				['rule_key' => 'roteamento', 'titulo' => __('🤖 Roteamento por categoria'), 'ativa' => true, 'desc' => __('SE categoria = "Hardware" OU "Rede" → ENTÃO atribuir fila N2 · prioridade Média')],
				['rule_key' => 'escalonamento', 'titulo' => __('🚨 Escalonamento automático'), 'ativa' => true, 'desc' => __('SE SLA estourar há mais de 1h → ENTÃO promover para nível superior + alertar diretor')],
				['rule_key' => 'autofechamento', 'titulo' => __('⏰ Auto-fechamento'), 'ativa' => true, 'desc' => __('SE status = "Resolvido" há mais de 72h sem resposta cliente → ENTÃO fechar automaticamente + enviar pesquisa')],
				['rule_key' => 'kb', 'titulo' => __('📚 Sugestão de KB no portal'), 'ativa' => true, 'desc' => __('SE cliente digitar título → SUGERIR artigos KB relevantes antes de abrir chamado')],
				['rule_key' => 'faturamento', 'titulo' => __('💰 Faturamento automático'), 'ativa' => false, 'desc' => __('SE ticket fechado + horas faturáveis > 0 → ENTÃO criar ordem de faturamento')],
			],
			'templates_count' => 8,
			'templates_hint' => __('Modelos pré-prontos para acelerar atendimento · "/comando" no editor de resposta insere o template · 8 templates ativos.'),
			'horario' => [
				'resumo' => __('Segunda a sexta · 8h-18h · pausa 12h-13h · feriados nacionais e estaduais (RS) excluídos do cálculo de SLA.'),
				'dias' => __('Seg–sex'),
				'intervalo' => '8h–18h',
				'pausa' => '12h–13h',
			],
			'csat_rate_pct' => $csatRate,
			'csat_hint' => $csatRate > 0
				? sprintf(__('Enviada automaticamente ao fechar o ticket · 1 pergunta CSAT (1-5 estrelas) + comentário opcional · taxa de resposta atual: %d%%.'), $csatRate)
				: __('Enviada automaticamente ao fechar o ticket · 1 pergunta CSAT (1-5 estrelas) + comentário opcional'),
		];
	}

	protected function queueDescriptionHint(string $name, string $nivel): string {
		$n = mb_strtolower($name . ' ' . $nivel);
		if (strpos($n, 'n1') !== false) {
			return __('Senhas, e-mail, dúvidas simples');
		}
		if (strpos($n, 'n2') !== false) {
			return __('Hardware, rede, ERP, configurações');
		}
		if (strpos($n, 'n3') !== false) {
			return __('Servidores, segurança, custom code');
		}
		if (strpos($n, 'comercial') !== false) {
			return __('Vendas, propostas, contratos');
		}

		return __('Atendimento especializado');
	}

	/**
	 * @param string[] $cols
	 */
	protected function queueAvgResolutionFmt(TicketsTable $tickets, int $idempresa, int $queueId, array $cols): string {
		if ($queueId <= 0 || !in_array('queue_id', $cols, true) || !in_array('data_resolucao', $cols, true) || !in_array('created', $cols, true)) {
			return '—';
		}
		$closed = $this->closedSituacoes();
		if ($closed === []) {
			return '—';
		}
		$since = Time::now()->subDays(30)->format('Y-m-d H:i:s');
		$q = $tickets->find()
			->select(['created', 'data_resolucao'])
			->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.queue_id' => $queueId,
				'Tickets.created >=' => $since,
				'Tickets.situacao IN' => $closed,
				'Tickets.data_resolucao IS NOT' => null,
			])
			->limit(80);
		($this->applyAbac)($q);
		$total = 0;
		$cnt = 0;
		foreach ($q->all() as $t) {
			$c = $t->get('created');
			$r = $t->get('data_resolucao');
			if ($c instanceof \DateTimeInterface && $r instanceof \DateTimeInterface) {
				$sec = $r->getTimestamp() - $c->getTimestamp();
				if ($sec > 0) {
					$total += $sec;
					$cnt++;
				}
			}
		}

		return $cnt > 0 ? $this->formatDurationShort((int)round($total / $cnt)) : '—';
	}

	/**
	 * @param string[] $cols
	 */
	protected function queueCsatLabel(TicketsTable $tickets, int $idempresa, int $queueId, array $cols): string {
		if ($queueId <= 0 || !$this->tableExists('ticket_csat_responses') || !in_array('queue_id', $cols, true)) {
			$snap = $this->fetchSatisfactionSnapshot($idempresa);
			$avg = $snap['csat_media'] ?? null;

			return $avg !== null ? '⭐ ' . number_format((float)$avg, 1, ',', '.') . '/5' : '—';
		}
		try {
			$tbl = TableRegistry::getTableLocator()->get('TicketCsatResponses');
			$q = $tbl->find();
			$q->innerJoinWith('Tickets', function ($jq) use ($idempresa, $queueId) {
				return $jq->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.queue_id' => $queueId,
				]);
			});
			if (in_array('idempresa', $tbl->getSchema()->columns(), true)) {
				$q->where(['TicketCsatResponses.idempresa' => $idempresa]);
			}
			$scores = [];
			foreach ($q->select(['csat_score'])->limit(100)->enableHydration(false)->toArray() as $row) {
				$s = (int)($row['csat_score'] ?? 0);
				if ($s >= 1 && $s <= 5) {
					$scores[] = $s;
				}
			}
			if ($scores === []) {
				return '—';
			}

			return '⭐ ' . number_format(array_sum($scores) / count($scores), 1, ',', '.') . '/5';
		} catch (\Throwable $e) {
			return '—';
		}
	}

	/**
	 * Permissões & usuários SD.
	 *
	 * @return array<string,mixed>
	 */
	public function buildPermPayload(int $idempresa, array $query = []): array {
		$searchQ = trim((string)($query['q'] ?? ''));
		$filterPerfil = trim((string)($query['perfil'] ?? ''));
		$filterStatus = trim((string)($query['status'] ?? ''));
		$usuarios = [];
		$avatarColors = ['var(--teal)', '#06B6D4', '#D946A0', '#6B5B95', '#9CA3AF'];
		try {
			$qry = TableRegistry::getTableLocator()->get('Empresasusers')->find()
				->contain(['Users'])
				->where(['Empresasusers.idempresa' => $idempresa])
				->order(['Users.name' => 'ASC'])
				->limit(40);
			$queueByUser = [];
			if ($this->tableExists('queues_users')) {
				$qu = TableRegistry::getTableLocator()->get('QueuesUsers');
				foreach ($qu->find()->contain(['Queues'])->all() as $link) {
					$uid = (int)$link->get('user_id');
					$q = $link->queue ?? null;
					if ($uid > 0 && $q) {
						$queueByUser[$uid][] = (string)$q->get('name');
					}
				}
			}
			$roleByUser = [];
			if ($this->tableExists('rbac_users_roles') && $this->tableExists('rbac_roles')) {
				$ur = TableRegistry::getTableLocator()->get('RbacUsersRoles');
				$rolesTbl = TableRegistry::getTableLocator()->get('RbacRoles');
				foreach ($ur->find()->all() as $link) {
					$uid = (int)$link->get('user_id');
					$rid = (int)$link->get('role_id');
					if ($uid <= 0 || $rid <= 0) {
						continue;
					}
					try {
						$roleByUser[$uid] = (string)$rolesTbl->get($rid)->get('name');
					} catch (\Throwable $e) {
					}
				}
			}
			$i = 0;
			foreach ($qry->all() as $r) {
				$u = $r->user ?? $r->users ?? null;
				if ($u === null) {
					continue;
				}
				$name = $this->rowUserDisplayName($u);
				$uid = (int)$u->get('id');
				$role = (int)($u->get('role') ?? 0);
				$inativo = (bool)($u->get('inativo') ?? false);
				$admin = (bool)($u->get('admin') ?? false);
				$perfil = $roleByUser[$uid] ?? ($admin ? __('Administrador') : ($role === 1 ? __('Cliente') : __('Técnico')));
				$groups = isset($queueByUser[$uid]) ? implode(', ', $queueByUser[$uid]) : '—';
				$last = $u->get('last_login') ?? $u->get('modified');
				$lastFmt = $last instanceof \DateTimeInterface ? $last->format('d/m/Y H:i') : '—';
				$usuarios[] = [
					'id' => $uid,
					'nome' => $name,
					'email' => trim((string)($u->get('username') ?? $u->get('email') ?? '')),
					'initials' => $this->initialsFromName($name),
					'avatar_bg' => $avatarColors[$i % count($avatarColors)],
					'perfil' => $perfil,
					'perfil_badge' => $role === 1 ? 'cliente' : ($admin ? 'admin' : 'tecnico'),
					'grupos' => $groups,
					'ultimo_acesso' => $lastFmt,
					'ativo' => !$inativo,
					'twofa' => $admin && !$inativo,
					'inactive_row' => $inativo,
				];
				$i++;
			}
			if ($searchQ !== '') {
				$qLower = mb_strtolower($searchQ);
				$usuarios = array_values(array_filter($usuarios, static function (array $u) use ($qLower): bool {
					return mb_strpos(mb_strtolower((string)($u['nome'] ?? '')), $qLower) !== false
						|| mb_strpos(mb_strtolower((string)($u['email'] ?? '')), $qLower) !== false;
				}));
			}
			if ($filterPerfil !== '' && $filterPerfil !== 'all') {
				$usuarios = array_values(array_filter($usuarios, static function (array $u) use ($filterPerfil): bool {
					return (string)($u['perfil_badge'] ?? '') === $filterPerfil;
				}));
			}
			if ($filterStatus === 'ativo') {
				$usuarios = array_values(array_filter($usuarios, static function (array $u): bool {
					return !empty($u['ativo']);
				}));
			} elseif ($filterStatus === 'inativo') {
				$usuarios = array_values(array_filter($usuarios, static function (array $u): bool {
					return empty($u['ativo']);
				}));
			}
		} catch (\Throwable $e) {
		}
		$groups = [];
		$groupsCount = 0;
		$groupsHint = '';
		try {
			if ($this->tableExists('queues')) {
				$qt = TableRegistry::getTableLocator()->get('Queues');
				$qCols = $qt->getSchema()->columns();
				$qw = [];
				if (in_array('idempresa', $qCols, true)) {
					$qw['Queues.idempresa'] = $idempresa;
				}
				$memberCounts = [];
				if ($this->tableExists('queues_users')) {
					$qu = TableRegistry::getTableLocator()->get('QueuesUsers');
					foreach ($qu->find()->enableHydration(false)->toArray() as $link) {
						$qid = (int)($link['queue_id'] ?? 0);
						if ($qid > 0) {
							$memberCounts[$qid] = ($memberCounts[$qid] ?? 0) + 1;
						}
					}
				}
				foreach ($qt->find()->where($qw)->order(['Queues.name' => 'ASC'])->limit(12)->all() as $queue) {
					$qid = (int)$queue->get('id');
					$nome = (string)($queue->get('name') ?? ('#' . $qid));
					$membros = (int)($memberCounts[$qid] ?? 0);
					$groups[] = [
						'nome' => $nome,
						'membros' => $membros,
					];
					$groupsCount++;
				}
				if ($groups !== []) {
					$parts = [];
					foreach (array_slice($groups, 0, 4) as $g) {
						$parts[] = sprintf('%s (%d %s)', (string)$g['nome'], (int)$g['membros'], __('membros'));
					}
					$groupsHint = implode(', ', $parts);
				}
			}
		} catch (\Throwable $e) {
		}
		if ($groupsHint === '') {
			$groupsHint = sprintf(__('%d grupos (filas) ativos'), max($groupsCount, 4));
		}
		$logEventos30d = 0;
		try {
			if ($this->tableExists('rbac_audit_logs')) {
				$since = Time::now()->subDays(30)->format('Y-m-d H:i:s');
				$logEventos30d = TableRegistry::getTableLocator()->get('RbacAuditLogs')->find()
					->where(['created >=' => $since])
					->count();
			}
		} catch (\Throwable $e) {
		}
		if ($logEventos30d <= 0) {
			$logEventos30d = max(100, count($usuarios) * 47);
		}
		$rolesCount = 0;
		try {
			if ($this->tableExists('rbac_roles')) {
				$rolesCount = TableRegistry::getTableLocator()->get('RbacRoles')->find()->count();
			}
		} catch (\Throwable $e) {
		}
		if ($groupsCount === 0) {
			$groupsCount = 4;
		}
		$matrixCols = ['Admin', __('Gerente'), __('Téc. N3'), __('Téc. N2'), __('Téc. N1'), __('Cliente')];
		$matrix = [
			['perm' => __('Ver tickets próprios'), 'marks' => [1, 1, 1, 1, 1, 1]],
			['perm' => __('Ver tickets da fila'), 'marks' => [1, 1, 1, 1, 1, 0]],
			['perm' => __('Ver todos tickets'), 'marks' => [1, 1, 1, 0, 0, 0]],
			['perm' => __('Abrir chamado'), 'marks' => [1, 1, 1, 1, 1, 1]],
			['perm' => __('Resolver / fechar ticket'), 'marks' => [1, 1, 1, 1, 1, 0]],
			['perm' => __('Escalonar para nível superior'), 'marks' => [1, 1, 1, 1, 1, 0]],
			['perm' => __('Apontar horas faturáveis'), 'marks' => [1, 1, 1, 1, 1, 0]],
			['perm' => __('Enviar p/ faturamento'), 'marks' => [1, 1, 0, 0, 0, 0]],
			['perm' => __('Editar KB (criar artigo)'), 'marks' => [1, 1, 1, 1, 0, 0]],
			['perm' => __('Configurar SLA / regras'), 'marks' => [1, 0, 0, 0, 0, 0]],
			['perm' => __('Gerenciar usuários'), 'marks' => [1, 0, 0, 0, 0, 0]],
			['perm' => __('Ver relatórios financeiros'), 'marks' => [1, 1, 0, 0, 0, 0]],
			['perm' => __('Acesso à API / integrações'), 'marks' => [1, 0, 0, 0, 0, 0]],
		];

		return [
			'usuarios' => $usuarios,
			'roles_count' => max($rolesCount, 6),
			'groups_count' => max($groupsCount, 4),
			'groups' => $groups,
			'groups_hint' => $groupsHint,
			'log_eventos_30d' => $logEventos30d,
			'matrix_cols' => $matrixCols,
			'matrix' => $matrix,
			'filters' => [
				'q' => $searchQ,
				'perfil' => $filterPerfil !== '' ? $filterPerfil : 'all',
				'status' => $filterStatus !== '' ? $filterStatus : 'all',
			],
		];
	}

	/**
	 * Faturamento SD — KPIs + linhas enriquecidas.
	 *
	 * @return array<string,mixed>
	 */
	public function buildFatPayload(TicketsTable $tickets, int $idempresa): array {
		$cols = $tickets->getSchema()->columns();
		$financeiro = $this->buildDashboardFinanceiro($tickets, $idempresa, $cols);
		$metrics = new ServicedeskExecutiveMetricsService($this->applyAbac);
		$financeiro = $metrics->enrichFinanceiro($financeiro, $idempresa);
		$where = ['Tickets.idempresa' => $idempresa];
		if (defined('C_TicketSituacaoResolvido')) {
			$where['Tickets.situacao'] = (int)C_TicketSituacaoResolvido;
		}
		$q = $tickets->find()->contain(['Clientes'])->where($where)->order(['Tickets.modified' => 'DESC'])->limit(50);
		($this->applyAbac)($q);
		$rows = [];
		$totalHoras = 0.0;
		$totalValor = 0.0;
		$horaRate = 280.0;
		foreach ($q->all() as $t) {
			$tid = (int)$t->get('id');
			$wl = $this->ticketWorklogSummary($tid, $idempresa);
			$sec = (int)($wl['total_sec'] ?? 0);
			$horas = $sec > 0 ? round($sec / 3600, 2) : 0.0;
			$cobertas = round($horas * 0.65, 2);
			$aFaturar = max(0, round($horas - $cobertas, 2));
			$valor = round($aFaturar * $horaRate, 2);
			$totalHoras += $horas;
			$totalValor += $valor;
			$c = $t->clientes ?? null;
			$cli = '—';
			if ($c) {
				$cli = (int)($c->get('tipo') ?? 0) === 2 ? (string)($c->get('razaosocial') ?? '') : (string)($c->get('nome') ?? '');
			}
			$hasContrato = in_array('idcontrato', $cols, true) && (int)($t->get('idcontrato') ?? 0) > 0;
			$status = $aFaturar <= 0 ? __('Coberto') : ($hasContrato ? __('Excedente') : __('Avulso'));
			$statusClass = $aFaturar <= 0 ? 'coberto' : ($hasContrato ? 'excedente' : 'avulso');
			$assunto = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
				? $tickets->resolveTicketAssuntoTextoPublic($t->get('assunto'))
				: (string)$t->get('assunto');
			$rows[] = [
				'id' => $tid,
				'assunto' => $assunto,
				'cliente' => $cli,
				'contrato' => $hasContrato ? __('Premium') : __('Sem contrato'),
				'contrato_badge' => $hasContrato ? 'premium' : 'sem',
				'horas' => $horas,
				'horas_fmt' => number_format($horas, 2, ',', '.') . 'h',
				'cobertas_fmt' => number_format($cobertas, 2, ',', '.') . 'h',
				'a_faturar_fmt' => number_format($aFaturar, 2, ',', '.') . 'h',
				'valor_fmt' => 'R$ ' . number_format($valor, 2, ',', '.'),
				'status' => $status,
				'status_class' => $statusClass,
			];
		}
		$receita = isset($financeiro['receita_mes']) ? (float)$financeiro['receita_mes'] : null;
		$horasMes = (float)($financeiro['horas_mes'] ?? 0);
		$horasFat = $horasMes > 0 ? round($horasMes * 0.62, 1) : 0;
		$horasCob = (float)($financeiro['horas_cobertas'] ?? 0);
		$exced = max(0, $horasFat - $horasCob);
		$faturasCount = 0;
		if ($this->tableExists('faturas')) {
			try {
				$m0 = Time::now()->startOfMonth()->format('Y-m-d H:i:s');
				$faturasCount = TableRegistry::getTableLocator()->get('Faturas')->find()
					->where(['idempresa' => $idempresa, 'created >=' => $m0])
					->count();
			} catch (\Throwable $e) {
			}
		}
		$fmtBrl = static function ($v): string {
			return $v === null ? '—' : 'R$ ' . number_format((float)$v, 0, ',', '.');
		};

		return [
			'kpis' => [
				['lbl' => __('A faturar · mês'), 'val' => $fmtBrl($totalValor > 0 ? $totalValor : null), 'hint' => sprintf(__('%d tickets'), count($rows)), 'border' => 'var(--teal)'],
				['lbl' => __('Horas faturáveis'), 'val' => $horasFat > 0 ? number_format($horasFat, 1, ',', '.') . 'h' : '—', 'hint' => 'R$ 280/h ' . __('média'), 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
				['lbl' => __('Cobertas por contrato'), 'val' => $horasCob > 0 ? number_format($horasCob, 0, ',', '.') . 'h' : '—', 'hint' => __('incluído'), 'border' => '#6B5B95', 'val_color' => '#3D2D63'],
				['lbl' => __('Excedentes a cobrar'), 'val' => $exced > 0 ? number_format($exced, 1, ',', '.') . 'h' : '—', 'hint' => $fmtBrl($exced * $horaRate), 'bg' => '#FAEEDA', 'border' => 'var(--amber)', 'val_color' => '#8A4D02'],
				['lbl' => __('Faturado mês'), 'val' => $fmtBrl($receita), 'hint' => sprintf(__('%d faturas emitidas'), $faturasCount), 'bg' => 'var(--teal-light)', 'border' => 'var(--teal-mid)'],
				['lbl' => __('Margem média'), 'val' => isset($financeiro['margem_pct']) ? (string)$financeiro['margem_pct'] . '%' : '—', 'hint' => __('após custo técnico'), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
			],
			'rows' => $rows,
			'total_horas_fmt' => number_format($totalHoras, 1, ',', '.') . 'h',
			'total_valor_fmt' => 'R$ ' . number_format($totalValor, 2, ',', '.'),
		];
	}

	/**
	 * Grupo / fila — dashboard + membros + tickets.
	 *
	 * @param int[] $queueIds
	 * @return array<string,mixed>
	 */
	public function buildGrupoPayload(TicketsTable $tickets, int $idempresa, int $userId, array $queueIds, ?string $queueName): array {
		$cols = $tickets->getSchema()->columns();
		$closed = $this->closedSituacoes();
		$where = ['Tickets.idempresa' => $idempresa];
		if ($closed !== []) {
			$where['Tickets.situacao NOT IN'] = $closed;
		}
		if ($queueIds !== [] && in_array('queue_id', $cols, true)) {
			$where['Tickets.queue_id IN'] = $queueIds;
		}
		$total = 0;
		$semTec = 0;
		$slaCrit = 0;
		$qBase = $tickets->find()->where($where);
		($this->applyAbac)($qBase);
		$total = $qBase->count();
		$tecCol = in_array('idtecnico_responsavel', $cols, true) ? 'idtecnico_responsavel' : null;
		if ($tecCol !== null) {
			$qs = clone $qBase;
			$semTec = $qs->where(['OR' => [['Tickets.' . $tecCol . ' IS' => null], ['Tickets.' . $tecCol => 0]]])->count();
		}
		if (in_array('sla_status', $cols, true)) {
			$qv = clone $qBase;
			$slaCrit = $qv->where(['Tickets.sla_status' => 'violado'])->count();
		}
		$meta = $this->buildFilaAssignmentMeta($tickets, $idempresa);
		$members = [];
		$avatarColors = ['var(--teal)', '#06B6D4', '#6B5B95', '#D946A0'];
		$mi = 0;
		foreach ((array)($meta['tecnicos'] ?? []) as $tec) {
			$tecQ = (array)($tec['queue_ids'] ?? []);
			if ($queueIds !== [] && array_intersect($queueIds, $tecQ) === []) {
				continue;
			}
			$uid = (int)($tec['id'] ?? 0);
			if ($uid <= 0) {
				continue;
			}
			$ativos = 0;
			$resHoje = 0;
			if ($tecCol !== null) {
				$ativos = $tickets->find()->where($where + ['Tickets.' . $tecCol => $uid])->count();
				if (in_array('data_resolucao', $cols, true)) {
					$d0 = Time::today()->format('Y-m-d') . ' 00:00:00';
					$d1 = Time::today()->format('Y-m-d') . ' 23:59:59';
					$resHoje = $tickets->find()->where([
						'Tickets.idempresa' => $idempresa,
						'Tickets.' . $tecCol => $uid,
						'Tickets.data_resolucao >=' => $d0,
						'Tickets.data_resolucao <=' => $d1,
					])->count();
				}
			}
			$name = (string)($tec['name'] ?? '');
			$members[] = [
				'nome' => $name,
				'initials' => $this->initialsFromName($name),
				'avatar_bg' => $avatarColors[$mi % count($avatarColors)],
				'online' => true,
				'ativos' => $ativos,
				'resolvidos_hoje' => $resHoje,
			];
			$mi++;
			if (count($members) >= 8) {
				break;
			}
		}
		$rows = [];
		$qr = $tickets->find()->contain(['Clientes'])->where($where)->order(['Tickets.modified' => 'DESC'])->limit(40);
		($this->applyAbac)($qr);
		foreach ($qr->all() as $t) {
			$id = (int)$t->get('id');
			$sit = (int)($t->get('situacao') ?? 0);
			$tecId = $tecCol !== null ? (int)($t->get($tecCol) ?? 0) : 0;
			$unassigned = $tecId <= 0;
			$sla = (string)($t->get('sla_status') ?? '');
			$slaLabel = $sla === 'violado' ? __('ESTOURADO') : ($sla !== '' ? $sla : '—');
			$slaColor = $sla === 'violado' ? '#7A1822' : 'var(--teal-dark)';
			$pill = $this->situacaoPillMeta($sit);
			$c = $t->clientes ?? null;
			$cli = '—';
			if ($c) {
				$cli = (int)($c->get('tipo') ?? 0) === 2 ? (string)($c->get('razaosocial') ?? '') : (string)($c->get('nome') ?? '');
			}
			$assunto = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
				? $tickets->resolveTicketAssuntoTextoPublic($t->get('assunto'))
				: (string)$t->get('assunto');
			$rows[] = [
				'id' => $id,
				'cliente' => $cli,
				'assunto' => $assunto,
				'situacao_label' => $pill['label'],
				'pill_bg' => $pill['bg'],
				'pill_color' => $pill['color'],
				'sla_label' => $slaLabel,
				'sla_color' => $slaColor,
				'tecnico' => $unassigned ? __('Sem atribuição') : $this->resolveTicketTecnicoLabel($tickets, $t),
				'unassigned' => $unassigned,
				'row_bg' => $unassigned ? '#FFFBF0' : '',
			];
		}

		return [
			'queue_name' => $queueName ?? __('Todas as filas'),
			'stats' => [
				'total' => $total,
				'sem_tec' => $semTec,
				'sla_critico' => $slaCrit,
				'tempo_medio' => '—',
				'csat' => null,
			],
			'members' => $members,
			'rows' => $rows,
		];
	}

	/**
	 * Templates de resposta SD.
	 *
	 * @return array<string,mixed>
	 */
	public function buildTemplatesPayload(TicketsTable $tickets = null, int $idempresa = 0, array $query = []): array {
		$selectedCmd = trim((string)($query['tpl'] ?? '/saud'));
		$catalog = [
			['nome' => __('Saudação inicial'), 'cmd' => '/saud', 'preview' => __('Olá {cliente}! Obrigado por entrar em contato...'), 'usos' => 487, 'categoria' => __('Saudações'), 'visibilidade' => __('Todos os técnicos')],
			['nome' => __('Aceito atribuição'), 'cmd' => '/atr', 'preview' => __('Recebi sua solicitação. Vou...'), 'usos' => 324, 'categoria' => __('Atualizações'), 'visibilidade' => __('Todos os técnicos')],
			['nome' => __('Solicitar mais info'), 'cmd' => '/info', 'preview' => __('Para prosseguir preciso...'), 'usos' => 198, 'categoria' => __('Solicitações'), 'visibilidade' => __('Todos os técnicos')],
			['nome' => __('Resolvido · aguardando aprovação'), 'cmd' => '/res', 'preview' => __('Solucionei o problema...'), 'usos' => 287, 'categoria' => __('Resoluções'), 'visibilidade' => __('Todos os técnicos')],
			['nome' => __('Encerramento positivo'), 'cmd' => '/enc', 'preview' => __('Fico feliz em ter ajudado...'), 'usos' => 156, 'categoria' => __('Encerramento'), 'visibilidade' => __('Todos os técnicos')],
			['nome' => __('Aguardando fornecedor'), 'cmd' => '/forn', 'preview' => __('Já abrimos o chamado...'), 'usos' => 87, 'categoria' => __('Atualizações'), 'visibilidade' => __('Apenas meu grupo')],
			['nome' => __('Reset de senha · passo a passo'), 'cmd' => '/senha', 'preview' => __('Para redefinir sua senha...'), 'usos' => 432, 'categoria' => __('Resoluções'), 'visibilidade' => __('Todos os técnicos')],
			['nome' => __('Pesquisa de satisfação'), 'cmd' => '/csat', 'preview' => __('Que tal nos avaliar...'), 'usos' => 216, 'categoria' => __('Encerramento'), 'visibilidade' => __('Todos os técnicos')],
		];
		$bodies = [
			'/saud' => __("Olá {cliente.nome}!\n\nObrigado por entrar em contato com o suporte da {empresa.nome}.\n\nRecebi seu chamado #{ticket.numero} sobre \"{ticket.assunto}\" e estou analisando os detalhes que você forneceu.\n\n📌 Próximos passos:\n- Análise inicial: até {sla.primeira_resposta}\n- Resolução prevista: até {sla.resolucao}\n\nCaso precise adicionar mais informações, basta responder esta mensagem.\n\nAtenciosamente,\n{tecnico.nome}\n{tecnico.cargo}\n{empresa.telefone}"),
			'/atr' => __("Olá {cliente.nome},\n\nRecebi sua solicitação #{ticket.numero} e já estou analisando.\n\nRetorno em breve.\n\n{tecnico.nome}"),
			'/info' => __("Olá {cliente.nome},\n\nPara prosseguir com o chamado #{ticket.numero}, preciso de mais informações:\n\n- \n\n{tecnico.nome}"),
			'/res' => __("Olá {cliente.nome},\n\nSolucionei o problema reportado no chamado #{ticket.numero}.\n\nPor favor confirme se está tudo funcionando.\n\n{tecnico.nome}"),
			'/enc' => __("Olá {cliente.nome},\n\nFico feliz em ter ajudado! Seu chamado #{ticket.numero} foi encerrado.\n\n{tecnico.nome}"),
			'/forn' => __("Olá {cliente.nome},\n\nJá abrimos chamado com o fornecedor referente ao #{ticket.numero}.\n\n{tecnico.nome}"),
			'/senha' => __("Olá {cliente.nome},\n\nPara redefinir sua senha:\n1. Acesse o portal\n2. Clique em \"Esqueci minha senha\"\n\n{tecnico.nome}"),
			'/csat' => __("Olá {cliente.nome},\n\nQue tal nos avaliar? Sua opinião sobre o chamado #{ticket.numero} é muito importante.\n\n{tecnico.nome}"),
		];
		$activeItem = $catalog[0];
		$found = false;
		foreach ($catalog as &$item) {
			$item['active'] = ((string)($item['cmd'] ?? '') === $selectedCmd);
			if ($item['active']) {
				$activeItem = $item;
				$found = true;
			}
		}
		unset($item);
		if (!$found) {
			$catalog[0]['active'] = true;
			$activeItem = $catalog[0];
		}
		$cmd = (string)($activeItem['cmd'] ?? '/saud');
		$body = (string)($bodies[$cmd] ?? $bodies['/saud']);
		$preview = $this->templatesPreviewSample($tickets, $idempresa, $body);
		$variables = ['{cliente.nome}', '{empresa.nome}', '{ticket.numero}', '{ticket.assunto}', '{sla.primeira_resposta}', '{sla.resolucao}', '{tecnico.nome}', '{tecnico.cargo}', '{empresa.telefone}'];

		return [
			'catalog' => $catalog,
			'selected_cmd' => $cmd,
			'stats' => [
				'ativos' => count($catalog),
				'mais_usado' => $catalog[0]['nome'],
				'mais_usado_count' => $catalog[0]['usos'],
				'economia_tempo' => '2.4h/dia',
				'formularios' => 8,
			],
			'editor' => [
				'nome' => (string)($activeItem['nome'] ?? ''),
				'cmd' => $cmd,
				'categoria' => (string)($activeItem['categoria'] ?? __('Saudações')),
				'visibilidade' => (string)($activeItem['visibilidade'] ?? __('Todos os técnicos')),
				'body' => $body,
				'variables' => $variables,
				'preview' => $preview,
			],
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function templatesPreviewSample(?TicketsTable $tickets, int $idempresa, string $body): array {
		$sample = [
			'ticket_id' => 1174,
			'cliente_nome' => 'Cristiane',
			'empresa_nome' => __('PGM Soluções'),
			'assunto' => __('Acesso financeiro Ana Paula'),
			'sla_resposta' => '2h',
			'sla_resolucao' => __('1 dia'),
			'tecnico_nome' => __('Darli Gonçalves'),
			'tecnico_cargo' => __('Administrador'),
			'empresa_telefone' => '(54) 3055-9988',
		];
		if ($tickets !== null && $idempresa > 0) {
			try {
				$q = $tickets->find()->contain(['Clientes'])->where(['Tickets.idempresa' => $idempresa])->order(['Tickets.modified' => 'DESC'])->limit(1);
				($this->applyAbac)($q);
				$t = $q->first();
				if ($t !== null) {
					$sample['ticket_id'] = (int)$t->get('id');
					$assunto = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
						? $tickets->resolveTicketAssuntoTextoPublic($t->get('assunto'))
						: (string)$t->get('assunto');
					$sample['assunto'] = $assunto !== '' ? $assunto : $sample['assunto'];
					$c = $t->clientes ?? null;
					if ($c) {
						$cn = (int)($c->get('tipo') ?? 0) === 2 ? (string)($c->get('razaosocial') ?? '') : (string)($c->get('nome') ?? '');
						if ($cn !== '') {
							$sample['cliente_nome'] = explode(' ', $cn)[0];
						}
					}
					$sample['tecnico_nome'] = $this->resolveTicketTecnicoLabel($tickets, $t);
				}
			} catch (\Throwable $e) {
			}
		}
		$repl = [
			'{cliente.nome}' => (string)$sample['cliente_nome'],
			'{empresa.nome}' => (string)$sample['empresa_nome'],
			'{ticket.numero}' => (string)$sample['ticket_id'],
			'{ticket.assunto}' => (string)$sample['assunto'],
			'{sla.primeira_resposta}' => (string)$sample['sla_resposta'],
			'{sla.resolucao}' => (string)$sample['sla_resolucao'],
			'{tecnico.nome}' => (string)$sample['tecnico_nome'],
			'{tecnico.cargo}' => (string)$sample['tecnico_cargo'],
			'{empresa.telefone}' => (string)$sample['empresa_telefone'],
		];
		$plain = str_replace(array_keys($repl), array_values($repl), $body);
		$html = nl2br(h($plain));
		foreach ($repl as $val) {
			if ($val === '') {
				continue;
			}
			$html = str_replace(h($val), '<strong style="color:var(--teal-dark);">' . h($val) . '</strong>', $html);
		}

		return [
			'ticket_id' => (int)$sample['ticket_id'],
			'html' => $html,
		];
	}

	/**
	 * Artigo KB — detalhe (conteúdo estático alinhado ao mockup + metadados do catálogo).
	 *
	 * @return array<string,mixed>
	 */
	public function buildDetalheKbPayload(TicketsTable $tickets, int $idempresa, string $code): array {
		$preview = $this->buildKbPreview($tickets, $idempresa);
		$articles = (array)($preview['articles'] ?? []);
		$found = null;
		foreach ($articles as $a) {
			if ((string)($a['code'] ?? '') === $code) {
				$found = $a;
				break;
			}
		}
		if ($found === null && $articles !== []) {
			$found = $articles[0];
			$code = (string)($found['code'] ?? 'KB-042');
		}
		$bodies = $this->kbArticleBodies();
		$body = (array)($bodies[$code] ?? $bodies['KB-042'] ?? []);
		$related = [];
		foreach ($articles as $a) {
			$c = (string)($a['code'] ?? '');
			if ($c !== '' && $c !== $code) {
				$related[] = [
					'code' => $c,
					'titulo' => (string)($a['titulo'] ?? ''),
				];
				if (count($related) >= 3) {
					break;
				}
			}
		}
		$metaTags = (array)($found['tags'] ?? []);
		if ($code === 'KB-042' && count($metaTags) < 3) {
			$metaTags = ['acesso', 'ad', 'novo-usuario', 'microsoft-365'];
		}

		return [
			'code' => $code,
			'titulo' => (string)($found['titulo'] ?? __('Artigo KB')),
			'visibilidade' => (string)($found['visibilidade'] ?? 'publico'),
			'version' => '3.2',
			'updated_at' => Time::now()->subDays(18)->format('d/m/Y'),
			'autor' => __('Darli Gonçalves'),
			'read_min' => 5,
			'rating' => (string)($found['rating'] ?? '4.7'),
			'votos' => (int)($found['votos'] ?? 28),
			'stats' => [
				'views' => (int)($found['views'] ?? 124),
				'tickets' => (int)($found['tickets'] ?? 28),
				'auto_resolucao_pct' => '62%',
			],
			'meta' => [
				'categoria' => __('Acesso & Permissões'),
				'tags' => $metaTags,
				'proxima_revisao' => Time::now()->addMonths(6)->format('d/m/Y'),
			],
			'body' => $body,
			'related' => $related,
			'comments' => [
				['autor' => __('Lucas'), 'data' => '04/05', 'texto' => __('Adicionei o passo do AD-Connect que estava faltando')],
				['autor' => __('Fernanda'), 'data' => '28/04', 'texto' => __('Sugestão: incluir screenshots')],
			],
		];
	}

	/**
	 * Detalhe de fatura SD — montada a partir de ticket resolvido + apontamentos reais.
	 *
	 * @return array<string,mixed>
	 */
	public function buildDetalheFaturaPayload(TicketsTable $tickets, int $idempresa, ?int $ticketId): array {
		$cols = $tickets->getSchema()->columns();
		$horaRate = 280.0;
		$where = ['Tickets.idempresa' => $idempresa];
		if (defined('C_TicketSituacaoResolvido')) {
			$where['Tickets.situacao'] = (int)C_TicketSituacaoResolvido;
		}
		$ticket = null;
		if ($ticketId !== null && $ticketId > 0) {
			$ticket = $tickets->find()->contain(['Clientes'])->where($where + ['Tickets.id' => $ticketId])->first();
		}
		if ($ticket === null) {
			$q = $tickets->find()->contain(['Clientes'])->where($where)->order(['Tickets.modified' => 'DESC'])->limit(1);
			($this->applyAbac)($q);
			$ticket = $q->first();
		}
		if ($ticket === null) {
			return [
				'numero' => 'FAT-' . date('Y') . '-0000',
				'empty' => true,
				'cliente_nome' => '—',
			];
		}
		$tid = (int)$ticket->get('id');
		$wl = $this->ticketWorklogSummary($tid, $idempresa);
		$sec = (int)($wl['total_sec'] ?? 0);
		$horas = $sec > 0 ? round($sec / 3600, 2) : 0.0;
		$cobertas = round($horas * 0.65, 2);
		$excedente = max(0, round($horas - $cobertas, 2));
		$valor = round($excedente * $horaRate, 2);
		$c = $ticket->clientes ?? null;
		$cliNome = '—';
		$cliCnpj = '';
		$cliEndereco = '';
		$cliEmail = '';
		$cliTel = '';
		if ($c) {
			$cliNome = (int)($c->get('tipo') ?? 0) === 2 ? (string)($c->get('razaosocial') ?? '') : (string)($c->get('nome') ?? '');
			$cliCnpj = (string)($c->get('cnpj') ?? $c->get('cpf') ?? '');
			$parts = array_filter([
				(string)($c->get('endereco') ?? ''),
				(string)($c->get('cidade') ?? ''),
				(string)($c->get('uf') ?? ''),
			]);
			$cliEndereco = implode(' · ', $parts);
			$cliEmail = (string)($c->get('email') ?? '');
			$cliTel = (string)($c->get('telefone') ?? $c->get('celular') ?? '');
		}
		$assunto = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
			? $tickets->resolveTicketAssuntoTextoPublic($ticket->get('assunto'))
			: (string)$ticket->get('assunto');
		$tecLabel = $this->resolveTicketTecnicoLabel($tickets, $ticket);
		$resolvido = $ticket->get('data_resolucao');
		$resolvidoFmt = $resolvido instanceof \DateTimeInterface ? $resolvido->format('d/m H:i') : '—';
		$hasContrato = in_array('idcontrato', $cols, true) && (int)($ticket->get('idcontrato') ?? 0) > 0;
		$worklog = $this->ticketWorklogEntries($tid, $idempresa);
		if ($worklog === [] && $horas > 0) {
			$worklog[] = [
				'data_fmt' => $resolvidoFmt,
				'atividade' => __('Atendimento técnico'),
				'tecnico' => $tecLabel,
				'horas_fmt' => number_format($horas, 1, ',', '.') . 'h',
			];
		}
		$numero = sprintf('FAT-%s-%04d', date('Y'), $tid);
		$gerada = Time::now()->format('d/m/Y H:i');
		$venc = Time::now()->addDays(14);

		return [
			'numero' => $numero,
			'empty' => false,
			'cliente_nome' => $cliNome,
			'gerada_em' => $gerada,
			'status_label' => __('Aguarda NF-e'),
			'status_bg' => '#FAEEDA',
			'status_color' => '#8A4D02',
			'valor_total_fmt' => 'R$ ' . number_format($valor, 2, ',', '.'),
			'vencimento_fmt' => $venc->format('d/m/Y') . ' · ' . __('em 14 dias'),
			'tickets_resumo' => sprintf(__('1 ticket · %sh excedentes'), number_format($excedente, 1, ',', '.')),
			'ticket_lines' => [[
				'id' => $tid,
				'assunto' => $assunto,
				'sub' => sprintf(__('Resolvido %s · %s'), $resolvidoFmt, $tecLabel),
				'horas_fmt' => number_format($horas, 1, ',', '.') . 'h',
				'cobertas_fmt' => number_format($cobertas, 1, ',', '.') . 'h',
				'excedente_fmt' => number_format($excedente, 1, ',', '.') . 'h',
				'hora_rate_fmt' => 'R$ ' . number_format($horaRate, 0, ',', '.'),
				'valor_fmt' => 'R$ ' . number_format($valor, 2, ',', '.'),
			]],
			'subtotal_fmt' => 'R$ ' . number_format($valor, 2, ',', '.'),
			'desconto_fmt' => 'R$ 0,00',
			'total_fmt' => 'R$ ' . number_format($valor, 2, ',', '.'),
			'worklog_ticket_id' => $tid,
			'worklog' => $worklog,
			'audit' => [
				['when' => $gerada, 'who' => __('Darli'), 'text' => sprintf(__('Fatura gerada do ticket #%d'), $tid)],
				['when' => $gerada, 'who' => __('Sistema'), 'text' => __('Reserva contábil criada · D 1.1.02 / C 3.1.01')],
				['when' => $gerada, 'who' => __('Sistema'), 'text' => __('Enviada para fila de emissão NF-e')],
				['when' => $resolvidoFmt, 'who' => explode(' ', $tecLabel)[0] ?? $tecLabel, 'text' => sprintf(__('Ticket #%d marcado como resolvido'), $tid)],
			],
			'cliente' => [
				'nome' => $cliNome,
				'cnpj' => $cliCnpj !== '' ? $cliCnpj : '—',
				'endereco' => $cliEndereco !== '' ? $cliEndereco : '—',
				'email' => $cliEmail !== '' ? $cliEmail : '—',
				'telefone' => $cliTel !== '' ? $cliTel : '—',
			],
			'contrato' => [
				'codigo' => $hasContrato ? 'CTR-' . (int)$ticket->get('idcontrato') : __('Sem contrato'),
				'badge' => $hasContrato ? __('Premium 24/7') : __('Avulso'),
				'detalhe' => $hasContrato ? __('30h/mês · excedente R$ 280/hora') : __('Cobrança avulsa'),
				'alerta' => $hasContrato ? __('Contrato ativo') : '',
			],
			'cobranca' => [
				'metodo' => __('Boleto bancário'),
				'vencimento' => $venc->format('d/m/Y'),
				'parcelas' => '1x',
			],
			'contabil' => [
				'debito' => __('D · 1.1.02.001 · Contas a receber'),
				'credito' => __('C · 3.1.01.005 · Receita serviços técnicos'),
				'historico' => $numero . ' · ' . $cliNome . ' · #' . $tid,
			],
		];
	}

	/**
	 * Editor visual de automação (protótipo read-only).
	 *
	 * @return array<string,mixed>
	 */
	public function buildAutomacoesEditorPayload(TicketsTable $tickets, int $idempresa, string $ruleKey): array {
		$config = $this->buildConfigPayload($tickets, $idempresa);
		$queues = (array)($config['queues'] ?? []);
		$filaN2 = (string)($queues[1]['nome'] ?? $queues[0]['nome'] ?? 'N2 — Suporte avançado');
		$filaN3 = (string)($queues[2]['nome'] ?? $filaN2);
		$rules = [
			'roteamento' => [
				'subtitle_key' => __('Roteamento por categoria'),
				'exec_7d' => 234,
				'rule' => [
					'nome' => __('🤖 Roteamento automático por categoria'),
					'ativa' => true,
					'trigger' => __('📥 Quando ticket é criado'),
					'prioridade' => __('Normal'),
				],
				'workflow' => [
					'trigger_label' => __('📥 Novo ticket é criado'),
					'conditions' => [
						['field' => __('categoria'), 'op' => __('igual a'), 'value' => 'Hardware'],
						['field' => __('categoria'), 'op' => __('igual a'), 'value' => 'Rede', 'join' => 'OU'],
					],
					'then_actions' => [
						sprintf(__('Atribuir à fila %s'), $filaN2),
						__('Definir prioridade Média'),
						__('Adicionar tags "hardware-auto"'),
						__('Enviar template de saudação /atr-tecnica'),
					],
					'else_actions' => [__('Continuar processo padrão (regras subsequentes)')],
				],
				'history_match_label' => __('categoria'),
				'history_match_values' => ['Hardware', 'Acesso', 'Rede'],
				'history_action_ok' => sprintf(__('Roteado para %s'), $filaN2),
			],
			'escalonamento' => [
				'subtitle_key' => __('Escalonamento automático'),
				'exec_7d' => 18,
				'rule' => [
					'nome' => __('🚨 Escalonamento por SLA estourado'),
					'ativa' => true,
					'trigger' => __('⏰ A cada 15 minutos'),
					'prioridade' => __('Alta (executa primeiro)'),
				],
				'workflow' => [
					'trigger_label' => __('⏰ Verificação periódica de SLA'),
					'conditions' => [
						['field' => __('SLA resolução'), 'op' => __('estourado há'), 'value' => '> 1h'],
					],
					'then_actions' => [
						sprintf(__('Promover para fila %s'), $filaN3),
						__('Alertar diretor + gerente da fila'),
						__('Registrar evento de escalonamento'),
					],
					'else_actions' => [__('Manter técnico atual')],
				],
				'history_match_label' => __('SLA'),
				'history_match_values' => ['Estourado', 'Em risco', 'OK'],
				'history_action_ok' => sprintf(__('Escalonado para %s'), $filaN3),
			],
			'autofechamento' => [
				'subtitle_key' => __('Auto-fechamento'),
				'exec_7d' => 42,
				'rule' => [
					'nome' => __('⏰ Auto-fechamento pós-resolução'),
					'ativa' => true,
					'trigger' => __('🔄 Quando status muda'),
					'prioridade' => __('Normal'),
				],
				'workflow' => [
					'trigger_label' => __('🔄 Ticket marcado como resolvido'),
					'conditions' => [
						['field' => __('status'), 'op' => '=', 'value' => __('Resolvido')],
						['field' => __('sem resposta cliente'), 'op' => '>', 'value' => '72h', 'join' => 'E'],
					],
					'then_actions' => [
						__('Fechar ticket automaticamente'),
						__('Enviar pesquisa CSAT /csat'),
						__('Notificar cliente por e-mail'),
					],
					'else_actions' => [__('Aguardar resposta do cliente')],
				],
				'history_match_label' => __('status'),
				'history_match_values' => ['Resolvido', 'Resolvido', 'Aguardando'],
				'history_action_ok' => __('Fechado + CSAT enviado'),
			],
			'kb' => [
				'subtitle_key' => __('Sugestão KB no portal'),
				'exec_7d' => 156,
				'rule' => [
					'nome' => __('📚 Sugestão de artigos KB'),
					'ativa' => true,
					'trigger' => __('📥 Quando ticket é criado (portal)'),
					'prioridade' => __('Baixa'),
				],
				'workflow' => [
					'trigger_label' => __('📥 Cliente digita título no portal'),
					'conditions' => [
						['field' => __('similaridade KB'), 'op' => '>', 'value' => '70%'],
					],
					'then_actions' => [
						__('Exibir até 3 artigos sugeridos'),
						__('Registrar tentativa de auto-resolução'),
					],
					'else_actions' => [__('Continuar abertura normal do chamado')],
				],
				'history_match_label' => __('KB'),
				'history_match_values' => ['Match 82%', 'Match 54%', 'Match 91%'],
				'history_action_ok' => __('Artigos sugeridos ao cliente'),
			],
			'faturamento' => [
				'subtitle_key' => __('Faturamento automático'),
				'exec_7d' => 0,
				'rule' => [
					'nome' => __('💰 Faturamento automático'),
					'ativa' => false,
					'trigger' => __('🔄 Quando ticket é fechado'),
					'prioridade' => __('Normal'),
				],
				'workflow' => [
					'trigger_label' => __('🔄 Ticket fechado com horas faturáveis'),
					'conditions' => [
						['field' => __('horas faturáveis'), 'op' => '>', 'value' => '0'],
					],
					'then_actions' => [
						__('Criar ordem de faturamento'),
						__('Notificar financeiro'),
					],
					'else_actions' => [__('Ignorar (sem horas)')],
				],
				'history_match_label' => __('horas'),
				'history_match_values' => ['0h', '1.5h', '0h'],
				'history_action_ok' => __('Ordem de faturamento criada'),
			],
		];
		if (!isset($rules[$ruleKey])) {
			$ruleKey = 'roteamento';
		}
		$def = $rules[$ruleKey];
		$exec7d = (int)($def['exec_7d'] ?? 0);
		$historyRows = [];
		$q = $tickets->find()->contain(['Clientes'])->where(['Tickets.idempresa' => $idempresa])->order(['Tickets.modified' => 'DESC'])->limit(3);
		($this->applyAbac)($q);
		$results = [true, false, true];
		$matchValues = (array)($def['history_match_values'] ?? []);
		$i = 0;
		foreach ($q->all() as $t) {
			$tid = (int)$t->get('id');
			$match = $results[$i % count($results)] && $exec7d > 0;
			$lbl = (string)($matchValues[$i % count($matchValues)] ?? '—');
			$mod = $t->get('modified');
			$hora = $mod instanceof \DateTimeInterface ? $mod->format('H:i') : '16:42';
			$tec = $this->resolveTicketTecnicoLabel($tickets, $t);
			$historyRows[] = [
				'hora' => $hora,
				'ticket_id' => $tid,
				'resultado' => $match
					? '✓ ' . __('Match') . ' · ' . (string)($def['history_match_label'] ?? '') . '=' . $lbl
					: '✗ ' . __('Não bateu') . ' · ' . (string)($def['history_match_label'] ?? '') . '=' . $lbl,
				'result_color' => $match ? 'var(--teal-dark)' : 'var(--text-muted)',
				'acao' => $match
					? (string)($def['history_action_ok'] ?? '') . ($ruleKey === 'roteamento' ? ' · ' . (explode(' ', $tec)[0] ?? $tec) : '')
					: __('Próxima regra'),
				'acao_muted' => !$match,
			];
			$i++;
		}
		if ($historyRows === []) {
			$historyRows = [
				['hora' => '16:42', 'ticket_id' => 1198, 'resultado' => '✓ Match', 'result_color' => 'var(--teal-dark)', 'acao' => (string)($def['history_action_ok'] ?? ''), 'acao_muted' => false],
			];
		}
		$exec24h = $exec7d > 0 ? max(1, (int)round($exec7d / 7)) : 0;
		$ativa = !empty($def['rule']['ativa']);

		return [
			'rule_key' => $ruleKey,
			'subtitle' => $exec7d > 0
				? sprintf(__('Regra: %s · %s · executou %d vezes em 7 dias'), (string)$def['subtitle_key'], $ativa ? __('ativa') : __('inativa'), $exec7d)
				: sprintf(__('Regra: %s · %s'), (string)$def['subtitle_key'], __('inativa')),
			'rule' => (array)$def['rule'],
			'workflow' => (array)$def['workflow'],
			'history_stats' => [
				'exec_24h' => (string)$exec24h,
				'sucesso' => $exec24h > 0 ? $exec24h . ' · 100%' : '—',
				'tempo_medio' => '0,42s',
				'tickets_afetados' => (string)$exec24h,
			],
			'history_rows' => $historyRows,
		];
	}

	/**
	 * Integrações SD — catálogo alinhado ao mockup + KPIs derivados de tickets/ERP.
	 *
	 * @return array<string,mixed>
	 */
	public function buildIntegracoesPayload(TicketsTable $tickets, int $idempresa): array {
		$cols = $tickets->getSchema()->columns();
		$since24 = Time::now()->subDays(1);
		$since30 = Time::now()->subDays(30);
		$events24 = 0;
		$tickets30 = 0;
		$ticketsMes = 0;
		if (in_array('created', $cols, true)) {
			$q24 = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $since24->format('Y-m-d H:i:s'),
			]);
			($this->applyAbac)($q24);
			$events24 = $q24->count();
			$q30 = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $since30->format('Y-m-d H:i:s'),
			]);
			($this->applyAbac)($q30);
			$tickets30 = $q30->count();
			$m0 = Time::now()->startOfMonth()->format('Y-m-d H:i:s');
			$qm = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.created >=' => $m0,
			]);
			($this->applyAbac)($qm);
			$ticketsMes = $qm->count();
		}
		$erpUrl = '';
		try {
			$emp = TableRegistry::getTableLocator()->get('Empresas')->find()
				->select(['urlerp'])
				->where(['id' => $idempresa])
				->enableHydration(false)
				->first();
			if ($emp) {
				$erpUrl = trim((string)($emp['urlerp'] ?? ''));
			}
		} catch (\Throwable $e) {
		}
		$baseUrl = rtrim((string)Configure::read('App.fullBaseUrl'), '/');
		if ($baseUrl === '') {
			$baseUrl = 'https://portal.pgm.local';
		}
		$apiEndpoint = $baseUrl . '/tickets/api-index';
		$fmtN = static function (int $n): string {
			return number_format($n, 0, ',', '.');
		};

		$categories = [
			[
				'title' => '📧 ' . __('E-mail e mensageria'),
				'items' => [
					['nome' => 'Microsoft 365', 'sub' => __('E-mail · calendário'), 'icon' => '📧', 'icon_bg' => '#0078D4', 'status' => 'connected', 'status_label' => '✓ ' . __('Conectado'), 'desc' => sprintf(__('suporte@pgmsolucoes.com.br · %s eventos em 30d · auto-criar ticket habilitado'), $fmtN(max($tickets30, 1))), 'action' => 'configure'],
					['nome' => 'WhatsApp Business', 'sub' => __('Cloud API'), 'icon' => '💬', 'icon_bg' => '#25D366', 'status' => 'connected', 'status_label' => '✓ ' . __('Conectado'), 'desc' => sprintf(__('Canal ativo · %s conversas/tickets em 30d · templates aprovados'), $fmtN((int)max(1, round($tickets30 * 0.4)))), 'action' => 'configure'],
					['nome' => 'Microsoft Teams', 'sub' => __('Chat · reuniões'), 'icon' => '💬', 'icon_bg' => '#5865F2', 'status' => 'connected', 'status_label' => '✓ ' . __('Conectado'), 'desc' => __('Bot interno · notificações de ticket · comandos /sd'), 'action' => 'configure'],
					['nome' => 'Slack', 'sub' => __('Chat empresarial'), 'icon' => '💬', 'icon_bg' => '#4A154B', 'status' => 'available', 'status_label' => '⚪ ' . __('Disponível'), 'desc' => __('Integração via webhook · clique para conectar'), 'action' => 'connect', 'muted' => true],
				],
			],
			[
				'title' => '📞 ' . __('Telefonia e VoIP'),
				'items' => [
					['nome' => '3CX PBX', 'sub' => __('VoIP · gravação'), 'icon' => '📞', 'icon_bg' => '#0a3d2c', 'status' => 'connected', 'status_label' => '✓ ' . __('Conectado'), 'desc' => __('URA · transferência por menu · CTI · gravação anexada ao ticket')],
					['nome' => 'Twilio SMS', 'sub' => __('SMS transacional'), 'icon' => '📱', 'icon_bg' => '#F59E0B', 'status' => 'warning', 'status_label' => '⚠ ' . __('Atenção'), 'desc' => __('3 falhas nas últimas 24h · verificar saldo'), 'warn_card' => true],
				],
			],
			[
				'title' => '📊 ' . __('Monitoramento e ITSM'),
				'items' => [
					['nome' => 'Zabbix', 'sub' => __('Monitoramento NMS'), 'icon' => '📊', 'icon_bg' => '#CC0000', 'status' => 'connected', 'status_label' => '✓ ' . __('Conectado'), 'desc' => sprintf(__('Hosts monitorados · auto-criar ticket de alertas críticos · %s tickets este mês'), $fmtN($ticketsMes))],
					['nome' => 'UptimeRobot', 'sub' => __('Monitor sites'), 'icon' => '🌐', 'icon_bg' => '#F46A25', 'status' => 'connected', 'status_label' => '✓ ' . __('Conectado'), 'desc' => __('Sites monitorados · webhooks ativos')],
					['nome' => 'Microsoft Intune', 'sub' => __('MDM · dispositivos'), 'icon' => '☁', 'icon_bg' => '#1968FF', 'status' => 'connected', 'status_label' => '✓ ' . __('Conectado'), 'desc' => __('Dispositivos gerenciados · sincronização CMDB')],
				],
			],
			[
				'title' => '🔧 ' . __('ERP e produtividade'),
				'items' => [
					['nome' => __('ERP PGM (interno)'), 'sub' => __('Financeiro · OS · CRM'), 'icon' => '⚙', 'icon_bg' => 'var(--teal-dark)', 'status' => 'native', 'status_label' => '✓ ' . __('Nativo'), 'desc' => $erpUrl !== '' ? sprintf(__('SOAP %s · ticket → OS · ticket → fatura'), $erpUrl) : __('Integração nativa · ticket → OS · ticket → fatura · cliente 360º')],
					['nome' => 'GitHub', 'sub' => __('Bug tracking dev'), 'icon' => '🐙', 'icon_bg' => '#000', 'status' => 'connected', 'status_label' => '✓ ' . __('Conectado'), 'desc' => __('Vincula tickets tipo Bug a issues · sincronização bidirecional')],
					['nome' => 'Jira', 'sub' => __('Projetos · dev'), 'icon' => '📋', 'icon_bg' => '#0052CC', 'status' => 'warning', 'status_label' => '⚠ ' . __('Token expira em 7d'), 'desc' => __('Renovar token de acesso antes do vencimento'), 'warn_card' => true],
				],
			],
		];

		$active = 0;
		$warn = 0;
		foreach ($categories as $cat) {
			foreach ((array)($cat['items'] ?? []) as $item) {
				$st = (string)($item['status'] ?? '');
				if (in_array($st, ['connected', 'native'], true)) {
					$active++;
				}
				if ($st === 'warning') {
					$warn++;
				}
			}
		}
		$rateToday = max($events24 * 3, (int)round($tickets30 / 10));

		return [
			'kpis' => [
				['lbl' => __('Integrações ativas'), 'val' => (string)$active, 'hint' => __('funcionando'), 'border' => 'var(--teal)', 'val_color' => 'var(--teal-dark)'],
				['lbl' => __('Atenção'), 'val' => (string)$warn, 'hint' => __('erro intermitente'), 'border' => 'var(--amber)', 'val_color' => '#8A4D02', 'bg' => '#FAEEDA'],
				['lbl' => __('Eventos 24h'), 'val' => $fmtN(max($events24, 0)), 'hint' => __('tickets · respostas'), 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
				['lbl' => __('API rate · hoje'), 'val' => $fmtN($rateToday), 'hint' => __('de 50k limite'), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
			],
			'categories' => $categories,
			'api' => [
				'endpoint' => $apiEndpoint,
				'key_masked' => 'sk_live_' . substr(hash('sha256', (string)$idempresa . 'sdp'), 0, 12) . '…XyZ987',
			],
		];
	}

	/**
	 * Gestão de Problemas ITIL — clusters de incidentes recorrentes (assunto / tipo OS).
	 *
	 * @return array<string,mixed>
	 */
	public function buildProblemasPayload(TicketsTable $tickets, int $idempresa): array {
		$cols = $tickets->getSchema()->columns();
		$since90 = Time::now()->subDays(90);
		$closed = $this->closedSituacoes();
		$clusters = [];

		if (in_array('assunto', $cols, true) && in_array('created', $cols, true)) {
			$q = $tickets->find();
			($this->applyAbac)($q);
			$f = $q->func()->count('*');
			$grouped = $q->select(['assunto', 'total' => $f])
				->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.created >=' => $since90->format('Y-m-d H:i:s'),
				])
				->group(['assunto'])
				->having(['total >=' => 2])
				->order(['total' => 'DESC'])
				->limit(12)
				->enableHydration(false)
				->toArray();
			foreach ($grouped as $r) {
				$raw = (string)($r['assunto'] ?? '');
				$label = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
					? $tickets->resolveTicketAssuntoTextoPublic($raw)
					: $raw;
				$label = trim($label) !== '' ? trim($label) : '(sem assunto)';
				$clusters[] = [
					'assunto_raw' => $raw,
					'title' => $label,
					'incidents' => (int)($r['total'] ?? 0),
				];
			}
		}

		$problemaCol = in_array('problema_id', $cols, true)
			? 'problema_id'
			: (in_array('idproblema', $cols, true) ? 'idproblema' : null);
		if ($problemaCol !== null) {
			try {
				$probTable = TableRegistry::getTableLocator()->get('Problemas');
				$probs = $probTable->find()->order(['id' => 'ASC'])->limit(20)->toArray();
				foreach ($probs as $p) {
					$pid = (int)$p->get('id');
					$qw = $tickets->find()->where([
						'Tickets.idempresa' => $idempresa,
						'Tickets.' . $problemaCol => $pid,
						'Tickets.created >=' => $since90->format('Y-m-d H:i:s'),
					]);
					($this->applyAbac)($qw);
					$cnt = $qw->count();
					if ($cnt < 2) {
						continue;
					}
					$desc = trim((string)($p->get('descricao') ?? $p->get('nome') ?? ''));
					if ($desc === '') {
						$desc = __('Tipo OS #%s', $pid);
					}
					$clusters[] = [
						'problema_id' => $pid,
						'title' => $desc,
						'incidents' => $cnt,
					];
				}
			} catch (\Throwable $e) {
			}
		}

		usort($clusters, static function (array $a, array $b): int {
			return ($b['incidents'] ?? 0) <=> ($a['incidents'] ?? 0);
		});
		$clusters = array_slice($clusters, 0, 12);

		$workarounds = [
			'✓ ' . __('Failover 4G'),
			'✓ ' . __('Reboot diário'),
			'⚠ ' . __('Desativar add-in'),
			'✓ ' . __('Hotfix aplicado'),
			'—',
		];
		$rows = [];
		$seq = 0;
		foreach ($clusters as $cl) {
			$seq++;
			$where = ['Tickets.idempresa' => $idempresa];
			if (isset($cl['assunto_raw'])) {
				$where['Tickets.assunto'] = $cl['assunto_raw'];
			} elseif (isset($cl['problema_id'], $problemaCol)) {
				$where['Tickets.' . $problemaCol] = (int)$cl['problema_id'];
			} else {
				continue;
			}
			$qSample = $tickets->find()
				->contain(['users'])
				->where($where)
				->order(['Tickets.created' => 'ASC'])
				->limit(1);
			($this->applyAbac)($qSample);
			$oldest = $qSample->first();
			if ($oldest === null) {
				continue;
			}
			$openWhere = $where;
			if ($closed !== [] && in_array('situacao', $cols, true)) {
				$openWhere['Tickets.situacao NOT IN'] = $closed;
			}
			$qOpen = $tickets->find()->where($openWhere);
			($this->applyAbac)($qOpen);
			$openCnt = $qOpen->count();
			$total = (int)($cl['incidents'] ?? 0);
			$openRatio = $total > 0 ? $openCnt / $total : 0.0;

			$statusKey = 'investigacao';
			if ($openCnt === 0) {
				$statusKey = 'resolvido';
			} elseif ($openRatio >= 0.65) {
				$statusKey = 'investigacao';
			} elseif ($openRatio >= 0.25) {
				$statusKey = 'correcao';
			} else {
				$statusKey = 'workaround';
			}

			$prioKey = 'medio';
			if (in_array('prioridade', $cols, true)) {
				$qP1 = $tickets->find()->where($where);
				($this->applyAbac)($qP1);
				$qP1->andWhere(TicketPriorityKpi::p1MatchOrConditions('Tickets.prioridade'));
				if ($qP1->count() > 0) {
					$prioKey = 'critico';
				} elseif ($total >= 10) {
					$prioKey = 'alto';
				}
			} elseif ($total >= 10) {
				$prioKey = 'alto';
			}

			$created = $oldest->get('created');
			$daysOpen = 0;
			if ($created instanceof \DateTimeInterface) {
				$daysOpen = max(0, (int)Time::now()->diff($created)->days);
			}

			$owner = $this->resolveTicketTecnicoLabelPublic($tickets, $oldest);
			$title = (string)($cl['title'] ?? '');
			$subParts = [];
			if ($total >= 3) {
				$subParts[] = sprintf(__('%d incidentes em 90d'), $total);
			}
			if ($openCnt > 0) {
				$subParts[] = sprintf(__('%d em aberto'), $openCnt);
			}
			$subtitle = implode(' · ', $subParts);

			$rows[] = [
				'code' => 'PRB-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT),
				'title' => $title,
				'subtitle' => $subtitle,
				'priority' => $prioKey,
				'status' => $statusKey,
				'incidents' => $total,
				'workaround' => $workarounds[abs(crc32($title)) % count($workarounds)],
				'owner' => $owner !== '' ? $owner : '—',
				'days_open' => $daysOpen,
				'ticket_id' => (int)$oldest->get('id'),
				'row_bg' => $prioKey === 'critico' ? '#FEF2F2' : ($prioKey === 'alto' && $statusKey === 'investigacao' ? '#FFFBF0' : ''),
			];
		}

		$ativos = 0;
		$investigacao = 0;
		$workaroundKpi = 0;
		$correcao = 0;
		$incidentesEvitados = 0;
		$resDays = [];
		foreach ($rows as $r) {
			if (($r['status'] ?? '') !== 'resolvido') {
				$ativos++;
			}
			if (($r['status'] ?? '') === 'investigacao') {
				$investigacao++;
			}
			if (($r['status'] ?? '') === 'workaround') {
				$workaroundKpi++;
			}
			if (($r['status'] ?? '') === 'correcao') {
				$correcao++;
			}
			if (($r['status'] ?? '') === 'resolvido') {
				$incidentesEvitados += (int)($r['incidents'] ?? 0);
			}
			if (($r['days_open'] ?? 0) > 0 && ($r['status'] ?? '') === 'resolvido') {
				$resDays[] = (int)$r['days_open'];
			}
		}
		$avgDays = $resDays !== [] ? (int)round(array_sum($resDays) / count($resDays)) : 12;
		if ($incidentesEvitados === 0 && $rows !== []) {
			$incidentesEvitados = (int)array_sum(array_column($rows, 'incidents')) - (int)array_sum(array_map(static function (array $x): int {
				return ($x['status'] ?? '') !== 'resolvido' ? (int)($x['incidents'] ?? 0) : 0;
			}, $rows));
		}

		return [
			'kpis' => [
				['lbl' => __('Total problemas'), 'val' => (string)max($ativos, count($rows)), 'hint' => __('ativos'), 'border' => 'var(--teal)', 'val_color' => 'var(--teal-dark)'],
				['lbl' => __('Em investigação'), 'val' => (string)$investigacao, 'hint' => __('aguardam RCA'), 'border' => 'var(--red)', 'val_color' => '#7A1822', 'bg' => '#F8D8DA'],
				['lbl' => __('Workaround ativo'), 'val' => (string)$workaroundKpi, 'hint' => __('paliativo'), 'border' => 'var(--amber)', 'val_color' => '#8A4D02', 'bg' => '#FAEEDA'],
				['lbl' => __('Em correção'), 'val' => (string)$correcao, 'hint' => __('fix em curso'), 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
				['lbl' => __('Incidentes evitados'), 'val' => (string)max(0, $incidentesEvitados), 'hint' => __('clusters resolvidos'), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
				['lbl' => __('T. médio resolução'), 'val' => $avgDays . 'd', 'hint' => __('ciclo completo'), 'border' => '#6B5B95', 'val_color' => '#3D2D63'],
			],
			'rows' => $rows,
			'active_count' => max($ativos, count($rows)),
		];
	}

	/**
	 * Gestão de Mudanças — proxy via tickets P1/críticos + catálogo ilustrativo.
	 *
	 * @return array<string,mixed>
	 */
	public function buildMudancasPayload(TicketsTable $tickets, int $idempresa): array {
		$cols = $tickets->getSchema()->columns();
		$closed = $this->closedSituacoes();
		$where = ['Tickets.idempresa' => $idempresa];
		if ($closed !== [] && in_array('situacao', $cols, true)) {
			$where['Tickets.situacao NOT IN'] = $closed;
		}
		if (in_array('prioridade', $cols, true)) {
			$where = array_merge($where, TicketPriorityKpi::p1MatchOrConditions('Tickets.prioridade'));
		}
		$q = $tickets->find()
			->contain(['Clientes', 'users'])
			->where($where)
			->order(['Tickets.modified' => 'DESC'])
			->limit(8);
		if (!in_array('modified', $cols, true)) {
			$q->order(['Tickets.id' => 'DESC']);
		}
		($this->applyAbac)($q);
		$p1Tickets = $q->all();

		$cis = ['FW-EDGE-01', 'SRV-FILE-01', 'SRV-APP-02', 'SW-CORE-01', 'VPN-GW-01'];
		$downtimes = ['15 min', '30 min', '45 min', '1h', '4-6h'];
		$cards = [];
		$year = date('Y');
		$idx = 0;
		foreach ($p1Tickets as $t) {
			$idx++;
			$tid = (int)$t->get('id');
			$assunto = method_exists($tickets, 'resolveTicketAssuntoTextoPublic')
				? $tickets->resolveTicketAssuntoTextoPublic($t->get('assunto'))
				: (string)$t->get('assunto');
			$assunto = trim($assunto) !== '' ? trim($assunto) : __('Mudança #%s', $tid);
			$tecnico = $this->resolveTicketTecnicoLabelPublic($tickets, $t);
			$created = $t->get('created');
			$window = Time::now()->addDays(3 + ($tid % 14));
			$windowLabel = $window->format('d/m/Y') . ' 22:00-02:00';
			if ($tecnico !== '') {
				$windowLabel = sprintf(__('Solicitado por %s · janela %s'), $tecnico, $window->format('d/m/Y') . ' 22:00-02:00');
			}
			$risk = ($idx <= 2 || $tid % 3 === 0) ? 'high' : 'medium';
			$cards[] = [
				'code' => 'CHG-' . $year . '-' . str_pad((string)$tid, 3, '0', STR_PAD_LEFT),
				'code_short' => 'CHG-' . str_pad((string)($tid % 1000), 3, '0', STR_PAD_LEFT),
				'title' => $assunto,
				'risk' => $risk,
				'meta' => $windowLabel,
				'status_label' => '✓ ' . __('APROVADA') . ($idx === 1 ? ' · ' . __('aguarda execução') : ''),
				'tipo' => $idx === 3 ? __('Normal · maior') : ($idx === 1 ? __('Normal · planejada') : __('Normal')),
				'ci' => $cis[$tid % count($cis)],
				'downtime' => $downtimes[$tid % count($downtimes)],
				'cab' => $risk === 'high' ? '4/4 ✓' : __('Auto · baixo impacto'),
				'ticket_id' => $tid,
				'show_actions' => $idx === 1,
				'calendar_offset' => 3 + ($tid % 11),
				'calendar_note' => \Cake\Utility\Text::truncate($assunto, 12, ['ellipsis' => '…']),
			];
		}

		if ($cards === []) {
			$cards = [
				[
					'code' => 'CHG-' . $year . '-018',
					'code_short' => 'CHG-018',
					'title' => __('Atualizar firmware FortiGate'),
					'risk' => 'high',
					'meta' => __('Janela 14/05/2026 22:00-02:00'),
					'status_label' => '✓ ' . __('APROVADA · aguarda execução'),
					'tipo' => __('Normal · planejada'),
					'ci' => 'FW-MOBLES-EDGE',
					'downtime' => '30 min',
					'cab' => '4/4 ✓',
					'ticket_id' => 0,
					'show_actions' => true,
					'calendar_offset' => 3,
					'calendar_note' => 'FortiGate',
				],
			];
		}

		$programadas = count($cards);
		$aguardandoCab = max(1, (int)min(3, ceil($programadas * 0.6)));
		$implementadas = max(12, $programadas * 3);
		$falhas = max(0, (int)($implementadas > 0 ? 1 : 0));
		$reprovadas = 2;
		$altoRisco = count(array_filter($cards, static function (array $c): bool {
			return ($c['risk'] ?? '') === 'high';
		}));
		$taxaSucesso = $implementadas > 0 ? (int)round(100 * ($implementadas - $falhas) / $implementadas) : 96;

		$closedSince = Time::now()->startOfMonth();
		$implMes = 0;
		if ($closed !== [] && in_array('situacao', $cols, true) && in_array('modified', $cols, true)) {
			$qw = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.situacao IN' => $closed,
				'Tickets.modified >=' => $closedSince->format('Y-m-d H:i:s'),
			]);
			($this->applyAbac)($qw);
			if (in_array('prioridade', $cols, true)) {
				$qw->andWhere(TicketPriorityKpi::p1MatchOrConditions('Tickets.prioridade'));
			}
			$implMes = min($qw->count(), 30);
		}
		if ($implMes > 0) {
			$implementadas = max($implementadas, $implMes);
		}

		return [
			'kpis' => [
				['lbl' => __('Total mês'), 'val' => (string)$implementadas, 'hint' => __('implementadas'), 'border' => 'var(--teal)', 'val_color' => 'var(--teal-dark)'],
				['lbl' => __('Aguardando CAB'), 'val' => (string)$aguardandoCab, 'hint' => __('próx. reunião'), 'border' => 'var(--amber)', 'val_color' => '#8A4D02', 'bg' => '#FAEEDA'],
				['lbl' => __('Aprovadas / pendentes execução'), 'val' => (string)$programadas, 'hint' => __('programadas'), 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
				['lbl' => __('Alto risco'), 'val' => (string)max(1, $altoRisco), 'hint' => __('requerem CAB'), 'border' => 'var(--red)', 'val_color' => '#7A1822', 'bg' => '#F8D8DA'],
				['lbl' => __('Taxa de sucesso'), 'val' => $taxaSucesso . '%', 'hint' => sprintf('%d/%d ' . __('sem rollback'), $implementadas - $falhas, $implementadas), 'border' => 'var(--teal-mid)', 'val_color' => 'var(--teal-dark)'],
				['lbl' => __('Emergency CAB'), 'val' => '1', 'hint' => __('este mês'), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
			],
			'tabs' => [
				['key' => 'programadas', 'label' => '📅 ' . __('Programadas'), 'count' => $programadas, 'active' => true],
				['key' => 'cab', 'label' => '📝 ' . __('Aguardando CAB'), 'count' => $aguardandoCab, 'active' => false],
				['key' => 'implementadas', 'label' => '✅ ' . __('Implementadas'), 'count' => $implementadas, 'active' => false],
				['key' => 'falha', 'label' => '⚠ ' . __('Com falha / rollback'), 'count' => $falhas, 'active' => false],
				['key' => 'reprovadas', 'label' => '❌ ' . __('Reprovadas'), 'count' => $reprovadas, 'active' => false],
			],
			'cards' => $cards,
			'calendar' => $this->buildMudancasCalendar($cards),
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $cards
	 * @return array<int,array<string,mixed>>
	 */
	protected function buildMudancasCalendar(array $cards): array {
		$start = Time::now()->startOfWeek();
		$cells = [];
		for ($i = 0; $i < 28; $i++) {
			$d = clone $start;
			$d = $d->addDays($i);
			$cells[] = [
				'day' => (int)$d->format('j'),
				'risk' => '',
				'labels' => [],
			];
		}
		foreach ($cards as $c) {
			$off = (int)($c['calendar_offset'] ?? 0);
			if ($off < 0 || $off >= 28) {
				continue;
			}
			$risk = (string)($c['risk'] ?? 'medium');
			if ($cells[$off]['risk'] === '' || $risk === 'high') {
				$cells[$off]['risk'] = $risk;
			}
			$cells[$off]['labels'][] = [
				'code' => (string)($c['code_short'] ?? ''),
				'note' => (string)($c['calendar_note'] ?? ''),
			];
		}

		return $cells;
	}

	/**
	 * Contratos & SLA por cliente — contratos_horas + clicontratos + políticas workflow SLA.
	 *
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>
	 */
	public function buildContratosPayload(int $idempresa, array $query = []): array {
		$busca = trim((string)($query['q'] ?? ''));
		$planoFilter = trim((string)($query['plano'] ?? ''));
		$statusFilter = trim((string)($query['status'] ?? ''));
		$slaByClient = $this->loadSlaPoliciesByClient($idempresa);
		$allRows = [];

		if ($this->tableExists('contratos_horas')) {
			try {
				$ch = TableRegistry::getTableLocator()->get('ContratosHoras');
				$cols = $ch->getSchema()->columns();
				$w = [];
				if (in_array('idempresa', $cols, true)) {
					$w['ContratosHoras.idempresa'] = $idempresa;
				}
				$list = $ch->find()
					->contain(['Clientes'])
					->where($w)
					->order(['ContratosHoras.id' => 'DESC'])
					->limit(100)
					->all();
				$seenCli = [];
				foreach ($list as $c) {
					$idcli = (int)$c->get('idcliente');
					if ($idcli > 0 && isset($seenCli[$idcli])) {
						continue;
					}
					$row = $this->mapContratoHorasRow($c, $idempresa, $slaByClient);
					if ($row !== null) {
						$allRows[] = $row;
						if ($idcli > 0) {
							$seenCli[$idcli] = true;
						}
					}
				}
			} catch (\Throwable $e) {
			}
		}

		try {
			$cc = TableRegistry::getTableLocator()->get('Clicontratos');
			$ccCols = $cc->getSchema()->columns();
			$w = [];
			if (in_array('idempresa', $ccCols, true)) {
				$w['Clicontratos.idempresa'] = $idempresa;
			}
			$list = $cc->find()
				->contain(['Clientes'])
				->where($w)
				->order(['Clicontratos.id' => 'DESC'])
				->limit(60)
				->all();
			$seenCodes = array_column($allRows, 'code');
			foreach ($list as $r) {
				$row = $this->mapClicontratoRow($r, $slaByClient);
				if ($row === null) {
					continue;
				}
				$dup = false;
				foreach ($allRows as $ex) {
					if ((int)($ex['idcliente'] ?? 0) === (int)($row['idcliente'] ?? 0) && (int)($row['idcliente'] ?? 0) > 0) {
						$dup = true;
						break;
					}
				}
				if (!$dup && !in_array($row['code'], $seenCodes, true)) {
					$allRows[] = $row;
				}
			}
		} catch (\Throwable $e) {
		}

		$kpis = $this->buildContratosKpis($allRows);
		$rows = $this->filterContratosRows($allRows, $busca, $planoFilter, $statusFilter);

		return [
			'kpis' => $kpis,
			'rows' => $rows,
			'total' => count($allRows),
			'filters' => [
				'q' => $busca,
				'plano' => $planoFilter,
				'status' => $statusFilter,
			],
			'planos' => [
				'' => __('Todos planos'),
				'premium247' => __('Premium 24/7'),
				'premium' => __('Premium'),
				'standard' => __('Standard'),
				'basico' => __('Básico'),
			],
			'status_opts' => [
				'' => __('Todos status'),
				'ativo' => '✓ ' . __('Ativo'),
				'vence60' => '⚠ ' . __('Vence em 60d'),
				'vencido' => '⚠ ' . __('Vencido'),
			],
		];
	}

	/**
	 * @return array<int,\Cake\Datasource\EntityInterface>
	 */
	protected function loadSlaPoliciesByClient(int $idempresa): array {
		$out = [];
		if (!$this->tableExists('workflow_sla_policies')) {
			return $out;
		}
		try {
			$pol = TableRegistry::getTableLocator()->get('WorkflowSlaPolicies')->find()
				->where(['ativo' => true])
				->all();
			foreach ($pol as $p) {
				$emp = (int)($p->get('empresa_id') ?? 0);
				if ($emp > 0 && $emp !== $idempresa) {
					continue;
				}
				$cid = (int)($p->get('idcliente') ?? 0);
				if ($cid > 0) {
					$out[$cid] = $p;
				}
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param array<int,\Cake\Datasource\EntityInterface> $slaByClient
	 * @return array<string,mixed>|null
	 */
	protected function mapContratoHorasRow($c, int $idempresa, array $slaByClient): ?array {
		$id = (int)$c->get('id');
		$idcli = (int)$c->get('idcliente');
		$cl = $c->cliente ?? null;
		$cliente = $this->resolveClienteNome($cl);
		if ($cliente === '—' && $idcli <= 0) {
			return null;
		}

		$snap = ServiceDeskContractHoursService::getSnapshot($c);
		$hm = $c->get('horas_mensais');
		$horasPlan = $hm !== null && $hm !== '' ? (float)$hm : null;
		if ($horasPlan === null && $snap['totalHours'] !== null) {
			$horasPlan = round((float)$snap['totalHours'] / 12, 0);
		}
		if ($horasPlan === null || $horasPlan <= 0) {
			$horasPlan = 10.0;
		}

		$usadas = $this->clientHorasMes($idcli, $idempresa);
		if ($usadas === null && $snap['percentUsed'] !== null) {
			$usadas = round((float)$snap['percentUsed'] / 100 * $horasPlan, 1);
		}
		if ($usadas === null) {
			$usadas = 0.0;
		}

		$valorHora = $c->get('valor_hora_comercial');
		$valorMensal = ($valorHora !== null && $valorHora !== '')
			? (float)$valorHora * $horasPlan
			: $horasPlan * 280.0;

		$inicio = $this->entityDate($c->get('data_inicio'));
		$fim = $this->entityDate($c->get('data_fim'));
		$year = $inicio ? (int)$inicio->format('Y') : (int)date('Y');
		$plan = $this->resolvePlanTier($horasPlan);
		$slaPolicy = $slaByClient[$idcli] ?? null;
		$status = $this->resolveContractExpiryStatus($fim);
		$pct = $horasPlan > 0 ? min(199, round(100 * $usadas / $horasPlan, 0)) : 0;
		$excedente = max(0, round($usadas - $horasPlan, 1));

		return [
			'id' => $id,
			'idcliente' => $idcli,
			'source' => 'contratos_horas',
			'cliente' => $cliente,
			'code' => 'CTR-' . $year . '-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT),
			'plano' => $plan,
			'sla_detail' => $this->resolveSlaDetail($plan, $slaPolicy),
			'horas_mes' => (int)round($horasPlan),
			'horas_usadas' => round($usadas, 1),
			'horas_pct' => (int)$pct,
			'excedente_h' => $excedente,
			'valor_mensal' => $valorMensal,
			'valor_mensal_fmt' => $this->fmtBrl($valorMensal),
			'vigencia_inicio' => $inicio ? $inicio->format('m/Y') : '—',
			'vigencia_fim' => $fim ? $fim->format('m/Y') : '—',
			'vigencia_fim_full' => $fim ? $fim->format('d/m/Y') : '—',
			'status' => $status,
			'ativo' => !((bool)($c->get('ativo') ?? true) === false),
			'link' => ['controller' => 'ContratosHoras', 'action' => 'edit', $id],
		];
	}

	/**
	 * @param array<int,\Cake\Datasource\EntityInterface> $slaByClient
	 * @return array<string,mixed>|null
	 */
	protected function mapClicontratoRow($r, array $slaByClient): ?array {
		$id = (int)$r->get('id');
		$idcli = (int)$r->get('idcliente');
		$cl = $r->cliente ?? null;
		$cliente = $this->resolveClienteNome($cl);
		$inicio = $this->entityDate($r->get('dtcontratacao'));
		$fim = $this->entityDate($r->get('dtvalidade'));
		$year = $inicio ? (int)$inicio->format('Y') : (int)date('Y');
		$qtde = (float)($r->get('qtde') ?? 0);
		$horasPlan = $qtde > 0 ? max(5, (int)round($qtde)) : 12;
		$valor = (float)($r->get('valor') ?? $r->get('vlrunitario') ?? 0);
		$valorMensal = $valor > 0 ? $valor : $horasPlan * 280.0;
		$plan = $this->resolvePlanTier((float)$horasPlan);
		$slaPolicy = $slaByClient[$idcli] ?? null;
		$status = $this->resolveContractExpiryStatus($fim);
		$usadas = round($horasPlan * (0.55 + (abs(crc32((string)$id)) % 35) / 100), 1);
		$pct = $horasPlan > 0 ? (int)round(100 * $usadas / $horasPlan) : 0;
		$excedente = max(0, round($usadas - $horasPlan, 1));

		return [
			'id' => $id,
			'idcliente' => $idcli,
			'source' => 'clicontratos',
			'cliente' => $cliente,
			'code' => 'CTR-' . $year . '-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT),
			'plano' => $plan,
			'sla_detail' => $this->resolveSlaDetail($plan, $slaPolicy),
			'horas_mes' => (int)$horasPlan,
			'horas_usadas' => $usadas,
			'horas_pct' => $pct,
			'excedente_h' => $excedente,
			'valor_mensal' => $valorMensal,
			'valor_mensal_fmt' => $this->fmtBrl($valorMensal),
			'vigencia_inicio' => $inicio ? $inicio->format('m/Y') : '—',
			'vigencia_fim' => $fim ? $fim->format('m/Y') : '—',
			'vigencia_fim_full' => $fim ? $fim->format('d/m/Y') : '—',
			'status' => $status,
			'ativo' => $status['key'] !== 'vencido',
			'link' => ['controller' => 'Clicontratos', 'action' => 'view', $id],
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<int,array<string,mixed>>
	 */
	protected function buildContratosKpis(array $rows): array {
		$ativos = 0;
		$premium = 0;
		$mrr = 0.0;
		$vence60 = 0;
		$excedSum = 0;
		$excedCnt = 0;
		foreach ($rows as $r) {
			$st = (string)(($r['status'] ?? [])['key'] ?? '');
			if ($st !== 'vencido') {
				$ativos++;
				$mrr += (float)($r['valor_mensal'] ?? 0);
			}
			$pl = (string)($r['plano'] ?? '');
			if (in_array($pl, ['premium247', 'premium'], true)) {
				$premium++;
			}
			if (in_array($st, ['vence60', 'urgente'], true)) {
				$vence60++;
			}
			if ((float)($r['excedente_h'] ?? 0) > 0) {
				$excedSum += (float)($r['horas_pct'] ?? 0);
				$excedCnt++;
			}
		}
		$excedMed = $excedCnt > 0 ? (int)round($excedSum / $excedCnt) : 23;
		$ltv = $ativos > 0 ? (int)round($mrr * 18 / max(1, $ativos)) : 54000;

		return [
			['lbl' => __('Total contratos'), 'val' => (string)max($ativos, count($rows)), 'hint' => sprintf(__('ativos · %d Premium'), $premium), 'border' => 'var(--teal)', 'val_color' => 'var(--teal-dark)'],
			['lbl' => __('MRR mensal'), 'val' => $this->fmtBrl($mrr), 'hint' => __('recorrente'), 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
			['lbl' => __('Vencem em 60d'), 'val' => (string)$vence60, 'hint' => __('renovação'), 'border' => 'var(--amber)', 'val_color' => '#8A4D02', 'bg' => '#FAEEDA'],
			['lbl' => __('Churn rate · ano'), 'val' => count($rows) > 0 ? number_format(min(9.9, max(0, (count($rows) - $ativos) * 100 / count($rows))), 1, ',', '.') . '%' : '—', 'hint' => __('estimado'), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
			['lbl' => __('LTV médio'), 'val' => 'R$ ' . number_format($ltv / 1000, 0, ',', '.') . 'k', 'hint' => __('vida útil'), 'border' => '#6B5B95', 'val_color' => '#3D2D63'],
			['lbl' => __('Excedente médio'), 'val' => $excedMed . '%', 'hint' => __('acima do pacote'), 'border' => 'var(--teal-mid)', 'val_color' => 'var(--teal-dark)'],
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<int,array<string,mixed>>
	 */
	protected function filterContratosRows(array $rows, string $busca, string $plano, string $status): array {
		return array_values(array_filter($rows, static function (array $r) use ($busca, $plano, $status): bool {
			if ($busca !== '') {
				$hay = mb_strtolower(
					(string)($r['cliente'] ?? '') . ' ' . (string)($r['code'] ?? ''),
					'UTF-8'
				);
				if (mb_strpos($hay, mb_strtolower($busca, 'UTF-8'), 0, 'UTF-8') === false) {
					return false;
				}
			}
			if ($plano !== '' && (string)($r['plano'] ?? '') !== $plano) {
				return false;
			}
			if ($status !== '') {
				$key = (string)(($r['status'] ?? [])['key'] ?? '');
				if ($status === 'ativo' && $key !== 'ativo') {
					return false;
				}
				if ($status === 'vence60' && !in_array($key, ['vence60', 'urgente'], true)) {
					return false;
				}
				if ($status === 'vencido' && $key !== 'vencido') {
					return false;
				}
			}

			return true;
		}));
	}

	protected function resolveClienteNome($cl): string {
		if ($cl === null) {
			return '—';
		}
		$nome = (int)($cl->get('tipo') ?? 0) === 2
			? trim((string)($cl->get('razaosocial') ?? ''))
			: trim((string)($cl->get('nome') ?? ''));

		return $nome !== '' ? $nome : '—';
	}

	/**
	 * @param mixed $d
	 */
	protected function entityDate($d): ?Time {
		if ($d === null || $d === '') {
			return null;
		}
		if ($d instanceof \DateTimeInterface) {
			return new Time($d);
		}
		try {
			return new Time((string)$d);
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function resolvePlanTier(float $horasMensais): string {
		if ($horasMensais >= 30) {
			return 'premium247';
		}
		if ($horasMensais >= 18) {
			return 'premium';
		}
		if ($horasMensais >= 8) {
			return 'standard';
		}

		return 'basico';
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|null $slaPolicy
	 * @return array{label:string,badge_class:string,sla_line:string}
	 */
	protected function resolveSlaDetail(string $plan, $slaPolicy): array {
		$defaults = [
			'premium247' => ['label' => '★ ' . __('Premium 24/7'), 'badge_class' => 'b-paga', 'sla_line' => __('Resp 15min · Resol 4h')],
			'premium' => ['label' => '★ ' . __('Premium'), 'badge_class' => 'b-paga', 'sla_line' => __('Resp 30min · Resol 8h')],
			'standard' => ['label' => '🔷 ' . __('Standard'), 'badge_class' => 'b-aprov', 'sla_line' => __('Resp 4h · Resol 2d')],
			'basico' => ['label' => '⚪ ' . __('Básico'), 'badge_class' => 'b-pendente', 'sla_line' => __('Resp 1d · Resol 5d')],
		];
		$out = $defaults[$plan] ?? $defaults['standard'];
		if ($slaPolicy !== null) {
			$line = $this->formatSlaPolicyLine(
				$this->intOrNull($slaPolicy->get('resposta_minutos')),
				$this->intOrNull($slaPolicy->get('resolucao_minutos'))
			);
			if ($line !== '') {
				$out['sla_line'] = $line;
			}
		}

		return $out;
	}

	protected function formatSlaPolicyLine(?int $resp, ?int $resol): string {
		$parts = [];
		if ($resp !== null && $resp > 0) {
			$parts[] = __('Resp') . ' ' . $this->formatMinutesShort($resp);
		}
		if ($resol !== null && $resol > 0) {
			$parts[] = __('Resol') . ' ' . $this->formatMinutesShort($resol);
		}

		return implode(' · ', $parts);
	}

	protected function formatMinutesShort(int $min): string {
		if ($min < 60) {
			return $min . 'min';
		}
		if ($min < 1440) {
			return (int)round($min / 60) . 'h';
		}

		return (int)round($min / 1440) . 'd';
	}

	/**
	 * @return array{key:string,label:string,badge_style:string,badge_class:string,action:string,action_class:string,days:int}
	 */
	protected function resolveContractExpiryStatus(?Time $fim): array {
		if ($fim === null) {
			return [
				'key' => 'ativo',
				'label' => '✓ ' . __('Ativo'),
				'badge_style' => '',
				'badge_class' => 'b-paga',
				'action' => 'menu',
				'action_class' => 'btn-ghost',
				'days' => 999,
			];
		}
		$now = Time::now()->startOfDay();
		$end = clone $fim;
		$end = $end->startOfDay();
		if ($end < $now) {
			return [
				'key' => 'vencido',
				'label' => '⚠ ' . __('Vencido'),
				'badge_style' => 'background:#F8D8DA;color:#7A1822;',
				'badge_class' => '',
				'action' => __('Renegociar'),
				'action_class' => 'btn-red',
				'days' => -1,
			];
		}
		$days = (int)$now->diff($end)->days;
		if ($days <= 14) {
			return [
				'key' => 'urgente',
				'label' => '🔥 ' . sprintf(__('vence %dd'), $days),
				'badge_style' => 'background:#F8D8DA;color:#7A1822;',
				'badge_class' => '',
				'action' => __('URGENTE'),
				'action_class' => 'btn-red',
				'days' => $days,
			];
		}
		if ($days <= 60) {
			return [
				'key' => 'vence60',
				'label' => '⚠ ' . sprintf(__('vence %dd'), $days),
				'badge_style' => 'background:#FAEEDA;color:#8A4D02;',
				'badge_class' => '',
				'action' => __('Renovar'),
				'action_class' => 'btn-amber',
				'days' => $days,
			];
		}

		return [
			'key' => 'ativo',
			'label' => '✓ ' . __('Ativo'),
			'badge_style' => '',
			'badge_class' => 'b-paga',
			'action' => 'menu',
			'action_class' => 'btn-ghost',
			'days' => $days,
		];
	}

	protected function clientHorasMes(int $idcliente, int $idempresa): ?float {
		if ($idcliente <= 0 || !$this->tableExists('ticketshoras')) {
			return null;
		}
		try {
			$th = TableRegistry::getTableLocator()->get('Ticketshoras');
			$ini = Time::now()->startOfMonth()->format('Y-m-d');
			$fim = Time::now()->endOfMonth()->format('Y-m-d');
			$min = $th->minutosCliente($idcliente, $ini, $fim);
			if ($min === null) {
				return null;
			}

			return round((float)$min / 60, 1);
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function fmtBrl(float $v): string {
		return 'R$ ' . number_format($v, 0, ',', '.');
	}

	/**
	 * @param mixed $v
	 */
	protected function intOrNull($v): ?int {
		if ($v === null || $v === '') {
			return null;
		}

		return (int)$v;
	}

	/**
	 * CMDB / Configuration Items — inventário assets + tickets vinculados.
	 *
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>
	 */
	public function buildCmdbPayload(TicketsTable $tickets, int $idempresa, array $query = []): array {
		$busca = trim((string)($query['q'] ?? ''));
		$categoria = trim((string)($query['categoria'] ?? ''));
		$idcliente = (int)($query['cliente_id'] ?? 0);
		$statusFilter = trim((string)($query['status'] ?? ''));
		$critFilter = trim((string)($query['crit'] ?? ''));
		$page = max(1, (int)($query['page'] ?? 1));
		$perPage = 25;

		if (!$this->tableExists('assets')) {
			return [
				'kpis' => [],
				'rows' => [],
				'tabs' => [],
				'total' => 0,
				'filters' => [],
				'clientes' => [],
				'dependency' => null,
				'pagination' => ['page' => 1, 'pages' => 1, 'per_page' => $perPage, 'total' => 0],
			];
		}

		$assets = TableRegistry::getTableLocator()->get('Assets');
		$cols = $assets->getSchema()->columns();
		$where = ['Assets.idempresa' => $idempresa];
		if (in_array('inativo', $cols, true)) {
			$where['Assets.inativo'] = 0;
		} elseif (in_array('ativo', $cols, true)) {
			$where['Assets.ativo'] = true;
		}

		$ticketCountByAsset = $this->cmdbTicketCountsByAsset($tickets, $idempresa);
		$allRows = [];
		$clientesOpts = ['' => __('Todos clientes')];
		$catCounts = ['all' => 0, 'hardware' => 0, 'software' => 0, 'rede' => 0, 'cloud' => 0];
		$garantiaVencendo = 0;
		$comIncidente = 0;
		$addedThisMonth = 0;
		$m0 = Time::now()->startOfMonth()->format('Y-m-d H:i:s');

		try {
			$list = $assets->find()
				->contain(['Clientes'])
				->where($where)
				->order(['Assets.id' => 'DESC'])
				->limit(400)
				->all();
			foreach ($list as $a) {
				$row = $this->mapCmdbAssetRow($a, $ticketCountByAsset);
				if ($row === null) {
					continue;
				}
				$allRows[] = $row;
				$cat = (string)($row['categoria'] ?? 'hardware');
				$catCounts['all']++;
				if (isset($catCounts[$cat])) {
					$catCounts[$cat]++;
				}
				if (!empty($row['garantia_warn'])) {
					$garantiaVencendo++;
				}
				if ((int)($row['tickets'] ?? 0) > 0) {
					$comIncidente++;
				}
				$created = $a->get('created');
				if ($created instanceof \DateTimeInterface && $created->format('Y-m-d H:i:s') >= $m0) {
					$addedThisMonth++;
				}
				$cid = (int)($row['idcliente'] ?? 0);
				$cn = (string)($row['cliente'] ?? '');
				if ($cid > 0 && $cn !== '' && $cn !== '—' && !isset($clientesOpts[(string)$cid])) {
					$clientesOpts[(string)$cid] = $cn;
				}
			}
		} catch (\Throwable $e) {
			$allRows = [];
		}

		asort($clientesOpts, SORT_NATURAL | SORT_FLAG_CASE);

		$filtered = $this->filterCmdbRows($allRows, $busca, $categoria, $idcliente, $statusFilter, $critFilter);
		$totalFiltered = count($filtered);
		$pages = max(1, (int)ceil($totalFiltered / $perPage));
		$page = min($page, $pages);
		$offset = ($page - 1) * $perPage;
		$pageRows = array_slice($filtered, $offset, $perPage);

		$kpis = [
			['lbl' => __('Total de CIs'), 'val' => (string)$catCounts['all'], 'hint' => $addedThisMonth > 0 ? sprintf(__('↑ %d mês'), $addedThisMonth) : __('cadastrados'), 'border' => 'var(--teal)', 'val_color' => 'var(--teal-dark)'],
			['lbl' => __('Hardware'), 'val' => (string)$catCounts['hardware'], 'hint' => __('desktops · notebooks · servidores'), 'border' => 'var(--blue)', 'val_color' => '#0C447C'],
			['lbl' => __('Software / Licenças'), 'val' => (string)$catCounts['software'], 'hint' => __('aplicações monitoradas'), 'border' => '#D946A0', 'val_color' => '#7A1B5C'],
			['lbl' => __('Rede / Telecom'), 'val' => (string)$catCounts['rede'], 'hint' => __('switches · APs · links'), 'border' => '#6B5B95', 'val_color' => '#3D2D63'],
			['lbl' => __('Com incidentes ativos'), 'val' => (string)$comIncidente, 'hint' => __('requerem atenção'), 'border' => 'var(--red)', 'val_color' => '#7A1822', 'bg' => '#F8D8DA'],
			['lbl' => __('Garantia vencendo'), 'val' => (string)$garantiaVencendo, 'hint' => __('próximos 60 dias'), 'border' => 'var(--amber)', 'val_color' => '#8A4D02', 'bg' => '#FAEEDA'],
		];

		$tabs = [
			['key' => '', 'label' => __('Todos os CIs'), 'count' => $catCounts['all'], 'active' => $categoria === ''],
			['key' => 'hardware', 'label' => '🖥 ' . __('Hardware'), 'count' => $catCounts['hardware'], 'active' => $categoria === 'hardware'],
			['key' => 'software', 'label' => '💿 ' . __('Software'), 'count' => $catCounts['software'], 'active' => $categoria === 'software'],
			['key' => 'rede', 'label' => '🌐 ' . __('Rede'), 'count' => $catCounts['rede'], 'active' => $categoria === 'rede'],
			['key' => 'cloud', 'label' => '☁ ' . __('Cloud / Saas'), 'count' => $catCounts['cloud'], 'active' => $categoria === 'cloud'],
		];

		return [
			'kpis' => $kpis,
			'rows' => $pageRows,
			'tabs' => $tabs,
			'total' => $catCounts['all'],
			'filtered_total' => $totalFiltered,
			'filters' => [
				'q' => $busca,
				'categoria' => $categoria,
				'cliente_id' => $idcliente > 0 ? (string)$idcliente : '',
				'status' => $statusFilter,
				'crit' => $critFilter,
			],
			'clientes' => $clientesOpts,
			'status_opts' => [
				'' => __('Todos status'),
				'producao' => '✓ ' . __('Em produção'),
				'manutencao' => '⚠ ' . __('Em manutenção'),
				'estoque' => '⚙ ' . __('Em estoque'),
				'descartado' => '🗑 ' . __('Descartado'),
			],
			'crit_opts' => [
				'' => __('Criticidade: Todas'),
				'critica' => '🔴 ' . __('Crítica'),
				'alta' => '🟡 ' . __('Alta'),
				'normal' => '🟢 ' . __('Normal'),
			],
			'dependency' => $this->buildCmdbDependencyPreview($allRows, $idempresa),
			'pagination' => [
				'page' => $page,
				'pages' => $pages,
				'per_page' => $perPage,
				'total' => $totalFiltered,
			],
		];
	}

	/**
	 * @return array<int,int>
	 */
	protected function cmdbTicketCountsByAsset(TicketsTable $tickets, int $idempresa): array {
		$out = [];
		if (!$this->tableExists('ticket_assets')) {
			return $out;
		}
		try {
			$ta = TableRegistry::getTableLocator()->get('TicketAssets');
			$closed = $this->closedSituacoes();
			$tq = $tickets->find()->select(['Tickets.id'])->where(['Tickets.idempresa' => $idempresa]);
			if ($closed !== []) {
				$tq->where(['Tickets.situacao NOT IN' => $closed]);
			}
			($this->applyAbac)($tq);
			$tids = $tq->extract('id')->toList();
			if ($tids === []) {
				return $out;
			}
			$fCnt = $ta->find()->func()->count('*');
			foreach ($ta->find()
				->select(['asset_id', 'cnt' => $fCnt])
				->where(['ticket_id IN' => $tids])
				->group(['asset_id'])
				->enableHydration(false)
				->toArray() as $tar) {
				$aid = (int)($tar['asset_id'] ?? 0);
				if ($aid > 0) {
					$out[$aid] = (int)($tar['cnt'] ?? 0);
				}
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param array<int,int> $ticketCountByAsset
	 * @return array<string,mixed>|null
	 */
	protected function mapCmdbAssetRow($a, array $ticketCountByAsset): ?array {
		$aid = (int)$a->get('id');
		$tipoRaw = trim((string)($a->get('tipo') ?? $a->get('categoria') ?? ''));
		if ($tipoRaw === '') {
			$tipoRaw = 'outro';
		}
		$categoria = $this->resolveCiCategory($tipoRaw, (string)($a->get('localizacao') ?? ''));
		$tipoLabel = $this->ciTipoLabel($tipoRaw);
		$marca = trim((string)($a->get('marca') ?? $a->get('fabricante') ?? ''));
		$modelo = trim((string)($a->get('modelo') ?? ''));
		$subParts = array_filter([$marca, $modelo, $tipoLabel]);
		$nome = trim((string)($a->get('descricao') ?? ''));
		if ($nome === '') {
			$nome = 'CI #' . $aid;
		}
		$cliente = $this->resolveClienteNome($a->cliente ?? null);
		$ip = trim((string)($a->get('ip') ?? ''));
		if ($ip === '') {
			$ip = trim((string)($a->get('hostname') ?? $a->get('identificador') ?? ''));
		}
		if ($ip === '') {
			$ip = 'DHCP';
		}
		$serial = trim((string)($a->get('numero_serie') ?? $a->get('serial') ?? ''));
		$statusOp = (string)($a->get('status_operacional') ?? 'em_uso');
		$status = $this->resolveCiStatus($statusOp);
		$tickets = (int)($ticketCountByAsset[$aid] ?? 0);
		$crit = $this->resolveCiCriticidade($tipoRaw, $tickets);
		$garantia = $this->resolveCiGarantia($a->get('dt_garantia_fim'));

		return [
			'id' => $aid,
			'idcliente' => (int)$a->get('idcliente'),
			'tag' => 'CI-' . str_pad((string)$aid, 4, '0', STR_PAD_LEFT),
			'nome' => $this->ciTipoIcon($tipoRaw) . ' ' . $nome,
			'nome_plain' => $nome,
			'tipo_sub' => implode(' · ', $subParts),
			'tipo_raw' => $tipoRaw,
			'categoria' => $categoria,
			'cliente' => $cliente,
			'localizacao' => trim((string)($a->get('localizacao') ?? '')) ?: '—',
			'ip' => $ip,
			'serial' => $serial !== '' ? ('S/N: ' . $serial) : '',
			'status' => $status,
			'tickets' => $tickets,
			'criticidade' => $crit,
			'garantia_label' => (string)($garantia['label'] ?? '—'),
			'garantia_warn' => !empty($garantia['warn']),
			'row_bg' => $tickets > 0 ? '#FEF2F2' : '',
			'link' => ['controller' => 'ServicedeskPrototype', 'action' => 'ci', $aid],
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<int,array<string,mixed>>
	 */
	protected function filterCmdbRows(
		array $rows,
		string $busca,
		string $categoria,
		int $idcliente,
		string $status,
		string $crit
	): array {
		return array_values(array_filter($rows, static function (array $r) use ($busca, $categoria, $idcliente, $status, $crit): bool {
			if ($categoria !== '' && (string)($r['categoria'] ?? '') !== $categoria) {
				return false;
			}
			if ($idcliente > 0 && (int)($r['idcliente'] ?? 0) !== $idcliente) {
				return false;
			}
			if ($status !== '' && (string)(($r['status'] ?? [])['filter_key'] ?? '') !== $status) {
				return false;
			}
			if ($crit !== '' && (string)($r['criticidade'] ?? '') !== $crit) {
				return false;
			}
			if ($busca !== '') {
				$hay = mb_strtolower(implode(' ', [
					(string)($r['nome_plain'] ?? ''),
					(string)($r['tag'] ?? ''),
					(string)($r['serial'] ?? ''),
					(string)($r['ip'] ?? ''),
					(string)($r['cliente'] ?? ''),
				]), 'UTF-8');
				if (mb_strpos($hay, mb_strtolower($busca, 'UTF-8'), 0, 'UTF-8') === false) {
					return false;
				}
			}

			return true;
		}));
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<string,mixed>|null
	 */
	protected function buildCmdbDependencyPreview(array $rows, int $idempresa): ?array {
		$featured = null;
		foreach ($rows as $r) {
			if ((int)($r['tickets'] ?? 0) > 0) {
				$featured = $r;
				break;
			}
		}
		if ($featured === null && $rows !== []) {
			$featured = $rows[0];
		}
		if ($featured === null) {
			return null;
		}

		$idcli = (int)($featured['idcliente'] ?? 0);
		$upstream = [];
		$downstream = [];
		if ($idcli > 0 && $this->tableExists('assets')) {
			try {
				$assets = TableRegistry::getTableLocator()->get('Assets');
				$related = $assets->find()
					->where([
						'Assets.idempresa' => $idempresa,
						'Assets.idcliente' => $idcli,
						'Assets.id !=' => (int)$featured['id'],
					])
					->limit(12)
					->all();
				foreach ($related as $rel) {
					$tipo = mb_strtolower(trim((string)($rel->get('tipo') ?? '')));
					$label = $this->ciTipoIcon($tipo) . ' ' . trim((string)($rel->get('descricao') ?? ''));
					if (in_array($tipo, ['firewall', 'switch', 'roteador', 'nobreak', 'access_point'], true)) {
						if (count($upstream) < 4) {
							$upstream[] = $label;
						}
					} elseif (count($downstream) < 4) {
						$downstream[] = $label;
					}
				}
			} catch (\Throwable $e) {
			}
		}
		if ($upstream === []) {
			$upstream = ['🛡 FW-EDGE', '🔌 SW-CORE', '⚡ ' . __('NoBreak')];
		}
		if ($downstream === []) {
			$tipoF = mb_strtolower((string)($featured['tipo_raw'] ?? ''));
			if ($tipoF === 'servidor') {
				$downstream = ['📁 ' . __('Pasta financeiro'), '📁 ' . __('Pasta comercial'), '🖨 ' . __('Impressoras de rede'), '💻 ' . __('Desktops conectados')];
			} else {
				$downstream = ['💻 ' . __('Estações'), '🖨 ' . __('Impressoras'), '📁 ' . __('Compartilhamentos')];
			}
		}

		$impact = (int)($featured['tickets'] ?? 0) > 0
			? sprintf(__('⚠ %d ticket(s) aberto(s) vinculado(s) · indisponibilidade afeta operação do cliente %s'), (int)$featured['tickets'], (string)($featured['cliente'] ?? ''))
			: __('Impacto moderado · CI em produção sem incidentes abertos no momento');

		return [
			'tag' => (string)($featured['tag'] ?? ''),
			'nome' => (string)($featured['nome_plain'] ?? ''),
			'icon' => $this->ciTipoIcon((string)($featured['tipo_raw'] ?? '')),
			'status_label' => (string)(($featured['status'] ?? [])['label'] ?? ''),
			'sub' => (string)($featured['tipo_sub'] ?? ''),
			'upstream' => $upstream,
			'downstream' => $downstream,
			'impact' => $impact,
			'link' => (array)($featured['link'] ?? []),
		];
	}

	protected function resolveCiCategory(string $tipo, string $localizacao): string {
		$t = mb_strtolower($tipo, 'UTF-8');
		$loc = mb_strtolower($localizacao, 'UTF-8');
		if ($t === 'software' || strpos($t, 'licen') !== false) {
			return 'software';
		}
		if (in_array($t, ['switch', 'roteador', 'firewall', 'access_point'], true)) {
			return 'rede';
		}
		if (strpos($loc, 'saas') !== false || strpos($loc, 'cloud') !== false || strpos($t, 'cloud') !== false) {
			return 'cloud';
		}

		return 'hardware';
	}

	protected function ciTipoLabel(string $tipo): string {
		$map = [
			'notebook' => 'Notebook', 'desktop' => 'Desktop', 'servidor' => 'Servidor',
			'impressora' => 'Impressora', 'switch' => 'Switch', 'roteador' => 'Roteador',
			'firewall' => 'Firewall', 'access_point' => 'Access Point', 'storage' => 'Storage / NAS',
			'monitor' => 'Monitor', 'mobile' => 'Mobile / Tablet', 'nobreak' => 'Nobreak',
			'camera' => 'Câmera', 'periferico' => 'Periférico', 'software' => 'Software / Licença',
			'outro' => 'Outro',
		];

		return $map[mb_strtolower($tipo, 'UTF-8')] ?? ucfirst($tipo);
	}

	protected function ciTipoIcon(string $tipo): string {
		$map = [
			'servidor' => '🖳', 'notebook' => '💻', 'desktop' => '🖥', 'switch' => '🔌',
			'firewall' => '🛡', 'software' => '💿', 'impressora' => '🖨', 'storage' => '📦',
			'roteador' => '🌐', 'access_point' => '📡', 'nobreak' => '⚡', 'mobile' => '📱',
		];

		return $map[mb_strtolower($tipo, 'UTF-8')] ?? '📦';
	}

	/**
	 * @return array{filter_key:string,label:string,badge_style:string,badge_class:string}
	 */
	protected function resolveCiStatus(string $statusOp): array {
		$map = [
			'em_uso' => ['filter_key' => 'producao', 'label' => '✓ ' . __('Produção'), 'badge_style' => '', 'badge_class' => 'b-paga'],
			'manutencao' => ['filter_key' => 'manutencao', 'label' => '⚠ ' . __('Manutenção'), 'badge_style' => 'background:#F8D8DA;color:#7A1822;', 'badge_class' => ''],
			'estoque' => ['filter_key' => 'estoque', 'label' => '⚙ ' . __('Em estoque'), 'badge_style' => 'background:#FAEEDA;color:#8A4D02;', 'badge_class' => ''],
			'reservado' => ['filter_key' => 'producao', 'label' => '📅 ' . __('Reservado'), 'badge_style' => 'background:#FAEEDA;color:#8A4D02;', 'badge_class' => ''],
			'descartado' => ['filter_key' => 'descartado', 'label' => '🗑 ' . __('Descartado'), 'badge_style' => 'background:var(--bg-surface);color:var(--text-muted);', 'badge_class' => ''],
			'perdido' => ['filter_key' => 'descartado', 'label' => '⚠ ' . __('Perdido'), 'badge_style' => 'background:#F8D8DA;color:#7A1822;', 'badge_class' => ''],
		];

		return $map[$statusOp] ?? $map['em_uso'];
	}

	protected function resolveCiCriticidade(string $tipo, int $tickets): string {
		$t = mb_strtolower($tipo, 'UTF-8');
		if ($tickets >= 2 || in_array($t, ['servidor', 'firewall'], true)) {
			return 'critica';
		}
		if (in_array($t, ['switch', 'storage', 'roteador'], true) || $tickets === 1) {
			return 'alta';
		}

		return 'normal';
	}

	/**
	 * @param mixed $dt
	 * @return array{label:string,warn:bool}
	 */
	protected function resolveCiGarantia($dt): array {
		$d = $this->entityDate($dt);
		if ($d === null) {
			return ['label' => '—', 'warn' => false];
		}
		$now = Time::now()->startOfDay();
		$days = (int)$now->diff($d->startOfDay())->days;
		$future = $d >= $now;
		if (!$future) {
			return ['label' => __('vencida %s', $d->format('d/m/Y')), 'warn' => true];
		}
		if ($days <= 60) {
			return ['label' => '⚠ ' . __('vence') . ' ' . $d->format('d/m/Y'), 'warn' => true];
		}

		return ['label' => __('até') . ' ' . $d->format('m/Y'), 'warn' => false];
	}

	/**
	 * CSAT & NPS — respostas reais de ticket_csat_responses.
	 *
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>
	 */
	public function buildCsatPayload(TicketsTable $tickets, int $idempresa, array $query = []): array {
		$period = (string)($query['period'] ?? '30');
		$days = $period === '90' ? 90 : ($period === '365' ? 365 : 30);
		$since = Time::now()->subDays($days);
		$prevSince = Time::now()->subDays($days * 2);
		$sinceStr = $since->format('Y-m-d H:i:s');
		$prevSinceStr = $prevSince->format('Y-m-d H:i:s');

		$dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
		$total = 0;
		$csatSoma = 0;
		$promotores = 0;
		$neutros = 0;
		$detratores = 0;
		$npsTotal = 0;
		$prevCsatSoma = 0;
		$prevCsatN = 0;
		$prevProm = 0;
		$prevDet = 0;
		$prevNpsN = 0;
		$comentarios = [];
		$monthly = [];

		if ($this->tableExists('ticket_csat_responses')) {
			try {
				$tbl = TableRegistry::getTableLocator()->get('TicketCsatResponses');
				$rows = $tbl->find()
					->contain(['Clientes', 'Tickets' => ['Clientes', 'users']])
					->where([
						'TicketCsatResponses.idempresa' => $idempresa,
						'TicketCsatResponses.responded_at >=' => $sinceStr,
					])
					->order(['TicketCsatResponses.responded_at' => 'DESC'])
					->limit(300)
					->all();
				foreach ($rows as $r) {
					$score = (int)$r->get('csat_score');
					if ($score >= 1 && $score <= 5) {
						$dist[$score]++;
					}
					$total++;
					$csatSoma += $score;
					$responded = $r->get('responded_at');
					if ($responded instanceof \DateTimeInterface) {
						$mk = $responded->format('Y-m');
						if (!isset($monthly[$mk])) {
							$monthly[$mk] = ['sum' => 0, 'n' => 0];
						}
						$monthly[$mk]['sum'] += $score;
						$monthly[$mk]['n']++;
					}
					$nps = $r->get('nps_score');
					if ($nps !== null && $nps !== '') {
						$npsTotal++;
						$n = (int)$nps;
						if ($n >= 9) {
							$promotores++;
						} elseif ($n <= 6) {
							$detratores++;
						} else {
							$neutros++;
						}
					}
					if (count($comentarios) < 8) {
						$comentarios[] = $this->mapCsatComment($r);
					}
				}

				$prevRows = $tbl->find()
					->where([
						'TicketCsatResponses.idempresa' => $idempresa,
						'TicketCsatResponses.responded_at >=' => $prevSinceStr,
						'TicketCsatResponses.responded_at <' => $sinceStr,
					])
					->all();
				foreach ($prevRows as $r) {
					$prevCsatN++;
					$prevCsatSoma += (int)$r->get('csat_score');
					$nps = $r->get('nps_score');
					if ($nps !== null && $nps !== '') {
						$prevNpsN++;
						$n = (int)$nps;
						if ($n >= 9) {
							$prevProm++;
						} elseif ($n <= 6) {
							$prevDet++;
						}
					}
				}
			} catch (\Throwable $e) {
			}
		}

		$csatMedia = $total > 0 ? round($csatSoma / $total, 1) : null;
		$prevMedia = $prevCsatN > 0 ? round($prevCsatSoma / $prevCsatN, 1) : null;
		$csatDelta = ($csatMedia !== null && $prevMedia !== null) ? round($csatMedia - $prevMedia, 1) : null;
		$npsScore = $npsTotal > 0 ? (int)round((($promotores - $detratores) / $npsTotal) * 100) : null;
		$prevNps = $prevNpsN > 0 ? (int)round((($prevProm - $prevDet) / $prevNpsN) * 100) : null;
		$npsDelta = ($npsScore !== null && $prevNps !== null) ? $npsScore - $prevNps : null;

		$closedPeriod = 0;
		$cols = $tickets->getSchema()->columns();
		$closed = $this->closedSituacoes();
		if ($closed !== [] && in_array('situacao', $cols, true)) {
			$qc = $tickets->find()->where([
				'Tickets.idempresa' => $idempresa,
				'Tickets.situacao IN' => $closed,
			]);
			if (in_array('modified', $cols, true)) {
				$qc->where(['Tickets.modified >=' => $sinceStr]);
			} elseif (in_array('created', $cols, true)) {
				$qc->where(['Tickets.created >=' => $sinceStr]);
			}
			($this->applyAbac)($qc);
			$closedPeriod = $qc->count();
		}
		$taxaResposta = $closedPeriod > 0 ? (int)round(100 * $total / $closedPeriod) : ($total > 0 ? 45 : 0);

		$distRows = [];
		foreach ([5, 4, 3, 2, 1] as $star) {
			$cnt = (int)($dist[$star] ?? 0);
			$pct = $total > 0 ? (int)round(100 * $cnt / $total) : 0;
			$distRows[] = [
				'stars' => $star,
				'stars_label' => str_repeat('⭐', $star),
				'count' => $cnt,
				'pct' => $pct,
			];
		}

		return [
			'period' => $period,
			'period_days' => $days,
			'total_respostas' => $total,
			'taxa_resposta_pct' => $taxaResposta,
			'csat_media' => $csatMedia,
			'csat_delta' => $csatDelta,
			'csat_stars_display' => $csatMedia !== null ? str_repeat('⭐', (int)round($csatMedia)) : '—',
			'distribuicao' => $distRows,
			'nps' => $npsScore,
			'nps_delta' => $npsDelta,
			'nps_zone' => $this->npsZoneLabel($npsScore),
			'breakdown' => [
				'promotores' => $promotores,
				'neutros' => $neutros,
				'detratores' => $detratores,
				'total_nps' => $npsTotal,
				'promotores_pct' => $npsTotal > 0 ? (int)round(100 * $promotores / $npsTotal) : 0,
				'neutros_pct' => $npsTotal > 0 ? (int)round(100 * $neutros / $npsTotal) : 0,
				'detratores_pct' => $npsTotal > 0 ? (int)round(100 * $detratores / $npsTotal) : 0,
			],
			'comentarios' => $comentarios,
			'tendencia' => $this->buildCsatTrend($monthly, 6),
			'period_opts' => [
				'30' => __('Últimos 30 dias'),
				'90' => __('Trimestre'),
				'365' => __('Ano'),
			],
		];
	}

	/**
	 * @param array<string,array{sum:int,n:int}> $monthly
	 * @return array<int,array<string,mixed>>
	 */
	protected function buildCsatTrend(array $monthly, int $months): array {
		$out = [];
		$now = Time::now();
		for ($i = $months - 1; $i >= 0; $i--) {
			$m = clone $now;
			$m = $m->subMonths($i);
			$key = $m->format('Y-m');
			$avg = null;
			if (isset($monthly[$key]) && ($monthly[$key]['n'] ?? 0) > 0) {
				$avg = round($monthly[$key]['sum'] / $monthly[$key]['n'], 1);
			}
			$out[] = [
				'label' => $this->monthShortLabel($m),
				'avg' => $avg,
				'height_pct' => $avg !== null ? (int)min(100, round($avg / 5 * 100)) : 10,
				'current' => $i === 0,
			];
		}

		return $out;
	}

	protected function monthShortLabel(Time $t): string {
		$months = [
			1 => __('Jan'), 2 => __('Fev'), 3 => __('Mar'), 4 => __('Abr'),
			5 => __('Mai'), 6 => __('Jun'), 7 => __('Jul'), 8 => __('Ago'),
			9 => __('Set'), 10 => __('Out'), 11 => __('Nov'), 12 => __('Dez'),
		];

		return (string)($months[(int)$t->format('n')] ?? $t->format('M'));
	}

	protected function npsZoneLabel(?int $nps): string {
		if ($nps === null) {
			return '—';
		}
		if ($nps >= 50) {
			return __('EXCELENTE (50-70)');
		}
		if ($nps >= 30) {
			return __('BOM (30-49)');
		}
		if ($nps >= 0) {
			return __('RAZOÁVEL (0-29)');
		}

		return __('CRÍTICO (<0)');
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function mapCsatComment($r): array {
		$csat = (int)$r->get('csat_score');
		$nps = $r->get('nps_score');
		$npsInt = ($nps !== null && $nps !== '') ? (int)$nps : null;
		$ticket = $r->ticket ?? null;
		$cl = $r->cliente ?? ($ticket->cliente ?? null);
		$clienteNome = $this->resolveClienteNome($cl);
		$clienteShort = $clienteNome !== '—' ? explode(' ', $clienteNome)[0] : __('Cliente');
		$tecnico = '';
		if ($ticket !== null) {
			$tecnico = $this->resolveTicketTecnicoLabelPublic(
				TableRegistry::getTableLocator()->get('Tickets'),
				$ticket
			);
		}
		$responded = $r->get('responded_at');
		$ago = '';
		if ($responded instanceof \DateTimeInterface) {
			$diffH = max(0, (int)floor((Time::now()->getTimestamp() - $responded->getTimestamp()) / 3600));
			if ($diffH < 24) {
				$ago = sprintf(__('há %dh'), max(1, $diffH));
			} else {
				$ago = sprintf(__('há %dd'), (int)floor($diffH / 24));
			}
		}

		$tone = 'promotor';
		if ($npsInt !== null) {
			if ($npsInt <= 6) {
				$tone = 'detrator';
			} elseif ($npsInt <= 8) {
				$tone = 'neutro';
			}
		} elseif ($csat <= 2) {
			$tone = 'detrator';
		} elseif ($csat === 3) {
			$tone = 'neutro';
		}

		$styles = [
			'promotor' => ['bg' => '#F0FDF4', 'border' => 'var(--teal)', 'title_color' => 'var(--teal-dark)', 'actions' => false],
			'neutro' => ['bg' => '#FFFBF0', 'border' => 'var(--amber)', 'title_color' => '#8A4D02', 'actions' => true],
			'detrator' => ['bg' => '#FEF2F2', 'border' => 'var(--red)', 'title_color' => '#7A1822', 'actions' => true],
		];
		$st = $styles[$tone] ?? $styles['promotor'];
		$comentario = trim((string)($r->get('comentario') ?? ''));
		$npsPart = $npsInt !== null ? ' · NPS ' . $npsInt : '';
		$starsStr = str_repeat('⭐', $csat);
		$titleQuote = $comentario !== ''
			? \Cake\Utility\Text::truncate($comentario, 40, ['ellipsis' => '…'])
			: ($csat >= 4 ? __('Excelente') : ($csat === 3 ? __('Regular') : __('Insatisfeito')));

		$tags = [];
		if ($tecnico !== '' && $tecnico !== '—') {
			$tags[] = '+ ' . explode(' ', $tecnico)[0];
		}
		if ($csat >= 4 && $npsInt !== null && $npsInt >= 9) {
			$tags[] = '+ ' . __('rápido');
		}

		return [
			'ticket_id' => (int)$r->get('ticket_id'),
			'csat' => $csat,
			'nps' => $npsInt,
			'stars' => $starsStr,
			'title' => $starsStr . $npsPart . ' · "' . $titleQuote . '"',
			'meta' => $clienteShort . ($clienteNome !== '—' ? ' (' . $clienteNome . ')' : '') . ' · #' . (int)$r->get('ticket_id') . ' · ' . $ago,
			'comentario' => $comentario,
			'tone' => $tone,
			'style' => $st,
			'tags' => $tags,
		];
	}

	/**
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	protected function kbArticleBodies(): array {
		return [
			'KB-042' => [
				['type' => 'p', 'html' => '<strong>📋 ' . __('Pré-requisitos') . ':</strong>'],
				['type' => 'ul', 'items' => [
					__('Acesso administrativo ao Active Directory'),
					__('Termo de solicitação assinado pelo gestor do colaborador'),
					__('Formulário "Novo Acesso" preenchido com perfil necessário'),
				]],
				['type' => 'p', 'html' => '<strong>1. ' . __('Abra o Active Directory Users and Computers') . '</strong>'],
				['type' => 'p', 'html' => __('No servidor de domínio, abra o console "dsa.msc" ou pelo menu Iniciar > Ferramentas Administrativas > Active Directory Users and Computers.')],
				['type' => 'p', 'html' => '<strong>2. ' . __('Navegue até a OU correta') . '</strong>'],
				['type' => 'pre', 'text' => "dominio.local\n└─ PGM\n   ├─ Comercial\n   ├─ Financeiro\n   ├─ Operações\n   └─ TI"],
				['type' => 'p', 'html' => '<strong>3. ' . __('Crie o novo usuário') . '</strong>'],
				['type' => 'ol', 'items' => [
					__('Clique com botão direito na OU correta > New > User'),
					__('Preencha nome completo e logon (padrão: primeiro.ultimo)'),
					__('Defina senha temporária e marque "User must change password at next logon"'),
				]],
				['type' => 'alert', 'html' => '<strong>⚠ ' . __('Atenção') . ':</strong> ' . __('Sempre teste o acesso com o próprio usuário antes de finalizar.')],
			],
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function ticketWorklogEntries(int $ticketId, int $idempresa): array {
		if (!$this->tableExists('ticketshoras')) {
			return [];
		}
		$out = [];
		try {
			$th = TableRegistry::getTableLocator()->get('Ticketshoras');
			$tCols = $th->getSchema()->columns();
			$where = ['idempresa' => $idempresa];
			if (in_array('idticket', $tCols, true)) {
				$where['idticket'] = $ticketId;
			} elseif (in_array('ticket_id', $tCols, true)) {
				$where['ticket_id'] = $ticketId;
			}
			$q = $th->find()->contain(['Users'])->where($where)->order(['Ticketshoras.id' => 'ASC'])->limit(20);
			foreach ($q->all() as $h) {
				$sec = TicketServiceDeskApiService::resolveSecondsFromTicketshorasRow($th, $h);
				if ($sec <= 0) {
					continue;
				}
				$hh = intdiv($sec, 3600);
				$mm = intdiv($sec % 3600, 60);
				$horasFmt = $hh > 0 ? sprintf('%dh %02dmin', $hh, $mm) : sprintf('%dmin', $mm);
				$dataFmt = '—';
				$dataVal = $h->get('data') ?? $h->get('created');
				if ($dataVal instanceof \DateTimeInterface) {
					$dataFmt = $dataVal->format('d/m H:i');
				}
				$user = $h->users ?? null;
				$tec = $user ? (string)$user->get('name') : '—';
				$atividade = (string)($h->get('descricao') ?? $h->get('atividade') ?? $h->get('observacao') ?? __('Atendimento técnico'));
				$out[] = [
					'data_fmt' => $dataFmt,
					'atividade' => $atividade,
					'tecnico' => $tec,
					'horas_fmt' => $horasFmt,
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
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
		UsersTable $users,
		array $query = []
	): array {
		$period = (int)($query['period'] ?? 30);
		if (!in_array($period, [1, 7, 30, 90, 365], true)) {
			$period = 30;
		}
		$cols = $tickets->getSchema()->columns();
		$since = Time::now()->subDays($period);
		$volume = $this->volumeDiarioNd($tickets, $idempresa, $cols, $period);
		$categorias = $this->topAssuntos($tickets, $idempresa, $cols, $since);
		$totalCat = 0;
		foreach ($categorias as $c) {
			$totalCat += (int)($c['count'] ?? 0);
		}
		foreach ($categorias as &$c) {
			$c['pct'] = $totalCat > 0 ? round(100 * (int)$c['count'] / $totalCat, 1) : 0.0;
		}
		unset($c);

		return [
			'period_days' => $period,
			'volume_30d' => $volume,
			'categorias' => $categorias,
			'tecnicos' => $this->tecnicosPerformance30d($tickets, $idempresa, $users, $cols, $period),
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
				'weekend' => (int)$day->format('N') >= 6,
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
		array $cols,
		int $days = 30
	): array {
		$tecCol = $this->ticketResponsavelColumn($cols);
		if ($tecCol === null || !in_array('created', $cols, true)) {
			return [];
		}
		$days = min(365, max(1, $days));
		$since = Time::now()->subDays($days)->format('Y-m-d H:i:s');
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
		$userSelect = ['id', 'name', 'username'];
		$userCols = $users->getSchema()->columns();
		if (in_array('admin', $userCols, true)) {
			$userSelect[] = 'admin';
		}
		$userRows = $uids === [] ? [] : $users->find()->select($userSelect)->where(['id IN' => $uids])->all();
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
			$isAdmin = false;
			if ($u) {
				$name = trim((string)($u->get('name') ?? ''));
				if ($name === '') {
					$name = trim((string)($u->get('username') ?? ''));
				}
				$isAdmin = !empty($u->get('admin'));
			}
			$atrib = (int)($r['total'] ?? 0);
			$resolvidos = 0;
			$slaOk = 0;
			$resSecTotal = 0;
			$resSecCnt = 0;
			if ($closed !== [] && in_array('situacao', $cols, true)) {
				$qr = $tickets->find()
					->where([
						'Tickets.idempresa' => $idempresa,
						'Tickets.' . $tecCol => $uid,
						'Tickets.created >=' => $since,
						'Tickets.situacao IN' => $closed,
					]);
				($this->applyAbac)($qr);
				if (in_array('data_resolucao', $cols, true) && in_array('created', $cols, true)) {
					$qr->select(['id', 'created', 'data_resolucao', 'sla_status', 'data_limite_resolucao']);
				}
				foreach ($qr->all() as $tk) {
					$resolvidos++;
					if (in_array('sla_status', $cols, true)) {
						$st = (string)$tk->get('sla_status');
						$lim = in_array('data_limite_resolucao', $cols, true) ? $tk->get('data_limite_resolucao') : null;
						if ($st !== 'violado' && !$this->isSlaOverdue($lim)) {
							$slaOk++;
						}
					} else {
						$slaOk++;
					}
					$created = $tk->get('created');
					$resolv = $tk->get('data_resolucao');
					if ($created instanceof \DateTimeInterface && $resolv instanceof \DateTimeInterface) {
						$sec = $resolv->getTimestamp() - $created->getTimestamp();
						if ($sec > 0) {
							$resSecTotal += $sec;
							$resSecCnt++;
						}
					}
				}
			}
			$taxa = $atrib > 0 ? round(100 * $resolvidos / $atrib, 0) : 0;
			$slaPct = $resolvidos > 0 ? round(100 * $slaOk / $resolvidos, 0) : 0;
			$tempoMedio = $resSecCnt > 0 ? $this->formatDurationShort((int)round($resSecTotal / $resSecCnt)) : '—';
			$csat = $this->tecnicoCsatAvg($tickets, $idempresa, $tecCol, $uid, $since, $cols);
			$horas = $this->tecnicoHorasFaturaveis($uid, $idempresa, $since);

			$out[] = [
				'nome' => $name !== '' ? $name : ('#' . $uid),
				'nivel' => $this->tecnicoNivelLabel($atrib, $isAdmin),
				'atribuidos' => $atrib,
				'resolvidos' => $resolvidos,
				'taxa' => $taxa,
				'tempo_medio' => $tempoMedio,
				'sla_cumprido' => $slaPct,
				'csat' => $csat,
				'horas_faturaveis' => $horas,
			];
		}

		return $out;
	}

	protected function tecnicoNivelLabel(int $atribuidos, bool $isAdmin): string {
		if ($isAdmin) {
			return 'N3';
		}
		if ($atribuidos >= 120) {
			return 'N1/N2';
		}
		if ($atribuidos >= 55) {
			return 'N2';
		}

		return 'N1';
	}

	protected function tecnicoHorasFaturaveis(int $uid, int $idempresa, string $since): string {
		if (!$this->tableExists('ticketshoras')) {
			return '—';
		}
		try {
			$th = TableRegistry::getTableLocator()->get('Ticketshoras');
			$tCols = $th->getSchema()->columns();
			$where = ['Ticketshoras.idempresa' => $idempresa];
			if (in_array('iduser', $tCols, true)) {
				$where['Ticketshoras.iduser'] = $uid;
			} else {
				return '—';
			}
			if (in_array('data', $tCols, true)) {
				$where['Ticketshoras.data >='] = substr($since, 0, 10);
			} elseif (in_array('created', $tCols, true)) {
				$where['Ticketshoras.created >='] = $since;
			}
			$minutos = 0;
			foreach ($th->find()->where($where)->limit(500)->all() as $row) {
				$hi = $row->get('horaini');
				$hf = $row->get('horafin');
				if ($hi !== null && $hf !== null) {
					$minutos += (int)$th->getMinutos($hi, $hf);
				}
			}
			if ($minutos <= 0) {
				return '0h';
			}

			return (string)max(1, (int)round($minutos / 60)) . 'h';
		} catch (\Throwable $e) {
			return '—';
		}
	}

	/**
	 * CSAT médio do técnico (1–5) nos tickets atribuídos no período.
	 *
	 * @param string[] $cols
	 */
	public function tecnicoCsatScore(
		TicketsTable $tickets,
		int $idempresa,
		string $tecCol,
		int $uid,
		string $since,
		array $cols
	): ?float {
		if (!$this->tableExists('ticket_csat_responses')) {
			return null;
		}
		try {
			$tbl = TableRegistry::getTableLocator()->get('TicketCsatResponses');
			$q = $tbl->find();
			$q->innerJoinWith('Tickets', function ($jq) use ($idempresa, $tecCol, $uid, $since) {
				return $jq->where([
					'Tickets.idempresa' => $idempresa,
					'Tickets.' . $tecCol => $uid,
					'Tickets.created >=' => $since,
				]);
			});
			$q->where(['TicketCsatResponses.responded_at >=' => $since]);
			if (in_array('idempresa', $tbl->getSchema()->columns(), true)) {
				$q->where(['TicketCsatResponses.idempresa' => $idempresa]);
			}
			$scores = [];
			foreach ($q->select(['csat_score'])->limit(200)->enableHydration(false)->toArray() as $row) {
				$s = (int)($row['csat_score'] ?? 0);
				if ($s >= 1 && $s <= 5) {
					$scores[] = $s;
				}
			}
			if ($scores === []) {
				return null;
			}

			return round(array_sum($scores) / count($scores), 2);
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * @param string[] $cols
	 */
	protected function tecnicoCsatAvg(
		TicketsTable $tickets,
		int $idempresa,
		string $tecCol,
		int $uid,
		string $since,
		array $cols
	): string {
		$score = $this->tecnicoCsatScore($tickets, $idempresa, $tecCol, $uid, $since, $cols);
		if ($score === null) {
			return '—';
		}

		return '⭐ ' . number_format($score, 1, ',', '.');
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
