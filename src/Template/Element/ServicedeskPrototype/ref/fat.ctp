<?php
/**
 * Faturamento de Service Desk — tickets que viram faturas.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$kpis = (array)($screen['kpis'] ?? []);
$rows = (array)($screen['rows'] ?? []);
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Financeiro')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">💵 <?= h(__('Faturamento de tickets')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Tickets resolvidos que entram em faturamento mensal de contrato')) ?></div>
		</div>
		<div style="display:flex;gap:8px;">
			<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('💵 ' . __('Contas a Receber'), ['controller' => 'FinanceiroPrototype', 'action' => 'titulos'], ['class' => 'btn btn-primary btn-sm']) ?>
		</div>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) : ?>
				<div class="summary-card" style="border-left:3px solid var(--teal);">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:var(--teal-dark);"><?= h((string)($k['val'] ?? '')) ?></div>
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ($rows !== []) : ?>
		<div class="card" style="padding:0;overflow:hidden;">
			<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);"><strong><?= h(__('Tickets a faturar (resolvidos no escopo)')) ?></strong></div>
			<div style="overflow-x:auto;">
				<table class="tbl" style="margin:0;">
					<thead><tr><th><?= h(__('ID')) ?></th><th><?= h(__('Cliente')) ?></th><th><?= h(__('Assunto')) ?></th><th><?= h(__('Técnico')) ?></th><th></th></tr></thead>
					<tbody>
						<?php foreach ($rows as $r) : ?>
							<tr>
								<td><strong>#<?= (int)($r['id'] ?? 0) ?></strong></td>
								<td><?= h((string)($r['cliente'] ?? '')) ?></td>
								<td><?= h(\Cake\Utility\Text::truncate((string)($r['assunto'] ?? ''), 70, ['ellipsis' => '…'])) ?></td>
								<td><?= h((string)($r['tecnico'] ?? '')) ?></td>
								<td class="r"><?= $this->Html->link(__('Abrir'), ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', (int)($r['id'] ?? 0)], ['class' => 'btn btn-ghost btn-xs']) ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php else : ?>
		<div class="card"><p style="margin:0;color:var(--text-muted);"><?= h(__('Sem tickets resolvidos no escopo para faturar.')) ?></p></div>
	<?php endif; ?>
</div>
