<?php
$this->assign('title', $title ?? 'Atendimento');
$hi = $history;
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($hi->subject) ?></h4>
			<p class="small text-muted">Ticket #<?= h($hi->ticket_id) ?></p>
			<dl class="row small mb-0">
				<dt class="col-sm-3">Status</dt><dd class="col-sm-9"><?= h($hi->status) ?></dd>
				<dt class="col-sm-3">SLA</dt><dd class="col-sm-9"><?= h($hi->sla_status) ?></dd>
			</dl>
			<?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-sm btn-secondary mt-2']) ?>
		</div>
	</div>
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h5 class="card-title">Linha do tempo</h5>
			<div class="table-responsive">
				<table class="table table-sm mb-0">
					<thead>
						<tr>
							<th>Quando</th>
							<th>Evento</th>
							<th>Nota pública</th>
							<th>Nota interna</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($timeline as $ev): ?>
						<tr>
							<td><?= h($ev->created ? $ev->created->format('d/m/Y H:i') : '') ?></td>
							<td><?= h($ev->event_label) ?></td>
							<td><?= h($ev->public_note) ?></td>
							<td><?= h($ev->internal_note) ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
