<?php
use Cake\Routing\Router;
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Empresas');

$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Empresas</h1>
				<p class="queues-page-sub">
					Cadastro de empresas do grupo. Cada empresa representa um workspace separado no sistema.
				</p>
			</div>
			<div class="queues-page-actions">
				<?php if ($admin): ?>
				<?= $this->Html->link('<i class="fas fa-plus"></i> Adicionar empresa', ['action' => 'add'], ['class' => 'queues-btn queues-btn--success', 'escape' => false, 'target' => '_blank']) ?>
				<?php endif; ?>
				<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<ul class="queues-tabs" role="tablist">
			<li><a class="queues-tab active" data-toggle="tab" href="#ativas" role="tab"><i class="fas fa-check-circle"></i> Ativas</a></li>
			<li><a class="queues-tab" data-toggle="tab" href="#inativas" role="tab"><i class="fas fa-ban"></i> Inativas</a></li>
		</ul>

		<div class="tab-content">
			<div class="tab-pane active" id="ativas">
				<div class="queues-table-outer">
					<div class="table-responsive">
						<table class="table table-hover" id="tableAtivas">
							<thead>
								<tr>
									<th>Nome</th>
									<th>E-mail</th>
									<th class="text-center">Token</th>
									<th class="text-center">Usuários</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($ativas as $ativa): ?>
								<tr>
									<td>
										<a class="users-table-link" href="<?= $this->Url->build(['action' => 'edit', $ativa->id]) ?>"><?= h($ativa->nomefantasia) ?></a>
									</td>
									<td>
										<a class="users-table-link" href="<?= $this->Url->build(['action' => 'edit', $ativa->id]) ?>"><?= h($ativa->email) ?></a>
									</td>
									<td class="text-center">
										<a class="queues-token-link" href="#" data-token="<?= h($ativa->token) ?>"><i class="fas fa-key"></i> Exibir</a>
									</td>
									<td class="text-center">
										<a class="users-table-link" href="<?= $this->Url->build(['controller' => 'Empresasusers', 'action' => 'index']) ?>"><?= h($ativa->nrousuarios) ?></a>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php if (count($ativas) === 0): ?>
					<p class="queues-empty">Nenhuma empresa ativa cadastrada.</p>
				<?php endif; ?>
			</div>

			<div class="tab-pane" id="inativas">
				<div class="queues-table-outer">
					<div class="table-responsive">
						<table class="table table-hover" id="tableInativas">
							<thead>
								<tr>
									<th>Nome</th>
									<th>E-mail</th>
									<th class="text-center">Token</th>
									<th class="text-center">Usuários</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($inativas as $inativa): ?>
								<tr>
									<td>
										<a class="users-table-link" href="<?= $this->Url->build(['action' => 'edit', $inativa->id]) ?>"><?= h($inativa->nomefantasia) ?></a>
									</td>
									<td>
										<a class="users-table-link" href="<?= $this->Url->build(['action' => 'edit', $inativa->id]) ?>"><?= h($inativa->email) ?></a>
									</td>
									<td class="text-center">
										<a class="queues-token-link" href="#" data-token="<?= h($inativa->token) ?>"><i class="fas fa-key"></i> Exibir</a>
									</td>
									<td class="text-center">
										<a class="users-table-link" href="<?= $this->Url->build(['controller' => 'Empresasusers', 'action' => 'index']) ?>"><?= h($inativa->nrousuarios) ?></a>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php if (count($inativas) === 0): ?>
					<p class="queues-empty">Nenhuma empresa inativa.</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	var dtOpts = {
		pageLength: <?= isset($pagelength) ? (int)$pagelength : 100 ?>,
		language: {
			sProcessing: "Processando...", sLengthMenu: "Mostrar _MENU_ registros",
			sZeroRecords: "Nenhum registro encontrado", sEmptyTable: "Nenhum dado disponível",
			sInfo: "Mostrando _START_ até _END_ de _TOTAL_ registros",
			sInfoEmpty: "Nenhum registro", sInfoFiltered: "(filtrado de _MAX_)",
			sSearch: "Buscar:",
			oPaginate: { sFirst: "<<", sLast: ">>", sNext: ">", sPrevious: "<" }
		}
	};
	var filters = typeof filters !== 'undefined' ? filters : '';
	$('#tableAtivas').DataTable(dtOpts).search(filters).draw();
	$('#tableInativas').DataTable(dtOpts);

	$(document).on('click', '.queues-token-link', function(e) {
		e.preventDefault();
		bootbox.alert($(this).data('token'));
	});
});
</script>
