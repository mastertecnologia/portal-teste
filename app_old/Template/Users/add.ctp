<?php $this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Adicionar usuário', [], ['class' => 'breadcrumb-item active']); ?>
<style>
	.padding-20 {
		padding-left: 20px;
		padding-right: 20px;
	}
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-content">
			<?= $this->Form->create($user, ['class' => 'form-material  m-t-10']) ?>
				<div class="row padding-20">
					<div class="col-lg-8 col-md-12">
						<div class="form-group">
							<label class="control-label text-muted"> Login </label>
							<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o usuário']) ?>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label text-muted"> CPF </label>
						<?= $this->Form->control('cpf', ['id' => 'cpf', 'class' => 'form-control', 'label' => false,'placeholder' => 'Insira o CPF']) ?>
					</div>
				</div>
				<div class="row padding-20">
					<div class="col-lg-6 col-md-6">
						<div class="form-group">
							<label class="control-label text-muted"> Senha </label>
							<?= $this->Form->control('password', ['class' => 'form-control ', 'label' => false, 'required' => true, 'placeholder' => 'Insira a senha']) ?>
						</div>
					</div>
					<div class="col-lg-6 col-md-6">
						<div class="form-group">
							<label class="control-label text-muted"> Confirmar senha </label>
							<?= $this->Form->control('confirm_password', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password',  'placeholder' => 'Confime a senha']) ?>
						</div>
					</div>
				</div>
				<div class="row padding-20">
					<div class="col-12">
						<div class="form-group">
							<label class="control-label text-muted">Nome </label>
							<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome']) ?>
						</div>
					</div>
				</div>
				<div class="row padding-20">
					<div class="col-lg-12 col-md-12"><br>
						<?= $this->Form->button('Criar usuário', ['class' => 'btn waves-effect waves-light btn-success']) ?></div>
					</div>
				</div>
				<div class="clearfix"><br></div>
			<?= $this->Form->end(); ?>
		</div> <!--card content-->
		<br/>
	</div> <!--card -->
</div>

<script>
	jQuery(function($){
		$("#cpf").mask("999.999.999-99");
	});
</script>
