<?php
use Cake\Routing\Router;
$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', ['fullBase' => true]);
echo $this->Html->css('dist/css/cadastro-cliente-erp');
$this->end();
?>
<style>
	.cadastro-erp-wrap .btn-disabled { background: #ccc !important; cursor: not-allowed; }
	.cadastro-erp-wrap select[readonly] {
		background: #f5f5f5 !important;
		pointer-events: none;
		touch-action: none;
	}
</style>
<section id="wrapper" class="step-register cadastro-erp-wrap">
	<div class="cadastro-erp-card">
		<h1 class="cadastro-erp-title">Cadastre-se</h1>
		<?= $this->Form->create($user, ['id' => "msform", 'method' => 'post', 'class' => '', 'autocomplete' => "nope"]) ?>
			<div class="row justify-content-center">
				<div class="col-md-6 col-lg-6">
					<div class="login-wrap p-0">
						<div class="form-group ">
							<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome completo', 'required' => true]) ?>
						</div>
						<div class="form-group">
							<?= $this->Form->control('idempresa_cadastro', ['empty' => 'Empresa (PGM/Master)', 'options' => $empresasCadastro ?? [], 'required' => true, 'class' => 'form-control', 'label' => false]) ?>
						</div>
						<div class="form-group">
							<?= $this->Form->control('tipo', ['empty' => 'Tipo do cliente', 'options' => C_ClientesTipoCadastroCliente, 'required' => true, 'class' => 'form-control', 'label' => false, ]) ?>
						</div>
						<div class="form-group">
							<?= $this->Form->control('fone', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Telefone', 'required' => true]) ?>
						</div>
						<div class="form-group">
							<?= $this->Form->control('password', ['autocomplete' => 'new-password', 'class' => 'form-control ', 'label' => false, 'required' => true, 'placeholder' => 'Senha']) ?>
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
			<div class="cadastro-erp-voltar-login text-center">
				Já tem conta? <?= $this->Html->link('Fazer login', ['controller' => 'Users', 'action' => 'login'], ['class' => 'link-voltar-login']) ?>
			</div>
		<?= $this->Form->end() ?>
	</div>
</section>
<script>
	jQuery(function($) {
		$("#cnpj").mask("99.999.999/9999-99");
		$("#cep").mask("99999-999");
		$("#cpf, #cpfcliente").mask("999.999.999-99");
		$("#fone").mask("(99) 9999-9999");
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
					bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">A  senha deve conter pelo menos 6 caracteres.</p>');
					deuerrado('sim', 'senha');
				}else{
					var hasNumber = /\d/;
					if(!hasNumber.test(senha) || !senha.match(/[a-z]/i) ){
						bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">A senha deve conter pelo menos 1 letra e 1 número.</p>');
						deuerrado('sim', 'senha');
					}
					else deuerrado('nao', 'senha');
				}
			} else {
				bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">As senhas nao conincidem.</p>');
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
				data: {cnpj: $('#cnpj').val(), idempresa: $('#idempresa-cadastro').val()},
				success: function(data){
					$('#cliente').val(data.IdCliente);
					$('#razaosocial').val(data.RazaoSocial);
					$('#razaosocial').prop('readonly', true);

					window.idcliente = data;
					deuerrado('nao', 'cnpj');
				},
				error: function(xhr) {
					var resp = xhr && xhr.responseJSON ? xhr.responseJSON : {};
					if(resp.Mensagem == 'jatem'){
						bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">Já existe um usuário para este cliente.</p>');
						deuerrado('sim', 'cpf');
					} else if(resp.Mensagem == 'inativo'){
						bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">O cliente encontra-se inativado no banco de dados.</p>');
						deuerrado('sim', 'cpf');
					} else if(resp.Mensagem == 'naopode'){
						// Auto-cadastro: permite digitar o nome da empresa e seguir.
						$('#cliente').val('');
						$('#razaosocial').val('');
						$('#razaosocial').prop('readonly', false);
						bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">CNPJ não encontrado. Informe o Nome da Empresa para criar o cadastro automaticamente.</p>');
						deuerrado('nao', 'cnpj');
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
				data: {cpf: $('#cpfcliente').val(), idempresa: $('#idempresa-cadastro').val()},
				success: function(data){
					$('#cliente').val(data.IdCliente);
					$('#nomecliente').val(data.NomeCliente);
					$('#nomecliente').prop('readonly', true);
					
					window.idcliente = data;
					deuerrado('nao', 'cpf');
				},
				error: function(xhr) {
					var resp = xhr && xhr.responseJSON ? xhr.responseJSON : {};
					if(resp.Mensagem == 'jatem'){
						bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">Já existe um usuário para este cliente.</p>');
						deuerrado('sim', 'cpf');
					} else if(resp.Mensagem == 'inativo'){
						bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">O cliente encontra-se inativado no banco de dados.</p>');
						deuerrado('sim', 'cpf');
					} else if(resp.Mensagem == 'naopode'){
						// Auto-cadastro: permite digitar o nome do cliente e seguir.
						$('#cliente').val('');
						$('#nomecliente').val('');
						$('#nomecliente').prop('readonly', false);
						bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">CPF não encontrado. Informe o Nome do cliente para criar o cadastro automaticamente.</p>');
						deuerrado('nao', 'cpf');
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
					bootbox.alert('<p class="text-center pgm-bootbox-msg-lg">Esse usuário já existe e não é possível criar outro com o mesmo nome.</p>');
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
