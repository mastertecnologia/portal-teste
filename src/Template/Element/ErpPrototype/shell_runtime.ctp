<?php
/**
 * Runtime JS compartilhado: atalhos, badges, tema, j/k, PWA (erp_prototype + servicedesk_prototype).
 *
 * @var \App\View\AppView $this
 * @var string $pgmShellWebroot
 * @var string $pgmSwScope
 * @var bool $pgmShellPwa
 */
$w = (string)($pgmShellWebroot ?? $this->getRequest()->getAttribute('webroot'));
$scope = (string)($pgmSwScope ?? '/');
if ($scope === '' || $scope[0] !== '/') {
	$scope = '/';
}
$pwa = !isset($pgmShellPwa) || $pgmShellPwa;
?>
<script>
(function () {
	var leader = false;
	var leaderTo = null;
	var routes = {
		f: <?= json_encode($this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'fila'])) ?>,
		a: <?= json_encode($this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'aprovacoes'])) ?>,
		o: <?= json_encode($this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'lista'])) ?>,
		s: <?= json_encode($this->Url->build(['controller' => 'OrdensservicoPrototype', 'action' => 'lista'])) ?>,
		c: <?= json_encode($this->Url->build(['controller' => 'ClientesPrototype', 'action' => 'lista'])) ?>,
		p: <?= json_encode($this->Url->build(['controller' => 'ProdutosPrototype', 'action' => 'lista'])) ?>,
		d: <?= json_encode($this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'index'])) ?>,
		h: <?= json_encode($this->Url->build(['controller' => 'Users', 'action' => 'dashboard'])) ?>,
		b: <?= json_encode($this->Url->build(['controller' => 'FinanceiroPrototype', 'action' => 'lista'])) ?>
	};
	var helpMap = {f: 'Fila SD', a: 'Aprovações', o: 'Orçamentos', s: 'OS', c: 'Clientes', p: 'Produtos', d: 'Dashboard SD', h: 'Home', b: 'Financeiro'};
	var rowHelp = {j: 'Próxima linha', k: 'Linha anterior', Enter: 'Abrir registro'};

	function showToast (txt) {
		var t = document.getElementById('pgm-kbd-toast');
		if (!t) {
			t = document.createElement('div');
			t.id = 'pgm-kbd-toast';
			t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1a1a18;color:#fff;padding:10px 16px;border-radius:8px;font-size:12px;z-index:9999;box-shadow:0 4px 14px rgba(0,0,0,.2);opacity:0;transition:opacity .15s;';
			document.body.appendChild(t);
		}
		t.innerHTML = txt;
		t.style.opacity = '1';
		clearTimeout(t._h);
		t._h = setTimeout(function () { t.style.opacity = '0'; }, 1500);
	}

	function showHelpModal () {
		var m = document.getElementById('pgm-help-modal');
		if (m) { m.style.display = 'flex'; return; }
		m = document.createElement('div');
		m.id = 'pgm-help-modal';
		m.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;display:flex;align-items:center;justify-content:center;padding:24px;';
		var navRows = Object.keys(helpMap).map(function (k) {
			return '<tr><td style="padding:6px 14px;font-family:monospace;font-size:13px;color:#1D9E75;font-weight:600;text-align:center;">g ' + k + '</td><td style="padding:6px 14px;color:#1a1a18;">' + helpMap[k] + '</td></tr>';
		}).join('');
		var listRows = Object.keys(rowHelp).map(function (k) {
			return '<tr><td style="padding:6px 14px;font-family:monospace;font-size:13px;color:#1D9E75;font-weight:600;text-align:center;">' + k + '</td><td style="padding:6px 14px;color:#1a1a18;">' + rowHelp[k] + '</td></tr>';
		}).join('');
		m.innerHTML = '' +
			'<div style="background:#fff;border-radius:16px;max-width:520px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;color:#1a1a18;">' +
			'  <div style="padding:18px 22px;border-bottom:1px solid #e5e4e0;display:flex;justify-content:space-between;align-items:center;">' +
			'    <strong style="font-size:16px;">⌨️ Atalhos</strong>' +
			'    <button type="button" onclick="document.getElementById(\'pgm-help-modal\').style.display=\'none\'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#6b6a65;">×</button>' +
			'  </div>' +
			'  <div style="padding:6px 0;max-height:60vh;overflow-y:auto;">' +
			'    <div style="padding:8px 14px;font-size:11px;font-weight:600;color:#6b6a65;text-transform:uppercase;">Navegação rápida (g + tecla)</div>' +
			'    <table style="width:100%;border-collapse:collapse;margin-bottom:10px;"><tbody>' + navRows + '</tbody></table>' +
			'    <div style="padding:8px 14px;font-size:11px;font-weight:600;color:#6b6a65;text-transform:uppercase;">Listas (linha focada)</div>' +
			'    <table style="width:100%;border-collapse:collapse;"><tbody>' + listRows + '</tbody></table>' +
			'    <div style="padding:14px 22px;border-top:1px solid #f0efec;font-size:11px;color:#6b6a65;line-height:1.6;">' +
			'      <strong>g:</strong> seguido de outra tecla em até 1,2s. Não funciona em campos de formulário.' +
			'    </div>' +
			'  </div></div>';
		m.addEventListener('click', function (e) { if (e.target === m) m.style.display = 'none'; });
		document.body.appendChild(m);
	}

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			var hm = document.getElementById('pgm-help-modal');
			if (hm) hm.style.display = 'none';
		}
	});

	document.addEventListener('keydown', function (e) {
		var tag = (e.target && e.target.tagName) || '';
		if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable) return;
		if (e.ctrlKey || e.metaKey || e.altKey) return;
		var k = e.key.toLowerCase();
		if (k === '?') {
			showHelpModal();
			return;
		}
		if (k === 'g') {
			leader = true;
			showToast('g…');
			clearTimeout(leaderTo);
			leaderTo = setTimeout(function () { leader = false; }, 1200);
			return;
		}
		if (leader && routes[k]) {
			leader = false;
			clearTimeout(leaderTo);
			showToast('→ ' + helpMap[k]);
			window.location.href = routes[k];
		}
	});

	var badgesUrl = <?= json_encode($this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'apiBadges'])) ?>;
	function refreshBadges () {
		fetch(badgesUrl, {credentials: 'same-origin'})
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (data) {
				if (!data || !data.ok || !data.badges) return;
				Object.keys(data.badges).forEach(function (key) {
					var n = parseInt(data.badges[key], 10) || 0;
					document.querySelectorAll('[data-nav-badge="' + key + '"]').forEach(function (el) {
						el.textContent = String(n);
						el.style.display = n > 0 ? '' : 'none';
					});
				});
			})
			.catch(function () {});
	}
	setTimeout(refreshBadges, 5000);
	setInterval(refreshBadges, 30000);

	var THEME_KEY = 'pgm_erp_theme';
	function applyTheme (t) {
		document.documentElement.setAttribute('data-pgm-theme', t === 'dark' ? 'dark' : 'light');
		try { localStorage.setItem(THEME_KEY, t); } catch (err) {}
		var btn = document.getElementById('pgm-theme-toggle');
		if (btn) btn.textContent = t === 'dark' ? '☀️' : '🌙';
	}
	try {
		var saved = localStorage.getItem(THEME_KEY);
		if (saved === 'dark' || saved === 'light') applyTheme(saved);
	} catch (err) {}
	window.pgmToggleErpTheme = function () {
		var cur = document.documentElement.getAttribute('data-pgm-theme');
		applyTheme(cur === 'dark' ? 'light' : 'dark');
	};

	var rowIdx = -1;
	function rowNavList () {
		return Array.prototype.slice.call(document.querySelectorAll('tbody tr[data-pgm-row-href]'));
	}
	function rowNavFocus (idx) {
		var rows = rowNavList();
		if (!rows.length) return;
		idx = Math.max(0, Math.min(idx, rows.length - 1));
		rows.forEach(function (r) { r.classList.remove('pgm-row-focus'); });
		rowIdx = idx;
		rows[rowIdx].classList.add('pgm-row-focus');
		rows[rowIdx].scrollIntoView({block: 'nearest', behavior: 'smooth'});
	}
	document.addEventListener('keydown', function (e) {
		var tag = (e.target && e.target.tagName) || '';
		if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable) return;
		if (e.ctrlKey || e.metaKey || e.altKey) return;
		var rows = rowNavList();
		if (!rows.length) return;
		if (e.key === 'j' || e.key === 'k') e.preventDefault();
		if (e.key === 'j') rowNavFocus(rowIdx < 0 ? 0 : rowIdx + 1);
		else if (e.key === 'k') rowNavFocus(rowIdx < 0 ? 0 : rowIdx - 1);
		else if (e.key === 'Enter' && rowIdx >= 0) {
			var href = rows[rowIdx].getAttribute('data-pgm-row-href');
			if (href) window.location.href = href;
		}
	});

	<?php if ($pwa) : ?>
	var swPath = <?= json_encode($w . 'js/pgm-push-sw.js') ?>;
	var swScope = <?= json_encode($scope) ?>;
	if ('serviceWorker' in navigator) {
		navigator.serviceWorker.register(swPath, {scope: swScope}).catch(function () {});
	}
	var deferredInstall;
	window.addEventListener('beforeinstallprompt', function (e) {
		e.preventDefault();
		deferredInstall = e;
		var bar = document.getElementById('pgm-pwa-install');
		if (bar) bar.style.display = 'flex';
	});
	window.pgmPwaInstall = function () {
		if (!deferredInstall) return;
		deferredInstall.prompt();
		deferredInstall.userChoice.finally(function () {
			var bar = document.getElementById('pgm-pwa-install');
			if (bar) bar.style.display = 'none';
		});
	};
	<?php endif; ?>
})();
</script>
<?php if ($pwa) : ?>
<div id="pgm-pwa-install" style="display:none;position:fixed;bottom:16px;right:16px;z-index:9998;background:var(--bg-raised,#fff);border:1px solid var(--border,#e5e4e0);border-radius:12px;padding:12px 14px;box-shadow:0 8px 24px rgba(0,0,0,.15);align-items:center;gap:10px;max-width:320px;">
	<span style="font-size:12px;flex:1;">📲 <?= h(__('Instalar PGM ERP no dispositivo')) ?></span>
	<button type="button" class="btn btn-primary btn-xs" onclick="pgmPwaInstall()"><?= h(__('Instalar')) ?></button>
	<button type="button" class="btn btn-ghost btn-xs" onclick="this.parentElement.style.display='none'">×</button>
</div>
<?php endif; ?>
