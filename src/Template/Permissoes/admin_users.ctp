<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Papéis por usuário', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero" style="border-bottom-color: rgba(255,255,255,0.08); margin-bottom: 16px;">
			<h1 style="color: #f0f2f8; font-size: 1.2rem; margin: 0 0 8px;">Papéis RBAC por usuário</h1>
			<p style="margin: 0;">Usuários da <strong style="color:#f0f2f8;">equipe</strong> (PGM/Master). Marque um ou mais papéis em cada pessoa; só passam a valer em <strong style="color:#f0f2f8;">enforce</strong> quando houver vínculo em <code style="color:#c4b5fd;">rbac_users_roles</code>.</p>
			<div class="admin-panel-hero-actions" style="margin-top: 12px;">
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
								<td style="text-align:left"><?= h($u->username) ?></td>
								<td style="text-align:left"><?= h($u->name ?: '—') ?></td>
								<td><?= $cnt > 0 ? '<span class="cell-yes">' . $cnt . '</span>' : '<span class="cell-no">0</span>' ?></td>
								<td>
									<?= $this->Html->link('Editar papéis', ['action' => 'adminUserRoles', $u->id], ['class' => 'admin-section-card-link', 'style' => 'margin-top:0']) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
