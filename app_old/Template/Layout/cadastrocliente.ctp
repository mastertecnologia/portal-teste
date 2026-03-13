<!DOCTYPE HTML>
<html>
<head>
	<!-- Charset e propriedades -->
	<?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Cadastro de Cliente">
    <meta name="author" content="Grid Sistemas">

	<!-- Ícone -->
	<?= $this->Html->meta('/assets/images/favicon.ico?data=11/06/2021','/favicon.ico?data=11/06/2021',['type' => 'icon']); ?>

	<!-- Título -->
	<title>Cadastro de Cliente</title>

    <!-- CSS -->
    <?= $this->Html->css("/assets/node_modules/clockpicker/dist/jquery-clockpicker.min") ?>
	<?= $this->Html->css("/assets/node_modules/morrisjs/morris") ?>
	<?= $this->Html->css("/assets/node_modules/toast-master/css/jquery.toast") ?>
	<?= $this->Html->css("/dist/css/style.min") ?>
	<?= $this->Html->css("/dist/css/css.css") ?>
	<?= $this->Html->css("/dist/css/pages/bootstrap-select.css") ?>
	<?= $this->Html->css("/assets/node_modules/register-steps/steps.css") ?>
	<?= $this->Html->css("/dist/css/pages/register3.css") ?>

	<!--- Scripts -->
	<?= $this->Html->script("/assets/node_modules/jquery/jquery-3.2.1.min") ?>
    <!-- Bootstrap popper Core JavaScript -->
	<?= $this->Html->script("/assets/node_modules/popper/popper.min") ?>
	<?= $this->Html->script("/assets/node_modules/bootstrap/dist/js/bootstrap.min") ?>
    <!-- Slimscrollbar scrollbar JavaScript -->
	<?= $this->Html->script("/dist/js/perfect-scrollbar.jquery.min") ?>
    <!-- Wave Effects -->
	<?= $this->Html->script("/dist/js/waves") ?>
    <!-- Menu sidebar -->
	<?= $this->Html->script("/dist/js/sidebarmenu") ?>
    <!-- Custom JavaScript -->
	<?= $this->Html->script("/dist/js/custom") ?>
	<?= $this->Html->script("/assets/node_modules/jquery-sparkline/jquery.sparkline.min") ?> 
	<?= $this->Html->script("/dist/js/pages/jasny-bootstrap.js") ?>
	<?= $this->Html->script("custom-file-input") ?>
	<!-- masks -->
	<?= $this->Html->script('/plugins/jQuery-Mask-Plugin-master/src/jquery.mask.js') ?>

	<!-- Scripts personalizados para as páginas -->
   
	<!--- Material Picker -->
	<?= $this->Html->script("/assets/node_modules/moment/moment-with-locales") ?>
	<?= $this->Html->script("/assets/node_modules/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker") ?>
	<!-- select multiple-->
	<?= $this->Html->script("/dist/js/pages/bootstrap-select") ?>
	<!-- Autosuggest (?) -->
	<?= $this->Html->script('/plugins/Chrome-like-jQuery-Autocomplete-Autosuggest-Plugin-typeAhead/lib/jquery-typeahead.js') ?>
	<!-- Bootbox - confirms -->
	<?= $this->Html->script('/plugins/bootbox/bootbox.min.js') ?>
	<?= $this->Html->script('/plugins/bootbox/bootbox.locales.min.js') ?>
	<!-- Clockpicker -->
	<?= $this->Html->script("/assets/node_modules/clockpicker/dist/jquery-clockpicker.min.js") ?>
	<!-- Register -->
	<?= $this->Html->script("/assets/node_modules/register-steps/register-init.js") ?>
	<?= $this->Html->script("/assets/node_modules/register-steps/jquery.easing.min.js") ?>
	<!-- Leitura dos componentes -->
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
	
</head>
<body>
	<!-- Plugins -->
	<?= $this->Html->script('/assets/node_modules/jquery/jquery-3.2.1.min'); ?>
	<?= $this->Html->script('/assets/node_modules/popper/popper.min'); ?>
	<?= $this->Html->script('/assets/node_modules/bootstrap/dist/js/bootstrap.min'); ?>
	<!-- Custom JavaScript -->
	<?= $this->Html->script("/dist/js/custom") ?>

	<!-- Pre laoder -->
	<div class="preloader">
		<div class="loader">
			<div class="loader__figure"></div>
			<p class="loader__label">PGM</p>
		</div>
	</div>

	<!-- Conteúdo -->
	<?= $this->fetch('content') ?>
</body>

</html>
<script>
	function validarCNPJ(cnpj) {
		cnpj = cnpj.replace(/[^\d]+/g,'');

		if(cnpj == '') {
			deuerrado('sim', 'cnpj');
			return false;
		}

		if (cnpj.length != 14) {
			deuerrado('sim', 'cnpj');
			return false;
		}

		// LINHA 10 - Elimina CNPJs invalidos conhecidos
		if (cnpj == "00000000000000" || 
			cnpj == "11111111111111" || 
			cnpj == "22222222222222" || 
			cnpj == "33333333333333" || 
			cnpj == "44444444444444" || 
			cnpj == "55555555555555" || 
			cnpj == "66666666666666" || 
			cnpj == "77777777777777" || 
			cnpj == "88888888888888" || 
			cnpj == "99999999999999")
			{
				deuerrado('sim', 'cnpj');
				return false;
			} // LINHA 21

		// Valida DVs LINHA 23 -
		tamanho = cnpj.length - 2
		numeros = cnpj.substring(0,tamanho);
		digitos = cnpj.substring(tamanho);
		soma = 0;
		pos = tamanho - 7;
		for (i = tamanho; i >= 1; i--) {
		soma += numeros.charAt(tamanho - i) * pos--;
		if (pos < 2)
				pos = 9;
		}
		resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
		if (resultado != digitos.charAt(0)) {
			deuerrado('sim', 'cnpj');
			return false;
		}


		tamanho = tamanho + 1;
		numeros = cnpj.substring(0,tamanho);
		soma = 0;
		pos = tamanho - 7;
		for (i = tamanho; i >= 1; i--) {
		soma += numeros.charAt(tamanho - i) * pos--;
		if (pos < 2)
				pos = 9;
		}
		resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
		if (resultado != digitos.charAt(1)){ 
			deuerrado('sim', 'cnpj');
			return false;
		}

		deuerrado('nao');
		return true; // LINHA 51

	}

	$('#cnpj').change(function(){
		if(validarCNPJ($(this).val()) == false) bootbox.alert('CNPJ inválido!');
	})

	$('#cpf, #cpfcliente').change(function(){
		if(validaCPF($(this).val()) == false) bootbox.alert('CPF inválido!');
	})

	function validaCPF(cpf){
		var regex = /[.-\s]/g;
		var cpf = cpf.replace(regex, '');

		var numeros, digitos, soma, i, resultado, digitos_iguais;
		digitos_iguais = 1;
		if (cpf.length < 11)
			return false;
		for (i = 0; i < cpf.length - 1; i++)
			if (cpf.charAt(i) != cpf.charAt(i + 1))
				{
					digitos_iguais = 0;
					break;
				}
		if (!digitos_iguais)
			{
			numeros = cpf.substring(0,9);
			digitos = cpf.substring(9);
			soma = 0;
			for (i = 10; i > 1; i--)
					soma += numeros.charAt(10 - i) * i;
			resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
			if (resultado != digitos.charAt(0))
					return false;
			numeros = cpf.substring(0,10);
			soma = 0;
			for (i = 11; i > 1; i--)
					soma += numeros.charAt(11 - i) * i;
			resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
			if (resultado != digitos.charAt(1))
					return false;
			return true;
			}
		else
			return false;
	}

	window.login = 'deuerrado';
	window.senha = 'deuerrado';
	window.cnpj  = 'deuerrado';
	window.cpf  = 'deuerrado';
	window.email  = 'deuerrado';
	
	function deuerrado(deuerrado, campo){
		// if(campo == 'login' && deuerrado == 'sim') window.login = 'deuerrado';
		// else if(campo == 'login' && deuerrado == 'nao') window.login = 'certo';
		if(campo == 'senha' && deuerrado == 'sim') window.senha = 'deuerrado';
		else if(campo == 'senha' && deuerrado == 'nao') window.senha = 'certo';
		if(campo == 'cnpj'  && deuerrado == 'sim') window.cnpj  = 'deuerrado';
		else if(campo == 'cnpj' && deuerrado == 'nao') window.cnpj = 'certo';
		if(campo == 'cpf'  && deuerrado == 'sim') window.cpf  = 'deuerrado';
		else if(campo == 'cpf' && deuerrado == 'nao') window.cpf = 'certo';
		if(campo == 'email'  && deuerrado == 'sim') window.email  = 'deuerrado';
		else if(campo == 'email' && deuerrado == 'nao') window.email = 'certo';

		if(window.senha == 'deuerrado' || window.email == 'deuerrado' || (window.cnpj == 'deuerrado' && window.cpf == 'deuerrado')){
			$('.btn-cadastrar').prop('disabled', 'disabled');
			$('.btn-cadastrar').addClass('btn-disabled');
		}else{
			$('.btn-cadastrar').prop('disabled', false);
			$('.btn-cadastrar').removeClass('btn-disabled');
		}
	};
</script>