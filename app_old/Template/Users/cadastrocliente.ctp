<?php use Cake\Routing\Router; ?>
<style>
	.btn-disabled { background: #eee !important; /*Simular campo inativo*/ }
	select[readonly] {
		background: #eee; /*Simular campo inativo - Sugestão @GabrielRodrigues*/
		pointer-events: none;
		touch-action: none;
	}
	.btn-voltarprologin{ width: 160px !important; }
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
	.form-control[readonly] {
		color: white !important; 
		background-color: #068a80 !important; 
	}
	.form-control::placeholder { color: #ebebeb !important; }
	.ftco-section { padding: 3em 0; }
	.btn-login {
		color: #fff;
		background-color: #098479 !important;
		border-color: #098479 !important;
	}
	.btn-login:hover { 
		color: white !important; 
		background-color: #00ab9e !important; 
	}
</style>
<section id="wrapper" class="step-register" style="background-image:url(<?=$this->request->getAttribute('webroot') . '/assets/images/background/login-register.jpg'?>);" >
	<div class="register-box">
		<section class="ftco-section">
			<div class="row justify-content-center">
				<div class="col-md-6 text-center mb-5">
					<h2 class="heading-section text-light">Cadastre-se</h2>
				</div>
			</div>
			<?= $this->Form->create($user, ['id' => "msform", 'method' => 'post', 'class' => '', 'autocomplete' => "nope"]) ?>
				<div class="row justify-content-center">
					<div class="col-md-6 col-lg-6">
						<div class="login-wrap p-0">
							<div class="form-group ">
								<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome completo', 'required' => true]) ?>
							</div>
							<div class="form-group">
								<?= $this->Form->control('tipo', ['empty' => 'Tipo do cliente', 'options' => C_ClientesTipoCadastroCliente, 'required' => true, 'class' => 'form-control', 'label' => false, ]) ?>
							</div>
							<div class="form-group">
								<?= $this->Form->control('password', ['a utocomplete' => 'new-password', 'class' => 'form-control ', 'label' => false, 'required' => true, 'placeholder' => 'Senha']) ?>
							</div>
							<div class="form-group">
								<?= $this->Form->control('confirm_password', ['id' => 'confirmasenha', 'class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password',  'placeholder' => 'Repetir senha']) ?>
							</div>
						</div>
					</div>
					<div class="col-md-6 col-lg-6">
						<div class="login-wrap p-0">
							<div class="form-group ">
								<?= $this->Form->control('email1', ['class' => 'form-control', 'label' => false, 'placeholder' => 'E-mail', 'required' => true]) ?>
							</div>
							<div class="form-group ">
								<?= $this->Form->control('setor', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Setor', 'required' => false]) ?>
							</div>
							<div class="pessoaJuridica">
								<div class="row padding-20">
									<div class="col-12">
										<div class="form-group">
											<?= $this->Form->control('cnpj', ['class' => 'form-control', 'label' => false, 'placeholder' => 'CNPJ da Empresa']) ?>
										</div>
									</div>
								</div>
								<div class="row padding-20">
									<div class="col-12">
										<div class="form-group">
											<?= $this->Form->control('razaosocial', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome da Empresa', 'readonly' => true]) ?>
										</div>
									</div>
								</div>
							</div>
							<div class="pessoaFisica">
								<div class="form-group">
									<?= $this->Form->control('cpfcliente', ['class' => 'form-control', 'label' => false, 'placeholder' => 'CPF do cliente']) ?>
								</div>
								<div class="form-group">
									<?= $this->Form->control('nomecliente', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome do cliente', 'readonly' => true]) ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row justify-content-center">
					<div class="form-group">
						<?= $this->Form->control('cliente', ['type' => 'hidden', 'label' => false, 'required' => true]) ?>
						<button type='submit' class="btn-login btn btn-lg btn-cadastrar btn-rounded px-3">Cadastre-se</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</section>
<script>
	jQuery(function($) {
		$("#cnpj").mask("99.999.999/9999-99");
		$("#cep").mask("99999-999");
		$("#cpf, #cpfcliente").mask("999.999.999-99");
		$("#celular").mask("(99) 99999-9999");
		$("#telefone").mask("(99) 9999-9999");
	});

	function SemMaisuclaEEspaco(e){
		var tecla=(window.event)?event.keyCode:e.which;  
		if((tecla >= 65 && tecla <= 90)) return false;
		if((tecla >= 192 && tecla <= 220)) return false;
		else if (tecla == 8 || tecla == 0) return true;
		else if (tecla == 32)  return false;
	}

	$('.etapa2, .btn-salvar').hide();

	window.iDisablebotao = 0;

	$('#password, #confirmasenha').change(function(){
		if( $('#password').val() !=  '' && $('#confirmasenha').val() != ""){
			senha = $('#password').val();
			confirmasenha = $('#confirmasenha').val();
			if( senha == confirmasenha){
				if(senha.length < 6){
					bootbox.alert('<p class="text-center" style="font-size: 1.2rem">A  senha deve conter pelo menos 6 caracteres.</p>');
					deuerrado('sim', 'senha');
				}else{
					var hasNumber = /\d/;
					if(!hasNumber.test(senha) || !senha.match(/[a-z]/i) ){
						bootbox.alert('<p class="text-center" style="font-size: 1.2rem">A senha deve conter pelo menos 1 letra e 1 número.</p>');
						deuerrado('sim', 'senha');
					}
					else deuerrado('nao', 'senha');
				}
			} else {
				bootbox.alert('<p class="text-center" style="font-size: 1.2rem">As senhas nao conincidem.</p>');
				deuerrado('sim', 'senha');
			}
		}
	})

	$('#cnpj').change(function() {
		if($('#cnpj').val() != ''){
			$.ajax({
				url: "<?= Router::url(['controller'=>'Users','action'=>'verificacnpjcliente']);?>",
				type: 'POST',
				dataType: 'JSON',
				data: {cnpj: $('#cnpj').val()},
				success: function(data){
					$('#cliente').val(data.IdCliente);
					$('#razaosocial').val(data.RazaoSocial);

					window.idcliente = data;
					deuerrado('nao', 'cnpj');
				},
				error: function(data) {
					if(data.Mensagem == 'jatem'){
						bootbox.alert('<p class="text-center" style="font-size: 1.2rem">Já existe um usuário para este cliente.</p>');
						deuerrado('sim', 'cpf');
					} else if(data.Mensagem == 'inativo'){
						bootbox.alert('<p class="text-center" style="font-size: 1.2rem">O cliente encontra-se inativado no banco de dados.</p>');
						deuerrado('sim', 'cpf');
					}
				}
			});
		}
	});

	$('#cpfcliente').change(function() {
		if($('#cpfcliente').val() != ''){
			$.ajax({
				url: "<?= Router::url(['controller'=>'Users','action'=>'verificacpfcliente']);?>",
				type: 'POST',
				dataType: 'JSON',
				data: {cpf: $('#cpfcliente').val()},
				success: function(data){
					$('#cliente').val(data.IdCliente);
					$('#nomecliente').val(data.NomeCliente);
					
					window.idcliente = data;
					deuerrado('nao', 'cpf');
				},
				error: function(data) {
					if(data.Mensagem == 'jatem'){
						bootbox.alert('<p class="text-center" style="font-size: 1.2rem">Já existe um usuário para este cliente.</p>');
						deuerrado('sim', 'cpf');
					} else if(data.Mensagem == 'inativo'){
						bootbox.alert('<p class="text-center" style="font-size: 1.2rem">O cliente encontra-se inativado no banco de dados.</p>');
						deuerrado('sim', 'cpf');
					}
				}
			});
		}
	});

	$('#email1').change(function() {
		$.ajax({
			url: "<?= Router::url(['controller'=>'Users','action'=>'verificalogincadastro']);?>/" + $('#email1').val(),
			success: function(data){
				if(data == 'podecadastrar') deuerrado('nao', 'email');
				else{
					bootbox.alert('<p class="text-center" style="font-size: 1.2rem">Esse usuário já existe e não é possível criar outro com o mesmo nome.</p>');
					deuerrado('sim', 'email');
				}
			},
		});
	});

	$('#btn-avancar').click(function(){
		$('#email1').val($('$email1').val());
		deuerrado('vamove', 'botao');
	});

	$('#username').change(function(){
		issominusculo = $(this).val().toLowerCase(); 
		$(this).val(issominusculo);
	});

	$('.pessoaFisica').hide();
	$('.pessoaJuridica').hide();

	$("#tipo").change(function(){
		if($(this).val() == 2){
			$('.pessoaFisica').hide();
			$('.pessoaJuridica').fadeIn();
		} else {
			$('.pessoaJuridica').hide();
			$('.pessoaFisica').fadeIn();
		}
	})

	$('.btn-cadastrar').click(function(){
		$('.btn-cadastrar').prop('disabled', 'disabled');
		$('.btn-cadastrar').addClass('btn-disabled');
		$('form').submit();
	});

</script>
