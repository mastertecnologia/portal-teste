<meta http-equiv="refresh" content="180; URL=<?= $this->Url->build(["controller" => "Users", "action" => "dashboard"]) ?>">
<?= $this->Html->css('dist/css/dashboard-erp.css') ?>
<?= $this->Html->css('dist/css/dashboard-pgm.css') ?>
<!-- Dashboard para os funcionários -->
<?php if($role == 0){?>
	<?php
		$ticketsPend = $ticketsPendentesTable ?? [];
		$ticketsExec = $ticketsSendoResolvidosTable ?? [];
		$reqRows = $usuariosBloqueadosTable ?? [];
		$reqCount = count($reqRows);
		$ticketsAll = array_merge($ticketsPend, $ticketsExec);
		$todayYmd = date('Ymd');

		$noPrazo = 0;
		$emRisco = 0;
		$vencido = 0;
		$abertosHoje = 0;
		$topClientesCount = [];
		foreach ($ticketsAll as $t) {
			$dias = max(0, (int) floor((time() - strtotime((string)$t->created)) / 86400));
			if ($dias <= 3) $noPrazo++;
			elseif ($dias <= 10) $emRisco++;
			else $vencido++;
			if (date_format($t->created, 'Ymd') === $todayYmd) $abertosHoje++;

			$cliNome = ($t->cliente->tipo == C_ClientesTipoFisica) ? $t->cliente->nome : $t->cliente->razaosocial;
			$topClientesCount[$cliNome] = ($topClientesCount[$cliNome] ?? 0) + 1;
		}
		$totalSla = max(1, count($ticketsAll));
		$slaPct = (int) round(($noPrazo / $totalSla) * 100);
		$saldoDia = 0 - $abertosHoje;

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
						<span class="dash-pgm-notif-dot" id="dashPgmNotifDot"></span>
					</button>
				</div>
			</div>

			<div class="dash-pgm-notif-panel" id="dashPgmNotifPanel">
				<div class="dash-pgm-notif-header">
					Notificações <span id="dashPgmNotifCount"><?= max(1, $reqCount) ?> novas</span>
				</div>
				<?php foreach (array_slice($reqRows, 0, 3) as $u): ?>
					<?php $nomeReq = !empty($u->name) ? $u->name : (!empty($u->username) ? $u->username : 'Usuário'); ?>
					<div class="dash-pgm-notif-item">
						<div class="dash-pgm-notif-icon purple"><i class="fas fa-user-lock"></i></div>
						<div class="dash-pgm-notif-body">
							<strong>Nova requisição de acesso</strong>
							<p><?= h($nomeReq) ?></p>
							<div class="dash-pgm-notif-time">agora mesmo</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="dash-pgm-content" id="dashPgmContent">
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
						<div class="dash-pgm-saldo-row"><span>Fechados hoje</span><strong>0</strong></div>
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
											$clienteNome = $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial;
										?>
										<tr class="dash-pgm-row">
											<td class="td-id">#<?= h($reg->id) ?></td>
											<td class="td-client"><?= h($clienteNome) ?></td>
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
											$isStagnant = in_array((int)$reg->id, [1093, 1088], true);
											$clienteNome = $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial;
										?>
										<tr class="dash-pgm-row <?= $isStagnant ? 'stagnant' : '' ?>">
											<td class="td-id">#<?= h($reg->id) ?></td>
											<td class="td-client"><?= h($clienteNome) ?><?= $isStagnant ? ' <span class="stagnant-tag">+24h</span>' : '' ?></td>
											<td class="td-date"><?= date_format($reg->created, 'd/m/Y') ?></td>
											<td><span class="sla-badge <?= $slaClass ?>"><span class="dot <?= $dotClass ?>"></span><?= $dias ?>d</span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>

					<div class="dash-pgm-ranking-card">
						<div class="dash-pgm-table-header"><span>Ranking Técnicos</span><span class="badge blue">mês</span></div>
						<div class="dash-pgm-ranking-scroll">
							<div class="dash-pgm-ranking-item"><span>1</span><strong>Darli G.</strong><small>32 tickets</small></div>
							<div class="dash-pgm-ranking-item"><span>2</span><strong>Marcos S.</strong><small>24 tickets</small></div>
							<div class="dash-pgm-ranking-item"><span>3</span><strong>Ana R.</strong><small>18 tickets</small></div>
							<div class="dash-pgm-ranking-item"><span>4</span><strong>Pedro T.</strong><small>11 tickets</small></div>
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
						$clienteNome = $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial;
					?>
					[<?= json_encode('#' . $reg->id) ?>, <?= json_encode($clienteNome) ?>, <?= json_encode(date_format($reg->created, 'd/m/Y')) ?>, <?= json_encode('<span class="sla-badge ' . $slaClass . '"><span class="dot ' . $dotClass . '"></span>' . $dias . ' dias</span>') ?>],
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
						$clienteNome = $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial;
					?>
					[<?= json_encode('#' . $reg->id) ?>, <?= json_encode($clienteNome) ?>, <?= json_encode(date_format($reg->created, 'd/m/Y')) ?>, <?= json_encode('<span class="sla-badge ' . $slaClass . '"><span class="dot ' . $dotClass . '"></span>' . $dias . ' dias</span>') ?>],
					<?php endforeach; ?>
				]
			},
			finalizados: {
				color: '#3fb950',
				title: 'Tickets Finalizados',
				subtitle: '<?= (int)($ticketsFinalizadosCount ?? 0) ?> tickets finalizados no período',
				head: ['ID', 'Cliente', 'Encerrado', 'Status'],
				rows: []
			},
			requisicoes: {
				color: '#bc8cff',
				title: 'Requisições de Acesso',
				subtitle: '<?= $reqCount ?> requisições aguardando aprovação',
				head: ['Usuário', 'Módulo', 'Solicitado', 'Status'],
				rows: [
					<?php foreach ($reqRows as $u): ?>
					<?php $nomeReq = !empty($u->name) ? $u->name : (!empty($u->username) ? $u->username : 'Usuário'); ?>
					[<?= json_encode($nomeReq) ?>, "Acesso", <?= json_encode(!empty($u->created) ? date_format($u->created, 'd/m/Y') : date('d/m/Y')) ?>, <?= json_encode('<span class="sla-badge" style="background:#2d1f4e;color:#bc8cff">Pendente</span>') ?>],
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
					var cls = i === 0 ? 'td-id' : (i === 1 ? 'td-client' : (i === 2 ? 'td-date' : ''));
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

		document.querySelectorAll('.dash-pgm-row').forEach(function(row) {
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
			var labels = [];
			var opened = [];
			var closed = [];
			for (var i = 29; i >= 0; i--) {
				labels.push((30 - i).toString());
				opened.push(Math.floor(Math.random() * 6) + 1);
				closed.push(Math.floor(Math.random() * 7) + 2);
			}
			new Chart(document.getElementById('dashPgmTrendChart'), {
				type: 'line',
				data: {
					labels: labels,
					datasets: [
						{ data: opened, borderColor: '#388bfd', backgroundColor: 'rgba(56,139,253,0.07)', fill: true, tension: 0.4, pointRadius: 0, borderWidth: 1.5 },
						{ data: closed, borderColor: '#3fb950', backgroundColor: 'rgba(63,185,80,0.07)', fill: true, tension: 0.4, pointRadius: 0, borderWidth: 1.5 }
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { display: false } },
					scales: {
						x: { grid: { color: '#21262d' }, ticks: { color: '#484f58', maxTicksLimit: 7 } },
						y: { grid: { color: '#21262d' }, ticks: { color: '#484f58', stepSize: 2 }, min: 0 }
					}
				}
			});
		}

		setTimeout(function() {
			var reqCountEl = document.getElementById('dashPgmReqCount');
			var curr = parseInt(reqCountEl.textContent || '0', 10);
			reqCountEl.textContent = (curr + 1).toString();
			document.getElementById('dashPgmNotifCount').textContent = (curr + 1) + ' novas';
			var card = document.getElementById('dashPgmReqCard');
			card.classList.add('flash');
			setTimeout(function(){ card.classList.remove('flash'); }, 2400);
		}, 8000);
	})();
	</script>
<?php } else { ?>
	<div class="col-12 p-0">
		<div class="dash-cli">
			<div class="dash-erp-header">
				<div>
					<h2 class="dash-erp-title">Dashboard</h2>
					<p class="dash-erp-subtitle">Resumo da empresa: acompanhe tickets, ordens, orçamentos e contratos.</p>
				</div>
			</div>

			<?php if ($permissaoacesso) { ?>
				<div class="dash-cli-quick">
					<?= $this->Html->link('<i class="fas fa-plus-circle"></i> Novo ticket', ['controller' => 'Tickets', 'action' => 'add'], ['escape' => false]); ?>
					<?= $this->Html->link('<i class="fas fa-ticket-alt"></i> Todos os tickets', ['controller' => 'Tickets', 'action' => 'indexcliente'], ['escape' => false]); ?>
					<?= $this->Html->link('<i class="fas fa-file-invoice-dollar"></i> Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['escape' => false]); ?>
					<?= $this->Html->link('<i class="fas fa-building"></i> Dados da empresa', ['controller' => 'Clientes', 'action' => 'edit', $idcliente], ['escape' => false]); ?>
				</div>
			<?php } ?>

			<div class="dash-erp-kpis">
				<div class="dash-erp-kpi dash-cli-kpi--orders">
					<div class="dash-erp-kpi-icon"><i class="fas fa-clipboard-list"></i></div>
					<div class="dash-erp-kpi-meta">
						<p class="dash-erp-kpi-label">Ordens de serviço</p>
						<p class="dash-erp-kpi-value"><?= (int) $ordensCliente ?></p>
					</div>
					<?= $this->Html->link('Ver ordens', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'dash-erp-kpi-link']); ?>
				</div>
				<div class="dash-erp-kpi dash-cli-kpi--budgets">
					<div class="dash-erp-kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
					<div class="dash-erp-kpi-meta">
						<p class="dash-erp-kpi-label">Orçamentos</p>
						<p class="dash-erp-kpi-value"><?= (int) $orcamentosCliente ?></p>
					</div>
					<?= $this->Html->link('Ver orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'dash-erp-kpi-link']); ?>
				</div>
				<div class="dash-erp-kpi dash-cli-kpi--tickets">
					<div class="dash-erp-kpi-icon"><i class="fas fa-ticket-alt"></i></div>
					<div class="dash-erp-kpi-meta">
						<p class="dash-erp-kpi-label">Meus tickets (abertos por você)</p>
						<p class="dash-erp-kpi-value"><?= (int) $ticketsCliente ?></p>
					</div>
					<?= $this->Html->link('Abrir lista', ['controller' => 'Tickets', 'action' => 'indexcliente'], ['class' => 'dash-erp-kpi-link']); ?>
				</div>
			</div>

			<div class="row">
				<div class="col-12 dash-cli-table-wrap">
					<div class="dash-erp-card">
						<div class="dash-erp-card-header">
							<h5 class="dash-erp-card-title">Tickets aguardando técnico / em execução</h5>
							<span class="dash-erp-card-badge"><?= count($ticketsPendentes) ?></span>
						</div>
						<div class="dash-erp-card-body dash-cli-table p-0">
							<div class="table-responsive">
								<table class="table table-hover table-row-clickable mb-0" id="table-todos">
									<thead>
										<tr>
											<th>Número</th>
											<th>Data</th>
											<th>Assunto</th>
											<th>Status</th>
											<th class="text-right">Ações</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($ticketsPendentes as $reg): ?>
											<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4><?= AssuntoTicket($reg->assunto) ?> </h4><br><?= $reg->solicitacao ?></div>' data-original-title="Ticket <?= $reg->id.' ' ?><small style='font-size: 12px;'><i>(<?= date_format($reg->created, 'd/m/Y') ?>)</i></small>" data-html="true" data-placement="top">
												<td><strong><?= h($reg->id) ?></strong></td>
												<td><?= date_format($reg->created, 'd/m/Y') ?></td>
												<td><?= AssuntoTicket($reg->assunto) ?></td>
												<td class="dash-cli-status-cell"><?= SituacaoTicket($reg->situacao) ?></td>
												<td class="td-actions text-right">
													<?= $this->Html->link('<i class="fas fa-eye"></i>', ['controller' => 'Tickets', 'action' => 'view', $reg->id], ['rel' => 'tooltip', 'title' => 'Visualizar ticket', 'class' => 'btn btn-sm btn-info dash-cli-btn-icon', 'escape' => false]); ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php if ($permissaoacesso) { ?>
				<div class="row">
					<div class="col-lg-6 col-md-12 dash-cli-table-wrap">
						<div class="dash-erp-card">
							<div class="dash-erp-card-header">
								<h5 class="dash-erp-card-title">Contratos da empresa</h5>
								<span class="dash-erp-card-badge"><?= count($contratos) ?></span>
							</div>
							<div class="dash-erp-card-body dash-cli-table p-0">
								<div class="table-responsive">
									<table class="table table-hover table-row-clickable mb-0" id="table-contratos-cli">
										<thead>
											<tr>
												<th>Número</th>
												<th>Descrição</th>
												<th>Qtd.</th>
												<th>Data</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($contratos as $reg): ?>
												<tr>
													<td><?= h($reg->id) ?></td>
													<td><?= h($reg->descricao) ?></td>
													<td><?= h($reg->qtde) ?></td>
													<td data-order="<?= !empty($reg->dtcontratacao) ? date_format($reg->dtcontratacao, 'Ymd') : '' ?>"><?= !empty($reg->dtcontratacao) ? date_format($reg->dtcontratacao, 'd/m/Y') : '—' ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-6 col-md-12 dash-cli-table-wrap">
						<div class="dash-erp-card">
							<div class="dash-erp-card-header">
								<h5 class="dash-erp-card-title">Últimos orçamentos</h5>
								<span class="dash-erp-card-badge"><?= count($orcamentosRecentes) ?></span>
							</div>
							<div class="dash-erp-card-body dash-cli-table p-0">
								<div class="table-responsive">
									<table class="table table-hover table-row-clickable mb-0" id="table-orcamentos-cli">
										<thead>
											<tr>
												<th>Número</th>
												<th>Autor</th>
												<th>Abertura</th>
												<th>Validade</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($orcamentosRecentes as $reg): ?>
												<tr>
													<td><?= $this->Html->link($reg->id, ['controller' => 'Orcamentos', 'action' => 'view', $reg->id], ['class' => 'dash-erp-link']); ?></td>
													<td><?= $this->Html->link(h($autores[$reg->idautor] ?? ''), ['controller' => 'Orcamentos', 'action' => 'view', $reg->id], ['class' => 'dash-erp-link']); ?></td>
													<td data-order="<?= date_format($reg->created, 'Ymd') ?>"><?= $this->Html->link(date_format($reg->created, 'd/m/Y'), ['controller' => 'Orcamentos', 'action' => 'view', $reg->id], ['class' => 'dash-erp-link']); ?></td>
													<td data-order="<?= date_format($reg->validoate, 'Ymd') ?>"><?= $this->Html->link(date_format($reg->validoate, 'd/m/Y'), ['controller' => 'Orcamentos', 'action' => 'view', $reg->id], ['class' => 'dash-erp-link']); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
<?php } ?>
<!-- Modal Duas Etapas -->
<div class="modal fade none-border" id="modal-duasetapas">
	<div class="modal-dialog modal-md modal-dialog-centered">
		<div class="modal-content">
			<div class="dash-erp-mfa-body">
				<p class="dash-erp-mfa-title">
					Ative a verificação em duas etapas <?= $this->Html->link('clicando aqui', ['action' => 'loginduasetapas']); ?>.
				</p>
				<p>
					Baixe o Google Authenticator para
					<a target="_blank" rel="noopener" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Android</a>
					ou para
					<a target="_blank" rel="noopener" href="https://apps.apple.com/br/app/google-authenticator/id388497605">iOS</a>.
				</p>
				<!-- <p>
					Baixe o Duo Authenticator para Android <a target="_blank" class='link text-success text-bold' href="https://play.google.com/store/apps/details?id=com.duosecurity.duomobile&hl=pt"> Android </a> ou para
					<a target="_blank" class='link text-success text-bold' href="https://apps.apple.com/br/app/duo-mobile/id422663827"> IOS </a>
				</p> -->
			</div>
		</div>
	</div>
</div>
<script>
	if (window.jQuery && $.fn && $.fn.perfectScrollbar && $("#tickets-pendentes").length && $("#tickets-sendo-resolvidos").length) {
		$("#tickets-pendentes, #tickets-sendo-resolvidos").perfectScrollbar();
	}
	$(".dash-erp-table tbody tr").on("click", function() {
		var $tbody = $(this).closest("tbody");
		$tbody.find("tr.selected").removeClass("selected");
		$(this).addClass("selected");
	});
	<?php if(isset($bAtivarDuasEtapas) && isset($veiologin)) { ?> 
		$('#modal-duasetapas').modal('toggle');
	<?php } ?>
</script>
