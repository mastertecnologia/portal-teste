<?php
	$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', ['controller' => 'Tickets', 'action' => 'edit', $ticket->id], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Cancelar', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($ticket, ['class' => 'form-material']); ?>
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group ">
							<label class="control-label text-muted"> Motivo do cancelamento </label>
							<?= $this->Form->textarea('observacao', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o motivo']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<?= $this->Form->button('Cancelar ticket', ['class' => 'btn btn-danger m-l-5']) ?>
						<?= $this->Html->link('Voltar para o Ticket', ["action" => $role == 1 ? 'view' : 'edit', $ticket->id], ['class' => 'btn btn-info']); ?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
