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
			<p>Visualização somente leitura. Use a ação abaixo para associar <strong class="ap-text-bright">todas</strong> as permissões atuais ao papel <strong class="ap-text-bright">Super administrador</strong> (útil após sincronizar o catálogo). Opcionalmente mostre uma coluna com o conjunto <strong class="ap-text-bright">efetivo</strong> de um utilizador da equipe (papéis + grupos + aliases), alinhado ao runtime — exceto <code class="ap-code-violet">rbac_permission_policies</code>.</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('Definir papéis', ['action' => 'adminRoles'], ['class' => 'admin-panel-btn']) ?>
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
			<?php
			$spotOn = !empty($matrixSpotlightUser);
			$roleColCount = $roles->count();
			$dataColspan = 1 + $roleColCount + ($spotOn ? 1 : 0);
			?>
			<?php if (!empty($matrixEquipeUserOptions)) : ?>
				<div class="admin-rbac-callout admin-rbac-callout--matrix-filter">
					<?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'adminMatrix'], 'class' => 'form-inline admin-rbac-matrix-filter-form']) ?>
					<label for="matrix-user-id" class="admin-rbac-matrix-filter-label">Coluna efetiva para</label>
					<?= $this->Form->control('user_id', [
						'type' => 'select',
						'label' => false,
						'id' => 'matrix-user-id',
						'options' => $matrixEquipeUserOptions,
						'empty' => '— nenhum —',
						'value' => $spotOn ? (int)$matrixSpotlightUser->id : '',
						'class' => 'form-control input-sm',
					]) ?>
					<?= $this->Form->button('Aplicar', ['class' => 'btn btn-default btn-sm']) ?>
					<?= $this->Form->end() ?>
					<?php if ($spotOn) : ?>
						<span class="admin-rbac-matrix-filter-meta">
							<?= (int)count($matrixSpotlightPermIds) ?> ID(s) em <code class="ap-code-blue">rbac_permissions</code> após aliases
							· <?= $this->Html->link('Relatório efetivo', ['action' => 'adminUserEffective', $matrixSpotlightUser->id], ['class' => 'admin-rbac-a-inline']) ?>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="admin-rbac-matrix-outer">
				<table class="admin-rbac-matrix">
					<thead>
						<tr>
							<th>Permissão / módulo</th>
							<?php foreach ($roles as $r) : ?>
								<?php
								$hl = (int)($r->hierarchy_level ?? 0);
								$tip = 'Nível hierárquico: ' . $hl;
								if (!empty($r->description)) {
									$tip .= ' — ' . $r->description;
								}
								?>
								<th title="<?= h($tip) ?>"><?= h($r->name) ?><?php if ($hl > 0) : ?><span class="admin-rbac-perm-code"> · <?= $hl ?></span><?php endif; ?></th>
							<?php endforeach; ?>
							<?php if ($spotOn) : ?>
								<th title="União efetiva (papéis + grupos + expand_legacy_aliases)">Efetivo<br><span class="admin-rbac-perm-code"><?= h($matrixSpotlightUser->username) ?></span></th>
							<?php endif; ?>
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
									<td colspan="<?= (int)$dataColspan ?>" class="admin-rbac-mod-row">
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
								<?php if ($spotOn) : ?>
									<?php $ue = !empty($matrixSpotlightPermIds[(int)$p->id]); ?>
									<td class="<?= $ue ? 'cell-yes admin-rbac-matrix-effective' : 'cell-no admin-rbac-matrix-effective' ?>"><?= $ue ? '●' : '·' ?></td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p class="admin-rbac-footnote">Vínculos usuário ↔ papel: <code class="ap-code-gray">rbac_users_roles</code> e grupos (<code class="ap-code-gray">rbac_user_groups</code> / <code class="ap-code-gray">rbac_group_roles</code>). A coluna <strong>Efetivo</strong> não reflete negações por <code class="ap-code-gray">rbac_permission_policies</code> em runtime.</p>
		<?php endif; ?>
	</div>
</div>
