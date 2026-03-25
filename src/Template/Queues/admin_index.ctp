<?php
use Cake\Routing\Router;
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Filas e técnicos', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<div class="d-flex flex-wrap justify-content-between align-items-start m-b-15">
				<div>
					<h5 class="card-title m-b-5">Filas de atendimento</h5>
					<p class="text-muted m-b-0">
						Empresa atual: <strong><?= h($nomeempresa ?? ('#' . (int)$emp)) ?></strong>
						— As filas e vínculos são por empresa (use o seletor Master/PGM no topo para alternar).
					</p>
				</div>
				<div class="text-right m-t-10">
					<?= $this->Html->link('Técnicos e filas', ['action' => 'adminTechnicians'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info btn-sm m-r-5']) ?>
					<?= $this->Html->link('Nova fila', ['action' => 'adminEdit'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success btn-sm m-r-5']) ?>
					<?php if ($queues->count() === 0) : ?>
						<?= $this->Form->postLink(
							'Criar filas padrão (N1–N3, NOC, serviço)',
							['action' => 'adminEnsureDefaults'],
							['class' => 'btn btn-warning btn-sm', 'confirm' => 'Criar as filas padrão para esta empresa?']
						) ?>
					<?php endif; ?>
				</div>
			</div>

			<?php if (!empty($supportLevelsEnabled)) : ?>
				<p class="small text-muted m-b-15">
					<strong>Regras:</strong> cada fila tem um <em>nível principal</em> (N1, N2, N3, NOC, Serviço). Nos usuários, defina o <em>nível do técnico</em> e em quais <em>filas</em> ele atua (várias filas permitidas). O técnico só assume tickets das filas em que está vinculado, com nível compatível com a fila.
				</p>
			<?php endif; ?>

			<div class="table-responsive">
				<table class="table table-hover" id="tableQueues">
					<thead class="text-primary">
						<tr>
							<th>Ordem</th>
							<th>Nome</th>
							<th>Código</th>
							<th>Nível da fila</th>
							<th class="text-right">Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($queues as $q) : ?>
							<tr>
								<td><?= (int)$q->sort_order ?></td>
								<td><?= h($q->name) ?></td>
								<td><code><?= h($q->codigo ?? '—') ?></code></td>
								<td>
									<?php if (!empty($q->support_level)) : ?>
										<span class="label label-primary"><?= h($q->support_level->name) ?></span>
									<?php else : ?>
										<span class="text-muted">—</span>
									<?php endif; ?>
								</td>
								<td class="text-right td-actions">
									<?= $this->Html->link('<i class="fa fa-edit"></i>', ['action' => 'adminEdit', $q->id], ['class' => 'btn btn-sm btn-pgm btn-pgm-situacao btn-info', 'escape' => false, 'title' => 'Editar']) ?>
									<?= $this->Form->postLink(
										'<i class="fa fa-trash"></i>',
										['action' => 'adminDelete', $q->id],
										['class' => 'btn btn-sm btn-danger', 'escape' => false, 'confirm' => 'Excluir esta fila? Só é permitido se não houver tickets.', 'title' => 'Excluir']
									) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ($queues->count() === 0) : ?>
				<p class="text-center text-muted m-t-20">Nenhuma fila nesta empresa. Use <strong>Criar filas padrão</strong> ou <strong>Nova fila</strong>.</p>
			<?php endif; ?>

			<hr class="m-t-25">
			<p class="m-b-0">
				<?= $this->Html->link('← Voltar às configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'btn btn-secondary btn-sm']) ?>
				<?= $this->Html->link('Usuários da equipe (cadastro completo)', ['controller' => 'Users', 'action' => 'index'], ['class' => 'btn btn-outline-secondary btn-sm m-l-5']) ?>
			</p>
		</div>
	</div>
</div>
<script>
	$(function () {
		if ($.fn.DataTable && $('#tableQueues tbody tr').length) {
			$('#tableQueues').DataTable({
				pageLength: 25,
				order: [[0, 'asc']],
				language: {
					sSearch: 'Buscar:',
					sZeroRecords: 'Nenhuma fila',
					sInfo: 'Mostrando _START_ a _END_ de _TOTAL_',
					oPaginate: { sPrevious: '<', sNext: '>' }
				}
			});
		}
	});
</script>
