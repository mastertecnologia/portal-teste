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
$band = (string)($t['status_band_style'] ?? '');
$id = (int)($t['id'] ?? 0);
?>
<div class="row">
<div class="col-12 pgm-sd-prototype" id="pg-sd-ticket">
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
<div>
<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM › <?= $this->Html->link(__('Service Desk'), $H->sdpPage('dashboard'), ['style'=>'color:var(--teal);']) ?> › <?= h(sprintf(__('Ticket #%s'), $id)) ?></div>
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
<h1 style="font-size:22px;font-weight:600;font-family:monospace;color:var(--teal);margin:0;">#<?= $id ?></h1>
<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h((string)($t['assunto'] ?? '')) ?></h1>
</div>
<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Aberto em')) ?> <?= h((string)($t['created_fmt'] ?? '')) ?> · <?= h(__('Atualizado')) ?> <?= h((string)($t['modified_fmt'] ?? '')) ?></div>
</div>
<div style="display:flex;gap:8px;flex-wrap:wrap;">
<?= $this->Html->link('← ' . __('Voltar fila'), $H->sdpPage('fila'), ['class' => 'btn btn-ghost btn-sm']) ?>
<?= $this->Html->link(__('Editar (oficial)'), ['controller' => 'Servicedesk', 'action' => 'view', $id], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
</div>

<div class="card" style="margin-bottom:14px;padding:16px;<?= h($band) ?>">
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
<div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
<div><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:4px;"><?= h(__('Status atual')) ?></div>
<span style="display:inline-block;background:<?= h((string)($pill['bg'] ?? '#7DD3C0')) ?>;color:<?= h((string)($pill['color'] ?? '#0a3d2c')) ?>;padding:5px 16px;border-radius:14px;font-size:12px;font-weight:700;"><?= h((string)($pill['label'] ?? '')) ?></span></div>
<?php if (!empty($t['sla_label'])) : ?>
<div><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:4px;">SLA</div>
<span style="display:inline-block;background:<?= $slaAlert ? '#FEE2E2' : '#DCFCE7' ?>;color:<?= $slaAlert ? '#7A1822' : 'var(--teal-dark)' ?>;padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;"><?= $slaAlert ? '⚠ ' : '' ?><?= h((string)$t['sla_label']) ?></span></div>
<?php endif; ?>
<div><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:4px;"><?= h(__('Tempo total')) ?></div>
<span style="font-size:13px;font-weight:600;"><?= h((string)($t['tempo_total'] ?? '—')) ?></span></div>
</div>
</div>
<?php if ($timeline !== []) : ?>
<div style="margin-top:14px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
<?php foreach ($timeline as $i => $step) : ?>
<?php if ($i > 0) : ?><div style="flex:1;height:2px;background:<?= !empty($step['done']) || !empty($step['active']) ? 'var(--teal-mid)' : 'var(--border)' ?>;min-width:30px;"></div><?php endif; ?>
<div style="display:flex;align-items:center;gap:4px;">
<div style="width:32px;height:32px;border-radius:50%;background:<?= !empty($step['active']) ? '#F59E0B' : (!empty($step['done']) ? 'var(--teal-mid)' : 'var(--gray-100,#eee)') ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;"><?= !empty($step['done']) ? '✓' : h((string)($step['num'] ?? '')) ?></div>
<div style="font-size:11px;"><strong><?= h((string)($step['label'] ?? '')) ?></strong><br><span style="color:var(--text-muted);"><?= h((string)($step['when'] ?? '')) ?></span></div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:14px;">
<div>
<div class="card" style="margin-bottom:14px;">
<div class="sec-title sdp-sec-no-line">📋 <?= h(__('Descrição original')) ?></div>
<div style="display:flex;gap:10px;padding:12px;background:var(--bg-surface);border-radius:var(--radius);">
<div style="width:36px;height:36px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;"><?= h((string)($t['solicitante_initials'] ?? '?')) ?></div>
<div style="flex:1;">
<strong><?= h((string)($t['solicitante'] ?? '')) ?></strong>
<div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;"><?= h((string)($t['cliente_email'] ?? '')) ?> · <?= h((string)($t['cliente'] ?? '')) ?></div>
<div style="font-size:13px;line-height:1.6;"><?= !empty($t['descricao']) ? nl2br(h((string)$t['descricao'])) : '<span class="text-muted">' . h(__('Sem texto.')) . '</span>' ?></div>
</div>
</div>
</div>

<div class="card" style="margin-bottom:14px;">
<div class="sec-title sdp-sec-no-line">💬 <?= h(__('Thread')) ?> · <?= count($messages) ?> <?= h(__('mensagens')) ?></div>
<?php foreach ($messages as $msg) : ?>
<?php $interno = (($msg['tipo'] ?? '') === 'interno'); ?>
<div style="display:flex;gap:10px;padding:12px;margin-bottom:10px;background:<?= $interno ? '#F5F3FF' : 'var(--bg-surface)' ?>;border-left:3px solid <?= $interno ? '#6B5B95' : 'transparent' ?>;border-radius:8px;">
<div style="width:32px;height:32px;border-radius:50%;background:<?= $interno ? '#6B5B95' : 'var(--teal)' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;"><?= h((string)($msg['initials'] ?? '?')) ?></div>
<div style="flex:1;">
<div style="display:flex;justify-content:space-between;margin-bottom:4px;"><strong><?= h((string)($msg['autor'] ?? '')) ?></strong><span style="font-size:11px;color:var(--text-muted);font-family:monospace;"><?= h((string)($msg['when'] ?? '')) ?></span></div>
<div style="font-size:13px;line-height:1.6;"><?= nl2br(h((string)($msg['body'] ?? ''))) ?></div>
</div>
</div>
<?php endforeach; ?>
<?php if ($messages === []) : ?><p class="text-muted"><?= h(__('Nenhum comentário.')) ?></p><?php endif; ?>
<div style="margin-top:14px;border-top:1px solid var(--border-light);padding-top:14px;">
<textarea rows="3" disabled placeholder="<?= h(__('Respostas no Service Desk oficial')) ?>" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;"></textarea>
</div>
</div>
</div>

<div>
<div class="card" style="margin-bottom:14px;">
<div class="sec-title">👥 <?= h(__('Cliente')) ?></div>
<div style="padding:10px;background:var(--bg-surface);border-radius:8px;">
<div style="font-weight:700;color:var(--teal-dark);"><?= h((string)($t['cliente'] ?? '—')) ?></div>
<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h((string)($t['cliente_email'] ?? '')) ?></div>
</div>
</div>
<div class="card" style="margin-bottom:14px;">
<div class="sec-title">🎯 <?= h(__('Atribuição')) ?></div>
<div class="field" style="margin-bottom:8px;"><label><?= h(__('Técnico')) ?></label><input class="form-control input-sm" disabled value="<?= h((string)($t['tecnico'] ?? '—')) ?>" /></div>
<div class="field"><label><?= h(__('Fila')) ?></label><input class="form-control input-sm" disabled value="<?= h((string)($prio['fila'] ?? '—')) ?>" /></div>
</div>
<div class="card">
<div class="sec-title">🏷 <?= h(__('Categorização')) ?></div>
<div class="field"><label><?= h(__('Prioridade')) ?></label><input class="form-control input-sm" disabled value="<?= h((string)($prio['label'] ?? '—')) ?>" /></div>
</div>
</div>
</div>
</div>
</div>
