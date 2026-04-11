<?php
/**
 * Gestão de Clientes — usuários do portal agrupados por empresa.
 * Reutiliza o design system cli-* de Clientes/index (clientes-premium.css).
 */
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Gestão de clientes');

// Carrega o mesmo CSS premium de Clientes
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));

// Helper iniciais
function ucInitials($str) {
	$parts = preg_split('/\s+/', trim($str), -1, PREG_SPLIT_NO_EMPTY);
	$a = mb_strtoupper(mb_substr($parts[0] ?? 'C', 0, 1));
	$b = mb_strtoupper(mb_substr($parts[1] ?? '', 0, 1));
	return $a . $b;
}

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

<style>
/* Extensões para a tela de gestão de usuários (detail rows) */
.cli-usr-detail { display: none; }
.cli-usr-detail.cli-usr-open { display: table-row; }
.cli-usr-detail > td { padding: 0 !important; background: var(--cli-bg) !important; border-bottom: 1px solid var(--cli-border) !important; }
.cli-usr-inner { padding: 6px 14px 14px 56px; }
.cli-usr-table { width: 100%; border-collapse: collapse; }
.cli-usr-table thead th {
	padding: 7px 12px; font-size: 9.5px; font-weight: 700;
	text-transform: uppercase; letter-spacing: .06em;
	color: var(--cli-dim); border-bottom: 1px solid var(--cli-border);
}
.cli-usr-table tbody td {
	padding: 8px 12px; font-size: 12.5px; color: var(--cli-muted);
	border-bottom: 1px solid rgba(255,255,255,0.03); vertical-align: middle;
}
.cli-usr-table tbody tr:last-child td { border-bottom: none; }
.cli-usr-table tbody tr:hover td { background: var(--cli-surface2); color: var(--cli-text); }
.cli-usr-name { font-weight: 600; color: var(--cli-text) !important; }
.cli-usr-email { color: var(--cli-dim) !important; font-size: 12px !important; }
.cli-usr-badge {
	display: inline-block; padding: 2px 9px; border-radius: 10px;
	font-size: 10.5px; font-weight: 600;
}
.cli-usr-badge--on { background: rgba(29,158,117,.12); color: #5cdbc0; }
.cli-usr-badge--off { background: rgba(248,81,73,.10); color: #f85149; }
.cli-usr-badge--mixed { background: rgba(251,191,36,.10); color: #fcd34d; }
.cli-usr-edit {
	display: inline-flex; align-items: center; gap: 5px;
	padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 500;
	color: var(--cli-muted) !important; text-decoration: none !important;
	border: 1px solid var(--cli-border2); background: transparent;
	transition: all .15s; font-family: inherit;
}
.cli-usr-edit:hover { background: var(--cli-surface2); color: #58a6ff !important; border-color: rgba(88,166,255,.4); }
.cli-usr-count {
	display: inline-flex; align-items: center; justify-content: center;
	min-width: 24px; height: 22px; padding: 0 7px; border-radius: 6px;
	font-size: 12px; font-weight: 700; font-family: 'DM Mono', monospace;
	background: var(--cli-surface2); color: var(--cli-muted);
	border: 1px solid var(--cli-border2);
}
.cli-usr-expand {
	transition: transform .2s ease;
}
.cli-row-open .cli-usr-expand {
	transform: rotate(90deg);
	color: var(--cli-teal-lt);
}
.cli-row-open td { background: var(--cli-surface2) !important; }
.cli-usr-empty {
	padding: 20px 12px; text-align: center; color: var(--cli-dim); font-size: 12.5px;
}
.cli-usr-empty a { color: var(--cli-teal-lt) !important; text-decoration: none !important; font-weight: 600; }
.cli-usr-empty a:hover { text-decoration: underline !important; }
</style>

<div class="col-md-12 p-0">
<div class="cli-root pgm-ds-pilot" data-pgm-ds-pilot="gestao-usuarios">

	<!-- ── Topbar ── -->
	<div class="cli-topbar">
		<div class="cli-topbar-left">
			<div class="cli-eyebrow">Painel administrativo</div>
			<h1 class="cli-h1">Gestão de Clientes</h1>
			<div class="cli-subtitle">Empresas clientes e seus usuários vinculados ao portal</div>
		</div>
		<div class="cli-topbar-right">
			<?php if ($admin): ?>
			<?= $this->Html->link(
				'<i class="fas fa-plus"></i> Novo cliente',
				['action' => 'addcliente'],
				['class' => 'btn-cli-primary', 'escape' => false, 'target' => '_blank']
			) ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- ── KPI Strip ── -->
	<div class="cli-kpi-strip">
		<div class="cli-kpi active" data-kpi="empresas">
			<div class="cli-kpi-label">Empresas</div>
			<div class="cli-kpi-val teal"><?= $totalEmpresas ?></div>
			<div class="cli-kpi-sub">Clientes ativos</div>
		</div>
		<div class="cli-kpi" data-kpi="usuarios">
			<div class="cli-kpi-label">Usuários</div>
			<div class="cli-kpi-val"><?= $totalClients ?></div>
			<div class="cli-kpi-sub">Total do portal</div>
		</div>
		<div class="cli-kpi" data-kpi="ativos">
			<div class="cli-kpi-label">Ativos</div>
			<div class="cli-kpi-val teal"><?= $totalAtivos ?></div>
			<div class="cli-kpi-sub">Usuários ativos</div>
		</div>
		<div class="cli-kpi" data-kpi="inativos">
			<div class="cli-kpi-label">Inativos</div>
			<div class="cli-kpi-val muted"><?= $totalInativos ?></div>
			<div class="cli-kpi-sub">Usuários inativos</div>
		</div>
	</div>

	<!-- ── Filter bar ── -->
	<div class="cli-filter-bar">
		<div class="cli-pill-group" id="uc-status-pills">
			<button class="cli-pill active" data-filter="todos">
				<i class="fas fa-circle pgm-pill-dot" aria-hidden="true"></i> Todos
				<span class="cnt"><?= $totalEmpresas ?></span>
			</button>
			<button class="cli-pill" data-filter="com-inativos">
				<i class="fas fa-circle pgm-pill-dot pgm-pill-dot--danger" aria-hidden="true"></i> Com inativos
			</button>
		</div>
		<div class="cli-filter-divider"></div>
		<div class="cli-filter-search">
			<svg width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true">
				<circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.5"/>
				<path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
			</svg>
			<input type="text" id="uc-search" placeholder="Nome da empresa, e-mail ou usuário" autocomplete="off" />
		</div>
	</div>

	<!-- ── Table ── -->
	<div class="cli-table-wrap">
		<div class="cli-table-card">
			<table class="cli-table" id="tableEmpresas">
				<thead>
					<tr>
						<th style="width:40px"></th>
						<th class="cli-col-rs">Empresa</th>
						<th style="width:24%">Contato principal</th>
						<th class="text-center" style="width:90px">Usuários</th>
						<th class="text-center" style="width:100px">Status</th>
						<th style="width:80px"></th>
					</tr>
				</thead>
				<tbody>
					<?php $idx = 0; foreach ($groups as $nome => $info): $idx++;
						$hasInativos = $info['inativos'] > 0;
						$allInactive = $info['ativos'] === 0 && $info['inativos'] > 0;
						if ($allInactive) { $statusClass = 'cli-usr-badge--off'; $statusText = 'Inativa'; }
						elseif ($hasInativos) { $statusClass = 'cli-usr-badge--mixed'; $statusText = $info['ativos'].'/'.(count($info['users'])); }
						else { $statusClass = 'cli-usr-badge--on'; $statusText = 'Ativa'; }
						// Build search blob
						$searchText = mb_strtolower($nome . ' ' . $info['email']);
						foreach ($info['users'] as $u) {
							$searchText .= ' ' . mb_strtolower($u->email . ' ' . $u->username);
						}
					?>
					<tr class="cli-usr-row" data-detail="ucDetail<?= $idx ?>" data-search="<?= h($searchText) ?>" data-has-inativos="<?= $hasInativos ? '1' : '0' ?>">
						<td class="text-center">
							<span class="cli-usr-expand cli-td-arrow-chev"><i class="fas fa-chevron-right"></i></span>
						</td>
						<td>
							<div class="cli-td-name">
								<div class="cli-av"><?= ucInitials($nome) ?></div>
								<span class="cli-name-main"><?= h($nome) ?></span>
							</div>
						</td>
						<td class="cli-td-email"><?= h($info['email']) ?></td>
						<td class="text-center">
							<span class="cli-usr-count"><?= count($info['users']) ?></span>
						</td>
						<td class="text-center">
							<span class="cli-usr-badge <?= $statusClass ?>"><?= $statusText ?></span>
						</td>
						<td class="cli-td-arrow">
							<?php if ($admin): ?>
							<?= $this->Html->link(
								'<i class="fas fa-user-plus"></i>',
								['action' => 'addcliente'],
								['class' => 'cli-btn-inativar', 'escape' => false, 'title' => 'Adicionar usuário', 'target' => '_blank', 'onclick' => 'event.stopPropagation();']
							) ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr class="cli-usr-detail" id="ucDetail<?= $idx ?>">
						<td colspan="6">
							<div class="cli-usr-inner">
								<?php if (count($info['users']) > 0): ?>
								<table class="cli-usr-table">
									<thead>
										<tr>
											<th>Usuário</th>
											<th>E-mail</th>
											<th class="text-center" style="width:80px">Status</th>
											<th style="width:90px"></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($info['users'] as $u): ?>
										<?php $off = $u->inativo == 1; ?>
										<tr>
											<td class="cli-usr-name"><?= h($u->username) ?></td>
											<td class="cli-usr-email"><?= h($u->email) ?></td>
											<td class="text-center">
												<span class="cli-usr-badge <?= $off ? 'cli-usr-badge--off' : 'cli-usr-badge--on' ?>">
													<?= $off ? 'Inativo' : 'Ativo' ?>
												</span>
											</td>
											<td>
												<?= $this->Html->link(
													'<i class="fas fa-pen"></i> Editar',
													['action' => 'editcliente', $u->id],
													['class' => 'cli-usr-edit', 'escape' => false]
												) ?>
											</td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
								<?php else: ?>
								<div class="cli-usr-empty">
									<i class="fas fa-user-friends" style="font-size:20px;display:block;margin-bottom:6px;opacity:.4"></i>
									Nenhum usuário cadastrado.
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
	</div>

</div>
</div>

<script>
(function() {
	var $ = window.jQuery;
	$(document).ready(function() {

		// Expand/collapse
		$(document).on('click', '.cli-usr-row', function(e) {
			if ($(e.target).closest('a, button').length) return;
			var $row = $(this);
			var $detail = $('#' + $row.data('detail'));
			$row.toggleClass('cli-row-open');
			$detail.toggleClass('cli-usr-open');
		});

		// Busca global
		$('#uc-search').on('input', function() {
			var q = $(this).val().toLowerCase();
			$('.cli-usr-row').each(function() {
				var match = !q || $(this).data('search').indexOf(q) !== -1;
				$(this).toggle(match);
				var $det = $('#' + $(this).data('detail'));
				if (!match) {
					$det.removeClass('cli-usr-open');
					$(this).removeClass('cli-row-open');
				}
				$det.toggle(match && $(this).hasClass('cli-row-open'));
			});
		});

		// Filtro por status
		$('#uc-status-pills .cli-pill').on('click', function() {
			$('#uc-status-pills .cli-pill').removeClass('active');
			$(this).addClass('active');
			var filter = $(this).data('filter');
			$('.cli-usr-row').each(function() {
				var show = filter === 'todos' || $(this).data('has-inativos') === '1';
				$(this).toggle(show);
				var $det = $('#' + $(this).data('detail'));
				if (!show) {
					$det.removeClass('cli-usr-open');
					$(this).removeClass('cli-row-open');
				}
				$det.toggle(show && $(this).hasClass('cli-row-open'));
			});
		});
	});
})();
</script>
