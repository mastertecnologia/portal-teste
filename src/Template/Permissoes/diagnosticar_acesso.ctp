<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões RBAC/ABAC', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Diagnóstico de acesso', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero">
			<h1><?= h($title) ?></h1>
			<p>Informe um usuário da <strong>equipe</strong> e a rota (controller + action) para simular o que o <code class="ap-code-violet">RbacComponent</code> faria após o login. Nenhuma alteração é gravada.</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('<i class="fa fa-arrow-left"></i> Catálogo de permissões', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
				<?= $this->Html->link('<i class="fa fa-user-tag"></i> Papéis por usuário', ['action' => 'adminUsers'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
				<?= $this->Html->link('<i class="fa fa-flask"></i> Simular com overrides', ['action' => 'simularDiagnosticoAcesso'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
			</div>
		</header>

		<?php if (!empty($rbacMissing)) : ?>
			<div class="admin-rbac-callout">
				<strong>Tabelas RBAC incompletas.</strong> Execute as migrations e volte a esta página.
			</div>
		<?php else : ?>
			<div class="admin-rbac-callout" style="margin-bottom:1rem;">
				<strong>Onde isto roda no código:</strong>
				<code class="ap-code-blue">AppController::beforeFilter</code> chama
				<code class="ap-code-violet">RbacComponent::checkRequest</code> (validação RBAC).
				Mapeamento controller/ação → permissões está na tabela <code class="ap-code-blue">rbac_permissions</code> (sincronizada com
				<code class="ap-code-blue">config/permissions_registry.php</code>). Escopo de consultas ORM:
				<code class="ap-code-violet">AbacComponent::applyToQuery</code> / <code class="ap-code-blue">App\Utility\AbacQuery</code> e
				<code class="ap-code-blue">config/abac.php</code>.
			</div>

			<?= $this->Form->create(null, ['class' => 'admin-rbac-form']) ?>
			<div class="row">
				<div class="col-md-3">
					<?= $this->Form->control('user_id', [
						'label' => 'ID do usuário (equipe)',
						'type' => 'number',
						'class' => 'form-control',
						'value' => $this->request->getData('user_id'),
						'required' => true,
					]) ?>
					<?php if (!empty($recentUsers)) : ?>
						<p class="help-block" style="margin-top:0.5rem;"><strong>Recentes:</strong>
							<?php foreach ($recentUsers as $ru) : ?>
								<?= $this->Html->link((string)$ru->id, '#', [
									'class' => 'js-diag-fill-user',
									'data-id' => (int)$ru->id,
									'title' => h($ru->username . ' — ' . $ru->name),
								]) ?>
								&nbsp;
							<?php endforeach; ?>
						</p>
					<?php endif; ?>
				</div>
				<div class="col-md-3">
					<?= $this->Form->control('controller', [
						'label' => 'Controller',
						'class' => 'form-control',
						'placeholder' => 'Ex.: Bancosenhas',
						'value' => $this->request->getData('controller'),
						'required' => true,
					]) ?>
				</div>
				<div class="col-md-2">
					<?= $this->Form->control('action', [
						'label' => 'Action',
						'class' => 'form-control',
						'placeholder' => 'Ex.: view',
						'value' => $this->request->getData('action'),
						'required' => true,
					]) ?>
				</div>
				<div class="col-md-2">
					<?= $this->Form->control('prefix', [
						'label' => 'Prefix (opc.)',
						'class' => 'form-control',
						'value' => $this->request->getData('prefix'),
					]) ?>
				</div>
				<div class="col-md-2">
					<?= $this->Form->control('plugin', [
						'label' => 'Plugin (opc.)',
						'class' => 'form-control',
						'value' => $this->request->getData('plugin'),
					]) ?>
				</div>
			</div>
			<div class="form-group">
				<?= $this->Form->button('<i class="fa fa-stethoscope"></i> Diagnosticar', ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
			</div>
			<?= $this->Form->end() ?>

			<?php if ($report !== null && is_array($report)) : ?>
				<hr>
				<h2>Resultado</h2>
				<p class="admin-rbac-meta"><?= h($report['disclaimer'] ?? '') ?></p>

				<?php $rb = $report['rbac'] ?? []; ?>
				<table class="table table-bordered table-condensed" style="max-width:900px;">
					<tbody>
						<tr><th>RBAC mode</th><td><?= h((string)($rb['mode'] ?? '')) ?></td></tr>
						<tr><th>Tabelas core</th><td><?= !empty($rb['core_tables']) ? 'OK' : 'ausentes' ?></td></tr>
						<tr><th>Whitelist rota</th><td><?= !empty($rb['whitelisted']) ? 'sim (RBAC não exige catálogo)' : 'não' ?></td></tr>
						<tr><th>Prefixo api ignorado</th><td><?= !empty($rb['skipped_api_prefix']) ? 'sim' : 'não' ?></td></tr>
						<tr><th>Bypass admin legado</th><td><?= !empty($rb['legacy_admin_bypass']) ? 'sim (RBAC de rota não aplica)' : 'não' ?></td></tr>
						<tr><th>Usuário tem permissão (simulado)</th><td>
							<?php if ($report['user_has_permission'] === true) : ?>
								<span class="label label-success">sim</span>
							<?php elseif ($report['user_has_permission'] === false) : ?>
								<span class="label label-danger">não</span>
							<?php else : ?>
								<span class="label label-default">—</span>
							<?php endif; ?>
							<?php if (!empty($report['deny_reason'])) : ?>
								· motivo: <code><?= h($report['deny_reason']) ?></code>
							<?php endif; ?>
						</td></tr>
					</tbody>
				</table>

				<h3>Papéis efetivos</h3>
				<ul>
					<?php foreach ($report['user_roles'] ?? [] as $r) : ?>
						<li><?= h($r['name']) ?> <small>(id <?= (int)$r['role_id'] ?><?= !empty($r['slug']) ? ', ' . h($r['slug']) : '' ?>)</small></li>
					<?php endforeach; ?>
					<?php if (empty($report['user_roles'])) : ?><li><em>Nenhum papel RBAC atribuído.</em></li><?php endif; ?>
				</ul>
				<?php if (!empty($report['roles_via_groups_only'])) : ?>
					<p><strong>Só via grupos:</strong>
						<?= h(implode(', ', array_column($report['roles_via_groups_only'], 'name'))) ?>
					</p>
				<?php endif; ?>

				<h3>Permissões do catálogo que cobrem a rota</h3>
				<table class="table table-striped table-bordered table-condensed">
					<thead>
						<tr>
							<th>Código</th><th>Nome</th><th>Controller#action</th><th>abac_scope</th><th>Usuário tem</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$hasCodes = [];
						foreach ($report['matching_permissions_user_has'] ?? [] as $m) {
							$hasCodes[(string)$m['code']] = true;
						}
						?>
						<?php foreach ($report['catalog_matches'] ?? [] as $row) : ?>
							<tr>
								<td><code><?= h($row['code']) ?></code></td>
								<td><?= h($row['name']) ?></td>
								<td><code><?= h($row['controller']) ?>#<?= h($row['action']) ?></code></td>
								<td><?= h((string)($row['abac_scope'] ?? '—')) ?></td>
								<td><?= isset($hasCodes[$row['code']]) ? 'sim' : 'não' ?></td>
							</tr>
						<?php endforeach; ?>
						<?php if (empty($report['catalog_matches'])) : ?>
							<tr><td colspan="5"><em>Nenhuma permissão no banco cobre esta rota.</em></td></tr>
						<?php endif; ?>
					</tbody>
				</table>

				<?php if (!empty($report['missing_permission_codes'])) : ?>
					<h3>Códigos exigidos pelo catálogo (que o usuário não tem)</h3>
					<p>
						<?php foreach ($report['missing_permission_codes'] as $mc) : ?>
							<code><?= h($mc) ?></code>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>

				<?php if (!empty($report['effective_match'])) : ?>
					<h3>Permissão que prevaleceria (entre as que o usuário tem)</h3>
					<p><code><?= h($report['effective_match']['code']) ?></code> — <?= h($report['effective_match']['name']) ?></p>
				<?php endif; ?>

				<?php if (!empty($report['rbac_policies'])) : ?>
					<h3>Políticas RBAC (rbac_permission_policies)</h3>
					<pre class="admin-rbac-pre"><?= h(json_encode($report['rbac_policies'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
				<?php endif; ?>

				<h3>ABAC</h3>
				<p><?= h($report['abac']['description'] ?? '') ?></p>

				<h3>Papéis que já incluem alguma permissão desta rota (catálogo)</h3>
				<ul>
					<?php foreach ($report['roles_that_would_grant'] ?? [] as $g) : ?>
						<li><?= h($g['name']) ?> — exemplo <code><?= h($g['example_permission_code']) ?></code></li>
					<?php endforeach; ?>
					<?php if (empty($report['roles_that_would_grant'])) : ?><li><em>Nenhum papel liga-se às permissões que cobrem a rota.</em></li><?php endif; ?>
				</ul>

				<h3>Sugestões (somente texto)</h3>
				<ul>
					<?php foreach ($report['suggestions'] ?? [] as $s) : ?>
						<li><?= h($s) ?></li>
					<?php endforeach; ?>
					<?php foreach ($report['legacy_notes'] ?? [] as $n) : ?>
						<li><?= h($n) ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<script>
			(function () {
				document.querySelectorAll('.js-diag-fill-user').forEach(function (a) {
					a.addEventListener('click', function (e) {
						e.preventDefault();
						var id = this.getAttribute('data-id');
						var inp = document.querySelector('input[name="user_id"]');
						if (inp && id) inp.value = id;
					});
				});
			})();
			</script>
		<?php endif; ?>
	</div>
</div>
