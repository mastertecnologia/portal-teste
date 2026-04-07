<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Grupos', ['action' => 'adminGroups'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Membros: ' . h($group->name), [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 col-lg-8 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Membros do grupo <?= h($group->name) ?></h1>
			<p>Apenas utilizadores da <strong class="ap-text-bright">equipe</strong> (sem vínculo cliente portal).</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Grupos', ['action' => 'adminGroups'], ['class' => 'admin-panel-btn']) ?>
			</div>
		</header>

		<?php if (!empty($rbacGroupsMissing)) : ?>
			<div class="admin-rbac-callout">Tabelas de grupos ausentes.</div>
		<?php else : ?>
			<div class="queues-form-panel queues-form-panel--wide admin-rbac-role-panel">
				<?= $this->Form->create(null, ['url' => ['action' => 'adminGroupUsers', $group->id]]) ?>
				<?php foreach ($users as $u) : ?>
					<div class="custom-control custom-checkbox mb-2">
						<?php
						$uid = (int)$u->id;
						$checked = in_array($uid, $selected, true);
						?>
						<input type="checkbox" class="custom-control-input" name="user_ids[]" value="<?= $uid ?>" id="gr_u_<?= $uid ?>" <?= $checked ? 'checked' : '' ?>>
						<label class="custom-control-label admin-rbac-role-lbl" for="gr_u_<?= $uid ?>">
							<strong class="admin-rbac-role-name"><?= h($u->name ?: $u->username) ?></strong>
							<span class="admin-rbac-role-slug"> (<?= h($u->username) ?>)</span>
						</label>
					</div>
				<?php endforeach; ?>
				<div class="queues-form-actions m-t-20">
					<?= $this->Form->button('Salvar membros', ['class' => 'queues-btn queues-btn--success']) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		<?php endif; ?>
	</div>
</div>
