<?php
/**
 * Contratos & SLA — KPIs + tabela de contratos.
 * Dados via ServicedeskPrototypeScreensService::screenContratos().
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$title = (string)($screen['title'] ?? 'Contratos SLA');
$subtitle = (string)($screen['subtitle'] ?? '');
$items = (array)($screen['items'] ?? []);
$kpis = (array)($screen['kpis'] ?? []);
$empty = (string)($screen['empty'] ?? __('Sem contratos no escopo.'));
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">📄 <?= h($title) ?></h1>
			<?php if ($subtitle !== '') : ?>
				<div style="font-size:12px;color:var(--text-muted);"><?= h($subtitle) ?></div>
			<?php endif; ?>
		</div>
		<div style="display:flex;gap:8px;">
			<?= $this->Html->link('← ' . __('Service Desk'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('+ ' . __('Novo contrato'), ['controller' => 'ContractManagement', 'action' => 'add'], ['class' => 'btn btn-primary btn-sm']) ?>
		</div>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) : ?>
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
						<th><?= h(__('Contrato')) ?></th>
						<th><?= h(__('Cliente')) ?></th>
						<th><?= h(__('Descrição')) ?></th>
						<th><?= h(__('Situação')) ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($items === []) : ?>
						<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h($empty) ?></td></tr>
					<?php else : foreach ($items as $it) :
						$st = strtolower((string)($it['col4'] ?? ''));
						$badge = 'b-arq';
						if (strpos($st, 'ativo') !== false || strpos($st, 'vigente') !== false) {
							$badge = 'b-paga';
						} elseif (strpos($st, 'venc') !== false || strpos($st, 'expir') !== false) {
							$badge = 'b-vencida';
						} elseif (strpos($st, 'renov') !== false || strpos($st, 'analis') !== false) {
							$badge = 'b-pendente';
						}
					?>
						<tr>
							<td><span style="font-family:'SFMono-Regular',Consolas,monospace;font-size:11px;color:var(--text-muted);"><?= h((string)($it['col1'] ?? '')) ?></span></td>
							<td><strong><?= h((string)($it['col2'] ?? '')) ?></strong></td>
							<td><?= h((string)($it['col3'] ?? '')) ?></td>
							<td><span class="badge <?= h($badge) ?>"><?= h((string)($it['col4'] ?? '—')) ?></span></td>
							<td class="r">
								<?php if (!empty($it['link'])) : ?>
									<?= $this->Html->link(__('Ver'), $it['link'], ['class' => 'btn btn-ghost btn-xs']) ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
