<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />

	<?= $this->Html->meta('favicon.ico','/favicon.ico',['type' => 'icon']); ?>

	<title>We've got some trouble | {{code}} - {{title}}</title>

	<?= $this->Html->css('/dist/css/pages/pgm-theme-tokens') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-components-base') ?>
	<?= $this->Html->css('/dist/css/pages/pgm-error-http') ?>
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
</head>

<body class="pgm-error-http">
	<button type="button" class="pgm-error-theme-fab" id="pgmErrorThemeToggle"
		aria-label="Alternar tema claro ou escuro">
		<span class="pgm-tt-icon" aria-hidden="true">🌙</span>
		<span class="pgm-tt-label">Escuro</span>
	</button>
	<div class="cover">
		<?= $this->element('content'); ?>
	</div>
	<script>
	(function () {
		var KEY = 'pgmPortalTheme';
		function read() {
			try { return localStorage.getItem(KEY); } catch (e) { return null; }
		}
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
