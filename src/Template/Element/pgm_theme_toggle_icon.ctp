<?php
/**
 * Toggle tema claro/escuro — botão só ícone (sol/lua), alinhado ao sino.
 * @var \App\View\AppView $this
 */
$isLightToggle = (($skin ?? '') === 'skin-pgm-light');
?>
<button type="button" class="pgm-theme-toggle-btn pgm-theme-toggle-btn--icon pgm-js-theme-toggle" id="pgmThemeToggle"
	title="<?= $isLightToggle ? 'Mudar para tema escuro' : 'Mudar para tema claro' ?>"
	aria-pressed="<?= $isLightToggle ? 'true' : 'false' ?>"
	aria-label="<?= $isLightToggle ? 'Tema claro ativo. Ativar escuro' : 'Tema escuro ativo. Ativar claro' ?>"
	data-current="<?= $isLightToggle ? 'light' : 'dark' ?>">
	<span class="pgm-tt-icon"><?= $isLightToggle ? '<i class="fas fa-sun" aria-hidden="true"></i>' : '<i class="fas fa-moon" aria-hidden="true"></i>' ?></span>
</button>
