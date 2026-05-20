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
$w = $this->getRequest()->getAttribute('webroot');
$csrf = $this->getRequest()->getAttribute('csrfToken');
if (!$csrf && method_exists($this->getRequest(), 'getParam')) {
	$csrf = $this->getRequest()->getParam('_csrfToken');
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-pgm-theme="light">
<head>
	<?= $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if ($csrf) : ?>
		<meta name="csrfToken" content="<?= h($csrf) ?>">
	<?php endif; ?>
	<title><?= h($title ?? 'PGM ERP') ?> — PGM</title>
	<?= $this->Html->meta('icon') ?>
	<?= $this->Html->css($w . 'dist/css/style.min') ?>
	<?= $this->Html->css($w . 'dist/css/pgm-erp-prototype.css') ?>
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
<script>
(function () {
	// === Atalhos de teclado: g+f fila SD, g+a aprovações, g+o orçamentos, g+d dashboard SD, g+h home, ? mostra ajuda ===
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
	function showHelpModal () {
		var m = document.getElementById('pgm-help-modal');
		if (m) { m.style.display = 'flex'; return; }
		m = document.createElement('div');
		m.id = 'pgm-help-modal';
		m.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;display:flex;align-items:center;justify-content:center;padding:24px;';
		var rows = Object.keys(helpMap).map(function (k) {
			return '<tr><td style="padding:6px 14px;font-family:monospace;font-size:13px;color:#1D9E75;font-weight:600;text-align:center;">g ' + k + '</td><td style="padding:6px 14px;color:#1a1a18;">' + helpMap[k] + '</td></tr>';
		}).join('');
		m.innerHTML = '' +
			'<div style="background:#fff;border-radius:16px;max-width:520px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;">' +
			'  <div style="padding:18px 22px;border-bottom:1px solid #e5e4e0;display:flex;justify-content:space-between;align-items:center;">' +
			'    <strong style="font-size:16px;">⌨️ Atalhos de teclado</strong>' +
			'    <button type="button" onclick="document.getElementById(\'pgm-help-modal\').style.display=\'none\'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#6b6a65;">×</button>' +
			'  </div>' +
			'  <div style="padding:6px 0;max-height:60vh;overflow-y:auto;">' +
			'    <table style="width:100%;border-collapse:collapse;">' +
			'      <thead><tr style="background:#f9f9f8;"><th style="padding:8px 14px;text-align:center;font-size:11px;color:#6b6a65;text-transform:uppercase;letter-spacing:.4px;">Tecla</th><th style="padding:8px 14px;text-align:left;font-size:11px;color:#6b6a65;text-transform:uppercase;letter-spacing:.4px;">Vai para</th></tr></thead>' +
			'      <tbody>' + rows + '</tbody>' +
			'    </table>' +
			'    <div style="padding:14px 22px;border-top:1px solid #f0efec;font-size:11px;color:#6b6a65;line-height:1.6;">' +
			'      <strong>Como usar:</strong> aperte <code style="background:#f2f1ee;padding:2px 6px;border-radius:4px;">g</code> e depois a próxima tecla (dentro de 1,2s). Não funciona enquanto você digita em campos.' +
			'    </div>' +
			'  </div>' +
			'</div>';
		m.addEventListener('click', function (e) { if (e.target === m) m.style.display = 'none'; });
		document.body.appendChild(m);
	}
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			var m = document.getElementById('pgm-help-modal');
			if (m) m.style.display = 'none';
		}
	});

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

	// === Long-poll badges ===
	var url = <?= json_encode($this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'apiBadges'])) ?>;
	function refreshBadges () {
		fetch(url, {credentials: 'same-origin'})
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (data) {
				if (!data || !data.ok || !data.badges) return;
				Object.keys(data.badges).forEach(function (k) {
					var n = parseInt(data.badges[k], 10) || 0;
					document.querySelectorAll('[data-nav-badge="' + k + '"]').forEach(function (el) {
						el.textContent = String(n);
						el.style.display = n > 0 ? '' : 'none';
					});
				});
			})
			.catch(function () {});
	}
	// primeiro poll após 5s (deixa página carregar) e depois a cada 30s
	setTimeout(refreshBadges, 5000);
	setInterval(refreshBadges, 30000);
})();
</script>
</body>
</html>
