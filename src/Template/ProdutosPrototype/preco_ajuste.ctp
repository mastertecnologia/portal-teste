<?php
/**
 * Ajuste de preço individual — pg-preco-ajuste.
 *
 * @var array<string,mixed> $produto
 * @var array<int,array<string,mixed>> $historico
 */
$H = $this->ErpPrototype;
$p = $produto;
$venda = (float)$p['venda'];
$pctDefault = 4;
$novo = round($venda * (1 + ($pctDefault / 100)), 2);
$margemAtual = $p['margem'];
$margemNova = null;
if ($novo > 0 && (float)$p['custo'] > 0) {
	$margemNova = round((1 - ((float)$p['custo'] / $novo)) * 100, 0);
} elseif ($novo > 0 && (float)$p['custo'] <= 0) {
	$margemNova = 100;
}
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= h(__('Produtos › Tabela de Preços › Ajustar preço')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">💰 <?= sprintf(h(__('Ajustar Preço · %s')), h((string)$p['codigo'])) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h((string)$p['descricao']) ?> · <?= h(__('ajuste com vigência e histórico preservado')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Cancelar'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="g2" style="gap:14px;align-items:start;">
	<div>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">💰 <?= h(__('Novo preço')) ?></div>
			<?= $this->Form->create(null, ['url' => ['controller' => 'ProdutosPrototype', 'action' => 'precoSave']]) ?>
			<input type="hidden" name="produto_id" value="<?= (int)$p['id'] ?>">
			<input type="hidden" name="redirect" value="ajuste">
			<div class="g2" style="gap:10px;">
				<div class="field" style="margin:0;">
					<label><?= h(__('Tabela')) ?></label>
					<select disabled><option><?= sprintf(h(__('Padrão (atual: %s)')), h($H->brl($venda))) ?></option></select>
				</div>
				<div class="field" style="margin:0;">
					<label><?= h(__('Novo preço (R$)')) ?></label>
					<input type="number" name="vlunitario" step="0.01" min="0" value="<?= h(number_format($novo, 2, '.', '')) ?>" style="text-align:right;font-size:16px;font-weight:600;" required>
				</div>
			</div>
			<div style="padding:12px;background:var(--teal-light);border-radius:8px;margin-top:8px;">
				<div style="display:flex;justify-content:space-between;font-size:13px;"><span><?= h(__('De:')) ?></span><span style="text-decoration:line-through;color:var(--text-muted);"><?= h($H->brl($venda)) ?></span></div>
				<div style="display:flex;justify-content:space-between;font-size:15px;font-weight:600;"><span><?= h(__('Para:')) ?></span><span style="color:var(--teal-dark);"><?= h($H->brl($novo)) ?></span></div>
				<?php if ($margemAtual !== null && $margemNova !== null) : ?>
					<div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-top:4px;"><span><?= h(__('Margem resultante:')) ?></span><span><?= (int)$margemAtual ?>% → <?= (int)$margemNova ?>%</span></div>
				<?php endif; ?>
			</div>
			<div class="field" style="margin-top:10px;"><label><?= h(__('Motivo (histórico)')) ?></label><input type="text" name="motivo" placeholder="<?= h(__('Ex: ajuste de margem · negociação fornecedor')) ?>"/></div>
			<button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px;">💾 <?= h(__('Salvar ajuste')) ?></button>
			<?= $this->Form->end() ?>
		</div>
	</div>
	<div>
		<div class="card" style="position:sticky;top:14px;">
			<div class="sec-title">📜 <?= h(__('Histórico deste produto')) ?></div>
			<div style="display:flex;flex-direction:column;gap:6px;font-size:11px;">
				<?php foreach ($historico as $h) : ?>
					<div style="padding:8px;background:var(--bg-surface);border-radius:6px;border-left:2px solid var(--teal);">
						<div style="display:flex;justify-content:space-between;"><strong><?= h((string)$h['dia']) ?></strong>
							<?php if (!empty($h['pct'])) : ?><span style="color:var(--teal-dark);"><?= h((string)$h['pct']) ?></span><?php endif; ?>
						</div>
						<div style="color:var(--text-muted);">
							<?php if ($h['de'] !== null) : ?>
								<?= h($H->brl((float)$h['de'])) ?> → <?= h($H->brl((float)$h['para'])) ?>
							<?php else : ?>
								<?= h($H->brl((float)$h['para'])) ?>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?= $this->Html->link(__('Ver histórico completo →'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos', '?' => ['q' => (string)$p['codigo']]], ['class' => 'btn btn-ghost btn-xs', 'style' => 'margin-top:4px;']) ?>
		</div>
	</div>
</div>
