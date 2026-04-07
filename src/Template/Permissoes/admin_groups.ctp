<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Grupos RBAC', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Grupos RBAC</h1>
			<p>Agrupe utilizadores da <strong class="ap-text-bright">equipe</strong> e associe <strong class="ap-text-bright">papéis</strong>. Os papéis do grupo somam-se aos papéis diretos em <code class="ap-code-violet">rbac_users_roles</code> quando <code class="ap-code-blue">expand_group_roles</code> está ativo.</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('Papéis por usuário', ['action' => 'adminUsers'], ['class' => 'admin-panel-btn admin-panel-btn--teal']) ?>
				<?php if (empty($rbacGroupsMissing)) : ?>
					<?= $this->Html->link('<i class="fa fa-plus"></i> Novo grupo', ['action' => 'adminGroupEdit'], ['class' => 'admin-panel-btn admin-panel-btn--accent', 'escape' => false]) ?>
				<?php endif; ?>
			</div>
		</header>

		<?php if (!empty($rbacGroupsMissing)) : ?>
			<div class="admin-rbac-callout">Execute a migration <code class="ap-code-blue">20260416100000_RbacPhase3GroupsPoliciesAudit</code> (grupos RBAC) e recarregue.</div>
		<?php else : ?>
			<div class="admin-rbac-matrix-outer">
				<table class="admin-rbac-matrix">
					<thead>
						<tr>
							<th>Nome</th>
							<th>Slug</th>
							<th>Ativo</th>
							<th>Membros</th>
							<th>Papéis no grupo</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($groups as $g) :
							$gid = (int)$g->id;
							$nu = isset($userCounts[$gid]) ? (int)$userCounts[$gid] : 0;
							$nr = isset($roleCounts[$gid]) ? (int)$roleCounts[$gid] : 0;
							?>
							<tr>
								<td class="admin-rbac-td-left"><?= h($g->name) ?></td>
								<td><code class="ap-code-violet"><?= h($g->slug) ?></code></td>
								<td><?= !empty($g->active) ? '<span class="cell-yes">sim</span>' : '<span class="cell-no">não</span>' ?></td>
								<td><?= $nu ?></td>
								<td><?= $nr ?></td>
								<td class="admin-rbac-td-actions">
									<?= $this->Html->link('Editar', ['action' => 'adminGroupEdit', $gid], ['class' => 'admin-section-card-link admin-rbac-table-link']) ?>
									<?= $this->Html->link('Papéis', ['action' => 'adminGroupRoles', $gid], ['class' => 'admin-section-card-link admin-rbac-table-link']) ?>
									<?= $this->Html->link('Membros', ['action' => 'adminGroupUsers', $gid], ['class' => 'admin-section-card-link admin-rbac-table-link']) ?>
									<?php if (empty($g->is_system)) : ?>
										<?= $this->Form->postLink('Excluir', ['action' => 'adminGroupDelete', $gid], ['class' => 'admin-section-card-link admin-rbac-table-link text-danger', 'confirm' => 'Excluir este grupo? Vínculos de membros e papéis serão removidos.']) ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ($groups->count() === 0) : ?>
				<div class="admin-rbac-callout admin-rbac-callout--mt">Nenhum grupo. Crie um para começar a atribuir papéis em lote.</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
