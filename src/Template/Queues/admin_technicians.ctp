<?php
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Filas e técnicos', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Técnicos e vínculos', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<div class="d-flex flex-wrap justify-content-between align-items-center m-b-15">
				<div>
					<h5 class="card-title m-b-5">Técnicos da empresa atual</h5>
					<p class="text-muted m-b-0">
						<strong><?= h($nomeempresa ?? ('#' . (int)$emp)) ?></strong> — Cada técnico pode ter <strong>várias filas</strong> (como permissões de grupo).
						Use <strong>Editar</strong> para definir nível principal (N1/N2/N3), filas e, se necessário, nível por fila.
					</p>
				</div>
				<?= $this->Html->link('Gerenciar filas', ['action' => 'adminIndex'], ['class' => 'btn btn-primary btn-sm']) ?>
			</div>

			<div class="table-responsive">
				<table class="table table-hover" id="tableTecnicosFilas">
					<thead class="text-primary">
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
								<td><code><?= h($t->username) ?></code></td>
								<td>
									<?php if (!empty($t->support_level)) : ?>
										<?= h($t->support_level->name) ?>
									<?php else : ?>
										<span class="text-muted">—</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if (empty($links)) : ?>
										<span class="text-warning"><i class="fa fa-exclamation-triangle"></i> Nenhuma fila</span>
									<?php else : ?>
										<ul class="list-unstyled m-b-0 small">
											<?php foreach ($links as $lnk) :
												$qn = $lnk->queue ? h($lnk->queue->name) : '?';
												$ln = '';
												if (!empty($lnk->support_level)) {
													$ln = ' <span class="label label-default">' . h($lnk->support_level->name) . '</span>';
												}
												?>
												<li><?= $qn ?><?= $ln ?></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</td>
								<td class="text-right">
									<?= $this->Html->link('Editar vínculos', ['controller' => 'Users', 'action' => 'edit', $t->id], ['class' => 'btn btn-sm btn-success', 'title' => 'Nível, filas múltiplas e nível por fila']) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if (count($tecnicos) === 0) : ?>
				<p class="text-muted text-center m-t-15">Nenhum técnico vinculado a esta empresa em <code>empresasusers</code>.</p>
			<?php endif; ?>

			<p class="m-t-20 m-b-0">
				<?= $this->Html->link('← Voltar às filas', ['action' => 'adminIndex'], ['class' => 'btn btn-secondary btn-sm']) ?>
			</p>
		</div>
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
