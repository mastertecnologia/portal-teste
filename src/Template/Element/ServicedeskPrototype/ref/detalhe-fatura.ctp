<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$f = (array)($screen['fatura'] ?? []);
$H = $this->ServicedeskPrototype;
$uFat = $H->sdpPage('fat');
$empty = !empty($f['empty']);
$cliente = (array)($f['cliente'] ?? []);
$contrato = (array)($f['contrato'] ?? []);
$cobranca = (array)($f['cobranca'] ?? []);
$contabil = (array)($f['contabil'] ?? []);
$ticketLines = (array)($f['ticket_lines'] ?? []);
$worklog = (array)($f['worklog'] ?? []);
$audit = (array)($f['audit'] ?? []);
$worklogTid = (int)($f['worklog_ticket_id'] ?? 0);
?>
<div id="pg-sd-detalhe-fatura" class="pgm-sd-prototype">
	<?php if ($empty) : ?>
		<div class="card"><p style="margin:0;color:var(--text-muted);"><?= h(__('Nenhum ticket resolvido disponível para fatura de demonstração.')) ?></p></div>
		<a class="btn btn-ghost btn-sm" href="<?= h($uFat) ?>">← <?= h(__('Voltar')) ?></a>
	<?php else : ?>
		<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
			<div>
				<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM › <?= $this->Html->link(__('Faturamento SD'), $uFat, ['style' => 'color:var(--teal);']) ?> › <?= h(__('Fatura')) ?></div>
				<h1 style="font-size:22px;font-weight:600;font-family:monospace;color:var(--teal);margin:0;"><?= h((string)($f['numero'] ?? '')) ?></h1>
				<div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
					<?= h((string)($f['cliente_nome'] ?? '')) ?> · <?= h(__('gerada')) ?> <?= h((string)($f['gerada_em'] ?? '')) ?> ·
					<span class="badge" style="background:<?= h((string)($f['status_bg'] ?? '#FAEEDA')) ?>;color:<?= h((string)($f['status_color'] ?? '#8A4D02')) ?>;font-size:10px;">⏰ <?= h((string)($f['status_label'] ?? '')) ?></span>
				</div>
			</div>
			<div style="display:flex;gap:8px;flex-wrap:wrap;">
				<a class="btn btn-ghost btn-sm" href="<?= h($uFat) ?>">← <?= h(__('Voltar')) ?></a>
				<button type="button" class="btn btn-ghost btn-sm" disabled>📥 PDF</button>
				<button type="button" class="btn btn-ghost btn-sm" disabled>📧 <?= h(__('Enviar cliente')) ?></button>
				<button type="button" class="btn btn-primary btn-sm" disabled>📤 <?= h(__('Emitir NF-e')) ?></button>
			</div>
		</div>

		<div class="card" style="margin-bottom:14px;padding:14px 18px;background:linear-gradient(135deg,#FAEEDA,#fff);border-left:4px solid var(--amber);">
			<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
				<div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
					<div>
						<div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Valor total')) ?></div>
						<div style="font-size:24px;font-weight:700;color:var(--teal-dark);"><?= h((string)($f['valor_total_fmt'] ?? '')) ?></div>
					</div>
					<div>
						<div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Vencimento')) ?></div>
						<div style="font-size:14px;font-weight:600;"><?= h((string)($f['vencimento_fmt'] ?? '')) ?></div>
					</div>
					<div>
						<div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Tickets incluídos')) ?></div>
						<div style="font-size:14px;font-weight:600;"><?= h((string)($f['tickets_resumo'] ?? '')) ?></div>
					</div>
				</div>
				<div style="display:flex;gap:6px;">
					<button type="button" class="btn btn-ghost btn-sm" disabled>✏ <?= h(__('Editar')) ?></button>
					<button type="button" class="btn btn-red btn-sm" disabled>🗑 <?= h(__('Cancelar')) ?></button>
				</div>
			</div>
		</div>

		<div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;">
			<div>
				<div class="card" style="margin-bottom:14px;">
					<div class="sec-title">📋 <?= h(__('Tickets faturados nesta nota')) ?></div>
					<div style="overflow-x:auto;">
						<table style="width:100%;border-collapse:collapse;font-size:12px;">
							<thead>
								<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
									<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Ticket')) ?></th>
									<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Assunto')) ?></th>
									<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Horas')) ?></th>
									<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Cobertas')) ?></th>
									<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Excedente')) ?></th>
									<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);">R$/h</th>
									<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Valor')) ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($ticketLines as $line) : ?>
									<tr style="border-bottom:1px solid var(--border-light);">
										<td style="padding:10px;"><?= $this->Html->link('#' . (int)($line['id'] ?? 0), $H->sdpTicketUrl((int)($line['id'] ?? 0)), ['style' => 'font-family:monospace;color:var(--teal);font-weight:700;text-decoration:none;']) ?></td>
										<td style="padding:10px;"><div style="font-weight:600;"><?= h((string)($line['assunto'] ?? '')) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h((string)($line['sub'] ?? '')) ?></div></td>
										<td style="padding:10px;text-align:right;"><?= h((string)($line['horas_fmt'] ?? '')) ?></td>
										<td style="padding:10px;text-align:right;color:var(--teal-dark);"><?= h((string)($line['cobertas_fmt'] ?? '')) ?></td>
										<td style="padding:10px;text-align:right;color:#8A4D02;font-weight:600;"><?= h((string)($line['excedente_fmt'] ?? '')) ?></td>
										<td style="padding:10px;text-align:right;"><?= h((string)($line['hora_rate_fmt'] ?? '')) ?></td>
										<td style="padding:10px;text-align:right;font-weight:700;color:var(--teal-dark);"><?= h((string)($line['valor_fmt'] ?? '')) ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
							<tfoot>
								<tr style="background:var(--bg-surface);"><td colspan="6" style="padding:12px;text-align:right;font-weight:700;">SUBTOTAL:</td><td style="padding:12px;text-align:right;font-weight:700;"><?= h((string)($f['subtotal_fmt'] ?? '')) ?></td></tr>
								<tr style="background:var(--bg-surface);"><td colspan="6" style="padding:6px 12px;text-align:right;color:var(--text-muted);"><?= h(__('Desconto')) ?>:</td><td style="padding:6px 12px;text-align:right;color:var(--text-muted);"><?= h((string)($f['desconto_fmt'] ?? '')) ?></td></tr>
								<tr style="background:var(--teal-light);"><td colspan="6" style="padding:14px;text-align:right;font-weight:700;font-size:14px;color:var(--teal-dark);"><?= h(__('TOTAL DA FATURA')) ?>:</td><td style="padding:14px;text-align:right;font-weight:700;font-size:16px;color:var(--teal-dark);"><?= h((string)($f['total_fmt'] ?? '')) ?></td></tr>
							</tfoot>
						</table>
					</div>
				</div>

				<?php if ($worklog !== []) : ?>
					<div class="card" style="margin-bottom:14px;">
						<div class="sec-title">⏱ <?= h(__('Apontamento detalhado')) ?> · <?= h(__('ticket')) ?> #<?= $worklogTid ?></div>
						<table style="width:100%;border-collapse:collapse;font-size:12px;">
							<thead><tr style="background:var(--bg-surface);"><th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Data')) ?></th><th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Atividade')) ?></th><th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Técnico')) ?></th><th style="padding:8px;text-align:right;font-size:11px;color:var(--text-muted);text-transform:uppercase;"><?= h(__('Horas')) ?></th></tr></thead>
							<tbody>
								<?php foreach ($worklog as $w) : ?>
									<tr style="border-bottom:1px solid var(--border-light);">
										<td style="padding:8px;font-family:monospace;font-size:11px;"><?= h((string)($w['data_fmt'] ?? '')) ?></td>
										<td style="padding:8px;"><?= h((string)($w['atividade'] ?? '')) ?></td>
										<td style="padding:8px;"><?= h((string)($w['tecnico'] ?? '')) ?></td>
										<td style="padding:8px;text-align:right;font-weight:600;"><?= h((string)($w['horas_fmt'] ?? '')) ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<?php if ($audit !== []) : ?>
					<div class="card">
						<div class="sec-title">📜 <?= h(__('Trilha de auditoria')) ?></div>
						<div style="display:flex;flex-direction:column;gap:6px;font-size:11px;">
							<?php foreach ($audit as $a) : ?>
								<div><strong><?= h((string)($a['when'] ?? '')) ?></strong> · <?= h((string)($a['who'] ?? '')) ?> · <?= h((string)($a['text'] ?? '')) ?></div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div>
				<div class="card" style="margin-bottom:14px;">
					<div class="sec-title">📥 <?= h(__('Dados do cliente')) ?></div>
					<div style="font-size:13px;line-height:1.7;">
						<strong style="color:var(--teal-dark);"><?= h((string)($cliente['nome'] ?? '')) ?></strong><br>
						<span style="font-size:11px;color:var(--text-muted);"><?= h((string)($cliente['cnpj'] ?? '')) ?></span><br>
						<?= h((string)($cliente['endereco'] ?? '')) ?><br>
						📧 <?= h((string)($cliente['email'] ?? '')) ?><br>
						📞 <?= h((string)($cliente['telefone'] ?? '')) ?>
					</div>
				</div>

				<div class="card" style="margin-bottom:14px;">
					<div class="sec-title">📄 <?= h(__('Contrato vinculado')) ?></div>
					<div style="padding:10px;background:var(--bg-surface);border-radius:8px;font-size:12px;">
						<strong><?= h((string)($contrato['codigo'] ?? '')) ?></strong>
						<div style="margin-top:4px;"><span class="badge b-paga" style="font-size:10px;">★ <?= h((string)($contrato['badge'] ?? '')) ?></span></div>
						<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h((string)($contrato['detalhe'] ?? '')) ?></div>
						<?php if (!empty($contrato['alerta'])) : ?><div style="font-size:11px;color:#8A4D02;margin-top:4px;">⚠ <?= h((string)$contrato['alerta']) ?></div><?php endif; ?>
					</div>
				</div>

				<div class="card" style="margin-bottom:14px;">
					<div class="sec-title">💳 <?= h(__('Cobrança')) ?></div>
					<div style="font-size:12px;line-height:1.7;">
						<div><strong><?= h(__('Método')) ?>:</strong> <?= h((string)($cobranca['metodo'] ?? '')) ?></div>
						<div><strong><?= h(__('Vencimento')) ?>:</strong> <?= h((string)($cobranca['vencimento'] ?? '')) ?></div>
						<div><strong><?= h(__('Parcelas')) ?>:</strong> <?= h((string)($cobranca['parcelas'] ?? '')) ?></div>
					</div>
				</div>

				<div class="card">
					<div class="sec-title">📊 <?= h(__('Impacto contábil')) ?></div>
					<div style="padding:10px;background:var(--bg-surface);border-radius:8px;font-size:11px;line-height:1.6;font-family:monospace;">
						<div style="color:var(--teal-dark);"><?= h((string)($contabil['debito'] ?? '')) ?></div>
						<div style="color:#7A1822;"><?= h((string)($contabil['credito'] ?? '')) ?></div>
						<div style="color:var(--text-muted);font-size:10px;margin-top:4px;"><?= h(__('Histórico')) ?>: <?= h((string)($contabil['historico'] ?? '')) ?></div>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
