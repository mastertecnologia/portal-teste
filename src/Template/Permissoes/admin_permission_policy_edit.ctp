<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Políticas', ['action' => 'adminPermissionPolicies'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add($id > 0 ? 'Editar' : 'Nova', [], ['class' => 'breadcrumb-item active']);
$isEdit = $id > 0;
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1><?= $isEdit ? 'Editar política' : 'Nova política' ?></h1>
			<p><code class="ap-code-violet">conditions_json</code> vazio = linha não restringe além do RBAC. Formato suportado: <code class="ap-code-blue">{"all":[{"path":"user.role","eq":0}]}</code> (ver <code class="ap-code-violet">RbacPolicyConditions</code>).</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Lista', ['action' => 'adminPermissionPolicies'], ['class' => 'admin-panel-btn']) ?>
			</div>
		</header>

		<?php if (!empty($rbacPoliciesMissing)) : ?>
			<div class="admin-rbac-callout">Tabela <code class="ap-code-blue">rbac_permission_policies</code> ausente.</div>
		<?php else : ?>
			<?= $this->Form->create($policy) ?>
			<div class="admin-rbac-callout admin-rbac-callout--mt" style="background: transparent; border: 1px solid rgba(255,255,255,.12);">
				<div class="form-group">
					<label>Permissão (catálogo)</label>
					<?= $this->Form->select('rbac_permission_id', $permList, [
						'empty' => '— selecione —',
						'class' => 'form-control',
					]) ?>
				</div>
				<div class="form-group">
					<label>Nome</label>
					<?= $this->Form->text('name', ['class' => 'form-control', 'required' => true]) ?>
				</div>
				<div class="form-group">
					<label>Prioridade (maior avaliada primeiro no OR)</label>
					<?= $this->Form->number('priority', ['class' => 'form-control']) ?>
				</div>
				<div class="form-group">
					<?= $this->Form->control('active', ['type' => 'checkbox', 'label' => 'Ativa']) ?>
				</div>
				<div class="form-group">
					<label>conditions_json</label>
					<?= $this->Form->textarea('conditions_json', [
						'class' => 'form-control',
						'rows' => 8,
					]) ?>
				</div>
				<div class="form-group">
					<label>Descrição (interna)</label>
					<?= $this->Form->textarea('description', ['class' => 'form-control', 'rows' => 3]) ?>
				</div>
				<?= $this->Form->button($isEdit ? 'Salvar' : 'Criar', ['class' => 'btn btn-primary']) ?>
			</div>
			<?= $this->Form->end() ?>
		<?php endif; ?>
	</div>
</div>
