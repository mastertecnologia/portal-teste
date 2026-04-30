<div class="col-md-12">
	<div class="card">
		<div class="card-header"><strong><?= h($title) ?></strong></div>
		<div class="card-body table-responsive">
			<table class="table table-striped table-bordered table-condensed">
				<thead>
					<tr>
						<th>ID</th>
						<th>Data</th>
						<th>Ator</th>
						<th>Alvo</th>
						<th>Pedido</th>
						<th>Ação</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach (($rows ?? []) as $r) : ?>
					<tr>
						<td><?= (int)$r->id ?></td>
						<td><?= h((string)$r->created) ?></td>
						<td><?= (int)$r->actor_user_id ?></td>
						<td><?= (int)$r->target_user_id ?></td>
						<td><?= (int)$r->access_request_id ?></td>
						<td><code><?= h((string)$r->action_type) ?></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

