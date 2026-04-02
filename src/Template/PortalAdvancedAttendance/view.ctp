<?php $this->assign('title', $title ?? 'Atendimento'); ?>
<div class="card mb-3">
	<div class="card-body">
		<h4 class="card-title"><?= h($history->subject) ?></h4>
		<p class="small text-muted">Ticket #<?= h($history->ticket_id) ?></p>
		<?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-sm btn-secondary']) ?>
	</div>
</div>
<div class="card">
	<div class="card-body">
		<h5 class="card-title">Linha do tempo</h5>
		<p class="small text-muted">Notas internas não são exibidas no portal.</p>
		<div class="table-responsive">
			<table class="table table-sm">
				<thead><tr><th>Quando</th><th>Evento</th><th>Nota</th></tr></thead>
				<tbody>
					<?php foreach ($timeline as $ev): ?>
					<tr>
						<td><?= h($ev->created ? $ev->created->format('d/m/Y H:i') : '') ?></td>
						<td><?= h($ev->event_label) ?></td>
						<td><?= h($ev->public_note) ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
