<!DOCTYPE HTML>
<html>
<head>
	<!-- Charset e propriedades -->
	<?= $this->Html->charset() ?>
	<?php use Cake\Routing\Router; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal PGM">
    <meta name="author" content="Grid Sistemas">

	<!-- Ícone -->
	<?= $this->Html->meta('/assets/images/favicon.ico?data=11/06/2021','/favicon.ico?data=11/06/2021',['type' => 'icon']); ?>

	<!-- Título -->
	<title><?= $title; ?></title>

	<!-- CSS -->
	<?= $this->Html->css("/assets/node_modules/clockpicker/dist/jquery-clockpicker.min") ?>
	<?= $this->Html->css("/assets/node_modules/morrisjs/morris") ?>
	<?= $this->Html->css("/assets/node_modules/toast-master/css/jquery.toast") ?>
	<?= $this->Html->css("/dist/css/style.min") ?>
	<?= $this->Html->css("/dist/css/popover") ?>
	<?= $this->Html->css("/assets/node_modules/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker") ?>
	<?= $this->Html->css("/dist/css/pages/stylish-tooltip") ?>
	<?= $this->Html->css("/dist/css/pages/tab-page") ?>
	<?= $this->Html->css("/dist/css/pages/floating-label.css") ?>
	<?= $this->Html->css("/assets/node_modules/datatables/datatables.min") ?>
	<?= $this->Html->css("/dist/css/pages/file-upload.css") ?>
	<?= $this->Html->css("/dist/css/dark-mode.css") ?>
	<?= $this->Html->css("/dist/css/css.css") ?>

	<!-- Timeline CSS -->
	<?= $this->Html->css("/assets/node_modules/horizontal-timeline/css/horizontal-timeline.css") ?>
    <!-- page css -->
	<?= $this->Html->css("/dist/css/pages/timeline-vertical-horizontal.css") ?>
	<!-- calendar -->
	<?= $this->Html->css("/assets/node_modules/calendar/dist/fullcalendar.css") ?>
	<!-- select multiple -->
	<?= $this->Html->css("/dist/css/pages/bootstrap-select.css") ?>
	<!-- fa iconpicker -->
	<?= $this->Html->css("/plugins/iconpicker/iconpicker.css") ?>
	<?= $this->Html->css('/plugins/bootstrap-iconpicker-1.10.0/dist/css/bootstrap-iconpicker.min') ?>
	<?= $this->Html->css('/plugins/bootstrap-iconpicker-1.10.0/dist/css/bootstrap-iconpicker.min') ?>

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
	<?= $this->Html->script("/js/custom-file-input") ?>
	<!-- masks -->
	<!-- <?= $this->Html->script("/dist/js/pages/mask.init.js") ?>
	<?= $this->Html->script("/dist/js/pages/mask.js") ?> -->
	<?= $this->Html->script('/plugins/jQuery-Mask-Plugin-master/src/jquery.mask.js') ?>
	<!--stickey kit -->
	<?= $this->Html->script("/assets/node_modules/sticky-kit-master/dist/sticky-kit.min.js") ?> 
	<?= $this->Html->script("/assets/node_modules/sparkline/jquery.sparkline.min.js") ?> 

	<!-- Scripts personalizados para as páginas -->
    <!-- Morris JavaScript -->
	<?= $this->Html->script("/assets/node_modules/raphael/raphael-min") ?>
	<?= $this->Html->script("/assets/node_modules/morrisjs/morris.min") ?>
	<?= $this->Html->script("/assets/node_modules/jquery-sparkline/jquery.sparkline.min") ?>
	<!--- Chartjs -->
	<?= $this->Html->script("/assets/node_modules/Chart.js/Chart.min") ?>
    <!-- Popup message jquery -->
	<?= $this->Html->script("/assets/node_modules/toast-master/js/jquery.toast") ?>
	<!--- Material Picker -->
	<?= $this->Html->script("/assets/node_modules/moment/moment-with-locales") ?>
	<?= $this->Html->script("/assets/node_modules/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker") ?>
	<!--- E-Charts -->
	<?= $this->Html->script("/assets/node_modules/echarts/echarts-all") ?>
	<!--- Data Tables -->
	<?= $this->Html->script("/assets/node_modules/datatables/datatables.min") ?>
	<!-- editor html -->
	<?= $this->Html->script('/plugins/tinymce/jquery.tinymce.min') ?>
	<?= $this->Html->script('/plugins/tinymce/tinymce.min') ?>
	<!-- Horizontal-timeline JavaScript -->
	<?= $this->Html->script("/assets/node_modules/horizontal-timeline/js/horizontal-timeline.js") ?>
	<!-- Calendar JavaScript -->
	<?= $this->Html->script("/assets/node_modules/calendar/jquery-ui.min") ?>
	<?= $this->Html->script("/assets/node_modules/moment/moment") ?>
	<?= $this->Html->script("/assets/node_modules/calendar/dist/fullcalendar") ?>
	<?= $this->Html->script("/assets/node_modules/calendar/dist/locale/pt-br.js") ?>
	<!-- select multiple-->
	<?= $this->Html->script("/dist/js/pages/bootstrap-select") ?>
	<!-- editor html -->
	<?= $this->Html->script('/plugins/clipboard.js-master/dist/clipboard') ?>
	<!-- color picker -->
	<?= $this->Html->script('/plugins/jscolor') ?>
	<?= $this->Html->script('/plugins/ColorLuminance') ?>
	<?= $this->Html->script('/plugins/iconpicker/iconpicker') ?>
	<!-- Autosuggest (?) -->
	<?= $this->Html->script('/plugins/Chrome-like-jQuery-Autocomplete-Autosuggest-Plugin-typeAhead/lib/jquery-typeahead.js') ?>
	<!-- fa iconpicker -->
	<?= $this->Html->script('/plugins/bootstrap-iconpicker-1.10.0/dist/js/bootstrap-iconpicker.bundle.min') ?>
	<!-- Bootbox - confirms -->
	<?= $this->Html->script('/plugins/bootbox/bootbox.min.js') ?>
	<?= $this->Html->script('/plugins/bootbox/bootbox.locales.min.js') ?>
	<!-- Validação Inscrição Estadual -->
	<?= $this->Html->script('/plugins/validaInscricaoEstadual.js') ?>
	<!-- Clockpicker -->
	<?= $this->Html->script("/assets/node_modules/clockpicker/dist/jquery-clockpicker.min.js") ?>
	<!-- JSGrid -->
	<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid.min.css" />
	<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid-theme.min.css" />
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid.min.js"></script>
	<!-- Máscara dinheiro  -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js"></script>
    <!-- Inclusão do Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>

	<!-- Leitura dos componentes -->
	<?= $this->fetch('meta'); ?>
	<?= $this->fetch('css'); ?>
	<?= $this->fetch('script'); ?>
</head>
<body class="fixed-layout skin-default-dark mini">
	<!--- Pre loader -->
	<div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label">PGM</p>
        </div>
    </div>
	<?= $this->element('deleteModal'); ?>
	<!-- Painel principal -->
	<div class="main-wrapper">
		<!-- Header -->
		<?php
			if ($role == 0) echo $this->element('header');
			else echo $this->element('headercli');
		?>
		<!-- Sidebar -->
		<?php
			if ($role == 0) echo $this->element('sidebar');
			else echo $this->element('sidebarcli');
		?>
		<div class="page-wrapper">
			<div class="container-fluid">
				<?php if (!($hideLayoutPageTitle ?? false)): ?>
				<div class="row page-titles" style='padding-bottom: 8px;'>
					<!-- Título -->
		            <div class="col-md-5 align-self-center">
						<h5 class="text-themecolor m-b-0"><?= $title ?></h5>
					</div>
					<!-- Breadcumbs -->
		            <div class="col-md-7 align-self-center text-right">
		                <div class="d-flex justify-content-end align-items-center">
							<?= $this->Breadcrumbs->render(); ?>
		                </div>
		            </div>
	        	</div>
				<?php endif; ?>
				<?= $this->element('content'); ?>
			</div>
		</div>
		<?= $this->element('footer'); ?>
	</div>
	<div class='hide' id='notificacao'></div>
</body>
</html>
<script>
	$(function() {
		// Realiza a alteração do menu lateral
		$(".sidebartoggler").on('click', function () {
			verificaSidebar()
	    });

		// Verifica se é para ocultar o logo da sidebar
		function verificaSidebar() {
			if ($('body').hasClass('mini-sidebar')) {
				$(".mini-itens").css('display', '');
				$(".mini-itens").css('display', 'none');
				$('.logo').addClass('imagem-sidebar-mini');
				$('.logo').removeClass('imagem-sidebar-expandida');
			}
			else {
				$("#mini-logout").css('display', 'none');
				$("#mini-logout").css('display', '');
				$('.logo').addClass('imagem-sidebar-expandida');
				$('.logo').removeClass('imagem-sidebar-mini');
			} 
		};
		verificaSidebar();
	});

	// CPF e CNPJ 
		function validarCNPJ(cnpj) {
			cnpj = cnpj.replace(/[^\d]+/g,'');

			if(cnpj == '') return false;

			if (cnpj.length != 14)
				return false;

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
				return false; // LINHA 21

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
			if (resultado != digitos.charAt(0))
				return false;

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
				return false; // LINHA 50
			}

			return true; // LINHA 51
		}

		$('#cnpj').change(function(){
			if(validarCNPJ($(this).val()) == false) bootbox.alert('CNPJ inválido!');
		})

		$('#cpf').change(function(){
			var regex = /[.-\s]/g;
			var result = $(this).val().replace(regex, '');
			if(validaCPF( result ) == false) bootbox.alert('CPF inválido!');
		})

		function validaCPF(cpf) {
			var numeros, digitos, soma, i, resultado, digitos_iguais;
			digitos_iguais = 1;
			if (cpf.length < 11)
				return false;
			for (i = 0; i < cpf.length - 1; i++)
				if (cpf.charAt(i) != cpf.charAt(i + 1)) {
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

	// Page length 
		function pagelength(len){
			var url = "<?= $this->Url->build(['controller' => 'Users', 'action' => 'pagelength']) ?>";
			url = url + '/' + len;
			$.ajax({
				type:"POST",
				url: url,
				data: {"len": len},
				error: function (tab) {
					bootbox.alert('Um erro ocorreu ao salvar sua preferência! Tente novamente.');
				}
			});
		}
	// Misc 
		function numberToReal(numero) {
			if(!isNaN(numero)){
				// var numero = numero.toFixed(2).split('.');
				var numero = numero.toFixed(2).split('.');
				numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
				return numero.join(',');
			}
		}

		$(function(){
			$(".mascaramonetaria").maskMoney({
				allowNegative: true,
				thousands: '.',
				decimal: ','
			});
		});

		$('.datepicker').bootstrapMaterialDatePicker({ format : 'DD/MM/YYYY', lang : 'pt-br', time : false, switchOnClick : true, nowButton : true, cancelText : 'Cancelar' , 'setDate' : 'currentDate', nowText : 'Hoje'});	

	// Notificações 
		Notification.requestPermission();

		function notificacaoTicket(){
			$.ajax({ 
				url: "<?= Router::url(['controller' => 'Notificacoes', 'action' => 'notificacoes']);?>",
				success:function(data){ $('#notificacao').html(data); },
			})
		}
		
		notificacaoTicket();

		var count = 1;
		setInterval(
			function() { //verifica a cada 1 minuto
			count++;
			if (count == 60) {
				notificacaoTicket();
				count = 0;
			}
		}, 1000);
	// Empresa 
		function handleEmpresaSidebarChange(e) {
			// Sempre lê do <select> real (bootstrap-select pode disparar o evento com outro target).
			var empresa = $('#empresaSidebar').val();
			if (!empresa) {
				bootbox.alert('Falha ao trocar a empresa: valor inválido no seletor.');
				return;
			}

			$.ajax({
				data: {empresa: empresa},
				type: 'POST',
				url: "<?= Router::url(['controller' => 'Empresas', 'action' => 'alteraempresa']);?>",
				success: function(data) {
					location.reload();
				},
				error: function(xhr) {
					var msg = (xhr && xhr.responseText) ? String(xhr.responseText).trim() : '';
					bootbox.alert('Falha ao trocar a empresa. HTTP: ' + xhr.status + (msg ? ('\\n' + msg) : ''));
				},
			});
		}

		// Suporta tanto select normal (change) quanto bootstrap-select (changed.bs.select).
		$('#empresaSidebar').on('change', handleEmpresaSidebarChange);
		$('#empresaSidebar').on('changed.bs.select', handleEmpresaSidebarChange);
	// 

</script>
