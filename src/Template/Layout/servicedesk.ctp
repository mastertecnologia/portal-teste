<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?= $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= h($title ?? 'Service Desk') ?> — PGM</title>
	<?= $this->Html->meta('icon') ?>
	<?= $this->Html->css('/dist/css/style.min') ?>
	<?= $this->Html->css('/css/pgm-action-buttons') ?>
	<?= $this->Html->css('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', ['fullBase' => true]) ?>
	<style>
		.sd-shell { min-height: 100vh; background: #f4f6f9; display: flex; flex-direction: column; }
		.sd-topbar {
			background: linear-gradient(90deg, #0d5c63 0%, #127a82 100%);
			color: #fff;
			padding: 0.65rem 1.25rem;
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 0.5rem;
			box-shadow: 0 2px 8px rgba(0,0,0,.12);
		}
		.sd-topbar a { color: #e8fffc; text-decoration: none; }
		.sd-topbar a:hover { color: #fff; text-decoration: underline; }
		.sd-topbar .sd-brand { font-weight: 700; font-size: 1.05rem; letter-spacing: .02em; }
		.sd-topbar .sd-actions { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; font-size: 0.875rem; }
		.sd-main { flex: 1; width: 100%; max-width: 100%; padding: 0; }
		body.sd-body { margin: 0; }
	</style>
	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
	<?= $this->fetch('script') ?>
</head>
<body class="sd-body">
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
			<?php if ($this->request->getSession()->read('Auth.User.id')) : ?>
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
<?= $this->Html->script('/assets/node_modules/bootstrap/dist/js/bootstrap.min') ?>
</body>
</html>
