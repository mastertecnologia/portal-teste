<?php
/**
 * Topbar portal alinhada ao mock ERP (breadcrumb + empresa + sino + data + avatar).
 *
 * @var string|null $topbarParentLabel
 * @var string|null $topbarCurrentLabel
 * @var string|null $title
 * @var array<int,array<string,mixed>> $erpEmpresas
 */
$parentLbl = isset($topbarParentLabel) ? trim((string)$topbarParentLabel) : '';
$currentLbl = isset($topbarCurrentLabel) ? trim((string)$topbarCurrentLabel) : '';
if ($currentLbl === '' && !empty($title)) {
	$currentLbl = (string)$title;
}
$showSlash = $parentLbl !== '';
?>
<header class="pgm-app-topbar pgm-app-topbar--erp-mock" role="banner">
	<div class="pgm-app-topbar__left">
		<?php if ($showSlash) : ?>
			<nav class="pgm-app-topbar__crumb" aria-label="<?= h(__('Navegação secundária')) ?>">
				<span class="pgm-app-topbar__parent"><?= h($parentLbl) ?></span>
				<span class="pgm-app-topbar__sep"> / </span>
				<strong class="pgm-app-topbar__current"><?= h($currentLbl) ?></strong>
			</nav>
		<?php else : ?>
			<div class="pgm-app-topbar__crumb">
				<strong class="pgm-app-topbar__current"><?= h($currentLbl) ?></strong>
			</div>
		<?php endif; ?>
	</div>
	<div class="pgm-app-topbar__right">
		<?= $this->element('ErpPrototype/topbar_portal_actions', ['erpEmpresas' => (array)($erpEmpresas ?? [])]) ?>
	</div>
</header>
