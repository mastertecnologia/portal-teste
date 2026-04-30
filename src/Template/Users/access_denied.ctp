<?php $this->Breadcrumbs->add('Dashboard', ['controller' => 'users', 'action' => 'dashboard'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Acesso não autorizado', [], ['class' => 'breadcrumb-item active']); ?>

<?php
$reasonLabels = [
	'no_rbac_roles' => 'Sem papéis RBAC atribuídos',
	'no_matching_permission' => 'Nenhuma permissão do catálogo cobre esta rota nos seus papéis',
	'policy_denied' => 'Permissão existe, mas uma política extra (conditions_json) bloqueou',
];
$supportCodeSafe = isset($supportCode) && $supportCode !== '' ? (string)$supportCode : null;
?>

<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<p class="m-b-15">
				Você não tem permissão para acessar a página solicitada. Se precisar desta função, fale com um administrador.
			</p>
			<?php if ($supportCodeSafe !== null) : ?>
				<p class="m-b-15">
					<strong>Código de suporte:</strong> <code><?= h($supportCodeSafe) ?></code>
				</p>
			<?php endif; ?>
			<?php if (!empty($allowAccessRequests) && $supportCodeSafe !== null) : ?>
				<p class="m-b-15">
					<?= $this->Form->postLink(
						'Solicitar acesso',
						['controller' => 'RbacAccessRequests', 'action' => 'solicitarAcesso', $supportCodeSafe],
						['class' => 'btn btn-pgm btn-warning']
					) ?>
				</p>
			<?php endif; ?>
			<p class="m-b-0">
				<?= $this->Html->link('Ir ao painel inicial', ['controller' => 'Users', 'action' => 'dashboard'], ['class' => 'btn btn-pgm btn-success']) ?>
			</p>
		</div>
	</div>

	<?php if (!empty($canViewDetailedDiagnostic) && !empty($rbacDenialReport) && is_array($rbacDenialReport)) : ?>
		<?php
		$cap = $rbacDenialReport['capture'] ?? [];
		$capReason = isset($cap['reason']) ? (string)$cap['reason'] : '';
		$reasonLabel = $reasonLabels[$capReason] ?? $capReason;
		?>
		<div class="card m-t-15">
			<div class="card-header">
				<strong>Diagnóstico RBAC</strong>
				<small class="text-muted"> — referência para o administrador (não concede acesso)</small>
			</div>
			<div class="card-body">
				<p class="m-b-10">
					<strong>Rota negada:</strong>
					<code><?= h($cap['controller'] ?? '') ?>#<?= h($cap['action'] ?? '') ?></code>
					· <strong>Motivo (runtime):</strong> <?= h($reasonLabel !== '' ? $reasonLabel : '—') ?>
					<?php if (!empty($cap['prefix']) || !empty($cap['plugin'])) : ?>
						· <code>prefix=<?= h((string)($cap['prefix'] ?? '')) ?></code>
						<code>plugin=<?= h((string)($cap['plugin'] ?? '')) ?></code>
					<?php endif; ?>
				</p>
				<?php if (!empty($rbacDenialReport['diagnosis_reason_mismatch_note'])) : ?>
					<p class="text-warning small"><?= h($rbacDenialReport['diagnosis_reason_mismatch_note']) ?></p>
				<?php endif; ?>

				<?php if (!empty($rbacDenialReport['user_roles'])) : ?>
					<h4 class="h5 m-t-15">Seus papéis (RBAC)</h4>
					<ul class="m-b-10">
						<?php foreach ($rbacDenialReport['user_roles'] as $r) : ?>
							<li><?= h($r['name']) ?> <small>(id <?= (int)$r['role_id'] ?>)</small></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<h4 class="h5">Permissões no catálogo para esta rota</h4>
				<div class="table-responsive">
					<table class="table table-bordered table-condensed table-striped m-b-10">
						<thead>
							<tr>
								<th>Código</th>
								<th>Nome</th>
								<th>Mapeamento</th>
								<th>Você tem</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$hasCodes = [];
							foreach ($rbacDenialReport['matching_permissions_user_has'] ?? [] as $m) {
								$hasCodes[(string)$m['code']] = true;
							}
							?>
							<?php foreach ($rbacDenialReport['catalog_matches'] ?? [] as $row) : ?>
								<tr>
									<td><code><?= h($row['code']) ?></code></td>
									<td><?= h($row['name']) ?></td>
									<td><code><?= h($row['controller']) ?>#<?= h($row['action']) ?></code></td>
									<td><?= isset($hasCodes[$row['code']]) ? 'sim' : 'não' ?></td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($rbacDenialReport['catalog_matches'])) : ?>
								<tr><td colspan="4"><em>Nenhuma permissão no banco cobre esta rota.</em></td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<?php if (!empty($rbacDenialReport['missing_permission_codes'])) : ?>
					<p><strong>Códigos que faltam nos seus papéis:</strong>
						<?php foreach ($rbacDenialReport['missing_permission_codes'] as $mc) : ?>
							<code><?= h($mc) ?></code>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>

				<?php if (!empty($rbacDenialReport['roles_that_would_grant'])) : ?>
					<h4 class="h5 m-t-15">Papéis que já incluem alguma permissão desta rota</h4>
					<ul class="m-b-10">
						<?php foreach ($rbacDenialReport['roles_that_would_grant'] as $g) : ?>
							<li><?= h($g['name']) ?> — ex.: <code><?= h($g['example_permission_code']) ?></code></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if (!empty($rbacDenialReport['effective_match']) && ($capReason === 'policy_denied')) : ?>
					<h4 class="h5 m-t-15">Permissão que casou antes da política</h4>
					<p><code><?= h($rbacDenialReport['effective_match']['code'] ?? '') ?></code> — <?= h($rbacDenialReport['effective_match']['name'] ?? '') ?></p>
				<?php endif; ?>

				<?php if (!empty($rbacDenialReport['policy_conditions_eval'])) : ?>
					<h4 class="h5 m-t-15">Políticas (rbac_permission_policies · conditions_json)</h4>
					<p class="small text-muted m-b-10">Basta uma linha com condição vazia ou satisfeita para permitir (OR). Se todas falham, o acesso é negado.</p>
					<ul class="m-b-10">
						<?php foreach ($rbacDenialReport['policy_conditions_eval'] as $pe) : ?>
							<li>
								<strong>Policy #<?= (int)($pe['policy_id'] ?? 0) ?>:</strong>
								<?= !empty($pe['matched']) ? '<span class="label label-success">ok</span>' : '<span class="label label-danger">não</span>' ?>
								— <?= h($pe['detail'] ?? '') ?>
								<?php if (!empty($pe['conditions_json'])) : ?>
									<pre class="small m-t-5 m-b-0" style="max-height:120px;overflow:auto;"><?= h((string)$pe['conditions_json']) ?></pre>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if (!empty($rbacDenialReport['abac']['description'])) : ?>
					<h4 class="h5 m-t-15">Escopo ABAC (permissão / consultas)</h4>
					<p class="small m-b-0"><?= h($rbacDenialReport['abac']['description']) ?></p>
				<?php endif; ?>

				<?php if (!empty($rbacDenialReport['suggestions'])) : ?>
					<h4 class="h5 m-t-15">Sugestões</h4>
					<ul class="m-b-0">
						<?php foreach ($rbacDenialReport['suggestions'] as $s) : ?>
							<li><?= h($s) ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
