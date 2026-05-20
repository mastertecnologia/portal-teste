<?php
/**
 * Permissões SD — atalhos para RBAC central.
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
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · RBAC')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">🔐 <?= h(__('Permissões')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Quem pode atender, aprovar, fechar tickets e gerir filas')) ?></div>
		</div>
		<div style="display:flex;gap:8px;">
			<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('🎭 ' . __('Simular acesso'), ['controller' => 'SistemaPrototype', 'action' => 'viewAs'], ['class' => 'btn btn-primary btn-sm']) ?>
		</div>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) : ?>
				<div class="summary-card" style="border-left:3px solid var(--purple);">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:var(--purple-dark);"><?= h((string)($k['val'] ?? '')) ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="card">
		<div class="sec-title"><?= h(__('Permissões Service Desk no registry')) ?></div>
		<?php if ($items === []) : ?>
			<p style="color:var(--text-muted);margin:0;"><?= h(__('Nenhuma permissão específica de SD encontrada.')) ?></p>
		<?php else : ?>
			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px;max-height:340px;overflow-y:auto;">
				<?php foreach ($items as $it) : ?>
					<div style="border:1px solid var(--border-light);border-radius:var(--radius);padding:8px 10px;font-size:11px;">
						<strong style="font-family:monospace;color:var(--teal-dark);"><?= h((string)$it['col1']) ?></strong>
						<div style="color:var(--text-muted);margin-top:2px;"><?= h(\Cake\Utility\Text::truncate((string)$it['col2'], 70, ['ellipsis' => '…'])) ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="card">
		<div class="sec-title"><?= h(__('Atalhos RBAC')) ?></div>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
			<?= $this->Html->link('🛡 ' . __('Papéis RBAC'), ['controller' => 'SistemaPrototype', 'action' => 'acessoPapeis'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('👥 ' . __('Usuários'), ['controller' => 'SistemaPrototype', 'action' => 'usuarios'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('📨 ' . __('Pedidos de acesso'), ['controller' => 'RbacAccessRequests', 'action' => 'pedidosAcessoManager'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('📋 ' . __('Filas / atribuições'), ['controller' => 'Queues', 'action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</div>
</div>
