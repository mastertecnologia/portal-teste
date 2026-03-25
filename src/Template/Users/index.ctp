<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<div class="d-flex justify-content-between align-items-center m-b-15">
				<div>
					<h5 class="card-title m-b-0">Usuários da equipe (PGM/Master)</h5>
					<small class="text-muted">Controle dos usuários internos que acessam o portal como equipe PGM / Master.</small>
				</div>
				<?php if ($admin): ?>
					<div class="text-right">
						<?= $this->Html->link('Adicionar usuário', ['action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success btn-sm m-r-5', 'target' => '_blank']) ?>
						<?= $this->Html->link('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-primary btn-sm']) ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="table-responsive">
				<table class="table table-hover table-row-clickable" id="tableAdmins">
					<thead class="text-primary">
						<tr>
							<th>Usuário</th>
							<th>E-mail</th>
							<th>Nome</th>
							<th class="text-center">Status</th>
							<?php if ($admin): ?>
								<th class="text-center">2FA</th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($admins as $adm): ?>
							<?php
								$labelStatus = $adm->inativo ? 'danger' : 'success';
								$textStatus  = $adm->inativo ? 'Inativo' : 'Ativo';
							?>
							<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4>Desativar 2FA</h4><br>Desative a autenticação de dois fatores</div>' data-original-title="Autenticação 2FA <small style='font-size: 12px;'></small>" data-html="true" data-placement="top">
								<td width="25%"><a class="link" target="_blank" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'edit', $adm->id]) ?>"><?= h($adm->username) ?></a></td>
								<td width="30%"><a class="link" target="_blank" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'edit', $adm->id]) ?>"><?= h($adm->email) ?></a></td>
								<td width="25%"><a class="link" target="_blank" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'edit', $adm->id]) ?>"><?= h($adm->name) ?></a></td>
								<td width="10%" class="text-center">
									<span class="label label-<?= $labelStatus ?>"><?= $textStatus ?></span>
								</td>
								<?php if ($admin): ?>
									<td width="10%" class="td-actions text-center">
										<?php if ($adm->secret != null): ?>
											<?= $this->Html->link('<i class="far fa-id-card"></i>', ['action' => 'desativaverificacaoqualqueruser', $adm->id, '0'], ['rel' => 'tooltip', 'title' => 'Desativar 2FA', 'class' => 'btn btn-pgm btn-pgm-situacao btn-info btn-secondary btn-xs', 'id' => $adm->id, 'escape' => false, 'confirm' => 'Tem certeza que deseja desativar a autenticação de dois fatores deste usuário?']) ?>
										<?php else: ?>
											<span class="text-muted small">—</span>
										<?php endif; ?>
									</td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script>
	$('[rel=popover]').popover({ offset: 0 });
	$(document).ready(function() {
		var filters = typeof filters !== 'undefined' ? filters : '';
		var table = $('#tableAdmins').DataTable({
			pageLength: <?= isset($pagelength) ? (int)$pagelength : 100 ?>,
			language: {
				sProcessing: "Procesando...",
				sLengthMenu: "Mostrar _MENU_ registros",
				sZeroRecords: "Nenhum registro encontrado",
				sEmptyTable: "Nenhum dado disponível",
				sInfo: "Mostrando registros de _START_ até _END_ de um total de _TOTAL_ registros",
				sInfoEmpty: "Mostrando registros de 0 a 0 de um total de 0 registros",
				sInfoFiltered: "(filtrado de um total de _MAX_ registros)",
				sSearch: "Buscar:",
				oPaginate: { sFirst: "<<", sLast: ">>", sNext: ">", sPrevious: "<" },
				oAria: { sSortAscending: ": Ordem Ascendente", sSortDescending: ": Ordem descendente" },
				drawCallback: function(settings) {
					if ($('body').hasClass('dark-mode')) $('td').each(function() { $(this).addClass('dark-mode'); });
					else $('td').each(function() { $(this).removeClass('dark-mode'); });
				}
			}
		});
		table.search(filters).draw();
	});
</script>
