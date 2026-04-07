<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Auditoria RBAC', [], ['class' => 'breadcrumb-item active']);
$params = $this->Paginator->params();
$auditCount = isset($auditRows) ? count($auditRows) : 0;
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero admin-rbac-hero--sub">
			<h1>Auditoria RBAC</h1>
			<p>Registos gravados pelo <code class="ap-code-violet">RbacComponent</code> quando <code class="ap-code-blue">Rbac.audit_decisions_db</code> está ativo (<strong>true</strong> = negações; <strong>all</strong> = também concessões). CLI: <code class="ap-code-blue">bin/cake rbac_rollout audit_recent</code>.</p>
			<p class="admin-rbac-meta">Modo atual em config: <strong><?= h(var_export($auditDecisionsDbMode, true)) ?></strong>
				<?php if (empty($auditDecisionsDbMode)) : ?>
					— <span class="ap-text-bright">nenhum registo novo será criado até ativar.</span>
				<?php endif; ?>
			</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('← Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn']) ?>
			</div>
		</header>

		<?php if (!empty($rbacAuditMissing)) : ?>
			<div class="admin-rbac-callout">Tabela <code class="ap-code-blue">rbac_audit_authorizations</code> ausente — execute a migration da Fase 3.</div>
		<?php elseif ($auditCount === 0) : ?>
			<div class="admin-rbac-callout">Nenhum registo ainda. Ative <code class="ap-code-blue">audit_decisions_db</code> em <code class="ap-code-blue">config/rbac.php</code> e use RBAC em modo <strong>warn</strong> ou <strong>enforce</strong> para gerar entradas.</div>
		<?php else : ?>
			<p class="admin-rbac-meta">
				Página <strong><?= (int)($params['page'] ?? 1) ?></strong> de <strong><?= max(1, (int)($params['pageCount'] ?? 1)) ?></strong>
				· Total <strong><?= (int)($params['count'] ?? 0) ?></strong> registo(s)
			</p>
			<div class="admin-rbac-matrix-outer">
				<table class="admin-rbac-matrix">
					<thead>
						<tr>
							<th>ID</th>
							<th>Data</th>
							<th>Utilizador</th>
							<th>Resultado</th>
							<th>Controller::action</th>
							<th>Código permissão</th>
							<th>Contexto (resumo)</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($auditRows as $row) :
							$granted = !empty($row->granted);
							$ctx = $row->context_json;
							$ctxShort = $ctx !== null && $ctx !== '' ? (strlen($ctx) > 80 ? substr($ctx, 0, 80) . '…' : $ctx) : '—';
							?>
							<tr>
								<td><?= (int)$row->id ?></td>
								<td class="admin-rbac-td-left"><?= h($row->created ? $row->created->format('Y-m-d H:i:s') : '—') ?></td>
								<td class="admin-rbac-td-left">
									<?= (int)$row->user_id ?>
									<?= $this->Html->link('papéis', ['action' => 'adminUserRoles', $row->user_id], ['class' => 'admin-section-card-link admin-rbac-table-link']) ?>
								</td>
								<td><?= $granted ? '<span class="cell-yes">permitido</span>' : '<span class="cell-no">negado</span>' ?></td>
								<td class="admin-rbac-td-left"><code class="ap-code-violet"><?= h($row->controller) ?>::<?= h($row->action) ?></code></td>
								<td class="admin-rbac-td-left"><?= $row->permission_code ? h($row->permission_code) : '—' ?></td>
								<td class="admin-rbac-td-left"><span title="<?= h((string)$ctx) ?>"><?= h($ctxShort) ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<nav class="m-t-20"><?= $this->Paginator->numbers(['prev' => true, 'next' => true]) ?></nav>
		<?php endif; ?>
	</div>
</div>
