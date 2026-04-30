<?php
$this->assign('title', $title);
?>
<div class="col-md-12 card">
	<div class="card-header"><strong><?= h($title) ?></strong></div>
	<div class="card-body">
		<?php if (!empty($grantTableMissing)) : ?>
			<p>Tabela rbac_access_grants ausente. Rode a migration.</p>
		<?php else : ?>
			<table class="table table-bordered table-striped">
				<tr>
					<th>ID</th>
					<th>Pedido</th>
					<th>User</th>
					<th>Role</th>
					<th>Vence</th>
					<th>Status</th>
					<th></th>
				</tr>
				<?php foreach ($rows as $g) : ?>
					<tr>
						<td><?= (int)$g->id ?></td>
						<td><?= (int)$g->access_request_id ?></td>
						<td><?= (int)$g->user_id ?></td>
						<td><?= (int)$g->role_id ?></td>
						<td><?= h((string)$g->expires_at) ?></td>
						<td><?= h((string)$g->status) ?></td>
						<td>
							<?php if ((string)$g->status === 'active') : ?>
								<?= $this->Form->postLink('Revogar', ['action' => 'revogar', $g->id], ['data' => ['revoke_reason' => 'manual']]) ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</div>
</div>
