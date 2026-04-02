<?php
$this->assign('title', $title ?? 'Faturas');
?>
<div class="card">
	<div class="card-body">
		<h4 class="card-title"><?= h($title) ?></h4>
		<p class="small">
			<?= $this->Html->link('Exportar CSV', ['action' => 'export', '?' => $this->request->getQueryParams()], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
		</p>
		<div class="table-responsive">
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Código</th>
						<th>Mês</th>
						<th>Cliente</th>
						<th>Total</th>
						<th>Status</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($invoices as $inv): ?>
					<tr>
						<td><?= h($inv->code) ?></td>
						<td><?= h($inv->reference_month) ?></td>
						<td><?= isset($inv->cliente) ? h($inv->cliente->razaosocial ?: $inv->cliente->nome) : '—' ?></td>
						<td><?= h($inv->total) ?></td>
						<td><?= h($inv->status) ?></td>
						<td><?= $this->Html->link('Ver', ['action' => 'view', $inv->id], ['class' => 'btn btn-sm btn-outline-primary']) ?></td>
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
