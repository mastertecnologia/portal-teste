<?php
/**
 * Topbar — seletor de empresa + sino (lista Clientes CRM).
 *
 * @var array<int,array{id:int,nome:string,cnpj?:string,initials?:string,current?:bool}> $pgmTopbarEmpresas
 */
$empresas = (array)($pgmTopbarEmpresas ?? []);
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
$siglaFn = static function (string $nome): string {
	$parts = preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY);
	if ($parts === false || $parts === []) {
		return 'PG';
	}
	$a = mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
	$b = count($parts) > 1 ? mb_strtoupper(mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8')) : '';

	return $a . $b;
};
$curNome = (string)($current['nome'] ?? '');
$curCnpj = (string)($current['cnpj'] ?? '');
$curIni = (string)($current['initials'] ?? $siglaFn($curNome !== '' ? $curNome : 'PGM'));
?>
<?php if ($empresas !== []) : ?>
<div class="pgm-topbar-emp" id="pgm-topbar-emp">
	<button type="button" class="pgm-topbar-emp-btn" id="pgm-topbar-emp-btn" aria-expanded="false" aria-haspopup="listbox">
		<span class="pgm-topbar-emp-logo" aria-hidden="true"><?= h($curIni) ?></span>
		<span class="pgm-topbar-emp-text">
			<span class="pgm-topbar-emp-name"><?= h($curNome) ?></span>
			<?php if ($curCnpj !== '') : ?>
				<span class="pgm-topbar-emp-cnpj"><?= h($curCnpj) ?></span>
			<?php endif; ?>
			<span class="pgm-topbar-emp-sub"><?= h(__('Matriz')) ?></span>
		</span>
		<i class="fas fa-chevron-down pgm-topbar-emp-chev" aria-hidden="true"></i>
	</button>
	<div class="pgm-topbar-emp-menu" id="pgm-topbar-emp-menu" role="listbox" hidden>
		<?php foreach ($empresas as $e) :
			$id = (int)($e['id'] ?? 0);
			$nome = (string)($e['nome'] ?? '');
			$ini = (string)($e['initials'] ?? $siglaFn($nome));
			$cur = !empty($e['current']);
			$url = $this->Url->build([
				'controller' => 'Empresasusers',
				'action' => 'switchempresa',
				$id,
				'?' => ['redirect' => (string)$this->request->getRequestTarget()],
			]);
		?>
		<a href="<?= h($url) ?>" class="pgm-topbar-emp-item<?= $cur ? ' is-active' : '' ?>" role="option">
			<span class="pgm-topbar-emp-logo"><?= h($ini) ?></span>
			<span class="pgm-topbar-emp-name"><?= h($nome) ?></span>
		</a>
		<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>
<div class="pgm-topbar-notif-wrap">
	<?= $this->element('portal_notification_bell') ?>
</div>
<script>
(function () {
	var btn = document.getElementById('pgm-topbar-emp-btn');
	var menu = document.getElementById('pgm-topbar-emp-menu');
	if (!btn || !menu) return;
	btn.addEventListener('click', function (e) {
		e.stopPropagation();
		var open = menu.hidden;
		menu.hidden = !open;
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
	});
	document.addEventListener('click', function () {
		menu.hidden = true;
		btn.setAttribute('aria-expanded', 'false');
	});
})();
</script>
