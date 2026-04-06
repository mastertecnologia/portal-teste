<?php
use Cake\Routing\Router;
$pgmSdTheme = (($skin ?? '') === 'skin-pgm-light') ? 'light' : 'dark';
$pgmSdThemeClass = ($pgmSdTheme === 'light') ? 'pgm-theme-light' : '';
$authSd = (bool)$this->request->getSession()->read('Auth.User.id');
$isLightSd = ($pgmSdTheme === 'light');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-pgm-theme="<?= h($pgmSdTheme) ?>">
<head>
	<?= $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= h($title ?? 'Service Desk') ?> — PGM</title>
	<?= $this->Html->meta('icon') ?>
	<?= $this->Html->css('/dist/css/style.min') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-theme-tokens') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-components-base') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-advanced-module') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-servicedesk-theme') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-theme-light') ?>
	<?= $this->element('pgm_premium_css', ['name' => 'pgm-action-buttons']) ?>
	<?= $this->Html->css('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', ['fullBase' => true]) ?>
	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
	<?= $this->fetch('script') ?>
</head>
<body class="sd-body layout-no-topbar <?= h($pgmSdThemeClass) ?>">
<div class="sd-shell">
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
				<button type="button" class="pgm-sd-theme-toggle pgm-js-theme-toggle" id="pgmThemeToggle"
					title="<?= $isLightSd ? 'Mudar para tema escuro' : 'Mudar para tema claro' ?>"
					aria-pressed="<?= $isLightSd ? 'true' : 'false' ?>"
					aria-label="<?= $isLightSd ? 'Tema claro ativo. Ativar escuro' : 'Tema escuro ativo. Ativar claro' ?>"
					data-current="<?= $isLightSd ? 'light' : 'dark' ?>">
					<span class="pgm-tt-icon"><?= $isLightSd ? '☀️' : '🌙' ?></span>
					<span class="pgm-tt-label"><?= $isLightSd ? 'Claro' : 'Escuro' ?></span>
				</button>
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
<?php if ($authSd) : ?>
<script>
if (window.PgmPortalTheme) {
	PgmPortalTheme.initSidebarToggle(<?= json_encode(Router::url(['controller' => 'Users', 'action' => 'selectTheme'])) ?>);
}
</script>
<?php endif; ?>
</body>
</html>
