<?php
	use Cake\Routing\Router;
	// Breadcumbs
	$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', ['controller' => 'Tickets', 'action' => 'edit', $ticket->id], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Alterar Status', [], ['class' => 'breadcrumb-item active']);

	if($ticket->situacao == C_TicketSituacaoResolvido || $ticket->situacao == C_TicketSituacaoRespondido) $sitopcoes = C_TicketSituacoesResolvidoRespondido;
	else if($ticket->situacao == C_TicketSituacaoFechado) $sitopcoes = C_TicketSituacoesFechado;
	else if($ticket->Users['role'] == 0) $sitopcoes = C_TicketSituacoesFuncionario;
	else $sitopcoes = C_TicketSituacoesCliente;
?>

<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($ticket, ['class' => 'form-material']); ?>
			<div class="tab-content">
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group">
							<label class="control-label text-muted">Situação</label>
							<?= $this->Form->control('situacao', ['class' => 'form-control', 'options' => $sitopcoes, 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group ">
							<label class="control-label text-muted">Motivo da alteração</label>
							<?= $this->Form->textarea('motivo', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o motivo da alteração']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<?= $this->Form->button('Alterar situação do Ticket', ['class' => 'btn btn-success btn-options m-l-5']) ?>						
						<?= $this->Html->link('Voltar para o ticket', ["action" => "edit", $ticket->id], ['class' => 'btn btn-info btn-options']); ?>
					</div>
				</div>
			</div>

			<div class="clearfix"></div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<script>
$('#situacao').change(function(){
		if( $(this).val() == 3 ){
			var url = "<?= Router::url(array('controller'=>'Tickets','action'=>'poderesolver', $ticket->id));?>";
			url = url;
			$.ajax({
				dataType: "json",
				type:"get",
				url: url,
				success: function(data){
					if(data != 'poderesolver'){
						$('.btn-options').each(function(){
							$(this).attr('disabled', true)
						});
						bootbox.dialog({
							message: '<p class="titulomodal text-center"> Você já está resolvendo outro Ticket!</p> <p class="text-center">Para realizar essa ação é necessário finalizar ou pausar o chamado nº ' + data + '.',
							buttons: {
								ver: {
									label: 'Ver',
									className: 'btn-success',
									callback: function(){
										var url = "<?= Router::url(array('controller'=>'Tickets','action'=>'edit'));?>";
										window.open(url + '/' + data);
									}
								},
								pausar: {
									label: 'Pausar',
									className: 'btn-danger',
									callback: function(){
										var url = "<?= Router::url(array('controller'=>'Tickets','action'=>'pausar'));?>";
										window.open(url + '/' + data);
										$('.btn-options').each(function(){
											$(this).attr('disabled', false)
										});
									}
								},
							},
						});
					}else{
						$('.btn-options').each(function(){
							$(this).attr('disabled', true)
						});
					}
				},
				error: function (data) {
				
				}
			});
		}else{
			$('.btn-options').each(function(){
				$(this).attr('disabled', false)
			});
		}
	});

</script>

