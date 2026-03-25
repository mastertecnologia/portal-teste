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
						<?= $this->Html->link('Abrir Ticket', ["action" => "add"], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-20 m-l-10', 'escape' => false]) ?>
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
					</thead>
					<tbody>
						<?php foreach ($tickets as $reg): ?>							
							<?php
								$urlView = $this->Url->build(['controller' => 'Tickets', 'action' => 'view', $reg->id]);
								// Normaliza duplicação de prefixo quando o APPBase/servidor gera URLs como:
								// /portal/portal/tickets/view/:id  (isso quebra rotas/dispatch dependendo do ambiente).
								if (is_string($urlView) && $urlView !== '') {
									if (strpos($urlView, 'http') !== 0 && substr($urlView, 0, 1) !== '/') {
										$urlView = '/' . $urlView;
									}
									$urlView = preg_replace('#/portal/portal/#', '/portal/', $urlView);
									$urlView = preg_replace('#/portal/portal$#', '/portal', $urlView);
								}
							?>
							<tr
								class="ticket-row"
								data-url-view="<?= h((string)$urlView) ?>"
								style="cursor:pointer;"
								rel="popover"
								data-trigger="hover"
								data-content='<div class="popover-big"><h4><?= AssuntoTicket($reg->assunto) ?> </h4><br><?= $reg->solicitacao ?></div>'
								data-original-title="Ticket <?= $reg->id.' ' ?><small style='font-size: 12px;'><i>(<?= date_format($reg->created, 'd/m/Y') ?>)</i></small>"
								data-html="true"
								data-placement="top"
							>
								<td><span class="ticket-id"><?= (int)$reg->id ?></span></td>
								<td>
									<div><?= date_format($reg->created, 'd/m/Y') ?></div>
								</td>
								<td>
									<div class="ticket-assunto"><?= AssuntoTicket($reg->assunto) ?></div>
									<div class="ticket-meta"><?= h(mb_strimwidth((string)($reg->solicitacao ?? ''), 0, 60, '...')) ?></div>
								</td>
								<td><?= SituacaoTicket($reg->situacao) ?></td>
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

		// Debug opcional para identificar por que o clique não está abrindo o ticket.
		// Use na URL: ?debug=1 (ex.: /tickets/indexcliente?debug=1)
		var DEBUG_TICKETS = (window.location.search.indexOf('debug=1') !== -1);
		if (DEBUG_TICKETS) {
			console.log('[Tickets/indexcliente] debug ON', { url: window.location.href });
			window.addEventListener('error', function (event) {
				console.error('[Tickets/indexcliente] window error', event.message || event.error);
			});
			window.addEventListener('unhandledrejection', function (event) {
				console.error('[Tickets/indexcliente] unhandledrejection', event.reason);
			});
		}

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
		// Alguns templates definem `filters` globalmente; caso não exista, evita erro JS
		// que impediria o click abrir o ticket.
		var filters = typeof filters !== 'undefined' ? filters : '';
		table.search(filters).draw();

		// Clique no ticket abre a visualização completa.
		// Captura o clique em fase de captura para não depender de propagação/bloqueio
		// de handlers internos (ex.: botão do status renderizado por SituacaoTicket()).
		document.addEventListener('click', function(ev) {
			var row = ev.target && ev.target.closest ? ev.target.closest('#table-todos tbody tr.ticket-row') : null;
			if (!row) return;

			var url = row.getAttribute('data-url-view') || '';
			if (!url) return;

			// Evita ação padrão de links/botões internos.
			if (ev.preventDefault) ev.preventDefault();
			if (ev.stopPropagation) ev.stopPropagation();

			// Normalização defensiva: remove repetição de /portal/portal/
			while (url.indexOf('/portal/portal/') !== -1) {
				url = url.replace('/portal/portal/', '/portal/');
			}

			if (url && url[0] !== '/' && url.indexOf('http') !== 0) {
				url = '/' + url;
			}

			if (DEBUG_TICKETS) {
				console.log('[Tickets/indexcliente] ticket click redirect', { ticketUrl: url });
			}
			window.location.href = url;
		}, true);

		// (Fallback) Listener delegado no document para cenários sem `closest`.
		$(document).off('click.ticketRow').on('click.ticketRow', '#table-todos tbody tr.ticket-row', function(ev){
			var url = this.getAttribute('data-url-view') || '';
			if (!url) return;

			if (DEBUG_TICKETS) {
				console.log('[Tickets/indexcliente] ticket click (fallback) redirect', { ticketUrl: url });
			}
			window.location.href = url;
		});
	});
</script>
