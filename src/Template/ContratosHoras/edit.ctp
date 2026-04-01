<?php
$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index']);
$this->Breadcrumbs->add('Contratos de Horas', ['action' => 'index', $contrato->idcliente]);
$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);
?>
<?= $this->element('Pgm/form_shell_dark', ['formId' => 'form-contratos-horas-edit']) ?>
<div class="col-md-12 clictr-edit-page">
	<div class="clictr-card">
		<?= $this->Form->create($contrato, ['class' => 'form-material clictr-form', 'id' => 'form-contratos-horas-edit']) ?>
			<div class="clictr-section">
				<div class="clictr-section-title">Vigência e status</div>
				<div class="row">
					<div class="col-md-3 col-sm-6"><?= $this->Form->control('data_inicio', ['type' => 'text', 'class' => 'form-control datepicker', 'label' => 'Data início']) ?></div>
					<div class="col-md-3 col-sm-6"><?= $this->Form->control('data_fim', ['type' => 'text', 'class' => 'form-control datepicker', 'label' => 'Data fim']) ?></div>
					<div class="col-md-3 col-sm-6"><?= $this->Form->control('horas_contratadas', ['class' => 'form-control', 'label' => 'Horas contratadas', 'step' => '0.01']) ?></div>
					<div class="col-md-3 col-sm-6"><?= $this->Form->control('ativo', ['type' => 'checkbox', 'label' => 'Ativo']) ?></div>
				</div>
			</div>
			<div class="clictr-section">
				<div class="clictr-section-title">Valores por tipo de hora</div>
				<div class="row">
					<div class="col-md-4 col-sm-12"><?= $this->Form->control('valor_hora_comercial', ['class' => 'form-control', 'label' => 'Valor hora comercial']) ?></div>
					<div class="col-md-4 col-sm-12"><?= $this->Form->control('valor_hora_adicional_comercial', ['class' => 'form-control', 'label' => 'Valor hora adicional comercial']) ?></div>
					<div class="col-md-4 col-sm-12"><?= $this->Form->control('valor_hora_especial', ['class' => 'form-control', 'label' => 'Valor hora especial']) ?></div>
				</div>
			</div>
			<div class="clictr-section">
				<div class="clictr-section-title">Relatórios</div>
				<div class="row">
					<div class="col-12"><?= $this->Form->control('contatos_email_relatorio', ['class' => 'form-control', 'label' => 'E-mails adicionais para relatório (separados por ;)', 'type' => 'textarea']) ?></div>
				</div>
			</div>
			<div class="clictr-actions row">
				<div class="col-12">
					<?= $this->Form->button('Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
					<?= $this->Html->link('Cancelar', ['action' => 'index', $contrato->idcliente], ['class' => 'btn btn-secondary m-l-5']) ?>
				</div>
			</div>
		<?= $this->Form->end() ?>
	</div>
</div>
