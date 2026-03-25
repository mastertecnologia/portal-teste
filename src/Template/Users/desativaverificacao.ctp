<?php
	$this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Desativar verificação', [], ['class' => 'breadcrumb-item active']); 
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($user, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-md-4 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Senha</label>
							<?= $this->Form->control('senha', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Insira sua senha']) ?>
						</div>
					</div>
					<div class="col-md-4 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Código de verificação</label>
							<?= $this->Form->control('codigo', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o código']) ?>
						</div>
					</div>
				</div>
				<?= $this->Form->button('Desativar Verificação', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-10']) ?>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
