<?php
$this->Breadcrumbs->add('Dashboard', ['controller' => 'Users', 'action' => 'dashboard'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Meus pedidos de acesso', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-header">
			<strong><?= h($title) ?></strong>
		</div>
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-striped table-bordered">
					<thead>
						<tr>
							<th>Data</th>
							<th>Código suporte</th>
							<th>Rota</th>
							<th>Status</th>
							<th>Resposta admin</th>
							<th>Revisado em</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $r) : ?>
							<tr>
								<td><?= h((string)$r->created) ?></td>
								<td><code><?= h($r->support_code) ?></code></td>
								<td><code><?= h($r->controller) ?>#<?= h($r->action) ?></code></td>
								<td><?= h($r->status) ?></td>
								<td><?= h((string)($r->admin_response ?? '')) ?></td>
								<td><?= h((string)($r->reviewed_at ?? '')) ?></td>
								<td><?= $this->Html->link('Ver', ['action' => 'visualizarPedidoAcesso', $r->id]) ?></td>
							</tr>
						<?php endforeach; ?>
						<?php if (count($rows) === 0) : ?>
							<tr><td colspan="7"><em>Nenhum pedido encontrado.</em></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

