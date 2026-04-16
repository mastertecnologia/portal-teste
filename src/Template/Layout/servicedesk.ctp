<?php
$authSd = (bool)$this->request->getSession()->read('Auth.User.id');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-pgm-theme="dark">
<head>
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
	<title><?= h($title ?? 'Service Desk') ?> — PGM</title>
	<?= $this->Html->meta('icon') ?>
	<?= $this->Html->css('/dist/css/style.min') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-theme-tokens') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-components-base') ?>
	<?= $this->element('pgm_premium_css', ['name' => 'produtos-premium']) ?>
	<?= $this->Html->css('/dist/css/pages/pgm-advanced-module') ?>
	<link rel="stylesheet" href="<?= $this->Url->build('/dist/css/pages/pgm-servicedesk-premium.css') ?>">
	<?= $this->element('pgm_premium_css', ['name' => 'pgm-action-buttons']) ?>
	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
	<?= $this->fetch('script') ?>
</head>
<body class="sd-body sd-body--fullpage layout-no-topbar <?= h(trim($bodyPageClass ?? '')) ?>">
<div class="sd-shell sd-shell--no-topbar prd-root">
	<main class="sd-main sd-main--full container-fluid p-0">
		<?= $this->Flash->render() ?>
		<?= $this->fetch('content') ?>
	</main>
</div>
<?= $this->Html->script('/assets/node_modules/jquery/jquery-3.2.1.min') ?>
<?= $this->Html->script('/js/pgm-portal-theme') ?>
<?= $this->Html->script('/assets/node_modules/bootstrap/dist/js/bootstrap.min') ?>
</body>
</html>
