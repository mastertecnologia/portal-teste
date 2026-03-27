<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Usuários da equipe', [], ['class' => 'breadcrumb-item active']);
$addUserUrl = ['action' => 'add'];
if (!empty($fromQueues)) {
	$addUserUrl['?'] = ['from' => 'queues'];
}
$configUrl = ['controller' => 'Config', 'action' => 'index'];
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Usuários da equipe (PGM/Master)</h1>
				<p class="queues-page-sub">
					Controle dos usuários internos que acessam o portal como equipe PGM / Master.
				</p>
			</div>
			<div class="queues-page-actions">
				<?php if (!empty($fromQueues)) : ?>
					<?= $this->Html->link('<i class="fa fa-list-alt"></i> Filas de atendimento', ['controller' => 'Queues', 'action' => 'adminIndex'], ['class' => 'queues-btn', 'escape' => false]) ?>
				<?php endif; ?>
				<?php if ($admin) : ?>
					<?= $this->Html->link('<i class="fa fa-user-plus"></i> Adicionar usuário', $addUserUrl, ['class' => 'queues-btn queues-btn--success', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-cog"></i> Configurações', $configUrl, ['class' => 'queues-btn queues-btn--primary', 'escape' => false]) ?>
				<?php endif; ?>
			</div>
		</header>

		<div class="queues-table-outer">
			<div class="table-responsive">
				<table class="table table-hover" id="tableAdmins">
					<thead>
						<tr>
							<th>Usuário</th>
							<th>E-mail</th>
							<th>Nome</th>
							<th class="text-center">Status</th>
							<?php if ($admin) : ?>
								<th class="text-center">2FA</th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($admins as $adm) : ?>
							<?php
								$textStatus = $adm->inativo ? 'Inativo' : 'Ativo';
								$badgeClass = $adm->inativo ? 'queues-badge--danger' : 'queues-badge--success';
								$editUserUrl = ['controller' => 'Users', 'action' => 'edit', $adm->id];
								if (!empty($fromQueues)) {
									$editUserUrl['?'] = ['from' => 'queues'];
								}
							?>
							<tr>
								<td>
									<a class="users-table-link" href="<?= $this->Url->build($editUserUrl) ?>"><?= h($adm->username) ?></a>
								</td>
								<td>
									<a class="users-table-link" href="<?= $this->Url->build($editUserUrl) ?>"><?= h($adm->email) ?></a>
								</td>
								<td>
									<a class="users-table-link" href="<?= $this->Url->build($editUserUrl) ?>"><?= h($adm->name) ?></a>
								</td>
								<td class="text-center">
									<span class="queues-badge <?= $badgeClass ?>"><?= h($textStatus) ?></span>
								</td>
								<?php if ($admin) : ?>
									<td class="td-actions text-center">
										<?php if ($adm->secret != null) : ?>
											<?= $this->Html->link('<i class="far fa-id-card"></i>', ['action' => 'desativaverificacaoqualqueruser', $adm->id, '0'], [
												'rel' => 'tooltip',
												'title' => 'Desativar autenticação em dois fatores (2FA)',
												'class' => 'queues-icon-btn queues-icon-btn--edit',
												'id' => $adm->id,
												'escape' => false,
												'confirm' => 'Tem certeza que deseja desativar a autenticação de dois fatores deste usuário?',
											]) ?>
										<?php else : ?>
											<span class="queues-badge queues-badge--muted">—</span>
										<?php endif; ?>
									</td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php if (count($admins) === 0) : ?>
			<p class="queues-empty">Nenhum usuário da equipe cadastrado.</p>
		<?php endif; ?>
	</div>
</div>
<script>
	jQuery(document).ready(function($) {
		var filters = typeof filters !== 'undefined' ? filters : '';
		if ($.fn.tooltip) {
			$('[rel=tooltip]').tooltip();
		}
		if ($.fn.DataTable && $('#tableAdmins tbody tr').length) {
			var table = $('#tableAdmins').DataTable({
				pageLength: <?= isset($pagelength) ? (int)$pagelength : 100 ?>,
				language: {
					sProcessing: 'Processando...',
					sLengthMenu: 'Mostrar _MENU_ registros',
					sZeroRecords: 'Nenhum registro encontrado',
					sEmptyTable: 'Nenhum dado disponível',
					sInfo: 'Mostrando registros de _START_ até _END_ de um total de _TOTAL_ registros',
					sInfoEmpty: 'Mostrando registros de 0 a 0 de um total de 0 registros',
					sInfoFiltered: '(filtrado de um total de _MAX_ registros)',
					sSearch: 'Buscar:',
					oPaginate: { sFirst: '<<', sLast: '>>', sNext: '>', sPrevious: '<' },
					oAria: { sSortAscending: ': Ordem ascendente', sSortDescending: ': Ordem descendente' },
				},
			});
			table.search(filters).draw();
		}
	});
</script>
