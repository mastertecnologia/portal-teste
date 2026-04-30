<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões RBAC/ABAC', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero">
			<h1>Permissões do sistema</h1>
			<p>Catálogo de capacidades (RBAC por controller/ação) e escopos ABAC (empresa, cliente, próprio). Sincronize a partir de <code class="ap-code-violet">config/permissions_registry.php</code>. Runtime: <strong class="ap-text-teal"><?= h(isset($rbacRuntimeMode) ? $rbacRuntimeMode : 'off') ?></strong> — ajuste em <code class="ap-code-blue">config/rbac.php</code> ou variável <code class="ap-code-blue">RBAC_MODE</code> (<strong>off</strong> | <strong>warn</strong> | <strong>enforce</strong>).</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('<i class="fa fa-th-large"></i> Painel administrativo', ['controller' => 'Config', 'action' => 'index'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
				<?php if (empty($rbacMissing)) : ?>
					<?= $this->Html->link('<i class="fa fa-user-tag"></i> Papéis por usuário', ['action' => 'adminUsers'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-users"></i> Grupos RBAC', ['action' => 'adminGroups'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-history"></i> Auditoria RBAC', ['action' => 'adminRbacAudit'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-stethoscope"></i> Diagnóstico de acesso', ['action' => 'diagnosticarAcesso'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-flask"></i> Simular diagnóstico (teste)', ['action' => 'simularDiagnosticoAcesso'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
					<?= $this->Html->link(
						'<i class="fa fa-inbox"></i> Pedidos de acesso pendentes'
						. ((int)($pendingAccessRequests ?? 0) > 0 ? ' <span class="badge">' . (int)$pendingAccessRequests . '</span>' : ''),
						['controller' => 'RbacAccessRequests', 'action' => 'pedidosAcesso'],
						['class' => 'admin-panel-btn', 'escape' => false]
					) ?>
					<?= $this->Html->link('<i class="fa fa-filter"></i> Políticas por permissão', ['action' => 'adminPermissionPolicies'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-i-cursor"></i> Campos por permissão', ['action' => 'adminFieldPermissions'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-user-tag"></i> Papéis RBAC (níveis)', ['action' => 'adminRoles'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-table"></i> Matriz papéis × permissões', ['action' => 'adminMatrix'], ['class' => 'admin-panel-btn admin-panel-btn--accent', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-th"></i> Matriz visual', ['action' => 'matrizVisual'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
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
				<strong>Tabelas não encontradas.</strong> Execute a migration <code class="ap-code-blue">20260327140000_RbacPermissionsFoundation</code> (bin/cake migrations migrate) e recarregue esta página.
			</div>
		<?php else : ?>
			<?php
			$catalogModuleOptions = isset($catalogModuleOptions) && is_array($catalogModuleOptions) ? $catalogModuleOptions : [];
			$catalogFilterModule = isset($catalogFilterModule) ? (string)$catalogFilterModule : '';
			$catalogFilterQ = isset($catalogFilterQ) ? (string)$catalogFilterQ : '';
			$nPermShown = isset($nPermShown) ? (int)$nPermShown : (int)$nPerm;
			$catalogFilterActive = ($catalogFilterModule !== '' || $catalogFilterQ !== '');
			?>
			<p class="admin-rbac-meta">
				<?php if ($catalogFilterActive) : ?>
					A mostrar <strong><?= (int)$nPermShown ?></strong> de <strong><?= (int)$nPerm ?></strong> permissões ·
				<?php else : ?>
					<strong><?= (int)$nPerm ?></strong> permissões no banco ·
				<?php endif; ?>
				<strong><?= (int)$nRoles ?></strong> papéis ativos
			</p>
			<div class="admin-rbac-callout admin-rbac-callout--matrix-toolbar admin-rbac-catalog-filter">
				<?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'adminIndex'], 'class' => 'admin-rbac-matrix-toolbar-form']) ?>
				<div class="admin-rbac-matrix-toolbar-row">
					<div class="admin-rbac-matrix-toolbar-field">
						<label for="catalog-module" class="admin-rbac-matrix-filter-label">Módulo</label>
						<?= $this->Form->control('module', [
							'type' => 'select',
							'label' => false,
							'id' => 'catalog-module',
							'options' => $catalogModuleOptions,
							'empty' => 'Todos',
							'value' => $catalogFilterModule,
							'class' => 'form-control input-sm',
						]) ?>
					</div>
					<div class="admin-rbac-matrix-toolbar-field admin-rbac-matrix-toolbar-field--grow">
						<label for="catalog-q" class="admin-rbac-matrix-filter-label">Pesquisar</label>
						<?= $this->Form->control('q', [
							'type' => 'text',
							'label' => false,
							'id' => 'catalog-q',
							'value' => $catalogFilterQ,
							'placeholder' => 'Código, nome, controller…',
							'class' => 'form-control input-sm',
							'autocomplete' => 'off',
						]) ?>
					</div>
					<div class="admin-rbac-matrix-toolbar-actions">
						<?= $this->Form->button('<i class="fa fa-search"></i> Filtrar', ['class' => 'btn btn-primary btn-sm', 'escape' => false]) ?>
					</div>
				</div>
				<?= $this->Form->end() ?>
				<?php if ($catalogFilterActive) : ?>
					<?= $this->Html->link('Limpar filtros', ['action' => 'adminIndex'], ['class' => 'admin-rbac-matrix-clear-link']) ?>
				<?php endif; ?>
			</div>
			<?php if ($catalogFilterActive && $nPermShown === 0) : ?>
				<div class="admin-rbac-callout">Nenhuma permissão corresponde ao filtro. <?= $this->Html->link('Limpar', ['action' => 'adminIndex'], ['class' => 'admin-rbac-a-inline']) ?>.</div>
			<?php endif; ?>
			<?php foreach ($byModule as $module => $items) : ?>
				<h3 class="admin-rbac-mod-title"><?= h($module) ?></h3>
				<ul class="admin-rbac-perm-list">
					<?php foreach ($items as $p) : ?>
						<li class="admin-rbac-perm-item">
							<strong><?= h($p->name) ?></strong>
							<span class="admin-rbac-perm-code"><?= h($p->code) ?></span>
							<div class="admin-rbac-perm-meta">
								<?= h($p->controller) ?><?= $p->action ? '::' . h($p->action) : '' ?>
								· <span class="admin-rbac-txt-blue"><?= h($p->perm_type) ?></span>
								<?php if (!empty($p->abac_scope)) : ?>
									· ABAC: <span class="admin-rbac-txt-teal"><?= h($p->abac_scope) ?></span>
								<?php endif; ?>
							</div>
							<?php if (!empty($p->description)) : ?>
								<div class="admin-rbac-perm-desc"><?= h($p->description) ?></div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
			<?php if ((int)$nPerm === 0) : ?>
				<div class="admin-rbac-callout admin-rbac-callout--mt">
					Nenhuma permissão na base ainda. Use <strong>Sincronizar catálogo</strong> para importar o registry.
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
