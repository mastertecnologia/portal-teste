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
			var lines = Object.keys(helpMap).map(function (key) { return 'g ' + key + ' = ' + helpMap[key]; });
			showToast('Atalhos:<br>' + lines.join('<br>'));
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
