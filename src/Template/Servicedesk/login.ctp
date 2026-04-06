<?php
use Cake\Routing\Router;

$webroot = $this->request->getAttribute('webroot');
$ret = $webroot . 'servicedesk';
$urlEquipe = Router::url(['controller' => 'Users', 'action' => 'acessoEmpresa', '?' => ['redirect' => $ret]]);
$urlCliente = Router::url(['controller' => 'Users', 'action' => 'login', '?' => ['redirect' => $ret]]);

$this->append('css', $this->Html->css('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', ['fullBase' => true]));
$this->append('css', $this->Html->css('dist/css/login-erp'));
$this->append(
	'css',
	'<style>
		.sd-login-tabs { display: flex; gap: 0.35rem; margin: 0 0 1.15rem; padding: 0.2rem; background: rgba(0,0,0,.06); border-radius: 12px; }
		.sd-login-tabs button { flex: 1; border: 0; border-radius: 10px; padding: 0.55rem 0.5rem; font-size: 0.8rem; font-weight: 600; color: #334155; background: transparent; cursor: pointer; transition: background .15s, color .15s; }
		.sd-login-tabs button:hover { background: rgba(255,255,255,.7); }
		.sd-login-tabs button.active { background: #0d9488; color: #fff; box-shadow: 0 2px 8px rgba(13,148,136,.35); }
		.sd-login-badge { display: inline-block; font-size: 0.7rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #0d9488; margin-bottom: 0.35rem; }
		.sd-login-hint { font-size: 0.8rem; color: #64748b; margin: 0 0 1rem; min-height: 2.5rem; }
		.sd-login-back { margin-top: 1rem; text-align: center; font-size: 0.8rem; }
		.sd-login-back a { color: #0f766e; font-weight: 500; }
	</style>'
);
?>
<div class="login-erp-wrap">
	<div class="login-erp-card login-erp" style="max-width: 440px;">
		<div class="login-erp-logo">
			<img src="<?= h($webroot) ?>assets/images/pgm.png" alt="PGM Soluções em TI" class="logo"/>
		</div>
		<span class="sd-login-badge">Central de Atendimento</span>
		<div class="pgm-auth-theme-bar" style="margin-top:0.25rem;">
			<button type="button" class="pgm-auth-theme-toggle" id="pgmAuthThemeToggle" aria-label="Alternar tema claro ou escuro">
				<span class="pgm-auth-tt-ico" aria-hidden="true">☀️</span>
				<span class="pgm-auth-tt-txt">Claro</span>
			</button>
		</div>
		<div class="login-erp-title title" id="sd-login-title">Acesso PGM / Master</div>
		<p class="login-erp-subtitle sd-login-hint" id="sd-login-subtitle">Equipe e usuários internos — use o mesmo usuário e senha do ERP.</p>

		<div class="sd-login-tabs" role="tablist">
			<button type="button" class="active" data-sd-tab="equipe" role="tab" aria-selected="true">Equipe</button>
			<button type="button" data-sd-tab="cliente" role="tab" aria-selected="false">Cliente</button>
		</div>

		<?= $this->Form->create(null, ['id' => 'login', 'class' => 'signin-form', 'url' => $urlEquipe]) ?>
			<?= $this->Form->hidden('service_desk', ['value' => '1']) ?>
			<div class="input-group">
				<span class="input-icon" aria-hidden="true"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '20', 'height' => '20']) ?></span>
				<?= $this->Form->control('username', [
					'id' => 'username',
					'onkeypress' => 'return SemMaisuclaEEspaco(event)',
					'class' => 'form-control',
					'placeholder' => 'Usuário',
					'label' => false,
					'autocomplete' => 'username',
				]) ?>
			</div>
			<small class="minsuculaOnly">Letras maiúsculas não são permitidas</small>
			<div class="input-group">
				<span class="input-icon" aria-hidden="true"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '20', 'height' => '20']) ?></span>
				<?= $this->Form->control('password', [
					'id' => 'password',
					'class' => 'form-control',
					'placeholder' => 'Senha',
					'label' => false,
					'autocomplete' => 'current-password',
				]) ?>
				<button type="button" class="eye-icon" id="btn-toggle-pwd" title="Mostrar/ocultar senha" aria-label="Alternar visibilidade da senha"><span class="icon-eye-open"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '20', 'height' => '20']) ?></span><span class="icon-eye-closed hidden"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '20', 'height' => '20']) ?></span></button>
			</div>
			<div class="remember">
				<input type="checkbox" id="remember-me" name="remember" value="1"/>
				<label for="remember-me">Lembrar de mim</label>
			</div>
			<?= $this->Flash->render() ?>
			<button type="button" class="login-btn btn-login login">ACESSAR SERVICE DESK</button>

			<div class="support sd-support-equipe">
				<a href="#" class="support-btn recuperasenha"><span class="support-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '18', 'height' => '18']) ?></span> Recuperar Senha</a>
			</div>
			<div class="support sd-support-cliente" style="display:none;">
				<a href="#" class="support-btn recuperasenha"><span class="support-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '18', 'height' => '18']) ?></span> Recuperar Senha</a>
				<a href="https://download.anydesk.com/AnyDesk.exe?_ga=2.75375893.662073418.1568052070-1217284854.1568052070" target="_blank" rel="noopener" class="support-btn"><span class="support-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '18', 'height' => '18']) ?></span> Suporte Remoto</a>
			</div>
			<div class="mfa">
				Deseja desativar a autenticação de dois fatores? <span class="link-mfa desativarautenticacao">Desativar!</span>
			</div>
			<div class="login-erp-cadastro sd-support-cliente" style="display:none;">
				<a href="#" class="comeceausar">Cadastre-se</a>
			</div>
		<?= $this->Form->end() ?>

		<div class="sd-login-back sd-support-equipe">
			<a href="<?= h(Router::url(['controller' => 'Users', 'action' => 'dashboard'])) ?>">Voltar ao portal ERP</a>
		</div>
		<div class="footer login-erp-footer">PGM Soluções em TI • Service Desk • <?= date('Y') ?></div>
	</div>
</div>

<!-- Modal Recuperar Senha -->
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

<!-- Modal Desativar 2FA -->
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

<!-- Modal Duas Etapas -->
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
					<input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" id="codigo" class="form-control login-erp-modal-input" placeholder="Código de 6 dígitos" autocomplete="one-time-code" />
					<small class="codigoInvalido hide login-erp-modal-error">O código não foi informado ou é inválido.</small>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
var SD_LOGIN_URLS = { equipe: <?= json_encode($urlEquipe) ?>, cliente: <?= json_encode($urlCliente) ?> };
function sdSetTab(tab) {
	var $tabs = $('[data-sd-tab]');
	$tabs.removeClass('active').attr('aria-selected', 'false');
	$tabs.filter('[data-sd-tab="' + tab + '"]').addClass('active').attr('aria-selected', 'true');
	$('#login').attr('action', SD_LOGIN_URLS[tab]);
	if (tab === 'equipe') {
		$('#sd-login-title').text('Acesso PGM / Master');
		$('#sd-login-subtitle').text('Equipe e usuários internos — use o mesmo usuário e senha do ERP.').show();
		$('.sd-support-equipe').show();
		$('.sd-support-cliente').hide();
	} else {
		$('#sd-login-title').text('Acesso cliente');
		$('#sd-login-subtitle').hide().text('');
		$('.sd-support-equipe').hide();
		$('.sd-support-cliente').show();
	}
}
$(document).ready(function(){
	$('[data-sd-tab]').on('click', function(){
		sdSetTab($(this).attr('data-sd-tab'));
	});
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
$('.comeceausar, .recuperasenha, .desativarautenticacao').hover(function(){
	$(this).css('cursor', 'pointer');
});
$('.comeceausar').click(function(e){
	e.preventDefault();
	window.location = '<?= Router::url(['controller'=>'Users','action'=>'cadastrocliente']); ?>';
});
window.iduser = '';
$('.recuperasenha').click(function(e){
	e.preventDefault();
	$('#email-recuperar').val('');
	$('#modal-recuperar-senha').modal('show');
	setTimeout(function(){ $('#email-recuperar').focus(); }, 300);
});
$('#btn-enviar-recuperar').click(function(){
	var email = $('#email-recuperar').val();
	if(email && email.trim()) {
		window.location = '<?= Router::url(['controller' => 'Users', 'action' => 'resetPassword']); ?>' + '/' + encodeURIComponent(email.trim());
	}
});
$('#email-recuperar').on('keypress', function(e){ if(e.which === 13) $('#btn-enviar-recuperar').click(); });

$('.desativarautenticacao').click(function(e){
	e.preventDefault();
	$('#email-desativar-mfa').val('');
	$('#modal-desativar-mfa').modal('show');
	setTimeout(function(){ $('#email-desativar-mfa').focus(); }, 300);
});
$('#btn-enviar-desativar-mfa').click(function(){
	var email = $('#email-desativar-mfa').val();
	if(email && email.trim()) {
		window.location = '<?= Router::url(['controller' => 'Users', 'action' => 'enviaEmailAutenticacaoSemLogin']); ?>' + '/' + encodeURIComponent(email.trim());
	}
});
$('#email-desativar-mfa').on('keypress', function(e){ if(e.which === 13) $('#btn-enviar-desativar-mfa').click(); });
$('.minsuculaOnly').hide();
function SemMaisuclaEEspaco(e){
	var tecla = (window.event) ? event.keyCode : e.which;
	if((tecla >= 65 && tecla <= 90) || (tecla >= 192 && tecla <= 220)) {
		$('.minsuculaOnly').show();
		return false;
	}
	$('.minsuculaOnly').hide();
}
$('#btn-toggle-pwd').on('click', function(){
	var $pwd = $('#password');
	var $btn = $(this);
	if($pwd.attr('type') === 'password') {
		$pwd.attr('type', 'text');
		$btn.find('.icon-eye-open').addClass('hidden');
		$btn.find('.icon-eye-closed').removeClass('hidden');
	} else {
		$pwd.attr('type', 'password');
		$btn.find('.icon-eye-open').removeClass('hidden');
		$btn.find('.icon-eye-closed').addClass('hidden');
	}
});
window.duasEtapas = true;
window.modalAberto = false;
$('#username').change(function(){
	$(this).val($(this).val().toLowerCase());
});
$('#username, #password').keyup(function(e){
	if(e.keyCode == 13) verificaduasetapas('login');
});
$('#codigo').keyup(function() {
	if($(this).val().length == 6) {
		$.ajax({
			url: "<?= Router::url(['controller'=>'Users','action'=>'verificacodigo']); ?>/"+encodeURIComponent($('#username').val())+'/'+encodeURIComponent($('#codigo').val()),
			async: false,
			success: function(data) {
				if(data == 'sucesso') $('.signin-form').submit();
				else {
					$('#codigo').val('');
					$('.codigoInvalido').show();
				}
			},
			error: function() {
				$('#codigo').val('');
				$('.codigoInvalido').show();
			}
		});
	}
});
$('.btn-login').click(function(e) {
	e.preventDefault();
	verificaduasetapas('login');
});
$(".btn-fecha-modal, #modal-duasetapas .btn-close-login-erp").click(function(e) {
	e.preventDefault();
	$("#modal-duasetapas").modal('hide');
	window.modalAberto = false;
});
$('#modal-duasetapas').on('shown.bs.modal', function () {
	$('#codigo').focus();
});
function verificaduasetapas(acao) {
	$.ajax({
		url: "<?= Router::url(['controller'=>'Users','action'=>'verificaloginduasetapas']); ?>/"+encodeURIComponent($('#username').val()),
		async: false,
		success: function(data) {
			if(data == 'temcodigo') $('#modal-duasetapas').modal('toggle');
			else if(acao == 'login') $('.signin-form').submit();
		},
	});
}
</script>
