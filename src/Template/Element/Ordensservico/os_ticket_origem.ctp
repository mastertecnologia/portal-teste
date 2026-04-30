<?php
/**
 * Bloco 1 — Origem do ticket (somente leitura; modo OS via ticket).
 *
 * @var array $p Chaves: situacaoLabel, abertura, tempoRegistrado, voltarUrl, documentoCliente, clienteNome,
 *     solicitanteLabel, tecnicoTicketNome, emailTicket, ativos (string[]), anexos (lista opcional)
 * @var int $ticketId
 */
$p = $p ?? [];
$ticketId = (int)($ticketId ?? ($p['id'] ?? 0));
?>
<div class="card m-b-15 os-ticket-origem-card os-add-bloco os-add-bloco-origem">
	<div class="card-body p-15">
		<p class="os-add-bloco-title m-b-10">Origem do ticket</p>
		<div class="d-flex flex-wrap justify-content-between align-items-start">
			<div>
				<h5 class="m-t-0 m-b-5 text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.06em;">Chamado</h5>
				<p class="m-b-5"><strong>Ticket #<?= h($ticketId) ?></strong>
					<span class="badge badge-info m-l-5"><?= h($p['situacaoLabel'] ?? '—') ?></span>
				</p>
				<p class="m-b-0 small text-muted">
					<?php if (!empty($p['abertura'])): ?>
						Abertura: <?= h($p['abertura']) ?>
					<?php endif; ?>
					<?php if (!empty($p['abertura']) && !empty($p['tempoRegistrado'])): ?> · <?php endif; ?>
					<?php if (!empty($p['tempoRegistrado'])): ?>
						Tempo total registrado: <?= h($p['tempoRegistrado']) ?>
					<?php endif; ?>
				</p>
				<?php if (!empty($p['clienteNome'])): ?>
					<p class="m-b-0 small m-t-10"><span class="text-muted">Cliente:</span> <?= h($p['clienteNome']) ?></p>
				<?php endif; ?>
				<?php if (!empty($p['solicitanteLabel'])): ?>
					<p class="m-b-0 small"><span class="text-muted">Solicitante:</span> <?= h($p['solicitanteLabel']) ?></p>
				<?php endif; ?>
				<?php if (!empty($p['tecnicoTicketNome'])): ?>
					<p class="m-b-0 small"><span class="text-muted">Responsável no ticket:</span> <?= h($p['tecnicoTicketNome']) ?></p>
				<?php endif; ?>
				<?php if (!empty($p['documentoCliente'])): ?>
					<p class="m-b-0 small"><span class="text-muted">Documento (cliente):</span> <?= h($p['documentoCliente']) ?></p>
				<?php endif; ?>
				<?php if (!empty($p['emailTicket'])): ?>
					<p class="m-b-0 small"><span class="text-muted">E-mail no ticket:</span> <?= h($p['emailTicket']) ?></p>
				<?php endif; ?>
			</div>
			<div class="m-t-5">
				<?php if (!empty($p['voltarUrl'])): ?>
					<?= $this->Html->link(
						'← Voltar ao ticket',
						$p['voltarUrl'],
						['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false]
					) ?>
				<?php endif; ?>
			</div>
		</div>
		<?php if (!empty($p['ativos']) && is_array($p['ativos'])): ?>
			<hr class="m-y-10">
			<p class="small text-muted m-b-5">Ativos vinculados ao ticket</p>
			<ul class="list-unstyled m-b-0 small">
				<?php foreach ($p['ativos'] as $al): ?>
					<li><?= h($al) ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if (!empty($p['anexos']) && is_array($p['anexos'])): ?>
			<hr class="m-y-10">
			<p class="small text-muted m-b-5">Anexos do ticket</p>
			<ul class="list-unstyled m-b-0 small">
				<?php foreach ($p['anexos'] as $ax): ?>
					<li>
						<?= $this->Html->link(h($ax['nome'] ?? 'Anexo'), $ax['url'] ?? '#', ['target' => '_blank', 'rel' => 'noopener']) ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
