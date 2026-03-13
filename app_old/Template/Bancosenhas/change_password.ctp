<?php $this->Breadcrumbs->add('Banco de Senhas', ['controller' => 'Bancosenhas', 'action' => 'index'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Editar', ['controller' => 'Bancosenhas', 'action' => 'edit', $senha->id], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Alterar Senha', [], ['class' => 'breadcrumb-item active']); ?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($senha, ['class' => 'form-material']) ?>
			<div class="row">
				<div class="col-md-4 col-xs-12">
					<div class="form-group ">
						<label class="control-label text-muted">Senha antiga</label>
						<?= $this->Form->control('old_password', ['class' => 'form-control', 'label' => false, 'required' => false, 'type' => 'password', 'placeholder' => 'Insira a senha antiga']) ?>
					</div>
				</div>
				<div class="col-md-4 col-xs-12">
					<div class="form-group ">
						<label class="control-label text-muted">Nova senha</label>
						<?= $this->Form->control('password1', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Insira a nova senha']) ?>
					</div>
				</div>
				<div class="col-md-4 col-xs-12">
					<div class="form-group ">
						<label class="control-label text-muted">Confirmar senha</label>
						<?= $this->Form->control('password2', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Insira novamente a nova senha']) ?>
					</div>
				</div>
			</div>
			<?= $this->Form->button('Salvar senha', ['class' => 'btn btn-success m-t-10']) ?>
			<div class="clearfix"></div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
