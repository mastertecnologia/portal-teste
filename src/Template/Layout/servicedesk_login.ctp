<!DOCTYPE HTML>
<html lang="pt-BR">
<head>
	<?= $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Service Desk — Login">
	<?= $this->Html->meta('assets/images/favicon.ico?data=11/06/2021', 'favicon.ico?data=11/06/2021', ['type' => 'icon']); ?>
	<title><?= h($title ?? 'Service Desk — Login') ?> &lsaquo; PGM</title>
	<?= $this->Html->script('assets/node_modules/jquery/jquery-3.2.1.min') ?>
	<?= $this->Html->script('plugins/bootbox/bootbox.min.js') ?>
	<?= $this->Html->script('plugins/bootbox/bootbox.locales.min.js') ?>
	<?= $this->Html->css('dist/css/pages/login-register-lock') ?>
	<?= $this->Html->css('dist/css/style.min') ?>
	<?= $this->element('pgm_premium_css', ['name' => 'pgm-action-buttons']) ?>
	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
	<?= $this->fetch('script') ?>
</head>
<body class="sd-login-body">
	<?= $this->Html->script('assets/node_modules/jquery/jquery-3.2.1.min'); ?>
	<?= $this->Html->script('assets/node_modules/popper/popper.min'); ?>
	<?= $this->Html->script('assets/node_modules/bootstrap/dist/js/bootstrap.min'); ?>
	<?= $this->Html->script('dist/js/custom') ?>

	<div class="preloader">
		<div class="loader">
			<div class="loader__figure"></div>
			<p class="loader__label">PGM</p>
		</div>
	</div>

	<?= $this->fetch('content') ?>
</body>
</html>
