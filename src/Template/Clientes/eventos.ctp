<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cliente $cliente
 * @var \App\Model\Entity\ClientDomainEvent[] $events
 * @var bool $domainEventsReady
 */
$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Editar', ['controller' => 'Clientes', 'action' => 'edit', $cliente->id], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Histórico', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card pgm-cli-eventos-card">
		<div class="card-body">
			<div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
				<div>
					<h5 class="card-title text-white mb-0">Histórico de eventos</h5>
					<small class="text-muted"><?= h($cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : $cliente->razaosocial) ?></small>
				</div>
				<?= $this->Html->link(
					'<i class="fas fa-arrow-left"></i> Voltar ao cadastro',
					['action' => 'edit', $cliente->id],
					['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false]
				) ?>
			</div>
			<?php if (empty($events)): ?>
				<p class="text-muted mb-0"><?= !empty($domainEventsReady) ? 'Nenhum evento registrado ainda.' : 'Execute a migration do módulo (portal_internal_notifications / client_domain_events) para habilitar o histórico.' ?></p>
			<?php else: ?>
				<div class="table-responsive">
					<table class="table table-sm table-hover pgm-cli-eventos-table">
						<thead>
							<tr>
								<th>Quando</th>
								<th>Tipo</th>
								<th>Descrição</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($events as $ev): ?>
							<tr>
								<td class="pgm-cli-eventos-timestamp">
									<?= $ev->created ? h($ev->created->i18nFormat('dd/MM/yyyy HH:mm')) : '—' ?>
								</td>
								<td><code class="pgm-cli-eventos-code"><?= h($ev->event_type) ?></code></td>
								<td><?= nl2br(h($ev->description ?? '')) ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
