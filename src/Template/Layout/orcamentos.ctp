<?php
use Cake\Routing\Router;
$pgmOrcTheme = (($skin ?? '') === 'skin-pgm-light') ? 'light' : 'dark';
$pgmOrcThemeClass = ($pgmOrcTheme === 'light') ? 'pgm-theme-light' : '';
?>
<!DOCTYPE HTML>
<html lang="pt-BR" data-pgm-theme="<?= h($pgmOrcTheme) ?>">
<head>
	<!-- Charset e propriedades -->
	<?= $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Portal PGM">
	<meta name="author" content="Grid Sistemas">

	<!-- Ícone -->
	<?= $this->Html->meta('/assets/images/favicon.ico?data=11/06/2021','/favicon.ico?data=11/06/2021',['type' => 'icon']); ?>
	<!-- Título -->
	<title><?= $title; ?></title>
	<!-- CSS -->
	<?= $this->Html->css("/dist/css/style.min") ?>
	<?= $this->Html->css("/dist/css/pages/stylish-tooltip") ?>
	<?= $this->Html->css("/assets/node_modules/datatables/datatables.min") ?>
	<?= $this->Html->css("/dist/css/css.css") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-theme-tokens") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-app-shell.css") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-advanced-module") ?>
	<?php if ($pgmOrcTheme === 'light') : ?>
	<?= $this->Html->css("/dist/css/pages/pgm-theme-light") ?>
	<?php endif; ?>
	<?= $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']) ?>
	<?= $this->element('pgm_premium_css', ['name' => 'pgm-action-buttons']) ?>

	<!-- Timeline CSS -->
	<?= $this->Html->css("/assets/node_modules/horizontal-timeline/css/horizontal-timeline.css") ?>
	<!-- select multiple -->
	<?= $this->Html->css("/dist/css/pages/bootstrap-select.css") ?>
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
	<?= $this->Html->script('/plugins/jQuery-Mask-Plugin-master/src/jquery.mask.js') ?>
	<!--stickey kit -->
	<?= $this->Html->script("/assets/node_modules/sticky-kit-master/dist/sticky-kit.min.js") ?> 
	<?= $this->Html->script("/assets/node_modules/sparkline/jquery.sparkline.min.js") ?> 

	<!-- Scripts personalizados para as páginas -->
	<!--- Data Tables -->
	<?= $this->Html->script("/assets/node_modules/datatables/datatables.min") ?>
	<!-- select multiple-->
	<?= $this->Html->script("/dist/js/pages/bootstrap-select") ?>
	<!-- Bootbox - confirms -->
	<?= $this->Html->script('/plugins/bootbox/bootbox.min.js') ?>
	<?= $this->Html->script('/plugins/bootbox/bootbox.locales.min.js') ?>
	<!-- Leitura dos componentes -->
	<?= $this->fetch('meta'); ?>
	<?= $this->fetch('css'); ?>
	<?= $this->fetch('script'); ?>
</head>
<body class="fixed-layout skin-green mini layout-no-topbar <?= h($pgmOrcThemeClass) ?>">
	<!--- Pre loader -->
	<div class="preloader">
		<div class="loader">
			<div class="loader__figure"></div>
			<p class="loader__label">PGM</p>
		</div>
	</div>
	<!-- Painel principal (shell alinhado ao default; sem sidebar neste layout) -->
	<div class="main-wrapper pgm-app-shell" id="main-wrapper">
		<div class="pgm-app-main pgm-app-main--no-sidebar pgm-orc-app-main">
			<div class="page-wrapper pgm-orc-page-wrapper">
				<div class="container-fluid pgm-app-container">
					<?= $this->element('content'); ?>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
<script>
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
			if ($.fn.maskMoney) {
				$(".mascaramonetaria").maskMoney({
					allowNegative: true,
					thousands: '.',
					decimal: ','
				});
				return;
			}
			if ($.fn.mask) {
				$(".mascaramonetaria").mask('#.##0,00', { reverse: true });
			}
		});
	// 
</script>
