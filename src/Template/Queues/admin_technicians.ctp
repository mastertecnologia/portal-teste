<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Filas e técnicos', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Técnicos e vínculos', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Técnicos da empresa</h1>
				<p class="queues-page-sub">
					<strong><?= h($nomeempresa ?? ('#' . (int)$emp)) ?></strong> — Cada técnico pode ter <strong>várias filas</strong> (como permissões de grupo).
					Use <strong>Editar vínculos</strong> para definir nível principal (N1/N2/N3), filas e, se necessário, nível por fila.
				</p>
			</div>
			<div class="queues-page-actions">
				<?= $this->Html->link('<i class="fa fa-list"></i> Gerenciar filas', ['action' => 'adminIndex'], ['class' => 'queues-btn queues-btn--primary', 'escape' => false]) ?>
			</div>
		</header>

		<div class="queues-table-outer">
			<div class="table-responsive">
				<table class="table table-hover" id="tableTecnicosFilas">
					<thead>
						<tr>
							<th>Nome</th>
							<th>Login</th>
							<th>Nível do técnico</th>
							<th>Filas vinculadas</th>
							<th class="text-right">Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($tecnicos as $t) :
							$uid = (int)$t->id;
							$links = $queuesByUser[$uid] ?? [];
							?>
							<tr>
								<td><?= h($t->name ?: $t->username) ?></td>
								<td><span class="queues-code"><?= h($t->username) ?></span></td>
								<td>
									<?php if (!empty($t->support_level)) : ?>
										<span class="queues-badge"><?= h($t->support_level->name) ?></span>
									<?php else : ?>
										<span class="queues-badge queues-badge--muted">—</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if (empty($links)) : ?>
										<span class="queues-warn-inline"><i class="fa fa-exclamation-triangle"></i> Nenhuma fila</span>
									<?php else : ?>
										<ul class="queues-tech-list">
											<?php foreach ($links as $lnk) :
												$qn = $lnk->queue ? h($lnk->queue->name) : '?';
												$ln = '';
												if (!empty($lnk->support_level)) {
													$ln = ' <span class="queues-badge queues-badge--muted">' . h($lnk->support_level->name) . '</span>';
												}
												?>
												<li><?= $qn ?><?= $ln ?></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</td>
								<td class="text-right">
									<?= $this->Html->link('Editar vínculos', ['controller' => 'Users', 'action' => 'edit', $t->id, '?' => ['from' => 'queues']], ['class' => 'queues-btn queues-btn--primary', 'title' => 'Nível, filas múltiplas e nível por fila']) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php if (count($tecnicos) === 0) : ?>
			<p class="queues-empty">Nenhum técnico vinculado a esta empresa em <span class="queues-code">empresasusers</span>.</p>
		<?php endif; ?>

		<footer class="queues-foot">
			<?= $this->Html->link('← Voltar às filas', ['action' => 'adminIndex'], ['class' => 'queues-btn']) ?>
		</footer>
	</div>
</div>
<script>
	$(function () {
		if ($.fn.DataTable && $('#tableTecnicosFilas tbody tr').length) {
			$('#tableTecnicosFilas').DataTable({
				pageLength: 50,
				language: {
					sSearch: 'Buscar:',
					sZeroRecords: 'Nenhum técnico',
					sInfo: 'Mostrando _START_ a _END_ de _TOTAL_',
					oPaginate: { sPrevious: '<', sNext: '>' }
				}
			});
		}
	});
</script>
