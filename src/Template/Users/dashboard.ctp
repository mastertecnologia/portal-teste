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
<?php }else{ ?>
	<!-- Totalizadores -->
	<div class="col-12">
		<div class="row">
			<!-- Chamados Desenvolvidos -->
			<div class="col-md-4">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title">Ordens de Serviço</h5>
						<div class="d-flex m-t-30 m-b-20 no-block align-items-center">
							<span class="display-5 text-purple"><i class="ti-pencil-alt"></i></span>
							<span class="display-5 ml-auto"><?= $ordensCliente ?></span>
						</div>
					</div>
				</div>
			</div>
			<!-- Tickets Resolvidos -->
			<div class="col-md-4">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title">Orçamentos</h5>
						<div class="d-flex m-t-30 m-b-20 no-block align-items-center">
							<span class="display-5 text-orange"><i class="ti-receipt"></i></span>
							<span class="display-5 ml-auto"><?= $orcamentosCliente ?></span>
						</div>
					</div>
				</div>
			</div>
			<!-- Atendimentos Realizados -->
			<div class="col-md-4">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title">Tickets</h5>
						<div class="d-flex m-t-30 m-b-20 no-block align-items-center">
							<span class="display-5 text-info"><i class="fa fa-user-alt"></i></span>
							<span class="display-5 ml-auto"><?= $ticketsCliente ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-12">
			<div class="card-group">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title"> Tickets Aguardando Técnico </h5>
						<div class="table-responsive">
							<table class="table table-hover table-row-clickable" id="table-todos">
								<thead class="text-primary">
									<th> Número </th>
									<th> Data </th>
									<th> Assunto </th>
									<th> Status </th>
									<th> Ações </th>
								</thead>
								<tbody>
									<?php foreach ($ticketsPendentes as $reg): ?>							
										<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4><?= AssuntoTicket($reg->assunto) ?> </h4><br><?= $reg->solicitacao ?></div>' data-original-title="Ticket <?= $reg->id.' ' ?><small style='font-size: 12px;'><i>(<?= date_format($reg->created, 'd/m/Y') ?>)</i></small>" data-html="true" data-placement="top">
											<td><?= $reg->id ?></td>
											<td><?= date_format($reg->created, 'd/m/Y') ?></td>
											<td><?= AssuntoTicket($reg->assunto) ?></td>
											<td><?= SituacaoTicket($reg->situacao) ?></td>
											<td class="td-actions">
												<?= $this->Html->link('<i class="fas fa-eye"></i><div class="ripple-container"></div>', ["controller" => "Tickets", "action" => "view", $reg->id], ['rel' => 'tooltip', 'title' => 'Visualizar ticket', 'class' => 'btn btn-info btn-simple btn-xs', 'escape' => false]); ?>
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
	<?php if($permissaoacesso) { ?>
		<!-- Grades -->
		<div class="col-12">
			<div class="card-group">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title">Contratos</h5>
						<div class="table-responsive">	
							<table class="table table-hover table-row-clickable" id="tableOrdens">
								<thead class="text-primary">
									<th>Número</th>
									<th>Descrição</th>
									<th>Quantidade</th>
									<th>Data</th>
								</thead>
								<tbody>
									<?php foreach ($contratos as $reg): ?>
										<tr>
											<td><?= $reg->id ?></td>
											<td><?= $reg->descricao ?></td>
											<td><?= $reg->qtde ?></a></td>
											<td data-order="<?= @date_format($reg->dtcontratacao, 'Ymd') ?>"><?= @date_format($reg->dtcontratacao, 'd/m/Y') ?></a></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-12">
			<div class="card-group">
				<div class="card card2">
					<div class="card-body">
						<h5 class="card-title">Últimos Orçamentos</h5>
						<div class="table-responsive">	
							<table class="table table-hover table-row-clickable" id="tableOrdens">
								<thead class="text-primary">
									<th>Número</th>
									<th>Autor</th>
									<th>Abertura</th>
									<th>Validade</th>
								</thead>
								<tbody>
									<?php foreach ($orcamentosRecentes as $reg): ?>
										<tr>
											<td><a class="link" href='<?= $this->Url->build(["controller" => "Orcamentos", "action" => "view", $reg->id]) ?>'><?= $reg->id ?></a></td>
											<td><a class="link" href='<?= $this->Url->build(["controller" => "Orcamentos", "action" => "view", $reg->id]) ?>'><?= $autores[$reg->idautor] ?></a></td>
											<td data-order="<?= date_format($reg->created, 'Ymd') ?>">  <a class="link" href='<?= $this->Url->build(["controller" => "Orcamentos", "action" => "view", $reg->id]) ?>'><?= date_format($reg->created, 'd/m/Y') ?></a></td>
											<td data-order="<?= date_format($reg->validoate, 'Ymd') ?>"><a class="link" href='<?= $this->Url->build(["controller" => "Orcamentos", "action" => "view", $reg->id]) ?>'><?= date_format($reg->validoate, 'd/m/Y') ?></a></td>
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
<?php } ?>
<!-- Modal Duas Etapas -->
<div class="modal fade none-border" id="modal-duasetapas">
	<div class="modal-dialog modal-md modal-dialog-centered">
		<div class="modal-content">
			<div class="row p-20">    
				<legend class='text-center' style='font-size: 1.1rem !important;'> Ative a verificação em duas etapas <?= $this->Html->link('clicando aqui', ['action' => 'loginduasetapas'], ['class' => 'link text-success text-bold']);?>! </legend>
				<legend class='text-center' style='font-size: 1.1rem !important;'>
					Baixe o Goole Authenticator para <a target="_blank" class='link text-success text-bold' href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2"> Android</a> ou para
					<a target="_blank" class='link text-success text-bold' href="https://apps.apple.com/br/app/google-authenticator/id388497605"> IOS </a>
				</p>
				<!-- <legend class='text-center'> 
					Baixe o Duo Authenticator para Android <a target="_blank" class='link text-success text-bold' href="https://play.google.com/store/apps/details?id=com.duosecurity.duomobile&hl=pt"> Android </a> ou para
					<a target="_blank" class='link text-success text-bold' href="https://apps.apple.com/br/app/duo-mobile/id422663827"> IOS </a>
				</legend> -->
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
