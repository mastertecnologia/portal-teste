<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Matriz', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero" style="border-bottom-color: rgba(255,255,255,0.08); margin-bottom: 16px;">
			<h1 style="color: #f0f2f8; font-size: 1.2rem; margin: 0 0 8px;">Matriz papéis × permissões</h1>
			<p style="margin: 0;">Visualização somente leitura. Use a ação abaixo para associar <strong>todas</strong> as permissões atuais ao papel <strong>Super administrador</strong> (útil após sincronizar o catálogo).</p>
			<div class="admin-panel-hero-actions" style="margin-top: 12px;">
				<?= $this->Html->link('← Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('Papéis por usuário', ['action' => 'adminUsers'], ['class' => 'admin-panel-btn admin-panel-btn--teal']) ?>
				<?php if (empty($rbacMissing) && $permissions->count() > 0) : ?>
					<?= $this->Form->postLink(
						'Associar tudo a Super administrador',
						['action' => 'adminGrantSuperAll'],
						['class' => 'admin-panel-btn admin-panel-btn--teal', 'confirm' => 'Substituir vínculos atuais do papel super_admin por TODAS as permissões do catálogo?']
					) ?>
				<?php endif; ?>
			</div>
		</header>

		<?php if (!empty($rbacMissing)) : ?>
			<div class="admin-rbac-callout">Execute a migration RBAC antes de usar a matriz.</div>
		<?php elseif ($permissions->count() === 0) : ?>
			<div class="admin-rbac-callout">Sincronize o catálogo em <a href="<?= $this->Url->build(['action' => 'adminIndex']) ?>" style="color:#93c5fd;">Permissões</a> primeiro.</div>
		<?php else : ?>
			<div class="admin-rbac-matrix-outer">
				<table class="admin-rbac-matrix">
					<thead>
						<tr>
							<th>Permissão / módulo</th>
							<?php foreach ($roles as $r) : ?>
								<th title="<?= h($r->description ?? '') ?>"><?= h($r->name) ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php
						$curMod = null;
						foreach ($permissions as $p) :
							if ($curMod !== $p->module) {
								$curMod = $p->module;
								?>
								<tr>
									<td colspan="<?= 1 + $roles->count() ?>" style="background:#1a1e28; color:#6ee7c5; font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:0.06em;">
										<?= h($curMod ?: 'Outros') ?>
									</td>
								</tr>
								<?php
							}
							?>
							<tr>
								<td title="<?= h($p->code) ?>"><?= h($p->name) ?></td>
								<?php foreach ($roles as $r) : ?>
									<?php $on = !empty($map[(int)$r->id][(int)$p->id]); ?>
									<td class="<?= $on ? 'cell-yes' : 'cell-no' ?>"><?= $on ? '●' : '·' ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p style="font-size: 11px; color: #555e78; margin-top: 12px;">Vínculos usuário ↔ papel: tabela <code style="color:#8b92a8;">rbac_users_roles</code> (gestão dedicada pode ser adicionada depois).</p>
		<?php endif; ?>
	</div>
</div>
