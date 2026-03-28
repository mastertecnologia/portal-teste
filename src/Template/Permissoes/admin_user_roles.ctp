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
		<header class="admin-panel-hero" style="border-bottom-color: rgba(255,255,255,0.08); margin-bottom: 16px;">
			<h1 style="color: #f0f2f8; font-size: 1.2rem; margin: 0 0 8px;">Papéis de <?= h($user->name ?: $user->username) ?></h1>
			<p style="margin: 0;">Login: <code style="color:#c4b5fd;"><?= h($user->username) ?></code></p>
			<div class="admin-panel-hero-actions" style="margin-top: 12px;">
				<?= $this->Html->link('← Lista de usuários', ['action' => 'adminUsers'], ['class' => 'admin-panel-btn']) ?>
			</div>
		</header>

		<?php if (!empty($rbacMissing)) : ?>
			<div class="admin-rbac-callout">Tabelas RBAC ausentes.</div>
		<?php else : ?>
			<div class="queues-form-panel queues-form-panel--wide" style="background:#13161d;border-color:rgba(255,255,255,0.08);">
				<?= $this->Form->create(null, ['url' => ['action' => 'adminUserRoles', $user->id]]) ?>
				<p style="font-size:12px;color:#8b92a8;margin:0 0 14px;">Marque os papéis. As permissões efetivas vêm da <strong style="color:#f0f2f8;">união</strong> das permissões de todos os papéis (matriz papel × permissão).</p>
				<?php foreach ($roles as $r) : ?>
					<div class="custom-control custom-checkbox mb-2">
						<?php
						$rid = (int)$r->id;
						$checked = in_array($rid, $selected, true);
						?>
						<input type="checkbox" class="custom-control-input" name="role_ids[]" value="<?= $rid ?>" id="role_<?= $rid ?>" <?= $checked ? 'checked' : '' ?>>
						<label class="custom-control-label" for="role_<?= $rid ?>" style="color:#c4cad9;">
							<strong style="color:#f0f2f8;"><?= h($r->name) ?></strong>
							<span style="color:#555e78;font-size:11px;"> (<?= h($r->slug) ?>)</span>
							<?php if (!empty($r->description)) : ?>
								<br><span style="font-size:11px;color:#8b92a8;"><?= h($r->description) ?></span>
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
