<!DOCTYPE HTML>
<html lang="pt-BR">
<head>
	<!-- Charset e propriedades -->
	<?= $this->Html->charset() ?>
	<script>
	(function () {
		var k = 'pgmPortalTheme', def = 'light', v;
		try { v = localStorage.getItem(k); } catch (e) { v = null; }
		var t = (v === 'dark' || v === 'light') ? v : def;
		document.documentElement.setAttribute('data-pgm-theme', t);
	})();
	</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Login">
    <meta name="author" content="Grid Sistemas">

	<!-- Ícone (path relativo; o HtmlHelper já adiciona a base /portal/) -->
	<?= $this->Html->meta('assets/images/favicon.ico?data=11/06/2021', 'favicon.ico?data=11/06/2021', ['type' => 'icon']); ?>

	<!-- Título -->
	<title>Login &lsaquo; PGM Soluções em TI</title>

	<?= $this->Html->css('dist/css/pages/pgm-theme-tokens') ?>
	<?= $this->Html->css('dist/css/pages/pgm-login-theme') ?>

	<!--- Scripts -->
	<?= $this->Html->script('assets/node_modules/jquery/jquery-3.2.1.min') ?>
	<?= $this->Html->script('js/pgm-portal-theme') ?>
	<!-- Bootbox - confirms -->
	<?= $this->Html->script('plugins/bootbox/bootbox.min.js') ?>
	<?= $this->Html->script('plugins/bootbox/bootbox.locales.min.js') ?>

	<!-- CSS -->
	<?= $this->Html->css('dist/css/pages/login-register-lock') ?>
	<?= $this->Html->css('dist/css/style.min') ?>
	<?= $this->element('pgm_premium_css', ['name' => 'pgm-action-buttons']) ?>

	<!-- Leitura dos componentes -->
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>

</head>

<body class="pgm-auth-page">
	<!-- Plugins -->
	<?= $this->Html->script('assets/node_modules/jquery/jquery-3.2.1.min'); ?>
	<?= $this->Html->script('assets/node_modules/popper/popper.min'); ?>
	<?= $this->Html->script('assets/node_modules/bootstrap/dist/js/bootstrap.min'); ?>
	<!-- Custom JavaScript -->
	<?= $this->Html->script('dist/js/custom') ?>

	<!-- Pre laoder -->
	<div class="preloader">
		<div class="loader">
			<div class="loader__figure"></div>
			<p class="loader__label">PGM</p>
		</div>
	</div>

	<!-- Conteúdo -->
	<?= $this->fetch('content') ?>
	<script>
	$(function () {
		if (window.PgmPortalTheme) {
			PgmPortalTheme.initGuest('light');
		}
	});
	</script>
</body>
</html>
