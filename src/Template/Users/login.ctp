<?php
use Cake\Routing\Router;
$webroot = $this->request->getAttribute('webroot');
$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', ['fullBase' => true]);
echo $this->Html->css('dist/css/login-erp');   /* modais */
echo $this->Html->css('dist/css/login-modern'); /* layout */
$this->end();
?>
<div class="lm-wrap lm-client">

	<!-- ══ Painel de Marca (esquerda) ══ -->
	<div class="lm-brand-panel">
		<div class="lm-circle2"></div>
		<div class="lm-brand-content">
			<img src="<?= $webroot ?>assets/images/pgm.png" alt="PGM Soluções em TI" class="lm-brand-logo"/>
			<h1 class="lm-brand-name">PGM Soluções em TI</h1>
			<p class="lm-brand-tagline">Portal do Cliente</p>
			<ul class="lm-brand-features">
				<li>
					<span class="lm-feat-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M20 6h-2.18c.07-.44.18-.86.18-1.3C18 2.12 15.88 0 13.3 0c-1.49 0-2.71.73-3.5 1.83L9 3.4 8.2 1.83C7.41.73 6.19 0 4.7 0 2.12 0 0 2.12 0 4.7c0 .44.11.86.18 1.3H0v14h20V6zm-8.5 11.5L9 16l-2.5 1.5V10h5v7.5z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 20 20', 'width' => '14', 'height' => '14']) ?></span>
					Abra e acompanhe chamados
				</li>
				<li>
					<span class="lm-feat-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M19 3H5c-1.1 0-2 .9-2 2v14l4-4h12c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '14', 'height' => '14']) ?></span>
					Histórico completo de atendimentos
				</li>
				<li>
					<span class="lm-feat-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 14H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '14', 'height' => '14']) ?></span>
					Contratos e faturas
				</li>
				<li>
					<span class="lm-feat-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm4.24 16L12 15.45 7.77 18l1.12-4.81-3.73-3.23 4.92-.42L12 5l1.92 4.53 4.92.42-3.73 3.23L16.23 18z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '14', 'height' => '14']) ?></span>
					Relatórios e indicadores
				</li>
			</ul>
		</div>
	</div>

	<!-- ══ Painel do Formulário (direita) ══ -->
	<div class="lm-form-panel">
		<div class="lm-form-card">
			<div class="pgm-auth-theme-bar">
				<button type="button" class="pgm-auth-theme-toggle" id="pgmAuthThemeToggle" aria-label="Alternar tema claro ou escuro">
					<span class="pgm-auth-tt-ico" aria-hidden="true">☀️</span>
					<span class="pgm-auth-tt-txt">Claro</span>
				</button>
			</div>

			<div class="lm-form-header">
				<p class="lm-form-eyebrow">Portal do Cliente</p>
				<h2 class="lm-form-title">Bem-vindo de volta</h2>
				<p class="lm-form-subtitle">Entre com suas credenciais para continuar</p>
			</div>

			<?= $this->Form->create('', ['id' => 'login', 'class' => 'signin-form']) ?>

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
							'placeholder'=> 'Digite seu usuário',
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
							'placeholder'=> 'Digite sua senha',
							'label'      => false,
						]) ?>
						<button type="button" class="lm-pwd-toggle" id="btn-toggle-pwd" title="Mostrar/ocultar senha" aria-label="Alternar visibilidade da senha">
							<span class="lm-eye-on"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '18', 'height' => '18']) ?></span>
							<span class="lm-eye-off"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '18', 'height' => '18']) ?></span>
						</button>
					</div>
				</div>

				<!-- Lembrar de mim -->
				<div class="lm-form-row">
					<div class="lm-remember">
						<input type="checkbox" id="remember-me" name="remember" value="1"/>
						<label for="remember-me">Lembrar de mim</label>
					</div>
				</div>

				<!-- Botão entrar -->
				<button type="button" class="lm-btn-submit login-btn btn-login login">Entrar no Portal</button>

				<!-- Links auxiliares -->
				<div class="lm-links">
					<div class="lm-support-row">
						<a href="#" class="lm-support-btn recuperasenha">
							<?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '14', 'height' => '14']) ?>
							Recuperar senha
						</a>
						<a href="https://download.anydesk.com/AnyDesk.exe" target="_blank" rel="noopener" class="lm-support-btn">
							<?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'width' => '14', 'height' => '14']) ?>
							Suporte remoto
						</a>
					</div>
					<div class="lm-link-small">
						Desativar autenticação de dois fatores?
						<span class="lm-link desativarautenticacao">Desativar!</span>
					</div>
					<div class="lm-link-small">
						Ainda não tem conta?
						<a href="#" class="comeceausar">Cadastre-se</a>
					</div>
	
				</div>

				<hr class="lm-divider">
				<p class="lm-notice">PGM Soluções em TI &bull; ERP Platform v3.2 &bull; <?= date('Y') ?></p>

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

<!-- ══ Modal 2FA (código Google Authenticator) ══ -->
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
	$('#idempresa').append("<option value='' disabled selected>Empresa</option>");

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

$('.comeceausar').on('click', function(e){
	e.preventDefault();
	window.location = '<?= Router::url(['controller'=>'Users','action'=>'cadastrocliente']); ?>';
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
			url: "<?= Router::url(['controller'=>'Users','action'=>'verificacodigo']); ?>/"+$('#username').val()+'/'+$('#codigo').val(),
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
		url: "<?= Router::url(['controller'=>'Users','action'=>'verificaloginduasetapas']); ?>/"+$('#username').val(),
		async: false,
		success: function(data) {
			if (data == 'temcodigo') $('#modal-duasetapas').modal('toggle');
			else if (acao == 'login') $('.signin-form').submit();
		},
	});
}
</script>
