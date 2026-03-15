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
				<span class="input-icon">👤</span>
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
				<span class="input-icon">🔒</span>
				<?= $this->Form->control('password', [
					'id' => 'password',
					'class' => 'form-control',
					'placeholder' => 'Senha',
					'label' => false
				]) ?>
				<button type="button" class="eye-icon" id="btn-toggle-pwd" title="Mostrar/ocultar senha" aria-label="Alternar visibilidade da senha">👁</button>
			</div>
			<div class="remember">
				<input type="checkbox" id="remember-me" name="remember" value="1"/>
				<label for="remember-me">Lembrar de mim</label>
			</div>
			<?= $this->Flash->render() ?>
			<button type="button" class="login-btn btn-login login">ACESSAR SISTEMA</button>
			<div class="support">
				<a href="#" class="support-btn recuperasenha">🔑 Recuperar Senha</a>
				<a href="https://download.anydesk.com/AnyDesk.exe?_ga=2.75375893.662073418.1568052070-1217284854.1568052070" target="_blank" rel="noopener" class="support-btn">🖥 Suporte Remoto</a>
			</div>
			<div class="mfa">
				Deseja desativar a autenticação de dois fatores? <span class="link-mfa desativarautenticacao">Desativar!</span>
			</div>
			<div class="login-erp-cadastro">
				<a href="#" class="comeceausar">Cadastre-se</a>
			</div>
		<?= $this->Form->end() ?>

		<div class="footer">PGM Soluções em TI • ERP Platform v3.2 • <?= date('Y') ?></div>
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
		$btn.text('🙈');
	} else {
		$pwd.attr('type', 'password');
		$btn.text('👁');
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
