<?php /* Estilos tkcli em public/dist/css/pages/pgm-client-premium.css (portal cliente) */ ?>
<div class="col-lg-12">
	<div class="tkcli-wrap">
		<!-- Page head -->
		<div class="tkcli-head">
			<div class="tkcli-head-left">
				<div class="tkcli-eyebrow">Portal do Cliente</div>
				<h1>Meus Tickets</h1>
				<p>Acompanhe e abra chamados de suporte</p>
			</div>
			<?= $this->Html->link('<i class="fa fa-plus"></i> Abrir Chamado', ['action' => 'add'], ['class' => 'tkcli-btn-abrir', 'escape' => false]) ?>
		</div>
		<div class="card-body" style="padding:0;background:transparent;">
			<?= $this->Form->create('Ticket', ['action' => 'indexcliente', 'type' => 'get', 'class' => 'tkcli-filters']); ?>
				<div class="tkcli-filter-group">
					<label>Assunto</label>
					<?= $this->Form->control('assunto', ['data-live-search' => true, 'title' => 'Todos', 'value' => $assunto, 'id' => 'assunto', 'class' => 'form-control selectpicker', 'options' => C_TicketCategoriaClienteQuery, 'label' => false]) ?>
				</div>
				<div class="tkcli-filter-group">
					<label>Status</label>
					<?= $this->Form->control('situacao', ['data-live-search' => true, 'title' => 'Selecione', 'value' => $situacao, 'id' => 'situacao', 'class' => 'form-control selectpicker', 'options' => [-1 => 'Todos'] + C_TicketSituacoes, 'label' => false]) ?>
				</div>
				<div class="tkcli-filter-group" style="padding-top:18px;">
					<?= $this->Html->link('Limpar filtros', ['action' => 'indexcliente'], ['class' => 'tkcli-btn-limpar']) ?>
				</div>
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
				$('td').removeClass('dark-mode');
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
