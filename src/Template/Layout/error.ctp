<!DOCTYPE html>
<html lang="pt-BR" data-pgm-theme="dark">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />

	<?= $this->Html->meta('favicon.ico','/favicon.ico',['type' => 'icon']); ?>

	<title>We've got some trouble | {{code}} - {{title}}</title>

	<?= $this->Html->css('/dist/css/pages/pgm-theme-tokens') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-components-base') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-error-http') ?>
</head>

<body class="pgm-error-http">
	<div class="cover">
		<?= $this->element('content'); ?>
	</div>
</body>
</html>
