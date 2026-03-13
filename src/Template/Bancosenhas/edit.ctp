<?php
	$this->Breadcrumbs->add('Banco de Senhas', ['controller' => 'Bancosenhas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($senha, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-md-3 col-xs-12">
						<div class="form-group ">
							<label class="control-label">Nome do serviço</label>
							<?= $this->Form->control('nomeservico', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o nome do serviço']);?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group ">
							<label class="control-label">Usuário</label>
							<?= $this->Form->control('usuario', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o usuario', 'required' => true,]);?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group ">
							<label class="control-label">IP</label>
							<?= $this->Form->control('ip', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o ip']);?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group ">
							<label class="control-label">Protocolo</label>
							<?= $this->Form->control('protocolo', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o protocolo', 'list' => 'protocolos']);?>
							<datalist id="protocolos">
								<?php foreach(C_ProtocolosArray as $reg) echo '<option value="'.$reg.'">'; ?>
							</datalist>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3 col-xs-12">
						<div class="form-group ">
							<label class="control-label">Provedor</label>
							<?= $this->Form->control('provedor', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o provedor']);?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group ">
							<label class="control-label">URL</label>
							<?= $this->Form->control('url', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe a URL']);?>
						</div>
					</div>
					<div class="col-md-3 col-xs-12">
						<div class="form-group ">
							<label class="control-label">Porta</label>
							<?= $this->Form->control('porta', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe a porta']);?>
						</div>
					</div>
				</div>
				<?= $this->Form->button('Salvar', ['class' => 'btn btn-success m-t-20']) ?>
				<?= $this->Html->link('Alterar senha', ['action' => 'change_password', $senha->id], ['class' => 'btn btn-warning m-t-20']) ?>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
