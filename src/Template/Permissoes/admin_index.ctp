<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões RBAC/ABAC', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero" style="border-bottom-color: rgba(255,255,255,0.08); margin-bottom: 18px;">
			<h1 style="color: #f0f2f8; font-size: 1.25rem; margin: 0 0 8px;">Permissões do sistema</h1>
			<p style="margin: 0; max-width: 720px;">Catálogo de capacidades (RBAC por controller/ação) e escopos ABAC (empresa, cliente, próprio). Sincronize a partir de <code style="color:#c4b5fd;">config/permissions_registry.php</code>. Runtime: <strong style="color:#6ee7c5;"><?= h(isset($rbacRuntimeMode) ? $rbacRuntimeMode : 'off') ?></strong> — ajuste em <code style="color:#93c5fd;">config/rbac.php</code> ou variável <code style="color:#93c5fd;">RBAC_MODE</code> (<strong>off</strong> | <strong>warn</strong> | <strong>enforce</strong>).</p>
			<div class="admin-panel-hero-actions" style="margin-top: 14px;">
				<?= $this->Html->link('<i class="fa fa-th-large"></i> Painel administrativo', ['controller' => 'Config', 'action' => 'index'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
				<?php if (empty($rbacMissing)) : ?>
					<?= $this->Html->link('<i class="fa fa-user-tag"></i> Papéis por usuário', ['action' => 'adminUsers'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-table"></i> Matriz papéis × permissões', ['action' => 'adminMatrix'], ['class' => 'admin-panel-btn admin-panel-btn--accent', 'escape' => false]) ?>
					<?= $this->Form->postLink(
						'<i class="fa fa-refresh"></i> Sincronizar catálogo',
						['action' => 'adminSyncRegistry'],
						['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false, 'confirm' => 'Importar permissões novas do registry? As já cadastradas não serão alteradas.']
					) ?>
				<?php endif; ?>
			</div>
		</header>

		<?php if (!empty($rbacMissing)) : ?>
			<div class="admin-rbac-callout">
				<strong>Tabelas não encontradas.</strong> Execute a migration <code style="color:#93c5fd;">20260327140000_RbacPermissionsFoundation</code> (bin/cake migrations migrate) e recarregue esta página.
			</div>
		<?php else : ?>
			<p style="font-size: 12px; margin: 0 0 16px; color: #8b92a8;">
				<strong style="color: #f0f2f8;"><?= (int)$nPerm ?></strong> permissões no banco ·
				<strong style="color: #f0f2f8;"><?= (int)$nRoles ?></strong> papéis ativos
			</p>
			<?php foreach ($byModule as $module => $items) : ?>
				<h3 style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #555e78; margin: 20px 0 10px; padding-bottom: 6px; border-bottom: 1px solid rgba(255,255,255,0.08);"><?= h($module) ?></h3>
				<ul style="list-style: none; margin: 0; padding: 0;">
					<?php foreach ($items as $p) : ?>
						<li style="padding: 10px 12px; margin-bottom: 6px; background: #13161d; border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; font-size: 13px;">
							<strong style="color: #f0f2f8;"><?= h($p->name) ?></strong>
							<span style="color: #555e78; font-size: 11px; margin-left: 8px;"><?= h($p->code) ?></span>
							<div style="font-size: 11px; color: #8b92a8; margin-top: 4px;">
								<?= h($p->controller) ?><?= $p->action ? '::' . h($p->action) : '' ?>
								· <span style="color:#93c5fd;"><?= h($p->perm_type) ?></span>
								<?php if (!empty($p->abac_scope)) : ?>
									· ABAC: <span style="color:#6ee7c5;"><?= h($p->abac_scope) ?></span>
								<?php endif; ?>
							</div>
							<?php if (!empty($p->description)) : ?>
								<div style="font-size: 11px; margin-top: 4px; color: #555e78;"><?= h($p->description) ?></div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
			<?php if ((int)$nPerm === 0) : ?>
				<div class="admin-rbac-callout" style="margin-top: 16px;">
					Nenhuma permissão na base ainda. Use <strong>Sincronizar catálogo</strong> para importar o registry.
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
