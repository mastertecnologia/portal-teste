<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Matriz', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Matriz papéis × permissões</h1>
			<p>Visualização somente leitura. Use a ação abaixo para associar <strong class="ap-text-bright">todas</strong> as permissões atuais ao papel <strong class="ap-text-bright">Super administrador</strong> (útil após sincronizar o catálogo).</p>
			<div class="admin-panel-hero-actions">
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
			<div class="admin-rbac-callout">Sincronize o catálogo em <a href="<?= $this->Url->build(['action' => 'adminIndex']) ?>" class="admin-rbac-a-inline">Permissões</a> primeiro.</div>
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
									<td colspan="<?= 1 + $roles->count() ?>" class="admin-rbac-mod-row">
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
			<p class="admin-rbac-footnote">Vínculos usuário ↔ papel: tabela <code class="ap-code-gray">rbac_users_roles</code> (gestão dedicada pode ser adicionada depois).</p>
		<?php endif; ?>
	</div>
</div>
