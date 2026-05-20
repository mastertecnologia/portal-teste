<?php
/**
 * SLA & Config — Workflow States cadastrados (real) + atalhos para config legado.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$items = (array)($screen['items'] ?? []);
$kpis = (array)($screen['kpis'] ?? []);
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Configuração')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">⚙ <?= h(__('SLA & Configurações')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Workflow states, políticas SLA, automações e templates')) ?></div>
		</div>
		<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) : ?>
				<div class="summary-card" style="border-left:3px solid var(--teal);">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:var(--teal-dark);"><?= h((string)($k['val'] ?? '')) ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="card" style="padding:0;overflow:hidden;">
		<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);"><strong><?= h(__('Workflow States')) ?></strong></div>
		<div style="overflow-x:auto;">
			<table class="tbl" style="margin:0;">
				<thead><tr><th><?= h(__('Nome')) ?></th><th><?= h(__('Código')) ?></th><th><?= h(__('Inicial?')) ?></th><th><?= h(__('Categoria')) ?></th><th></th></tr></thead>
				<tbody>
					<?php if ($items === []) : ?>
						<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Sem estados de workflow cadastrados.')) ?></td></tr>
					<?php else : foreach ($items as $it) : ?>
						<tr>
							<td><strong><?= h((string)$it['col1']) ?></strong></td>
							<td style="font-family:monospace;font-size:11px;color:var(--teal-dark);"><?= h((string)$it['col2']) ?></td>
							<td><?php if ((string)$it['col3'] === 'Sim') : ?><span class="badge b-paga"><?= h(__('Inicial')) ?></span><?php else : ?>—<?php endif; ?></td>
							<td><span class="badge b-aprov"><?= h((string)$it['col4']) ?></span></td>
							<td class="r"><?= $this->Html->link(__('Editar'), $it['link'] ?? ['controller' => 'Servicedesk', 'action' => 'workflowSlaAdmin'], ['class' => 'btn btn-ghost btn-xs']) ?></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="card" style="margin-top:14px;">
		<div class="sec-title"><?= h(__('Atalhos de configuração')) ?></div>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
			<?= $this->Html->link('🛡 ' . __('Workflow SLA admin'), ['controller' => 'Servicedesk', 'action' => 'workflowSlaAdmin'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('📋 ' . __('Filas / Queues'), ['controller' => 'Queues', 'action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('📑 ' . __('Workflow SLA logs'), ['controller' => 'Servicedesk', 'action' => 'workflowSlaLogs'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('🎯 ' . __('SLA relatório'), '/servicedesk/sla-relatorio', ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</div>
</div>
