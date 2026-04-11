<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Usuários clientes');

$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);

// Agrupar por empresa
$totalClients = count($clients);
$totalAtivos = 0;
$totalInativos = 0;
$empresaMap = []; // nome => ['total' => n, 'ativos' => n, 'inativos' => n]
foreach ($clients as $c) {
	$ativo = $c->inativo != 1;
	if ($ativo) { $totalAtivos++; } else { $totalInativos++; }
	$nome = '(sem empresa)';
	if ($c->cliente) {
		$nome = !empty($c->cliente->razaosocial) ? trim($c->cliente->razaosocial) : (trim($c->cliente->nome) ?: '(sem empresa)');
	}
	if (!isset($empresaMap[$nome])) {
		$empresaMap[$nome] = ['total' => 0, 'ativos' => 0, 'inativos' => 0];
	}
	$empresaMap[$nome]['total']++;
	if ($ativo) { $empresaMap[$nome]['ativos']++; } else { $empresaMap[$nome]['inativos']++; }
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
					<?= $totalEmpresas ?> empresas &middot; <?= $totalClients ?> usuários
					<span style="color:#6ee7c5;">(<?= $totalAtivos ?> ativos)</span>
					<?php if ($totalInativos > 0): ?>
						<span style="color:#fca5a5;">(<?= $totalInativos ?> inativos)</span>
					<?php endif; ?>
				</p>
			</div>
			<div class="queues-page-actions">
				<?php if ($admin): ?>
				<?= $this->Html->link('<i class="fas fa-user-plus"></i> Adicionar cliente', ['action' => 'addcliente'], ['class' => 'queues-btn queues-btn--success', 'escape' => false, 'target' => '_blank']) ?>
				<?php endif; ?>
				<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<!-- Busca de empresa -->
		<div class="qc-empresa-filter">
			<div class="qc-empresa-search-wrap">
				<i class="fas fa-search qc-empresa-search-ico"></i>
				<input type="text" id="buscaEmpresa" class="qc-empresa-search" placeholder="Buscar empresa...">
			</div>
			<button type="button" id="btnLimpar" class="queues-btn qc-btn-limpar" style="display:none;">
				<i class="fas fa-times"></i> Limpar filtro
			</button>
			<span class="qc-filter-info" id="filterInfo"></span>
		</div>

		<!-- Grid de empresas -->
		<div class="qc-empresa-grid" id="empresaGrid">
			<?php foreach ($empresaMap as $nome => $info): ?>
			<button type="button" class="qc-empresa-card" data-empresa="<?= h($nome) ?>">
				<span class="qc-empresa-card-nome"><?= h($nome) ?></span>
				<span class="qc-empresa-card-meta">
					<span class="qc-empresa-card-count"><?= $info['total'] ?></span>
					<?php if ($info['inativos'] > 0): ?>
						<span class="qc-empresa-card-inactive" title="Inativos"><?= $info['inativos'] ?> inativo<?= $info['inativos'] > 1 ? 's' : '' ?></span>
					<?php endif; ?>
				</span>
			</button>
			<?php endforeach; ?>
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
							$nomeEmpresa = '(sem empresa)';
							if ($client->cliente) {
								$nomeEmpresa = !empty($client->cliente->razaosocial) ? trim($client->cliente->razaosocial) : (trim($client->cliente->nome) ?: '(sem empresa)');
							}
						?>
						<tr data-empresa="<?= h($nomeEmpresa) ?>">
							<td>
								<a class="users-table-link" href="<?= $this->Url->build(['action' => 'editcliente', $client->id]) ?>"><?= h($client->username) ?></a>
							</td>
							<td>
								<a class="users-table-link" href="<?= $this->Url->build(['action' => 'editcliente', $client->id]) ?>"><?= h($client->email) ?></a>
							</td>
							<td class="td-empresa"><?= h($nomeEmpresa) ?></td>
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
	var activeEmpresa = null;

	// Custom DataTables filter
	$.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
		if (settings.nTable.id !== 'tableClients') return true;
		if (!activeEmpresa) return true;
		var row = settings.aoData[dataIndex].nTr;
		return $(row).attr('data-empresa') === activeEmpresa;
	});

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

	// Click em card de empresa
	$(document).on('click', '.qc-empresa-card', function() {
		var emp = $(this).data('empresa');
		if (activeEmpresa === emp) {
			// deselect
			activeEmpresa = null;
			$('.qc-empresa-card').removeClass('qc-empresa-card--active');
			$('#btnLimpar').hide();
			$('#filterInfo').text('');
		} else {
			activeEmpresa = emp;
			$('.qc-empresa-card').removeClass('qc-empresa-card--active');
			$(this).addClass('qc-empresa-card--active');
			$('#btnLimpar').show();
			$('#filterInfo').text(emp);
		}
		table.draw();
	});

	// Limpar filtro
	$('#btnLimpar').on('click', function() {
		activeEmpresa = null;
		$('.qc-empresa-card').removeClass('qc-empresa-card--active');
		$(this).hide();
		$('#filterInfo').text('');
		table.draw();
	});

	// Busca de empresa (filtra os cards)
	$('#buscaEmpresa').on('input', function() {
		var q = $(this).val().toLowerCase();
		$('.qc-empresa-card').each(function() {
			var nome = $(this).data('empresa').toLowerCase();
			$(this).toggle(nome.indexOf(q) !== -1);
		});
	});
});
</script>
