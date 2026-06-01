<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $lic
 */
$row = (array)($lic ?? []);
$valor = $row['valor_anual'] ?? null;
$valorFmt = $valor !== null && $valor !== '' ? 'R$ ' . number_format((float)$valor, 2, ',', '.') : '—';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			PGM ERP › <?= $this->Html->link(__('Licenciamento'), ['action' => 'dashboard'], ['style' => 'color:var(--teal)']) ?>
			› <?= $this->Html->link(__('Licenças'), ['action' => 'licencas'], ['style' => 'color:var(--teal)']) ?>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📄 <?= h($row['codigo'] ?? '') ?></h1>
		<p style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= h($row['produto'] ?? '') ?> · <?= h($row['cliente'] ?? '') ?></p>
	</div>
	<div style="display:flex;gap:8px;">
		<?php if (($row['status'] ?? '') === 'rascunho') : ?>
			<?= $this->Html->link(__('Continuar wizard'), ['action' => 'view', 'nova-2', '?' => ['id' => (int)$row['id']]], ['class' => 'btn btn-primary btn-sm']) ?>
		<?php endif; ?>
		<?= $this->Html->link('← ' . __('Lista'), ['action' => 'licencas'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="stats" style="margin-bottom:14px;">
	<div class="stat"><div class="stat-l"><?= h(__('Status')) ?></div><div class="stat-n" style="font-size:14px;"><?= h(ucfirst((string)($row['status'] ?? ''))) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Assentos')) ?></div><div class="stat-n"><?= (int)($row['assentos'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Modelo')) ?></div><div class="stat-n" style="font-size:14px;"><?= h($row['modelo'] ?? '') ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Valor anual')) ?></div><div class="stat-n" style="font-size:14px;"><?= h($valorFmt) ?></div></div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Vigência')) ?></div>
	<p style="margin:0;font-size:13px;"><?= h($row['inicio'] ? (string)$row['inicio'] : '—') ?> — <?= h($row['fim'] ? (string)$row['fim'] : '—') ?></p>
</div>

<?php if (!empty($row['assentos_rows'])) : ?>
<div class="card" style="margin-top:14px;">
	<div class="sec-title"><?= h(__('Assentos atribuídos')) ?></div>
	<table class="tbl">
		<thead><tr><th><?= h(__('E-mail')) ?></th><th><?= h(__('Status')) ?></th></tr></thead>
		<tbody>
		<?php foreach ((array)$row['assentos_rows'] as $a) : ?>
			<tr><td><?= h($a['email']) ?></td><td><?= h($a['status']) ?></td></tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php endif; ?>
