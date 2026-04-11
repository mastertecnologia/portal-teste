<?php
use Cake\Routing\Router;
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Vínculos empresa / usuário');

$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Vínculos empresa / usuário</h1>
				<p class="queues-page-sub">
					Relações que definem qual usuário pode atuar em qual empresa (multi-empresa).
				</p>
			</div>
			<div class="queues-page-actions">
				<?php if ($admin): ?>
				<?= $this->Html->link('<i class="fas fa-plus"></i> Adicionar vínculo', ['action' => 'add'], ['class' => 'queues-btn queues-btn--success', 'escape' => false, 'target' => '_blank']) ?>
				<?php endif; ?>
				<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<ul class="queues-tabs" role="tablist">
			<li><a class="queues-tab active" data-toggle="tab" href="#funcrelacoes" role="tab"><i class="fas fa-user-tie"></i> Equipe</a></li>
			<li><a class="queues-tab" data-toggle="tab" href="#clirelacoes" role="tab"><i class="fas fa-user"></i> Clientes</a></li>
		</ul>

		<div class="tab-content">
			<div class="tab-pane active" id="funcrelacoes">
				<div class="queues-table-outer">
					<div class="table-responsive">
						<table class="table table-hover" id="tableFuncrelacoes">
							<thead>
								<tr>
									<th>Empresa</th>
									<th>Usuário</th>
									<th class="text-center" style="width:100px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($funcrelacoes as $funcrelacao): ?>
								<tr>
									<td><?= h($funcrelacao['empresa']['nomefantasia']) ?></td>
									<td><?= h($funcrelacao['user']['name']) ?></td>
									<td class="text-center">
										<?= $this->Html->link('<i class="fas fa-trash-alt"></i>', ['action' => 'delete', $funcrelacao->id], [
											'class' => 'queues-icon-btn queues-icon-btn--danger',
											'escape' => false,
											'rel' => 'tooltip',
											'title' => 'Excluir vínculo',
											'confirm' => 'Tem certeza que deseja excluir este vínculo?'
										]) ?>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php if (count($funcrelacoes) === 0): ?>
					<p class="queues-empty">Nenhum vínculo de equipe cadastrado.</p>
				<?php endif; ?>
			</div>

			<div class="tab-pane" id="clirelacoes">
				<div class="queues-table-outer">
					<div class="table-responsive">
						<table class="table table-hover" id="tableClirelacoes">
							<thead>
								<tr>
									<th>Empresa</th>
									<th>Usuário</th>
									<th class="text-center" style="width:100px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($clirelacoes as $clirelacao): ?>
								<tr>
									<td><?= h($clirelacao['empresa']['nomefantasia']) ?></td>
									<td><?= h($clirelacao['user']['name']) ?></td>
									<td class="text-center">
										<?= $this->Html->link('<i class="fas fa-trash-alt"></i>', ['action' => 'delete', $clirelacao->id], [
											'class' => 'queues-icon-btn queues-icon-btn--danger',
											'escape' => false,
											'rel' => 'tooltip',
											'title' => 'Excluir vínculo',
											'confirm' => 'Tem certeza que deseja excluir este vínculo?'
										]) ?>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php if (count($clirelacoes) === 0): ?>
					<p class="queues-empty">Nenhum vínculo de cliente cadastrado.</p>
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
	$('#tableFuncrelacoes').DataTable(dtOpts).search(filters).draw();
	$('#tableClirelacoes').DataTable(dtOpts);

	if ($.fn.tooltip) {
		$('[rel=tooltip]').tooltip();
	}
});
</script>
