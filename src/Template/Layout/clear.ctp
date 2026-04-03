<?php
use Cake\Routing\Router;
$pgmClearTheme = (($skin ?? '') === 'skin-pgm-light') ? 'light' : 'dark';
$pgmClearThemeClass = ($pgmClearTheme === 'light') ? 'pgm-theme-light' : '';
$isLightClear = ($pgmClearTheme === 'light');
$clearAuth = (bool)$this->request->getSession()->read('Auth.User.id');
?>
<!doctype html>
<html lang="pt-BR" data-pgm-theme="<?= h($pgmClearTheme) ?>">
<head>
	<?= $this->Html->charset() ?>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<?= $this->Html->meta('icon') ?>

	<?php
		$pageTitle = (string)($this->fetch('title') ?: ($title ?? ''));
	?>
	<title><?= 'PGM - ' . h($pageTitle); ?></title>

	<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />

	<?= $this->Html->css('bootstrap.min'); ?>
	<?= $this->Html->css('material-dashboard'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-theme-tokens'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-legacy-material-theme'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-legacy-clear-theme'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-theme-light'); ?>

	<?= $this->Html->css('http://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css'); ?>
	<?= $this->Html->css('http://fonts.googleapis.com/css?family=Roboto:400,700,300|Material+Icons'); ?>

	<!-- jQuery 2.2.3 -->
	<?= $this->Html->script('/plugins/jQuery/jquery-2.2.3.min.js') ?>

	<?= $this->fetch('meta'); ?>
	<?= $this->fetch('css'); ?>
	<?= $this->fetch('script'); ?>
</head>
<body class="pgm-legacy-clear <?= h($pgmClearThemeClass) ?>">
	<?php if ($clearAuth) : ?>
	<button type="button" class="pgm-legacy-theme-fab pgm-js-theme-toggle"
		id="pgmThemeToggleClear"
		title="<?= $isLightClear ? 'Mudar para tema escuro' : 'Mudar para tema claro' ?>"
		aria-pressed="<?= $isLightClear ? 'true' : 'false' ?>"
		aria-label="<?= $isLightClear ? 'Tema claro ativo. Ativar escuro' : 'Tema escuro ativo. Ativar claro' ?>"
		data-current="<?= $isLightClear ? 'light' : 'dark' ?>">
		<span class="pgm-tt-icon" aria-hidden="true"><?= $isLightClear ? '☀️' : '🌙' ?></span>
		<span class="pgm-tt-label"><?= $isLightClear ? 'Claro' : 'Escuro' ?></span>
	</button>
	<?php endif; ?>
	<?= $this->element('content'); ?>

	<?= $this->Html->script('jquery-3.1.0.min'); ?>
	<?= $this->Html->script('/js/pgm-portal-theme'); ?>
	<?= $this->Html->script('bootstrap.min'); ?>
	<?= $this->Html->script('material.min'); ?>
	<?= $this->Html->script('material-dashboard'); ?>
	<?php if ($clearAuth) : ?>
	<script>
	if (window.PgmPortalTheme) {
		PgmPortalTheme.initSidebarToggle(<?= json_encode(Router::url(['controller' => 'Users', 'action' => 'selectTheme'])) ?>);
	}
	</script>
	<?php endif; ?>
</body>
</html>
