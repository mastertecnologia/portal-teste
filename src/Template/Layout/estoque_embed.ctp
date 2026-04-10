<?php
/**
 * Layout enxuto para editar / precificar a partir do Estoque (iframe ou janela única).
 * Mantém toolbar com retorno à listagem (target=_top) sem sidebar do portal.
 *
 * @var string|null $estoqueEmbedReturnUrl URL sanitizada da listagem de estoque
 * @var string|null $bodyPageClass ex.: prec-screen-active
 */
use App\Utility\PortalUrlPath;
use Cake\Routing\Router;

$toolbarUrl = !empty($estoqueEmbedReturnUrl)
	? $estoqueEmbedReturnUrl
	: PortalUrlPath::normalizeRelativeUrl(Router::url(['controller' => 'Produtos', 'action' => 'estoque', 't']));
$bodyExtra = isset($bodyPageClass) ? h((string)$bodyPageClass) : '';
?>
<!DOCTYPE HTML>
<html lang="pt-BR" data-pgm-theme="dark">
<head>
	<?= $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php
	$csrf = $this->request->getAttribute('csrfToken');
	if (!$csrf && method_exists($this->request, 'getParam')) {
		$csrf = $this->request->getParam('_csrfToken');
	}
	if ($csrf) : ?>
	<meta name="csrfToken" content="<?= h($csrf) ?>">
	<?php endif; ?>
	<title><?= h($title ?? 'Portal') ?></title>

	<?= $this->Html->css('/dist/css/style.min') ?>
	<?= $this->Html->css('/dist/css/popover') ?>
	<?= $this->Html->css('/dist/css/css.css') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-theme-tokens') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-components-base') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-advanced-module') ?>
	<?= $this->fetch('css') ?>
	<?= $this->Html->script('/assets/node_modules/jquery/jquery-3.2.1.min') ?>
	<?= $this->Html->script('/js/pgm-portal-theme') ?>
	<?= $this->Html->script('/assets/node_modules/popper/popper.min') ?>
	<?= $this->Html->script('/assets/node_modules/bootstrap/dist/js/bootstrap.min') ?>
	<?= $this->Html->script('/dist/js/waves') ?>
	<?= $this->fetch('script') ?>
	<?= $this->Html->css('/dist/css/pages/bootstrap-select.css') ?>
	<?= $this->Html->script('/dist/js/pages/bootstrap-select') ?>
	<?= $this->fetch('css_late') ?>
	<style>
		body.estoque-embed-shell { margin: 0; min-height: 100vh; background: var(--pgm-bg-page, #f0f2f5); }
		.est-embed-toolbar { display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: #141c24; color: #e6edf3; border-bottom: 1px solid rgba(255,255,255,.08); position: sticky; top: 0; z-index: 1040; }
		.est-embed-toolbar__back { color: #5cdbc0 !important; font-weight: 600; text-decoration: none !important; }
		.est-embed-toolbar__back:hover { color: #fff !important; }
		.est-embed-main { padding: 12px 16px 32px; max-width: 100%; }
	</style>
</head>
<body class="estoque-embed-shell layout-no-topbar <?= $bodyExtra ?>">
	<div class="est-embed-toolbar">
		<a class="est-embed-toolbar__back" target="_top" href="<?= h($toolbarUrl) ?>">← Voltar ao estoque</a>
	</div>
	<div class="est-embed-main">
		<?= $this->fetch('content') ?>
	</div>
	<?= $this->Html->script('/plugins/jQuery-Mask-Plugin-master/src/jquery.mask.js') ?>
	<script>
	$(function () {
		if ($.fn.mask) {
			$('.mascaramonetaria').mask('#.##0,00', { reverse: true });
		}
	});
	</script>
</body>
</html>
