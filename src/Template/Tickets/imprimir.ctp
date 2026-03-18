<style>
	.descricao  img{ 
		max-width: 100%;
		height: 50%;
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
<div class="col-12 col-btns">
	<?= $this->Html->link('Imprimir', [], ['id' => 'btn-imprimir', 'class' => 'btn btn-purple text-white btn-sm']) ?>
</div>
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
		</div>
	</div>
</div>
<script>
	$('#btn-imprimir').click(function(e) {
		e.preventDefault();
		printBy();
	});

	setTimeout(() => {
		printBy();
	}, "1500");

	function printBy(seletor){
		var $print = $('.col-print').clone().addClass('print');
		window.print();
		$print.remove();
	}
</script>