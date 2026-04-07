<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Grupos', ['action' => 'adminGroups'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add($group->isNew() ? 'Novo' : h($group->name), [], ['class' => 'breadcrumb-item active']);
$isNew = $group->isNew();
?>
<div class="col-md-12 col-lg-8 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1><?= $isNew ? 'Novo grupo RBAC' : 'Editar grupo' ?></h1>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Lista de grupos', ['action' => 'adminGroups'], ['class' => 'admin-panel-btn']) ?>
			</div>
		</header>

		<?php if (!empty($rbacGroupsMissing)) : ?>
			<div class="admin-rbac-callout">Tabelas de grupos ausentes.</div>
		<?php else : ?>
			<div class="queues-form-panel queues-form-panel--wide">
				<?php
				$url = $isNew ? ['action' => 'adminGroupEdit'] : ['action' => 'adminGroupEdit', $group->id];
				echo $this->Form->create($group, ['url' => $url]);
				?>
				<?php if (!empty($group->is_system)) : ?>
					<p class="admin-rbac-role-intro">Grupo de <strong>sistema</strong>: o slug não pode ser alterado.</p>
					<?= $this->Form->hidden('slug') ?>
					<div class="form-group">
						<label>Slug</label>
						<p><code class="ap-code-violet"><?= h($group->slug) ?></code></p>
					</div>
				<?php else : ?>
					<?= $this->Form->control('slug', ['label' => 'Slug (único)', 'required' => $isNew, 'placeholder' => 'ex.: equipe_suporte']) ?>
				<?php endif; ?>
				<?= $this->Form->control('name', ['label' => 'Nome']) ?>
				<?= $this->Form->control('description', ['label' => 'Descrição', 'type' => 'textarea', 'rows' => 3]) ?>
				<?= $this->Form->control('sort_order', ['label' => 'Ordem']) ?>
				<?= $this->Form->control('active', ['label' => 'Ativo', 'type' => 'checkbox']) ?>
				<div class="queues-form-actions m-t-20">
					<?= $this->Form->button($isNew ? 'Criar grupo' : 'Salvar', ['class' => 'queues-btn queues-btn--success']) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		<?php endif; ?>
	</div>
</div>
