<?php
use Cake\Routing\Router;
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Empresas');

$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
?>
<div class="col-12 p-0 admin-panel-ambient">
	<div class="admin-panel-wrap">
		<header class="admin-panel-hero">
			<div class="ap-hero-top">
				<div>
					<h1><i class="fas fa-city ap-hero-ico"></i> Empresas</h1>
					<p>Cadastro de empresas do grupo. Cada empresa representa um workspace separado no sistema.</p>
				</div>
				<div class="admin-panel-hero-actions">
					<?php if ($admin): ?>
					<?= $this->Html->link('<i class="fas fa-plus"></i> Adicionar empresa', ['action' => 'add'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false, 'target' => '_blank']) ?>
					<?php endif; ?>
					<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
				</div>
			</div>
		</header>

		<ul class="ap-tabs" role="tablist">
			<li><a class="ap-tab active" data-toggle="tab" href="#ativas" role="tab"><i class="fas fa-check-circle"></i> Ativas</a></li>
			<li><a class="ap-tab" data-toggle="tab" href="#inativas" role="tab"><i class="fas fa-ban"></i> Inativas</a></li>
		</ul>

		<div class="tab-content">
			<div class="tab-pane active" id="ativas">
				<div class="ap-table-wrap">
					<table class="ap-table" id="tableAtivas">
						<thead>
							<tr>
								<th>Nome</th>
								<th>E-mail</th>
								<th>Token</th>
								<th>Usuários</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($ativas as $ativa): ?>
							<tr>
								<td><a class="ap-link" href="<?= $this->Url->build(['action' => 'edit', $ativa->id]) ?>"><?= h($ativa->nomefantasia) ?></a></td>
								<td><a class="ap-link" href="<?= $this->Url->build(['action' => 'edit', $ativa->id]) ?>"><?= h($ativa->email) ?></a></td>
								<td><a class="ap-link-token" href="#" data-token="<?= h($ativa->token) ?>">Exibir</a></td>
								<td><a class="ap-link" href="<?= $this->Url->build(['controller' => 'Empresasusers', 'action' => 'index']) ?>"><?= h($ativa->nrousuarios) ?></a></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="tab-pane" id="inativas">
				<div class="ap-table-wrap">
					<table class="ap-table" id="tableInativas">
						<thead>
							<tr>
								<th>Nome</th>
								<th>E-mail</th>
								<th>Token</th>
								<th>Usuários</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($inativas as $inativa): ?>
							<tr>
								<td><a class="ap-link" href="<?= $this->Url->build(['action' => 'edit', $inativa->id]) ?>"><?= h($inativa->nomefantasia) ?></a></td>
								<td><a class="ap-link" href="<?= $this->Url->build(['action' => 'edit', $inativa->id]) ?>"><?= h($inativa->email) ?></a></td>
								<td><a class="ap-link-token" href="#" data-token="<?= h($inativa->token) ?>">Exibir</a></td>
								<td><a class="ap-link" href="<?= $this->Url->build(['controller' => 'Empresasusers', 'action' => 'index']) ?>"><?= h($inativa->nrousuarios) ?></a></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	$('#tableAtivas, #tableInativas').DataTable({
		pageLength: <?= $pagelength ?>,
		language: {
			sProcessing: "Processando...", sLengthMenu: "Mostrar _MENU_ registros",
			sZeroRecords: "Nenhum registro encontrado", sEmptyTable: "Nenhum dado disponível",
			sInfo: "Mostrando _START_ até _END_ de _TOTAL_ registros",
			sInfoEmpty: "Nenhum registro", sInfoFiltered: "(filtrado de _MAX_)",
			sSearch: "Buscar:",
			oPaginate: { sFirst: "<<", sLast: ">>", sNext: ">", sPrevious: "<" }
		}
	}).search(filters).draw();

	$(document).on('click', '.ap-link-token', function(e) {
		e.preventDefault();
		bootbox.alert($(this).data('token'));
	});
});
</script>
