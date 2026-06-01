<?php /** @var array<string,string> $licRelatorioTipos @var array<string,int> $licKpi */ ?>
<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;">📊 <?= h(__('Relatórios')) ?></h1>
<div class="stats" style="margin-bottom:14px;">
	<div class="stat"><div class="stat-l"><?= h(__('Licenças ativas')) ?></div><div class="stat-n"><?= (int)($licKpi['licencas_ativas'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Dispositivos')) ?></div><div class="stat-n"><?= (int)($licKpi['dispositivos'] ?? 0) ?></div></div>
</div>
<div class="card">
	<p style="font-size:13px;margin-bottom:12px;"><?= h(__('Exportação CSV (UTF-8, separador ;).')) ?></p>
	<div style="display:flex;flex-wrap:wrap;gap:8px;">
		<?php foreach ((array)($licRelatorioTipos ?? []) as $slug => $label) : ?>
		<?= $this->Html->link('⬇ ' . h($label), ['action' => 'exportarRelatorio', $slug], ['class' => 'btn btn-primary btn-sm']) ?>
		<?php endforeach; ?>
	</div>
</div>
