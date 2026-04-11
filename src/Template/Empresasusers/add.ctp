<?php
$this->Breadcrumbs->add('Relações', ['controller' => 'empresasusers', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Adicionar relação', [], ['class' => 'breadcrumb-item active']);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($empresasuser, ['class' => 'form-material  m-t-10']) ?>
				<div class="row">
					<div class="col-md-4 col-xs-12">
						<label class="control-label text-muted"> Empresa</label>
						<?= $this->Form->control('idempresa', ['class' => 'form-control selectpicker', 'label' => false, 'required' => true, 'options' => $empresas]) ?>
					</div>
					<div class="col-md-4 col-xs-12">
						<label class="control-label text-muted"> Usuário</label>
						<?= $this->Form->control('iduser', ['data-live-search' => true, 'class' => 'form-control selectpicker', 'label' => false, 'required' => true, 'options' => $users]) ?>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12 col-md-12">
						<?= $this->Form->button('Criar relação', ['class' => 'btn btn-pgm btn-pgm-salvar waves-effect waves-light btn-success']) ?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div> 
	</div>
</div>
