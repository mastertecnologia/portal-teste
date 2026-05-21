<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$perm = (array)($screen['perm'] ?? []);
$usuarios = (array)($perm['usuarios'] ?? []);
$matrix = (array)($perm['matrix'] ?? []);
$matrixCols = (array)($perm['matrix_cols'] ?? []);
$groups = (array)($perm['groups'] ?? []);
$filters = (array)($perm['filters'] ?? []);
$kpis = (array)($screen['kpis'] ?? []);
$H = $this->ServicedeskPrototype;
$nUsers = count($usuarios);
$nRoles = (int)($perm['roles_count'] ?? 0);
$nGroups = (int)($perm['groups_count'] ?? 0);
$logEventos = (int)($perm['log_eventos_30d'] ?? 0);
$uPerm = $H->sdpPage('perm');
$filterQ = (string)($filters['q'] ?? '');
$filterPerfil = (string)($filters['perfil'] ?? 'all');
$filterStatus = (string)($filters['status'] ?? 'all');
?>
<div id="pg-sd-perm" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">🔐 <?= h((string)($screen['title'] ?? __('Permissões & Usuários'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('fila')) ?>">← <?= h(__('Voltar')) ?></a>
			<?= $this->Html->link('📊 ' . __('Auditoria'), ['controller' => 'SistemaPrototype', 'action' => 'acessoPapeis'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('+ ' . __('Novo usuário'), ['controller' => 'SistemaPrototype', 'action' => 'usuarios'], ['class' => 'btn btn-primary btn-sm']) ?>
		</div>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) : ?>
				<?php
				$border = (string)($k['border'] ?? 'var(--teal)');
				$valColor = (string)($k['val_color'] ?? 'var(--teal-dark)');
				?>
				<div class="summary-card" style="border-left:3px solid <?= h($border) ?>;">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:<?= h($valColor) ?>;"><?= h((string)($k['val'] ?? '')) ?></div>
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="tabs" style="margin-bottom:14px;display:flex;gap:0;border-bottom:1px solid var(--border);overflow-x:auto;" id="sdp-perm-tabs">
		<button type="button" class="sdp-perm-tab" data-tab="usuarios" style="padding:10px 16px;border:none;background:transparent;cursor:pointer;border-bottom:3px solid var(--teal);font-weight:600;color:var(--teal-dark);">👥 <?= h(__('Usuários')) ?> (<?= (int)$nUsers ?>)</button>
		<button type="button" class="sdp-perm-tab" data-tab="perfis" style="padding:10px 16px;border:none;background:transparent;cursor:pointer;color:var(--text-muted);">🎭 <?= h(__('Perfis')) ?> (<?= (int)$nRoles ?>)</button>
		<button type="button" class="sdp-perm-tab" data-tab="grupos" style="padding:10px 16px;border:none;background:transparent;cursor:pointer;color:var(--text-muted);">👨‍👩‍👧 <?= h(__('Grupos')) ?> (<?= (int)$nGroups ?>)</button>
		<button type="button" class="sdp-perm-tab" data-tab="log" style="padding:10px 16px;border:none;background:transparent;cursor:pointer;color:var(--text-muted);">📜 <?= h(__('Log de acessos')) ?></button>
	</div>

	<div class="sdp-perm-panel" data-panel="usuarios">
		<div class="card" style="margin-bottom:14px;padding:12px 14px;">
			<form method="get" action="<?= h($uPerm) ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0;">
				<input type="text" name="q" value="<?= h($filterQ) ?>" placeholder="🔍 <?= h(__('Buscar usuário...')) ?>" style="flex:1;min-width:240px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;" />
				<select name="perfil" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
					<option value="all" <?= $filterPerfil === 'all' ? 'selected' : '' ?>><?= h(__('Todos os perfis')) ?></option>
					<option value="admin" <?= $filterPerfil === 'admin' ? 'selected' : '' ?>><?= h(__('Administrador')) ?></option>
					<option value="tecnico" <?= $filterPerfil === 'tecnico' ? 'selected' : '' ?>><?= h(__('Técnico')) ?></option>
					<option value="cliente" <?= $filterPerfil === 'cliente' ? 'selected' : '' ?>><?= h(__('Cliente')) ?></option>
				</select>
				<select name="status" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
					<option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>><?= h(__('Todos · ativos + inativos')) ?></option>
					<option value="ativo" <?= $filterStatus === 'ativo' ? 'selected' : '' ?>>✓ <?= h(__('Ativos')) ?></option>
					<option value="inativo" <?= $filterStatus === 'inativo' ? 'selected' : '' ?>>✗ <?= h(__('Inativos')) ?></option>
				</select>
				<button type="submit" class="btn btn-ghost btn-sm"><?= h(__('Filtrar')) ?></button>
			</form>
		</div>
		<div class="card" style="padding:0;overflow:hidden;">
			<div style="overflow-x:auto;">
				<table style="width:100%;border-collapse:collapse;font-size:12px;">
					<thead>
						<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Usuário')) ?></th>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('E-mail')) ?></th>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Perfil')) ?></th>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Grupos')) ?></th>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Último acesso')) ?></th>
							<th style="padding:10px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);">2FA</th>
							<th style="padding:10px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Status')) ?></th>
							<th style="padding:10px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Ações')) ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($usuarios as $u) : ?>
							<?php
							$badge = (string)($u['perfil_badge'] ?? 'tecnico');
							$badgeHtml = $badge === 'cliente'
								? '<span class="badge" style="background:var(--blue-light);color:#0C447C;font-size:10px;">👥 ' . h(__('Cliente')) . '</span>'
								: ($badge === 'admin'
									? '<span class="badge b-paga" style="font-size:10px;">🔑 ' . h($u['perfil']) . '</span>'
									: '<span class="badge b-aprov" style="font-size:10px;">🛠 ' . h($u['perfil']) . '</span>');
							$rowStyle = !empty($u['inactive_row']) ? 'background:#FEF2F2;' : '';
							?>
							<tr style="border-bottom:1px solid var(--border-light);<?= h($rowStyle) ?>">
								<td style="padding:10px 12px;">
									<div style="display:flex;align-items:center;gap:8px;">
										<div style="width:28px;height:28px;border-radius:50%;background:<?= h((string)($u['avatar_bg'] ?? 'var(--teal)')) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;"><?= h((string)($u['initials'] ?? '?')) ?></div>
										<strong><?= h((string)($u['nome'] ?? '')) ?></strong>
									</div>
								</td>
								<td style="padding:10px 12px;font-family:monospace;font-size:11px;"><?= h((string)($u['email'] ?? '')) ?></td>
								<td style="padding:10px 12px;"><?= $badgeHtml ?></td>
								<td style="padding:10px 12px;font-size:11px;"><?= h((string)($u['grupos'] ?? '')) ?></td>
								<td style="padding:10px 12px;font-family:monospace;font-size:11px;"><?= h((string)($u['ultimo_acesso'] ?? '')) ?></td>
								<td style="padding:10px 12px;text-align:center;"><?= !empty($u['twofa']) ? '<span class="badge b-paga" style="font-size:10px;">✓</span>' : '—' ?></td>
								<td style="padding:10px 12px;text-align:center;"><span class="badge <?= !empty($u['ativo']) ? 'b-paga' : 'b-pendente' ?>" style="font-size:10px;"><?= !empty($u['ativo']) ? '✓ ' . h(__('Ativo')) : h(__('Inativo')) ?></span></td>
								<td style="padding:10px 12px;text-align:center;"><?= $this->Html->link('✏', ['controller' => 'SistemaPrototype', 'action' => 'usuarios'], ['class' => 'btn btn-ghost btn-xs']) ?></td>
							</tr>
						<?php endforeach; ?>
						<?php if ($usuarios === []) : ?>
							<tr><td colspan="8" style="padding:16px;color:var(--text-muted);"><?= h(__('Nenhum usuário encontrado.')) ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="sdp-perm-panel" data-panel="perfis" style="display:none;">
		<div class="card">
			<div class="sec-title">🎭 <?= h(__('Perfis de acesso · matriz de permissões')) ?></div>
			<div style="overflow-x:auto;">
				<table style="width:100%;border-collapse:collapse;font-size:12px;">
					<thead>
						<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
							<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Permissão')) ?></th>
							<?php foreach ($matrixCols as $col) : ?>
								<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h((string)$col) ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($matrix as $row) : ?>
							<tr style="border-bottom:1px solid var(--border-light);">
								<td style="padding:8px 10px;font-weight:600;"><?= h((string)($row['perm'] ?? '')) ?></td>
								<?php foreach ((array)($row['marks'] ?? []) as $mark) : ?>
									<td style="text-align:center;padding:8px;color:<?= (int)$mark ? 'var(--teal)' : 'var(--text-muted)' ?>;font-weight:700;"><?= (int)$mark ? '✓' : '—' ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="sdp-perm-panel" data-panel="grupos" style="display:none;">
		<div class="card">
			<div class="sec-title">👨‍👩‍👧 <?= h(__('Grupos')) ?></div>
			<div style="font-size:13px;color:var(--text-muted);margin-bottom:12px;"><?= h(__('Grupos agregam usuários para roteamento e notificações.')) ?> <strong><?= h((string)($perm['groups_hint'] ?? sprintf(__('%d grupos ativos'), $nGroups))) ?></strong></div>
			<?php if ($groups !== []) : ?>
				<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:12px;">
					<?php foreach ($groups as $g) : ?>
						<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);border-left:3px solid var(--teal);">
							<strong style="font-size:13px;"><?= h((string)($g['nome'] ?? '')) ?></strong>
							<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= (int)($g['membros'] ?? 0) ?> <?= h(__('membros')) ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div><?= $this->Html->link(__('Gerenciar filas'), ['controller' => 'Queues', 'action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?></div>
		</div>
	</div>

	<div class="sdp-perm-panel" data-panel="log" style="display:none;">
		<div class="card">
			<div class="sec-title">📜 <?= h(__('Log de acessos · auditoria LGPD')) ?></div>
			<div style="font-size:13px;color:var(--text-muted);"><?= h(__('Registros imutáveis de todos os acessos · IP, dispositivo, ação, alvo · retenção 5 anos ·')) ?> <strong><?= number_format($logEventos, 0, ',', '.') ?> <?= h(__('eventos registrados últimos 30 dias')) ?></strong></div>
			<div style="margin-top:12px;"><?= $this->Html->link(__('Ver auditoria RBAC'), ['controller' => 'SistemaPrototype', 'action' => 'acessoPapeis'], ['class' => 'btn btn-ghost btn-sm']) ?></div>
		</div>
	</div>
</div>
<script>
(function () {
	var root = document.getElementById('sdp-perm-tabs');
	if (!root) return;
	root.querySelectorAll('.sdp-perm-tab').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var tab = btn.getAttribute('data-tab');
			root.querySelectorAll('.sdp-perm-tab').forEach(function (b) {
				b.style.borderBottom = '';
				b.style.color = 'var(--text-muted)';
				b.style.fontWeight = '';
			});
			btn.style.borderBottom = '3px solid var(--teal)';
			btn.style.color = 'var(--teal-dark)';
			btn.style.fontWeight = '600';
			document.querySelectorAll('.sdp-perm-panel').forEach(function (p) {
				p.style.display = p.getAttribute('data-panel') === tab ? 'block' : 'none';
			});
		});
	});
})();
</script>
