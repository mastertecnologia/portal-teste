<?php
/**
 * Ações da topbar ERP (empresa, idioma, tema, notificações, data, avatar) — embed no layout portal.
 *
 * @var \App\View\AppView $this
 * @var array<int,array{id:int,nome:string,cnpj?:string,sigla?:string,cor?:string,current?:bool}> $erpEmpresas
 */
$session = $this->getRequest()->getSession();
$empresas = (array)($erpEmpresas ?? []);
$current = null;
foreach ($empresas as $e) {
	if (!empty($e['current'])) {
		$current = $e;
		break;
	}
}
if ($current === null && $empresas !== []) {
	$current = $empresas[0];
}
$sigla = static function (string $nome): string {
	$parts = preg_split('/\s+/', trim($nome));

	return strtoupper(substr((string)($parts[0] ?? ''), 0, 3));
};
$currentSigla = $current['sigla'] ?? ($current ? $sigla((string)$current['nome']) : 'PGM');
$currentNome = $current['nome'] ?? 'PGM Soluções';
$currentCnpj = $current['cnpj'] ?? '';
?>
<div class="pgm-erp-topbar-actions">
	<?php if ($empresas !== []) : ?>
		<div class="emp-selector" onclick="erpToggleEmpresaDropdown(event)" role="button" tabindex="0" aria-haspopup="listbox">
			<div class="emp-logo" style="<?= isset($current['cor']) ? 'background:' . h($current['cor']) . ';' : '' ?>"><?= h($currentSigla) ?></div>
			<div>
				<div class="emp-nome"><?= h($currentNome) ?></div>
				<?php if ($currentCnpj !== '') : ?>
					<div class="emp-cnpj"><?= h($currentCnpj) ?></div>
				<?php endif; ?>
			</div>
			<svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
				<path d="M2 3.5L5 6.5 8 3.5"/>
			</svg>
			<div class="emp-dropdown" id="erp-emp-dropdown" role="listbox">
				<div class="emp-dropdown-header">🏢 <?= h(__('Trocar empresa ativa')) ?></div>
				<div class="emp-dropdown-body">
					<?php foreach ($empresas as $e) :
						$id = (int)($e['id'] ?? 0);
						$nome = (string)($e['nome'] ?? '');
						$cnpj = (string)($e['cnpj'] ?? '');
						$sg = (string)($e['sigla'] ?? $sigla($nome));
						$cor = (string)($e['cor'] ?? '#0a3d2c');
						$cur = !empty($e['current']);
						$url = $this->Url->build([
							'controller' => 'Empresasusers',
							'action' => 'switchempresa',
							$id,
							'?' => ['redirect' => (string)$this->getRequest()->getRequestTarget()],
						]);
						?>
						<a href="<?= h($url) ?>" class="emp-item<?= $cur ? ' current' : '' ?>" role="option" aria-selected="<?= $cur ? 'true' : 'false' ?>" style="text-decoration:none;color:inherit;">
							<div class="emp-item-logo" style="background:<?= h($cor) ?>;"><?= h($sg) ?></div>
							<div class="emp-item-info">
								<strong><?= h($nome) ?></strong>
								<?php if ($cnpj !== '') : ?>
									<div class="sub"><?= h($cnpj) ?></div>
								<?php endif; ?>
							</div>
							<?php if ($cur) : ?>
								<span class="badge b-paga">✓ <?= h(__('Ativa')) ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<div class="topbar-sep"></div>
	<?php endif; ?>

	<div style="position:relative;" id="pgm-notif-wrap">
		<button type="button" id="pgm-notif-btn" class="pgm-erp-notif-btn" aria-label="<?= h(__('Notificações')) ?>">
			🔔
			<span id="pgm-notif-badge" class="pgm-erp-notif-badge">0</span>
		</button>
		<div id="pgm-notif-panel" class="pgm-erp-notif-panel">
			<div class="pgm-erp-notif-panel__head">
				<span>🔔 <?= h(__('Notificações recentes')) ?></span>
				<span id="pgm-notif-count">0</span>
			</div>
			<div id="pgm-notif-list" class="pgm-erp-notif-panel__body">
				<div style="padding:14px;text-align:center;color:var(--text-muted);font-size:12px;"><?= h(__('Carregando...')) ?></div>
			</div>
		</div>
	</div>
	<div class="topbar-sep"></div>
	<span class="pgm-erp-topbar-date"><?= h(date('d/m/Y')) ?></span>
	<div class="topbar-sep"></div>
	<?php
	$userName = trim((string)$session->read('Auth.User.name')) ?: (string)$session->read('Auth.User.username');
	$initials = '?';
	if ($userName !== '') {
		$p = preg_split('/\s+/', trim($userName));
		$initials = strtoupper(substr((string)($p[0] ?? ''), 0, 1) . substr((string)($p[1] ?? ''), 0, 1)) ?: strtoupper(substr($userName, 0, 2));
	}
	?>
	<div class="pgm-erp-topbar-avatar" aria-hidden="true" title="<?= h($userName) ?>"><?= h($initials) ?></div>
</div>
<script>
window.erpToggleEmpresaDropdown = window.erpToggleEmpresaDropdown || function (e) {
	if (e) { e.stopPropagation(); }
	var d = document.getElementById('erp-emp-dropdown');
	if (!d) return;
	d.classList.toggle('open');
};
document.addEventListener('click', function (e) {
	var d = document.getElementById('erp-emp-dropdown');
	if (!d || !d.classList.contains('open')) return;
	var sel = d.closest('.emp-selector');
	if (sel && !sel.contains(e.target)) {
		d.classList.remove('open');
	}
});
(function () {
	var btn = document.getElementById('pgm-notif-btn');
	var panel = document.getElementById('pgm-notif-panel');
	var list = document.getElementById('pgm-notif-list');
	var badge = document.getElementById('pgm-notif-badge');
	var countLabel = document.getElementById('pgm-notif-count');
	if (!btn || !panel) return;
	var url = <?= json_encode($this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'apiNotificacoes'])) ?>;
	var loaded = false;
	function loadNotifs () {
		fetch(url, {credentials: 'same-origin'})
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (data) {
				if (!data || !data.ok) return;
				if (data.count > 0) {
					badge.style.display = 'inline-block';
					badge.textContent = data.count;
					countLabel.textContent = String(data.count);
				} else {
					badge.style.display = 'none';
					countLabel.textContent = '0';
				}
				if (!loaded) {
					if (!data.items || data.items.length === 0) {
						list.innerHTML = '<div style="padding:24px;text-align:center;color:#6b6a65;font-size:12px;">📭 <?= h(__('Nenhuma alteração recente.')) ?></div>';
					} else {
						list.innerHTML = data.items.map(function (n) {
							var href = n.url || '#';
							var by = n.by ? ' · ' + n.by : '';
							return '<a href="' + href + '" style="display:flex;gap:10px;padding:10px 14px;border-bottom:1px solid #f0efec;text-decoration:none;color:#1a1a18;align-items:start;">' +
								'<div style="font-size:18px;line-height:1;">' + n.icon + '</div>' +
								'<div style="flex:1;min-width:0;">' +
								'  <div style="font-size:12px;font-weight:500;">' + n.title + '</div>' +
								'  <div style="font-size:11px;color:#6b6a65;">' + n.sub + '</div>' +
								'  <div style="font-size:10px;color:#9a9890;margin-top:2px;">' + n.at + by + '</div>' +
								'</div></a>';
						}).join('');
					}
					loaded = true;
				}
			});
	}
	btn.addEventListener('click', function (e) {
		e.stopPropagation();
		var open = panel.style.display !== 'none';
		panel.style.display = open ? 'none' : 'block';
		if (!open && !loaded) loadNotifs();
	});
	document.addEventListener('click', function (e) {
		var wrap = document.getElementById('pgm-notif-wrap');
		if (wrap && !wrap.contains(e.target)) panel.style.display = 'none';
	});
	loadNotifs();
	setInterval(function () { loaded = false; loadNotifs(); }, 60000);
})();
</script>
