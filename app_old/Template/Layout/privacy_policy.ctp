<!DOCTYPE HTML>
<html>
<head>
	<!-- Charset e propriedades -->
	<?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Políticas de Privacidade">
    <meta name="author" content="Grid Sistemas">

	<!-- Ícone -->
	<?= $this->Html->meta('/assets/images/favicon.ico','/favicon.ico',['type' => 'icon']); ?>

	<!-- Título -->
	<!-- <title>Políticas de Privacidade</title> -->

	<!-- CSS -->
	<?= $this->Html->css("/dist/css/pages/login-register-lock") ?>
	<?= $this->Html->css("/dist/css/style.min") ?>

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
			<p class="loader__label">Grid Sistemas</p>
		</div>
	</div>

	<!-- Conteúdo -->
	<?= $this->fetch('content') ?>
</body>
</html>
