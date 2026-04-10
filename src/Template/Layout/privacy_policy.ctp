<?php
$privacyAuth = (bool)$this->request->getSession()->read('Auth.User.id');
?>
<!DOCTYPE HTML>
<html lang="pt-BR" data-pgm-theme="dark">
<head>
	<!-- Charset e propriedades -->
	<?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<?php
	$csrf = $this->request->getAttribute('csrfToken');
	if (!$csrf && method_exists($this->request, 'getParam')) {
		$csrf = $this->request->getParam('_csrfToken');
	}
	if ($csrf) :
	?>
	<meta name="csrfToken" content="<?= h($csrf) ?>">
	<?php endif; ?>
    <meta name="description" content="Políticas de Privacidade">
    <meta name="author" content="Grid Sistemas">

	<!-- Ícone -->
	<?= $this->Html->meta('/assets/images/favicon.ico','/favicon.ico',['type' => 'icon']); ?>

	<!-- CSS -->
	<?= $this->Html->css("/dist/css/pages/login-register-lock") ?>
	<?= $this->Html->css("/dist/css/style.min") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-theme-tokens") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-components-base") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-login-theme") ?>
	<?php if ($privacyAuth) : ?>
	<?= $this->Html->css("/dist/css/pages/pgm-advanced-module") ?>
	<?php endif; ?>
	<?= $this->element('pgm_premium_css', ['name' => 'pgm-action-buttons']) ?>

	<!-- Leitura dos componentes -->
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>

<body class="pgm-auth-page layout-no-topbar">
	<!-- Plugins -->
	<?= $this->Html->script('/assets/node_modules/jquery/jquery-3.2.1.min'); ?>
	<?= $this->Html->script('/js/pgm-portal-theme'); ?>
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

	<script>
	$(function () {
		if (window.PgmPortalTheme) {
			PgmPortalTheme.initGuest();
		}
	});
	</script>
</body>
</html>
