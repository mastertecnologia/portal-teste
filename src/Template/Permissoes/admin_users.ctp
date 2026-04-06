<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Papéis por usuário', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Papéis RBAC por usuário</h1>
			<p>Usuários da <strong class="ap-text-bright">equipe</strong> (PGM/Master). Marque um ou mais papéis em cada pessoa; só passam a valer em <strong class="ap-text-bright">enforce</strong> quando houver vínculo em <code class="ap-code-violet">rbac_users_roles</code>.</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('Matriz', ['action' => 'adminMatrix'], ['class' => 'admin-panel-btn admin-panel-btn--accent']) ?>
			</div>
		</header>

		<?php if (!empty($rbacMissing)) : ?>
			<div class="admin-rbac-callout">Execute a migration RBAC e sincronize o catálogo.</div>
		<?php else : ?>
			<div class="admin-rbac-matrix-outer">
				<table class="admin-rbac-matrix">
					<thead>
						<tr>
							<th>Usuário</th>
							<th>Nome</th>
							<th>Papéis atribuídos</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($users as $u) :
							$cnt = isset($roleCounts[(int)$u->id]) ? (int)$roleCounts[(int)$u->id] : 0;
							?>
							<tr>
								<td class="admin-rbac-td-left"><?= h($u->username) ?></td>
								<td class="admin-rbac-td-left"><?= h($u->name ?: '—') ?></td>
								<td><?= $cnt > 0 ? '<span class="cell-yes">' . $cnt . '</span>' : '<span class="cell-no">0</span>' ?></td>
								<td>
									<?= $this->Html->link('Editar papéis', ['action' => 'adminUserRoles', $u->id], ['class' => 'admin-section-card-link admin-rbac-table-link']) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
