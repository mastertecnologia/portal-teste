<?php
	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Tickets', [], ['class' => 'breadcrumb-item active']);
?>
<?= $this->Html->css('dist/css/dashboard-erp.css') ?>
<style>
	.popover-big { max-width: 520px; }
	.td-actions{ width: 11%; }
	.tickets-erp-tabs{ display:flex; flex-wrap:wrap; gap:10px; margin: 6px 0 14px; }
	.tickets-erp-tab{ border-radius: 12px; font-weight: 900; padding: 10px 14px; text-decoration:none; display:inline-flex; align-items:center; gap:10px; }
	.tickets-erp-tab .badge{ margin-left: 6px; }
	.tickets-erp-tab.bg-queequaseinfo, .tickets-erp-tab.bg-dark, .tickets-erp-tab.bg-orange, .tickets-erp-tab.bg-info, .tickets-erp-tab.bg-success, .tickets-erp-tab.bg-danger { color:#fff; }
	.tickets-erp-tab:not(.active){ opacity: .88; }
	.tickets-erp-tab.active{ opacity: 1; box-shadow: 0 10px 24px rgba(15,23,42,.10); }
</style>

<div class="col-12 p-0">
	<div class="dash-erp">
		<div class="dash-erp-header">
			<div>
				<h2 class="dash-erp-title">Listagem de Tickets</h2>
				<p class="dash-erp-subtitle">Gerencie tickets por situação, com visual corporativo e sem alterar integrações.</p>
			</div>
			<div>
				<a class="btn btn-outline-secondary" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'dashboard']) ?>">Voltar ao dashboard</a>
			</div>
		</div>

		<div class="dash-erp-card">
			<div class="dash-erp-card-body">
				<div class="tickets-erp-tabs">
					<a class="tickets-erp-tab bg-queequaseinfo btn-abrir-ticket" data-toggle="tab" href="#abrir" role="tab" aria-selected="false">
						<i class="fas fa-plus-circle"></i> Abrir Ticket
					</a>
					<a class="tickets-erp-tab bg-dark" data-toggle="tab" href="#todos" role="tab" aria-selected="false">
						<i class="fas fa-layer-group"></i> Tickets Totais
					</a>
					<a class="tickets-erp-tab bg-orange active" data-toggle="tab" href="#pendentes" role="tab" aria-selected="false">
						<i class="fas fa-hourglass-half"></i> Aguardando Técnico
					</a>
					<a class="tickets-erp-tab bg-info" data-toggle="tab" href="#emandamento" role="tab" aria-selected="false">
						<i class="fas fa-play-circle"></i> Em execução
					</a>
					<a class="tickets-erp-tab bg-success" data-toggle="tab" href="#resolvidos" role="tab" aria-selected="false">
						<i class="fas fa-check-circle"></i> Resolvidos
					</a>
					<a class="tickets-erp-tab bg-danger" data-toggle="tab" href="#fechados" role="tab" aria-selected="false">
						<i class="fas fa-times-circle"></i> Cancelados
					</a>
				</div>

				<div class="tab-content">
				<div class="tab-pane" id="todos">
					<div class="table-responsive">
						<table class="table table-hover table-row-clickable" id="table-todos">
							<thead class="text-primary">
								<th> Número </th>
								<th> Autor </th>
								<th> Data </th>
								<th> Assunto </th>
								<th> Status </th>
								<th> Cliente </th>
								<th class="td-actions"> Ações </th>
							</thead>
							<tbody>
								<?php foreach ($tickets as $reg): ?>
									<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4><?= AssuntoTicket($reg->assunto) ?> </h4><br><?= $reg->solicitacao ?></div>' data-original-title="Ticket <?= $reg->id.' ' ?><small style='font-size: 12px;'><i>(<?= date_format($reg->created, 'd/m/Y') ?>)</i></small>" data-html="true" data-placement="top">
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= $reg->id ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= $reg->users['name']; ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= date_format($reg->created, 'd/m/Y') ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= AssuntoTicket($reg->assunto) ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= SituacaoTicket($reg->situacao) ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?php if($reg->cliente->tipo == 1) echo $reg->cliente->nome; else echo $reg->cliente->razaosocial; ?></a></td>
										<td class="td-actions">
											<?php 
												if($reg->situacao != C_TicketSituacaoResolvido && $reg->situacao != C_TicketSituacaoFechado) {
													if($reg->situacao != C_TicketSituacaoPendente) echo $this->Html->link('<i class="fas fa-reply"></i>', ["action" => "alterarsituacao", $reg->id, '0'], ['rel' => 'tooltip', 'title' => 'Aguardando técnico', 'class' => 'btn btn-warning btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
													if($reg->situacao != C_TicketSituacaoEmandamento) echo $this->Html->link('<i class="fas fa-reply"></i>', ["action" => "alterarsituacao", $reg->id, '1'], ['rel' => 'tooltip', 'title' => 'Em execução', 'class' => 'btn btn-info btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
													if($reg->situacao != C_TicketSituacaoResolvido) echo $this->Html->link('<i class="fas fa-check"></i>', ["action" => "alterarsituacao", $reg->id, '2'], ['rel' => 'tooltip', 'title' => 'Resolvido', 'class' => 'btn btn-success btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
													if($reg->situacao != C_TicketSituacaoFechado) echo $this->Html->link('<i class="fa fa-times"></i>', ["action" => "cancelar", $reg->id], ['rel' => 'tooltip', 'title' => 'Fechado', 'class' => 'btn btn-danger btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
												} 
												echo $this->Html->link('<i class="fa fa-print"></i>', ["action" => "imprimir", $reg->id, "?" => ["autoprint" => 1]], ['rel' => 'tooltip', 'title' => 'Imprimir', 'target' => '_blank', 'class' => 'btn btn-purple btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane active" id="pendentes">
					<div class="table-responsive">
						<table class="table table-hover table-row-clickable" id="table-pendentes">
							<thead class="text-primary">
								<th>Número</th>
								<th>Autor</th>
								<th>Data</th>
								<th>Assunto</th>
								<th>Cliente</th>
								<th class="td-actions">Ações</th>
							</thead>
							<tbody>
								<?php foreach ($ticketsPendentes as $reg): ?>
									<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4><?= AssuntoTicket($reg->assunto) ?> </h4><br><?= $reg->solicitacao ?></div>' data-original-title="Ticket <?= $reg->id.' ' ?><small style='font-size: 12px;'><i>(<?= date_format($reg->created, 'd/m/Y') ?>)</i></small>" data-html="true" data-placement="top">
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= $reg->id ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= $reg->users['name']; ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= date_format($reg->created, 'd/m/Y') ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= AssuntoTicket($reg->assunto) ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?php if($reg->cliente->tipo == 1){ echo $reg->cliente->nome;} else {echo $reg->cliente->razaosocial;} ?></a></td>
										<td class="td-actions">
											<?php
												if($reg->situacao != C_TicketSituacaoEmandamento) echo $this->Html->link('<i class="fas fa-reply"></i>', ["action" => "alterarsituacao", $reg->id, '1'], ['rel' => 'tooltip', 'title' => 'Em execução', 'class' => 'btn btn-info btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
												echo $this->Html->link('<i class="fas fa-check"></i>', ["action" => "alterarsituacao", $reg->id, '2'], ['rel' => 'tooltip', 'title' => 'Resolvido', 'class' => 'btn btn-success btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
												echo $this->Html->link('<i class="fa fa-times"></i>', ["action" => "cancelar", $reg->id], ['rel' => 'tooltip', 'title' => 'Cancelar', 'class' => 'btn btn-danger btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
												echo $this->Html->link('<i class="fa fa-print"></i>', ["action" => "imprimir", $reg->id, "?" => ["autoprint" => 1]], ['rel' => 'tooltip', 'title' => 'Imprimir', 'target' => '_blank', 'class' => 'btn btn-purple btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane" id="emandamento">
					<div class="table-responsive">
						<table class="table table-hover table-row-clickable" id="table-emandamento">
							<thead class="text-primary">
								<th>Número</th>
								<th>Autor</th>
								<th>Data</th>
								<th>Assunto</th>
								<th>Cliente</th>
								<th class="td-actions">Ações</th>
							</thead>
							<tbody>
								<?php foreach ($ticketsEmandamento as $reg): ?>
									<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4><?= AssuntoTicket($reg->assunto) ?> </h4><br><?= $reg->solicitacao ?></div>' data-original-title="Ticket <?= $reg->id.' ' ?><small style='font-size: 12px;'><i>(<?= date_format($reg->created, 'd/m/Y') ?>)</i></small>" data-html="true" data-placement="top">
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= $reg->id ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= $reg->users['name']; ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= date_format($reg->created, 'd/m/Y') ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= AssuntoTicket($reg->assunto) ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?php if($reg->cliente->tipo == 1){ echo $reg->cliente->nome;} else {echo $reg->cliente->razaosocial;} ?></a></td>
										<td class="td-actions">
											<?php
												if($reg->situacao != C_TicketSituacaoPendente) echo $this->Html->link('<i class="fas fa-reply"></i>', ["action" => "alterarsituacao", $reg->id, '0'], ['rel' => 'tooltip', 'title' => 'Aguardando técnico', 'class' => 'btn btn-warning btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
												echo $this->Html->link('<i class="fas fa-check"></i>', ["action" => "alterarsituacao", $reg->id, '2'], ['rel' => 'tooltip', 'title' => 'Resolvido', 'class' => 'btn btn-success btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
												echo $this->Html->link('<i class="fa fa-times"></i>', ["action" => "cancelar", $reg->id], ['rel' => 'tooltip', 'title' => 'Cancelar', 'class' => 'btn btn-danger btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
												echo $this->Html->link('<i class="fa fa-print"></i>', ["action" => "imprimir", $reg->id, "?" => ["autoprint" => 1]], ['rel' => 'tooltip', 'title' => 'Imprimir', 'target' => '_blank', 'class' => 'btn btn-purple btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					
				</div>
				<div class="tab-pane" id="resolvidos">
					<div class="table-responsive">
						<table class="table table-hover table-row-clickable" id="table-resolvidos">
							<thead class="text-primary">
								<th> Número </th>
								<th> Autor </th>
								<th> Data </th>
								<th> Assunto </th>
								<th> Cliente </th>
								<th class="td-actions"> Ações </th>
							</thead>
							<tbody>
								<?php foreach ($ticketsResolvidos as $reg): ?>
									<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4><?= AssuntoTicket($reg->assunto) ?> </h4><br><?= $reg->solicitacao ?></div>' data-original-title="Ticket <?= $reg->id.' ' ?><small style='font-size: 12px;'><i>(<?= date_format($reg->created, 'd/m/Y') ?>)</i></small>" data-html="true" data-placement="top">
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'> <?= $reg->id ?></a></td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'> <?= $reg->users['name']; ?></a></td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'> <?= date_format($reg->created, 'd/m/Y') ?></a></td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'> <?= AssuntoTicket($reg->assunto) ?></a></td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'> <?php if($reg->cliente->tipo == 1){ echo $reg->cliente->nome;} else {echo $reg->cliente->razaosocial;} ?></a></td>
										<td class="td-actions">
											<?= $this->Html->link('<i class="fa fa-print"></i>', ["action" => "imprimir", $reg->id, "?" => ["autoprint" => 1]], ['rel' => 'tooltip', 'title' => 'Imprimir', 'target' => '_blank', 'class' => 'btn btn-purple btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]); ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					
				</div>
				<div class="tab-pane" id="fechados">
					<div class="table-responsive">
						<table class="table table-hover table-row-clickable" id="table-fechados">
							<thead class="text-primary">
								<th> Número </th>
								<th> Autor </th>
								<th> Data </th>
								<th> Assunto </th>
								<th> Cliente </th>
								<th class="td-actions"> Ações </th>
							</thead>
							<tbody>
								<?php foreach ($ticketsFechados as $reg): ?>
									<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4><?= AssuntoTicket($reg->assunto) ?> </h4><br><?= $reg->solicitacao ?></div>' data-original-title="Ticket <?= $reg->id.' ' ?><small style='font-size: 12px;'><i>(<?= date_format($reg->created, 'd/m/Y') ?>)</i></small>" data-html="true" data-placement="top">
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= $reg->id ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= $reg->users['name']; ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= date_format($reg->created, 'd/m/Y') ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?= AssuntoTicket($reg->assunto) ?></a></td>
										<td><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Tickets", "action" => "edit", $reg->id]) ?>'><?php if($reg->cliente->tipo == 1){ echo $reg->cliente->nome;} else {echo $reg->cliente->razaosocial;} ?></a></td>
										<td class="td-actions">
											<?= $this->Html->link('<i class="fa fa-print"></i>', ["action" => "imprimir", $reg->id, "?" => ["autoprint" => 1]], ['rel' => 'tooltip', 'title' => 'Imprimir', 'target' => '_blank', 'class' => 'btn btn-purple btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]); ?>
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
</div>
</div>
<script>
	// Tabs
	$('.nav-link').click(function(){
		$('.nav-link').each(function(){
			$( this ).removeClass('active');
		});
	});

	$('.btn-abrir-ticket').click(function() {
		window.open('<?= Router::url(['controller'=>'Tickets','action'=>'add']);?>');
	})

	$('.fa-times').click(function(){
		if(confirm('Tem certeza de que deseja fechar esse ticket?')){return true}
		else{return false};
	});

	$('[rel=popover]').popover({offset: 0});

	$(document).ready(function() {
		table = $('#table-todos, #table-pendentes, #table-emandamento, #table-resolvidos, #table-fechados');
		table.on( 'length.dt', function ( e, settings, len ) {
			pagelength(len);
		} )
		table.DataTable({
			"pageLength": <?= $pagelength ?>,
			"bLengthChange": false,
			"order": [[ 0, "desc" ]],
			"language": {
				"sProcessing":    "Procesando...",
				"sLengthMenu":    "Mostrar _MENU_ registros",
				"sZeroRecords":   "Nenhum registro encontrado",
				"sEmptyTable":    "Nenhum dado disponível",
				"sInfo":          "Mostrando de _START_ até _END_ de um total de _TOTAL_ registros",
				"sInfoEmpty":     "Mostrando registros de 0 a 0 de um total de 0 registros",
				"sInfoFiltered":  "(filtrado de um total de _MAX_ registros)",
				"sInfoPostFix":   "",
				"sSearch":        "Buscar:",
				"sUrl":           "",
				"sInfoThousands":  ",",
				"sLoadingRecords": "Carregando...",
				"oPaginate": {
					"sFirst":    "<<",
					"sLast":    ">>",
					"sNext":    ">",
					"sPrevious": "<"
				},
				"oAria": {
					"sSortAscending":  ": Ordem Ascendente",
					"sSortDescending": ": Ordem Descendente"
				}
			},
			"drawCallback": function( settings ) {
				if ($('body').hasClass('dark-mode') ) $('td').each(function(){$(this).addClass('dark-mode');});
				else $('td').each(function(){$(this).removeClass('dark-mode');});
			},
		});
		table.search(filters).draw();
	});

	$('.btn-resolver').click(function(e){
		id = $(this).attr('id');
		e.preventDefault();
		$.ajax({
			type:"get",
			url: '<?= Router::url(['controller'=>'Tickets','action'=>'poderesolver']);?>/' + id,
			success: function(data){
				console.log(data)
				if(data == 'poderesolver'){
					var url = "<?= Router::url(['controller'=>'Ticketsusers','action'=>'resolver']);?>";
					window.location.href = url + '/' + id;;
				}else{
					bootbox.dialog({
						message: '<p class="titulomodal text-center"> Você já está resolvendo outro Ticket! </p> <p class="text-center">Para realizar essa ação é necessário resolver ou pausar o ticket nº ' + data + '.',
						buttons: {
							ver: {
								label: 'Ver',
								className: 'btn-success',
								callback: function(){
									var url = "<?= Router::url(['controller'=>'Tickets','action'=>'edit']);?>";
									window.open(url + '/' + data);
								}
							},
							pausar: {
								label: 'Pausar',
								className: 'btn-danger',
								callback: function(){
									var url = "<?= Router::url(['controller'=>'Tickets','action'=>'pausar']);?>";
									window.open(url + '/' + data);
									location.reload();
								}
							},
						},
					});
				}
			},
		});
	})
</script>