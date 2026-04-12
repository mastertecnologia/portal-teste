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
<body class="sd-body layout-no-topbar <?= h(trim($bodyPageClass ?? '')) ?>">
<div class="sd-shell prd-root">
	<header class="sd-topbar">
		<div class="d-flex align-items-center gap-3 flex-wrap">
			<span class="sd-brand">Service Desk</span>
			<?php if (!empty($name) || !empty($nomeempresa)) : ?>
				<span class="opacity-90 small">
					<?php if (!empty($nomeempresa)) : ?><?= h($nomeempresa) ?><?php endif; ?>
					<?php if (!empty($name)) : ?> · <?= h($name) ?><?php endif; ?>
				</span>
			<?php endif; ?>
		</div>
		<nav class="sd-actions" aria-label="Acesso">
			<?php if ($authSd) : ?>
				<?php if ((int)$this->request->getSession()->read('Auth.User.role') === 0) : ?>
					<a href="<?= $this->Url->build(['controller' => 'Servicedesk', 'action' => 'index']) ?>">Fila</a>
					<a href="<?= $this->Url->build(['controller' => 'Servicedesk', 'action' => 'operacional']) ?>">Painel operacional</a>
				<?php endif; ?>
				<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'dashboard']) ?>">Dashboard</a>
				<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'logout']) ?>">Sair</a>
			<?php else : ?>
				<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>">Login cliente</a>
				<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'acessoEmpresa']) ?>">Login equipe</a>
			<?php endif; ?>
		</nav>
	</header>
	<main class="sd-main container-fluid p-0">
		<div class="row no-gutters m-0">
			<div class="col-12">
				<?= $this->Flash->render() ?>
				<?= $this->fetch('content') ?>
			</div>
		</div>
	</main>
</div>
<?= $this->Html->script('/assets/node_modules/jquery/jquery-3.2.1.min') ?>
<?= $this->Html->script('/js/pgm-portal-theme') ?>
<?= $this->Html->script('/assets/node_modules/bootstrap/dist/js/bootstrap.min') ?>
</body>
</html>
