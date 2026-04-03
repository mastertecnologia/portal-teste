<?php
use Cake\Routing\Router;
$privacyAuth = (bool)$this->request->getSession()->read('Auth.User.id');
$privacyTheme = (($skin ?? '') === 'skin-pgm-light') ? 'light' : 'dark';
$privacyThemeClass = ($privacyTheme === 'light') ? 'pgm-theme-light' : '';
$isLightPrivacy = ($privacyTheme === 'light');
?>
<!DOCTYPE HTML>
<html lang="pt-BR">
<head>
	<!-- Charset e propriedades -->
	<?= $this->Html->charset() ?>
	<script>
	(function () {
		var server = <?= json_encode($privacyAuth ? $privacyTheme : null) ?>;
		var k = 'pgmPortalTheme', def = 'light', v, t;
		if (server === 'light' || server === 'dark') {
			t = server;
		} else {
			try { v = localStorage.getItem(k); } catch (e) { v = null; }
			t = (v === 'dark' || v === 'light') ? v : def;
		}
		document.documentElement.setAttribute('data-pgm-theme', t);
	})();
	</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Políticas de Privacidade">
    <meta name="author" content="Grid Sistemas">

	<!-- Ícone -->
	<?= $this->Html->meta('/assets/images/favicon.ico','/favicon.ico',['type' => 'icon']); ?>

	<!-- CSS -->
	<?= $this->Html->css("/dist/css/pages/login-register-lock") ?>
	<?= $this->Html->css("/dist/css/style.min") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-theme-tokens") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-login-theme") ?>
	<?= $this->Html->css("/dist/css/pages/pgm-theme-light") ?>
	<?php if ($privacyAuth) : ?>
	<?= $this->Html->css("/dist/css/pages/pgm-advanced-module") ?>
	<?php endif; ?>
	<?= $this->element('pgm_premium_css', ['name' => 'pgm-action-buttons']) ?>

	<!-- Leitura dos componentes -->
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>

<body class="pgm-auth-page layout-no-topbar <?= h($privacyThemeClass) ?>">
	<!-- Plugins -->
	<?= $this->Html->script('/assets/node_modules/jquery/jquery-3.2.1.min'); ?>
	<?= $this->Html->script('/js/pgm-portal-theme'); ?>
	<?= $this->Html->script('/assets/node_modules/popper/popper.min'); ?>
	<?= $this->Html->script('/assets/node_modules/bootstrap/dist/js/bootstrap.min'); ?>
	<!-- Custom JavaScript -->
	<?= $this->Html->script("/dist/js/custom") ?>

	<?php if ($privacyAuth) : ?>
	<div class="pgm-privacy-theme-float" style="position:fixed;top:10px;right:10px;z-index:9999;">
		<button type="button" class="pgm-auth-theme-toggle pgm-js-theme-toggle"
			title="<?= $isLightPrivacy ? 'Mudar para tema escuro' : 'Mudar para tema claro' ?>"
			aria-pressed="<?= $isLightPrivacy ? 'true' : 'false' ?>"
			aria-label="<?= $isLightPrivacy ? 'Tema claro ativo. Ativar escuro' : 'Tema escuro ativo. Ativar claro' ?>"
			data-current="<?= $isLightPrivacy ? 'light' : 'dark' ?>">
			<span class="pgm-tt-icon" aria-hidden="true"><?= $isLightPrivacy ? '☀️' : '🌙' ?></span>
			<span class="pgm-tt-label"><?= $isLightPrivacy ? 'Claro' : 'Escuro' ?></span>
		</button>
	</div>
	<?php else : ?>
	<div class="pgm-privacy-theme-float" style="position:fixed;top:10px;right:10px;z-index:9999;">
		<button type="button" class="pgm-auth-theme-toggle" id="pgmAuthThemeToggle" aria-label="Alternar tema claro ou escuro">
			<span class="pgm-auth-tt-ico" aria-hidden="true">☀️</span>
			<span class="pgm-auth-tt-txt">Claro</span>
		</button>
	</div>
	<?php endif; ?>

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
		if (!window.PgmPortalTheme) return;
		<?php if ($privacyAuth) : ?>
		PgmPortalTheme.initSidebarToggle(<?= json_encode(Router::url(['controller' => 'Users', 'action' => 'selectTheme'])) ?>);
		<?php else : ?>
		PgmPortalTheme.initGuest('light');
		<?php endif; ?>
	});
	</script>
</body>
</html>
