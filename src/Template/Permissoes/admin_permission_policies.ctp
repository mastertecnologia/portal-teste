<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Políticas por permissão', [], ['class' => 'breadcrumb-item active']);
$params = $this->Paginator->params();
$rowCount = isset($policyRows) ? count($policyRows) : 0;
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Políticas por permissão</h1>
			<p>Linhas em <code class="ap-code-blue">rbac_permission_policies</code> (condições ABAC em JSON). Em runtime, ative <code class="ap-code-blue">evaluate_permission_policies</code> em <code class="ap-code-blue">config/rbac.php</code>. Várias linhas para a mesma permissão funcionam em <strong>OR</strong> (prioridade descendente). Contexto disponível: <code class="ap-code-blue">user.id</code>, <code class="ap-code-blue">user.username</code>, <code class="ap-code-blue">user.role</code>, <code class="ap-code-blue">user.admin</code>, <code class="ap-code-blue">user.idempresa</code>, <code class="ap-code-blue">user.idcliente</code>, <code class="ap-code-blue">user.setor</code>, <code class="ap-code-blue">request.prefix</code>, <code class="ap-code-blue">request.plugin</code>.</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn']) ?>
				<?= $this->Html->link('<i class="fa fa-plus"></i> Nova política', ['action' => 'adminPermissionPolicyEdit'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
			</div>
		</header>

		<?php if (!empty($rbacPoliciesMissing)) : ?>
			<div class="admin-rbac-callout">Tabela <code class="ap-code-blue">rbac_permission_policies</code> ausente — execute a migration da Fase 3.</div>
		<?php elseif ($rowCount === 0) : ?>
			<div class="admin-rbac-callout">Nenhuma política cadastrada. Use <strong>Nova política</strong> ou importe dados manualmente.</div>
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
							<th>Prioridade</th>
							<th>Permissão</th>
							<th>Nome</th>
							<th>Condições (resumo)</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($policyRows as $row) :
							$cj = $row->conditions_json;
							$cjShort = $cj !== null && $cj !== '' ? (strlen((string)$cj) > 60 ? substr((string)$cj, 0, 60) . '…' : (string)$cj) : '— (sem restrição extra)';
							$permCode = !empty($row->rbac_permission) ? $row->rbac_permission->code : '—';
							?>
							<tr>
								<td><?= (int)$row->id ?></td>
								<td><?= !empty($row->active) ? '<span class="cell-yes">sim</span>' : '<span class="cell-no">não</span>' ?></td>
								<td><?= (int)$row->priority ?></td>
								<td class="admin-rbac-td-left"><code class="ap-code-violet"><?= h($permCode) ?></code></td>
								<td class="admin-rbac-td-left"><?= h($row->name) ?></td>
								<td class="admin-rbac-td-left"><span title="<?= h((string)$cj) ?>"><?= h($cjShort) ?></span></td>
								<td class="admin-rbac-td-left">
									<?= $this->Html->link('Editar', ['action' => 'adminPermissionPolicyEdit', $row->id], ['class' => 'admin-section-card-link admin-rbac-table-link']) ?>
									<?= $this->Form->postLink(
										'Excluir',
										['action' => 'adminPermissionPolicyDelete', $row->id],
										['class' => 'admin-section-card-link admin-rbac-table-link', 'confirm' => 'Excluir esta política?']
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
