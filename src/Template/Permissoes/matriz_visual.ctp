<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões RBAC/ABAC', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Matriz visual', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1><?= h($title) ?></h1>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('Exportar CSV', ['action' => 'matrizVisualCsv', '?' => $this->request->getQueryParams()], ['class' => 'admin-panel-btn admin-panel-btn--teal']) ?>
			</div>
		</header>

		<?= $this->Form->create(null, ['type' => 'get', 'class' => 'admin-rbac-form']) ?>
		<div class="row">
			<div class="col-md-3"><?= $this->Form->control('module', ['type' => 'select', 'options' => $moduleOptions, 'empty' => 'Todos', 'label' => 'Módulo', 'value' => $ctx['filters']['module'] ?? '', 'class' => 'form-control']) ?></div>
			<div class="col-md-3"><?= $this->Form->control('controller', ['label' => 'Controller', 'value' => $ctx['filters']['controller'] ?? '', 'class' => 'form-control']) ?></div>
			<div class="col-md-2"><?= $this->Form->control('action', ['label' => 'Action', 'value' => $ctx['filters']['action'] ?? '', 'class' => 'form-control']) ?></div>
			<div class="col-md-3"><?= $this->Form->control('role_id', ['type' => 'select', 'options' => $roleOptions, 'empty' => 'Todos', 'label' => 'Papel', 'value' => $ctx['filters']['role_id'] ?? '', 'class' => 'form-control']) ?></div>
			<div class="col-md-1"><label>&nbsp;</label><?= $this->Form->button('Filtrar', ['class' => 'btn btn-primary btn-block']) ?></div>
		</div>
		<?= $this->Form->end() ?>

		<p class="admin-rbac-meta m-t-10">
			Órfãs (na página): <strong><?= (int)($ctx['orphan_count_page'] ?? 0) ?></strong>
			· Papéis sem usuários: <strong><?= count($ctx['roles_without_users'] ?? []) ?></strong>
			· Página <strong><?= (int)($ctx['pagination']['page'] ?? 1) ?></strong> / <strong><?= (int)($ctx['pagination']['total_pages'] ?? 1) ?></strong>
			· Total permissões filtradas: <strong><?= (int)($ctx['pagination']['total'] ?? 0) ?></strong>
		</p>

		<div class="table-responsive">
			<table class="table table-striped table-bordered table-condensed">
				<thead>
					<tr>
						<th>Permissão</th>
						<?php foreach (($ctx['roles'] ?? []) as $r) : ?>
							<th><?= h($r->name) ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach (($ctx['permissions'] ?? []) as $p) : ?>
						<?php $isCritical = in_array((string)($p->criticality ?? ''), ['critical', 'high'], true); ?>
						<tr class="<?= $isCritical ? 'warning' : '' ?>">
							<td>
								<strong><?= h($p->code) ?></strong><br>
								<small><?= h(($p->module ?? '—') . ' · ' . ($p->controller ?? '') . '#' . ($p->action ?? '')) ?></small>
								<?php if (!empty($p->criticality)) : ?><span class="label label-default"><?= h($p->criticality) ?></span><?php endif; ?>
							</td>
							<?php foreach (($ctx['roles'] ?? []) as $r) : ?>
								<td><?= !empty($ctx['link_map'][(int)$r->id][(int)$p->id]) ? '✓' : '—' ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		$page = (int)($ctx['pagination']['page'] ?? 1);
		$totalPages = (int)($ctx['pagination']['total_pages'] ?? 1);
		$q = $this->request->getQueryParams();
		?>
		<?php if ($totalPages > 1) : ?>
			<div class="m-t-10">
				<?php if ($page > 1) : ?>
					<?= $this->Html->link('← Anterior', ['action' => 'matrizVisual', '?' => array_merge($q, ['page' => $page - 1])], ['class' => 'btn btn-default']) ?>
				<?php endif; ?>
				<?php if ($page < $totalPages) : ?>
					<?= $this->Html->link('Próxima →', ['action' => 'matrizVisual', '?' => array_merge($q, ['page' => $page + 1])], ['class' => 'btn btn-default']) ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

