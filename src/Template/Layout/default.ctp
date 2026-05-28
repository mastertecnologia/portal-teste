<?php
use App\Utility\PgmAppUrlBase;
use Cake\Routing\Router;

$pgmReactSidebar = (bool)\Cake\Core\Configure::read('PgmSidebar.react_enabled')
	&& !empty($iduser ?? null);
/** Web path do bundle (APP_BASE, pedido Cake ou SCRIPT_NAME — ver PgmAppUrlBase). `?v=` evita sidebar-app.js antigo em cache agressivo. */
$__pgmSbJsFs = ROOT . DS . 'public' . DS . 'js' . DS . 'pgm-sidebar-react' . DS . 'sidebar-app.js';
$__pgmSbCssFs = ROOT . DS . 'public' . DS . 'js' . DS . 'pgm-sidebar-react' . DS . 'sidebar-assets.css';
$pgmSidebarReactAssetV = is_file($__pgmSbJsFs) ? (string)filemtime($__pgmSbJsFs) : (string)time();
$pgmSidebarReactCssV = is_file($__pgmSbCssFs) ? (string)filemtime($__pgmSbCssFs) : (string)$pgmSidebarReactAssetV;
$__pgmSbWebBase = PgmAppUrlBase::path($this->request);
$pgmSidebarReactJs = $__pgmSbWebBase . '/js/pgm-sidebar-react/sidebar-app.js?v=' . rawurlencode($pgmSidebarReactAssetV);
/** CSS extraído pelo Vite (`import` nos .jsx); tem de ser ligado explicitamente no layout. */
$pgmSidebarReactCss = $__pgmSbWebBase . '/js/pgm-sidebar-react/sidebar-assets.css?v=' . rawurlencode($pgmSidebarReactCssV);
?>
<!DOCTYPE HTML>
<html lang="pt-BR" data-pgm-theme="light">
<head>
	<!-- Charset e propriedades -->
	<?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<?php
	$csrf = $this->request->getAttribute('csrfToken');
	if (!$csrf && method_exists($this->request, 'getParam')) {
		$csrf = $this->request->getParam('_csrfToken');
	}
	if ($csrf): ?>
	<meta name="csrfToken" content="<?= h($csrf) ?>">
	<?php endif; ?>
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
	<?= $this->Html->css("/dist/css/css.css") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-theme-tokens") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-components-base") ?>
	<?= $this->Html->css("/dist/css/pages/layout-sidebar-shell.css?v=" . time()) ?>
	<?php if (empty($disablePgmAppShellPremium)) : ?>
	<?= $this->Html->css("/dist/css/pages/pgm-app-shell-premium.css?v=12") ?>
	<?php endif; ?>
	<?php if (!empty($pgmLoadErpPrototypeAssets)) : ?>
	<?= $this->element('ErpPrototype/head_assets', ['includeServicedeskPrototypeCss' => !empty($loadServicedeskPrototypeCss)]) ?>
	<?php endif; ?>
	<?= $this->Html->css("/dist/css/pages/pgm-sidebar-premium.css?v=" . time()) ?>
	<?= $this->element('pgm_premium_css', ['name' => 'produtos-premium']) ?>
	<?php if ($pgmReactSidebar) : ?>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous" />
	<link rel="stylesheet" href="<?= h($pgmSidebarReactCss) ?>" crossorigin="anonymous" />
	<?php endif; ?>
	<?= $this->Html->css("/dist/css/pages/pgm-advanced-module") ?>
	<?php $pgmPortalClient = isset($role) && (int)$role !== 0; ?>
	<?php if (!empty($pgmPortalClient)): ?>
	<?= $this->Html->css("/dist/css/pages/pgm-client-premium") ?>
	<?php endif; ?>
	<?= $this->element('pgm_premium_css', ['name' => 'pgm-action-buttons']) ?>

	<!-- Timeline CSS -->
	<?= $this->Html->css("/assets/node_modules/horizontal-timeline/css/horizontal-timeline.css") ?>
    <!-- page css -->
	<?= $this->Html->css("/dist/css/pages/timeline-vertical-horizontal.css") ?>
	<!-- FullCalendar v6: bundle + locales só em Visitas/calendario.ctp -->
	<!-- select multiple -->
	<?= $this->Html->css("/dist/css/pages/bootstrap-select.css") ?>
	<!-- fa iconpicker -->
	<?= $this->Html->css("/plugins/iconpicker/iconpicker.css") ?>
	<?= $this->Html->css('/plugins/bootstrap-iconpicker-1.10.0/dist/css/bootstrap-iconpicker.min') ?>
	<?= $this->Html->css('/plugins/bootstrap-iconpicker-1.10.0/dist/css/bootstrap-iconpicker.min') ?>

	<!--- Scripts -->
	<?= $this->Html->script("/assets/node_modules/jquery/jquery-3.2.1.min") ?>
	<?= $this->element('pgm_turbo_head'); ?>
	<?= $this->Html->script("/js/pgm-portal-theme") ?>
	<?= $this->Html->script("/js/app-http") ?>
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
	<!-- FullCalendar v6 carregado na página da agenda (calendario.ctp) -->
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
	<!-- JSGrid local -->
	<?= $this->Html->css("/assets/node_modules/jsgrid/jsgrid.min") ?>
	<?= $this->Html->css("/assets/node_modules/jsgrid/jsgrid-theme") ?>
	<?= $this->Html->script("/assets/node_modules/jsgrid/jsgrid") ?>
    <!-- Select2 local -->
    <?= $this->Html->css("/assets/node_modules/select2/dist/css/select2") ?>
    <?= $this->Html->script("/assets/node_modules/select2/dist/js/select2") ?>

	<!-- Leitura dos componentes -->
	<?= $this->fetch('meta'); ?>
	<?= $this->fetch('css'); ?>
	<?= $this->fetch('script'); ?>
	<!-- Páginas que precisam vencer o tema global (ex.: estoque em modo escuro) -->
	<?= $this->fetch('css_late'); ?>
</head>
<body class="fixed-layout skin-green mini layout-no-topbar <?= empty($disablePgmAppShellPremium) ? 'pgm-app-shell-premium ' : '' ?><?= !empty($pgmLoadErpPrototypeAssets) ? 'pgm-erp-prototype-page ' : '' ?><?= !empty($pgmPortalClient) ? 'pgm-portal-client ' : '' ?><?= h($bodyPageClass ?? '') ?>">
	<!--- Pre loader -->
	<div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label">PGM</p>
        </div>
    </div>
	<?= $this->element('deleteModal'); ?>
	<!-- Painel principal -->
	<div class="main-wrapper" id="main-wrapper">
		<!-- Header horizontal removido: empresa, data e perfil estão na sidebar (layout-sidebar-shell.css) -->
		<!-- Sidebar: Cake (predefinição) ou montagem React se PGM_SIDEBAR_REACT=1 -->
		<?php if ($pgmReactSidebar) : ?>
		<div id="sidebar-app" data-turbo="true"></div>
		<script>window.__PGM_REACT_SIDEBAR__ = true;</script>
		<?php else : ?>
		<?php
			if ($role == 0) {
				echo $this->element('sidebar');
			} else {
				echo $this->element('sidebarcli');
			}
		?>
		<?php endif; ?>
		<div class="pgm-shell-main">
		<a href="javascript:void(0)" class="nav-toggler d-flex d-md-none pgm-shell-mobile-nav waves-effect waves-dark" aria-label="Abrir menu"><i class="ti-menu"></i></a>
		<turbo-frame id="pgm-main-frame" data-turbo-action="advance">
		<?php if (empty($disablePgmAppShellPremium)) : ?>
		<?php if (!empty($pgmLoadErpPrototypeAssets)) : ?>
		<?= $this->element('pgm_shell_topbar_erp', ['erpEmpresas' => (array)($erpEmpresas ?? [])]) ?>
		<?php else : ?>
		<?= $this->element('pgm_shell_topbar') ?>
		<?php endif; ?>
		<?php endif; ?>
		<div class="page-wrapper">
			<div class="container-fluid">
				<?php if (!($hideLayoutPageTitle ?? false)): ?>
				<div class="row page-titles pgm-page-titles--pb-tight">
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
		</turbo-frame>
		</div>
	</div>
	<div class='hide' id='notificacao'></div>
</body>
</html>
<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Realiza a alteração do menu lateral
		Array.prototype.forEach.call(document.querySelectorAll('.sidebartoggler'), function(toggleBtn) {
			toggleBtn.addEventListener('click', function() {
				verificaSidebar();
			});
		});

		// Verifica se é para ocultar o logo da sidebar
		function verificaSidebar() {
			var body = document.body;
			var mini = body && body.classList.contains('mini-sidebar');
			var miniItens = document.querySelectorAll('.mini-itens');
			var miniLogout = document.getElementById('mini-logout');
			var logos = document.querySelectorAll('.logo');

			Array.prototype.forEach.call(miniItens, function(el) {
				el.classList.toggle('d-none', !!mini);
			});
			if (miniLogout) {
				miniLogout.classList.toggle('d-none', !mini);
			}
			Array.prototype.forEach.call(logos, function(el) {
				el.classList.toggle('imagem-sidebar-mini', !!mini);
				el.classList.toggle('imagem-sidebar-expandida', !mini);
			});
		}
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

		document.addEventListener('DOMContentLoaded', function() {
			var cnpjInput = document.getElementById('cnpj');
			var cpfInput = document.getElementById('cpf');

			if (cnpjInput) {
				cnpjInput.addEventListener('change', function() {
					if (validarCNPJ(cnpjInput.value) == false) bootbox.alert('CNPJ inválido!');
				});
			}

			if (cpfInput) {
				cpfInput.addEventListener('change', function() {
					var regex = /[.-\s]/g;
					var result = String(cpfInput.value || '').replace(regex, '');
					if (validaCPF(result) == false) bootbox.alert('CPF inválido!');
				});
			}
		});

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
			window.PGMHttp.httpPost(url, {len: len})
				.catch(function() {
					bootbox.alert('Um erro ocorreu ao salvar sua preferência! Tente novamente.');
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

		document.addEventListener('DOMContentLoaded', function() {
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

		$('.datepicker').bootstrapMaterialDatePicker({ format : 'DD/MM/YYYY', lang : 'pt-br', time : false, switchOnClick : true, nowButton : true, cancelText : 'Cancelar' , 'setDate' : 'currentDate', nowText : 'Hoje'});	

	// Notificações: não chamar requestPermission no carregamento — o navegador exige gesto do usuário.
	// Permissão pode ser concedida nas configurações do site ou num fluxo futuro com botão explícito.

		function notificacaoTicket(){
			var url = "<?= Router::url(['controller' => 'Notificacoes', 'action' => 'notificacoes']);?>";
			var notificacaoEl = document.getElementById('notificacao');
			if (!notificacaoEl) return;
			window.PGMHttp.httpGet(url)
				.then(function(res) { return res.text(); })
				.then(function(data) { notificacaoEl.innerHTML = data; })
				.catch(function() {
					// Mantém comportamento silencioso do legado em falhas transitórias.
				});
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
			var $sel = $('#empresaSidebar');
			if (!$sel.length || $sel.attr('type') === 'hidden') {
				return;
			}
			var empresa = $sel.val();
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

		function pgmLayoutNoTopbarMinHeight() {
			var root = document.documentElement;
			var body = document.body;
			if (!body || !body.classList.contains('layout-no-topbar')) {
				root.style.removeProperty('--pgm-layout-min-h');
				if (body) body.classList.remove('pgm-layout-min-h-legacy-wrapper');
				return;
			}
			var h = Math.max(1, (window.innerHeight > 0 ? window.innerHeight : screen.height) - 1);
			root.style.setProperty('--pgm-layout-min-h', h + 'px');
			var shell = document.querySelector('.pgm-shell-main');
			body.classList.toggle('pgm-layout-min-h-legacy-wrapper', !shell);
		}
		window.addEventListener('resize', pgmLayoutNoTopbarMinHeight);
		window.addEventListener('load', function() {
			pgmLayoutNoTopbarMinHeight();
		});

<?= $this->element('pgm_turbo_shell'); ?>

</script>
<?php if ($pgmReactSidebar) : ?>
<script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js" crossorigin="anonymous"></script>
<?php
$pgmSidebarReactProps = isset($pgmSidebarReactProps) && is_array($pgmSidebarReactProps) ? $pgmSidebarReactProps : [];
$__pgmSbJsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
	$__pgmSbJsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}
echo '<script>window.__PGM_SIDEBAR_PROPS__ = ' . json_encode($pgmSidebarReactProps, $__pgmSbJsonFlags) . ";</script>\n";
?>
<script type="module" src="<?= h($pgmSidebarReactJs) ?>"></script>
<?php endif; ?>
