<?php
/**
 * Layout genérico do shell premium (mockup pgm_erp_completo.html).
 *
 * Usado por todas as rotas `*-prototype` (servicedesk, orcamentos, os, etc.).
 * Inclui sidebar fixa + topbar com seletor multi-empresa.
 *
 * Para usar numa view:
 *
 *   $this->viewBuilder()->setLayout('erp_prototype');
 *   $this->set([
 *     'title' => 'Tickets',
 *     'erpNavActive' => 'sd-fila',
 *     'erpNavBadges' => ['sd-aprovacoes' => 5],
 *     'erpBreadcrumb' => [
 *       ['label' => 'Service Desk'],
 *       ['label' => 'Fila técnica', 'cur' => true],
 *     ],
 *     'erpEmpresas' => $empresasList,
 *   ]);
 *
 * @var \App\View\AppView $this
 * @var string $title
 * @var string $erpNavActive
 * @var array $erpNavBadges
 * @var array $erpBreadcrumb
 * @var array $erpEmpresas
 */
$w = (string)($this->getRequest()->getAttribute('webroot') ?? '');
$csrf = $this->getRequest()->getAttribute('csrfToken');
if (!$csrf && method_exists($this->getRequest(), 'getParam')) {
	$csrf = $this->getRequest()->getParam('_csrfToken');
}
?>
<?php
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
	<meta name="turbo-visit-control" content="reload">
	<?php if ($csrf) : ?>
		<meta name="csrfToken" content="<?= h($csrf) ?>">
	<?php endif; ?>
	<title><?= h($title ?? 'PGM ERP') ?> — PGM</title>
	<?= $this->Html->meta('icon') ?>
	<?= $this->element('ErpPrototype/head_assets', [
		'includeServicedeskPrototypeCss' => !empty($loadServicedeskPrototypeCss),
	]) ?>
	<?php
	// Opt-in: views que precisam de gráficos setam $useChartJs = true.
	if (!empty($useChartJs)) :
	?>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
	<?php endif; ?>
	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
</head>
<body>
<div class="pgm-erp-shell">
	<div class="app">
		<?= $this->element('ErpPrototype/sidebar', [
			'active' => (string)($erpNavActive ?? ''),
			'erpNavBadges' => (array)($erpNavBadges ?? []),
		]) ?>
		<div class="main">
			<?= $this->element('ErpPrototype/topbar', [
				'erpBreadcrumb' => (array)($erpBreadcrumb ?? []),
				'erpEmpresas' => (array)($erpEmpresas ?? []),
			]) ?>
			<div class="content">
				<?= $this->Flash->render() ?>
				<?= $this->fetch('content') ?>
			</div>
		</div>
	</div>
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
	'pgmShellPwa' => true,
]);
?>
</body>
</html>
