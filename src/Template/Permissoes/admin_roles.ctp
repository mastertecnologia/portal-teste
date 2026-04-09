<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Papéis RBAC', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Papéis RBAC</h1>
			<p>Defina <strong class="ap-text-bright">hierarchy_level</strong> (maior = mais privilegiado) para a <a href="<?= $this->Url->build(['action' => 'adminUsers']) ?>" class="admin-rbac-a-inline">anti-escalação</a> na atribuição de papéis. A matriz de permissões continua em <a href="<?= $this->Url->build(['action' => 'adminMatrix']) ?>" class="admin-rbac-a-inline">Matriz</a>.</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('Matriz', ['action' => 'adminMatrix'], ['class' => 'admin-panel-btn admin-panel-btn--accent']) ?>
				<?php if (empty($rbacMissing)) : ?>
					<?= $this->Html->link(
						'<i class="fa fa-plus"></i> Novo papel',
						['action' => 'adminRoleAdd'],
						['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]
					) ?>
				<?php endif; ?>
			</div>
		</header>

		<?php if (!empty($rbacMissing)) : ?>
			<div class="admin-rbac-callout">Execute a migration RBAC antes de usar esta página.</div>
		<?php else : ?>
			<div class="admin-rbac-matrix-outer">
				<table class="admin-rbac-matrix">
					<thead>
						<tr>
							<th>Nome</th>
							<th>Slug</th>
							<th>Nível</th>
							<th>Ordem</th>
							<th>Ativo</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($roles as $r) :
							$rid = (int)$r->id;
							$hl = (int)($r->hierarchy_level ?? 0);
							?>
							<tr>
								<td class="admin-rbac-td-left">
									<?= h($r->name) ?>
									<?php if (!empty($r->is_system)) : ?>
										<span class="admin-rbac-perm-code"> · sistema</span>
									<?php endif; ?>
								</td>
								<td><code class="ap-code-violet"><?= h($r->slug) ?></code></td>
								<td><?= $hl ?></td>
								<td><?= (int)$r->sort_order ?></td>
								<td><?= !empty($r->active) ? '<span class="cell-yes">sim</span>' : '<span class="cell-no">não</span>' ?></td>
								<td class="admin-rbac-td-actions">
									<?= $this->Html->link('Editar', ['action' => 'adminRoleEdit', $rid], ['class' => 'admin-section-card-link admin-rbac-table-link']) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
