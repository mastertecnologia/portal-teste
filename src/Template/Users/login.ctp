<?php
use Cake\Routing\Router;
$webroot = $this->request->getAttribute('webroot');
$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap', ['fullBase' => true]);
echo $this->Html->css('dist/css/login-erp');
$this->end();
?>
<div class="login-erp-wrap">
	<div class="login-erp-card login-erp">
		<div class="login-erp-logo">
			<img src="<?= $webroot ?>assets/images/pgm.png" alt="PGM Soluções em TI" class="logo"/>
		</div>
		<div class="login-erp-title title">Acessar Plataforma de Gestão</div>

		<?= $this->Form->create('', ['id' => 'login', 'class' => 'signin-form']) ?>
			<div class="input-group">
				<span class="input-icon" aria-hidden="true"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '20', 'height' => '20']) ?></span>
				<?= $this->Form->control('username', [
					'id' => 'username',
					'onkeypress' => 'return SemMaisuclaEEspaco(event)',
					'class' => 'form-control',
					'placeholder' => 'Usuário',
					'label' => false
				]) ?>
			</div>
			<small class="minsuculaOnly">Letras maiúsculas não são permitidas</small>
			<div class="input-group">
				<span class="input-icon" aria-hidden="true"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '20', 'height' => '20']) ?></span>
				<?= $this->Form->control('password', [
					'id' => 'password',
					'class' => 'form-control',
					'placeholder' => 'Senha',
					'label' => false
				]) ?>
				<button type="button" class="eye-icon" id="btn-toggle-pwd" title="Mostrar/ocultar senha" aria-label="Alternar visibilidade da senha"><span class="icon-eye-open"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '20', 'height' => '20']) ?></span><span class="icon-eye-closed hidden"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '20', 'height' => '20']) ?></span></button>
			</div>
			<div class="remember">
				<input type="checkbox" id="remember-me" name="remember" value="1"/>
				<label for="remember-me">Lembrar de mim</label>
			</div>
			<?= $this->Flash->render() ?>
			<button type="button" class="login-btn btn-login login">ACESSAR SISTEMA</button>
			<div class="support">
				<a href="#" class="support-btn recuperasenha"><span class="support-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '18', 'height' => '18']) ?></span> Recuperar Senha</a>
				<a href="https://download.anydesk.com/AnyDesk.exe?_ga=2.75375893.662073418.1568052070-1217284854.1568052070" target="_blank" rel="noopener" class="support-btn"><span class="support-icon"><?= $this->Html->tag('svg', $this->Html->tag('path', null, ['d' => 'M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z']), ['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'width' => '18', 'height' => '18']) ?></span> Suporte Remoto</a>
			</div>
			<div class="mfa">
				Deseja desativar a autenticação de dois fatores? <span class="link-mfa desativarautenticacao">Desativar!</span>
			</div>
			<div class="login-erp-cadastro">
				<a href="#" class="comeceausar">Cadastre-se</a>
			</div>
		<?= $this->Form->end() ?>

		<div class="footer login-erp-footer">PGM Soluções em TI • ERP Platform v3.2 • <?= date('Y') ?></div>
	</div>
</div>

<!-- Modal Duas Etapas -->
<div class="modal fade none-border" id="modal-duasetapas" data-backdrop="static">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
			<div class="row p-20">
				<div class="col-12 codigo">
					<label class="control-label text-white">Informe o código disponibilizado pelo Google Authenticator App</label>
					<?= $this->Form->control('codigo', ['id' => 'codigo', 'class' => 'form-control', 'placeholder' => 'Código', 'label' => false]) ?>
					<small class="codigoInvalido hide text-white">O código não foi informado ou é inválido</small>
					<?= $this->Html->link('Cancelar', '#', ['class' => 'btn btn-fecha-modal btn-secondary btn-sm text-uppercase float-right m-t-10 btn-rounded']) ?>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function(){
	$('#idempresa').append("<option value='' disabled selected>Empresa</option>");
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
	bootbox.prompt({
		title: "Insira o seu E-mail:",
		callback: function(result){
			if(result != null) {
				window.location = '<?= Router::url(['controller' => 'Users', 'action' => 'resetPassword']); ?>' + '/' + result;
			}
		}
	});
});
$('.desativarautenticacao').click(function(e){
	e.preventDefault();
	bootbox.prompt({
		title: "Insira o seu E-mail:",
		callback: function(result){
			if(result != null) {
				window.location = '<?= Router::url(['controller' => 'Users', 'action' => 'enviaEmailAutenticacaoSemLogin']); ?>' + '/' + result;
			}
		}
	});
});
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
			url: "<?= Router::url(['controller'=>'Users','action'=>'verificacodigo']); ?>/"+$('#username').val()+'/'+$('#codigo').val(),
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
$(".btn-fecha-modal").click(function(e) {
	e.preventDefault();
	$("#modal-duasetapas").modal('toggle');
	window.modalAberto = false;
});
$('#modal-duasetapas').on('shown.bs.modal', function () {
	$('#codigo').focus();
});
function verificaduasetapas(acao) {
	$.ajax({
		url: "<?= Router::url(['controller'=>'Users','action'=>'verificaloginduasetapas']); ?>/"+$('#username').val(),
		async: false,
		success: function(data) {
			if(data == 'temcodigo') $('#modal-duasetapas').modal('toggle');
			else if(acao == 'login') $('.signin-form').submit();
		},
	});
}
</script>
