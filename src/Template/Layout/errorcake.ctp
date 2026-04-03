<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of this file must retain the above copyright notice.
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?= $this->Html->charset() ?>
	<title>
		<?= $this->fetch('title') ?>
	</title>

	<?= $this->Html->meta('favicon.ico','/favicon.ico',['type' => 'icon']); ?>

	<?= $this->Html->css('/dist/css/pages/pgm-theme-tokens') ?>
	<?= $this->Html->css('base.css') ?>
	<?= $this->Html->css('cake.css') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-error-cake') ?>
	<script>
	(function () {
		var k = 'pgmPortalTheme';
		try {
			var v = localStorage.getItem(k);
			var t = (v === 'light' || v === 'dark') ? v : 'dark';
			document.documentElement.setAttribute('data-pgm-theme', t);
		} catch (e) {
			document.documentElement.setAttribute('data-pgm-theme', 'dark');
		}
	})();
	</script>

	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
	<?= $this->fetch('script') ?>
</head>
<body class="pgm-error-cake">
	<button type="button" class="pgm-error-cake-fab" id="pgmErrorThemeToggle"
		aria-label="Alternar tema claro ou escuro">
		<span class="pgm-tt-icon" aria-hidden="true">🌙</span>
		<span class="pgm-tt-label">Escuro</span>
	</button>
	<div id="container">
		<div id="header">
			<h1><?= __('Error') ?></h1>
		</div>
		<div id="content">
			<?= $this->Flash->render() ?>

			<?= $this->fetch('content') ?>
		</div>
		<div id="footer">
			<?= $this->Html->link(__('Back'), 'javascript:history.back()') ?>
		</div>
	</div>
	<script>
	(function () {
		var KEY = 'pgmPortalTheme';
		function write(v) {
			try { localStorage.setItem(KEY, v); } catch (e) {}
		}
		function syncButton(mode) {
			var btn = document.getElementById('pgmErrorThemeToggle');
			if (!btn) return;
			var L = mode === 'light';
			btn.setAttribute('aria-pressed', L ? 'true' : 'false');
			btn.setAttribute('title', L ? 'Mudar para tema escuro' : 'Mudar para tema claro');
			btn.setAttribute('aria-label', L ? 'Tema claro ativo. Ativar escuro' : 'Tema escuro ativo. Ativar claro');
			var ico = btn.querySelector('.pgm-tt-icon');
			var lbl = btn.querySelector('.pgm-tt-label');
			if (ico) ico.textContent = L ? '☀️' : '🌙';
			if (lbl) lbl.textContent = L ? 'Claro' : 'Escuro';
		}
		function apply(mode) {
			if (mode !== 'light' && mode !== 'dark') mode = 'dark';
			document.documentElement.setAttribute('data-pgm-theme', mode);
			syncButton(mode);
			write(mode);
		}
		var cur = document.documentElement.getAttribute('data-pgm-theme') || 'dark';
		syncButton(cur);
		document.getElementById('pgmErrorThemeToggle').addEventListener('click', function () {
			var c = document.documentElement.getAttribute('data-pgm-theme') || 'dark';
			apply(c === 'dark' ? 'light' : 'dark');
		});
	})();
	</script>
</body>
</html>
