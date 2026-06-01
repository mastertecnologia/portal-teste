<?php /** @var array<string,mixed> $licDispositivo */ $d = (array)($licDispositivo ?? []); ?>
<h1 style="font-size:22px;margin-bottom:8px;">💻 <?= h($d['hostname'] ?: __('Dispositivo')) ?></h1>
<p style="color:var(--text-muted);"><?= h($d['cliente']) ?> · <?= h($d['serial'] ?: '') ?></p>
<div class="card" style="margin-top:14px;font-size:13px;line-height:1.8;">
	<div><strong>SO:</strong> <?= h($d['so'] ?: '—') ?></div>
	<div><strong><?= h(__('Último visto')) ?>:</strong> <?= h(is_object($d['ultimo_visto'] ?? null) && method_exists($d['ultimo_visto'], 'format') ? $d['ultimo_visto']->format('d/m/Y H:i') : (string)($d['ultimo_visto'] ?? '—')) ?></div>
</div>
<?= $this->Html->link(__('Lista'), ['action' => 'view', 'dispositivos'], ['class' => 'btn btn-ghost btn-sm']) ?>
