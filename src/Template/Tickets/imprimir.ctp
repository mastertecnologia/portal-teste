<style>
	.descricao  img{ 
		max-width: 100%;
		height: 50%;
	}
	@media print {
		.col-btns { display: none !important; }
		.col-print { margin-top: 0 !important; }
	}

	/* Em autoprint, não mostrar "tela atrás" (screen). */
	body.ticket-autoprint-screen {
		background: #fff !important;
	}
	body.ticket-autoprint-screen .col-print,
	body.ticket-autoprint-screen .card,
	body.ticket-autoprint-screen .card-body {
		display: none !important;
	}
	@media print {
		body.ticket-autoprint-screen .col-print,
		body.ticket-autoprint-screen .card,
		body.ticket-autoprint-screen .card-body {
			display: block !important;
		}
	}
	
	.col-btns { 
		background-color: white;
		position: fixed;
		top: 0;
		padding: 15px;
		width: 100%;
		z-index: 999;
	}
	.col-print { 
		margin-top: 60px;
	}
</style>
<?php $autoPrint = (bool)$this->request->getQuery('autoprint'); ?>
<?php if (!$autoPrint) { ?>
	<div class="col-12 col-btns">
		<?= $this->Html->link('Imprimir', [], ['id' => 'btn-imprimir', 'class' => 'btn btn-pgm btn-pgm-imprimir btn-orange text-white btn-sm']) ?>
	</div>
<?php } ?>
<div class="col-12 col-print">
	<div class="card">
		<div class="card-body">
			<div class="row">
				<div class="col-12">
					<p class='h3 font-weight-bolder'> Ticket nº <?= $ticket->id ?> </p>
				</div>
				<div class="col-4">
					<p class="h5 font-weight-bolder"> Autor: </p>
					<p class='h4'> <?= $ticket['users']['name'] ?> </p>
				</div>
				<div class="col-4">
					<p class="h5 font-weight-bolder"> Cliente: </p>
					<p class='h4'> <?= $cliente ?> </p>
				</div>
				<?php if(!empty($ticket->solicitante)) { ?>
					<div class="col-4">
						<p class="h5 font-weight-bolder"> Solicitante: </p>
						<p class='h4'> <?= $solicitante ?> </p>
					</div>
				<?php } ?>
				<?php if(!empty($ticket->email)) { ?>
					<div class="col-4">
						<p class="h5 font-weight-bolder"> E-mail: </p>
						<p class='h4'> <?= $ticket->email ?> </p>
					</div>
				<?php } ?>
				<div class="col-4">
					<p class="h5 font-weight-bolder"> Assunto: </p>
					<p class='h4'> <?= AssuntoTicket($ticket->assunto) ?> </p>
				</div>
				<div class="col-4">
					<p class="h5 font-weight-bolder"> Status: </p>
					<p class='h4'> <?= SituacaoTicket($ticket->situacao) ?> </p>
				</div>
				<div class="col-4">
					<p class="h5 font-weight-bolder"> Data de abertura: </p>
					<p class='h4'> <?= date_format($ticket->created, 'd/m/Y') ?> </p>
				</div>
			</div>
			<div class="descricao">
				<p class="h5 font-weight-bolder"> Descrição </p>
				<?= $ticket->solicitacao ?>
				<?php if($ticket->assunto == C_TicketCategoriaVisita) { ?>
					<p  class='h6'> Data solicitada para a visita: <b> <?= date_format($ticket->data, 'd/m/Y') ?> </b> </p>
				<?php } ?>
			</div>
			<?php if (!empty($ticket->descricao_atendimento)) { ?>
			<div class="descricao mt-3">
				<p class="h5 font-weight-bolder"> Atendimento técnico (o que foi feito) </p>
				<?= nl2br(h($ticket->descricao_atendimento)) ?>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
<script>
	(function(){
		var autoPrint = <?= $autoPrint ? 'true' : 'false' ?>;
		function doPrint(){
			window.print();
		}
		var btn = document.getElementById('btn-imprimir');
		if (btn) {
			btn.addEventListener('click', function(e){
				e.preventDefault();
				doPrint();
			});
		}
		if (autoPrint) {
			// No modo screen, esconder conteúdo (evita "tela por trás").
			try { document.body.classList.add('ticket-autoprint-screen'); } catch (e) {}
			// Espera o layout/pintura do browser antes de abrir o diálogo
			setTimeout(doPrint, 50);
			window.onafterprint = function(){
				try { window.close(); } catch (e) {}
			};
		}
	})();
</script>