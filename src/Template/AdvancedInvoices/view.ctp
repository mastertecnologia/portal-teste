<?php
$this->assign('title', $title ?? 'Fatura');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($invoice->code) ?></h4>
			<dl class="row small">
				<dt class="col-sm-3">Referência</dt><dd class="col-sm-9"><?= h($invoice->reference_month) ?></dd>
				<dt class="col-sm-3">Total</dt><dd class="col-sm-9"><?= h($invoice->total) ?></dd>
				<dt class="col-sm-3">Status</dt><dd class="col-sm-9"><?= h($invoice->status) ?></dd>
				<dt class="col-sm-3">Vencimento</dt><dd class="col-sm-9"><?= h($invoice->due_date ? $invoice->due_date->format('d/m/Y') : '') ?></dd>
			</dl>
			<?php if ($invoice->status !== 'paid'): ?>
				<?= $this->Form->postLink('Marcar como paga', ['action' => 'markPaid', $invoice->id], ['class' => 'btn btn-sm btn-success', 'confirm' => 'Confirmar pagamento desta fatura?']) ?>
			<?php endif; ?>
			<?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-sm btn-secondary']) ?>
		</div>
	</div>
	<?php if (!empty($invoice->invoice_items)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5>Itens</h5>
			<table class="table table-sm mb-0">
				<thead><tr><th>Descrição</th><th>Qtd</th><th>Unit.</th><th>Total</th></tr></thead>
				<tbody>
					<?php foreach ($invoice->invoice_items as $it): ?>
					<tr>
						<td><?= h($it->description) ?></td>
						<td><?= h($it->quantity) ?></td>
						<td><?= h($it->unit_value) ?></td>
						<td><?= h($it->total_value) ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>
</div>
