<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Gestão de clientes');

$this->Html->css('/dist/css/pages/client-management.css', ['block' => true]);

// Paleta para avatares
$avatarPalette = ['#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#06b6d4','#6366f1','#e11d48'];

// Agrupar por empresa
$groups = [];
$totalAtivos = 0;
$totalInativos = 0;
foreach ($clients as $c) {
	$ativo = $c->inativo != 1;
	if ($ativo) { $totalAtivos++; } else { $totalInativos++; }
	$nome = '(sem empresa)';
	if ($c->cliente) {
		$nome = !empty($c->cliente->razaosocial) ? trim($c->cliente->razaosocial) : (trim($c->cliente->nome) ?: '(sem empresa)');
	}
	if (!isset($groups[$nome])) {
		$groups[$nome] = ['users' => [], 'ativos' => 0, 'inativos' => 0, 'email' => $c->email];
	}
	$groups[$nome]['users'][] = $c;
	if ($ativo) { $groups[$nome]['ativos']++; } else { $groups[$nome]['inativos']++; }
}
ksort($groups);
$totalClients = count($clients);
$totalEmpresas = count($groups);
?>
<div class="col-md-12 p-0">
	<div class="cm-wrap">
		<!-- Header -->
		<div class="cm-header">
			<div>
				<h1 class="cm-header-title">Gestão de Clientes</h1>
				<p class="cm-header-sub">Gerencie empresas clientes, usuários e vínculos do portal.</p>
			</div>
			<div class="cm-header-actions">
				<?php if ($admin): ?>
				<?= $this->Html->link('<i class="fas fa-plus"></i> Novo Cliente', ['action' => 'addcliente'], ['class' => 'cm-btn cm-btn--primary', 'escape' => false, 'target' => '_blank']) ?>
				<?php endif; ?>
				<?= $this->Html->link('<i class="fas fa-cog"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'cm-btn', 'escape' => false]) ?>
			</div>
		</div>

		<!-- KPIs -->
		<div class="cm-kpis">
			<div class="cm-kpi">
				<div class="cm-kpi-icon cm-kpi-icon--empresas"><i class="fas fa-building"></i></div>
				<div class="cm-kpi-info">
					<span class="cm-kpi-value"><?= $totalEmpresas ?></span>
					<span class="cm-kpi-label">Empresas</span>
				</div>
			</div>
			<div class="cm-kpi">
				<div class="cm-kpi-icon cm-kpi-icon--usuarios"><i class="fas fa-users"></i></div>
				<div class="cm-kpi-info">
					<span class="cm-kpi-value"><?= $totalClients ?></span>
					<span class="cm-kpi-label">Usuários</span>
				</div>
			</div>
			<div class="cm-kpi">
				<div class="cm-kpi-icon cm-kpi-icon--ativos"><i class="fas fa-check-circle"></i></div>
				<div class="cm-kpi-info">
					<span class="cm-kpi-value"><?= $totalAtivos ?></span>
					<span class="cm-kpi-label">Ativos</span>
				</div>
			</div>
			<div class="cm-kpi">
				<div class="cm-kpi-icon cm-kpi-icon--inativos"><i class="fas fa-user-slash"></i></div>
				<div class="cm-kpi-info">
					<span class="cm-kpi-value"><?= $totalInativos ?></span>
					<span class="cm-kpi-label">Inativos</span>
				</div>
			</div>
		</div>

		<!-- Search -->
		<div class="cm-toolbar">
			<div class="cm-search-wrap">
				<i class="fas fa-search"></i>
				<input type="text" id="cmSearch" class="cm-search" placeholder="Buscar por empresa, e-mail ou nome de usuário...">
			</div>
			<span class="cm-toolbar-info" id="cmInfo"><?= $totalEmpresas ?> empresas</span>
		</div>

		<!-- Table -->
		<div class="cm-table-wrap">
			<table class="cm-table">
				<thead>
					<tr>
						<th style="width:40px"></th>
						<th>Empresa</th>
						<th class="text-center" style="width:90px">Usuários</th>
						<th class="text-center" style="width:100px">Status</th>
						<th style="width:120px"></th>
					</tr>
				</thead>
				<tbody>
					<?php $idx = 0; foreach ($groups as $nome => $info): $idx++;
						$initials = mb_strtoupper(mb_substr($nome, 0, 2));
						$color = $avatarPalette[crc32($nome) % count($avatarPalette)];
						$allActive = $info['inativos'] === 0;
						$allInactive = $info['ativos'] === 0;
						if ($allInactive) { $statusClass = 'cm-status--inactive'; $statusText = 'Inativa'; }
						elseif ($allActive) { $statusClass = 'cm-status--active'; $statusText = 'Ativa'; }
						else { $statusClass = 'cm-status--mixed'; $statusText = $info['ativos'] . ' / ' . count($info['users']); }
						$searchData = mb_strtolower($nome);
						foreach ($info['users'] as $u) {
							$searchData .= ' ' . mb_strtolower($u->email) . ' ' . mb_strtolower($u->username);
						}
					?>
					<!-- Company row -->
					<tr class="cm-row" data-target="cmDetail<?= $idx ?>" data-search="<?= h($searchData) ?>">
						<td class="text-center">
							<span class="cm-expand-arrow"><i class="fas fa-chevron-right"></i></span>
						</td>
						<td>
							<div class="cm-empresa-cell">
								<div class="cm-avatar" style="background:<?= $color ?>"><?= $initials ?></div>
								<div class="cm-empresa-info">
									<span class="cm-empresa-name"><?= h($nome) ?></span>
									<span class="cm-empresa-email"><?= h($info['email']) ?></span>
								</div>
							</div>
						</td>
						<td class="text-center">
							<span class="cm-count"><?= count($info['users']) ?></span>
						</td>
						<td class="text-center">
							<span class="cm-status <?= $statusClass ?>">
								<span class="cm-status-dot"></span>
								<?= $statusText ?>
							</span>
						</td>
						<td>
							<div class="cm-actions">
								<?= $this->Html->link('<i class="fas fa-user-plus"></i>', ['action' => 'addcliente'], [
									'class' => 'cm-action-btn cm-action-btn--add',
									'escape' => false,
									'title' => 'Adicionar usuário',
									'target' => '_blank'
								]) ?>
							</div>
						</td>
					</tr>
					<!-- Detail row (users) -->
					<tr class="cm-detail" id="cmDetail<?= $idx ?>">
						<td colspan="5">
							<div class="cm-detail-inner">
								<?php if (count($info['users']) > 0): ?>
								<table class="cm-users">
									<thead>
										<tr>
											<th>Usuário</th>
											<th>E-mail</th>
											<th class="text-center" style="width:80px">Status</th>
											<th style="width:100px"></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($info['users'] as $u): ?>
										<?php $off = $u->inativo == 1; ?>
										<tr>
											<td class="cm-user-name"><?= h($u->username) ?></td>
											<td class="cm-user-email"><?= h($u->email) ?></td>
											<td class="text-center">
												<span class="cm-user-status <?= $off ? 'cm-user-status--off' : 'cm-user-status--on' ?>">
													<?= $off ? 'Inativo' : 'Ativo' ?>
												</span>
											</td>
											<td>
												<div class="cm-actions">
													<?= $this->Html->link('<i class="fas fa-pen"></i> Editar', ['action' => 'editcliente', $u->id], [
														'class' => 'cm-action-btn cm-action-btn--edit',
														'escape' => false
													]) ?>
												</div>
											</td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
								<?php else: ?>
								<div class="cm-empty-detail">
									<i class="fas fa-user-friends"></i>
									Nenhum usuário cadastrado para esta empresa.
									<?= $this->Html->link('Adicionar primeiro usuário', ['action' => 'addcliente'], ['target' => '_blank']) ?>
								</div>
								<?php endif; ?>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ($totalClients === 0): ?>
		<div class="cm-empty-detail" style="padding:40px 24px;">
			<i class="fas fa-users" style="font-size:32px"></i>
			Nenhum cliente cadastrado no portal.
			<?= $this->Html->link('Cadastrar primeiro cliente', ['action' => 'addcliente'], ['target' => '_blank']) ?>
		</div>
		<?php endif; ?>
	</div>
</div>

<script>
$(document).ready(function() {
	// Expand/collapse company
	$(document).on('click', '.cm-row', function(e) {
		if ($(e.target).closest('a').length) return; // não interceptar links
		var $row = $(this);
		var targetId = $row.data('target');
		var $detail = $('#' + targetId);
		var isOpen = $row.hasClass('cm-row--open');

		if (isOpen) {
			$row.removeClass('cm-row--open');
			$detail.removeClass('cm-detail--open');
		} else {
			$row.addClass('cm-row--open');
			$detail.addClass('cm-detail--open');
		}
	});

	// Global search
	$('#cmSearch').on('input', function() {
		var q = $(this).val().toLowerCase();
		var visible = 0;
		$('.cm-row').each(function() {
			var data = $(this).data('search');
			var targetId = $(this).data('target');
			var match = !q || data.indexOf(q) !== -1;
			$(this).toggle(match);
			$('#' + targetId).toggle(match && $(this).hasClass('cm-row--open'));
			if (match) visible++;
		});
		$('#cmInfo').text(visible + ' empresa' + (visible !== 1 ? 's' : ''));
	});
});
</script>
