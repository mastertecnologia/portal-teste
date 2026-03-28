<style>
	.popover-big { max-width: 500px; }
	/* ── Tickets cliente — Premium dark layout ─────────────────── */
	.tkcli-wrap{background:#161b22;border:1px solid #21262d;border-radius:12px;padding:24px;}
	.tkcli-head{display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;}
	.tkcli-head-left .tkcli-eyebrow{font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:#1d9e75;font-weight:700;margin-bottom:3px;}
	.tkcli-head-left h1{font-size:1.2rem;font-weight:800;color:#e6edf3;margin:0;}
	.tkcli-head-left p{font-size:.78rem;color:#6e7681;margin:3px 0 0;}
	/* Filters bar */
	.tkcli-filters{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-bottom:18px;padding-bottom:16px;border-bottom:1px solid #21262d;}
	.tkcli-filter-group label{font-size:.68rem;font-weight:600;color:#6e7681;text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:4px;}
	.tkcli-filter-group .form-control,.tkcli-filter-group .selectpicker{background:#0d1117;border:1px solid #30363d;color:#c9d1d9;border-radius:7px;font-size:.8rem;}
	.tkcli-filter-group .form-control:focus{border-color:#1d9e75;box-shadow:none;}
	.tkcli-btn-abrir{background:linear-gradient(135deg,#1d9e75 0%,#13715a 100%);color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:.8rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:opacity .15s;}
	.tkcli-btn-abrir:hover{opacity:.85;color:#fff;text-decoration:none;}
	.tkcli-btn-limpar{background:transparent;border:1px solid #30363d;color:#8b949e;border-radius:8px;padding:8px 14px;font-size:.8rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;transition:border-color .15s,color .15s;}
	.tkcli-btn-limpar:hover{border-color:#6e7681;color:#c9d1d9;text-decoration:none;}
	/* Table */
	#table-todos{border-collapse:separate;border-spacing:0 6px;width:100%;}
	#table-todos thead th{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6e7681;padding:6px 10px;border-bottom:1px solid #21262d;}
	#table-todos tbody td{background:#0d1117;border-top:1px solid #21262d;padding:12px 10px;vertical-align:middle;color:#c9d1d9;}
	#table-todos tbody tr.ticket-row td{transition:background .12s;}
	#table-todos tbody tr.ticket-row:hover td{background:#161b22;}
	#table-todos tbody tr.ticket-row td:first-child{border-radius:8px 0 0 8px;}
	#table-todos tbody tr.ticket-row td:last-child{border-radius:0 8px 8px 0;}
	.ticket-id{font-weight:900;color:#5cdbc0;font-size:13px;font-family:'DM Mono',monospace;}
	.ticket-assunto{font-weight:700;color:#e6edf3;line-height:1.3;word-break:break-word;font-size:.85rem;}
	.ticket-meta{color:#6e7681;font-size:.72rem;margin-top:3px;}
	.ticket-actions a{margin-right:4px;}
	/* DataTables overrides for dark theme */
	.dataTables_wrapper .dataTables_length select,
	.dataTables_wrapper .dataTables_filter input{background:#0d1117;border:1px solid #30363d;color:#c9d1d9;border-radius:6px;}
	.dataTables_wrapper .dataTables_info,.dataTables_wrapper .dataTables_length,.dataTables_wrapper .dataTables_filter{color:#6e7681;font-size:.75rem;}
	.dataTables_wrapper .dataTables_paginate .paginate_button{color:#8b949e!important;border-radius:6px!important;}
	.dataTables_wrapper .dataTables_paginate .paginate_button.current{background:#1d9e75!important;color:#fff!important;border:none!important;}
	.dataTables_wrapper .dataTables_paginate .paginate_button:hover{background:#21262d!important;color:#e6edf3!important;}
</style>
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
