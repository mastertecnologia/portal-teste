<?php
use Cake\Routing\Router;
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Filas e técnicos', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Filas de atendimento</h1>
				<p class="queues-page-sub">
					Empresa atual: <strong><?= h($nomeempresa ?? ('#' . (int)$emp)) ?></strong>
					— Filas e vínculos são por empresa (use o seletor Master/PGM no topo para alternar).
				</p>
			</div>
			<div class="queues-page-actions">
				<?= $this->Html->link('<i class="fa fa-users"></i> Técnicos e filas', ['action' => 'adminTechnicians'], ['class' => 'queues-btn queues-btn--primary', 'escape' => false]) ?>
				<?= $this->Html->link('<i class="fa fa-plus"></i> Nova fila', ['action' => 'adminEdit'], ['class' => 'queues-btn queues-btn--success', 'escape' => false]) ?>
				<?php if ($queues->count() === 0) : ?>
					<?= $this->Form->postLink(
						'<i class="fa fa-magic"></i> Criar filas padrão',
						['action' => 'adminEnsureDefaults'],
						['class' => 'queues-btn queues-btn--warn', 'escape' => false, 'confirm' => 'Criar as filas padrão (N1–N3, NOC, serviço) para esta empresa?']
					) ?>
				<?php endif; ?>
			</div>
		</header>

		<?php if (!empty($supportLevelsEnabled)) : ?>
			<div class="queues-callout">
				<strong>Regras:</strong> cada fila tem um <em>nível principal</em> (N1, N2, N3, NOC, Serviço). Nos usuários, defina o <em>nível do técnico</em> e em quais <em>filas</em> ele atua. O técnico só assume tickets das filas em que está vinculado, com nível compatível com a fila.
			</div>
		<?php endif; ?>

		<div class="queues-table-outer">
			<div class="table-responsive">
				<table class="table table-hover" id="tableQueues">
					<thead>
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
								<td><span class="queues-code"><?= h($q->codigo ?? '—') ?></span></td>
								<td>
									<?php if (!empty($q->support_level)) : ?>
										<span class="queues-badge"><?= h($q->support_level->name) ?></span>
									<?php else : ?>
										<span class="queues-badge queues-badge--muted">—</span>
									<?php endif; ?>
								</td>
								<td class="text-right td-actions">
									<div class="queues-actions">
										<?= $this->Html->link('<i class="fa fa-edit"></i>', ['action' => 'adminEdit', $q->id], ['class' => 'queues-icon-btn queues-icon-btn--edit', 'escape' => false, 'title' => 'Editar']) ?>
										<?= $this->Form->postLink(
											'<i class="fa fa-trash"></i>',
											['action' => 'adminDelete', $q->id],
											['class' => 'queues-icon-btn queues-icon-btn--danger', 'escape' => false, 'confirm' => 'Excluir esta fila? Só é permitido se não houver tickets.', 'title' => 'Excluir']
										) ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php if ($queues->count() === 0) : ?>
			<p class="queues-empty">Nenhuma fila nesta empresa. Use <strong>Criar filas padrão</strong> ou <strong>Nova fila</strong>.</p>
		<?php endif; ?>

		<footer class="queues-foot">
			<?= $this->Html->link('← Voltar às configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'queues-btn']) ?>
			<?= $this->Html->link('Usuários da equipe (cadastro completo)', ['controller' => 'Users', 'action' => 'index', '?' => ['from' => 'queues']], ['class' => 'queues-btn']) ?>
		</footer>
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
