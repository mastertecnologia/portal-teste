<?php
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));

	$this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Adicionar cliente', [], ['class' => 'breadcrumb-item active']);

	$urlVoltarUsuarios = ['controller' => 'Users', 'action' => 'index'];
?>
<style>
.cli-addcliente-page .cli-form-footer { flex-wrap: wrap; }
.cli-addcliente-page .cli-form-footer-right { flex-wrap: wrap; align-items: center; }
</style>
<div class="col-md-12 p-0 cli-addcliente-page">
	<div class="cli-form-root cli-layout-unificado">
		<?= $this->Form->create($user, ['class' => 'form-material', 'id' => 'form-users-addcliente']) ?>
		<div class="cli-form-body cli-form-body--cadastro-lead">
			<div class="d-flex justify-content-end mb-3">
				<?= $this->Html->link(
					'<i class="fas fa-arrow-left"></i> Voltar à lista de usuários',
					$urlVoltarUsuarios,
					['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false, 'data-turbo' => 'false']
				) ?>
			</div>

			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-lock"></i></div>
					<div class="cli-section-title">Acesso</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-3-2">
						<div class="cli-fgroup">
							<label for="username">Usuário <span class="cli-req">*</span></label>
							<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o usuário']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="cpf">CPF <span class="cli-req">*</span></label>
							<?= $this->Form->control('cpf', ['id' => 'cpf', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o cpf']) ?>
						</div>
					</div>
					<div class="cli-fg cli-fg-2">
						<div class="cli-fgroup">
							<label for="password">Senha <span class="cli-req">*</span></label>
							<?= $this->Form->control('password', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira a senha']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="confirm-password">Confirmar senha <span class="cli-req">*</span></label>
							<?= $this->Form->control('confirm_password', ['id' => 'confirm-password', 'class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Confirme a senha']) ?>
						</div>
					</div>
				</div>
			</div>

			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-id-badge"></i></div>
					<div class="cli-section-title">Identificação</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-2">
						<div class="cli-fgroup">
							<label for="name">Nome</label>
							<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="email">E-mail</label>
							<?= $this->Form->control('email', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o e-mail']) ?>
						</div>
					</div>
					<div class="cli-fg cli-fg-1">
						<div class="cli-fgroup">
							<label for="idcliente">Cliente <span class="cli-req">*</span></label>
							<?= $this->Form->control('idcliente', [
								'type' => 'select',
								'data-live-search' => 'true',
								'data-container' => 'body',
								'options' => $clientes,
								'title' => 'Selecione um cliente',
								'class' => 'form-control',
								'label' => false,
								'required' => true,
							]) ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="cli-form-footer">
			<div class="cli-form-footer-left">
				<i class="fas fa-user-plus cli-icon-teal" style="margin-right:5px;" aria-hidden="true"></i>
				Novo utilizador com acesso ao portal do cliente
			</div>
			<div class="cli-form-footer-right">
				<?= $this->Html->link('<i class="fas fa-times"></i> Cancelar', $urlVoltarUsuarios, ['class' => 'btn-cli-secondary', 'escape' => false, 'data-turbo' => 'false']) ?>
				<?= $this->Form->button('<i class="fas fa-check"></i> Criar usuário', ['class' => 'btn-cli-primary', 'escape' => false, 'type' => 'submit']) ?>
			</div>
		</div>

		<?= $this->Form->end() ?>
	</div>
</div>
<script>
	jQuery(function ($) {
		$('#cpf').mask('999.999.999-99');

		var $cliSel = $('#form-users-addcliente select[name="idcliente"]');
		if ($cliSel.length && typeof $.fn.selectpicker === 'function') {
			var $fg = $cliSel.closest('.cli-fgroup');
			$fg.find('> .dropdown.bootstrap-select').each(function () {
				var $w = $(this);
				if (!$w.find('select[name="idcliente"]').length) {
					$w.remove();
				}
			});
			if ($cliSel.data('selectpicker')) {
				try {
					$cliSel.selectpicker('destroy');
				} catch (eCli) {}
			}
			$cliSel.selectpicker({
				container: 'body',
				liveSearch: true,
			});
		}
	});
</script>
