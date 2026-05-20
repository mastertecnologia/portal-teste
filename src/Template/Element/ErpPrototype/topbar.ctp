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
</script>
