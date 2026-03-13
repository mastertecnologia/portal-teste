<!DOCTYPE HTML>
<html>
<head>
	<!-- Charset e propriedades -->
	<?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Login">
    <meta name="author" content="Grid Sistemas">

	<!-- Ícone (paths relativos ao webroot para funcionar com ou sem /portal na URL) -->
	<?php $webroot = $this->request->getAttribute('webroot'); ?>
	<?= $this->Html->meta($webroot . 'assets/images/favicon.ico?data=11/06/2021', $webroot . 'favicon.ico?data=11/06/2021', ['type' => 'icon']); ?>

	<!-- Título -->
	<title>Login &lsaquo; PGM Soluções em TI</title>

	<!--- Scripts -->
	<?= $this->Html->script($webroot . 'assets/node_modules/jquery/jquery-3.2.1.min') ?>
	<!-- Bootbox - confirms -->
	<?= $this->Html->script($webroot . 'plugins/bootbox/bootbox.min.js') ?>
	<?= $this->Html->script($webroot . 'plugins/bootbox/bootbox.locales.min.js') ?>

	<!-- CSS -->
	<?= $this->Html->css($webroot . 'dist/css/pages/login-register-lock') ?>
	<?= $this->Html->css($webroot . 'dist/css/style.min') ?>

	<!-- Leitura dos componentes -->
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>

</head>

<body>
	<!-- Plugins -->
	<?= $this->Html->script($webroot . 'assets/node_modules/jquery/jquery-3.2.1.min'); ?>
	<?= $this->Html->script($webroot . 'assets/node_modules/popper/popper.min'); ?>
	<?= $this->Html->script($webroot . 'assets/node_modules/bootstrap/dist/js/bootstrap.min'); ?>
	<!-- Custom JavaScript -->
	<?= $this->Html->script($webroot . 'dist/js/custom') ?>

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
