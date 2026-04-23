<?php
$authSd = (bool)$this->request->getSession()->read('Auth.User.id');
$sdUser = $this->request->getSession()->read('Auth.User');
$sdRole = is_array($sdUser) && isset($sdUser['role']) ? (int)$sdUser['role'] : -1;
$sdUrlFila = $this->Url->build(['controller' => 'Servicedesk', 'action' => 'index']);
$sdUrlAdd = $this->Url->build(['controller' => 'Servicedesk', 'action' => 'add']);
$sdAddLabel = ($sdRole === 1) ? __('Abrir ticket') : __('Abrir chamado');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-pgm-theme="light">
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
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<?= $this->Html->css('/dist/css/style.min') ?>
	<?= $this->Html->css('/dist/css/pages/bootstrap-select.css') ?>
	<link rel="stylesheet" href="<?= $this->Url->build('/dist/css/pages/pgm-servicedesk-premium.css') ?>">
	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
	<?= $this->fetch('script') ?>
</head>
<body class="sd-body sd-body--fullpage sd-body--sd-topbar <?= h(trim($bodyPageClass ?? '')) ?>">
	<?= $this->Html->script('/assets/node_modules/jquery/jquery-3.2.1.min') ?>
	<?= $this->Html->script('/js/pgm-portal-theme') ?>
	<?= $this->Html->script('/assets/node_modules/popper/popper.min') ?>
	<?= $this->Html->script('/assets/node_modules/bootstrap/dist/js/bootstrap.min') ?>
	<?= $this->Html->script('/dist/js/pages/bootstrap-select') ?>
	<?= $this->Html->script('/plugins/Chrome-like-jQuery-Autocomplete-Autosuggest-Plugin-typeAhead/lib/jquery-typeahead.js') ?>
	<?= $this->Html->script('/plugins/bootbox/bootbox.min.js') ?>
	<?= $this->Html->script('/plugins/bootbox/bootbox.locales.min.js') ?>
<div class="sd-shell sd-shell--with-sd-topbar prd-root">
	<header class="sd-topbar" role="banner">
		<div class="flex-center">
			<span class="sd-brand-icon" aria-hidden="true">SD</span>
			<a href="<?= h($sdUrlFila) ?>" class="sd-brand" style="color:inherit;text-decoration:none"><?= h(__('PGM Service Desk')) ?></a>
		</div>
		<nav class="sd-actions" aria-label="<?= h(__('Service Desk — atalhos')) ?>">
			<?php
			$__sdTopbarActions = trim((string)$this->fetch('sd_topbar_actions'));
			$__sdHideAbrir = !empty($hideServicedeskOpenTicketCta);
			if ($__sdTopbarActions !== '') :
				echo $__sdTopbarActions;
			elseif ($__sdHideAbrir) :
			?>
			<a href="<?= h($sdUrlFila) ?>" class="sd-topbar__btn sd-topbar__btn--voltar-fila" style="background:transparent;border:1px solid rgba(0,0,0,0.12);color:inherit;text-decoration:none">
				<?= h(__('Voltar à fila')) ?>
			</a>
			<?php else : ?>
			<a href="<?= h($sdUrlAdd) ?>" class="sd-topbar__btn sd-topbar__btn--abrir-chamado">
				<span class="sd-topbar__btn-plus" aria-hidden="true">+</span>
				<?= h($sdAddLabel) ?>
			</a>
			<?php endif; ?>
		</nav>
	</header>
	<main class="sd-main sd-main--full container-fluid p-0">
		<?= $this->Flash->render() ?>
		<?= $this->fetch('content') ?>
	</main>
</div>
</body>
</html>
