<!-- breadcrumb -->
<?php $this->Breadcrumbs->add('Acesso', ['controller' => 'Cliacessos', 'action' => 'edit', $cliacesso->id], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Alterar Senha', [], ['class' => 'breadcrumb-item active']); ?>

<?= $this->element('Pgm/form_shell_dark', ['formId' => 'form-cliacessos-senha']) ?>
<div class="col-md-12 clictr-edit-page">
	<div class="clictr-card">
			<?= $this->Form->create($cliacesso, ['class' => 'form-material clictr-form', 'id' => 'form-cliacessos-senha']) ?>
			<div class="clictr-section">
				<div class="clictr-section-title">Nova credencial</div>
				<div class="row">
					<div class="col-lg-4 col-md-4">
						<label class="clictr-label" for="old-password">Senha antiga</label>
						<?= $this->Form->control('old_password', ['class' => 'form-control', 'label' => false, 'required' => false, 'type' => 'password', 'placeholder' => 'Insira a senha antiga', 'id' => 'old-password']) ?>
					</div>
					<div class="col-lg-4 col-md-4">
						<label class="clictr-label" for="password1">Nova senha</label>
						<?= $this->Form->control('password1', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Insira a nova senha']) ?>
					</div>
					<div class="col-lg-4 col-md-4">
						<label class="clictr-label" for="password2">Confirmar senha</label>
						<?= $this->Form->control('password2', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Insira novamente a nova senha']) ?>
					</div>
				</div>
			</div>
			<div class="clictr-actions">
				<?= $this->Form->button('Salvar senha', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
			</div>
			<?= $this->Form->end(); ?>
	</div>
</div>
