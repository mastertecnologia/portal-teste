<style> .popover-big { max-width: 500px; } </style>
<div class="col-lg-12">
	<div class="card card-nav-tabs">
		<div class="card-body">
			<?= $this->Form->create('Ticket', ['action' => 'indexcliente', 'type' => 'get', 'class' => 'form-material']); ?>
				<div class="row">
					<div class="col-xl-2 col-md-3 col-md-12">
						<label class="control-label text-muted"> Assunto: </label>
						<?= $this->Form->control('assunto', ['data-live-search' => true, 'title' => 'Todos', 'value' => $assunto, 'id' => 'assunto', 'class' => 'form-control selectpicker', 'options' => C_TicketCategoriaClienteQuery, 'label' => false]) ?>
					</div>
					<div class="col-xl-2 col-md-3 col-md-12">
						<label class="control-label text-muted"> Status: </label>
						<?= $this->Form->control('situacao', ['data-live-search' => true, 'title' => 'Selecione', 'value' => $situacao, 'id' => 'assunto', 'class' => 'form-control selectpicker', 'options' => [-1 => 'Todos'] + C_TicketSituacoes, 'label' => false]) ?>
					</div>
					<div class="col-lg-4 col-md-12 m-t-10">
						<?= $this->Html->link('Limpar', ["action" => "indexcliente"], ['rel' => 'tooltip', 'class' => 'btn btn-secondary m-t-20']); ?>
						<?= $this->Html->link('Abrir Ticket', ["action" => "add"], ['class' => 'btn btn-success m-t-20 m-l-10', 'escape' => false]) ?>
					</div>
				</div><br>
			<?= $this->Form->end(); ?>
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
						<?php foreach ($tickets as $reg): ?>							
							<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4><?= AssuntoTicket($reg->assunto) ?> </h4><br><?= $reg->solicitacao ?></div>' data-original-title="Ticket <?= $reg->id.' ' ?><small style='font-size: 12px;'><i>(<?= date_format($reg->created, 'd/m/Y') ?>)</i></small>" data-html="true" data-placement="top">
								<td><?= $reg->id ?></td>
								<td> <?= date_format($reg->created, 'd/m/Y') ?> </td>
								<td> <?= AssuntoTicket($reg->assunto) ?> </td>
								<td> <?= SituacaoTicket($reg->situacao) ?> </td>
								<td class="td-actions">
									<?= $this->Html->link('<i class="fas fa-eye"></i>', ["controller" => "Tickets", "action" => "view", $reg->id], ['rel' => 'tooltip', 'title' => 'Visualizar ticket', 'class' => 'btn btn-info btn-simple btn-xs', 'escape' => false]); ?>
									<?php if(in_array($reg->situacao, [C_TicketSituacaoEmandamento, C_TicketSituacaoPendente]) && ($permissaoacesso || $reg->idautor == $iduser)) 
										echo $this->Html->link('<i class="fa fa-times"></i>', ["action" => "cancelar", $reg->id], ['rel' => 'tooltip', 'title' => 'Cancelar', 'class' => 'btn btn-danger btn-simple btn-xs m-r-5', 'id' => $reg->id, 'escape' => false]); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script> 
	$('.nav-link').click(function(){
		$('.nav-link').each(function(){
			$( this ).removeClass('active');
		});
	});

	$('[rel=popover]').popover({offset: 0});

	$(document).ready(function() {
		$('#assunto, #situacao').on('change', function() {
			this.form.submit();
		});

		var $window = $(window);

		table = $('#table-todos');
		table.on( 'length.dt', function ( e, settings, len ) {
			pagelength(len);
		} )
		table.DataTable({
			"pageLength": <?= $pagelength ?>,
			"bLengthChange": true,
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
</script>
