<?php
/**
 * @var \App\View\AppView $this
 * @var string $page
 * @var array<string,string> $pageMeta
 * @var bool $licMigrationHint
 */
$title = (string)($pageMeta['title'] ?? ucfirst((string)$page));
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Licenciamento · Protótipo')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h($title) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Painel'), ['action' => 'dashboard'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?php if (!empty($licMigrationHint)) : ?>
<div class="alert alert-warn" style="margin-bottom:14px;"><?= h(__('Aguardando migration lic_* — ver PLANO_DESENVOLVIMENTO_MIGRACAO_PGM_ERP_2026.md')) ?></div>
<?php endif; ?>

<div class="card" style="text-align:center;padding:48px 22px;">
	<div style="font-size:48px;margin-bottom:14px;">🚧</div>
	<h2 style="font-size:18px;margin-bottom:8px;"><?= h(__('Em desenvolvimento')) ?></h2>
	<p style="color:var(--text-muted);"><?= h(__('Rota registrada; implementação conforme plano L1–L5.')) ?></p>
</div>
