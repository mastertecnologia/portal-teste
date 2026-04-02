<?php
$this->assign('title', $title ?? 'Indicadores');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<?= $this->Form->create(null, ['type' => 'get', 'class' => 'mb-3']) ?>
			<div class="form-row align-items-end">
				<div class="form-group col-md-3 mb-2 mb-md-0">
					<?= $this->Form->control('period_start', ['label' => 'De', 'value' => $ps, 'type' => 'date', 'class' => 'form-control form-control-sm']) ?>
				</div>
				<div class="form-group col-md-3 mb-2 mb-md-0">
					<?= $this->Form->control('period_end', ['label' => 'Até', 'value' => $pe, 'type' => 'date', 'class' => 'form-control form-control-sm']) ?>
				</div>
				<div class="form-group col-md-3 mb-0">
					<?= $this->Form->button('Filtrar', ['class' => 'btn btn-sm btn-primary']) ?>
				</div>
			</div>
			<?= $this->Form->end() ?>
			<p class="small mb-3"><?= $this->Html->link('Exportar CSV', ['action' => 'export', '?' => $this->request->getQueryParams()], ['class' => 'btn btn-sm btn-outline-secondary']) ?></p>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0" style="max-width:520px">
					<tbody>
						<tr><th>Tickets (empresa)</th><td><?= h((string)$ticketsCount) ?></td></tr>
						<tr><th>Contratos avançados (criados no período)</th><td><?= h((string)$contractsCount) ?></td></tr>
						<tr><th>Soma faturas avançadas</th><td><?= h((string)$invoicesTotal) ?></td></tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
