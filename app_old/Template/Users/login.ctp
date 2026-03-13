<?php use Cake\Routing\Router; ?>
<style>
	.form-control {
		background: transparent;
		border: none;
		height: 50px;
		color: white !important;
		border: 1px solid transparent;
		background: #004640;
		border-radius: 40px;
		padding-left: 20px;
		padding-right: 20px;
		-webkit-transition: 0.3s;
		-o-transition: 0.3s;
		transition: 0.3s;
	}
	.form-control:focus { 
		color: white !important; 
		background-color: #00ab9e !important; 
	}
	.form-control::placeholder { color: #ebebeb !important; }
	.btn-login, .comeceausar {
		color: #fff;
		background-color: #098479 !important;
		border-color: #098479 !important;
	}
	.btn-login:hover { 
		color: white !important; 
		background-color: #00ab9e !important; 
	}
	.modal.show .modal-dialog { margin-top: 15%; }
</style>
<section id="wrapper">
	<div class="login-register" style="background-image:url(<?=$this->request->getAttribute('webroot') . 'assets/images/background/login-register.jpg'?>);">
		<section class="ftco-section">
			<div class="container">
				<div class="row justify-content-center">
					<div class="col-md-4 col-lg-2">
						<img src="<?=$this->request->getAttribute('webroot') . 'assets/images/pgm.png'?>"  alt="logo" class="img-fluid">
					</div>
					<div class="col-md-4 col-lg-4">
						<div class="login-wrap p-0">
							<?= $this->Form->create("", ['id' => 'login', 'class' => 'signin-form']) ?>
								<div class="form-group m-t-40">
									<div class="col-xs-12">
										<?= $this->Form->control('username', ['id' => 'username', 'onkeypress' => 'return SemMaisuclaEEspaco(event)', 'class' => 'form-control', 'placeholder' => 'Usuário', 'label' => false]) ?>
										<small class='minsuculaOnly text-light'>Letras maiúsculas não são permitidas</small>
									</div>
								</div>
								<div class="form-group">
									<div class="col-xs-12">
										<?= $this->Form->control('password', ['id' => 'password', 'class' => 'form-control', 'placeholder' => 'Senha', 'label' => false]) ?>
									</div>
								</div>
								<?= $this->Flash->render() ?>
								<div class="row justify-content-center text-light">
									<div class="col-md-6 col-sm-12">
										<?= $this->Html->link('Acessar', ['#'], ['class' => 'btn btn-lg btn-login btn-block text-uppercase btn-rounded login']) ?>
									</div>
									<div class="col-md-6 col-sm-12">
										<?= $this->Html->link('Cadastre-se', ['#'], ['class' => 'btn btn-lg  btn-block text-uppercase btn-rounded comeceausar']) ?>
									</div>
								</div>
								<div class="form-group text-center text-light m-t-20">
									<h5>  <b style='font-weight: 500' class='recuperasenha'>Recuperar Senha</b> </h5>
									<!-- <h5 class='p-t-5'> <b style='font-weight: 500' class='suporte'> Suporte Remoto </b> </h5> -->
									<h5 class='p-t-5'> <b style='font-weight: 500'> <a href='https://download.anydesk.com/AnyDesk.exe?_ga=2.75375893.662073418.1568052070-1217284854.1568052070' class='link text-white'> Suporte Remoto </a> </b> </h5>
									<!-- <div class="suportes hide p-t-10">
										<a href='https://download.anydesk.com/AnyDesk.exe?_ga=2.75375893.662073418.1568052070-1217284854.1568052070' class='link'> <img src="<?=$this->request->getAttribute('webroot') . 'assets/images/anydesk_icon.png'?>" alt="" style='max-width: 40%'>  </a>
										<a href='https://download.msp360.com/RemoteAssistantQuickSupport_v2.3.0.54_netv4.5.1.exe' class='p-l-20 link'> <img src="<?=$this->request->getAttribute('webroot') . 'assets/images/msp.png'?>" alt="" style='max-width: 40%'>  </a>
									</div> -->
									<h5> Deseja desativar a autenticação de dois fatores? <b style='font-weight: 500' class='desativarautenticacao'>Desativar!</b> </h5>
								</div>
							<?= $this->Form->end() ?>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
</section>
<!-- Modal Duas Etapas -->
<div class="modal fade none-border" id="modal-duasetapas" data-backdrop="static">
	<div class="modal-dialog modal-md ">
		<div class="modal-content" style='background-color: #01978b'>
			<div class="row p-20">
				<div class="col-12 codigo">
					<label class="control-label text-white"> Informe o código disponibilizado pelo Google Authenticator App </label>
					<?= $this->Form->control('codigo', ['id' => 'codigo', 'class' => 'form-control', 'placeholder' => 'Código', 'label' => false]) ?>
					<small class='codigoInvalido hide text-white'>O código não foi informado ou é inválido</small>
					<?= $this->Html->link('Cancelar', ['#'], ['class' => 'btn btn-fecha-modal btn-secondary btn-sm text-uppercase float-right m-t-10 btn-rounded']) ?>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('#idempresa').append("<option value='' disabled selected>Empresa</option>");
	});
	// Comece a usar 
		$('.comeceausar, .suporte, .recuperasenha, .desativarautenticacao').hover(function(){
			$(this).css('cursor', 'pointer');
		});

		$('.comeceausar').click(function(e){
			e.preventDefault();
			window.location = '<?= Router::url(['controller'=>'Users','action'=>'cadastrocliente']);?>';
		});
	// Recupera senha 
		window.iduser = '';
		$('.recuperasenha').click(function(){
			bootbox.prompt({
				title: "Insira o seu E-mail:", 
				callback: function(result){ 
					if(result != null) {
						window.location = '<?= Router::url(['controller' => 'Users', 'action' => 'resetPassword']);?>' + '/' + result;
					}
				}
			});
		});
		$('.desativarautenticacao').click(function(){
			bootbox.prompt({
				title: "Insira o seu E-mail:", 
				callback: function(result){ 
					console.log('testeaqui', result)
					if(result != null) {
						window.location = '<?= Router::url(['controller' => 'Users', 'action' => 'enviaEmailAutenticacaoSemLogin']);?>' + '/' + result;
					}
				}
			});
		});
	// Suporte 
		$('.suporte').click(function(){
			$('.suportes').show();
		});
	// Misc 
		$('.minsuculaOnly').hide()

		function SemMaisuclaEEspaco(e){
			var tecla=(window.event)?event.keyCode:e.which;  
			if((tecla >= 65 && tecla <= 90) || (tecla >= 192 && tecla <= 220 )) {
				$('.minsuculaOnly').show()
				return false;
			}
			$('.minsuculaOnly').hide()
		}
	// Duas etapas 
		window.duasEtapas = true;
		window.modalAberto = false;

		$('#username').change(function(){
			userLower = $(this).val().toLowerCase(); 
			$(this).val(userLower);
		});

		$('#username, #password').keyup(function(e){
			if(e.keyCode == 13) verificaduasetapas('login');
		});

		$('#codigo').keyup(function() {
			if($(this).val().length == 6) {
				$.ajax({
					url: "<?= Router::url(['controller'=>'Users','action'=>'verificacodigo']);?>/"+$('#username').val()+'/'+$('#codigo').val(),
					async: false,
					success: function(data) {
						if(data == 'sucesso') $('.signin-form').submit()
						else {
							$('#codigo').val('');
							$('.codigoInvalido').show();
						}
					},
					error: function(data) { 
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
		})  

		function verificaduasetapas(acao) {
			$.ajax({
				url: "<?= Router::url(['controller'=>'Users','action'=>'verificaloginduasetapas']);?>/"+$('#username').val(),
				async: false,
				success: function(data) {
					if(data == 'temcodigo') $('#modal-duasetapas').modal('toggle');
					else if(acao == 'login') $('.signin-form').submit();
				},
			});
		}
	//     
</script>