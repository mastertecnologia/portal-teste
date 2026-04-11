<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Usuários clientes');

$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Usuários do portal</h1>
				<p class="queues-page-sub">
					Gestão dos usuários finais (clientes) vinculados às empresas do portal.
				</p>
			</div>
			<div class="queues-page-actions">
				<?php if ($admin): ?>
				<?= $this->Html->link('<i class="fas fa-user-plus"></i> Adicionar cliente', ['action' => 'addcliente'], ['class' => 'queues-btn queues-btn--success', 'escape' => false, 'target' => '_blank']) ?>
				<?php endif; ?>
				<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<div class="queues-table-outer">
			<div class="table-responsive">
				<table class="table table-hover" id="tableClients">
					<thead>
						<tr>
							<th>Usuário</th>
							<th>E-mail</th>
							<th>Empresa</th>
							<th class="text-center" style="width:100px;">Situação</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($clients as $client): ?>
						<?php $isInativo = $client->inativo == 1; ?>
						<tr>
							<td>
								<a class="users-table-link" href="<?= $this->Url->build(['action' => 'editcliente', $client->id]) ?>"><?= h($client->username) ?></a>
							</td>
							<td>
								<a class="users-table-link" href="<?= $this->Url->build(['action' => 'editcliente', $client->id]) ?>"><?= h($client->email) ?></a>
							</td>
							<td>
								<?php
									$nomeEmpresa = '—';
									if ($client->cliente) {
										$nomeEmpresa = !empty($client->cliente->razaosocial) ? $client->cliente->razaosocial : ($client->cliente->nome ?? '—');
									}
								?>
								<?= h($nomeEmpresa) ?>
							</td>
							<td class="text-center">
								<span class="queues-badge <?= $isInativo ? 'queues-badge--danger' : 'queues-badge--success' ?>">
									<?= $isInativo ? 'Inativo' : 'Ativo' ?>
								</span>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php if (count($clients) === 0): ?>
			<p class="queues-empty">Nenhum usuário cliente cadastrado.</p>
		<?php endif; ?>
	</div>
</div>

<script>
$(document).ready(function() {
	var filters = typeof filters !== 'undefined' ? filters : '';
	if ($.fn.DataTable && $('#tableClients tbody tr').length) {
		$('#tableClients').DataTable({
			pageLength: <?= isset($pagelength) ? (int)$pagelength : 100 ?>,
			language: {
				sProcessing: "Processando...", sLengthMenu: "Mostrar _MENU_ registros",
				sZeroRecords: "Nenhum registro encontrado", sEmptyTable: "Nenhum dado disponível",
				sInfo: "Mostrando _START_ até _END_ de _TOTAL_ registros",
				sInfoEmpty: "Nenhum registro", sInfoFiltered: "(filtrado de _MAX_)",
				sSearch: "Buscar:",
				oPaginate: { sFirst: "<<", sLast: ">>", sNext: ">", sPrevious: "<" }
			}
		}).search(filters).draw();
	}
});
</script>
