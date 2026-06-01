<?php
/** @var array<string,array<int,array<string,mixed>>> $licRenovacoes */
$pipe = (array)($licRenovacoes ?? []);
$cols = [
	'vencido' => ['title' => __('Vencidas'), 'color' => 'var(--red)'],
	'd30' => ['title' => __('0–30 dias'), 'color' => 'var(--amber)'],
	'd60' => ['title' => __('31–60 dias'), 'color' => 'var(--blue)'],
	'd90' => ['title' => __('61–90 dias'), 'color' => 'var(--teal)'],
];
?>
<div style="margin-bottom:14px;">
	<h1 style="font-size:22px;font-weight:600;margin:0;">🔄 <?= h(__('Pipeline de renovações')) ?></h1>
	<p style="font-size:12px;color:var(--text-muted);"><?= h(__('Licenças ativas/rascunho com data de fim nos próximos 90 dias.')) ?></p>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;">
	<?php foreach ($cols as $key => $meta) : $items = (array)($pipe[$key] ?? []); ?>
	<div class="card">
		<div style="font-weight:600;margin-bottom:10px;color:<?= h($meta['color']) ?>"><?= h($meta['title']) ?> (<?= count($items) ?>)</div>
		<?php if ($items === []) : ?>
		<p style="font-size:12px;color:var(--text-muted);margin:0;"><?= h(__('Nenhuma')) ?></p>
		<?php else : foreach ($items as $it) : ?>
		<div style="padding:8px 0;border-bottom:1px solid var(--border-light);font-size:12px;">
			<strong><?= h($it['codigo']) ?></strong><br>
			<?= h($it['cliente']) ?><br>
			<span style="color:var(--text-muted);"><?= h($it['fim']) ?></span>
			<?= $this->Html->link(__('Abrir'), ['action' => 'licencaDetalhe', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs', 'style' => 'margin-top:4px;']) ?>
		</div>
		<?php endforeach; endif; ?>
	</div>
	<?php endforeach; ?>
</div>
