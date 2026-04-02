<?php
$this->assign('title', $title ?? 'Faturas');
$params = $this->Paginator->params();
$invCount = isset($params['count']) ? (int)$params['count'] : (is_countable($invoices) ? count($invoices) : 0);
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<p class="small mb-3">
				<?= $this->Html->link('Exportar CSV', ['action' => 'exportar'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
				<span class="text-muted d-block d-md-inline mt-2 mt-md-0 ml-md-2">Locação / faturas clássicas: <?= $this->Html->link('Locação', '/locacao') ?></span>
			</p>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead><tr><th>Código</th><th>Mês</th><th>Total</th><th>Status</th><th></th></tr></thead>
					<tbody>
						<?php foreach ($invoices as $inv): ?>
						<tr>
							<td><?= h($inv->code) ?></td>
							<td><?= h($inv->reference_month) ?></td>
							<td><?= h($inv->total) ?></td>
							<td><?= h($inv->status) ?></td>
							<td><?= $this->Html->link('Ver', ['action' => 'view', $inv->id], ['class' => 'btn btn-sm btn-outline-primary']) ?></td>
						</tr>
						<?php endforeach; ?>
						<?php if ($invCount === 0): ?>
						<tr>
							<td colspan="5" class="text-muted">Nenhuma fatura no módulo avançado. Use o link de locação acima para faturas já existentes no portal.</td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
			$__pgAdv = $this->Paginator->params();
			if (!empty($__pgAdv['pageCount']) && (int)$__pgAdv['pageCount'] > 1) :
			?>
			<nav class="mt-2"><?= $this->Paginator->numbers(['prev' => true, 'next' => true]) ?></nav>
			<?php endif; ?>
		</div>
	</div>
</div>
