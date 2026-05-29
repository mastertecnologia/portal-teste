<?php
/**
 * Cobrança & Baixa de títulos — pg-orc-cobranca.
 *
 * @var \App\View\AppView $this
 */
$H = $this->ErpPrototype;
$cliente = (array)($cliente ?? []);
$k = (array)($kpi ?? []);
$totalFat = (float)($k['faturado'] ?? 0);
$pctRec = $totalFat > 0 ? round(((float)($k['recebido'] ?? 0) / $totalFat) * 100) : 0;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			<?= $this->Html->link(__('Orçamentos'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['style' => 'color:var(--teal);']) ?>
			› <?= h(__('Cobrança')) ?>
		</div>
		<h1 style="font-size:20px;font-weight:600;margin:0;"><?= h(__('Cobrança & Baixa de títulos · Orç. #{0}', (int)($orcId ?? 0) > 0 ? (int)$orcId : (string)($fatNumero ?? ''))) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
			<?= h((string)($cliente['nome'] ?? '—')) ?> · <?= sprintf(h(__('%d títulos · Total %s')), (int)($k['qtd'] ?? 0), $H->brl($totalFat)) ?>
		</div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📤 ' . __('Remessa CNAB 240'), (array)($remessa_url ?? []), ['class' => 'btn btn-ghost btn-sm', 'escape' => false]) ?>
		<?= $this->Html->link('📥 ' . __('Importar retorno'), (array)($retorno_url ?? []), ['class' => 'btn btn-blue btn-sm', 'escape' => false]) ?>
		<?= $this->Html->link(__('Ver lista'), ['action' => 'titulos'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<?= $H->stepper((array)($steps ?? [])) ?>

<?php if (!empty($notas)) : ?>
<div class="alert-box alert-green" style="margin:14px 0;">
	<strong><?= h(__('Notas autorizadas:')) ?></strong>
	<?php foreach ($notas as $n) : ?>
		<?= h((string)$n->get('modelo')) ?> <?= h((string)$n->get('numero')) ?>
	<?php endforeach; ?>
	· <?= h(__('PDFs e XMLs disponíveis no módulo fiscal.')) ?>
</div>
<?php endif; ?>

<div class="summary-grid" style="margin:14px 0;">
	<div class="summary-card" style="background:var(--teal-light);"><div class="lbl"><?= h(__('Total faturado')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl($totalFat)) ?></div><div style="font-size:11px;"><?= sprintf(h(__('%d títulos')), (int)($k['qtd'] ?? 0)) ?></div></div>
	<div class="summary-card" style="background:#E1F5EE;"><div class="lbl"><?= h(__('Recebido')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)($k['recebido'] ?? 0))) ?></div><div style="font-size:11px;"><?= (int)$pctRec ?>% · <?= h(__('parcelas pagas')) ?></div></div>
	<div class="summary-card" style="background:var(--blue-light);"><div class="lbl"><?= h(__('A receber')) ?></div><div class="val" style="color:#0C447C;"><?= h($H->brl((float)($k['a_receber'] ?? 0))) ?></div></div>
	<div class="summary-card" style="background:#FAEEDA;"><div class="lbl"><?= h(__('Em atraso')) ?></div><div class="val" style="color:#8A4D02;"><?= h($H->brl((float)($k['atraso'] ?? 0))) ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:12px 18px;background:var(--bg-surface);border-bottom:1px solid var(--border);font-weight:600;">
		<?= sprintf(h(__('Títulos a receber · %d títulos · %s')), (int)($k['qtd'] ?? 0), h((string)($banco_label ?? '—'))) ?>
	</div>
	<div style="padding:0;">
		<?php if (($titulos ?? []) === []) : ?>
			<div style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum título gerado para este faturamento.')) ?></div>
		<?php else : foreach ($titulos as $t) :
			$st = (array)($t['status'] ?? []);
		?>
			<div style="display:flex;align-items:flex-start;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border-light);gap:12px;flex-wrap:wrap;<?= !empty($st['row_bg']) ? 'background:' . h((string)$st['row_bg']) . ';' : '' ?>">
				<div style="flex:1;min-width:220px;">
					<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
						<strong><?= h(__('Parc. {0}', (string)$t['parcela'])) ?></strong>
						<span class="titulo-cod"><?= h((string)$t['codigo']) ?></span>
						<?= $H->badge((string)($st['label'] ?? '—'), (string)($st['badge'] ?? 'pendente')) ?>
					</div>
					<div style="font-size:12px;color:var(--text-muted);">
						<?= h(__('Boleto')) ?> · <?= h(__('Vencimento')) ?> <?= h($H->dt($t['vencimento'])) ?>
						<?php if (!empty($t['nosso_numero'])) : ?> · NN <?= h((string)$t['nosso_numero']) ?><?php endif; ?>
					</div>
					<?php if (!empty($t['data_baixa']) && ($st['state'] ?? '') === 'pago') : ?>
						<div style="font-size:11px;color:var(--teal-dark);margin-top:4px;">✓ <?= h(__('Baixado')) ?> · <?= h($H->dt($t['data_baixa'], 'd/m/Y H:i')) ?></div>
					<?php endif; ?>
				</div>
				<div style="text-align:right;">
					<div style="font-size:16px;font-weight:700;color:var(--teal-dark);"><?= h($H->brl((float)$t['valor'])) ?></div>
					<div style="margin-top:8px;display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
						<?php if (($st['state'] ?? '') === 'pago') : ?>
							<?= $this->Html->link(__('Recibo'), (array)$t['fatura_url'], ['class' => 'btn btn-ghost btn-xs']) ?>
						<?php else : ?>
							<?= $this->Html->link(__('Baixa manual'), (array)$t['fatura_url'], ['class' => 'btn btn-primary btn-xs']) ?>
							<?= $this->Html->link(__('2ª via'), (array)$t['fatura_url'], ['class' => 'btn btn-ghost btn-xs']) ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; endif; ?>
	</div>
</div>
