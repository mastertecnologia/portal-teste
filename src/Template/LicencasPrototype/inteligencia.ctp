<?php
/** @var array<string,mixed> $licInteligencia */
$data = (array)($licInteligencia ?? []);
$kpis = (array)($data['kpis'] ?? []);
$insights = (array)($data['insights'] ?? []);
$sevClass = ['danger' => '#FEE2E2', 'warn' => '#FEF3C7', 'ok' => '#D1FAE5', 'info' => '#DBEAFE'];
?>
<div style="display:flex;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🤖 <?= h(__('Inteligência & Insights')) ?></h1>
		<p style="font-size:12px;color:var(--text-muted);margin:4px 0 0;"><?= h(__('Regras automáticas sobre dados lic_* (sem serviços externos).')) ?></p>
	</div>
	<?= $this->Html->link('← ' . __('Painel'), ['action' => 'dashboard'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>
<div class="stats" style="margin-bottom:14px;">
	<div class="stat"><div class="stat-l"><?= h(__('Custo anual (est.)')) ?></div><div class="stat-n">R$ <?= h(number_format((float)($kpis['custo_anual_estimado'] ?? 0), 2, ',', '.')) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Vencidas')) ?></div><div class="stat-n"><?= (int)($kpis['vencidas'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Vencem 30d')) ?></div><div class="stat-n"><?= (int)($kpis['venc_30'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Assentos ociosos')) ?></div><div class="stat-n"><?= (int)($kpis['assentos_ociosos'] ?? 0) ?></div></div>
</div>
<div class="card">
	<div class="sec-title"><?= h(__('Insights detectados')) ?></div>
	<?php foreach ($insights as $ins) :
		$bg = $sevClass[$ins['severity'] ?? 'info'] ?? '#f5f5f5';
		?>
	<div style="padding:12px;margin-bottom:10px;border-left:4px solid var(--teal);background:<?= h($bg) ?>;border-radius:6px;">
		<strong><?= h($ins['title'] ?? '') ?></strong>
		<p style="font-size:12px;margin:6px 0 8px;"><?= h($ins['detail'] ?? '') ?></p>
		<?php if (!empty($ins['url'])) : ?>
		<?= $this->Html->link(__('Ver'), $ins['url'], ['class' => 'btn btn-ghost btn-xs']) ?>
		<?php endif; ?>
	</div>
	<?php endforeach; ?>
</div>
