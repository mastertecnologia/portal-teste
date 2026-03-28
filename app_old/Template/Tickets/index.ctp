<?php
	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Tickets', [], ['class' => 'breadcrumb-item active']);
?>
<style>
	.popover-big { max-width: 500px; }
	.td-actions{ width: 11%; }
	.nav-link{
		padding: 3%;
		width: 110%;
        transition: 0.5s;
    }
    .nav-link:hover{
		padding: 0%;
        cursor: pointer;
    }
    .nav-link.active{  padding: 0%; }
</style>
<div class="col-12">
	<div class="card card-nav-tabs">
		<div class="card-body">
			<!-- tabs -->
			<div class="row" >
				<div class='m-r-30'>
					<a class="nav-link btn-abrir-ticket" data-toggle="tab" href="#abrir" role="tab" aria-selected="false">
						<div class="card">
							<div class="box bg-queequaseinfo text-center">
								<p class="text-white h5"> Abrir Ticket </p>
							</div>
						</div>
					</a>
				</div>
				<div class='m-r-30'>
					<a class="nav-link" data-toggle="tab" href="#todos" role="tab" aria-selected="false">
						<div class="card">
							<div class="box bg-dark text-center">
								<p class="text-white h5"> Tickets Totais </p>
							</div>
						</div>
					</a>
				</div>
				<div class='m-r-30'>
					<a class="nav-link active" data-toggle="tab" href="#pendentes" role="tab" aria-selected="false">
						<div class="card">
							<div class="box bg-orange text-center">
								<p class="text-white h5"> Aguardando Técnico </p>
							</div>
						</div>
					</a>
				</div>
				<div class='m-r-30'>
					<a class="nav-link" data-toggle="tab" href="#emandamento" role="tab" aria-selected="false">
						<div class="card">
							<div class="box bg-info text-center">
								<p class="text-white h5"> Em execução </p>
							</div>
						</div>
					</a>
				</div>
				<div class='m-r-30'>
					<a class="nav-link" data-toggle="tab" href="#resolvidos" role="tab" aria-selected="false">
						<div class="card">
							<div class="box bg-success text-center">
								<p class="text-white h5"> Resolvidos </p>
							</div>
						</div>
					</a>
				</div>
				<div class='m-r-30'>
					<a class="nav-link" data-toggle="tab" href="#fechados" role="tab" aria-selected="false">
						<div class="card">
							<div class="box bg-danger text-center">
								<p class="text-white h5"> Cancelados </p>
							</div>
						</div>
					</a>
				</div>
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
												echo $this->Html->link('<i class="fa fa-print"></i>', ["action" => "imprimir", $reg->id], ['rel' => 'tooltip', 'title' => 'Imprimir', 'target' => '_blank', 'class' => 'btn btn-purple btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
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
												echo $this->Html->link('<i class="fa fa-print"></i>', ["action" => "imprimir", $reg->id], ['rel' => 'tooltip', 'title' => 'Imprimir', 'target' => '_blank', 'class' => 'btn btn-purple btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
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
												echo $this->Html->link('<i class="fa fa-print"></i>', ["action" => "imprimir", $reg->id], ['rel' => 'tooltip', 'title' => 'Imprimir', 'target' => '_blank', 'class' => 'btn btn-purple btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]);
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
											<?= $this->Html->link('<i class="fa fa-print"></i>', ["action" => "imprimir", $reg->id], ['rel' => 'tooltip', 'title' => 'Imprimir', 'target' => '_blank', 'class' => 'btn btn-purple btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]); ?>
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
											<?= $this->Html->link('<i class="fa fa-print"></i>', ["action" => "imprimir", $reg->id], ['rel' => 'tooltip', 'title' => 'Imprimir', 'target' => '_blank', 'class' => 'btn btn-purple btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]); ?>
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
				$('td').removeClass('dark-mode');
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