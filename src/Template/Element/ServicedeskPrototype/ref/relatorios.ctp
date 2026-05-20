<?php
/**
 * Relatórios SD — snapshot operacional + atalhos.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 * @var array<string,mixed> $charts
 */
$kpis = (array)($screen['kpis'] ?? []);
$links = (array)($screen['links'] ?? []);
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Relatórios')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">📊 <?= h((string)($screen['title'] ?? 'Relatórios')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<?= $this->Html->link('← ' . __('Service Desk'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) :
				$alert = !empty($k['alert']);
			?>
				<div class="summary-card" style="border-left:3px solid <?= $alert ? 'var(--red)' : 'var(--teal)' ?>;<?= $alert ? 'background:#F8D8DA;' : '' ?>">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:<?= $alert ? '#7A1822' : 'var(--teal-dark)' ?>;"><?= h((string)($k['val'] ?? '')) ?></div>
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="card">
		<div class="sec-title"><?= h(__('Relatórios disponíveis')) ?></div>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:10px;">
			<?php foreach ($links as $l) : ?>
				<?php if (!empty($l['url'])) : ?>
					<?= $this->Html->link((string)($l['label'] ?? ''), $l['url'], ['class' => 'btn btn-ghost btn-sm']) ?>
				<?php endif; ?>
			<?php endforeach; ?>
			<?= $this->Html->link('📈 ' . __('Dashboard executivo'), ['controller' => 'ServicedeskPrototype', 'action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('🗂 ' . __('Fila técnica'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-primary btn-sm']) ?>
		</div>
	</div>
</div>
