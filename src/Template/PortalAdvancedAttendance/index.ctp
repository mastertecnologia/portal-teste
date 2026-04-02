<?php
$this->assign('title', $title ?? 'Histórico');
$ticketsRecent = $ticketsRecent ?? [];
$params = $this->Paginator->params();
$histCount = isset($params['count']) ? (int)$params['count'] : (is_countable($histories) ? count($histories) : 0);
$ticketAssunto = function ($t) {
	$a = $t->assunto ?? '';
	if (function_exists('AssuntoTicket')) {
		return strip_tags((string)AssuntoTicket($a));
	}

	return (string)$a;
};
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<p class="text-muted small">Histórico consolidado do módulo avançado (quando houver registros).</p>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
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
						<?php if ($histCount === 0): ?>
						<tr>
							<td colspan="4" class="text-muted">Nenhum registro consolidado. Abaixo estão seus chamados (tickets) reais.</td>
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
	<?php if (!empty($ticketsRecent) && count($ticketsRecent) > 0): ?>
	<div class="pgm-adv-section-label">Meus chamados</div>
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr><th>ID</th><th>Assunto</th><th>Situação</th><th>Atualizado</th><th></th></tr>
					</thead>
					<tbody>
						<?php foreach ($ticketsRecent as $t): ?>
						<tr>
							<td><?= (int)$t->id ?></td>
							<td><?= h($ticketAssunto($t)) ?></td>
							<td><?= function_exists('SituacaoTicket') ? h(strip_tags((string)SituacaoTicket($t->situacao))) : h((string)($t->situacao ?? '')) ?></td>
							<td><?= h($t->modified instanceof \DateTimeInterface ? $t->modified->format('d/m/Y H:i') : '') ?></td>
							<td><?= $this->Html->link('Abrir', ['controller' => 'Tickets', 'action' => 'view', $t->id], ['class' => 'btn btn-sm btn-outline-primary']) ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php endif; ?>
</div>
