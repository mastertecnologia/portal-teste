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
			<p>Marque ou desmarque as <strong class="ap-text-bright">caixinhas</strong> por papel (colunas editáveis conforme o seu nível hierárquico). Use <strong class="ap-text-bright">módulo</strong> e <strong class="ap-text-bright">pesquisa</strong> abaixo para reduzir a lista. Com filtro ativo, ao gravar só mudam as linhas visíveis; o resto do catálogo mantém-se. Utilizadores recebem permissões via <strong class="ap-text-bright">papéis</strong> em «Papéis por usuário». Coluna <strong class="ap-text-bright">Efetivo</strong>: papéis + grupos + aliases (sem <code class="ap-code-violet">rbac_permission_policies</code>).</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('Definir papéis', ['action' => 'adminRoles'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('Papéis por usuário', ['action' => 'adminUsers'], ['class' => 'admin-panel-btn admin-panel-btn--teal']) ?>
				<?php if (empty($rbacMissing) && !empty($matrixPermTotal)) : ?>
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
		<?php elseif (empty($matrixPermTotal)) : ?>
			<div class="admin-rbac-callout">Sincronize o catálogo em <a href="<?= $this->Url->build(['action' => 'adminIndex']) ?>" class="admin-rbac-a-inline">Permissões</a> primeiro.</div>
		<?php else : ?>
			<?php
			$spotOn = !empty($matrixSpotlightUser);
			$roleColCount = $roles->count();
			$dataColspan = 1 + $roleColCount + ($spotOn ? 1 : 0);
			$matrixModuleOptions = isset($matrixModuleOptions) && is_array($matrixModuleOptions) ? $matrixModuleOptions : [];
			$matrixFilterModule = isset($matrixFilterModule) ? (string)$matrixFilterModule : '';
			$matrixFilterQ = isset($matrixFilterQ) ? (string)$matrixFilterQ : '';
			$matrixPermShown = isset($matrixPermShown) ? (int)$matrixPermShown : 0;
			$matrixPermTotal = isset($matrixPermTotal) ? (int)$matrixPermTotal : 0;
			$matrixFilterActive = !empty($matrixFilterActive);
			$matrixClearQuery = [];
			if ($spotOn) {
				$matrixClearQuery['user_id'] = (int)$matrixSpotlightUser->id;
			}
			$matrixCsvQuery = [];
			if ($matrixFilterModule !== '') {
				$matrixCsvQuery['module'] = $matrixFilterModule;
			}
			if ($matrixFilterQ !== '') {
				$matrixCsvQuery['q'] = $matrixFilterQ;
			}
			if ($spotOn) {
				$matrixCsvQuery['user_id'] = (int)$matrixSpotlightUser->id;
			}
			$matrixPermissionsByModule = isset($matrixPermissionsByModule) && is_array($matrixPermissionsByModule) ? $matrixPermissionsByModule : [];
			?>
			<div class="admin-rbac-callout admin-rbac-callout--matrix-toolbar">
				<?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'adminMatrix'], 'class' => 'admin-rbac-matrix-toolbar-form']) ?>
				<div class="admin-rbac-matrix-toolbar-row">
					<?php if (!empty($matrixEquipeUserOptions)) : ?>
						<div class="admin-rbac-matrix-toolbar-field">
							<label for="matrix-user-id" class="admin-rbac-matrix-filter-label">Coluna efetiva</label>
							<?= $this->Form->control('user_id', [
								'type' => 'select',
								'label' => false,
								'id' => 'matrix-user-id',
								'options' => $matrixEquipeUserOptions,
								'empty' => '— nenhum —',
								'value' => $spotOn ? (int)$matrixSpotlightUser->id : '',
								'class' => 'form-control input-sm',
							]) ?>
						</div>
					<?php endif; ?>
					<div class="admin-rbac-matrix-toolbar-field">
						<label for="matrix-module" class="admin-rbac-matrix-filter-label">Módulo</label>
						<?= $this->Form->control('module', [
							'type' => 'select',
							'label' => false,
							'id' => 'matrix-module',
							'options' => $matrixModuleOptions,
							'empty' => 'Todos',
							'value' => $matrixFilterModule,
							'class' => 'form-control input-sm',
						]) ?>
					</div>
					<div class="admin-rbac-matrix-toolbar-field admin-rbac-matrix-toolbar-field--grow">
						<label for="matrix-q" class="admin-rbac-matrix-filter-label">Pesquisar</label>
						<?= $this->Form->control('q', [
							'type' => 'text',
							'label' => false,
							'id' => 'matrix-q',
							'value' => $matrixFilterQ,
							'placeholder' => 'Código, nome ou controller…',
							'class' => 'form-control input-sm',
							'autocomplete' => 'off',
						]) ?>
					</div>
					<div class="admin-rbac-matrix-toolbar-actions">
						<?= $this->Form->button('<i class="fa fa-search"></i> Filtrar', ['class' => 'btn btn-primary btn-sm', 'escape' => false]) ?>
					</div>
				</div>
				<?= $this->Form->end() ?>
				<?php if ($matrixFilterActive) : ?>
					<?= $this->Html->link('Limpar filtros de módulo/texto', ['action' => 'adminMatrix', '?' => $matrixClearQuery], ['class' => 'admin-rbac-matrix-clear-link']) ?>
				<?php endif; ?>
				<div class="admin-rbac-matrix-toolbar-meta">
					<strong><?= (int)$matrixPermShown ?></strong> de <strong><?= (int)$matrixPermTotal ?></strong> permissões
					<?php if ($spotOn) : ?>
						· <?= (int)count($matrixSpotlightPermIds) ?> ID(s) efetivos em <code class="ap-code-blue">rbac_permissions</code>
						· <?= $this->Html->link('Relatório efetivo', ['action' => 'adminUserEffective', $matrixSpotlightUser->id], ['class' => 'admin-rbac-a-inline']) ?>
					<?php endif; ?>
				</div>
				<?php if ($matrixFilterActive && $matrixPermShown > 0) : ?>
					<p class="admin-rbac-matrix-partial-hint"><i class="fa fa-info-circle"></i> Ao gravar, apenas as <strong><?= (int)$matrixPermShown ?></strong> linhas visíveis são atualizadas nas colunas editáveis; as outras permissões de cada papel mantêm o estado anterior.</p>
				<?php endif; ?>
				<?php if ($matrixPermShown > 0) : ?>
					<div class="admin-rbac-matrix-toolbar-row admin-rbac-matrix-toolbar-row--secondary">
						<div class="admin-rbac-matrix-mod-bulk">
							<button type="button" class="admin-panel-btn btn-sm" id="admin-rbac-expand-mods" title="Mostrar linhas de todas as secções"><?= $this->Html->tag('span', '', ['class' => 'fa fa-plus-square-o', 'escape' => false]) ?> Expandir módulos</button>
							<button type="button" class="admin-panel-btn btn-sm" id="admin-rbac-collapse-mods" title="Ocultar linhas; cabeçalhos de módulo ficam visíveis"><?= $this->Html->tag('span', '', ['class' => 'fa fa-minus-square-o', 'escape' => false]) ?> Recolher módulos</button>
						</div>
						<div class="admin-rbac-matrix-export-wrap">
							<?= $this->Html->link(
								'<i class="fa fa-download"></i> Exportar CSV (vista atual)',
								['action' => 'adminMatrixExportCsv', '?' => $matrixCsvQuery],
								['class' => 'admin-panel-btn admin-panel-btn--accent btn-sm', 'escape' => false, 'title' => 'Mesmos filtros e coluna efetiva (se selecionada)']
							) ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php if ($matrixPermShown === 0) : ?>
				<div class="admin-rbac-callout">Nenhuma permissão corresponde ao filtro. <?= $this->Html->link('Limpar filtros', ['action' => 'adminMatrix', '?' => $matrixClearQuery], ['class' => 'admin-rbac-a-inline']) ?> ou altere módulo/pesquisa.</div>
			<?php else : ?>
			<div class="admin-rbac-matrix-outer">
				<?php
				$matrixCanEdit = !empty($matrixCanEdit);
				$matrixRoleEditable = isset($matrixRoleEditable) && is_array($matrixRoleEditable) ? $matrixRoleEditable : [];
				?>
				<?php
				$matrixVisibleIds = [];
				foreach ($permissions as $_p) {
					$matrixVisibleIds[] = (int)$_p->id;
				}
				?>
				<?php if ($matrixCanEdit) : ?>
					<?= $this->Form->create(null, ['url' => ['action' => 'adminMatrixSave'], 'class' => 'admin-rbac-matrix-form']) ?>
					<?= $this->Form->hidden('matrix_visible_perm_ids', ['value' => implode(',', $matrixVisibleIds)]) ?>
					<?php if ($spotOn) : ?>
						<?= $this->Form->hidden('return_user_id', ['value' => (int)$matrixSpotlightUser->id]) ?>
					<?php endif; ?>
					<?php if ($matrixFilterModule !== '') : ?>
						<?= $this->Form->hidden('return_matrix_module', ['value' => $matrixFilterModule]) ?>
					<?php endif; ?>
					<?php if ($matrixFilterQ !== '') : ?>
						<?= $this->Form->hidden('return_matrix_q', ['value' => $matrixFilterQ]) ?>
					<?php endif; ?>
				<?php endif; ?>
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
								$colEdit = !empty($matrixRoleEditable[(int)$r->id]);
								if ($colEdit) {
									$tip .= ' — editável';
								} else {
									$tip .= ' — só leitura (nível)';
								}
								?>
								<th title="<?= h($tip) ?>"><?= h($r->name) ?><?php if ($hl > 0) : ?><span class="admin-rbac-perm-code"> · <?= $hl ?></span><?php endif; ?><?php if (!$colEdit && $matrixCanEdit) : ?><span class="admin-rbac-perm-code"> · só leitura</span><?php endif; ?></th>
							<?php endforeach; ?>
							<?php if ($spotOn) : ?>
								<th title="União efetiva (papéis + grupos + expand_legacy_aliases)">Efetivo<br><span class="admin-rbac-perm-code"><?= h($matrixSpotlightUser->username) ?></span></th>
							<?php endif; ?>
						</tr>
					</thead>
						<?php foreach ($matrixPermissionsByModule as $modLabel => $permRows) :
							$nInMod = count($permRows);
							?>
					<tbody class="admin-rbac-matrix-mod-group">
						<tr class="admin-rbac-mod-row admin-rbac-mod-row--toggle" role="button" tabindex="0" aria-expanded="true">
							<td colspan="<?= (int)$dataColspan ?>" class="admin-rbac-mod-row admin-rbac-mod-row-inner">
								<span class="admin-rbac-mod-chevron" aria-hidden="true">▼</span>
								<span class="admin-rbac-mod-title-text"><?= h($modLabel) ?></span>
								<span class="admin-rbac-mod-count"><?= (int)$nInMod ?> permissão(ões)</span>
							</td>
						</tr>
							<?php foreach ($permRows as $p) : ?>
						<tr class="admin-rbac-matrix-data-row">
							<td title="<?= h($p->code) ?>"><span class="admin-rbac-perm-code"><?= h($p->code) ?></span><br><?= h($p->name) ?></td>
								<?php foreach ($roles as $r) : ?>
									<?php
									$on = !empty($map[(int)$r->id][(int)$p->id]);
									$colEdit = !empty($matrixRoleEditable[(int)$r->id]);
									?>
									<?php if ($matrixCanEdit && $colEdit) : ?>
							<td class="admin-rbac-matrix-td-cb">
								<label class="admin-rbac-matrix-cb-label">
									<input type="checkbox" class="admin-rbac-matrix-cb" name="matrix[<?= (int)$r->id ?>][]" value="<?= (int)$p->id ?>"<?= $on ? ' checked="checked"' : '' ?> aria-label="<?= h($r->name . ' — ' . $p->name) ?>" />
								</label>
							</td>
									<?php else : ?>
							<td class="<?= $on ? 'cell-yes' : 'cell-no' ?>"><?= $on ? '●' : '·' ?></td>
									<?php endif; ?>
								<?php endforeach; ?>
								<?php if ($spotOn) : ?>
									<?php $ue = !empty($matrixSpotlightPermIds[(int)$p->id]); ?>
							<td class="<?= $ue ? 'cell-yes admin-rbac-matrix-effective' : 'cell-no admin-rbac-matrix-effective' ?>"><?= $ue ? '●' : '·' ?></td>
								<?php endif; ?>
						</tr>
							<?php endforeach; ?>
					</tbody>
						<?php endforeach; ?>
				</table>
				<?php if ($matrixCanEdit) : ?>
					<div class="admin-rbac-matrix-actions">
						<?= $this->Form->button('Gravar matriz', ['class' => 'admin-panel-btn admin-panel-btn--teal']) ?>
					</div>
					<?= $this->Form->end() ?>
				<?php else : ?>
					<p class="admin-rbac-callout">Para editar a matriz é necessário <code class="ap-code-gray">permissoes.matrix.edit</code> no seu papel RBAC ou ser administrador legado (<code class="ap-code-gray">users.admin</code>).</p>
				<?php endif; ?>
			</div>
			<p class="admin-rbac-footnote">Vínculos usuário ↔ papel: <code class="ap-code-gray">rbac_users_roles</code> e grupos (<code class="ap-code-gray">rbac_user_groups</code> / <code class="ap-code-gray">rbac_group_roles</code>). A coluna <strong>Efetivo</strong> não reflete negações por <code class="ap-code-gray">rbac_permission_policies</code> em runtime.</p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
<?php if (empty($rbacMissing) && !empty($matrixPermTotal) && !empty($matrixPermShown)) : ?>
<?php
$this->Html->scriptBlock(
	'(function(){var g=document.querySelectorAll(".admin-rbac-matrix-mod-group");function setAll(collapsed){for(var i=0;i<g.length;i++){var tb=g[i];var h=tb.querySelector(".admin-rbac-mod-row--toggle");if(collapsed){tb.classList.add("is-collapsed");if(h){h.setAttribute("aria-expanded","false");}}else{tb.classList.remove("is-collapsed");if(h){h.setAttribute("aria-expanded","true");}}}}function toggle(tb){tb.classList.toggle("is-collapsed");var h=tb.querySelector(".admin-rbac-mod-row--toggle");if(h){h.setAttribute("aria-expanded",tb.classList.contains("is-collapsed")?"false":"true");}}for(var j=0;j<g.length;j++){var head=g[j].querySelector(".admin-rbac-mod-row--toggle");if(head){head.addEventListener("click",function(ev){toggle(ev.currentTarget.closest("tbody"));});head.addEventListener("keydown",function(ev){if(ev.key==="Enter"||ev.key===" "){ev.preventDefault();toggle(ev.currentTarget.closest("tbody"));}});}}var ex=document.getElementById("admin-rbac-expand-mods");var cl=document.getElementById("admin-rbac-collapse-mods");if(ex){ex.addEventListener("click",function(){setAll(false);});}if(cl){cl.addEventListener("click",function(){setAll(true);});}})();',
	['block' => true]
);
?>
<?php endif; ?>
