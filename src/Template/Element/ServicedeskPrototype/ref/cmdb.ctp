<?php
/**
 * CMDB · Configuration Items — tabela de ativos + KPIs por tipo.
 * Dados via ServicedeskPrototypeScreensService::screenCmdb().
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$title = (string)($screen['title'] ?? 'CMDB · Configuration Items');
$subtitle = (string)($screen['subtitle'] ?? '');
$items = (array)($screen['items'] ?? []);
$typeKpis = (array)($screen['kpis'] ?? []);
$empty = (string)($screen['empty'] ?? __('Sem ativos no escopo.'));
$totalShown = count($items);
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · ITIL')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">🗄 <?= h($title) ?></h1>
			<?php if ($subtitle !== '') : ?>
				<div style="font-size:12px;color:var(--text-muted);"><?= h($subtitle) ?></div>
			<?php endif; ?>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<?= $this->Html->link('← ' . __('Service Desk'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('+ ' . __('Novo ativo'), ['controller' => 'Ativos', 'action' => 'add'], ['class' => 'btn btn-primary btn-sm']) ?>
		</div>
	</div>

	<?php if ($typeKpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($typeKpis as $k) : ?>
				<div class="summary-card" style="border-left:3px solid var(--teal);">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:var(--teal-dark);"><?= h((string)($k['val'] ?? '')) ?></div>
					<?php if (!empty($k['hint'])) : ?>
						<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['hint']) ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="card" style="padding:0;overflow:hidden;">
		<div style="overflow-x:auto;">
			<table class="tbl" style="margin:0;">
				<thead>
					<tr>
						<th><?= h(__('Tag CI')) ?></th>
						<th><?= h(__('Nome / Tipo')) ?></th>
						<th><?= h(__('Cliente')) ?></th>
						<th><?= h(__('Host / Identificador')) ?></th>
						<th class="r"><?= h(__('Tickets')) ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($items === []) : ?>
						<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h($empty) ?></td></tr>
					<?php else : foreach ($items as $it) :
						$tickets = (int)($it['tickets'] ?? 0);
					?>
						<tr<?= $tickets > 0 ? ' style="background:#FEF2F2;"' : '' ?>>
							<td><span style="font-family:'SFMono-Regular',Consolas,monospace;font-size:11px;font-weight:600;color:var(--teal-dark);background:var(--teal-light);padding:3px 8px;border-radius:4px;"><?= h((string)($it['tag'] ?? '—')) ?></span></td>
							<td>
								<div style="font-weight:600;"><?= h((string)($it['nome'] ?? '')) ?></div>
								<div style="font-size:11px;color:var(--text-muted);"><?= h((string)($it['tipo'] ?? '—')) ?></div>
							</td>
							<td><?= h((string)($it['cliente'] ?? '—')) ?></td>
							<td style="font-family:monospace;font-size:11px;"><?= h((string)($it['host'] ?? '—')) ?></td>
							<td class="r"><?php if ($tickets > 0) : ?><strong style="color:#7A1822;"><?= $tickets ?> <?= h(__('ativos')) ?></strong><?php else : ?>0<?php endif; ?></td>
							<td class="r">
								<?= $this->Html->link(__('Ver'), ['controller' => 'ServicedeskPrototype', 'action' => 'ci', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php if ($items !== []) : ?>
			<div style="padding:10px 14px;background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;font-size:12px;border-top:1px solid var(--border-light);">
				<span style="color:var(--text-muted);"><?= sprintf(h(__('Mostrando %d ativos')), $totalShown) ?></span>
				<?= $this->Html->link(__('Abrir módulo completo'), ['controller' => 'Ativos', 'action' => 'index'], ['class' => 'btn btn-ghost btn-xs']) ?>
			</div>
		<?php endif; ?>
	</div>
</div>
