<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<div class="d-flex justify-content-between align-items-center m-b-15">
				<div>
					<h5 class="card-title m-b-0">Usuários clientes</h5>
					<small class="text-muted">Gestão dos usuários finais vinculados às empresas clientes do portal.</small>
				</div>
				<?php if ($admin): ?>
					<div class="text-right">
						<?= $this->Html->link('Adicionar cliente', ['action' => 'addcliente'], ['class' => 'btn btn-success btn-sm m-r-5', 'target' => '_blank']) ?>
						<?= $this->Html->link('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm']) ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="table-responsive">
				<table class="table table-hover table-row-clickable" id="tableClients">
					<thead class="text-primary">
						<tr>
							<th>Usuário</th>
							<th>E-mail</th>
							<th>Empresa</th>
							<th>Situação</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($clients as $client): ?>
							<?php
								$label = $client->inativo == 1 ? 'danger' : 'success';
								$sit = $client->inativo == 1 ? 'Inativo' : 'Ativo';
							?>
							<tr>
								<td width="25%"><a class="link" target="_blank" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'editcliente', $client->id]) ?>"><?= h($client->username) ?></a></td>
								<td width="25%"><a class="link" target="_blank" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'editcliente', $client->id]) ?>"><?= h($client->email) ?></a></td>
								<td width="25%">
									<a class="link" target="_blank" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'editcliente', $client->id]) ?>">
										<?= $client->cliente ? (empty($client->cliente->razaosocial) ? h($client->cliente->nome) : h($client->cliente->razaosocial)) : '—' ?>
									</a>
								</td>
								<td width="25%"><span class="label label-<?= $label ?>"><?= $sit ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function() {
		var filters = typeof filters !== 'undefined' ? filters : '';
		var table = $('#tableClients').DataTable({
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
