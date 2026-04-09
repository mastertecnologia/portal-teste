<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Campos', ['action' => 'adminFieldPermissions'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add($id > 0 ? 'Editar' : 'Nova', [], ['class' => 'breadcrumb-item active']);
$isEdit = $id > 0;
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1><?= $isEdit ? 'Editar regra de campo' : 'Nova regra de campo' ?></h1>
			<p>Chave estável por convénio, ex.: <code class="ap-code-blue">Clientes.field.limite_credito</code>. Maior <strong>sort_order</strong> avalia primeiro quando existirem várias linhas para a mesma chave.</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Lista', ['action' => 'adminFieldPermissions'], ['class' => 'admin-panel-btn']) ?>
			</div>
		</header>

		<?php if (!empty($rbacFieldPermsMissing)) : ?>
			<div class="admin-rbac-callout">Tabela <code class="ap-code-blue">rbac_field_permissions</code> ausente.</div>
		<?php else : ?>
			<?= $this->Form->create($fieldPerm) ?>
			<div class="admin-rbac-callout admin-rbac-callout--mt" style="background: transparent; border: 1px solid rgba(255,255,255,.12);">
				<div class="form-group">
					<label>Chave recurso/campo</label>
					<?= $this->Form->text('resource_key', ['class' => 'form-control', 'required' => true]) ?>
				</div>
				<div class="form-group">
					<label>Modo</label>
					<?= $this->Form->select('access_mode', $accessModes, ['class' => 'form-control']) ?>
				</div>
				<div class="form-group">
					<label>Permissão (catálogo) — obrigatória para hidden e readonly</label>
					<?= $this->Form->select('rbac_permission_id', $permList, [
						'empty' => '— selecione —',
						'class' => 'form-control',
					]) ?>
				</div>
				<div class="form-group">
					<label>Ordem (maior primeiro)</label>
					<?= $this->Form->number('sort_order', ['class' => 'form-control']) ?>
				</div>
				<div class="form-group">
					<?= $this->Form->control('active', ['type' => 'checkbox', 'label' => 'Ativa']) ?>
				</div>
				<?= $this->Form->button($isEdit ? 'Salvar' : 'Criar', ['class' => 'btn btn-primary']) ?>
			</div>
			<?= $this->Form->end() ?>
		<?php endif; ?>
	</div>
</div>
