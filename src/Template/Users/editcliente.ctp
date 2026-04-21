<?php
	use Cake\Routing\Router;

	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));

	$this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);

	$idClienteVoltar = (int)($user->idcliente ?? 0);
	$urlVoltarFichaCliente = $idClienteVoltar > 0
		? Router::url(['controller' => 'Clientes', 'action' => 'edit', $idClienteVoltar]) . '#usuarios'
		: Router::url(['controller' => 'Clientes', 'action' => 'index']);
?>
<style>
.cli-editcliente-page .cli-form-footer { flex-wrap: wrap; }
.cli-editcliente-page .cli-form-footer-right { flex-wrap: wrap; align-items: center; }
.btn-cli-primary.btn-disabled { opacity: 0.55; pointer-events: none; cursor: not-allowed !important; }
</style>
<div class="col-md-12 p-0 cli-editcliente-page">
	<div class="cli-form-root cli-layout-unificado">
		<?= $this->Form->create($user, ['class' => 'form-material', 'id' => 'form-users-editcliente']) ?>
		<div class="cli-form-body cli-form-body--cadastro-lead">
			<div class="d-flex justify-content-end mb-3">
				<?= $this->Html->link(
					'<i class="fas fa-arrow-left"></i> Voltar à ficha do cliente',
					$urlVoltarFichaCliente,
					['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false, 'data-turbo' => 'false']
				) ?>
			</div>

			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-id-card"></i></div>
					<div class="cli-section-title">Login e documento</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-3-2">
						<div class="cli-fgroup">
							<label for="username">Usuário <span class="cli-req">*</span></label>
							<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o usuário']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="email">E-mail <span class="cli-req">*</span></label>
							<?= $this->Form->email('email', ['id' => 'email', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o e-mail']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="cpf">CPF</label>
							<?= $this->Form->control('cpf', ['id' => 'cpf', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o cpf']) ?>
						</div>
					</div>
				</div>
			</div>

			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-user"></i></div>
					<div class="cli-section-title">Perfil</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-1">
						<div class="cli-fgroup">
							<label for="name">Nome do usuário</label>
							<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome']) ?>
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

			<div class="cli-section mb-3">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-shield-alt"></i></div>
					<div class="cli-section-title">Status e permissões</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-4">
						<div class="cli-check-row">
							<?= $this->Form->checkbox('inativo', ['id' => 'inativo']) ?>
							<label for="inativo">Inativo</label>
						</div>
						<div class="cli-check-row">
							<?= $this->Form->checkbox('bloqueado', ['id' => 'bloqueado']) ?>
							<label for="bloqueado">Bloqueado</label>
						</div>
						<div class="cli-check-row">
							<?= $this->Form->checkbox('permissaoacesso', ['id' => 'permissaoacesso']) ?>
							<label for="permissaoacesso">Permissões administrativas</label>
						</div>
						<?php if (!empty($user->secret)) { ?>
							<div class="cli-check-row">
								<?= $this->Form->checkbox('desativasecret', ['id' => 'desativasecret']) ?>
								<label for="desativasecret">Desativar verificação em 2 fatores</label>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>

		</div>

		<div class="cli-form-footer">
			<div class="cli-form-footer-left">
				<i class="fas fa-shield-alt cli-icon-teal" style="margin-right:5px;" aria-hidden="true"></i>
				Usuário vinculado ao cliente no ERP
			</div>
			<div class="cli-form-footer-right">
				<?= $this->Form->button('<i class="fas fa-check"></i> Salvar usuário', ['class' => 'btn-cli-primary', 'escape' => false, 'type' => 'submit', 'id' => 'btn-editcliente-save']) ?>
				<?= $this->Html->link('<i class="fas fa-key"></i> Alterar senha', ['action' => 'changePasswordAdmin', $user->id], ['class' => 'btn-cli-secondary', 'escape' => false]) ?>
				<?= $this->Html->link('<i class="fas fa-envelope"></i> Redefinir senha', ['action' => 'resetPassword', $user->id], ['class' => 'btn-cli-secondary btn-reset-password', 'escape' => false]) ?>
				<?= $this->Html->link('<i class="fas fa-trash-alt"></i> Excluir usuário', ['#'], ['class' => 'btn btn-danger btn-delete', 'escape' => false]) ?>
			</div>
		</div>

		<?= $this->Form->end() ?>
	</div>
</div>

<!-- Modal Senha -->
<div class="modal fade none-border" id="modal-senha" tabindex="-1" role="dialog" aria-labelledby="modal-senha-title">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modal-senha-title">Confirmar exclusão</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body cli-form-root cli-layout-unificado">
				<div class="cli-fgroup">
					<label for="senhaadministrativa">Senha administrativa</label>
					<?= $this->Form->control('senhaadministrativa', ['type' => 'text', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira a senha administrativa']); ?>
				</div>
				<div class="cli-check-row mt-2">
					<?= $this->Form->checkbox('exibirsenha', ['checked' => true, 'id' => 'exibirsenha']) ?>
					<label for="exibirsenha">Exibir senha</label>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Html->link('Confirmar', ['action' => 'delete', $user->id], ['class' => 'btn-cli-primary btn-verificasenha']) ?>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<script>
	jQuery(function ($) {
		$('#cpf').mask('999.999.999-99');

		var $save = $('#btn-editcliente-save');
		var email = $('#email').val();
		$('#email').change(function () {
			if (email !== $(this).val()) {
				$.ajax({
					url: "<?= Router::url(['controller' => 'Users', 'action' => 'verificaloginedit']); ?>/" + $('#email').val(),
					success: function (data) {
						if (data === 'podecadastrar') {
							$save.prop('disabled', false);
							$save.removeClass('btn-disabled');
						} else {
							bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">Já existe um usuário com este e-mail no sistema, verifique e inative o usuário \'' + data + '\'.</p>');
							$save.prop('disabled', true);
							$save.addClass('btn-disabled');
						}
					},
				});
			} else {
				$save.prop('disabled', false);
				$save.removeClass('btn-disabled');
			}
		});

		$('.btn-delete').click(function (e) {
			e.preventDefault();
			$('#modal-senha').modal('toggle');
		});

		$('.btn-verificasenha').click(function (e) {
			e.preventDefault();
			var href = $(this).attr('href');
			var senha = $('#senhaadministrativa').val();
			$.ajax({
				dataType: 'json',
				url: "<?= Router::url(['action' => 'verificasenha']); ?>/" + senha,
				success: function () { window.location = href; },
				error: function (data) { bootbox.alert(data.responseJSON.Mensagem); },
			});
		});

		$('#exibirsenha').change(function () {
			if ($(this).is(':checked')) {
				$('#senhaadministrativa').attr('type', 'text');
			} else {
				$('#senhaadministrativa').attr('type', 'password');
			}
		});

		$('.btn-reset-password').click(function (e) {
			e.preventDefault();
			var href = $(this).attr('href');
			bootbox.dialog({
				title: 'Confirmar a redefinição da senha?',
				message: '<p> Será enviado um email para o usuário, que deverá redefinir a sua senha </p>',
				size: 'large',
				buttons: {
					cancel: {
						label: 'Cancelar',
						className: 'btn-danger',
						callback: function () {},
					},
					ok: {
						label: 'Confirmar',
						className: 'btn btn-pgm btn-pgm-salvar btn-success',
						callback: function () {
							window.location = href;
						},
					},
				},
			});
		});

		// Um único bootstrap-select: sem classe selectpicker no HTML (evita init global duplicado).
		var $cliSel = $('#form-users-editcliente select[name="idcliente"]');
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
