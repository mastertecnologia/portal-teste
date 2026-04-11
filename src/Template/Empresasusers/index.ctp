<?php
use Cake\Routing\Router;
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Vínculos empresa / usuário');

$this->start('css');
echo $this->Html->css('/dist/css/pages/config-admin-shell.css');
$this->end();
?>
<div class="col-12 p-0 admin-panel-ambient">
	<div class="admin-panel-wrap">
		<header class="admin-panel-hero">
			<div class="ap-hero-top">
				<div>
					<h1><i class="fas fa-link ap-hero-ico"></i> Vínculos empresa / usuário</h1>
					<p>Relações que definem qual usuário pode atuar em qual empresa (multi-empresa).</p>
				</div>
				<div class="admin-panel-hero-actions">
					<?php if ($admin): ?>
					<?= $this->Html->link('<i class="fas fa-plus"></i> Adicionar vínculo', ['action' => 'add'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false, 'target' => '_blank']) ?>
					<?php endif; ?>
					<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
				</div>
			</div>
		</header>

		<ul class="ap-tabs" role="tablist">
			<li><a class="ap-tab active" data-toggle="tab" href="#funcrelacoes" role="tab"><i class="fas fa-user-tie"></i> Equipe</a></li>
			<li><a class="ap-tab" data-toggle="tab" href="#clirelacoes" role="tab"><i class="fas fa-user"></i> Clientes</a></li>
		</ul>

		<div class="tab-content">
			<div class="tab-pane active" id="funcrelacoes">
				<div class="ap-table-wrap">
					<table class="ap-table" id="tableFuncrelacoes">
						<thead>
							<tr>
								<th>Empresa</th>
								<th>Usuário</th>
								<th style="width:80px;"></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($funcrelacoes as $funcrelacao): ?>
							<tr>
								<td><?= h($funcrelacao['empresa']['nomefantasia']) ?></td>
								<td><?= h($funcrelacao['user']['name']) ?></td>
								<td>
									<?= $this->Html->link('<i class="fas fa-trash-alt"></i> Excluir', ['action' => 'delete', $funcrelacao->id], [
										'class' => 'ap-btn-danger',
										'escape' => false,
										'confirm' => 'Tem certeza que deseja excluir este vínculo?'
									]) ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="tab-pane" id="clirelacoes">
				<div class="ap-table-wrap">
					<table class="ap-table" id="tableClirelacoes">
						<thead>
							<tr>
								<th>Empresa</th>
								<th>Usuário</th>
								<th style="width:80px;"></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($clirelacoes as $clirelacao): ?>
							<tr>
								<td><?= h($clirelacao['empresa']['nomefantasia']) ?></td>
								<td><?= h($clirelacao['user']['name']) ?></td>
								<td>
									<?= $this->Html->link('<i class="fas fa-trash-alt"></i> Excluir', ['action' => 'delete', $clirelacao->id], [
										'class' => 'ap-btn-danger',
										'escape' => false,
										'confirm' => 'Tem certeza que deseja excluir este vínculo?'
									]) ?>
								</td>
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
	$('#tableFuncrelacoes, #tableClirelacoes').DataTable({
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
});
</script>
