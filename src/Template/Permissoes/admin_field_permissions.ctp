<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Campos por permissão', [], ['class' => 'breadcrumb-item active']);
$params = $this->Paginator->params();
$rowCount = isset($fieldRows) ? count($fieldRows) : 0;
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Campos por permissão</h1>
			<p>Linhas em <code class="ap-code-blue">rbac_field_permissions</code>. Nas views, use <code class="ap-code-violet">RbacChecker::resourceFieldAccess($userId, 'Modulo.campo.chave')</code>: <strong>hidden</strong> oculta sem a permissão; <strong>readonly</strong> exige a permissão e força só leitura; <strong>inherit</strong> ignora a linha. Exemplos no portal: <code class="ap-code-blue">Clientes.field.api_token</code>, <code class="ap-code-blue">Clientes.field.inativo</code> (com proteção no POST em <code class="ap-code-violet">ClientesController::edit</code>).</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('<i class="fa fa-plus"></i> Nova regra', ['action' => 'adminFieldPermissionEdit'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
			</div>
		</header>

		<?php if (!empty($rbacFieldPermsMissing)) : ?>
			<div class="admin-rbac-callout">Tabela <code class="ap-code-blue">rbac_field_permissions</code> ausente — execute a migration da Fase 3.</div>
		<?php elseif ($rowCount === 0) : ?>
			<div class="admin-rbac-callout">Nenhuma regra cadastrada. Use <strong>Nova regra</strong> ou importe dados manualmente.</div>
		<?php else : ?>
			<p class="admin-rbac-meta">
				Página <strong><?= (int)($params['page'] ?? 1) ?></strong> de <strong><?= max(1, (int)($params['pageCount'] ?? 1)) ?></strong>
				· Total <strong><?= (int)($params['count'] ?? 0) ?></strong> linha(s)
			</p>
			<div class="admin-rbac-matrix-outer">
				<table class="admin-rbac-matrix">
					<thead>
						<tr>
							<th>ID</th>
							<th>Ativa</th>
							<th>Ordem</th>
							<th>Chave recurso</th>
							<th>Modo</th>
							<th>Permissão</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($fieldRows as $row) :
							$permCode = !empty($row->rbac_permission) ? $row->rbac_permission->code : '—';
							?>
							<tr>
								<td><?= (int)$row->id ?></td>
								<td><?= !empty($row->active) ? '<span class="cell-yes">sim</span>' : '<span class="cell-no">não</span>' ?></td>
								<td><?= (int)$row->sort_order ?></td>
								<td class="admin-rbac-td-left"><code class="ap-code-violet"><?= h($row->resource_key) ?></code></td>
								<td><?= h($row->access_mode) ?></td>
								<td class="admin-rbac-td-left"><code class="ap-code-violet"><?= h($permCode) ?></code></td>
								<td class="admin-rbac-td-left">
									<?= $this->Html->link('Editar', ['action' => 'adminFieldPermissionEdit', $row->id], ['class' => 'admin-section-card-link admin-rbac-table-link']) ?>
									<?= $this->Form->postLink(
										'Excluir',
										['action' => 'adminFieldPermissionDelete', $row->id],
										['class' => 'admin-section-card-link admin-rbac-table-link', 'confirm' => 'Excluir esta regra?']
									) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<nav class="m-t-20"><?= $this->Paginator->numbers(['prev' => true, 'next' => true]) ?></nav>
		<?php endif; ?>
	</div>
</div>
