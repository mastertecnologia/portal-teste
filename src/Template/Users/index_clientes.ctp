<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Usuários clientes');

$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);

// Agregar stats
$totalClients = count($clients);
$totalAtivos = 0;
$totalInativos = 0;
$empresaMap = [];
foreach ($clients as $c) {
	if ($c->inativo == 1) { $totalInativos++; } else { $totalAtivos++; }
	$nome = '—';
	if ($c->cliente) {
		$nome = !empty($c->cliente->razaosocial) ? $c->cliente->razaosocial : ($c->cliente->nome ?? '—');
	}
	if (!isset($empresaMap[$nome])) { $empresaMap[$nome] = 0; }
	$empresaMap[$nome]++;
}
ksort($empresaMap);
$totalEmpresas = count($empresaMap);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Usuários do portal</h1>
				<p class="queues-page-sub">
					Gestão dos usuários clientes vinculados às empresas do portal.
					Selecione uma empresa para filtrar a visualização.
				</p>
			</div>
			<div class="queues-page-actions">
				<?php if ($admin): ?>
				<?= $this->Html->link('<i class="fas fa-user-plus"></i> Adicionar cliente', ['action' => 'addcliente'], ['class' => 'queues-btn queues-btn--success', 'escape' => false, 'target' => '_blank']) ?>
				<?php endif; ?>
				<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<!-- Stats -->
		<div class="qc-stats-row">
			<div class="qc-stat">
				<span class="qc-stat-value"><?= $totalEmpresas ?></span>
				<span class="qc-stat-label">Empresas</span>
			</div>
			<div class="qc-stat">
				<span class="qc-stat-value"><?= $totalClients ?></span>
				<span class="qc-stat-label">Usuários</span>
			</div>
			<div class="qc-stat">
				<span class="qc-stat-value qc-stat-value--success"><?= $totalAtivos ?></span>
				<span class="qc-stat-label">Ativos</span>
			</div>
			<div class="qc-stat">
				<span class="qc-stat-value qc-stat-value--danger"><?= $totalInativos ?></span>
				<span class="qc-stat-label">Inativos</span>
			</div>
		</div>

		<!-- Filtro por empresa -->
		<div class="qc-filter-bar">
			<label class="qc-filter-label" for="filtroEmpresa">
				<i class="fas fa-building"></i> Filtrar por empresa:
			</label>
			<select id="filtroEmpresa" class="qc-filter-select">
				<option value="">Todas as empresas (<?= $totalClients ?>)</option>
				<?php foreach ($empresaMap as $nome => $qty): ?>
				<option value="<?= h($nome) ?>"><?= h($nome) ?> (<?= $qty ?>)</option>
				<?php endforeach; ?>
			</select>
			<span class="qc-filter-count" id="filtroCount">Exibindo <?= $totalClients ?> de <?= $totalClients ?> usuários</span>
		</div>

		<!-- Tabela -->
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
						<?php
							$isInativo = $client->inativo == 1;
							$nomeEmpresa = '—';
							if ($client->cliente) {
								$nomeEmpresa = !empty($client->cliente->razaosocial) ? $client->cliente->razaosocial : ($client->cliente->nome ?? '—');
							}
						?>
						<tr>
							<td>
								<a class="users-table-link" href="<?= $this->Url->build(['action' => 'editcliente', $client->id]) ?>"><?= h($client->username) ?></a>
							</td>
							<td>
								<a class="users-table-link" href="<?= $this->Url->build(['action' => 'editcliente', $client->id]) ?>"><?= h($client->email) ?></a>
							</td>
							<td><?= h($nomeEmpresa) ?></td>
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
		<?php if ($totalClients === 0): ?>
			<p class="queues-empty">Nenhum usuário cliente cadastrado.</p>
		<?php endif; ?>
	</div>
</div>

<script>
$(document).ready(function() {
	var table = $('#tableClients').DataTable({
		pageLength: <?= isset($pagelength) ? (int)$pagelength : 100 ?>,
		order: [[2, 'asc'], [0, 'asc']],
		language: {
			sProcessing: "Processando...", sLengthMenu: "Mostrar _MENU_ registros",
			sZeroRecords: "Nenhum registro encontrado", sEmptyTable: "Nenhum dado disponível",
			sInfo: "Mostrando _START_ até _END_ de _TOTAL_ registros",
			sInfoEmpty: "Nenhum registro", sInfoFiltered: "(filtrado de _MAX_)",
			sSearch: "Buscar:",
			oPaginate: { sFirst: "<<", sLast: ">>", sNext: ">", sPrevious: "<" }
		}
	});

	var totalAll = <?= $totalClients ?>;

	$('#filtroEmpresa').on('change', function() {
		var val = $(this).val();
		// Coluna 2 = Empresa; regex exact match
		table.column(2).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
		var shown = table.rows({ search: 'applied' }).count();
		$('#filtroCount').text('Exibindo ' + shown + ' de ' + totalAll + ' usuários');
	});
});
</script>
