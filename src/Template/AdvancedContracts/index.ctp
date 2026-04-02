<?php
$this->assign('title', $title ?? 'Contratos');
?>
<div class="card">
	<div class="card-body">
		<h4 class="card-title"><?= h($title) ?></h4>
		<p class="text-muted small">Módulo avançado (tabela <code>contracts</code>). Filtro opcional: <code>?idcliente=</code></p>
		<div class="table-responsive">
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Código</th>
						<th>Nome</th>
						<th>Cliente</th>
						<th>Status</th>
						<th>Vigência</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($contracts as $c): ?>
					<tr>
						<td><?= h($c->code) ?></td>
						<td><?= h($c->name) ?></td>
						<td><?= isset($c->cliente) ? h($c->cliente->razaosocial ?: $c->cliente->nome) : '—' ?></td>
						<td><?= h($c->status) ?></td>
						<td><?= h($c->start_date ? $c->start_date->format('d/m/Y') : '') ?> — <?= h($c->end_date ? $c->end_date->format('d/m/Y') : '') ?></td>
						<td><?= $this->Html->link('Ver', ['action' => 'view', $c->id], ['class' => 'btn btn-sm btn-outline-primary']) ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if (!empty($this->Paginator->params()['count'])): ?>
			<nav><?= $this->Paginator->numbers() ?></nav>
		<?php endif; ?>
	</div>
</div>
