<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of this file must retain the above copyright notice.
 */
?>
<!DOCTYPE html>
<html lang="pt-BR" data-pgm-theme="dark">
<head>
	<?= $this->Html->charset() ?>
	<title>
		<?= $this->fetch('title') ?>
	</title>

	<?= $this->Html->meta('favicon.ico','/favicon.ico',['type' => 'icon']); ?>

	<?= $this->Html->css('/dist/css/pages/pgm-theme-tokens') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-components-base') ?>
	<?= $this->Html->css('base.css') ?>
	<?= $this->Html->css('cake.css') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-error-cake') ?>

	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
	<?= $this->fetch('script') ?>
</head>
<body class="pgm-error-cake">
	<div id="container">
		<div id="header">
			<h1><?= __('Error') ?></h1>
		</div>
		<div id="content">
			<?= $this->Flash->render() ?>

			<?= $this->fetch('content') ?>
		</div>
		<div id="footer">
			<?= $this->Html->link(__('Back'), 'javascript:history.back()') ?>
		</div>
	</div>
</body>
</html>
