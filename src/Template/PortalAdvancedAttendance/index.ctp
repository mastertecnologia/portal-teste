<?php $this->assign('title', $title ?? 'Histórico'); ?>
<div class="card">
	<div class="card-body">
		<h4 class="card-title"><?= h($title) ?></h4>
		<div class="table-responsive">
			<table class="table table-sm table-striped">
				<thead><tr><th>Ticket</th><th>Assunto</th><th>Status</th><th></th></tr></thead>
				<tbody>
					<?php foreach ($histories as $h): ?>
					<tr>
						<td><?= h($h->ticket_id) ?></td>
						<td><?= h($h->subject) ?></td>
						<td><?= h($h->status) ?></td>
						<td><?= $this->Html->link('Ver', ['action' => 'view', $h->id], ['class' => 'btn btn-sm btn-outline-primary']) ?></td>
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
