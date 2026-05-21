<?php
/**
 * @var \App\View\AppView $this
 * @var string $title
 * @var array<string,mixed> $ticket
 */
$this->assign('title', $title);
$t = $ticket;
$H = $this->ServicedeskPrototype;
$slaAlert = !empty($t['sla_alert']);
$pill = (array)($t['situacao_pill'] ?? []);
$prio = (array)($t['prioridade_meta'] ?? []);
$timeline = (array)($t['timeline'] ?? []);
$messages = (array)($t['messages'] ?? []);
$threadCounts = (array)($t['thread_counts'] ?? []);
$threadFilter = trim((string)$this->request->getQuery('thread'));
if (!in_array($threadFilter, ['todos', 'publicos', 'internos'], true)) {
	$threadFilter = 'todos';
}
if ($threadFilter === 'publicos') {
	$messages = array_values(array_filter($messages, static function (array $m): bool {
		$tipo = (string)($m['tipo'] ?? '');

		return $tipo === 'publico' || $tipo === 'cliente';
	}));
} elseif ($threadFilter === 'internos') {
	$messages = array_values(array_filter($messages, static function (array $m): bool {
		return (string)($m['tipo'] ?? '') === 'interno';
	}));
}
$anexos = (array)($t['anexos'] ?? []);
$worklog = (array)($t['worklog'] ?? []);
$clientStats = (array)($t['cliente_stats'] ?? []);
$clienteBadges = (array)($t['cliente_badges'] ?? []);
$categoriaDetalhe = (string)($t['categoria_detalhe'] ?? '');
$subcategoria = (string)($t['subcategoria'] ?? '');
$tempoEtapaAlert = !empty($t['tempo_etapa_alert']);
$tempoEtapaLabel = (string)($t['tempo_etapa_label'] ?? '');
$tempoEtapa = (string)($t['tempo_etapa'] ?? '');
$threadUrl = static function (string $filter) use ($H, $id): string {
	return $H->sdpUrl(['controller' => 'ServicedeskPrototype', 'action' => 'ticket', $id] + ($filter !== 'todos' ? ['?' => ['thread' => $filter]] : []));
};
$related = (array)($t['related_tickets'] ?? []);
$audit = (array)($t['audit_log'] ?? []);
$kb = (array)($t['kb_articles'] ?? []);
$tags = (array)($t['tags'] ?? []);
$band = (string)($t['status_band_style'] ?? '');
$id = (int)($t['id'] ?? 0);
$officialUrl = (array)($t['official_url'] ?? ['controller' => 'Servicedesk', 'action' => 'view', $id]);
?>
<div class="pgm-sd-prototype" id="pg-sd-ticket">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM › <?= $this->Html->link(__('Service Desk'), $H->sdpPage('fila'), ['style' => 'color:var(--teal);']) ?> › <?= h(__('Ticket')) ?></div>
			<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
				<h1 style="font-size:22px;font-weight:600;font-family:monospace;color:var(--teal);margin:0;">#<?= $id ?></h1>
				<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h((string)($t['assunto'] ?? '')) ?></h1>
			</div>
			<div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= h(__('Aberto em')) ?> <?= h((string)($t['created_fmt'] ?? '')) ?> · <?= h(__('Última atualização')) ?>: <?= h((string)($t['modified_fmt'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<?= $this->Html->link('← ' . __('Voltar fila'), $H->sdpPage('fila'), ['class' => 'btn btn-ghost btn-sm']) ?>
			<button type="button" class="btn btn-ghost btn-sm" disabled>📚 <?= h(__('Vincular KB')) ?></button>
			<button type="button" class="btn btn-ghost btn-sm" disabled>⬆ <?= h(__('Escalonar')) ?></button>
			<?= $this->Html->link('✓ ' . __('Resolver ticket'), $officialUrl, ['class' => 'btn btn-primary btn-sm']) ?>
		</div>
	</div>

	<div class="card" style="margin-bottom:14px;padding:16px;<?= h($band) ?>">
		<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
			<div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
				<div>
					<div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;margin-bottom:4px;"><?= h(__('Status atual')) ?></div>
					<span style="display:inline-block;background:<?= h((string)($pill['bg'] ?? '#7DD3C0')) ?>;color:<?= h((string)($pill['color'] ?? '#0a3d2c')) ?>;padding:5px 16px;border-radius:14px;font-size:12px;font-weight:700;"><?= h((string)($pill['label'] ?? '')) ?></span>
				</div>
				<?php if (!empty($t['sla_label'])) : ?>
					<div>
						<div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;margin-bottom:4px;">SLA</div>
						<span style="display:inline-block;background:<?= $slaAlert ? '#FEE2E2' : '#DCFCE7' ?>;color:<?= $slaAlert ? '#7A1822' : 'var(--teal-dark)' ?>;padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;"><?= $slaAlert ? '⚠ ' : '' ?><?= h((string)$t['sla_label']) ?></span>
					</div>
				<?php endif; ?>
				<div>
					<div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;margin-bottom:4px;"><?= h(__('Tempo total')) ?> · <?= h((string)($t['tempo_total'] ?? '—')) ?></div>
					<span style="font-size:13px;font-weight:600;"><?= h($tempoEtapaLabel) ?> <strong style="color:<?= $tempoEtapaAlert ? '#7A1822' : 'inherit' ?>;"><?= h($tempoEtapa) ?></strong></span>
				</div>
			</div>
			<div style="display:flex;gap:6px;flex-wrap:wrap;">
				<button type="button" class="btn btn-ghost btn-sm" disabled>⏸ <?= h(__('Pausar SLA')) ?></button>
				<button type="button" class="btn btn-ghost btn-sm" disabled>+ <?= h(__('Estender prazo')) ?></button>
			</div>
		</div>
		<?php if ($timeline !== []) : ?>
			<div style="margin-top:14px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
				<?php foreach ($timeline as $i => $step) : ?>
					<?php if ($i > 0) : ?>
						<div style="flex:1;height:2px;background:<?= !empty($step['done']) || !empty($step['active']) ? 'var(--teal-mid)' : 'var(--border)' ?>;min-width:30px;"></div>
					<?php endif; ?>
					<div style="display:flex;align-items:center;gap:4px;">
						<div style="width:32px;height:32px;border-radius:50%;background:<?= !empty($step['active']) ? '#F59E0B' : (!empty($step['done']) ? 'var(--teal-mid)' : 'var(--gray-100,#eee)') ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;"><?= !empty($step['done']) ? '✓' : h((string)($step['num'] ?? '')) ?></div>
						<div style="font-size:11px;"><strong<?= !empty($step['active']) ? ' style="color:#B45309;"' : '' ?>><?= h((string)($step['label'] ?? '')) ?></strong><br><span style="color:var(--text-muted);"><?= h((string)($step['when'] ?? '—')) ?></span></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:14px;">
		<div>
			<div class="card" style="margin-bottom:14px;">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
					<div class="sec-title sdp-sec-no-line" style="margin:0;border:none;">📋 <?= h(__('Descrição original')) ?></div>
					<span class="badge b-aprov" style="font-size:10px;"><?= h(__('Categoria')) ?>: <?= h((string)($t['tipo_ticket'] ?? '')) ?></span>
				</div>
				<div style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:var(--bg-surface);border-radius:var(--radius);">
					<div style="width:36px;height:36px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;"><?= h((string)($t['solicitante_initials'] ?? '?')) ?></div>
					<div style="flex:1;">
						<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
							<strong style="font-size:13px;"><?= h((string)($t['solicitante'] ?? '')) ?></strong>
							<span style="font-size:11px;color:var(--text-muted);font-family:monospace;"><?= h((string)($t['created_fmt'] ?? '')) ?></span>
						</div>
						<div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;"><?= h((string)($t['cliente_email'] ?? '')) ?> · <?= h((string)($t['cliente'] ?? '')) ?></div>
						<div style="font-size:13px;line-height:1.6;"><?= !empty($t['descricao']) ? nl2br(h((string)$t['descricao'])) : '<span style="color:var(--text-muted);">' . h(__('Sem texto.')) . '</span>' ?></div>
						<?php if ($anexos !== []) : ?>
							<div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap;">
								<?php foreach ($anexos as $ax) : ?>
									<span style="padding:4px 8px;background:#fff;border:1px solid var(--border-light);border-radius:6px;font-size:11px;">📎 <?= h((string)($ax['nome'] ?? '')) ?><?= !empty($ax['size']) ? ' · ' . h((string)$ax['size']) : '' ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="card" style="margin-bottom:14px;">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
					<div class="sec-title sdp-sec-no-line" style="margin:0;border:none;">💬 <?= h(sprintf(__('Thread · %d mensagens'), (int)($threadCounts['todos'] ?? count($messages)))) ?></div>
					<div style="display:flex;gap:4px;">
						<?= $this->Html->link(__('Todos'), $threadUrl('todos'), ['class' => 'btn btn-ghost btn-xs' . ($threadFilter === 'todos' ? '' : ''), 'style' => $threadFilter === 'todos' ? 'background:var(--teal-light);color:var(--teal-dark);' : '']) ?>
						<?= $this->Html->link(__('Públicos'), $threadUrl('publicos'), ['class' => 'btn btn-ghost btn-xs', 'style' => $threadFilter === 'publicos' ? 'background:var(--teal-light);color:var(--teal-dark);' : '']) ?>
						<?= $this->Html->link('🔒 ' . __('Internos'), $threadUrl('internos'), ['class' => 'btn btn-ghost btn-xs', 'style' => $threadFilter === 'internos' ? 'background:var(--teal-light);color:var(--teal-dark);' : '']) ?>
					</div>
				</div>
				<div style="display:flex;flex-direction:column;gap:14px;">
					<?php foreach ($messages as $msg) : ?>
						<?php $interno = (($msg['tipo'] ?? '') === 'interno'); ?>
						<div style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:<?= $interno ? '#F5F3FF' : 'var(--bg-surface)' ?>;border-left:3px solid <?= $interno ? '#6B5B95' : 'transparent' ?>;border-radius:8px;">
							<div style="width:32px;height:32px;border-radius:50%;background:<?= h((string)($msg['avatar_bg'] ?? ($interno ? '#6B5B95' : 'var(--teal)'))) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;"><?= h((string)($msg['initials'] ?? '?')) ?></div>
							<div style="flex:1;">
								<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;flex-wrap:wrap;gap:4px;">
									<strong style="font-size:13px;"><?= h((string)($msg['autor'] ?? '')) ?>
										<span style="font-size:10px;font-weight:600;background:<?= h((string)($msg['badge_bg'] ?? '')) ?>;color:<?= h((string)($msg['badge_color'] ?? '')) ?>;padding:2px 6px;border-radius:4px;margin-left:4px;"><?= h((string)($msg['badge'] ?? '')) ?></span>
									</strong>
									<span style="font-size:11px;color:var(--text-muted);font-family:monospace;"><?= h((string)($msg['when'] ?? '')) ?></span>
								</div>
								<div style="font-size:13px;line-height:1.6;"><?= nl2br(h((string)($msg['body'] ?? ''))) ?></div>
							</div>
						</div>
					<?php endforeach; ?>
					<?php if ($messages === []) : ?>
						<p style="margin:0;color:var(--text-muted);font-size:13px;"><?= h(__('Nenhum comentário.')) ?></p>
					<?php endif; ?>
				</div>
				<div style="margin-top:14px;border-top:1px solid var(--border-light);padding-top:14px;">
					<div style="display:flex;gap:6px;margin-bottom:8px;flex-wrap:wrap;">
						<button type="button" class="btn btn-ghost btn-xs" style="background:var(--teal-light);color:var(--teal-dark);" disabled>📤 <?= h(__('Resposta pública')) ?></button>
						<button type="button" class="btn btn-ghost btn-xs" disabled>🔒 <?= h(__('Nota interna')) ?></button>
						<button type="button" class="btn btn-ghost btn-xs" disabled>⏰ <?= h(__('Aguardar cliente')) ?></button>
						<button type="button" class="btn btn-ghost btn-xs" disabled>✓ <?= h(__('Marcar como resolvido')) ?></button>
					</div>
					<textarea rows="4" disabled placeholder="<?= h(__('Digite sua resposta... use @colaborador para mencionar · /comando para inserir templates')) ?>" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:inherit;resize:vertical;"></textarea>
					<div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;flex-wrap:wrap;gap:8px;">
						<div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
							<button type="button" class="btn btn-ghost btn-xs" disabled>📎 <?= h(__('Anexar')) ?></button>
							<button type="button" class="btn btn-ghost btn-xs" disabled>📚 <?= h(__('Inserir KB')) ?></button>
							<button type="button" class="btn btn-ghost btn-xs" disabled>🖼 <?= h(__('Imagem')) ?></button>
							<select disabled style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:#fff;"><option><?= h(__('Template: nenhum')) ?></option></select>
						</div>
						<div style="display:flex;gap:6px;">
							<button type="button" class="btn btn-ghost btn-sm" disabled><?= h(__('Salvar rascunho')) ?></button>
							<?= $this->Html->link('📤 ' . __('Abrir no Service Desk'), $officialUrl, ['class' => 'btn btn-primary btn-sm']) ?>
						</div>
					</div>
				</div>
			</div>

			<div class="card">
				<div class="sec-title">⏱ <?= h(__('Apontamento de horas (faturável)')) ?></div>
				<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:10px;">
					<div class="field"><label><?= h(__('Data')) ?></label><input type="date" disabled value="<?= h(date('Y-m-d')) ?>" /></div>
					<div class="field"><label><?= h(__('Tempo (h:mm)')) ?></label><input type="text" disabled placeholder="01:30" /></div>
					<div class="field"><label><?= h(__('Atividade')) ?></label><select disabled><option><?= h(__('Análise técnica')) ?></option></select></div>
				</div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field"><label><?= h(__('Tipo')) ?></label><select disabled><option><?= h(__('Faturável · contrato')) ?></option></select></div>
					<div class="field"><label><?= h(__('Técnico')) ?></label><input disabled value="<?= h((string)($t['tecnico'] ?? '')) ?>" /></div>
				</div>
				<div class="field" style="margin-bottom:10px;"><label><?= h(__('Descrição da atividade')) ?></label><input type="text" disabled placeholder="<?= h(__('Ex: configuração do perfil de acesso no AD')) ?>" /></div>
				<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
					<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Total apontado neste ticket')) ?>: <strong style="color:var(--teal-dark);"><?= h((string)($worklog['total_fmt'] ?? '0min')) ?></strong><?php if (!empty($worklog['billable_fmt']) && $worklog['billable_fmt'] !== '—') : ?> · <?= h(__('valor faturável')) ?> <strong><?= h((string)$worklog['billable_fmt']) ?></strong><?php endif; ?></div>
					<?= $this->Html->link('+ ' . __('Apontar hora'), $officialUrl, ['class' => 'btn btn-primary btn-sm']) ?>
				</div>
			</div>
		</div>

		<div>
			<div class="card" style="margin-bottom:14px;">
				<div class="sec-title">👥 <?= h(__('Cliente')) ?></div>
				<div style="padding:10px;background:var(--bg-surface);border-radius:8px;">
					<div style="font-weight:700;font-size:14px;color:var(--teal-dark);"><?= h((string)($t['cliente'] ?? '—')) ?></div>
					<?php if (!empty($t['cliente_codigo'])) : ?>
						<div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= h((string)$t['cliente_codigo']) ?><?= !empty($t['cliente_cnpj']) ? ' · ' . h((string)$t['cliente_cnpj']) : '' ?></div>
					<?php endif; ?>
					<?php if (!empty($t['cliente_tel']) || !empty($t['cliente_email'])) : ?>
						<div style="font-size:11px;color:var(--text-muted);"><?php if (!empty($t['cliente_tel'])) : ?>📞 <?= h((string)$t['cliente_tel']) ?><?php endif; ?><?php if (!empty($t['cliente_email'])) : ?> · 📧 <?= h((string)$t['cliente_email']) ?><?php endif; ?></div>
					<?php endif; ?>
					<?php if ($clienteBadges !== []) : ?>
						<div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap;">
							<?php foreach ($clienteBadges as $badge) : ?>
								<span class="badge <?= h((string)($badge['class'] ?? 'b-paga')) ?>" style="font-size:10px;"><?= h((string)($badge['label'] ?? '')) ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<div style="margin-top:10px;font-size:12px;">
					<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Tickets este mês')) ?></span><strong><?= (int)($clientStats['mes'] ?? 0) ?></strong></div>
					<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Tickets total')) ?></span><strong><?= (int)($clientStats['total'] ?? 0) ?></strong></div>
					<?php if (($clientStats['csat'] ?? null) !== null) : ?>
						<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Satisfação média')) ?></span><strong>⭐ <?= h(number_format((float)$clientStats['csat'], 1, ',', '.')) ?>/5</strong></div>
					<?php endif; ?>
					<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="color:var(--text-muted);"><?= h(__('Solicitante')) ?></span><strong><?= h((string)($t['solicitante'] ?? '—')) ?></strong></div>
				</div>
			</div>

			<div class="card" style="margin-bottom:14px;">
				<div class="sec-title">🎯 <?= h(__('Atribuição')) ?></div>
				<div class="field" style="margin-bottom:8px;"><label><?= h(__('Fila')) ?></label><select disabled class="form-control input-sm"><option><?= h((string)($t['queue_name'] ?? '—')) ?></option></select></div>
				<div class="field" style="margin-bottom:8px;"><label><?= h(__('Nível')) ?></label><select disabled class="form-control input-sm"><option><?= h((string)($t['support_level'] ?? ($prio['nivel'] ?? '—'))) ?></option></select></div>
				<div class="field" style="margin-bottom:8px;"><label><?= h(__('Técnico responsável')) ?></label><select disabled class="form-control input-sm"><option><?= h((string)($t['tecnico'] ?? '—')) ?></option></select></div>
				<div class="field" style="margin-bottom:0;"><label><?= h(__('Observador (CC)')) ?></label><input type="text" disabled placeholder="<?= h(__('email do observador')) ?>" /></div>
			</div>

			<div class="card" style="margin-bottom:14px;">
				<div class="sec-title">🏷 <?= h(__('Categorização')) ?></div>
				<div class="field" style="margin-bottom:8px;"><label><?= h(__('Tipo')) ?></label><select disabled><option><?= h((string)($t['tipo_ticket'] ?? '')) ?></option></select></div>
				<?php if ($categoriaDetalhe !== '') : ?>
					<div class="field" style="margin-bottom:8px;"><label><?= h(__('Categoria')) ?></label><select disabled><option><?= h($categoriaDetalhe) ?></option></select></div>
				<?php endif; ?>
				<?php if ($subcategoria !== '') : ?>
					<div class="field" style="margin-bottom:8px;"><label><?= h(__('Subcategoria')) ?></label><select disabled><option><?= h($subcategoria) ?></option></select></div>
				<?php endif; ?>
				<div class="field" style="margin-bottom:8px;"><label><?= h(__('Prioridade')) ?></label><select disabled><option><?= h((string)($prio['label'] ?? '—')) ?></option></select></div>
				<?php if ($tags !== []) : ?>
					<div class="field" style="margin-bottom:0;"><label><?= h(__('Tags')) ?></label>
						<div style="display:flex;gap:4px;flex-wrap:wrap;">
							<?php foreach ($tags as $tag) : ?>
								<span style="padding:3px 8px;background:var(--teal-light);color:var(--teal-dark);border-radius:6px;font-size:11px;"><?= h((string)$tag) ?></span>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="card" style="margin-bottom:14px;">
				<div class="sec-title">⏱ <?= h(__('Política de SLA')) ?></div>
				<div style="font-size:12px;display:flex;flex-direction:column;gap:6px;">
					<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light);"><span style="color:var(--text-muted);"><?= h(__('Política aplicada')) ?></span><strong><?= h((string)($t['sla_policy_name'] ?? '—')) ?></strong></div>
					<?php if (!empty($t['primeira_resposta_fmt'])) : ?>
						<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light);"><span style="color:var(--text-muted);"><?= h(__('Tempo 1ª resposta')) ?></span><strong style="color:var(--teal-dark);"><?= h((string)$t['primeira_resposta_fmt']) ?></strong></div>
					<?php endif; ?>
					<?php if (!empty($t['resolucao_sla_fmt'])) : ?>
						<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-light);"><span style="color:var(--text-muted);"><?= h(__('Tempo resolução')) ?></span><strong style="color:<?= $slaAlert ? '#7A1822' : 'var(--teal-dark)' ?>;"><?= h((string)$t['resolucao_sla_fmt']) ?></strong></div>
					<?php endif; ?>
					<?php if (!empty($t['data_limite_fmt'])) : ?>
						<div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--text-muted);"><?= h(__('Limite')) ?></span><strong><?= h((string)$t['data_limite_fmt']) ?></strong></div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ($kb !== []) : ?>
				<div class="card" style="margin-bottom:14px;">
					<div class="sec-title">📚 <?= h(__('Artigos KB sugeridos')) ?></div>
					<div style="display:flex;flex-direction:column;gap:6px;">
						<?php foreach ($kb as $art) : ?>
							<?php $kbCode = (string)($art['code'] ?? ''); ?>
							<div style="padding:8px 10px;background:var(--bg-surface);border-radius:6px;border-left:3px solid var(--teal);cursor:pointer;" onclick="window.location.href='<?= h($H->sdpPage('detalhe-kb', ['code' => $kbCode !== '' ? $kbCode : 'KB-042'])) ?>'">
								<div style="font-size:12px;font-weight:600;"><?= h($kbCode) ?> · <?= h(\Cake\Utility\Text::truncate((string)($art['titulo'] ?? ''), 42, ['ellipsis' => '…'])) ?></div>
								<div style="font-size:11px;color:var(--text-muted);"><?php if (!empty($art['rating'])) : ?>⭐ <?= h((string)$art['rating']) ?>/5 · <?php endif; ?><?= (int)($art['tickets'] ?? 0) ?> <?= h(__('tickets')) ?></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ($related !== []) : ?>
				<div class="card" style="margin-bottom:14px;">
					<div class="sec-title">🔗 <?= h(__('Tickets relacionados')) ?></div>
					<div style="display:flex;flex-direction:column;gap:6px;font-size:12px;">
						<?php foreach ($related as $rel) : ?>
							<div style="display:flex;justify-content:space-between;cursor:pointer;" onclick="window.location.href='<?= h($H->sdpTicketUrl((int)($rel['id'] ?? 0))) ?>'">
								<span style="font-family:monospace;color:var(--teal);">#<?= (int)($rel['id'] ?? 0) ?></span>
								<span style="flex:1;margin:0 8px;color:var(--text-muted);"><?= h(\Cake\Utility\Text::truncate((string)($rel['assunto'] ?? ''), 28, ['ellipsis' => '…'])) ?></span>
								<span class="badge b-paga" style="font-size:10px;"><?= h((string)($rel['situacao_label'] ?? '')) ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="card" style="margin-bottom:14px;background:linear-gradient(135deg,#0a3d2c 0%,#1D9E75 100%);color:#fff;border:none;">
				<div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px;">⚡ <?= h(__('Ações rápidas')) ?></div>
				<div style="display:flex;flex-direction:column;gap:6px;">
					<?= $this->Html->link('✓ ' . __('Resolver ticket'), $officialUrl, ['class' => 'btn btn-sm', 'style' => 'background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);text-align:left;']) ?>
					<button type="button" class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);text-align:left;" disabled>⬆ <?= h(__('Escalonar')) ?></button>
					<button type="button" class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);text-align:left;" disabled>💰 <?= h(__('Enviar p/ faturamento')) ?></button>
				</div>
			</div>

			<?php if ($audit !== []) : ?>
				<div class="card">
					<div class="sec-title">📜 <?= h(__('Histórico de auditoria')) ?></div>
					<div style="font-size:11px;display:flex;flex-direction:column;gap:6px;max-height:240px;overflow-y:auto;">
						<?php foreach ($audit as $ev) : ?>
							<div style="padding-bottom:6px;border-bottom:1px solid var(--border-light);"><strong><?= h((string)($ev['when'] ?? '')) ?></strong> · <?= h((string)($ev['text'] ?? '')) ?></div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
