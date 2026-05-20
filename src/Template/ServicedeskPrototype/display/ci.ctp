<?php
/**
 * Configuration Item · detalhe (CMDB).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $ci
 * @var array<int,array<string,mixed>> $ciTickets
 */
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · CMDB')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">
				🗄 <?= h((string)$ci['tag']) ?> · <?= h((string)$ci['descricao']) ?>
			</h1>
			<div style="font-size:12px;color:var(--text-muted);">
				<?= h(__('Cliente')) ?>: <strong><?= h((string)$ci['cliente']) ?></strong>
				<?php if (!empty($ci['tipo'])) : ?>· <?= h((string)$ci['tipo']) ?><?php endif; ?>
			</div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<?= $this->Html->link('← ' . __('CMDB'), ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'cmdb'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('✍ ' . __('Editar (clássico)'), ['controller' => 'Ativos', 'action' => 'view', (int)$ci['id']], ['class' => 'btn btn-primary btn-sm']) ?>
		</div>
	</div>

	<div class="summary-grid" style="margin-bottom:14px;">
		<div class="summary-card" style="border-left:3px solid var(--teal);">
			<div class="lbl"><?= h(__('Tag')) ?></div>
			<div class="val" style="color:var(--teal-dark);font-family:monospace;font-size:16px;"><?= h((string)$ci['tag']) ?></div>
		</div>
		<div class="summary-card" style="border-left:3px solid var(--blue);">
			<div class="lbl"><?= h(__('Tickets ativos')) ?></div>
			<div class="val" style="color:<?= count($ciTickets) > 0 ? '#7A1822' : '#0C447C' ?>;"><?= count($ciTickets) ?></div>
		</div>
		<div class="summary-card" style="border-left:3px solid var(--purple);">
			<div class="lbl"><?= h(__('Host / Identificador')) ?></div>
			<div class="val" style="font-size:14px;font-family:monospace;color:var(--text);"><?= h((string)$ci['host']) ?: '—' ?></div>
		</div>
	</div>

	<div class="g2">
		<div class="card">
			<div class="sec-title"><?= h(__('Ficha técnica')) ?></div>
			<div style="font-size:12px;line-height:1.8;">
				<div><strong><?= h(__('Tipo')) ?>:</strong> <?= h((string)$ci['tipo']) ?: '—' ?></div>
				<div><strong><?= h(__('Fabricante')) ?>:</strong> <?= h((string)$ci['fabricante']) ?: '—' ?></div>
				<div><strong><?= h(__('Modelo')) ?>:</strong> <?= h((string)$ci['modelo']) ?: '—' ?></div>
				<div><strong><?= h(__('Serial')) ?>:</strong> <span style="font-family:monospace;"><?= h((string)$ci['serial']) ?: '—' ?></span></div>
			</div>
		</div>
		<div class="card">
			<div class="sec-title"><?= h(__('Mapa de dependências')) ?></div>
			<p style="color:var(--text-muted);margin:0;font-size:12px;">
				<?= h(__('Análise de impacto, upstream e downstream em desenvolvimento. Por enquanto, consulte tickets associados abaixo.')) ?>
			</p>
		</div>
	</div>

	<div class="card" style="padding:0;overflow:hidden;">
		<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
			<strong style="font-size:13px;"><?= h(__('Tickets ativos vinculados')) ?></strong>
		</div>
		<div style="overflow-x:auto;">
			<table class="tbl" style="margin:0;">
				<thead>
					<tr>
						<th><?= h(__('ID')) ?></th>
						<th><?= h(__('Assunto')) ?></th>
						<th><?= h(__('Situação')) ?></th>
						<th><?= h(__('Prioridade')) ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($ciTickets === []) : ?>
						<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum ticket ativo vinculado a este CI.')) ?></td></tr>
					<?php else : foreach ($ciTickets as $t) : ?>
						<tr>
							<td><strong>#<?= (int)$t['id'] ?></strong></td>
							<td><?= h(\Cake\Utility\Text::truncate((string)$t['assunto'], 80, ['ellipsis' => '…'])) ?></td>
							<td><?= h((string)$t['situacao']) ?></td>
							<td><?= h((string)$t['prioridade']) ?></td>
							<td class="r"><?= $this->Html->link(__('Abrir'), ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', (int)$t['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
