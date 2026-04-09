<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Papéis RBAC', ['action' => 'adminRoles'], ['class' => 'breadcrumb-item']);
$isNew = $role->isNew();
$this->Breadcrumbs->add(
	$isNew ? 'Novo' : h($role->name),
	[],
	['class' => 'breadcrumb-item active']
);
?>
<div class="col-md-12 col-lg-8 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1><?= $isNew ? 'Novo papel RBAC' : 'Editar papel' ?></h1>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Lista de papéis', ['action' => 'adminRoles'], ['class' => 'admin-panel-btn']) ?>
			</div>
		</header>

		<?php if (!empty($rbacMissing)) : ?>
			<div class="admin-rbac-callout">Tabelas RBAC ausentes.</div>
		<?php else : ?>
			<p class="admin-rbac-role-intro">O campo <strong class="ap-text-bright">Nível hierárquico</strong> controla quem pode atribuir este papel a outros (operadores só concedem papéis com nível ≤ ao máximo dos seus).</p>
			<div class="queues-form-panel queues-form-panel--wide">
				<?= $this->Form->create($role, [
					'url' => $isNew ? ['action' => 'adminRoleAdd'] : ['action' => 'adminRoleEdit', $role->id],
				]) ?>
				<?php if (!empty($role->is_system)) : ?>
					<p class="admin-rbac-role-intro">Papel de <strong>sistema</strong>: o slug não pode ser alterado.</p>
					<div class="form-group">
						<label>Slug</label>
						<p><code class="ap-code-violet"><?= h($role->slug) ?></code></p>
					</div>
				<?php else : ?>
					<?= $this->Form->control('slug', ['label' => 'Slug (único)', 'required' => true, 'placeholder' => 'ex.: equipe_n1']) ?>
				<?php endif; ?>
				<?= $this->Form->control('name', ['label' => 'Nome']) ?>
				<?= $this->Form->control('description', ['label' => 'Descrição', 'type' => 'textarea', 'rows' => 3]) ?>
				<?= $this->Form->control('hierarchy_level', [
					'label' => 'Nível hierárquico',
					'type' => 'number',
					'min' => 0,
					'max' => 999999,
					'default' => 0,
				]) ?>
				<?= $this->Form->control('sort_order', ['label' => 'Ordem na lista']) ?>
				<?= $this->Form->control('active', ['label' => 'Ativo', 'type' => 'checkbox']) ?>
				<div class="queues-form-actions m-t-20">
					<?= $this->Form->button($isNew ? 'Criar papel' : 'Salvar', ['class' => 'queues-btn queues-btn--success']) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		<?php endif; ?>
	</div>
</div>
