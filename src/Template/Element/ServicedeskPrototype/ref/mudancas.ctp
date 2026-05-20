<?php
/**
 * Gestão de Mudanças (ITIL Change Management) — proxy via tickets P1.
 * Dados via ServicedeskPrototypeScreensService::screenMudancas().
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$title = (string)($screen['title'] ?? 'Mudanças');
$subtitle = (string)($screen['subtitle'] ?? '');
$rows = (array)($screen['rows'] ?? []);
$kpis = (array)($screen['kpis'] ?? []);
$empty = (string)($screen['empty'] ?? __('Nenhuma mudança em curso.'));
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · ITIL')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">🔄 <?= h($title) ?></h1>
			<?php if ($subtitle !== '') : ?>
				<div style="font-size:12px;color:var(--text-muted);"><?= h($subtitle) ?></div>
			<?php endif; ?>
		</div>
		<?= $this->Html->link('← ' . __('Service Desk'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
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

	<?php if ($rows !== []) : ?>
		<div class="card" style="padding:0;overflow:hidden;">
			<div style="padding:10px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;">
				<?= h(__('Tickets P1/críticos em aberto · candidatos a CHG no CAB')) ?>
			</div>
			<div style="overflow-x:auto;">
				<table class="tbl" style="margin:0;">
					<thead>
						<tr>
							<th><?= h(__('ID')) ?></th>
							<th><?= h(__('Cliente')) ?></th>
							<th><?= h(__('Assunto')) ?></th>
							<th><?= h(__('Situação')) ?></th>
							<th><?= h(__('Técnico')) ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $row) : ?>
							<tr>
								<td><strong>CHG-<?= (int)($row['id'] ?? 0) ?></strong></td>
								<td><?= h((string)($row['cliente'] ?? '')) ?></td>
								<td><?= h(\Cake\Utility\Text::truncate((string)($row['assunto'] ?? ''), 80, ['ellipsis' => '…'])) ?></td>
								<td><?= h((string)($row['situacao_label'] ?? '—')) ?></td>
								<td><?= h((string)($row['tecnico'] ?? '')) ?></td>
								<td class="r"><?= $this->Html->link(__('Abrir'), ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', (int)($row['id'] ?? 0)], ['class' => 'btn btn-ghost btn-xs']) ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php else : ?>
		<div class="card"><p style="margin:0;color:var(--text-muted);"><?= h($empty) ?></p></div>
	<?php endif; ?>

	<div class="alert-box alert-blue" style="margin-top:14px;">
		<strong><?= h(__('Sobre o módulo')) ?>:</strong>
		<?= h(__('o portal ainda não tem CAB/Change Management formal — esta tela mostra tickets P1 / fila “mudança” como proxy. Para CAB completo (RFC, aprovações, janela de mudança), abra um pedido de evolução.')) ?>
	</div>
</div>
