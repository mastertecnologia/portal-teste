<?php
/**
 * Relatórios Financeiros — pg-relatorios-fin.
 *
 * @var \App\View\AppView $this
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			PGM Soluções › <?= $this->Html->link(__('Financeiro'), ['action' => 'lista'], ['style' => 'color:var(--teal);']) ?> › <?= h(__('Relatórios')) ?>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📊 <?= h(__('Relatórios Financeiros')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Central de relatórios · prontos para gestão e contabilidade')) ?></div>
	</div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
	<?php foreach ((array)($relCards ?? []) as $card) : ?>
		<?= $this->Html->link(
			'<div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">'
				. '<div style="width:44px;height:44px;background:' . h((string)$card['gradient']) . ';color:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;">' . h((string)$card['icon']) . '</div>'
				. '<div><strong style="font-size:14px;color:var(--text);">' . h((string)$card['title']) . '</strong>'
				. '<div style="font-size:11px;color:var(--text-muted);">' . h((string)$card['tags']) . '</div></div></div>'
				. '<div style="font-size:12px;color:var(--text-muted);line-height:1.5;">' . h((string)$card['desc']) . '</div>',
			(array)$card['url'],
			['class' => 'card', 'escape' => false, 'style' => 'cursor:pointer;text-decoration:none;display:block;']
		) ?>
	<?php endforeach; ?>
</div>

<div class="card" style="margin-top:14px;">
	<div class="sec-title">📜 <?= h(__('Últimos relatórios gerados')) ?></div>
	<table style="width:100%;border-collapse:collapse;font-size:12px;">
		<thead><tr style="background:var(--bg-surface);">
			<th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Relatório')) ?></th>
			<th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Período')) ?></th>
			<th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Gerado')) ?></th>
			<th style="padding:8px;text-align:left;font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Por')) ?></th>
			<th style="padding:8px;text-align:center;font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Ações')) ?></th>
		</tr></thead>
		<tbody>
			<?php foreach ((array)($relRecentes ?? []) as $r) : ?>
				<tr style="border-bottom:1px solid var(--border-light);">
					<td style="padding:8px;"><?= h((string)$r['relatorio']) ?></td>
					<td style="padding:8px;font-size:11px;"><?= h((string)$r['periodo']) ?></td>
					<td style="padding:8px;font-size:11px;color:var(--text-muted);"><?= h($H->dt($r['gerado'] ?? null, 'd/m/Y H:i')) ?></td>
					<td style="padding:8px;"><?= h((string)$r['por']) ?></td>
					<td style="padding:8px;text-align:center;">
						<?= $this->Html->link('📥 PDF', (array)$r['url'], ['class' => 'btn btn-ghost btn-xs', 'escape' => false]) ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
