<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Permissões RBAC/ABAC', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Simular diagnóstico', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="admin-rbac-wrap">
		<header class="admin-panel-hero">
			<h1><?= h($title) ?></h1>
			<p>Teste <strong>sem bloquear</strong> o utilizador: aplica-se uma cópia em memória do registo do utilizador com <code class="ap-code-blue">idempresa</code> / <code class="ap-code-blue">idcliente</code> opcionais para avaliar <code class="ap-code-violet">rbac_permission_policies</code> e texto ABAC. Nada é gravado na base.</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('<i class="fa fa-arrow-left"></i> Catálogo', ['action' => 'adminIndex'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
				<?= $this->Html->link('<i class="fa fa-stethoscope"></i> Diagnóstico simples', ['action' => 'diagnosticarAcesso'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
			</div>
		</header>

		<?php if (!empty($rbacMissing)) : ?>
			<div class="admin-rbac-callout">
				<strong>Tabelas RBAC incompletas.</strong> Execute as migrations e volte a esta página.
			</div>
		<?php else : ?>
			<div class="admin-rbac-callout m-b-15">
				<strong>Saída esperada:</strong> permissões exigidas pelo catálogo (OR), papéis atuais, códigos em falta, papéis que já liberam a rota, avaliação linha a linha de <code>conditions_json</code> (policies RBAC), escopo ABAC, sugestões de liberação (apenas texto).
			</div>

			<?= $this->Form->create(null, ['class' => 'admin-rbac-form']) ?>
			<div class="row">
				<div class="col-md-2">
					<?= $this->Form->control('user_id', [
						'label' => 'ID usuário (equipe)',
						'type' => 'number',
						'class' => 'form-control',
						'value' => $this->request->getData('user_id'),
						'required' => true,
					]) ?>
					<?php if (!empty($recentUsers)) : ?>
						<p class="help-block small m-t-5"><strong>Recentes:</strong>
							<?php foreach ($recentUsers as $ru) : ?>
								<?= $this->Html->link((string)$ru->id, '#', [
									'class' => 'js-sim-fill-user',
									'data-id' => (int)$ru->id,
									'title' => h($ru->username . ' — ' . $ru->name),
								]) ?> &nbsp;
							<?php endforeach; ?>
						</p>
					<?php endif; ?>
				</div>
				<div class="col-md-2">
					<?= $this->Form->control('controller', [
						'label' => 'Controller',
						'class' => 'form-control',
						'value' => $this->request->getData('controller'),
						'required' => true,
					]) ?>
				</div>
				<div class="col-md-2">
					<?= $this->Form->control('action', [
						'label' => 'Action',
						'class' => 'form-control',
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
				<div class="col-md-2">
					<?= $this->Form->control('resource_id', [
						'label' => 'resource_id (opc.)',
						'class' => 'form-control',
						'placeholder' => 'ex.: id registo',
						'value' => $this->request->getData('resource_id'),
					]) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3">
					<?= $this->Form->control('idempresa', [
						'label' => 'Override idempresa (opc.)',
						'type' => 'number',
						'class' => 'form-control',
						'value' => $this->request->getData('idempresa'),
					]) ?>
				</div>
				<div class="col-md-3">
					<?= $this->Form->control('idcliente', [
						'label' => 'Override idcliente (opc.)',
						'type' => 'number',
						'class' => 'form-control',
						'value' => $this->request->getData('idcliente'),
					]) ?>
				</div>
			</div>
			<div class="form-group">
				<?= $this->Form->button('<i class="fa fa-flask"></i> Simular diagnóstico', ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
			</div>
			<?= $this->Form->end() ?>

			<?php if ($report !== null && is_array($report)) : ?>
				<hr>
				<h2>Resultado do teste</h2>
				<p class="admin-rbac-meta"><?= h($report['disclaimer'] ?? '') ?></p>
				<?php if (!empty($report['simulation']['note'])) : ?>
					<p class="small text-muted"><?= h($report['simulation']['note']) ?></p>
				<?php endif; ?>

				<h3 class="h4">1. Permissões exigidas (catálogo · OR)</h3>
				<p><code><?= h($report['required_permissions_or_label'] ?? '') ?></code></p>
				<?php if (!empty($report['effective_match']['code'])) : ?>
					<p class="small">Permissão que prevaleceria entre as que o utilizador tem: <code><?= h($report['effective_match']['code']) ?></code></p>
				<?php endif; ?>

				<h3 class="h4 m-t-20">2. Papéis atuais (RBAC)</h3>
				<ul>
					<?php foreach ($report['user_roles'] ?? [] as $r) : ?>
						<li><?= h($r['name']) ?> <small>(id <?= (int)$r['role_id'] ?>)</small></li>
					<?php endforeach; ?>
					<?php if (empty($report['user_roles'])) : ?><li><em>Nenhum papel RBAC.</em></li><?php endif; ?>
				</ul>
				<?php if (!empty($report['roles_via_groups_only'])) : ?>
					<p class="small"><strong>Só via grupos:</strong> <?= h(implode(', ', array_column($report['roles_via_groups_only'], 'name'))) ?></p>
				<?php endif; ?>

				<h3 class="h4 m-t-20">3. Permissões em falta (códigos)</h3>
				<p>
					<?php if (!empty($report['missing_permission_codes'])) : ?>
						<?php foreach ($report['missing_permission_codes'] as $mc) : ?>
							<code><?= h($mc) ?></code>
						<?php endforeach; ?>
					<?php else : ?>
						<em>—</em>
					<?php endif; ?>
				</p>

				<h3 class="h4 m-t-20">4. Papéis que já liberam esta rota (catálogo)</h3>
				<ul>
					<?php foreach ($report['roles_that_would_grant'] ?? [] as $g) : ?>
						<li><?= h($g['name']) ?> — ex.: <code><?= h($g['example_permission_code']) ?></code></li>
					<?php endforeach; ?>
					<?php if (empty($report['roles_that_would_grant'])) : ?><li><em>—</em></li><?php endif; ?>
				</ul>

				<h3 class="h4 m-t-20">5. Policies / ABAC avaliados</h3>
				<p class="small"><?= h($report['abac_evaluated']['description'] ?? '') ?></p>
				<p class="small"><strong>abac_scope (permissão efetiva):</strong> <?= h((string)($report['abac_evaluated']['rbac_abac_scope_effective'] ?? '—')) ?></p>
				<?php if (!empty($report['rbac_policy_row_evaluations'])) : ?>
					<p class="small text-muted">Políticas RBAC (<code>rbac_permission_policies.conditions_json</code>) avaliadas com o contexto do utilizador simulado:</p>
					<ul>
						<?php foreach ($report['rbac_policy_row_evaluations'] as $pe) : ?>
							<li>
								<strong>#<?= (int)($pe['policy_id'] ?? 0) ?>:</strong>
								<?= !empty($pe['matched']) ? '<span class="label label-success">ok</span>' : '<span class="label label-danger">não</span>' ?>
								<?= h($pe['detail'] ?? '') ?>
								<?php if (!empty($pe['conditions_json'])) : ?>
									<pre class="small m-t-5" style="max-height:100px;overflow:auto;"><?= h((string)$pe['conditions_json']) ?></pre>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="small"><em>Sem linhas de política para a permissão escolhida ou políticas desligadas.</em></p>
				<?php endif; ?>

				<h3 class="h4 m-t-20">6. Sugestão de liberação (texto)</h3>
				<ul>
					<?php foreach ($report['liberation_hints'] ?? [] as $h) : ?>
						<li><?= h($h) ?></li>
					<?php endforeach; ?>
				</ul>

				<h3 class="h4 m-t-20">Contexto simulado</h3>
				<pre class="small admin-rbac-pre"><?= h(json_encode($report['simulation'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
			<?php endif; ?>

			<script>
			(function () {
				document.querySelectorAll('.js-sim-fill-user').forEach(function (a) {
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
