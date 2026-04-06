<?php
use Cake\Routing\Router;
$webroot = $this->request->getAttribute('webroot');
$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', ['fullBase' => true]);
echo $this->Html->css('dist/css/login-erp');    /* modais */
echo $this->Html->css('dist/css/login-modern'); /* layout */
$this->end();
?>
<div class="lm-wrap lm-admin">

	<!-- ══ Painel de Marca (esquerda) ══ -->
	<div class="lm-brand-panel">
		<div class="lm-circle2"></div>
		<div class="lm-brand-content">
			<img src="<?= $webroot ?>assets/images/pgm.png" alt="PGM Soluções em TI" class="lm-brand-logo"/>
			<h1 class="lm-brand-name">PGM Soluções em TI</h1>
			<p class="lm-brand-tagline">Painel Administrativo &amp; Técnico</p>
			<ul class="lm-brand-features">
				<li>
					<span class="lm-feat-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '14', 'height' => '14']) ?></span>
					Gestão de chamados e OS
				</li>
				<li>
					<span class="lm-feat-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '14', 'height' => '14']) ?></span>
					Gerenciamento de clientes
				</li>
				<li>
					<span class="lm-feat-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '14', 'height' => '14']) ?></span>
					Configurações do sistema
				</li>
				<li>
					<span class="lm-feat-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '14', 'height' => '14']) ?></span>
					Relatórios e dashboards
				</li>
			</ul>
		</div>
	</div>

	<!-- ══ Painel do Formulário (direita) ══ -->
	<div class="lm-form-panel">
		<div class="lm-form-card">
			<div class="lm-form-header">
				<p class="lm-form-eyebrow">Equipe PGM / Master</p>
				<h2 class="lm-form-title">Acesso Administrativo</h2>
				<p class="lm-form-subtitle">Área restrita — somente equipe autorizada</p>
			</div>

			<?= $this->Form->create('', ['id' => 'login', 'class' => 'signin-form', 'url' => ['action' => 'acessoEmpresa']]) ?>

				<?= $this->Flash->render() ?>

				<!-- Usuário -->
				<div class="lm-field">
					<label class="lm-field-label" for="username">Usuário</label>
					<div class="lm-field-wrap">
						<span class="lm-field-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '18', 'height' => '18']) ?></span>
						<?= $this->Form->control('username', [
							'id'         => 'username',
							'onkeypress' => 'return SemMaisuclaEEspaco(event)',
							'class'      => 'form-control',
							'placeholder'=> 'Usuário interno',
							'label'      => false,
						]) ?>
					</div>
					<small class="lm-uppercase-warn minsuculaOnly">Letras maiúsculas não são permitidas</small>
				</div>

				<!-- Senha -->
				<div class="lm-field">
					<label class="lm-field-label" for="password">Senha</label>
					<div class="lm-field-wrap">
						<span class="lm-field-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '18', 'height' => '18']) ?></span>
						<?= $this->Form->control('password', [
							'id'         => 'password',
							'class'      => 'form-control',
							'placeholder'=> 'Senha de acesso',
							'label'      => false,
						]) ?>
						<button type="button" class="lm-pwd-toggle" id="btn-toggle-pwd" title="Mostrar/ocultar senha" aria-label="Alternar visibilidade da senha">
							<span class="lm-eye-on"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '18', 'height' => '18']) ?></span>
							<span class="lm-eye-off"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '18', 'height' => '18']) ?></span>
						</button>
					</div>
				</div>

				<!-- Lembrar -->
				<div class="lm-form-row">
					<div class="lm-remember">
						<input type="checkbox" id="remember-me" name="remember" value="1"/>
						<label for="remember-me">Lembrar de mim</label>
					</div>
				</div>

				<!-- Botão entrar -->
				<button type="button" class="lm-btn-submit login-btn btn-login login">Entrar no Sistema</button>

				<!-- Links auxiliares -->
				<div class="lm-links">
					<div class="lm-support-row">
						<a href="#" class="lm-support-btn recuperasenha">
							<?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '14', 'height' => '14']) ?>
							Recuperar senha
						</a>
					</div>
					<div class="lm-link-small">
						Desativar autenticação de dois fatores?
						<span class="lm-link desativarautenticacao">Desativar!</span>
					</div>
					<div class="lm-link-small">
						<?= $this->Html->link('← Sou cliente — acessar portal', ['controller' => 'Users', 'action' => 'login'], ['class' => 'link-cliente']) ?>
					</div>
				</div>

				<hr class="lm-divider">
				<p class="lm-notice">PGM Soluções em TI &bull; ERP Platform v3.2 &bull; <?= date('Y') ?><br>Acesso monitorado — uso exclusivo da equipe interna</p>

			<?= $this->Form->end() ?>
		</div>
	</div>
</div>

<!-- ══ Modal Recuperar Senha ══ -->
<div class="modal fade login-erp-modal" id="modal-recuperar-senha" tabindex="-1" data-backdrop="static">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Recuperar senha</h5>
				<button type="button" class="btn-close-login-erp" data-dismiss="modal" aria-label="Fechar">&times;</button>
			</div>
			<div class="modal-body">
				<label for="email-recuperar" class="login-erp-modal-label">Informe o e-mail da sua conta para receber o link de redefinição.</label>
				<input type="email" id="email-recuperar" class="form-control login-erp-modal-input" placeholder="E-mail" autocomplete="email">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-login-erp-modal btn-cancel" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-pgm btn-pgm-email btn-login-erp-modal btn-primary" id="btn-enviar-recuperar">Enviar</button>
			</div>
		</div>
	</div>
</div>

<!-- ══ Modal Desativar 2FA ══ -->
<div class="modal fade login-erp-modal" id="modal-desativar-mfa" tabindex="-1" data-backdrop="static">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Desativar autenticação em duas etapas</h5>
				<button type="button" class="btn-close-login-erp" data-dismiss="modal" aria-label="Fechar">&times;</button>
			</div>
			<div class="modal-body">
				<label for="email-desativar-mfa" class="login-erp-modal-label">Informe o e-mail da sua conta para receber o link de desativação.</label>
				<input type="email" id="email-desativar-mfa" class="form-control login-erp-modal-input" placeholder="E-mail" autocomplete="email">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-login-erp-modal btn-cancel" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-pgm btn-pgm-email btn-login-erp-modal btn-primary" id="btn-enviar-desativar-mfa">Enviar</button>
			</div>
		</div>
	</div>
</div>

<!-- ══ Modal 2FA ══ -->
<div class="modal fade none-border login-erp-modal" id="modal-duasetapas" data-backdrop="static">
	<div class="modal-dialog modal-dialog-centered modal-md">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Verificação em duas etapas</h5>
				<button type="button" class="btn-close-login-erp btn-fecha-modal" aria-label="Fechar">&times;</button>
			</div>
			<div class="modal-body">
				<div class="codigo">
					<label class="login-erp-modal-label" for="codigo">Informe o código do Google Authenticator</label>
					<?= $this->Form->control('codigo', ['id' => 'codigo', 'class' => 'form-control login-erp-modal-input', 'placeholder' => 'Código de 6 dígitos', 'label' => false]) ?>
					<small class="codigoInvalido hide login-erp-modal-error">O código não foi informado ou é inválido.</small>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function(){
	try {
		var savedUsername = localStorage.getItem('portal_remember_username');
		if (savedUsername) {
			$('#username').val(savedUsername);
			$('#remember-me').prop('checked', true);
		}
	} catch (e) {}

	$('#remember-me').on('change', function(){
		try {
			if ($(this).is(':checked')) {
				var u = ($('#username').val() || '').trim();
				if (u) localStorage.setItem('portal_remember_username', u);
			} else {
				localStorage.removeItem('portal_remember_username');
			}
		} catch (e) {}
	});

	$('form#login').on('submit', function(){
		try {
			var u = ($('#username').val() || '').trim();
			var remember = $('#remember-me').is(':checked');
			if (remember && u) localStorage.setItem('portal_remember_username', u);
			if (!remember) localStorage.removeItem('portal_remember_username');
		} catch (e) {}
	});
});

window.iduser = '';

$('.recuperasenha').on('click', function(e){
	e.preventDefault();
	$('#email-recuperar').val('');
	$('#modal-recuperar-senha').modal('show');
	setTimeout(function(){ $('#email-recuperar').focus(); }, 300);
});
$('#btn-enviar-recuperar').on('click', function(){
	var email = $('#email-recuperar').val();
	if (email && email.trim()) {
		window.location = '<?= Router::url(['controller' => 'Users', 'action' => 'resetPassword']); ?>' + '/' + encodeURIComponent(email.trim());
	}
});
$('#email-recuperar').on('keypress', function(e){ if (e.which === 13) $('#btn-enviar-recuperar').click(); });

$('.desativarautenticacao').on('click', function(e){
	e.preventDefault();
	$('#email-desativar-mfa').val('');
	$('#modal-desativar-mfa').modal('show');
	setTimeout(function(){ $('#email-desativar-mfa').focus(); }, 300);
});
$('#btn-enviar-desativar-mfa').on('click', function(){
	var email = $('#email-desativar-mfa').val();
	if (email && email.trim()) {
		window.location = '<?= Router::url(['controller' => 'Users', 'action' => 'enviaEmailAutenticacaoSemLogin']); ?>' + '/' + encodeURIComponent(email.trim());
	}
});
$('#email-desativar-mfa').on('keypress', function(e){ if (e.which === 13) $('#btn-enviar-desativar-mfa').click(); });

$('.lm-uppercase-warn, .minsuculaOnly').hide();
function SemMaisuclaEEspaco(e){
	var tecla = (window.event) ? event.keyCode : e.which;
	if ((tecla >= 65 && tecla <= 90) || (tecla >= 192 && tecla <= 220)) {
		$('.lm-uppercase-warn, .minsuculaOnly').show();
		return false;
	}
	$('.lm-uppercase-warn, .minsuculaOnly').hide();
}

$('#btn-toggle-pwd').on('click', function(){
	var $pwd = $('#password');
	var $btn = $(this);
	if ($pwd.attr('type') === 'password') {
		$pwd.attr('type', 'text');
		$btn.find('.lm-eye-on').hide();
		$btn.find('.lm-eye-off').show();
	} else {
		$pwd.attr('type', 'password');
		$btn.find('.lm-eye-on').show();
		$btn.find('.lm-eye-off').hide();
	}
});

window.duasEtapas = true;
window.modalAberto = false;

$('#username').on('change', function(){ $(this).val($(this).val().toLowerCase()); });
$('#username, #password').on('keyup', function(e){ if (e.keyCode == 13) verificaduasetapas('login'); });

$('#codigo').on('keyup', function() {
	if ($(this).val().length == 6) {
		$.ajax({
			url: "<?= Router::url(['controller'=>'Users','action'=>'verificacodigo']); ?>/"+encodeURIComponent($('#username').val())+'/'+encodeURIComponent($('#codigo').val()),
			async: false,
			success: function(data) {
				if (data == 'sucesso') $('.signin-form').submit();
				else { $('#codigo').val(''); $('.codigoInvalido').show(); }
			},
			error: function() { $('#codigo').val(''); $('.codigoInvalido').show(); }
		});
	}
});

$('.btn-login').on('click', function(e){ e.preventDefault(); verificaduasetapas('login'); });
$(".btn-fecha-modal, #modal-duasetapas .btn-close-login-erp").on('click', function(e){
	e.preventDefault();
	$("#modal-duasetapas").modal('hide');
	window.modalAberto = false;
});
$('#modal-duasetapas').on('shown.bs.modal', function(){ $('#codigo').focus(); });

function verificaduasetapas(acao) {
	$.ajax({
		url: "<?= Router::url(['controller'=>'Users','action'=>'verificaloginduasetapas']); ?>/"+encodeURIComponent($('#username').val()),
		async: false,
		success: function(data) {
			if (data == 'temcodigo') $('#modal-duasetapas').modal('toggle');
			else if (acao == 'login') $('.signin-form').submit();
		},
	});
}
</script>
