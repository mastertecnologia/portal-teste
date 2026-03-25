<?php
$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index']);
$this->Breadcrumbs->add('Contratos de Horas', ['action' => 'index', $idcliente]);
$this->Breadcrumbs->add('Novo', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h4 class="card-title">Novo Contrato de Horas Técnicas</h4>
			<?= $this->Form->create($contrato, ['class' => 'form-material']) ?>
			<?= $this->Form->hidden('idcliente', ['value' => $idcliente]) ?>
			<div class="row">
				<div class="col-md-3"><?= $this->Form->control('data_inicio', ['type' => 'text', 'class' => 'form-control datepicker', 'label' => 'Data início']) ?></div>
				<div class="col-md-3"><?= $this->Form->control('data_fim', ['type' => 'text', 'class' => 'form-control datepicker', 'label' => 'Data fim']) ?></div>
				<div class="col-md-3"><?= $this->Form->control('horas_contratadas', ['class' => 'form-control', 'label' => 'Horas contratadas', 'step' => '0.01']) ?></div>
				<div class="col-md-3"><?= $this->Form->control('ativo', ['type' => 'checkbox', 'checked' => true]) ?></div>
			</div>
			<div class="row">
				<div class="col-md-4"><?= $this->Form->control('valor_hora_comercial', ['class' => 'form-control', 'label' => 'Valor hora comercial']) ?></div>
				<div class="col-md-4"><?= $this->Form->control('valor_hora_adicional_comercial', ['class' => 'form-control', 'label' => 'Valor hora adicional comercial']) ?></div>
				<div class="col-md-4"><?= $this->Form->control('valor_hora_especial', ['class' => 'form-control', 'label' => 'Valor hora especial']) ?></div>
			</div>
			<div class="row">
				<div class="col-md-12"><?= $this->Form->control('contatos_email_relatorio', ['class' => 'form-control', 'label' => 'E-mails adicionais para relatório (separados por ;)', 'type' => 'textarea']) ?></div>
			</div>
			<?= $this->Form->button('Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
			<?= $this->Html->link('Cancelar', ['action' => 'index', $idcliente], ['class' => 'btn btn-secondary']) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
