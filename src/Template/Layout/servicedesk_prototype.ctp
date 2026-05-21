<?php
/**
 * Layout dedicado ao protótipo SD (navegação lateral própria, alinhado ao mockup pgm_erp_completo.html).
 *
 * @var \App\View\AppView $this
 * @var string $title
 * @var string|null $sdpNavActive
 */
$w = $this->request->getAttribute('webroot');
$erpLocale = (string)$this->getRequest()->getSession()->read('Erp.locale');
if (!in_array($erpLocale, ['pt_BR', 'en_US', 'es'], true)) {
	$erpLocale = 'pt_BR';
}
$htmlLang = $erpLocale === 'en_US' ? 'en' : ($erpLocale === 'es' ? 'es' : 'pt-BR');
?>
<!DOCTYPE html>
<html lang="<?= h($htmlLang) ?>" data-pgm-theme="light">
<head>
	<?= $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#1D9E75">
	<?php
	$csrf = $this->request->getAttribute('csrfToken');
	if (!$csrf && method_exists($this->request, 'getParam')) {
		$csrf = $this->request->getParam('_csrfToken');
	}
	if ($csrf) :
	?>
	<meta name="csrfToken" content="<?= h($csrf) ?>">
	<?php endif; ?>
	<title><?= h($title ?? 'Service Desk (β)') ?> — PGM</title>
	<?= $this->Html->meta('icon') ?>
	<?= $this->element('ErpPrototype/head_assets', ['includeServicedeskPrototypeCss' => true]) ?>
	<style>
		.sdp-shell{display:flex;min-height:100vh;background:var(--bg-surface,#f9f9f8);}
		.sdp-shell-nav{width:220px;flex-shrink:0;background:#1a1a18;color:#fff;padding:14px 10px 18px;display:flex;flex-direction:column;gap:6px;}
		.sdp-shell-nav a{color:rgba(255,255,255,.72);text-decoration:none;font-size:12px;padding:6px 8px;border-radius:6px;display:block;}
		.sdp-shell-nav a:hover{background:rgba(255,255,255,.07);color:#fff;}
		.sdp-shell-nav a.sdp-nav-active{background:rgba(29,158,117,.28);color:#5dcaa5;}
		.sdp-shell-nav .sdp-nav-h{font-size:9px;text-transform:uppercase;letter-spacing:.7px;color:rgba(255,255,255,.35);padding:10px 8px 4px;}
		.sdp-shell-main{flex:1;padding:18px 22px 32px;overflow-x:auto;}
		.sdp-shell-brand{font-size:13px;font-weight:600;padding:4px 8px 12px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:8px;}
		.sdp-shell-brand small{display:block;font-size:10px;font-weight:500;color:rgba(255,255,255,.38);margin-top:3px;}
	</style>
	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
</head>
<body>
<div class="sdp-shell pgm-sd-prototype">
	<nav class="sdp-shell-nav" aria-label="<?= h(__('Módulo Service Desk (protótipo)')) ?>">
		<div class="sdp-shell-brand">
			SD · <?= h(__('Protótipo')) ?>
			<small><?= h(__('Dados reais de tickets')) ?></small>
		</div>
		<?= $this->element('ServicedeskPrototype/shell_nav', [
			'active' => $sdpNavActive ?? '',
			'sdpNavBadges' => $sdpNavBadges ?? [],
		]) ?>
		<div style="margin-top:auto;padding-top:12px;border-top:1px solid rgba(255,255,255,.08);">
			<?= $this->Html->link('← ' . __('Portal PGM'), ['controller' => 'Users', 'action' => 'dashboard'], ['class' => '', 'style' => 'color:rgba(255,255,255,.55);font-size:11px;display:block;margin-bottom:6px;']) ?>
			<a href="<?= h($this->Url->build(['controller' => 'Users', 'action' => 'acessoEmpresa', '?' => ['redirect' => $this->request->getRequestTarget()]])) ?>" style="color:rgba(255,255,255,.45);font-size:10px;"><?= h(__('Login equipe')) ?></a>
		</div>
	</nav>
	<?php
	$fluidMain = in_array((string)($sdpNavActive ?? ''), ['portal', 'portal-novo', 'kb', 'calendar', 'aprovacoes'], true);
	?>
	<main class="sdp-shell-main<?= $fluidMain ? ' sdp-shell-main-fluid' : '' ?>">
		<?= $this->element('ErpPrototype/sdp_toolbar') ?>
		<?= $this->Flash->render() ?>
		<?= $this->fetch('content') ?>
	</main>
</div>
<?= $this->fetch('script') ?>
<?php
use Cake\Core\Configure;
$pgmSwScope = '/';
$appBase = (string)(Configure::read('App.base') ?: '');
if ($appBase !== '') {
	$pgmSwScope = rtrim($appBase, '/') . '/';
}
echo $this->element('ErpPrototype/shell_runtime', [
	'pgmShellWebroot' => $w,
	'pgmSwScope' => $pgmSwScope,
	'pgmShellPwa' => false,
]);
?>
</body>
</html>
