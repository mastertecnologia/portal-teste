<?php
/**
 * DRE Gerencial — pg-dre.
 *
 * @var \App\View\AppView $this
 */
$H = $this->ErpPrototype;
$k = (array)($dreKpi ?? []);
$linhas = (array)($dreLinhas ?? []);
$toneColors = [
	'teal' => 'var(--teal-dark)',
	'blue' => '#0C447C',
	'purple' => '#3D2D63',
	'red' => '#7A1822',
	'muted' => 'var(--text-muted)',
];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			PGM Soluções › <?= $this->Html->link(__('Financeiro'), ['action' => 'lista'], ['style' => 'color:var(--teal);']) ?> › DRE
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📊 <?= h(__('DRE · Demonstrativo de Resultados')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Análise gerencial completa · receitas · custos · despesas · resultado líquido')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
		<?= $this->Form->create(null, ['type' => 'get', 'style' => 'margin:0;']) ?>
			<select name="periodo" style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:#fff;" onchange="this.form.submit()">
				<?php foreach ((array)($drePeriodos ?? []) as $p) : ?>
					<option value="<?= h((string)$p['value']) ?>" <?= (string)($drePeriodo ?? '') === (string)$p['value'] ? 'selected' : '' ?>><?= h(__('Período: {0}', (string)$p['label'])) ?></option>
				<?php endforeach; ?>
			</select>
		<?= $this->Form->end() ?>
		<?= $this->Html->link('📥 ' . __('Exportar PDF'), (array)($dreExportUrl ?? ['action' => 'view', 'dre']), ['class' => 'btn btn-primary btn-sm', 'escape' => false]) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Receita Bruta')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)($k['receita_bruta'] ?? 0))) ?></div><div style="font-size:11px;color:var(--teal-dark);">↑ <?= h(number_format((float)($k['receita_bruta_delta'] ?? 0), 1, ',', '.')) ?>% vs ant.</div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('Receita Líquida')) ?></div><div class="val" style="color:#0C447C;"><?= h($H->brl((float)($k['receita_liquida'] ?? 0))) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(number_format((float)($k['receita_liquida_pct'] ?? 0), 1, ',', '.')) ?>% da bruta</div></div>
	<div class="summary-card" style="border-left:3px solid #6B5B95;"><div class="lbl"><?= h(__('Lucro Bruto')) ?></div><div class="val" style="color:#3D2D63;"><?= h($H->brl((float)($k['lucro_bruto'] ?? 0))) ?></div><div style="font-size:11px;color:var(--teal-dark);"><?= h(number_format((float)($k['lucro_bruto_margem'] ?? 0), 1, ',', '.')) ?>% margem bruta</div></div>
	<div class="summary-card" style="border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('EBITDA')) ?></div><div class="val" style="color:#8A4D02;"><?= h($H->brl((float)($k['ebitda'] ?? 0))) ?></div><div style="font-size:11px;color:var(--teal-dark);"><?= h(number_format((float)($k['ebitda_margem'] ?? 0), 1, ',', '.')) ?>% margem</div></div>
	<div class="summary-card" style="background:var(--teal-light);border-left:3px solid var(--teal-mid);"><div class="lbl"><?= h(__('Lucro Líquido')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)($k['lucro_liquido'] ?? 0))) ?></div><div style="font-size:11px;color:var(--teal-dark);"><?= h(number_format((float)($k['lucro_liquido_margem'] ?? 0), 1, ',', '.')) ?>% margem · ↑ <?= h(number_format((float)($k['lucro_liquido_delta'] ?? 0), 1, ',', '.')) ?>%</div></div>
	<div class="summary-card" style="border-left:3px solid #D946A0;"><div class="lbl"><?= h(__('Geração de caixa')) ?></div><div class="val" style="color:#7A1B5C;"><?= h($H->brl((float)($k['geracao_caixa'] ?? 0))) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('após investimentos')) ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:14px 16px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;">
		<strong style="font-size:14px;">📊 <?= h(__('DRE Gerencial · {0}', (string)($dreLabel ?? ''))) ?></strong>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Valores em R$ · arredondamento padrão')) ?></div>
	</div>
	<div style="overflow-x:auto;">
		<table style="width:100%;border-collapse:collapse;font-size:13px;">
			<thead>
				<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
					<th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;width:40%;"><?= h(__('Conta')) ?></th>
					<th style="padding:10px 14px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h((string)($dreLabel ?? '')) ?></th>
					<th style="padding:10px 14px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;">% <?= h(__('Receita')) ?></th>
					<th style="padding:10px 14px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h((string)($drePrevLabel ?? '')) ?></th>
					<th style="padding:10px 14px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;">Δ %</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($linhas as $ln) :
					$tone = (string)($ln['tone'] ?? 'muted');
					$color = $toneColors[$tone] ?? 'inherit';
					$pad = ((int)($ln['indent'] ?? 0) > 0) ? '8px 14px 8px 30px' : '10px 14px';
					$bg = (string)($ln['bg'] ?? '');
					$val = (float)($ln['valor'] ?? 0);
					$valAnt = (float)($ln['valor_ant'] ?? 0);
					$delta = (float)($ln['delta'] ?? 0);
					$fmt = static function ($n) {
						$abs = number_format(abs($n), 2, ',', '.');
						return $n < 0 ? '(' . $abs . ')' : $abs;
					};
				?>
					<tr style="<?= $bg !== '' ? 'background:' . h($bg) . ';' : '' ?><?= !empty($ln['bold']) ? 'font-weight:700;' : '' ?>border-bottom:1px solid var(--border-light);">
						<td style="padding:<?= h($pad) ?>;color:<?= h($color) ?>;<?= empty($ln['bold']) ? '' : '' ?>"><?= h((string)$ln['conta']) ?></td>
						<td style="padding:<?= h($pad) ?>;text-align:right;color:<?= h($color) ?>;font-variant-numeric:tabular-nums;"><?= h($fmt($val)) ?></td>
						<td style="padding:<?= h($pad) ?>;text-align:right;color:var(--text-muted);"><?= h(number_format((float)($ln['pct'] ?? 0), 1, ',', '.')) ?>%</td>
						<td style="padding:<?= h($pad) ?>;text-align:right;font-variant-numeric:tabular-nums;color:var(--text-muted);"><?= h($fmt($valAnt)) ?></td>
						<td style="padding:<?= h($pad) ?>;text-align:right;color:<?= $delta >= 0 ? 'var(--teal-dark)' : '#7A1822' ?>;"><?= $delta >= 0 ? '↑' : '↓' ?> <?= h(number_format(abs($delta), 1, ',', '.')) ?>%</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
