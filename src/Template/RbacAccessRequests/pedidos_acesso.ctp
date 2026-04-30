<?php
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Pedidos de acesso', [], ['class' => 'breadcrumb-item active']);
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
							<th>ID</th>
							<th>Data</th>
							<th>Usuário</th>
							<th>Código suporte</th>
							<th>Rota</th>
							<th>Status</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $r) : ?>
							<tr>
								<td><?= (int)$r->id ?></td>
								<td><?= h((string)$r->created) ?></td>
								<td><?= (int)$r->user_id ?></td>
								<td><code><?= h($r->support_code) ?></code></td>
								<td><code><?= h($r->controller) ?>#<?= h($r->action) ?></code></td>
								<td><?= h($r->status) ?></td>
								<td><?= $this->Html->link('Abrir', ['action' => 'visualizarPedidoAcesso', $r->id], ['class' => 'btn btn-xs btn-default']) ?></td>
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

