<!-- breadcrumb -->
<?php $this->Breadcrumbs->add('Dashboard', ['controller' => 'users', 'action' => 'dashboard'], ['class' => 'breadcrumb-item']); ?>

<?php if($role == 0) $this->Breadcrumbs->add('Editar', ['controller' => 'users', 'action' => "editcliente/$user->id"], ['class' => 'breadcrumb-item']); ?>

<?php $this->Breadcrumbs->add('Alterar senha', [], ['class' => 'breadcrumb-item active']); ?>


<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($user, ['class' => 'form-material']) ?>
			<div class="row">
				<div class="col-lg-4 col-md-6">
					<div class="form-group ">
						<label class="control-label text-muted">Nova senha</label>
						<?= $this->Form->control('password1', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Insira a nova senha']) ?>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
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
