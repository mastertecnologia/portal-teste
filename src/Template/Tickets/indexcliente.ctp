<style>
	.popover-big { max-width: 500px; }
	/* Visual "card" premium sem quebrar DataTables: mantém a tabela, mas
	   aplica espaçamento/borda suave entre linhas e melhora hierarquia. */
	#table-todos{
		border-collapse: separate;
		border-spacing: 0 12px;
	}
	#table-todos thead th{
		font-size: 11px;
		font-weight: 900;
		text-transform: uppercase;
		letter-spacing: .04em;
	}
	#table-todos tbody td{
		background:#fff;
		border-top: 1px solid rgba(15,23,42,.06);
		padding: 14px 12px;
		vertical-align: middle;
	}
	#table-todos tbody tr.ticket-row td{
		box-shadow: 0 10px 24px rgba(15,23,42,.05);
	}
	#table-todos tbody tr.ticket-row td:first-child{
		border-radius: 12px 0 0 12px;
	}
	#table-todos tbody tr.ticket-row td:last-child{
		border-radius: 0 12px 12px 0;
	}
	.ticket-id{
		font-weight: 900;
		color:#0f172a;
		font-size: 13px;
	}
	.ticket-assunto{
		font-weight: 900;
		color:#0f172a;
		line-height: 1.2;
		word-break: break-word;
	}
	.ticket-meta{
		color:#64748b;
		font-size: 12px;
		margin-top: 4px;
	}
	.ticket-actions a{ margin-right: 4px; }
</style>
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
							<?php
								$urlViewModal = $this->Url->build(['controller' => 'Tickets', 'action' => 'viewModal', $reg->id]);
							?>
							<tr class="ticket-row" rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4><?= AssuntoTicket($reg->assunto) ?> </h4><br><?= $reg->solicitacao ?></div>' data-original-title="Ticket <?= $reg->id.' ' ?><small style='font-size: 12px;'><i>(<?= date_format($reg->created, 'd/m/Y') ?>)</i></small>" data-html="true" data-placement="top">
								<td><span class="ticket-id"><?= (int)$reg->id ?></span></td>
								<td>
									<div><?= date_format($reg->created, 'd/m/Y') ?></div>
								</td>
								<td>
									<div class="ticket-assunto"><?= AssuntoTicket($reg->assunto) ?></div>
									<div class="ticket-meta"><?= h(mb_strimwidth((string)($reg->solicitacao ?? ''), 0, 60, '...')) ?></div>
								</td>
								<td><?= SituacaoTicket($reg->situacao) ?></td>
								<td class="td-actions ticket-actions">
									<?= $this->Html->link('<i class="fas fa-eye"></i>', $urlViewModal, [
										'rel' => 'tooltip',
										'title' => 'Visualizar ticket',
										'class' => 'btn btn-info btn-simple btn-xs',
										'escape' => false,
										'data-open-ticket-modal' => '1',
										'data-url-ticket-modal' => (string)$urlViewModal,
									]); ?>
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
<!-- Modal: visualizar ticket no portal do cliente -->
<div class="modal fade" id="modal-ticket-client" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<div>
					<h5 class="modal-title m-0" id="modal-ticket-client-title">Ticket</h5>
					<small class="text-muted">Visualização completa do ticket.</small>
				</div>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body p-0" style="height: 78vh;">
				<iframe id="modal-ticket-client-iframe" title="Ticket" src="about:blank" style="width:100%; height:100%; border:0;"></iframe>
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

		// Abre viewModal no modal (evita navegação completa e melhora UX)
		function openTicketModal(url, ticketId) {
			var iframe = document.getElementById('modal-ticket-client-iframe');
			var title = document.getElementById('modal-ticket-client-title');
			if (title) title.textContent = ticketId ? ('Ticket #' + ticketId) : 'Ticket';
			if (iframe) iframe.src = url || 'about:blank';
			$('#modal-ticket-client').modal('show');
		}

		// Clique na linha (sem interferir nas ações)
		$('#table-todos tbody').on('click', 'tr.ticket-row', function(ev){
			if ($(ev.target).closest('.td-actions').length) return;
			var url = $(this).find('a[data-open-ticket-modal="1"]').attr('data-url-ticket-modal');
			var ticketId = $(this).find('span.ticket-id').text().trim();
			if (url) openTicketModal(url, ticketId);
		});

		// Clique no botão "Visualizar"
		$('#table-todos').on('click', 'a[data-open-ticket-modal="1"]', function(ev){
			ev.preventDefault();
			ev.stopPropagation();
			var url = $(this).attr('data-url-ticket-modal') || $(this).attr('href');
			var ticketId = $(this).closest('tr.ticket-row').find('span.ticket-id').text().trim();
			openTicketModal(url, ticketId);
		});
	});
</script>
