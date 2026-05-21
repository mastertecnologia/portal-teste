<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$ed = (array)($screen['automacoes_editor'] ?? []);
$rule = (array)($ed['rule'] ?? []);
$wf = (array)($ed['workflow'] ?? []);
$histStats = (array)($ed['history_stats'] ?? []);
$histRows = (array)($ed['history_rows'] ?? []);
$conditions = (array)($wf['conditions'] ?? []);
$thenActions = (array)($wf['then_actions'] ?? []);
$elseActions = (array)($wf['else_actions'] ?? []);
$H = $this->ServicedeskPrototype;
?>
<div id="pg-sd-automacoes-editor" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Automações')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">⚡ <?= h((string)($screen['title'] ?? __('Editor de Automações'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? $ed['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('config')) ?>">← <?= h(__('Configurações')) ?></a>
			<button type="button" class="btn btn-ghost btn-sm" disabled>🧪 <?= h(__('Testar regra')) ?></button>
			<button type="button" class="btn btn-primary btn-sm" disabled>💾 <?= h(__('Salvar workflow')) ?></button>
		</div>
	</div>

	<div class="card" style="margin-bottom:14px;">
		<div class="g2">
			<div class="field"><label><?= h(__('Nome da regra')) ?> *</label><input type="text" disabled value="<?= h((string)($rule['nome'] ?? '')) ?>" /></div>
			<div class="field"><label><?= h(__('Status')) ?></label><select disabled><option><?= !empty($rule['ativa']) ? '✓ ' . h(__('Ativa')) : h(__('Inativa')) ?></option></select></div>
		</div>
		<div class="g2">
			<div class="field"><label><?= h(__('Quando disparar')) ?></label><select disabled><option><?= h((string)($rule['trigger'] ?? '')) ?></option></select></div>
			<div class="field"><label><?= h(__('Prioridade execução')) ?></label><select disabled><option><?= h((string)($rule['prioridade'] ?? __('Normal'))) ?></option></select></div>
		</div>
	</div>

	<div class="card">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
			<div class="sec-title" style="margin:0;border:none;">🔀 <?= h(__('Fluxo da regra')) ?></div>
			<div style="display:flex;gap:6px;">
				<button type="button" class="btn btn-ghost btn-xs" disabled>+ <?= h(__('Condição')) ?></button>
				<button type="button" class="btn btn-ghost btn-xs" disabled>+ <?= h(__('Ação')) ?></button>
				<button type="button" class="btn btn-ghost btn-xs" disabled>+ <?= h(__('Aprovação')) ?></button>
			</div>
		</div>

		<div style="background:var(--bg-surface);border-radius:var(--radius);padding:24px;">
			<div style="background:linear-gradient(135deg,#0a3d2c,#1D9E75);color:#fff;padding:14px 18px;border-radius:var(--radius);max-width:480px;margin:0 auto 8px auto;text-align:center;box-shadow:0 4px 12px rgba(10,61,44,.2);">
				<div style="font-size:11px;opacity:.85;text-transform:uppercase;font-weight:600;">⚡ <?= h(__('GATILHO')) ?></div>
				<div style="font-size:14px;font-weight:700;margin-top:4px;"><?= h((string)($wf['trigger_label'] ?? '')) ?></div>
			</div>
			<div style="text-align:center;color:var(--text-muted);font-size:20px;line-height:1;">↓</div>

			<div style="background:#fff;border:2px solid var(--blue);padding:14px 18px;border-radius:var(--radius);max-width:560px;margin:8px auto;">
				<div style="font-size:11px;color:#0C447C;text-transform:uppercase;font-weight:600;margin-bottom:8px;">🔍 <?= h(__('SE (condição)')) ?></div>
				<div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;font-size:13px;margin-bottom:6px;">
					<?php foreach ($conditions as $ci => $cond) : ?>
						<?php if ($ci > 0 && !empty($cond['join'])) : ?>
							<span style="color:var(--text-muted);font-weight:700;"><?= h((string)$cond['join']) ?></span>
						<?php endif; ?>
						<span style="padding:4px 10px;background:var(--blue-light);color:#0C447C;border-radius:6px;font-weight:600;"><?= h((string)($cond['field'] ?? '')) ?></span>
						<span style="padding:4px 8px;color:var(--text-muted);"><?= h((string)($cond['op'] ?? '')) ?></span>
						<span style="padding:4px 10px;background:#fff;border:1px solid var(--border);border-radius:6px;">"<?= h((string)($cond['value'] ?? '')) ?>"</span>
					<?php endforeach; ?>
				</div>
				<button type="button" class="btn btn-ghost btn-xs" disabled>+ <?= h(__('adicionar critério')) ?></button>
			</div>
			<div style="text-align:center;color:var(--text-muted);font-size:20px;line-height:1;">↓</div>

			<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:780px;margin:8px auto;">
				<div>
					<div style="text-align:center;font-size:11px;color:var(--teal-dark);font-weight:700;text-transform:uppercase;margin-bottom:6px;">✓ <?= h(__('ENTÃO (verdadeiro)')) ?></div>
					<div style="background:#fff;border:2px solid var(--teal);padding:12px;border-radius:var(--radius);display:flex;flex-direction:column;gap:6px;">
						<?php foreach ($thenActions as $i => $act) : ?>
							<div style="padding:8px 10px;background:var(--teal-light);border-radius:6px;font-size:12px;"><strong><?= (int)$i + 1 ?>.</strong> <?= h((string)$act) ?></div>
						<?php endforeach; ?>
						<button type="button" class="btn btn-ghost btn-xs" disabled>+ <?= h(__('adicionar ação')) ?></button>
					</div>
				</div>
				<div>
					<div style="text-align:center;font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-bottom:6px;">✗ <?= h(__('SENÃO (falso)')) ?></div>
					<div style="background:#fff;border:2px dashed var(--border);padding:12px;border-radius:var(--radius);display:flex;flex-direction:column;gap:6px;">
						<?php foreach ($elseActions as $i => $act) : ?>
							<div style="padding:8px 10px;background:var(--bg-surface);border-radius:6px;font-size:12px;color:var(--text-muted);"><strong><?= (int)$i + 1 ?>.</strong> <?= h((string)$act) ?></div>
						<?php endforeach; ?>
						<button type="button" class="btn btn-ghost btn-xs" disabled>+ <?= h(__('adicionar ação')) ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="card" style="margin-top:14px;">
		<div class="sec-title">📊 <?= h(__('Histórico de execuções · últimas 24h')) ?></div>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:14px;">
			<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);"><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Execuções 24h')) ?></div><strong style="font-size:20px;color:var(--teal-dark);"><?= h((string)($histStats['exec_24h'] ?? '0')) ?></strong></div>
			<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);"><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Sucesso')) ?></div><strong style="font-size:20px;color:var(--teal-dark);"><?= h((string)($histStats['sucesso'] ?? '—')) ?></strong></div>
			<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);"><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Tempo médio')) ?></div><strong style="font-size:20px;"><?= h((string)($histStats['tempo_medio'] ?? '—')) ?></strong></div>
			<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);"><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Tickets afetados')) ?></div><strong style="font-size:20px;"><?= h((string)($histStats['tickets_afetados'] ?? '0')) ?></strong></div>
		</div>
		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<thead><tr style="background:var(--bg-surface);"><th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Hora')) ?></th><th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Ticket')) ?></th><th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Resultado')) ?></th><th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Ação')) ?></th></tr></thead>
				<tbody>
					<?php foreach ($histRows as $row) : ?>
						<tr style="border-bottom:1px solid var(--border-light);">
							<td style="padding:8px;font-family:monospace;font-size:11px;"><?= h((string)($row['hora'] ?? '')) ?></td>
							<td style="padding:8px;"><?= $this->Html->link('#' . (int)($row['ticket_id'] ?? 0), $H->sdpTicketUrl((int)($row['ticket_id'] ?? 0)), ['style' => 'font-family:monospace;color:var(--teal);text-decoration:none;']) ?></td>
							<td style="padding:8px;color:<?= h((string)($row['result_color'] ?? 'var(--text-muted)')) ?>;"><?= h((string)($row['resultado'] ?? '')) ?></td>
							<td style="padding:8px;<?= !empty($row['acao_muted']) ? 'color:var(--text-muted);' : '' ?>"><?= h((string)($row['acao'] ?? '')) ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
