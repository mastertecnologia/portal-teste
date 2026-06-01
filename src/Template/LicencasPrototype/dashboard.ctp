<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,int> $licKpi
 * @var bool $licMigrationHint
 * @var array<string,array<string,string>> $licPages
 */
$k = (array)($licKpi ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM ERP › <?= h(__('Licenciamento')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🔑 <?= h(__('PGM Licenças — Painel')) ?></h1>
	</div>
</div>

<?php if (!empty($licMigrationHint)) : ?>
<div class="alert alert-warn" style="margin-bottom:14px;">
	<?= h(__('Execute a migration do módulo: bin/cake migrations migrate (LicModuleFoundation).')) ?>
</div>
<?php endif; ?>

<div class="stats" style="margin-bottom:14px;">
	<div class="stat"><div class="stat-l"><?= h(__('Licenças ativas')) ?></div><div class="stat-n"><?= (int)($k['licencas_ativas'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Assentos')) ?></div><div class="stat-n"><?= (int)($k['assentos'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Vencem em 30 dias')) ?></div><div class="stat-n"><?= (int)($k['venc_30'] ?? 0) ?></div></div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
	<?php foreach ((array)($licPages ?? []) as $slug => $meta) :
		if ($slug === 'dashboard') {
			continue;
		}
		?>
	<div class="card" style="cursor:pointer;" onclick="location.href='<?= h($this->Url->build(['action' => 'view', $slug])) ?>'">
		<strong><?= h($meta['title'] ?? $slug) ?></strong>
	</div>
	<?php endforeach; ?>
</div>
