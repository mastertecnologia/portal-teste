<?php
/**
 * Auditoria · LGPD — mockup pg-auditoria.
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $audItems
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Sistema · LGPD')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📑 <?= h(__('Auditoria')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('Últimos %d eventos registrados em audit_logs')), count($audItems)) ?></div>
	</div>
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'SistemaPrototype', 'action' => 'acessoCentral'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Quando')) ?></th>
					<th><?= h(__('Usuário')) ?></th>
					<th><?= h(__('Entidade')) ?></th>
					<th><?= h(__('Ação')) ?></th>
					<th><?= h(__('IP')) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($audItems === []) : ?>
					<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Sem registros de auditoria.')) ?></td></tr>
				<?php else : foreach ($audItems as $a) : ?>
					<tr>
						<td class="mu"><?= h($H->dt($a['created'], 'd/m/Y H:i')) ?></td>
						<td><?= (int)$a['user_id'] > 0 ? ('#' . (int)$a['user_id']) : '—' ?></td>
						<td><strong><?= h((string)$a['entity_type']) ?></strong> <span style="font-size:11px;color:var(--text-muted);">#<?= h((string)$a['entity_id']) ?></span></td>
						<td><span class="badge b-aprov"><?= h((string)$a['action']) ?></span></td>
						<td style="font-family:monospace;font-size:11px;color:var(--text-muted);"><?= h((string)$a['ip']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
