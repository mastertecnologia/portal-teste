<?php
/**
 * Topbar premium — breadcrumb à esquerda; data + avatar à direita.
 * Opcionais: $topbarParentLabel + $topbarCurrentLabel (formato "Pai / **Atual**").
 * Caso contrário: tenta Breadcrumbs->render(); fallback: $title.
 *
 * @var string|null $name Nome do utilizador (AppController)
 * @var string|null $title Título da página
 * @var string|null $topbarParentLabel
 * @var string|null $topbarCurrentLabel
 */
use Cake\I18n\Time;

$userName = isset($name) ? (string) $name : '';
$initials = '';
if ($userName !== '') {
    $parts = preg_split('/\s+/u', trim($userName), -1, PREG_SPLIT_NO_EMPTY);
    if ($parts !== false && $parts !== []) {
        $initials .= mb_strtoupper(mb_substr($parts[0], 0, 1));
        if (count($parts) > 1) {
            $initials .= mb_strtoupper(mb_substr($parts[count($parts) - 1], 0, 1));
        }
    }
}
$parentLbl = isset($topbarParentLabel) ? trim((string) $topbarParentLabel) : '';
$currentLbl = isset($topbarCurrentLabel) ? trim((string) $topbarCurrentLabel) : '';
if ($currentLbl === '' && !empty($title)) {
    $currentLbl = (string) $title;
}
$showSlash = $parentLbl !== '';
$dateStr = '';
try {
    $dateStr = Time::now()->format('d/m/Y');
} catch (\Throwable $e) {
    $dateStr = date('d/m/Y');
}
?>
<header class="pgm-app-topbar" role="banner">
	<div class="pgm-app-topbar__left">
		<?php if ($showSlash) : ?>
			<nav class="pgm-app-topbar__crumb" aria-label="<?= __('Navegação secundária') ?>">
				<span class="pgm-app-topbar__parent"><?= h($parentLbl) ?></span>
				<span class="pgm-app-topbar__sep"> / </span>
				<strong class="pgm-app-topbar__current"><?= h($currentLbl) ?></strong>
			</nav>
		<?php else : ?>
			<div class="pgm-app-topbar__crumb">
				<?php
				$bcHtml = '';
				try {
					$bcHtml = $this->Breadcrumbs->render();
				} catch (\Throwable $e) {
					$bcHtml = '';
				}
				$bcPlain = trim(strip_tags($bcHtml));
				if ($bcPlain !== '') {
					echo $bcHtml;
				} else {
					echo '<strong class="pgm-app-topbar__current">' . h($currentLbl) . '</strong>';
				}
				?>
			</div>
		<?php endif; ?>
	</div>
	<div class="pgm-app-topbar__right">
		<span class="pgm-app-topbar__date" id="pgm-app-topbar-date"><?= h($dateStr) ?></span>
		<?php if ($initials !== '') : ?>
			<div class="pgm-app-topbar__avatar" title="<?= h($userName) ?>"><?= h($initials) ?></div>
		<?php endif; ?>
	</div>
</header>
