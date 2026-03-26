<!DOCTYPE HTML>
<html>
<head>
	<!-- Charset e propriedades -->
	<?= $this->Html->charset() ?>
	<?= $this->Html->meta(['link' => $this->request->getAttribute('webroot') . 'manifest.json', 'rel' => 'manifest']); ?>

	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Portal PGM">
	<meta name="author" content="Grid Sistemas">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="-1">

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
	<?= $this->element('pgm_premium_css', ['name' => 'pgm-action-buttons']) ?>

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
	

	<!-- Leitura dos componentes -->
	<?= $this->fetch('meta'); ?>
	<?= $this->fetch('css'); ?>
	<?= $this->fetch('script'); ?>
</head>
<body class="bg-white">
	<!-- Painel principal -->
	<div class="main-wrapper">
		<div class="container-fluid">
			<?= $this->element('content'); ?>
		</div>
	</div>
</body>
</html>

<script>

</script>
