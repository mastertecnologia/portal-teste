<!-- breadcrumb -->
<?php $this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Adicionar cliente', [], ['class' => 'breadcrumb-item active']); ?>
<?= $this->element('Pgm/form_shell_dark', ['formId' => 'form-users-addcliente']) ?>

<div class="col-md-12 clictr-edit-page">
	<div class="clictr-card">
			<?= $this->Form->create($user, ['class' => 'form-material clictr-form', 'id' => 'form-users-addcliente']) ?>
			<div class="clictr-section">
				<div class="clictr-section-title">Acesso</div>
				<div class="row">
					<div class="col-lg-8 col-md-12">
						<div class="form-group">
							<label class="clictr-label" for="username">Usuário</label>
							<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o usuário']) ?>
						</div>
					</div>
					<div class="col-lg-4 col-md-12">
						<div class="form-group">
							<label class="clictr-label" for="cpf">CPF</label>
							<?= $this->Form->control('cpf', ['id' => 'cpf', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o cpf']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3 col-md-6">
						<div class="form-group">
							<label class="clictr-label" for="password">Senha</label>
							<?= $this->Form->control('password', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira a senha']) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-6">
						<div class="form-group">
							<label class="clictr-label" for="confirm-password">Confirmar senha</label>
							<?= $this->Form->control('confirm_password', ['id' => 'confirm-password', 'class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Confime a senha']) ?>
						</div>
					</div>
				</div>
			</div>
			<div class="clictr-section">
				<div class="clictr-section-title">Identificação</div>
				<div class="row">
					<div class="col-lg-3 col-md-6">
						<div class="form-group">
							<label class="clictr-label" for="name">Nome</label>
							<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome']) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-6">
						<div class="form-group">
							<label class="clictr-label" for="email">E-mail</label>
							<?= $this->Form->control('email', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o e-mail']) ?>
						</div>
					</div>
					<div class="col-lg-6 col-md-6">
						<label class="clictr-label" for="idcliente">Cliente</label>
						<?= $this->Form->control('idcliente', ['data-live-search' => 'true', 'class' => 'form-control selectpicker', 'options' => $clientes, 'title' => 'Selecione um cliente', 'label' => false, 'required' => true]) ?>
					</div>
				</div>
			</div>
			<div class="clictr-actions">
				<?= $this->Form->button('Criar usuário', ['class' => 'btn btn-pgm btn-pgm-salvar waves-effect waves-light btn-success']) ?>
			</div>
			<?= $this->Form->end(); ?>
	</div>
</div>
<script>
	jQuery(function($){
		$("#cpf").mask("999.999.999-99");
	});
</script>
