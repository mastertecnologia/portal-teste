<?php
	$this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Desativar verificação', [], ['class' => 'breadcrumb-item active']); 
?>
<div class="col-md-12" style="display:flex; justify-content: center; margin-top: 10%;">
<div class="card">
	<h2 class="card-title" style="padding: 10px"> Desativar Verificação de Dois Fatores </h2>
		<div class="card-body" >
			<?= $this->Form->create(null, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-md-8 col-xs-12" style="align-items: left; justify-content:start">
						<div class="form-group ">
							<label class="control-label text-muted" style="display:flex;">Email/Usuário</label>
							<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'text', 'placeholder' => 'Insira seu email ou nome de usuario']) ?>
						</div>
						<div class="form-group ">
							<label class="control-label text-muted"  style="display:flex;">Senha</label>
							<?= $this->Form->control('password', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Insira sua senha']) ?>
						</div>
					</div>
				</div>
				<?= $this->Form->button('Desativar Verificação', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-10']) ?>
			<?= $this->Form->end(); ?>
		</div>
</div>
</div>
