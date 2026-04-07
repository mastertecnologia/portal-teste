<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Grupos', ['action' => 'adminGroups'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Papéis: ' . h($group->name), [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 col-lg-8 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Papéis do grupo <?= h($group->name) ?></h1>
			<p>Slug: <code class="ap-code-violet"><?= h($group->slug) ?></code></p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Grupos', ['action' => 'adminGroups'], ['class' => 'admin-panel-btn']) ?>
			</div>
		</header>

		<?php if (!empty($rbacGroupsMissing)) : ?>
			<div class="admin-rbac-callout">Tabelas de grupos ausentes.</div>
		<?php else : ?>
			<div class="queues-form-panel queues-form-panel--wide admin-rbac-role-panel">
				<?= $this->Form->create(null, ['url' => ['action' => 'adminGroupRoles', $group->id]]) ?>
				<p class="admin-rbac-role-intro">Membros deste grupo herdam a <strong class="ap-text-bright">união</strong> destes papéis (além dos papéis diretos do utilizador).</p>
				<?php foreach ($roles as $r) : ?>
					<div class="custom-control custom-checkbox mb-2">
						<?php
						$rid = (int)$r->id;
						$checked = in_array($rid, $selected, true);
						?>
						<input type="checkbox" class="custom-control-input" name="role_ids[]" value="<?= $rid ?>" id="gr_role_<?= $rid ?>" <?= $checked ? 'checked' : '' ?>>
						<label class="custom-control-label admin-rbac-role-lbl" for="gr_role_<?= $rid ?>">
							<strong class="admin-rbac-role-name"><?= h($r->name) ?></strong>
							<span class="admin-rbac-role-slug"> (<?= h($r->slug) ?>)</span>
						</label>
					</div>
				<?php endforeach; ?>
				<div class="queues-form-actions m-t-20">
					<?= $this->Form->button('Salvar papéis do grupo', ['class' => 'queues-btn queues-btn--success']) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		<?php endif; ?>
	</div>
</div>
