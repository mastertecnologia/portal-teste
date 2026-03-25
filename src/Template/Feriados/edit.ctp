<?php
$this->Breadcrumbs->add('Feriados', ['action' => 'index']);
$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h4 class="card-title">Editar Feriado</h4>
			<?= $this->Form->create($feriado, ['class' => 'form-material']) ?>
			<?= $this->Form->control('data', ['type' => 'text', 'class' => 'form-control datepicker']) ?>
			<?= $this->Form->control('descricao', ['class' => 'form-control']) ?>
			<?= $this->Form->button('Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
			<?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
