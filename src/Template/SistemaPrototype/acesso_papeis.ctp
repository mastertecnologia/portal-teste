<?php
/**
 * Papéis RBAC — mockup pg-acesso-papeis.
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $roleItems
 */
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Sistema · RBAC')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🛡 <?= h(__('Papéis RBAC')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('%d papéis ativos · ordenados por hierarquia')), count($roleItems)) ?></div>
	</div>
	<?= $this->Html->link('← ' . __('Central'), ['controller' => 'SistemaPrototype', 'action' => 'acessoCentral'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;">
	<?php if ($roleItems === []) : ?>
		<div class="card" style="text-align:center;color:var(--text-muted);padding:32px;"><?= h(__('Nenhum papel ativo.')) ?></div>
	<?php else : foreach ($roleItems as $r) : ?>
		<div class="card" style="margin:0;">
			<div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
				<div>
					<strong style="font-size:14px;"><?= h((string)$r['name']) ?></strong>
					<div style="font-size:11px;color:var(--text-muted);font-family:monospace;"><?= h((string)$r['slug']) ?></div>
				</div>
				<?php if (!empty($r['is_system'])) : ?>
					<span class="badge b-aprov">⚙ <?= h(__('Sistema')) ?></span>
				<?php endif; ?>
			</div>
			<?php if (!empty($r['description'])) : ?>
				<div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;"><?= h(\Cake\Utility\Text::truncate((string)$r['description'], 140, ['ellipsis' => '…'])) ?></div>
			<?php endif; ?>
			<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Hierarquia')) ?>: <strong><?= (int)$r['hierarchy'] ?></strong></div>
		</div>
	<?php endforeach; endif; ?>
</div>
