<?php
	$this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Desativar verificação', [], ['class' => 'breadcrumb-item active']); 
?>
<style>
	.mfa-disable-page {
		display: flex;
		justify-content: center;
		margin-top: 10%;
	}
	.mfa-disable-title {
		padding: 10px;
	}
	.mfa-disable-form-col {
		align-items: flex-start;
		justify-content: flex-start;
	}
	.mfa-disable-label {
		display: flex;
	}
</style>
<div class="col-md-12 mfa-disable-page">
<div class="card">
	<h2 class="card-title mfa-disable-title"> Desativar Verificação de Dois Fatores </h2>
		<div class="card-body" >
			<?= $this->Form->create(null, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-md-8 col-xs-12 mfa-disable-form-col">
						<div class="form-group ">
							<label class="control-label text-muted mfa-disable-label">Email/Usuário</label>
							<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'text', 'placeholder' => 'Insira seu email ou nome de usuario']) ?>
						</div>
						<div class="form-group ">
							<label class="control-label text-muted mfa-disable-label">Senha</label>
							<?= $this->Form->control('password', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Insira sua senha']) ?>
						</div>
					</div>
				</div>
				<?= $this->Form->button('Desativar Verificação', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-10']) ?>
			<?= $this->Form->end(); ?>
		</div>
</div>
</div>
