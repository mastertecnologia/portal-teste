<?php
/**
 * Topbar premium compartilhada — breadcrumb à esquerda + ações (seletor empresa,
 * notificações, data, avatar) à direita.
 *
 * @var \App\View\AppView $this
 * @var array<int,array{label:string,url?:array}> $erpBreadcrumb Ex.: [['label'=>'Service Desk'],['label'=>'Fila','cur'=>true]]
 * @var array<int,array{id:int,nome:string,cnpj?:string,sigla?:string,cor?:string,current?:bool}> $erpEmpresas
 */
$session = $this->getRequest()->getSession();
$breadcrumb = (array)($erpBreadcrumb ?? []);
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
<div class="topbar">
	<div class="topbar-left">
		<?php if ($breadcrumb !== []) : ?>
			<?php foreach ($breadcrumb as $i => $crumb) :
				$label = (string)($crumb['label'] ?? '');
				$isLast = $i === count($breadcrumb) - 1;
				$isCur = !empty($crumb['cur']) || $isLast;
				if (!$isCur && !empty($crumb['url'])) :
					?>
					<a href="<?= h($this->Url->build($crumb['url'])) ?>" style="color:inherit;text-decoration:none;"><?= h($label) ?></a>
				<?php else : ?>
					<span class="<?= $isCur ? 'cur' : '' ?>"><?= h($label) ?></span>
				<?php endif; ?>
				<?php if (!$isLast) : ?>
					<span class="sep">›</span>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<div class="topbar-actions">
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

		<?php
		$erpLoc = (string)$session->read('Erp.locale');
		if (!in_array($erpLoc, ['pt_BR', 'en_US', 'es'], true)) {
			$erpLoc = 'pt_BR';
		}
		$locRedirect = \App\Utility\PortalUrlPath::sanitizeInternalRedirect($this->getRequest()->getRequestTarget()) ?? '';
		$locUrl = static function (string $code) use ($locRedirect) {
			$params = ['controller' => 'PrototypeHistory', 'action' => 'setLocale', $code];
			if ($locRedirect !== '') {
				$params['?'] = ['redirect' => $locRedirect];
			}

			return $params;
		};
		?>
		<div style="display:flex;align-items:center;gap:2px;font-size:11px;">
			<a href="<?= h($this->Url->build($locUrl('pt_BR'))) ?>" class="btn btn-ghost btn-xs" style="padding:4px 7px;<?= $erpLoc === 'pt_BR' ? 'font-weight:700;color:var(--teal);' : '' ?>" title="Português">PT</a>
			<a href="<?= h($this->Url->build($locUrl('en'))) ?>" class="btn btn-ghost btn-xs" style="padding:4px 7px;<?= $erpLoc === 'en_US' ? 'font-weight:700;color:var(--teal);' : '' ?>" title="English">EN</a>
			<a href="<?= h($this->Url->build($locUrl('es'))) ?>" class="btn btn-ghost btn-xs" style="padding:4px 7px;<?= $erpLoc === 'es' ? 'font-weight:700;color:var(--teal);' : '' ?>" title="Español">ES</a>
		</div>
		<button type="button" id="pgm-theme-toggle" class="btn btn-ghost btn-xs" onclick="pgmToggleErpTheme()" title="<?= h(__('Alternar tema')) ?>" style="padding:4px 8px;font-size:16px;line-height:1;">🌙</button>
		<div class="topbar-sep"></div>
		<div style="position:relative;" id="pgm-notif-wrap">
			<button type="button" id="pgm-notif-btn" style="background:none;border:none;cursor:pointer;padding:6px;position:relative;font-size:18px;">
				🔔
				<span id="pgm-notif-badge" style="display:none;position:absolute;top:2px;right:0;background:var(--red);color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;min-width:14px;text-align:center;">0</span>
			</button>
			<div id="pgm-notif-panel" style="display:none;position:absolute;top:calc(100% + 8px);right:0;background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:0 8px 24px rgba(0,0,0,.15);width:340px;max-height:420px;overflow-y:auto;z-index:1000;">
				<div style="padding:10px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:flex;justify-content:space-between;">
					<span>🔔 <?= h(__('Notificações recentes')) ?></span>
					<span id="pgm-notif-count" style="font-weight:400;">0</span>
				</div>
				<div id="pgm-notif-list" style="padding:6px 0;">
					<div style="padding:14px;text-align:center;color:var(--text-muted);font-size:12px;"><?= h(__('Carregando...')) ?></div>
				</div>
			</div>
		</div>
		<div class="topbar-sep"></div>
		<span style="font-size:11px;color:var(--text-muted);"><?= h(date('d/m/Y')) ?></span>
		<div class="topbar-sep"></div>
		<?php
		$userName = trim((string)$session->read('Auth.User.name')) ?: (string)$session->read('Auth.User.username');
		$initials = '?';
		if ($userName !== '') {
			$p = preg_split('/\s+/', trim($userName));
			$initials = strtoupper(substr((string)($p[0] ?? ''), 0, 1) . substr((string)($p[1] ?? ''), 0, 1)) ?: strtoupper(substr($userName, 0, 2));
		}
		?>
		<div class="user-av" style="width:28px;height:28px;font-size:10px;" aria-hidden="true"><?= h($initials) ?></div>
	</div>
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

// === Sino de notificações ===
(function () {
	var btn = document.getElementById('pgm-notif-btn');
	var panel = document.getElementById('pgm-notif-panel');
	var list = document.getElementById('pgm-notif-list');
	var badge = document.getElementById('pgm-notif-badge');
	var countLabel = document.getElementById('pgm-notif-count');
	if (!btn) return;
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
					countLabel.textContent = data.count + ' nos últimos 7 dias';
				}
				if (!loaded) {
					if (data.items.length === 0) {
						list.innerHTML = '<div style="padding:24px;text-align:center;color:#6b6a65;font-size:12px;">📭 Nenhuma alteração recente.</div>';
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
	// Refresh badge a cada 60s sem abrir painel
	loadNotifs();
	setInterval(function () { loaded = false; loadNotifs(); }, 60000);
})();
</script>
