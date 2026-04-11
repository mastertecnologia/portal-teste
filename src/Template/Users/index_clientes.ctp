<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Usuários clientes');

$this->start('css');
echo $this->Html->css('/dist/css/pages/config-admin-shell.css');
$this->end();
?>
<div class="col-12 p-0 admin-panel-ambient">
	<div class="admin-panel-wrap">
		<header class="admin-panel-hero">
			<div class="ap-hero-top">
				<div>
					<h1><i class="fas fa-user ap-hero-ico"></i> Usuários do portal</h1>
					<p>Gestão dos usuários finais (role cliente) vinculados às empresas clientes do portal.</p>
				</div>
				<div class="admin-panel-hero-actions">
					<?php if ($admin): ?>
					<?= $this->Html->link('<i class="fas fa-user-plus"></i> Adicionar cliente', ['action' => 'addcliente'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false, 'target' => '_blank']) ?>
					<?php endif; ?>
					<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
				</div>
			</div>
		</header>

		<div class="ap-table-wrap">
			<table class="ap-table" id="tableClients">
				<thead>
					<tr>
						<th>Usuário</th>
						<th>E-mail</th>
						<th>Empresa</th>
						<th style="width:100px;">Situação</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($clients as $client): ?>
					<?php $isInativo = $client->inativo == 1; ?>
					<tr>
						<td><a class="ap-link" href="<?= $this->Url->build(['action' => 'editcliente', $client->id]) ?>"><?= h($client->username) ?></a></td>
						<td><a class="ap-link" href="<?= $this->Url->build(['action' => 'editcliente', $client->id]) ?>"><?= h($client->email) ?></a></td>
						<td>
							<?php
								$nomeEmpresa = '—';
								if ($client->cliente) {
									$nomeEmpresa = !empty($client->cliente->razaosocial) ? $client->cliente->razaosocial : ($client->cliente->nome ?? '—');
								}
							?>
							<?= h($nomeEmpresa) ?>
						</td>
						<td>
							<span class="ap-badge <?= $isInativo ? 'ap-badge--danger' : 'ap-badge--success' ?>">
								<?= $isInativo ? 'Inativo' : 'Ativo' ?>
							</span>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	var filters = typeof filters !== 'undefined' ? filters : '';
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
});
</script>
