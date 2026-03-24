<meta http-equiv="refresh" content="180; URL=<?= $this->Url->build(["controller" => "Users", "action" => "dashboard"]) ?>">
<?= $this->Html->css('dist/css/dashboard-erp.css') ?>
<!-- Dashboard para os funcionários -->
<?php if($role == 0){?>
	<div class="col-12 p-0">
	<div class="dash-erp">
		<div class="dash-erp-header">
			<div>
				<h2 class="dash-erp-title">Dashboard</h2>
				<p class="dash-erp-subtitle">Visão operacional rápida: chamados e aprovações pendentes.</p>
			</div>
		</div>

		<div class="dash-erp-kpis">
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon"><i class="fas fa-ticket-alt"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Tickets aguardando técnico</p>
					<p class="dash-erp-kpi-value"><?= count($ticketsPendentesTable ?? []) ?></p>
				</div>
				<a class="dash-erp-kpi-link" href="<?= $this->Url->build(['controller' => 'Tickets', 'action' => 'index']) ?>">Abrir lista</a>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon"><i class="fas fa-play-circle"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Tickets em execução</p>
					<p class="dash-erp-kpi-value"><?= count($ticketsSendoResolvidosTable ?? []) ?></p>
				</div>
				<a class="dash-erp-kpi-link" href="<?= $this->Url->build(['controller' => 'Tickets', 'action' => 'index']) ?>">Acompanhar</a>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon"><i class="fas fa-check-circle"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Tickets finalizados</p>
					<p class="dash-erp-kpi-value"><?= (int)($ticketsFinalizadosCount ?? 0) ?></p>
				</div>
				<a class="dash-erp-kpi-link" href="<?= $this->Url->build(['controller' => 'Tickets', 'action' => 'finalizados']) ?>">Ver finalizados</a>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon"><i class="fas fa-user-lock"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Requisições de acesso</p>
					<p class="dash-erp-kpi-value"><?= count($usuariosBloqueadosTable ?? []) ?></p>
				</div>
				<a class="dash-erp-kpi-link" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'requisicoesAcesso']) ?>">Ver solicitações</a>
			</div>
		</div>

		<div class="row">
			<!-- Tickets Pendentes -->
			<div class="col-lg-6 col-md-12">
				<div class="dash-erp-card">
					<div class="dash-erp-card-header">
						<h5 class="dash-erp-card-title">Tickets aguardando técnico</h5>
						<span class="dash-erp-card-badge"><?= count($ticketsPendentesTable ?? []) ?></span>
					</div>
					<div class="dash-erp-card-body">
						<div class="dash-erp-scroll" id="tickets-pendentes">
							<div class="table-responsive">
								<table class="dash-erp-table">
									<thead>
										<tr>
											<th>ID</th>
											<th>Cliente</th>
											<th>Data</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach (($ticketsPendentesTable ?? []) as $reg): ?>
											<?php $urlTicket = $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]); ?>
											<tr>
												<td><a class="dash-erp-link" target="_blank" href="<?= $urlTicket ?>"><?= $reg->id ?></a></td>
												<td><a class="dash-erp-link" target="_blank" href="<?= $urlTicket ?>"><?= $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial ?></a></td>
												<td><a class="dash-erp-link" target="_blank" href="<?= $urlTicket ?>"><?= date_format($reg->created, 'd/m/Y') ?></a></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Tickets em andamento -->
			<div class="col-lg-6 col-md-12">
				<div class="dash-erp-card">
					<div class="dash-erp-card-header">
						<h5 class="dash-erp-card-title">Tickets em execução</h5>
						<span class="dash-erp-card-badge"><?= count($ticketsSendoResolvidosTable ?? []) ?></span>
					</div>
					<div class="dash-erp-card-body">
						<div class="dash-erp-scroll" id="tickets-sendo-resolvidos">
							<div class="table-responsive">
								<table class="dash-erp-table">
									<thead>
										<tr>
											<th>ID</th>
											<th>Cliente</th>
											<th>Data</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach (($ticketsSendoResolvidosTable ?? []) as $reg): ?>
											<?php $urlTicket = $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]); ?>
											<tr>
												<td><a class="dash-erp-link" target="_blank" href="<?= $urlTicket ?>"><?= $reg->id ?></a></td>
												<td><a class="dash-erp-link" target="_blank" href="<?= $urlTicket ?>"><?= $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial ?></a></td>
												<td><a class="dash-erp-link" target="_blank" href="<?= $urlTicket ?>"><?= date_format($reg->created, 'd/m/Y') ?></a></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Requisições de acesso: ver página dedicada -->

		<!-- <div class="col-lg-2 p-r-0 p-l-0 card1">
				<div class="card cardcontadores">
					<div class="card-body">
						<h5 class="card-title">Ordens Finalizadas</h5>
						<div class="d-flex m-t-5 m-b-5 no-block align-items-center">
							<span class="display-5" style='color: #00C851'><i class="fa fa-file-signature"></i></span>
							<?= $this->Html->link($ordensFinalizadas, ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'link display-5 ml-auto']); ?>
						</div>
					</div>
				</div>
				<div class="card cardcontadores">
					<div class="card-body">
						<h5 class="card-title">Tickets Finalizados</h5>
						<div class="d-flex m-t-5 m-b-5 no-block align-items-center">
							<span class="display-5" style='color: #00C851'><i class="fas fa-ticket-alt"></i></span>
							<?= $this->Html->link($ticketsFinalizados, ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'link display-5 ml-auto']); ?>
						</div>
					</div>
				</div>
				<div class="card cardcontadores">
					<div class="card-body">
						<h5 class="card-title">Visitas Finalizadas</h5>
						<div class="d-flex m-t-5 m-b-5 no-block align-items-center">
							<span class="display-5" style='color: #00C851'><i class="fa fa-briefcase"></i></span>
							<?= $this->Html->link($visitasFinalizadas, ['controller' => 'Visitas', 'action' => 'index'], ['class' => 'link display-5 ml-auto']); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-2 p-r-0 p-l-0 card1">
				<div class="card cardcontadores">
					<div class="card-body">
						<h5 class="card-title">Ordens Pendentes</h5>
						<div class="d-flex m-t-5 m-b-5 no-block align-items-center">
							<span class="display-5" style='color: #ff4444'><i class="fa fa-file-signature"></i></span>
							<?= $this->Html->link($ordensPendentes, ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'link display-5 ml-auto']); ?>
						</div>
					</div>
				</div>
				<div class="card cardcontadores">
					<div class="card-body">
						<h5 class="card-title">Tickets Pendentes</h5>
						<div class="d-flex m-t-5 m-b-5 no-block align-items-center">
							<span class="display-5" style="color: #ff4444"><i class="fas fa-ticket-alt"></i></span>
							<?= $this->Html->link($ticketsPendentes, ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'link display-5 ml-auto']); ?>
						</div>
					</div>
				</div>
				<div class="card cardcontadores">
					<div class="card-body">
						<h5 class="card-title">Visitas Pendentes</h5>
						<div class="d-flex m-t-5 m-b-5 no-block align-items-center">
							<span class="display-5" style="color: #ff4444"><i class="fa fa-briefcase"></i></span>
							<?= $this->Html->link($visitasPendentes, ['controller' => 'Visitas', 'action' => 'index'], ['class' => 'link display-5 ml-auto']); ?>
						</div>
					</div>
				</div>
			</div> -->
	</div>
	</div>
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
	$("#tickets-pendentes, #tickets-sendo-resolvidos").perfectScrollbar();
	<?php if(isset($bAtivarDuasEtapas) && isset($veiologin)) { ?> 
		$('#modal-duasetapas').modal('toggle');
	<?php } ?>
</script>
