<style>
.padding-20 {
	padding-left: 20px;
	padding-right: 20px;
}

.margin-10-bottom {
	margin-bottom: 10px;
}
</style>

<!-- breadcrumb -->
<?php $this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Adicionar cliente', [], ['class' => 'breadcrumb-item active']); ?>

<div class="col-md-12">
	<div class="card">
		<div class="card-content">
			<?= $this->Form->create($user, ['class' => 'form-material  m-t-10']) ?>
			<div class="row padding-20">
				<div class="col-lg-8 col-md-12">
					<div class="input-group">
						<div class="form-group  col-md-12">
							<label class="control-label text-muted">Usuário</label>
							<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o usuário']) ?>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-12">
					<div class="form-group">
						<label class="control-label text-muted"> CPF</label>
						<?= $this->Form->control('cpf', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o cpf']) ?>
					</div>
				</div>
			</div>
			<div class="row padding-20">
				<div class="col-lg-3 col-md-6">
					<div class="input-group">
						<div class="form-group  col-md-12">
							<label class="control-label text-muted"> Senha</label>
							<?= $this->Form->control('password', ['class' => 'form-control ', 'label' => false, 'required' => true, 'placeholder' => 'Insira a senha']) ?>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-6">
					<div class="input-group">
						<div class="form-group  col-md-12">
							<label class="control-label text-muted"> Confirmar senha</label>
							<?= $this->Form->control('confirm_password', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password',  'placeholder' => 'Confime a senha']) ?>
						</div>
					</div>
				</div>
			</div>
			<div class="row padding-20">
				<div class="col-lg-3 col-md-6">
					<div class="input-group">
						<div class="form-group  col-md-12">
							<label class="control-label text-muted"> Nome</label>
							<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome']) ?>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-6">
					<div class="input-group">
						<div class="form-group  col-md-12">
							<label class="control-label text-muted"> E-mail</label>
							<?= $this->Form->control('email', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o e-mail']) ?>
						</div>
					</div>
				</div>
				<div class="col-lg-6 col-md-6">
					<label class="control-label">Cliente</label>
					<?= $this->Form->control('idcliente', ['data-live-search' => 'true', 'class' => 'form-control selectpicker', 'options' => $clientes, 'title' => 'Selecione um cliente', 'label' => false, 'required' => true]) ?>
				</div>
			</div>

			<?= $this->Form->button('Criar usuário', ['class' => 'btn btn-pgm btn-pgm-salvar waves-effect waves-light btn-success m-l-30 m-b-20']) ?>
			<div class="clearfix"></div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<script>
	jQuery(function($){
		$("#cpf").mask("999.999.999-99");
	});
</script>
