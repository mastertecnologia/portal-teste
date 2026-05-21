<?php
/**
 * SLA & Config — 6 abas alinhadas ao mockup ERP.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$config = (array)($screen['config'] ?? []);
$slaPolicies = (array)($config['sla_policies'] ?? []);
$queues = (array)($config['queues'] ?? []);
$automacoes = (array)($config['automacoes'] ?? []);
$horario = (array)($config['horario'] ?? []);
$kpis = (array)($screen['kpis'] ?? []);
$activeTab = (string)($screen['active_tab'] ?? 'sla');
$H = $this->ServicedeskPrototype;
$uConfig = $H->sdpPage('config');
$firstPolicy = $slaPolicies[0] ?? null;
$tabDefs = [
	'sla' => '📋 ' . __('Políticas SLA'),
	'filas' => '👥 ' . __('Filas'),
	'auto' => '⚡ ' . __('Automações'),
	'templ' => '📝 ' . __('Templates'),
	'horario' => '🕐 ' . __('Horário comercial'),
	'sat' => '⭐ ' . __('Pesquisa satisfação'),
];
?>
<div id="pg-sd-config" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">⚙ <?= h((string)($screen['title'] ?? __('SLA & Configurações'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('fila')) ?>">← <?= h(__('Voltar')) ?></a>
			<button type="button" class="btn btn-primary btn-sm" disabled title="<?= h(__('Protótipo somente leitura')) ?>">💾 <?= h(__('Salvar alterações')) ?></button>
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

	<div class="tabs" style="margin-bottom:14px;display:flex;gap:0;border-bottom:1px solid var(--border);overflow-x:auto;" id="sdp-config-tabs">
		<?php foreach ($tabDefs as $key => $label) :
			$isActive = ($key === $activeTab);
		?>
			<a href="<?= h($H->sdpPage('config', ['tab' => $key])) ?>" class="sdp-cfg-tab" data-tab="<?= h($key) ?>" style="padding:10px 16px;border:none;background:transparent;cursor:pointer;flex-shrink:0;font-size:13px;text-decoration:none;<?= $isActive ? 'border-bottom:3px solid var(--teal);color:var(--teal-dark);font-weight:600;' : 'color:var(--text-muted);' ?>"><?= h($label) ?></a>
		<?php endforeach; ?>
	</div>

	<div class="sdp-cfg-panel" data-panel="sla" style="<?= $activeTab !== 'sla' ? 'display:none;' : '' ?>">
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">📋 <?= h(__('Políticas de SLA cadastradas')) ?></div>
			<div style="overflow-x:auto;">
				<table style="width:100%;border-collapse:collapse;font-size:12px;">
					<thead>
						<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
							<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Política')) ?></th>
							<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Aplica a')) ?></th>
							<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('1ª resp')) ?></th>
							<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Resolução')) ?></th>
							<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Horário')) ?></th>
							<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Tickets')) ?></th>
							<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Ações')) ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ($slaPolicies === []) : ?>
							<tr><td colspan="7" style="padding:16px;color:var(--text-muted);"><?= h(__('Nenhuma política cadastrada.')) ?></td></tr>
						<?php else : foreach ($slaPolicies as $p) : ?>
							<tr style="border-bottom:1px solid var(--border-light);">
								<td style="padding:10px;"><strong><?= h((string)($p['nome'] ?? '')) ?></strong><?php if (!empty($p['subtitulo'])) : ?><div style="font-size:10px;color:var(--text-muted);"><?= h((string)$p['subtitulo']) ?></div><?php endif; ?></td>
								<td style="padding:10px;font-size:11px;"><?= h((string)($p['aplica'] ?? '')) ?></td>
								<td style="padding:10px;text-align:right;font-weight:600;"><?= h((string)($p['resposta'] ?? '')) ?></td>
								<td style="padding:10px;text-align:right;font-weight:600;"><?= h((string)($p['resolucao'] ?? '')) ?></td>
								<td style="padding:10px;font-size:11px;"><?= h((string)($p['horario'] ?? '')) ?></td>
								<td style="padding:10px;text-align:center;"><?= (int)($p['tickets'] ?? 0) ?></td>
								<td style="padding:10px;text-align:center;"><?= $this->Html->link('✏', ['controller' => 'Servicedesk', 'action' => 'workflowSlaAdmin'], ['class' => 'btn btn-ghost btn-xs']) ?></td>
							</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
			<?= $this->Html->link('+ ' . __('Nova política de SLA'), ['controller' => 'Servicedesk', 'action' => 'workflowSlaAdmin'], ['class' => 'btn btn-ghost btn-sm', 'style' => 'margin-top:12px;']) ?>
		</div>

		<?php if ($firstPolicy !== null) : ?>
			<div class="card">
				<div class="sec-title">⚙ <?= h(sprintf(__('Editor da política · %s'), (string)($firstPolicy['nome'] ?? ''))) ?></div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field"><label><?= h(__('Nome')) ?></label><input type="text" disabled value="<?= h((string)($firstPolicy['nome'] ?? '')) ?>" /></div>
					<div class="field"><label><?= h(__('Status')) ?></label><select disabled><option><?= h(__('Ativa')) ?></option></select></div>
				</div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field"><label><?= h(__('Tempo de 1ª resposta')) ?></label><input type="text" disabled value="<?= h((string)($firstPolicy['resposta'] ?? '')) ?>" /></div>
					<div class="field"><label><?= h(__('Tempo de resolução')) ?></label><input type="text" disabled value="<?= h((string)($firstPolicy['resolucao'] ?? '')) ?>" /></div>
				</div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field"><label><?= h(__('Horário aplicável')) ?></label><select disabled><option><?= h((string)($firstPolicy['horario_aplicavel'] ?? __('Comercial · seg-sex 8h-18h'))) ?></option></select></div>
					<div class="field"><label><?= h(__('Pausar SLA quando')) ?></label><select disabled><option><?= h((string)($firstPolicy['pausar_sla'] ?? __('Aguardando cliente OU fornecedor'))) ?></option></select></div>
				</div>
				<div style="background:var(--bg-surface);padding:12px;border-radius:8px;">
					<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:8px;">🔔 <?= h(__('Alertas escalonados')) ?></div>
					<div style="display:flex;flex-direction:column;gap:6px;font-size:12px;">
						<label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" checked disabled /> <?= h(__('A 75% do prazo · alertar técnico responsável')) ?></label>
						<label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" checked disabled /> <?= h(__('A 90% do prazo · alertar gerente da fila')) ?></label>
						<label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" checked disabled /> <?= h(__('SLA estourado · alertar diretor e cliente')) ?></label>
						<label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" disabled /> <?= h(__('Auto-escalonar para próximo nível ao estourar')) ?></label>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<div class="sdp-cfg-panel" data-panel="filas" style="<?= $activeTab !== 'filas' ? 'display:none;' : '' ?>">
		<div class="card">
			<div class="sec-title">👥 <?= h(__('Filas de atendimento')) ?></div>
			<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
				<?php if ($queues === []) : ?>
					<p style="color:var(--text-muted);font-size:13px;"><?= h(__('Nenhuma fila cadastrada.')) ?></p>
				<?php else : foreach ($queues as $q) : ?>
					<div style="padding:14px;background:var(--bg-surface);border-radius:var(--radius);border-left:4px solid <?= h((string)($q['border'] ?? 'var(--teal)')) ?>;">
						<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;"><strong><?= h((string)($q['nome'] ?? '')) ?></strong><?= $this->Html->link('✏', ['controller' => 'Queues', 'action' => 'index'], ['class' => 'btn btn-ghost btn-xs']) ?></div>
						<?php if (!empty($q['descricao'])) : ?><div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;"><?= h((string)$q['descricao']) ?></div><?php elseif (!empty($q['nivel'])) : ?><div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;"><?= h((string)$q['nivel']) ?></div><?php endif; ?>
						<div style="font-size:12px;display:flex;flex-direction:column;gap:4px;">
							<div>👥 <strong><?= (int)($q['tecnicos'] ?? 0) ?> <?= h(__('técnicos')) ?></strong><?php if (!empty($q['tecnicos_nomes'])) : ?> · <?= h((string)$q['tecnicos_nomes']) ?><?php endif; ?></div>
							<div>📋 <strong><?= (int)($q['tickets_30d'] ?? 0) ?> <?= h(__('tickets')) ?></strong> · 30d</div>
							<?php if ((string)($q['tempo_medio'] ?? '') !== '—') : ?><div>⏱ <?= h(__('Tempo médio')) ?>: <strong><?= h((string)$q['tempo_medio']) ?></strong></div><?php endif; ?>
							<?php if ((string)($q['satisfacao'] ?? '') !== '—') : ?><div>⭐ <?= h(__('Satisfação')) ?>: <strong><?= h((string)$q['satisfacao']) ?></strong></div><?php endif; ?>
						</div>
					</div>
				<?php endforeach; endif; ?>
			</div>
			<?= $this->Html->link('+ ' . __('Nova fila'), ['controller' => 'Queues', 'action' => 'index'], ['class' => 'btn btn-ghost btn-sm', 'style' => 'margin-top:12px;']) ?>
		</div>
	</div>

	<div class="sdp-cfg-panel" data-panel="auto" style="<?= $activeTab !== 'auto' ? 'display:none;' : '' ?>">
		<div class="card">
			<div class="sec-title">⚡ <?= h(__('Regras de automação')) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;">
				<?php foreach ($automacoes as $a) : ?>
					<?php $ruleKey = (string)($a['rule_key'] ?? 'roteamento'); ?>
					<div style="padding:12px;border:1px solid var(--border-light);border-radius:var(--radius);">
						<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
							<strong style="font-size:13px;"><?= h((string)($a['titulo'] ?? '')) ?></strong>
							<div style="display:flex;align-items:center;gap:8px;">
								<?= $this->Html->link('✏ ' . __('Editor'), $H->sdpPage('automacoes-editor', ['rule' => $ruleKey]), ['class' => 'btn btn-ghost btn-xs']) ?>
								<label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;"><input type="checkbox" <?= !empty($a['ativa']) ? 'checked' : '' ?> disabled /> <?= h(__('Ativa')) ?></label>
							</div>
						</div>
						<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($a['desc'] ?? '')) ?></div>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="btn btn-ghost btn-sm" style="margin-top:12px;" disabled>+ <?= h(__('Nova regra')) ?></button>
		</div>
	</div>

	<div class="sdp-cfg-panel" data-panel="templ" style="<?= $activeTab !== 'templ' ? 'display:none;' : '' ?>">
		<div class="card">
			<div class="sec-title">📝 <?= h(__('Templates de resposta')) ?></div>
			<div style="font-size:13px;color:var(--text-muted);margin-bottom:12px;"><?= h((string)($config['templates_hint'] ?? '')) ?></div>
			<?= $this->Html->link(__('Gerenciar templates'), $H->sdpPage('templates'), ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</div>

	<div class="sdp-cfg-panel" data-panel="horario" style="<?= $activeTab !== 'horario' ? 'display:none;' : '' ?>">
		<div class="card">
			<div class="sec-title">🕐 <?= h(__('Horário comercial')) ?></div>
			<div style="font-size:13px;color:var(--text-muted);"><?= h((string)($horario['resumo'] ?? $config['horario_hint'] ?? '')) ?></div>
		</div>
	</div>

	<div class="sdp-cfg-panel" data-panel="sat" style="<?= $activeTab !== 'sat' ? 'display:none;' : '' ?>">
		<div class="card">
			<div class="sec-title">⭐ <?= h(__('Pesquisa de satisfação CSAT')) ?></div>
			<div style="font-size:13px;color:var(--text-muted);margin-bottom:12px;"><?= h((string)($config['csat_hint'] ?? '')) ?></div>
			<?= $this->Html->link(__('Ver histórico CSAT'), $H->sdpPage('csat'), ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</div>
</div>

<script>
(function () {
	var root = document.getElementById('sdp-config-tabs');
	if (!root) return;
	root.querySelectorAll('.sdp-cfg-tab[data-tab]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			if (btn.tagName === 'A') {
				return;
			}
			e.preventDefault();
			var tab = btn.getAttribute('data-tab');
			root.querySelectorAll('.sdp-cfg-tab').forEach(function (b) {
				b.style.borderBottom = '';
				b.style.color = 'var(--text-muted)';
				b.style.fontWeight = '';
			});
			btn.style.borderBottom = '3px solid var(--teal)';
			btn.style.color = 'var(--teal-dark)';
			btn.style.fontWeight = '600';
			document.querySelectorAll('.sdp-cfg-panel').forEach(function (p) {
				p.style.display = p.getAttribute('data-panel') === tab ? 'block' : 'none';
			});
		});
	});
})();
</script>
