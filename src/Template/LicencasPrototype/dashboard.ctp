<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,int> $licKpi
 * @var bool $licMigrationHint
 */
$k = (array)($licKpi ?? []);
$tiles = [
	['licencas', __('Licenças'), 'licencas'],
	['catalogo', __('Catálogo'), 'view'],
	['renovacoes', __('Renovações'), 'view'],
	['calendario', __('Calendário'), 'view'],
	['dispositivos', __('Dispositivos'), 'view'],
	['cofre', __('Cofre'), 'view'],
	['solicitacoes', __('Solicitações'), 'view'],
	['auditoria', __('Auditoria'), 'view'],
	['config', __('Configurações'), 'view'],
	['inteligencia', __('Inteligência'), 'view'],
	['relatorios', __('Relatórios'), 'view'],
];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM ERP › <?= h(__('Licenciamento')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🔑 <?= h(__('PGM Licenças — Painel')) ?></h1>
	</div>
	<?= $this->Html->link('+ ' . __('Nova licença'), ['action' => 'view', 'nova'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>

<?php if (!empty($licMigrationHint)) : ?>
<div class="alert alert-warn" style="margin-bottom:14px;">
	<?= h(__('Execute: bin/cake migrations migrate')) ?>
</div>
<?php endif; ?>

<div class="stats" style="margin-bottom:14px;">
	<div class="stat"><div class="stat-l"><?= h(__('Licenças ativas')) ?></div><div class="stat-n"><?= (int)($k['licencas_ativas'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Assentos')) ?></div><div class="stat-n"><?= (int)($k['assentos'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Vencem em 30 dias')) ?></div><div class="stat-n"><?= (int)($k['venc_30'] ?? 0) ?></div></div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
	<?php foreach ($tiles as [$slug, $label, $action]) :
		$url = $action === 'licencas'
			? ['action' => 'licencas']
			: ['action' => 'view', $slug];
		?>
	<a class="card" href="<?= h($this->Url->build($url)) ?>" style="text-decoration:none;color:inherit;display:block;">
		<strong><?= h($label) ?></strong>
	</a>
	<?php endforeach; ?>
</div>
