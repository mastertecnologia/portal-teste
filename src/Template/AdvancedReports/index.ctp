<?php
$this->assign('title', $title ?? 'Indicadores');
?>
<div class="card">
	<div class="card-body">
		<h4 class="card-title"><?= h($title) ?></h4>
		<?= $this->Form->create(null, ['type' => 'get', 'class' => 'form-inline mb-3']) ?>
			<?= $this->Form->control('period_start', ['label' => 'De', 'value' => $ps, 'type' => 'date', 'class' => 'form-control form-control-sm']) ?>
			<?= $this->Form->control('period_end', ['label' => 'Até', 'value' => $pe, 'type' => 'date', 'class' => 'form-control form-control-sm']) ?>
			<?= $this->Form->button('Filtrar', ['class' => 'btn btn-sm btn-primary']) ?>
		<?= $this->Form->end() ?>
		<p class="small"><?= $this->Html->link('Exportar CSV', ['action' => 'export', '?' => $this->request->getQueryParams()], ['class' => 'btn btn-sm btn-outline-secondary']) ?></p>
		<table class="table table-bordered w-auto">
			<tr><th>Tickets (empresa)</th><td><?= h((string)$ticketsCount) ?></td></tr>
			<tr><th>Contratos avançados (criados no período)</th><td><?= h((string)$contractsCount) ?></td></tr>
			<tr><th>Soma faturas avançadas</th><td><?= h((string)$invoicesTotal) ?></td></tr>
		</table>
	</div>
</div>
