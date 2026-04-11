<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Usuários clientes');

$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);

// Agrupar usuários por empresa
$empresas = [];
$totalAtivos = 0;
$totalInativos = 0;
foreach ($clients as $c) {
	$ativo = $c->inativo != 1;
	if ($ativo) { $totalAtivos++; } else { $totalInativos++; }
	$nome = '(sem empresa)';
	if ($c->cliente) {
		$nome = !empty($c->cliente->razaosocial) ? trim($c->cliente->razaosocial) : (trim($c->cliente->nome) ?: '(sem empresa)');
	}
	if (!isset($empresas[$nome])) {
		$empresas[$nome] = ['users' => [], 'ativos' => 0, 'inativos' => 0];
	}
	$empresas[$nome]['users'][] = $c;
	if ($ativo) { $empresas[$nome]['ativos']++; } else { $empresas[$nome]['inativos']++; }
}
ksort($empresas);
$totalClients = count($clients);
$totalEmpresas = count($empresas);
?>
<div class="col-md-12 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Usuários do portal</h1>
				<p class="queues-page-sub">
					<strong><?= $totalEmpresas ?></strong> empresas &middot;
					<strong><?= $totalClients ?></strong> usuários &middot;
					<span style="color:#6ee7c5"><?= $totalAtivos ?> ativos</span><?php if ($totalInativos): ?> &middot;
					<span style="color:#fca5a5"><?= $totalInativos ?> inativos</span><?php endif; ?>
				</p>
			</div>
			<div class="queues-page-actions">
				<?php if ($admin): ?>
				<?= $this->Html->link('<i class="fas fa-user-plus"></i> Adicionar cliente', ['action' => 'addcliente'], ['class' => 'queues-btn queues-btn--success', 'escape' => false, 'target' => '_blank']) ?>
				<?php endif; ?>
				<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<!-- Busca -->
		<div class="ua-search-bar">
			<i class="fas fa-search ua-search-ico"></i>
			<input type="text" id="buscaEmpresa" class="ua-search-input" placeholder="Buscar empresa pelo nome...">
			<span class="ua-search-count" id="searchCount"><?= $totalEmpresas ?> empresas</span>
		</div>

		<!-- Accordion de empresas -->
		<div class="ua-accordion" id="accordionEmpresas">
			<?php $idx = 0; foreach ($empresas as $nome => $info): $idx++; ?>
			<div class="ua-section" data-empresa="<?= h(mb_strtolower($nome)) ?>">
				<button type="button" class="ua-section-header" data-target="#uaSec<?= $idx ?>">
					<span class="ua-section-arrow"><i class="fas fa-chevron-right"></i></span>
					<span class="ua-section-nome"><?= h($nome) ?></span>
					<span class="ua-section-badges">
						<?php if ($info['inativos'] > 0): ?>
						<span class="ua-badge ua-badge--warn"><?= $info['inativos'] ?> inativo<?= $info['inativos'] > 1 ? 's' : '' ?></span>
						<?php endif; ?>
						<span class="ua-badge"><?= count($info['users']) ?> usuário<?= count($info['users']) > 1 ? 's' : '' ?></span>
					</span>
				</button>
				<div class="ua-section-body" id="uaSec<?= $idx ?>">
					<table class="ua-users-table">
						<thead>
							<tr>
								<th>Usuário</th>
								<th>E-mail</th>
								<th class="text-center" style="width:80px">Status</th>
								<th class="text-center" style="width:60px"></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($info['users'] as $u): ?>
							<?php $inativo = $u->inativo == 1; ?>
							<tr>
								<td class="ua-user-name"><?= h($u->username) ?></td>
								<td class="ua-user-email"><?= h($u->email) ?></td>
								<td class="text-center">
									<span class="ua-status <?= $inativo ? 'ua-status--off' : 'ua-status--on' ?>">
										<?= $inativo ? 'Inativo' : 'Ativo' ?>
									</span>
								</td>
								<td class="text-center">
									<a href="<?= $this->Url->build(['action' => 'editcliente', $u->id]) ?>" class="ua-edit-link" title="Editar">
										<i class="fas fa-pen"></i>
									</a>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<?php if ($totalClients === 0): ?>
		<p class="queues-empty">Nenhum usuário cliente cadastrado.</p>
		<?php endif; ?>
	</div>
</div>

<script>
$(document).ready(function() {
	// Toggle accordion
	$(document).on('click', '.ua-section-header', function() {
		var $section = $(this).closest('.ua-section');
		$section.toggleClass('ua-section--open');
	});

	// Busca de empresa
	$('#buscaEmpresa').on('input', function() {
		var q = $(this).val().toLowerCase();
		var visible = 0;
		$('.ua-section').each(function() {
			var nome = $(this).data('empresa');
			var show = !q || nome.indexOf(q) !== -1;
			$(this).toggle(show);
			if (show) visible++;
		});
		$('#searchCount').text(visible + ' empresa' + (visible !== 1 ? 's' : ''));
	});
});
</script>
