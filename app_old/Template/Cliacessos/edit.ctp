<?php
	$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', ['controller' => 'Clientes', 'action' => 'edit', $cliacesso->idcliente], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Acesso', [], ['class' => 'breadcrumb-item active']);
	$disabled = $admin == 1 ? false : true;
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($cliacesso, ['class' => 'form-material']); ?>
				<div class="row">
					<div class="col-md-3 col-xs-12">
						<label class="control-label m-b-0">Nome do serviço</label>
						<?= $this->Form->control('nomeservico', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o nome do serviço', 'disabled' => $disabled]);?>
					</div>
					<div class="col-md-3 col-xs-12">
						<label class="control-label m-b-0">Usuário</label>
						<?= $this->Form->control('usuario', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o usuario', 'required' => true, 'disabled' => $disabled]);?>
					</div>
					<div class="col-md-3 col-xs-12">
						<label class="control-label m-b-0">IP</label>
						<?= $this->Form->control('ip', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o ip']);?>
					</div>
					<div class="col-3">
						<label class="control-label m-b-0">Protocolo</label>
						<?= $this->Form->control('protocolo', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o protocolo', 'list' => 'protocolos', 'disabled' => $disabled]);?>
						<datalist id="protocolos">
							<?php foreach(C_ProtocolosArray as $reg) echo '<option value="'.$reg.'">'; ?>
						</datalist>
					</div>
				</div>
				<div class="row m-t-5">
					<div class="col-md-3 col-xs-12">
						<label class="control-label m-b-0">Provedor</label>
						<?= $this->Form->text('nome', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o provedor', 'disabled' => $disabled]);?>
					</div>
					<div class="col-md-4 col-xs-12">
						<label class="control-label m-b-0">URL</label>
						<?= $this->Form->control('url', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe a url', 'disabled' => $disabled]);?>
					</div>
					<div class="col-md-3 col-xs-12">
						<label class="control-label m-b-0">Porta</label>
						<?= $this->Form->control('porta', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe a porta', 'disabled' => $disabled]);?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-lg-12">
						<?= $this->Form->button('Salvar acesso', ['class' => 'btn btn-success']) ?>
						<?= $this->Html->link('Alterar senha', ['action' => 'change_password', $cliacesso->id], ['class' => 'btn btn-warning m-l-5']) ?>
						<?= $this->Html->link('Voltar para o cliente', ["controller" => "clientes", "action" => "edit", $cliacesso->idcliente, 1], ['class' => 'btn btn-secondary m-l-5']); ?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>

