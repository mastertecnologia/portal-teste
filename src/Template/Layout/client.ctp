<?php
use Cake\Routing\Router;
$pgmLegacyTheme = (($skin ?? '') === 'skin-pgm-light') ? 'light' : 'dark';
$pgmLegacyThemeClass = ($pgmLegacyTheme === 'light') ? 'pgm-theme-light' : '';
$isLightLegacy = ($pgmLegacyTheme === 'light');
?>
<!doctype html>
<html lang="pt-BR" data-pgm-theme="<?= h($pgmLegacyTheme) ?>">
<head>
	<?= $this->Html->charset() ?>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

	<?= $this->Html->meta('favicon.ico','/favicon.ico',['type' => 'icon']); ?>

	<title><?= $this->fetch('title'); ?></title>

	<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />

	<?= $this->Html->css('bootstrap.min'); ?>
	<?= $this->Html->css('material-dashboard'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-theme-tokens'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-components-base'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-legacy-material-theme'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-theme-light'); ?>

	<?= $this->Html->css('http://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css'); ?>
	<?= $this->Html->css('http://fonts.googleapis.com/css?family=Roboto:400,700,300|Material+Icons'); ?>

	<!--- Data Table -->
	<?= $this->Html->css('/plugins/datatables/bootstrap-3-3-7') ?>
	<?= $this->Html->css('/plugins/datatables/dataTables.bootstrap') ?>
	<!-- WYSIHTML5 -->
	<?= $this->Html->css('/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css') ?>
	<?= $this->Html->css("/plugins/bootstrap-wysihtml5/wysiwyg-color") ?>

	<?= $this->fetch('meta'); ?>
	<?= $this->fetch('css'); ?>
	<?= $this->fetch('script'); ?>
</head>
<body class="<?= h($pgmLegacyThemeClass) ?>">
	<div class="wrapper">
	    <?= $this->element('sidebar'); ?>
		<?= $this->assign('title', $title); ?>

	    <div class="main-panel">
			<?= $this->element('navbar'); ?>
			<?= $this->element('content'); ?>
			<?= $this->element('footer'); ?>
	    </div>
	</div>

	<!--- Data Tables -->
	<?= $this->Html->script('jquery-3.1.0.min'); ?>
	<?= $this->Html->script('/js/pgm-portal-theme'); ?>
	<?= $this->Html->script('bootstrap.min'); ?>
	<?= $this->Html->script('material.min'); ?>
	<?= $this->Html->script('chartist.min'); ?>
	<?= $this->Html->script('bootstrap-notify'); ?>
	<?= $this->Html->script('material-dashboard'); ?>
    <?= $this->Html->script('/plugins/datatables/jquery.dataTables.min') ?>
    <?= $this->Html->script('/plugins/datatables/dataTables.bootstrap.min') ?>
	<?= $this->Html->script('maskedinput.min') ?>
	<?= $this->Html->script('/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js') ?>
	<script>
	if (window.PgmPortalTheme) {
		PgmPortalTheme.initSidebarToggle(<?= json_encode(Router::url(['controller' => 'Users', 'action' => 'selectTheme'])) ?>);
	}
	</script>
</body>
</html>
