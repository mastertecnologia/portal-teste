<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Papéis por usuário', ['action' => 'adminUsers'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Efetivo: ' . h($user->username), [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Permissões efetivas</h1>
			<p><?= h($user->name ?: $user->username) ?> — <code class="ap-code-violet"><?= h($user->username) ?></code></p>
			<p class="admin-rbac-role-intro">União dos papéis <strong>diretos</strong> e dos papéis dos <strong>grupos</strong>; contagem “após expansão” segue <code class="ap-code-blue">Rbac.expand_legacy_aliases</code> em <code class="ap-code-blue">config/rbac.php</code> (como o <code class="ap-code-violet">RbacComponent</code>).</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Editar papéis', ['action' => 'adminUserRoles', $user->id], ['class' => 'admin-panel-btn admin-panel-btn--teal']) ?>
				<?= $this->Html->link('Matriz (coluna efetiva)', ['action' => 'adminMatrix', '?' => ['user_id' => $user->id]], ['class' => 'admin-panel-btn admin-panel-btn--accent']) ?>
				<?= $this->Html->link('Lista de usuários', ['action' => 'adminUsers'], ['class' => 'admin-panel-btn']) ?>
			</div>
		</header>

		<?php if (!empty($rbacMissing)) : ?>
			<div class="admin-rbac-callout">Tabelas RBAC ausentes.</div>
		<?php else : ?>
			<p class="admin-rbac-meta">
				<strong><?= count($roleIds) ?></strong> papel(is) efetivo(s) (IDs: <?= h(implode(', ', $roleIds) ?: '—') ?>)
				· <strong><?= (int)$nPermLinks ?></strong> permissão(ões) na matriz (antes do alias)
				· <strong><?= (int)$nPermExpanded ?></strong> ID(s) após expansão de aliases
			</p>

			<h3 class="admin-rbac-mod-title">Papéis diretos</h3>
			<?php if ($directRoles === []) : ?>
				<p class="admin-rbac-perm-desc">Nenhum vínculo em <code class="ap-code-blue">rbac_users_roles</code>.</p>
			<?php else : ?>
				<ul class="admin-rbac-perm-list">
					<?php foreach ($directRoles as $r) : ?>
						<li class="admin-rbac-perm-item"><strong><?= h($r->name) ?></strong> <span class="admin-rbac-perm-code"><?= h($r->slug) ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<h3 class="admin-rbac-mod-title">Grupos</h3>
			<?php if ($groupBlocks === []) : ?>
				<p class="admin-rbac-perm-desc">Nenhum grupo ou tabelas de grupo indisponíveis.</p>
			<?php else : ?>
				<?php foreach ($groupBlocks as $block) :
					$g = $block['group'];
					$grs = $block['roles'];
					?>
					<div class="admin-rbac-callout admin-rbac-callout--mt">
						<strong><?= $g ? h($g->name) : '?' ?></strong>
						<?php if ($g) : ?><span class="admin-rbac-perm-code"><?= h($g->slug) ?></span><?php endif; ?>
						<?php if ($grs === []) : ?>
							<div class="admin-rbac-perm-desc m-t-10">Sem papéis associados ao grupo.</div>
						<?php else : ?>
							<ul class="admin-rbac-perm-list m-t-10">
								<?php foreach ($grs as $r) : ?>
									<li class="admin-rbac-perm-item"><strong><?= h($r->name) ?></strong> <span class="admin-rbac-perm-code"><?= h($r->slug) ?></span></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>

			<h3 class="admin-rbac-mod-title">Catálogo efetivo (por módulo)</h3>
			<?php if ($byModule === []) : ?>
				<div class="admin-rbac-callout">Nenhuma permissão na união dos papéis (matriz vazia ou sem expansão).</div>
			<?php else : ?>
				<?php foreach ($byModule as $module => $items) : ?>
					<h4 class="admin-rbac-mod-title"><?= h($module) ?> <span class="admin-rbac-meta">(<?= count($items) ?>)</span></h4>
					<ul class="admin-rbac-perm-list">
						<?php foreach ($items as $p) : ?>
							<li class="admin-rbac-perm-item">
								<strong><?= h($p->name) ?></strong>
								<span class="admin-rbac-perm-code"><?= h($p->code) ?></span>
								<div class="admin-rbac-perm-meta"><?= h($p->controller) ?><?= $p->action ? '::' . h($p->action) : '' ?></div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
