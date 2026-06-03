<?php
/**
 * Reajuste em massa — pg-preco-reajuste-massa.
 *
 * @var float $reajustePct
 * @var float $reajusteTrava
 * @var array<int,array<string,mixed>> $reajusteItens
 * @var int $reajusteSel
 * @var int $reajusteTotal
 * @var int $reajusteAbaixoTrava
 * @var float|null $reajusteMargemAntes
 * @var float|null $reajusteMargemDepois
 */
$H = $this->ErpPrototype;
$exibir = array_slice($reajusteItens, 0, 6);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= h(__('Produtos › Tabela de Preços › Reajuste em massa')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📈 <?= h(__('Reajuste em Massa')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Aplique reajuste por % em vários produtos · com pré-visualização')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Cancelar'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="g2" style="gap:14px;align-items:start;">
	<div>
		<?= $this->Form->create(null, ['url' => ['controller' => 'ProdutosPrototype', 'action' => 'reajusteSave']]) ?>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">🧮 1. <?= h(__('Tipo de reajuste')) ?></div>
			<div class="g2" style="gap:10px;margin-top:10px;">
				<div class="field" style="margin:0;">
					<label><?= h(__('Percentual aplicado')) ?></label>
					<div style="display:flex;gap:6px;align-items:center;">
						<input type="number" name="pct" value="<?= h((string)$reajustePct) ?>" step="0.1" style="text-align:right;font-size:15px;font-weight:600;"/>
						<span style="font-size:14px;color:var(--text-muted);">%</span>
					</div>
				</div>
				<div class="field" style="margin:0;">
					<label><?= h(__('Margem mínima (trava)')) ?></label>
					<div style="display:flex;gap:6px;align-items:center;">
						<input type="number" name="trava" value="<?= h((string)$reajusteTrava) ?>" style="text-align:right;" readonly/>
						<span>%</span>
					</div>
				</div>
			</div>
		</div>

		<div class="card" style="margin-bottom:14px;">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
				<div class="sec-title" style="margin:0;">📦 2. <?= h(__('Produtos afetados')) ?></div>
				<div style="font-size:12px;font-weight:600;color:var(--teal-dark);"><?= sprintf('%d %s %d', (int)$reajusteSel, h(__('de')), (int)$reajusteTotal) ?> <?= h(__('selecionados')) ?></div>
			</div>
			<div style="display:flex;flex-direction:column;gap:4px;max-height:320px;overflow-y:auto;">
				<?php foreach ($exibir as $it) :
					$bg = !empty($it['alerta_trava']) ? '#FFFBEB' : (!empty($it['selecionado']) ? 'var(--teal-light)' : '#fff');
				?>
					<label style="display:flex;align-items:center;gap:8px;padding:8px;background:<?= h($bg) ?>;border-radius:6px;cursor:pointer;font-size:12px;border:1px solid var(--border-light);">
						<?php if (!empty($it['selecionado'])) : ?>
							<input type="checkbox" name="produto_ids[]" value="<?= (int)$it['id'] ?>" checked/>
						<?php else : ?>
							<input type="checkbox" disabled/>
						<?php endif; ?>
						<span style="flex:1;"><?= h((string)$it['codigo']) ?> · <?= h(\Cake\Utility\Text::truncate((string)$it['descricao'], 36)) ?></span>
						<span style="color:var(--text-muted);">
							<?php if (!empty($it['excluido'])) : ?>
								<?= h(__('excluída')) ?>
							<?php else : ?>
								<?= h($H->brl((float)$it['venda'])) ?> → <strong style="color:var(--teal-dark);"><?= h($H->brl((float)$it['novo_preco'])) ?></strong>
								<?php if (!empty($it['alerta_trava'])) : ?> ⚠<?php endif; ?>
							<?php endif; ?>
						</span>
					</label>
				<?php endforeach; ?>
				<?php if (count($reajusteItens) > 6) : ?>
					<div style="padding:8px;color:var(--text-muted);font-size:12px;">+<?= count($reajusteItens) - 6 ?> <?= h(__('outros produtos (todos serão incluídos ao aplicar)')) ?>...</div>
					<?php foreach (array_slice($reajusteItens, 6) as $it) : ?>
						<?php if (!empty($it['selecionado'])) : ?>
							<input type="hidden" name="produto_ids[]" value="<?= (int)$it['id'] ?>">
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<div class="field"><label><?= h(__('Motivo (vai para o histórico)')) ?></label><input type="text" name="motivo" value="<?= h(__('Reajuste em massa via tabela de preços')) ?>"/></div>
		<button type="submit" class="btn btn-primary btn-sm">✓ <?= h(__('Aplicar reajuste')) ?></button>
		<?= $this->Form->end() ?>
	</div>

	<div>
		<div class="card" style="position:sticky;top:14px;">
			<div class="sec-title">📊 <?= h(__('Impacto do reajuste')) ?></div>
			<div style="padding:12px;background:var(--teal-light);border-radius:8px;margin-bottom:10px;font-size:12px;line-height:1.9;">
				<div style="display:flex;justify-content:space-between;"><span><?= h(__('Produtos afetados:')) ?></span><strong><?= (int)$reajusteSel ?></strong></div>
				<div style="display:flex;justify-content:space-between;"><span><?= h(__('Reajuste médio:')) ?></span><strong style="color:var(--teal-dark);">+<?= h((string)$reajustePct) ?>%</strong></div>
				<div style="display:flex;justify-content:space-between;"><span><?= h(__('Margem média:')) ?></span><strong><?= $reajusteMargemAntes !== null ? h((string)$reajusteMargemAntes) . '%' : '—' ?> → <?= $reajusteMargemDepois !== null ? h((string)$reajusteMargemDepois) . '%' : '—' ?></strong></div>
			</div>
			<?php if ($reajusteAbaixoTrava > 0) : ?>
				<div style="padding:10px;background:#FFFBEB;border-radius:6px;border-left:3px solid var(--amber);font-size:11px;color:#8A4D02;">
					⚠ <strong><?= (int)$reajusteAbaixoTrava ?></strong> <?= h(__('produto(s) ficariam abaixo da margem mínima — revise manualmente.')) ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
