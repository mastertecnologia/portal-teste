<!doctype html>
<html lang="pt-BR" data-pgm-theme="dark">
<head>
	<?= $this->Html->charset() ?>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<?= $this->Html->meta('icon') ?>

	<?php
		$pageTitle = (string)($this->fetch('title') ?: ($title ?? ''));
	?>
	<title><?= 'PGM - ' . h($pageTitle); ?></title>

	<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
	<?php
	$csrf = $this->request->getAttribute('csrfToken');
	if (!$csrf && method_exists($this->request, 'getParam')) {
		$csrf = $this->request->getParam('_csrfToken');
	}
	if ($csrf) :
	?>
	<meta name="csrfToken" content="<?= h($csrf) ?>">
	<?php endif; ?>

	<?= $this->Html->css('bootstrap.min'); ?>
	<?= $this->Html->css('material-dashboard'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-theme-tokens'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-components-base'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-legacy-material-theme'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-legacy-clear-theme'); ?>
	<?= $this->Html->css('http://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css'); ?>
	<?= $this->Html->css('http://fonts.googleapis.com/css?family=Roboto:400,700,300|Material+Icons'); ?>

	<!-- jQuery 2.2.3 -->
	<?= $this->Html->script('/plugins/jQuery/jquery-2.2.3.min.js') ?>

	<?= $this->fetch('meta'); ?>
	<?= $this->fetch('css'); ?>
	<?= $this->fetch('script'); ?>
</head>
<body class="pgm-legacy-clear">
	<?= $this->element('content'); ?>

	<?= $this->Html->script('jquery-3.1.0.min'); ?>
	<?= $this->Html->script('/js/pgm-portal-theme'); ?>
	<?= $this->Html->script('bootstrap.min'); ?>
	<?= $this->Html->script('material.min'); ?>
	<?= $this->Html->script('material-dashboard'); ?>
</body>
</html>
