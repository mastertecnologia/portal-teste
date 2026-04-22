<?php
use App\Utility\PgmAppUrlBase;
use Cake\Core\Configure;
use Cake\Routing\Router;

$pgmReactSidebar = (bool) Configure::read('PgmSidebar.react_enabled') && !empty($iduser ?? null);
$pgmSidebarReactJs = PgmAppUrlBase::path($this->request) . '/js/pgm-sidebar-react/sidebar-app.js';
$pgmSidebarReactCss = PgmAppUrlBase::path($this->request) . '/js/pgm-sidebar-react/sidebar-assets.css';
?>
<!doctype html>
<html lang="pt-BR" data-pgm-theme="dark">
<head>
	<?= $this->Html->charset() ?>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

	<?= $this->Html->meta('favicon.ico','/favicon.ico',['type' => 'icon']); ?>

	<title><?= $this->fetch('title'); ?></title>

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
	<?php if (empty($disablePgmAppShellPremium)) : ?>
	<?= $this->Html->css('/dist/css/pages/pgm-app-shell-premium.css?v=2'); ?>
	<?php endif; ?>
	<?php if (!empty($pgmReactSidebar)) : ?>
	<?= $this->Html->css('/dist/css/pages/layout-sidebar-shell.css?v=2'); ?>
	<?= $this->Html->css('/dist/css/pages/pgm-sidebar-premium.css?v=2'); ?>
	<link rel="stylesheet" href="<?= h($pgmSidebarReactCss) ?>" crossorigin="anonymous" />
	<?php endif; ?>
	<?= $this->Html->css('http://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css'); ?>
	<?= $this->Html->css('http://fonts.googleapis.com/css?family=Roboto:400,700,300|Material+Icons'); ?>

	<!--- Data Table -->
	<?= $this->Html->css('/plugins/datatables/bootstrap-3-3-7') ?>
	<?= $this->Html->css('/plugins/datatables/dataTables.bootstrap') ?>
	<!-- WYSIHTML5 -->
	<?= $this->Html->css('/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css') ?>
	<?= $this->Html->css("/plugins/bootstrap-wysihtml5/wysiwyg-color") ?>

	<?= $this->fetch('meta'); ?>
	<?= $this->fetch('css'); ?>
	<?= $this->fetch('script'); ?>
	<?= $this->Html->script('jquery-3.1.0.min'); ?>
	<?= $this->element('pgm_turbo_head'); ?>
</head>
<?php
$pgmShellBody = empty($disablePgmAppShellPremium) ? 'pgm-app-shell-premium pgm-portal-client-shell ' : '';
?>
<body class="<?= h(trim($pgmShellBody . ($bodyPageClass ?? ''))) ?>">
	<div class="wrapper">
		<?php if (!empty($pgmReactSidebar)) : ?>
		<div id="sidebar-app" data-turbo="true"></div>
		<script>window.__PGM_REACT_SIDEBAR__ = true;</script>
		<?php else : ?>
		<?= $this->element('sidebarcli'); ?>
		<?php endif; ?>
		<?= $this->assign('title', $title); ?>

	    <div class="main-panel">
			<turbo-frame id="pgm-main-frame" data-turbo-action="advance">
			<?php if (empty($disablePgmAppShellPremium)) : ?>
			<?= $this->element('pgm_shell_topbar') ?>
			<?php else : ?>
			<?= $this->element('navbar'); ?>
			<?php endif; ?>
			<?= $this->element('content'); ?>
			<?= $this->element('footer'); ?>
			</turbo-frame>
	    </div>
	</div>

	<!--- Data Tables (jQuery no head; sidebar inline precisa de $) -->
	<?= $this->Html->script('/js/pgm-portal-theme'); ?>
	<?= $this->Html->script('bootstrap.min'); ?>
	<?= $this->Html->script('material.min'); ?>
	<?= $this->Html->script('chartist.min'); ?>
	<?= $this->Html->script('bootstrap-notify'); ?>
	<?= $this->Html->script('material-dashboard'); ?>
    <?= $this->Html->script('/plugins/datatables/jquery.dataTables.min') ?>
    <?= $this->Html->script('/plugins/datatables/dataTables.bootstrap.min') ?>
	<?= $this->Html->script('maskedinput.min') ?>
	<?= $this->Html->script('/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js') ?>
	<?php if (!empty($pgmReactSidebar)) : ?>
	<?php
	$pgmSidebarReactProps = isset($pgmSidebarReactProps) && is_array($pgmSidebarReactProps) ? $pgmSidebarReactProps : [];
	$__pgmSbJsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
	if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
		$__pgmSbJsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
	}
	echo '<script>window.__PGM_SIDEBAR_PROPS__ = ' . json_encode($pgmSidebarReactProps, $__pgmSbJsonFlags) . ";</script>\n";
	?>
	<script type="module" src="<?= h($pgmSidebarReactJs) ?>"></script>
	<?php endif; ?>
<script>
<?= $this->element('pgm_turbo_shell'); ?>
</script>
</body>
</html>
