<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h5 class="card-title mb-3">Usuários da equipe (PGM/Master)</h5>
			<div class="table-responsive">
				<table class="table table-hover table-row-clickable" id="tableAdmins">
					<thead class="text-primary">
						<tr>
							<th>Usuário</th>
							<th>E-mail</th>
							<th>Nome</th>
							<?php if ($admin): ?>
								<th>Ações</th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($admins as $adm): ?>
							<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4>Desativar 2FA</h4><br>Desative a autenticação de dois fatores</div>' data-original-title="Autenticação 2FA <small style='font-size: 12px;'></small>" data-html="true" data-placement="top">
								<td width="33%"><a class="link" target="_blank" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'edit', $adm->id]) ?>"><?= h($adm->username) ?></a></td>
								<td width="33%"><a class="link" target="_blank" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'edit', $adm->id]) ?>"><?= h($adm->email) ?></a></td>
								<td width="20%"><a class="link" target="_blank" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'edit', $adm->id]) ?>"><?= h($adm->name) ?></a></td>
								<?php if ($admin): ?>
									<td class="td-actions">
										<?php if ($adm->secret != null): ?>
											<?= $this->Html->link('<i class="far fa-id-card"></i>', ['action' => 'desativaverificacaoqualqueruser', $adm->id, '0'], ['rel' => 'tooltip', 'title' => 'Desativar 2FA', 'class' => 'btn btn-info btn-secondary btn-xs', 'id' => $adm->id, 'escape' => false, 'confirm' => 'Tem certeza que deseja desativar a autenticação de dois fatores deste usuário?']) ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ($admin): ?>
				<?= $this->Html->link('Adicionar usuário', ['action' => 'add'], ['class' => 'btn btn-success m-t-20 m-r-5', 'style' => 'margin-bottom: 20px;', 'target' => '_blank']) ?>
				<?= $this->Html->link('Voltar para as configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'btn btn-primary m-t-20', 'style' => 'margin-bottom: 20px;']) ?>
			<?php endif; ?>
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
