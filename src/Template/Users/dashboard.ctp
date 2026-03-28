<meta http-equiv="refresh" content="180; URL=<?= $this->Url->build(["controller" => "Users", "action" => "dashboard"]) ?>">
<?= $this->Html->css('dist/css/dashboard-erp.css') ?>
<?= $this->Html->css('dist/css/dashboard-pgm.css') ?>
<!-- Dashboard para os funcionários -->
<?php if($role == 0){?>
	<?php
		$ticketsPend = $ticketsPendentesTable ?? [];
		$ticketsExec = $ticketsSendoResolvidosTable ?? [];
		$ticketsFin = $ticketsFinalizadosTable ?? [];
		$reqRows = $usuariosBloqueadosTable ?? [];
		$reqCount = count($reqRows);
		$ticketsAll = array_merge($ticketsPend, $ticketsExec);
		$pgmClienteNome = function ($reg) {
			if (empty($reg->cliente)) {
				return '—';
			}
			$c = $reg->cliente;

			return ($c->tipo == C_ClientesTipoFisica) ? $c->nome : $c->razaosocial;
		};
		$kpi = $dashPgmKpi ?? [];
		$noPrazo = (int)($kpi['sla_no_prazo'] ?? 0);
		$emRisco = (int)($kpi['sla_em_risco'] ?? 0);
		$vencido = (int)($kpi['sla_vencido'] ?? 0);
		$slaPct = (int)($kpi['sla_pct'] ?? 0);
		$abertosHoje = (int)($kpi['abertos_hoje'] ?? 0);
		$fechadosHoje = (int)($kpi['fechados_hoje'] ?? 0);
		$saldoDia = (int)($kpi['saldo_dia'] ?? 0);
		$rankingTecnicos = $kpi['ranking'] ?? [];
		$rankingPeriodLabel = $kpi['ranking_period_label'] ?? 'mês';
		$rankingMonthClosedCount = (int)($kpi['ranking_month_closed_count'] ?? 0);
		$trendLabels = $kpi['trend_labels'] ?? [];
		$trendOpened = $kpi['trend_opened'] ?? [];
		$trendClosed = $kpi['trend_closed'] ?? [];

		$topClientesCount = [];
		foreach ($ticketsAll as $t) {
			$cliNome = $pgmClienteNome($t);
			$topClientesCount[$cliNome] = ($topClientesCount[$cliNome] ?? 0) + 1;
		}
		arsort($topClientesCount);
		$topClientes = array_slice($topClientesCount, 0, 6, true);
	?>
	<div class="col-12 p-0">
		<div class="dash-pgm">
			<div class="dash-pgm-topbar">
				<div class="dash-pgm-topbar-title">Dashboard</div>
				<div class="dash-pgm-topbar-right">
					<div class="dash-pgm-clock" id="dashPgmClock"><?= date('d/m/Y') ?> — <?= date('H:i') ?></div>
					<button class="dash-pgm-notif-btn" id="dashPgmNotifBtn" type="button">
						<i class="fas fa-bell"></i>
						<span class="dash-pgm-notif-dot<?= $reqCount === 0 ? ' is-hidden' : '' ?>" id="dashPgmNotifDot"></span>
					</button>
				</div>
			</div>

			<div class="dash-pgm-notif-panel" id="dashPgmNotifPanel">
				<div class="dash-pgm-notif-header">
					Notificações <span id="dashPgmNotifCount"><?= $reqCount === 0 ? 'nenhuma pendente' : ((int)$reqCount . ' nova' . ((int)$reqCount !== 1 ? 's' : '')) ?></span>
				</div>
				<?php
					$urlReqAcesso = $this->Url->build(['controller' => 'Users', 'action' => 'requisicoesAcesso']);
				?>
				<?php foreach (array_slice($reqRows, 0, 3) as $u): ?>
					<?php $nomeReq = !empty($u->name) ? $u->name : (!empty($u->username) ? $u->username : 'Usuário'); ?>
					<a href="<?= h($urlReqAcesso) ?>" class="dash-pgm-notif-item dash-pgm-notif-item-link">
						<div class="dash-pgm-notif-icon purple"><i class="fas fa-user-lock"></i></div>
						<div class="dash-pgm-notif-body">
							<strong>Nova requisição de acesso</strong>
							<p><?= h($nomeReq) ?></p>
							<div class="dash-pgm-notif-time">Abrir requisições →</div>
						</div>
					</a>
				<?php endforeach; ?>
				<?php if ($reqCount > 0): ?>
					<a href="<?= h($urlReqAcesso) ?>" class="dash-pgm-notif-footer">Gerenciar requisições de acesso</a>
				<?php endif; ?>
			</div>

			<div class="dash-pgm-content" id="dashPgmContent">

			<!-- Módulos rápidos -->
			<div class="dash-pgm-modules">
				<?php
					$_ctrl = $this->request->getParam('controller');
					$modulos = [
						['label' => 'Clientes',   'icon' => 'fa-building',             'url' => ['controller'=>'Clientes','action'=>'index'],       'active' => $_ctrl === 'Clientes'],
						['label' => 'Produtos',   'icon' => 'fa-boxes',                'url' => ['controller'=>'Produtos','action'=>'index'],        'active' => $_ctrl === 'Produtos'],
						['label' => 'OS',         'icon' => 'fa-file-signature',       'url' => ['controller'=>'Ordensservico','action'=>'index'],   'active' => $_ctrl === 'Ordensservico'],
						['label' => 'Tickets',    'icon' => 'fa-ticket-alt',           'url' => ['controller'=>'Servicedesk','action'=>'index'],     'active' => $_ctrl === 'Servicedesk'],
						['label' => 'Orçamentos', 'icon' => 'fa-file-invoice-dollar',  'url' => ['controller'=>'Orcamentos','action'=>'index'],      'active' => $_ctrl === 'Orcamentos'],
						['label' => 'Locação',    'icon' => 'fa-file-invoice',         'url' => ['controller'=>'Faturas','action'=>'index'],         'active' => $_ctrl === 'Faturas'],
						['label' => 'Agenda',     'icon' => 'fa-calendar-alt',         'url' => ['controller'=>'Agenda','action'=>'calendario'],     'active' => $_ctrl === 'Agenda'],
						['label' => 'Senhas',     'icon' => 'fa-lock',                 'url' => ['controller'=>'Bancosenhas','action'=>'index'],     'active' => $_ctrl === 'Bancosenhas'],
					];
					foreach ($modulos as $mod):
				?>
				<?= $this->Html->link(
					'<div class="dash-pgm-mod-icon"><i class="fas ' . $mod['icon'] . '"></i></div>' . h($mod['label']),
					$mod['url'],
					['class' => 'dash-pgm-mod' . ($mod['active'] ? ' mod-active' : ''), 'escape' => false]
				) ?>
				<?php endforeach; ?>
			</div>

				<div class="dash-pgm-stats-grid">
					<div class="dash-pgm-stat-card" data-filter="aguardando">
						<div class="dash-pgm-stat-icon orange"><i class="fas fa-tools"></i></div>
						<div class="dash-pgm-stat-info">
							<div class="dash-pgm-stat-label">Tickets aguardando técnico</div>
							<div class="dash-pgm-stat-value orange"><?= count($ticketsPend) ?></div>
							<div class="dash-pgm-stat-hint">Clique para filtrar</div>
						</div>
					</div>
					<div class="dash-pgm-stat-card" data-filter="execucao">
						<div class="dash-pgm-stat-icon blue"><i class="fas fa-play-circle"></i></div>
						<div class="dash-pgm-stat-info">
							<div class="dash-pgm-stat-label">Tickets em execução</div>
							<div class="dash-pgm-stat-value blue"><?= count($ticketsExec) ?></div>
							<div class="dash-pgm-stat-hint">Clique para filtrar</div>
						</div>
					</div>
					<div class="dash-pgm-stat-card" data-filter="finalizados">
						<div class="dash-pgm-stat-icon green"><i class="fas fa-check-circle"></i></div>
						<div class="dash-pgm-stat-info">
							<div class="dash-pgm-stat-label">Tickets finalizados</div>
							<div class="dash-pgm-stat-value green"><?= (int)($ticketsFinalizadosCount ?? 0) ?></div>
							<div class="dash-pgm-stat-hint">Clique para filtrar</div>
						</div>
					</div>
					<div class="dash-pgm-stat-card" data-filter="requisicoes" id="dashPgmReqCard">
						<div class="dash-pgm-stat-icon purple"><i class="fas fa-user-lock"></i></div>
						<div class="dash-pgm-stat-info">
							<div class="dash-pgm-stat-label">Requisições de acesso</div>
							<div class="dash-pgm-stat-value purple" id="dashPgmReqCount"><?= $reqCount ?></div>
							<div class="dash-pgm-stat-hint">Clique para filtrar</div>
						</div>
					</div>
				</div>

				<div class="dash-pgm-filter-section" id="dashPgmFilterSection">
					<div class="dash-pgm-filter-bar">
						<div class="dash-pgm-filter-title-wrap">
							<div class="dash-pgm-filter-dot" id="dashPgmFilterDot"></div>
							<span class="dash-pgm-filter-title" id="dashPgmFilterTitle">Filtro</span>
						</div>
						<span class="dash-pgm-filter-subtitle" id="dashPgmFilterSubtitle"></span>
						<button class="dash-pgm-filter-close" id="dashPgmFilterClose" type="button">✕</button>
					</div>
					<div class="dash-pgm-full-table-card">
						<div class="dash-pgm-table-scroll">
							<table class="dash-pgm-table" id="dashPgmFilterTable">
								<thead id="dashPgmFilterThead"></thead>
								<tbody id="dashPgmFilterTbody"></tbody>
							</table>
						</div>
					</div>
				</div>

				<div class="dash-pgm-mid-row">
					<div class="dash-pgm-mini-card">
						<div class="dash-pgm-mini-title">SLA em Tempo Real</div>
						<div class="dash-pgm-sla-value"><?= $slaPct ?>%</div>
						<div class="dash-pgm-sla-grid">
							<div><span class="dot green"></span> <?= $noPrazo ?> no prazo</div>
							<div><span class="dot orange"></span> <?= $emRisco ?> em risco</div>
							<div><span class="dot red"></span> <?= $vencido ?> vencidos</div>
						</div>
					</div>
					<div class="dash-pgm-mini-card">
						<div class="dash-pgm-mini-title">Saldo do Dia</div>
						<div class="dash-pgm-saldo-row"><span>Abertos hoje</span><strong><?= $abertosHoje ?></strong></div>
						<div class="dash-pgm-saldo-row"><span>Fechados hoje</span><strong><?= $fechadosHoje ?></strong></div>
						<div class="dash-pgm-saldo-row"><span>Saldo</span><strong><?= $saldoDia >= 0 ? '+' : '' ?><?= $saldoDia ?></strong></div>
					</div>
					<div class="dash-pgm-mini-card">
						<div class="dash-pgm-mini-title">Volume — últimos 30 dias</div>
						<canvas id="dashPgmTrendChart"></canvas>
					</div>
				</div>

				<div class="dash-pgm-bottom-row">
					<div class="dash-pgm-table-card">
						<div class="dash-pgm-table-header"><span>Tickets aguardando técnico</span><span class="badge orange"><?= count($ticketsPend) ?></span></div>
						<div class="dash-pgm-table-scroll">
							<table class="dash-pgm-table">
								<thead><tr><th>ID</th><th>Cliente</th><th>Data</th><th>SLA</th></tr></thead>
								<tbody>
									<?php foreach ($ticketsPend as $reg): ?>
										<?php
											$dias = max(0, (int) floor((time() - strtotime((string)$reg->created)) / 86400));
											$slaClass = $dias <= 3 ? 'sla-ok' : ($dias <= 10 ? 'sla-warn' : 'sla-overdue');
											$dotClass = $dias <= 3 ? 'green' : ($dias <= 10 ? 'orange' : 'red');
											$clienteNome = $pgmClienteNome($reg);
										?>
										<tr class="dash-pgm-row">
											<td class="td-id">#<?= h($reg->id) ?></td>
											<td class="td-client"><span class="td-client-inner"><span class="td-client-name"><?= h($clienteNome) ?></span></span></td>
											<td class="td-date"><?= date_format($reg->created, 'd/m/Y') ?></td>
											<td><span class="sla-badge <?= $slaClass ?>"><span class="dot <?= $dotClass ?>"></span><?= $dias ?>d</span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>

					<div class="dash-pgm-table-card">
						<div class="dash-pgm-table-header"><span>Tickets em execução</span><span class="badge blue"><?= count($ticketsExec) ?></span></div>
						<div class="dash-pgm-table-scroll">
							<table class="dash-pgm-table">
								<thead><tr><th>ID</th><th>Cliente</th><th>Data</th><th>SLA</th></tr></thead>
								<tbody>
									<?php foreach ($ticketsExec as $reg): ?>
										<?php
											$dias = max(0, (int) floor((time() - strtotime((string)$reg->created)) / 86400));
											$slaClass = $dias <= 3 ? 'sla-ok' : ($dias <= 10 ? 'sla-warn' : 'sla-overdue');
											$dotClass = $dias <= 3 ? 'green' : ($dias <= 10 ? 'orange' : 'red');
											$refMod = !empty($reg->modified) ? $reg->modified : $reg->created;
											$isStagnant = (time() - strtotime((string)$refMod)) >= 86400;
											$clienteNome = $pgmClienteNome($reg);
										?>
										<tr class="dash-pgm-row <?= $isStagnant ? 'stagnant' : '' ?>">
											<td class="td-id">#<?= h($reg->id) ?></td>
											<td class="td-client"><span class="td-client-inner"><span class="td-client-name"><?= h($clienteNome) ?></span><?= $isStagnant ? '<span class="stagnant-tag">+24h</span>' : '' ?></span></td>
											<td class="td-date"><?= date_format($reg->created, 'd/m/Y') ?></td>
											<td><span class="sla-badge <?= $slaClass ?>"><span class="dot <?= $dotClass ?>"></span><?= $dias ?>d</span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>

					<div class="dash-pgm-ranking-card">
						<div class="dash-pgm-table-header"><span>Ranking Técnicos</span><span class="badge blue"><?= h($rankingPeriodLabel) ?></span></div>
						<div class="dash-pgm-ranking-scroll">
							<?php if (!empty($rankingTecnicos)): ?>
								<?php foreach ($rankingTecnicos as $row): ?>
									<div class="dash-pgm-ranking-item"><span><?= (int)$row['place'] ?></span><strong><?= h($row['nome']) ?></strong><small><?= (int)$row['tickets'] ?> tickets</small></div>
								<?php endforeach; ?>
							<?php elseif ($rankingMonthClosedCount === 0): ?>
								<div class="dash-pgm-ranking-item dash-pgm-ranking-empty"><span>—</span><strong>Sem dados</strong><small>nenhum ticket finalizado no mês calendário (mesma janela do ranking)</small></div>
							<?php else: ?>
								<div class="dash-pgm-ranking-item dash-pgm-ranking-empty"><span>—</span><strong>Sem ranking</strong><small><?= (int)$rankingMonthClosedCount ?> fechamento(s) no período sem técnico responsável atribuído ao ticket.</small></div>
							<?php endif; ?>
						</div>
					</div>

					<div class="dash-pgm-clients-card">
						<div class="dash-pgm-table-header"><span>Top Clientes</span><span class="badge purple">vol.</span></div>
						<div class="dash-pgm-clients-scroll">
							<?php foreach ($topClientes as $nome => $qtd): ?>
								<div class="dash-pgm-client-item"><span><?= h($nome) ?></span><strong><?= (int)$qtd ?></strong></div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
	<script>
	(function() {
		var filterData = {
			aguardando: {
				color: '#d29922',
				title: 'Tickets Aguardando Técnico',
				subtitle: '<?= count($ticketsPend) ?> tickets aguardando atribuição',
				head: ['ID', 'Cliente', 'Data', 'SLA'],
				rows: [
					<?php foreach ($ticketsPend as $reg): ?>
					<?php
						$dias = max(0, (int) floor((time() - strtotime((string)$reg->created)) / 86400));
						$slaClass = $dias <= 3 ? 'sla-ok' : ($dias <= 10 ? 'sla-warn' : 'sla-overdue');
						$dotClass = $dias <= 3 ? 'green' : ($dias <= 10 ? 'orange' : 'red');
						$clienteNome = $pgmClienteNome($reg);
						$clienteCellAg = '<span class="td-client-inner"><span class="td-client-name">' . h($clienteNome) . '</span></span>';
					?>
					[<?= json_encode('#' . $reg->id) ?>, <?= json_encode($clienteCellAg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode(date_format($reg->created, 'd/m/Y')) ?>, <?= json_encode('<span class="sla-badge ' . $slaClass . '"><span class="dot ' . $dotClass . '"></span>' . $dias . ' dias</span>') ?>],
					<?php endforeach; ?>
				]
			},
			execucao: {
				color: '#388bfd',
				title: 'Tickets Em Execução',
				subtitle: '<?= count($ticketsExec) ?> tickets em andamento',
				head: ['ID', 'Cliente', 'Data', 'SLA'],
				rows: [
					<?php foreach ($ticketsExec as $reg): ?>
					<?php
						$dias = max(0, (int) floor((time() - strtotime((string)$reg->created)) / 86400));
						$slaClass = $dias <= 3 ? 'sla-ok' : ($dias <= 10 ? 'sla-warn' : 'sla-overdue');
						$dotClass = $dias <= 3 ? 'green' : ($dias <= 10 ? 'orange' : 'red');
						$refMod = !empty($reg->modified) ? $reg->modified : $reg->created;
						$stagnantTag = (time() - strtotime((string)$refMod)) >= 86400 ? '<span class="stagnant-tag">+24h</span>' : '';
						$clienteNome = $pgmClienteNome($reg);
						$clienteCellExec = '<span class="td-client-inner"><span class="td-client-name">' . h($clienteNome) . '</span>' . $stagnantTag . '</span>';
					?>
					[<?= json_encode('#' . $reg->id) ?>, <?= json_encode($clienteCellExec, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode(date_format($reg->created, 'd/m/Y')) ?>, <?= json_encode('<span class="sla-badge ' . $slaClass . '"><span class="dot ' . $dotClass . '"></span>' . $dias . ' dias</span>') ?>],
					<?php endforeach; ?>
				]
			},
			finalizados: {
				color: '#3fb950',
				title: 'Tickets Finalizados',
				subtitle: '<?= (int)($ticketsFinalizadosCount ?? 0) ?> na empresa · detalhe: até <?= count($ticketsFin) ?> mais recentes',
				head: ['ID', 'Cliente', 'Encerrado', 'Status'],
				rows: [
					<?php foreach ($ticketsFin as $reg): ?>
					<?php
						$clienteNome = $pgmClienteNome($reg);
						$refData = (isset($reg->modified) && $reg->modified !== null) ? $reg->modified : $reg->created;
					?>
					[<?= json_encode('#' . $reg->id) ?>, <?= json_encode($clienteNome) ?>, <?= json_encode(date_format($refData, 'd/m/Y')) ?>, "Finalizado"],
					<?php endforeach; ?>
				]
			},
			requisicoes: {
				color: '#bc8cff',
				title: 'Requisições de Acesso',
				subtitle: '<?= $reqCount ?> requisições aguardando aprovação',
				head: ['Usuário', 'Módulo', 'Solicitado', 'Status', 'Ações'],
				rows: [
					<?php foreach ($reqRows as $u): ?>
					<?php
						$nomeReq = !empty($u->name) ? $u->name : (!empty($u->username) ? $u->username : 'Usuário');
						$urlDesbloquear = $this->Url->build(['controller' => 'Users', 'action' => 'desbloquear', $u->id]);
						$acaoHtml = ((int)($admin ?? 0) === 1)
							? '<a class="dash-pgm-liberar" href="' . h($urlDesbloquear) . '" onclick="event.stopPropagation();">Liberar</a>'
							: '<span class="dash-pgm-no-action" title="Somente administrador pode liberar.">—</span>';
					?>
					[<?= json_encode($nomeReq) ?>, "Acesso", <?= json_encode(!empty($u->created) ? date_format($u->created, 'd/m/Y') : date('d/m/Y')) ?>, <?= json_encode('<span class="sla-badge" style="background:#2d1f4e;color:#bc8cff">Pendente</span>') ?>, <?= json_encode($acaoHtml, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>],
					<?php endforeach; ?>
				]
			}
		};

		var activeFilter = null;
		var filterSection = document.getElementById('dashPgmFilterSection');
		var filterThead = document.getElementById('dashPgmFilterThead');
		var filterTbody = document.getElementById('dashPgmFilterTbody');
		var statCards = document.querySelectorAll('.dash-pgm-stat-card');

		function selectRow(row) {
			var tbody = row.closest('tbody');
			if (!tbody) return;
			tbody.querySelectorAll('tr.selected').forEach(function(r){ r.classList.remove('selected'); });
			row.classList.add('selected');
		}
		window.dashPgmSelectRow = selectRow;

		function closeFilter() {
			activeFilter = null;
			statCards.forEach(function(c){ c.classList.remove('active-filter'); });
			filterSection.classList.remove('visible');
		}

		function openFilter(key) {
			if (activeFilter === key) {
				closeFilter();
				return;
			}
			activeFilter = key;
			var d = filterData[key];
			statCards.forEach(function(c){ c.classList.remove('active-filter'); });
			document.querySelector('.dash-pgm-stat-card[data-filter="' + key + '"]').classList.add('active-filter');
			document.getElementById('dashPgmFilterTitle').textContent = d.title;
			document.getElementById('dashPgmFilterSubtitle').textContent = d.subtitle;
			document.getElementById('dashPgmFilterDot').style.background = d.color;
			filterThead.innerHTML = '<tr>' + d.head.map(function(h){ return '<th>' + h + '</th>'; }).join('') + '</tr>';
			filterTbody.innerHTML = d.rows.length ? d.rows.map(function(r){
				return '<tr class="dash-pgm-row" onclick="dashPgmSelectRow(this)">' + r.map(function(c, i){
					var cls = i === 0 ? 'td-id' : (i === 1 ? 'td-client' : (i === 2 ? 'td-date' : (i === 3 ? 'td-status' : (i === 4 ? 'td-actions' : ''))));
					return '<td class="' + cls + '">' + c + '</td>';
				}).join('') + '</tr>';
			}).join('') : '<tr><td colspan="' + d.head.length + '" class="dash-pgm-empty">Sem registros para este filtro.</td></tr>';
			filterSection.classList.add('visible');
		}

		statCards.forEach(function(card){
			card.addEventListener('click', function(){
				openFilter(card.getAttribute('data-filter'));
			});
		});
		document.getElementById('dashPgmFilterClose').addEventListener('click', closeFilter);

		document.querySelectorAll('#dashPgmContent .dash-pgm-row').forEach(function(row) {
			row.addEventListener('click', function() { selectRow(row); });
		});

		var notifBtn = document.getElementById('dashPgmNotifBtn');
		var notifPanel = document.getElementById('dashPgmNotifPanel');
		notifBtn.addEventListener('click', function(e) {
			e.stopPropagation();
			notifPanel.classList.toggle('open');
		});
		document.addEventListener('click', function(e) {
			if (!e.target.closest('#dashPgmNotifPanel') && !e.target.closest('#dashPgmNotifBtn')) {
				notifPanel.classList.remove('open');
			}
		});

		var clock = document.getElementById('dashPgmClock');
		setInterval(function() {
			var now = new Date();
			var pad = function(v){ return (v < 10 ? '0' : '') + v; };
			clock.textContent = pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear() + ' — ' + pad(now.getHours()) + ':' + pad(now.getMinutes());
		}, 30000);

		if (window.Chart) {
			var labels = <?= json_encode($trendLabels) ?>;
			var opened = <?= json_encode($trendOpened) ?>;
			var closed = <?= json_encode($trendClosed) ?>;
			new Chart(document.getElementById('dashPgmTrendChart'), {
				type: 'line',
				data: {
					labels: labels,
					datasets: [
						{ label: 'Abertos', data: opened, borderColor: '#388bfd', backgroundColor: 'rgba(56,139,253,0.07)', fill: true, tension: 0.4, pointRadius: 0, borderWidth: 1.5 },
						{ label: 'Fechados', data: closed, borderColor: '#3fb950', backgroundColor: 'rgba(63,185,80,0.07)', fill: true, tension: 0.4, pointRadius: 0, borderWidth: 1.5 }
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { display: false } },
					scales: {
						x: { grid: { color: '#21262d' }, ticks: { color: '#484f58', maxTicksLimit: 7 } },
						y: { grid: { color: '#21262d' }, ticks: { color: '#484f58', stepSize: 2 }, min: 0 }
					},
					elements: { line: { borderWidth: 1.5 } }
				},
				plugins: [{
					id: 'dashPgmChartClearBg',
					beforeDraw: function(chart) {
						var ctx = chart.ctx;
						var w = chart.width;
						var h = chart.height;
						if (w <= 0 || h <= 0) return;
						ctx.save();
						ctx.fillStyle = '#161b22';
						ctx.fillRect(0, 0, w, h);
						ctx.restore();
					}
				}]
			});
		}

	})();
	</script>
<?php } else { ?>
<style>
/* ── Dashboard Cliente (Premium) ──────────────────────────────── */
.dcli-root{display:flex;flex-direction:column;gap:20px;padding:4px 0 24px;}
/* Topbar */
.dcli-topbar{display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.dcli-topbar-left .dcli-eyebrow{font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:#1d9e75;font-weight:700;margin-bottom:3px;}
.dcli-topbar-left h1{font-size:1.5rem;font-weight:800;color:#e6edf3;margin:0;}
.dcli-topbar-left p{font-size:.8rem;color:#6e7681;margin:4px 0 0;}
/* Quick actions */
.dcli-actions{display:flex;flex-wrap:wrap;gap:8px;}
.dcli-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:.78rem;font-weight:600;text-decoration:none;transition:all .18s;border:none;cursor:pointer;}
.dcli-btn-primary{background:#1d9e75;color:#fff;}
.dcli-btn-primary:hover{background:#5cdbc0;color:#111;text-decoration:none;}
.dcli-btn-outline{background:transparent;color:#8b949e;border:1px solid #30363d;}
.dcli-btn-outline:hover{color:#e6edf3;border-color:#8b949e;text-decoration:none;}
/* KPI strip */
.dcli-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;}
.dcli-kpi{background:#161b22;border:1px solid #21262d;border-radius:12px;padding:16px 18px;display:flex;align-items:center;gap:14px;position:relative;overflow:hidden;transition:border-color .18s;}
.dcli-kpi:hover{border-color:#1d9e75;}
.dcli-kpi-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.dcli-kpi-icon.teal{background:rgba(29,158,117,.15);color:#5cdbc0;}
.dcli-kpi-icon.blue{background:rgba(56,139,253,.15);color:#79c0ff;}
.dcli-kpi-icon.purple{background:rgba(188,140,255,.15);color:#d2a8ff;}
.dcli-kpi-icon.orange{background:rgba(210,153,34,.15);color:#e3b341;}
.dcli-kpi-meta{flex:1;}
.dcli-kpi-label{font-size:.7rem;color:#6e7681;font-weight:500;margin:0 0 2px;}
.dcli-kpi-value{font-size:1.6rem;font-weight:800;color:#e6edf3;font-family:'DM Mono',monospace;line-height:1;margin:0 0 6px;}
.dcli-kpi-link{font-size:.7rem;color:#1d9e75;text-decoration:none;font-weight:600;}
.dcli-kpi-link:hover{color:#5cdbc0;text-decoration:none;}
.dcli-kpi-bar{position:absolute;bottom:0;left:0;right:0;height:2px;background:#21262d;}
.dcli-kpi-bar-fill{height:100%;background:linear-gradient(90deg,#1d9e75,#5cdbc0);}
/* Seções */
.dcli-section-row{display:grid;gap:16px;}
.dcli-section-row.two-col{grid-template-columns:1fr 1fr;}
@media(max-width:768px){.dcli-section-row.two-col{grid-template-columns:1fr;}}
/* Cards de tabela */
.dcli-card{background:#161b22;border:1px solid #21262d;border-radius:12px;overflow:hidden;}
.dcli-card-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #21262d;}
.dcli-card-title{font-size:.82rem;font-weight:700;color:#c9d1d9;}
.dcli-card-badge{background:#21262d;color:#8b949e;font-size:.68rem;font-family:'DM Mono',monospace;padding:2px 9px;border-radius:99px;font-weight:600;}
.dcli-card-link{font-size:.72rem;color:#1d9e75;text-decoration:none;font-weight:600;}
.dcli-card-link:hover{color:#5cdbc0;text-decoration:none;}
/* Tabela interna */
.dcli-table{width:100%;border-collapse:collapse;font-size:.78rem;}
.dcli-table thead th{padding:9px 14px;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6e7681;border-bottom:1px solid #21262d;white-space:nowrap;}
.dcli-table tbody tr{border-bottom:1px solid #161b2a;transition:background .12s;}
.dcli-table tbody tr:last-child{border-bottom:none;}
.dcli-table tbody tr:hover{background:#1c2230;}
.dcli-table td{padding:9px 14px;color:#c9d1d9;vertical-align:middle;}
.dcli-td-id{font-family:'DM Mono',monospace;font-size:.75rem;color:#6e7681;}
.dcli-td-link{color:#5cdbc0;text-decoration:none;font-weight:600;}
.dcli-td-link:hover{color:#1d9e75;text-decoration:none;}
.dcli-td-date{font-family:'DM Mono',monospace;font-size:.72rem;color:#6e7681;}
.dcli-empty{text-align:center;color:#6e7681;padding:24px 14px!important;font-size:.78rem;}
/* Status badges */
.dcli-status{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:99px;font-size:.68rem;font-weight:700;}
.dcli-status-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;}
.dcli-status.aguardando{background:rgba(210,153,34,.15);color:#e3b341;}
.dcli-status.aguardando .dcli-status-dot{background:#e3b341;}
.dcli-status.execucao{background:rgba(56,139,253,.15);color:#79c0ff;}
.dcli-status.execucao .dcli-status-dot{background:#388bfd;}
.dcli-status.finalizado{background:rgba(63,185,80,.15);color:#56d364;}
.dcli-status.finalizado .dcli-status-dot{background:#3fb950;}
/* Ações rápidas */
.dcli-td-actions{display:flex;gap:6px;justify-content:flex-end;}
.dcli-btn-icon{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.75rem;background:#21262d;color:#8b949e;border:none;cursor:pointer;text-decoration:none;transition:all .15s;}
.dcli-btn-icon:hover{background:#1d9e75;color:#fff;text-decoration:none;}
/* Contratos chip */
.dcli-contrato-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;background:rgba(29,158,117,.1);color:#5cdbc0;border:1px solid rgba(29,158,117,.3);border-radius:99px;font-size:.68rem;font-weight:700;}
/* Vazio state */
.dcli-empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:32px 20px;color:#6e7681;gap:8px;}
.dcli-empty-state i{font-size:1.8rem;opacity:.4;}
.dcli-empty-state p{margin:0;font-size:.78rem;}
</style>

<div class="col-12 p-0">
<div class="dcli-root">

	<!-- Topbar -->
	<div class="dcli-topbar">
		<div class="dcli-topbar-left">
			<div class="dcli-eyebrow">Portal do Cliente</div>
			<h1>Dashboard</h1>
			<p>Resumo da empresa — tickets, ordens, orçamentos e contratos.</p>
		</div>
		<div class="dcli-actions">
			<?= $this->Html->link(
				'<i class="fas fa-plus-circle"></i> Novo ticket',
				['controller' => 'Tickets', 'action' => 'add'],
				['class' => 'dcli-btn dcli-btn-primary', 'escape' => false]
			) ?>
			<?= $this->Html->link(
				'<i class="fas fa-ticket-alt"></i> Todos os tickets',
				['controller' => 'Tickets', 'action' => 'indexcliente'],
				['class' => 'dcli-btn dcli-btn-outline', 'escape' => false]
			) ?>
			<?php if ($permissaoacesso) : ?>
				<?= $this->Html->link(
					'<i class="fas fa-file-invoice-dollar"></i> Orçamentos',
					['controller' => 'Orcamentos', 'action' => 'index'],
					['class' => 'dcli-btn dcli-btn-outline', 'escape' => false]
				) ?>
				<?= $this->Html->link(
					'<i class="fas fa-building"></i> Dados da empresa',
					['controller' => 'Clientes', 'action' => 'edit', $idcliente],
					['class' => 'dcli-btn dcli-btn-outline', 'escape' => false]
				) ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- KPIs -->
	<div class="dcli-kpis">
		<div class="dcli-kpi">
			<div class="dcli-kpi-icon blue"><i class="fas fa-clipboard-list"></i></div>
			<div class="dcli-kpi-meta">
				<p class="dcli-kpi-label">Ordens de serviço</p>
				<p class="dcli-kpi-value"><?= (int)$ordensCliente ?></p>
				<?= $this->Html->link('Ver ordens →', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'dcli-kpi-link']); ?>
			</div>
			<div class="dcli-kpi-bar"><div class="dcli-kpi-bar-fill" style="width:<?= min(100, (int)$ordensCliente) ?>%"></div></div>
		</div>
		<div class="dcli-kpi">
			<div class="dcli-kpi-icon orange"><i class="fas fa-file-invoice-dollar"></i></div>
			<div class="dcli-kpi-meta">
				<p class="dcli-kpi-label">Orçamentos</p>
				<p class="dcli-kpi-value"><?= (int)$orcamentosCliente ?></p>
				<?php if ($permissaoacesso) : ?>
					<?= $this->Html->link('Ver orçamentos →', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'dcli-kpi-link']); ?>
				<?php else : ?>
					<span class="dcli-kpi-link" style="color:#484f58;">Solicite por ticket</span>
				<?php endif; ?>
			</div>
			<div class="dcli-kpi-bar"><div class="dcli-kpi-bar-fill" style="width:<?= min(100, (int)$orcamentosCliente * 4) ?>%"></div></div>
		</div>
		<div class="dcli-kpi">
			<div class="dcli-kpi-icon teal"><i class="fas fa-ticket-alt"></i></div>
			<div class="dcli-kpi-meta">
				<p class="dcli-kpi-label">Meus tickets</p>
				<p class="dcli-kpi-value"><?= (int)$ticketsCliente ?></p>
				<?= $this->Html->link('Abrir lista →', ['controller' => 'Tickets', 'action' => 'indexcliente'], ['class' => 'dcli-kpi-link']); ?>
			</div>
			<div class="dcli-kpi-bar"><div class="dcli-kpi-bar-fill" style="width:<?= min(100, (int)$ticketsCliente * 10) ?>%"></div></div>
		</div>
		<?php if ($permissaoacesso) : ?>
		<div class="dcli-kpi">
			<div class="dcli-kpi-icon purple"><i class="fas fa-file-contract"></i></div>
			<div class="dcli-kpi-meta">
				<p class="dcli-kpi-label">Contratos ativos</p>
				<p class="dcli-kpi-value"><?= count($contratos) ?></p>
				<a href="#secao-contratos" class="dcli-kpi-link">Ver contratos →</a>
			</div>
			<div class="dcli-kpi-bar"><div class="dcli-kpi-bar-fill" style="width:<?= min(100, count($contratos) * 20) ?>%"></div></div>
		</div>
		<?php endif; ?>
	</div>

	<!-- Tickets em aberto -->
	<div class="dcli-card">
		<div class="dcli-card-head">
			<span class="dcli-card-title"><i class="fas fa-tools" style="color:#e3b341;margin-right:7px;"></i>Tickets aguardando / em execução</span>
			<div style="display:flex;align-items:center;gap:10px;">
				<span class="dcli-card-badge"><?= count($ticketsPendentes) ?></span>
				<?= $this->Html->link('Ver todos →', ['controller' => 'Tickets', 'action' => 'indexcliente'], ['class' => 'dcli-card-link']); ?>
			</div>
		</div>
		<?php if (empty($ticketsPendentes)) : ?>
			<div class="dcli-empty-state">
				<i class="fas fa-check-circle" style="color:#3fb950;"></i>
				<p>Nenhum ticket aguardando no momento.</p>
			</div>
		<?php else : ?>
		<div class="table-responsive">
			<table class="dcli-table">
				<thead>
					<tr>
						<th style="width:70px;">#</th>
						<th style="width:100px;">Data</th>
						<th>Assunto</th>
						<th style="width:130px;">Status</th>
						<th style="width:110px;">Técnico</th>
						<th style="width:70px;text-align:right;">Ações</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($ticketsPendentes as $reg):
						$situacaoVal = (int)$reg->situacao;
						$stCls = $situacaoVal === 0 ? 'aguardando' : ($situacaoVal === 1 ? 'execucao' : 'finalizado');
						$tecnico = !empty($reg->user->name) ? $reg->user->name : ($autores[$reg->idtecnico ?? 0] ?? '—');
					?>
					<tr>
						<td class="dcli-td-id">#<?= h($reg->id) ?></td>
						<td class="dcli-td-date"><?= date_format($reg->created, 'd/m/Y') ?></td>
						<td style="color:#c9d1d9;font-weight:500;"><?= AssuntoTicket($reg->assunto) ?><div style="font-size:.68rem;color:#6e7681;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:340px;"><?= h(mb_substr((string)($reg->solicitacao ?? ''), 0, 80)) ?></div></td>
						<td><span class="dcli-status <?= $stCls ?>"><span class="dcli-status-dot"></span><?= SituacaoTicket($reg->situacao) ?></span></td>
						<td style="font-size:.75rem;color:#8b949e;"><?= h($tecnico) ?></td>
						<td>
							<div class="dcli-td-actions">
								<?= $this->Html->link('<i class="fas fa-eye"></i>', ['controller' => 'Tickets', 'action' => 'view', $reg->id], ['class' => 'dcli-btn-icon', 'title' => 'Ver ticket', 'escape' => false]); ?>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>

	<?php if ($permissaoacesso) : ?>
	<!-- Contratos + Orçamentos -->
	<div class="dcli-section-row two-col" id="secao-contratos">
		<!-- Contratos -->
		<div class="dcli-card">
			<div class="dcli-card-head">
				<span class="dcli-card-title"><i class="fas fa-file-contract" style="color:#d2a8ff;margin-right:7px;"></i>Contratos da empresa</span>
				<span class="dcli-card-badge"><?= count($contratos) ?></span>
			</div>
			<?php if (empty($contratos)) : ?>
				<div class="dcli-empty-state">
					<i class="fas fa-folder-open"></i>
					<p>Nenhum contrato cadastrado.</p>
				</div>
			<?php else : ?>
			<div class="table-responsive">
				<table class="dcli-table">
					<thead>
						<tr>
							<th style="width:60px;">#</th>
							<th>Descrição</th>
							<th style="width:50px;">Qtd.</th>
							<th style="width:90px;">Data</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($contratos as $reg): ?>
						<tr>
							<td class="dcli-td-id"><?= h($reg->id) ?></td>
							<td style="color:#c9d1d9;"><?= h($reg->descricao) ?></td>
							<td><span class="dcli-contrato-chip"><?= h($reg->qtde) ?></span></td>
							<td class="dcli-td-date"><?= !empty($reg->dtcontratacao) ? date_format($reg->dtcontratacao, 'd/m/Y') : '—' ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>
		</div>

		<!-- Orçamentos recentes -->
		<div class="dcli-card">
			<div class="dcli-card-head">
				<span class="dcli-card-title"><i class="fas fa-file-invoice-dollar" style="color:#e3b341;margin-right:7px;"></i>Últimos orçamentos</span>
				<div style="display:flex;align-items:center;gap:10px;">
					<span class="dcli-card-badge"><?= count($orcamentosRecentes) ?></span>
					<?= $this->Html->link('Ver todos →', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'dcli-card-link']); ?>
				</div>
			</div>
			<?php if (empty($orcamentosRecentes)) : ?>
				<div class="dcli-empty-state">
					<i class="fas fa-file-invoice"></i>
					<p>Nenhum orçamento recente.</p>
				</div>
			<?php else : ?>
			<div class="table-responsive">
				<table class="dcli-table">
					<thead>
						<tr>
							<th style="width:55px;">#</th>
							<th>Autor</th>
							<th style="width:90px;">Abertura</th>
							<th style="width:90px;">Validade</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($orcamentosRecentes as $reg): ?>
						<tr>
							<td><?= $this->Html->link('#' . h($reg->id), ['controller' => 'Orcamentos', 'action' => 'view', $reg->id], ['class' => 'dcli-td-link']); ?></td>
							<td style="font-size:.78rem;color:#c9d1d9;"><?= h($autores[$reg->idautor] ?? '—') ?></td>
							<td class="dcli-td-date"><?= date_format($reg->created, 'd/m/Y') ?></td>
							<td class="dcli-td-date"><?= date_format($reg->validoate, 'd/m/Y') ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

</div><!-- /dcli-root -->
</div>
<?php } ?>
<script>
	if (window.jQuery && $.fn && $.fn.perfectScrollbar && $("#tickets-pendentes").length && $("#tickets-sendo-resolvidos").length) {
		$("#tickets-pendentes, #tickets-sendo-resolvidos").perfectScrollbar();
	}
	$(".dash-erp-table tbody tr").on("click", function() {
		var $tbody = $(this).closest("tbody");
		$tbody.find("tr.selected").removeClass("selected");
		$(this).addClass("selected");
	});
</script>
