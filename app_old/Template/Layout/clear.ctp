<!doctype html>
<html>
<head>
	<?= $this->Html->charset() ?>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<?= $this->Html->meta('icon') ?>

	<title><?= 'PGM - ' . $title; ?></title>

	<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />

	<?= $this->Html->css('bootstrap.min'); ?>
	<?= $this->Html->css('material-dashboard'); ?>

	<?= $this->Html->css('http://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css'); ?>
	<?= $this->Html->css('http://fonts.googleapis.com/css?family=Roboto:400,700,300|Material+Icons'); ?>

	<!-- jQuery 2.2.3 -->
	<?= $this->Html->script('/plugins/jQuery/jquery-2.2.3.min.js') ?>

	<?= $this->fetch('meta'); ?>
	<?= $this->fetch('css'); ?>
	<?= $this->fetch('script'); ?>
</head>
<body>
	<?= $this->assign('title', $title); ?>

	<?= $this->element('content'); ?>

	<?= $this->Html->script('jquery-3.1.0.min'); ?>
	<?= $this->Html->script('bootstrap.min'); ?>
	<?= $this->Html->script('material.min'); ?>
	<?= $this->Html->script('material-dashboard'); ?>
</body>
</html>
