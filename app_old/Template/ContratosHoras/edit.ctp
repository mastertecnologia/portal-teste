<?php
$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index']);
$this->Breadcrumbs->add('Contratos de Horas', ['action' => 'index', $contrato->idcliente]);
$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h4 class="card-title">Editar Contrato de Horas Técnicas</h4>
			<?= $this->Form->create($contrato, ['class' => 'form-material']) ?>
			<div class="row">
				<div class="col-md-3"><?= $this->Form->control('data_inicio', ['type' => 'text', 'class' => 'form-control datepicker']) ?></div>
				<div class="col-md-3"><?= $this->Form->control('data_fim', ['type' => 'text', 'class' => 'form-control datepicker']) ?></div>
				<div class="col-md-3"><?= $this->Form->control('horas_contratadas', ['class' => 'form-control', 'step' => '0.01']) ?></div>
				<div class="col-md-3"><?= $this->Form->control('ativo', ['type' => 'checkbox']) ?></div>
			</div>
			<div class="row">
				<div class="col-md-4"><?= $this->Form->control('valor_hora_comercial', ['class' => 'form-control']) ?></div>
				<div class="col-md-4"><?= $this->Form->control('valor_hora_adicional_comercial', ['class' => 'form-control']) ?></div>
				<div class="col-md-4"><?= $this->Form->control('valor_hora_especial', ['class' => 'form-control']) ?></div>
			</div>
			<div class="row">
				<div class="col-md-12"><?= $this->Form->control('contatos_email_relatorio', ['class' => 'form-control', 'type' => 'textarea']) ?></div>
			</div>
			<?= $this->Form->button('Salvar', ['class' => 'btn btn-success']) ?>
			<?= $this->Html->link('Cancelar', ['action' => 'index', $contrato->idcliente], ['class' => 'btn btn-secondary']) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
