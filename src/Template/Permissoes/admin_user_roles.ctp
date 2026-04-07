<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Papéis por usuário', ['action' => 'adminUsers'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add(h($user->username), [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 col-lg-8 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Papéis de <?= h($user->name ?: $user->username) ?></h1>
			<p>Login: <code class="ap-code-violet"><?= h($user->username) ?></code></p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Lista de usuários', ['action' => 'adminUsers'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('Relatório efetivo', ['action' => 'adminUserEffective', $user->id], ['class' => 'admin-panel-btn admin-panel-btn--accent']) ?>
			</div>
		</header>

		<?php if (!empty($rbacMissing)) : ?>
			<div class="admin-rbac-callout">Tabelas RBAC ausentes.</div>
		<?php else : ?>
			<div class="queues-form-panel queues-form-panel--wide admin-rbac-role-panel">
				<?= $this->Form->create(null, ['url' => ['action' => 'adminUserRoles', $user->id]]) ?>
				<p class="admin-rbac-role-intro">Marque os papéis. As permissões efetivas vêm da <strong class="ap-text-bright">união</strong> das permissões de todos os papéis (matriz papel × permissão).</p>
				<?php foreach ($roles as $r) : ?>
					<div class="custom-control custom-checkbox mb-2">
						<?php
						$rid = (int)$r->id;
						$checked = in_array($rid, $selected, true);
						?>
						<input type="checkbox" class="custom-control-input" name="role_ids[]" value="<?= $rid ?>" id="role_<?= $rid ?>" <?= $checked ? 'checked' : '' ?>>
						<label class="custom-control-label admin-rbac-role-lbl" for="role_<?= $rid ?>">
							<strong class="admin-rbac-role-name"><?= h($r->name) ?></strong>
							<span class="admin-rbac-role-slug"> (<?= h($r->slug) ?>)</span>
							<?php if (!empty($r->description)) : ?>
								<br><span class="admin-rbac-role-desc"><?= h($r->description) ?></span>
							<?php endif; ?>
						</label>
					</div>
				<?php endforeach; ?>
				<div class="queues-form-actions m-t-20">
					<?= $this->Form->button('Salvar papéis', ['class' => 'queues-btn queues-btn--success']) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		<?php endif; ?>
	</div>
</div>
