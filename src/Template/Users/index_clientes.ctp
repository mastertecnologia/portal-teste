<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Usuários clientes');

$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);

// Agrupar por empresa
$totalClients = count($clients);
$totalAtivos = 0;
$totalInativos = 0;
$empresaMap = [];
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
					Selecione uma empresa à esquerda para visualizar seus usuários.
				</p>
			</div>
			<div class="queues-page-actions">
				<?php if ($admin): ?>
				<?= $this->Html->link('<i class="fas fa-user-plus"></i> Adicionar cliente', ['action' => 'addcliente'], ['class' => 'queues-btn queues-btn--success', 'escape' => false, 'target' => '_blank']) ?>
				<?php endif; ?>
				<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<div class="uc-layout">
			<!-- Painel esquerdo: lista de empresas -->
			<aside class="uc-sidebar">
				<div class="uc-sidebar-head">
					<div class="uc-sidebar-stats">
						<span><strong><?= $totalEmpresas ?></strong> empresas</span>
						<span class="uc-dot">&middot;</span>
						<span><strong><?= $totalClients ?></strong> usuários</span>
					</div>
					<div class="uc-search-wrap">
						<i class="fas fa-search uc-search-ico"></i>
						<input type="text" id="buscaEmpresa" class="uc-search" placeholder="Filtrar empresas...">
					</div>
				</div>
				<div class="uc-empresa-list" id="empresaList">
					<button type="button" class="uc-empresa-item uc-empresa-item--all uc-empresa-item--active" data-empresa="">
						<span class="uc-empresa-nome"><i class="fas fa-globe"></i> Todas as empresas</span>
						<span class="uc-empresa-badge"><?= $totalClients ?></span>
					</button>
					<?php foreach ($empresaMap as $nome => $info): ?>
					<button type="button" class="uc-empresa-item" data-empresa="<?= h($nome) ?>">
						<span class="uc-empresa-nome"><?= h($nome) ?></span>
						<span class="uc-empresa-badges">
							<span class="uc-empresa-badge"><?= $info['total'] ?></span>
							<?php if ($info['inativos'] > 0): ?>
							<span class="uc-empresa-badge uc-empresa-badge--warn"><?= $info['inativos'] ?> <small>off</small></span>
							<?php endif; ?>
						</span>
					</button>
					<?php endforeach; ?>
				</div>
			</aside>

			<!-- Painel direito: tabela de usuários -->
			<main class="uc-main">
				<div class="uc-main-head" id="mainHead">
					<h2 id="mainTitle">Todas as empresas</h2>
					<span class="uc-main-count" id="mainCount"><?= $totalClients ?> usuários</span>
				</div>
				<div class="queues-table-outer">
					<div class="table-responsive">
						<table class="table table-hover" id="tableClients">
							<thead>
								<tr>
									<th>Usuário</th>
									<th>E-mail</th>
									<th>Empresa</th>
									<th class="text-center" style="width:90px;">Situação</th>
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
									<td><?= h($client->email) ?></td>
									<td class="uc-td-empresa"><?= h($nomeEmpresa) ?></td>
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
			</main>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	var activeEmpresa = null;

	$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
		if (settings.nTable.id !== 'tableClients') return true;
		if (!activeEmpresa) return true;
		return $(settings.aoData[dataIndex].nTr).attr('data-empresa') === activeEmpresa;
	});

	var table = $('#tableClients').DataTable({
		pageLength: <?= isset($pagelength) ? (int)$pagelength : 100 ?>,
		order: [[2, 'asc'], [0, 'asc']],
		language: {
			sProcessing: "Processando...", sLengthMenu: "Mostrar _MENU_ registros",
			sZeroRecords: "Nenhum registro encontrado", sEmptyTable: "Nenhum dado disponível",
			sInfo: "Mostrando _START_ até _END_ de _TOTAL_ registros",
			sInfoEmpty: "Nenhum registro", sInfoFiltered: "",
			sSearch: "Buscar:",
			oPaginate: { sFirst: "<<", sLast: ">>", sNext: ">", sPrevious: "<" }
		}
	});

	$(document).on('click', '.uc-empresa-item', function() {
		var emp = $(this).data('empresa');
		$('.uc-empresa-item').removeClass('uc-empresa-item--active');
		$(this).addClass('uc-empresa-item--active');

		activeEmpresa = emp || null;
		table.draw();

		var shown = table.rows({ search: 'applied' }).count();
		$('#mainTitle').text(emp || 'Todas as empresas');
		$('#mainCount').text(shown + ' usuário' + (shown !== 1 ? 's' : ''));
	});

	$('#buscaEmpresa').on('input', function() {
		var q = $(this).val().toLowerCase();
		$('.uc-empresa-item').each(function() {
			if ($(this).hasClass('uc-empresa-item--all')) {
				$(this).toggle(q === '');
				return;
			}
			$(this).toggle($(this).data('empresa').toLowerCase().indexOf(q) !== -1);
		});
	});
});
</script>
