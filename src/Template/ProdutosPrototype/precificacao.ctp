<?php
/**
 * Centro de Cálculo — simulador de margem, desconto e impostos.
 *
 * @var \App\View\AppView $this
 * @var array<int,string> $precificOpcoes
 * @var array<string,mixed>|null $precificProduto
 * @var array{produto_id:int,margem:float,desconto:float,icms:float,pis_cofins:float} $precificFiltro
 * @var array<string,float>|null $precificResultado
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Produtos · Precificação')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🧮 <?= h(__('Centro de Cálculo de Preço')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Simule margem, desconto máximo e carga tributária para qualquer produto')) ?></div>
	</div>
	<?= $this->Html->link('← ' . __('Tabela de preços'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="card">
	<form method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:flex-end;">
		<div class="field" style="grid-column:1/-1;">
			<label><?= h(__('Produto base')) ?></label>
			<select name="produto_id" onchange="this.form.submit()">
				<option value="0"><?= h(__('— Escolha um produto —')) ?></option>
				<?php foreach ($precificOpcoes as $id => $lbl) :
					$sel = (int)$id === (int)$precificFiltro['produto_id'] ? ' selected' : '';
				?>
					<option value="<?= (int)$id ?>"<?= $sel ?>><?= h((string)$lbl) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field"><label><?= h(__('Margem alvo (%)')) ?></label><input type="number" name="margem" value="<?= h((string)$precificFiltro['margem']) ?>" step="0.01" min="0" max="500"></div>
		<div class="field"><label><?= h(__('Desconto máx (%)')) ?></label><input type="number" name="desconto" value="<?= h((string)$precificFiltro['desconto']) ?>" step="0.01" min="0" max="90"></div>
		<div class="field"><label><?= h(__('ICMS (%)')) ?></label><input type="number" name="icms" value="<?= h((string)$precificFiltro['icms']) ?>" step="0.01" min="0" max="35"></div>
		<div class="field"><label><?= h(__('PIS + COFINS (%)')) ?></label><input type="number" name="pis_cofins" value="<?= h((string)$precificFiltro['pis_cofins']) ?>" step="0.01" min="0" max="20"></div>
		<div><button type="submit" class="btn btn-primary btn-sm">🧮 <?= h(__('Recalcular')) ?></button></div>
	</form>
</div>

<?php if ($precificProduto === null) : ?>
	<div class="alert-box alert-blue"><?= h(__('Escolha um produto acima para ver a simulação de margem e impostos.')) ?></div>
<?php elseif ($precificResultado !== null) : ?>
	<div class="card" style="background:linear-gradient(135deg,var(--teal-light),#fff);">
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Produto base')) ?></div>
		<div style="font-size:16px;font-weight:600;color:var(--teal-dark);">
			<?= h((string)$precificProduto['codigo']) ?> · <?= h((string)$precificProduto['descricao']) ?>
		</div>
		<div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= h(__('Preço atual de tabela')) ?>: <strong><?= h($H->brl((float)$precificProduto['venda'])) ?></strong></div>
	</div>

	<div class="summary-grid" style="margin-bottom:14px;">
		<div class="summary-card" style="border-left:3px solid var(--blue);">
			<div class="lbl"><?= h(__('Custo estimado')) ?></div>
			<div class="val" style="color:#0C447C;"><?= h($H->brl((float)$precificResultado['custo_estimado'])) ?></div>
			<div style="font-size:10px;color:var(--text-muted);"><?= h(__('preço atual ÷ (1+margem%)')) ?></div>
		</div>
		<div class="summary-card" style="border-left:3px solid var(--teal);">
			<div class="lbl"><?= h(__('Preço sugerido (margem ' . (float)$precificFiltro['margem'] . '%)')) ?></div>
			<div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)$precificResultado['preco_sugerido'])) ?></div>
		</div>
		<div class="summary-card" style="border-left:3px solid var(--amber);">
			<div class="lbl"><?= h(__('Preço mín. com desconto')) ?></div>
			<div class="val" style="color:#8A4D02;"><?= h($H->brl((float)$precificResultado['preco_minimo_com_desconto'])) ?></div>
			<div style="font-size:10px;color:var(--text-muted);">−<?= (float)$precificFiltro['desconto'] ?>%</div>
		</div>
		<div class="summary-card" style="border-left:3px solid <?= (float)$precificResultado['margem_liquida_pct'] >= 10 ? 'var(--teal)' : 'var(--red)' ?>;">
			<div class="lbl"><?= h(__('Margem líquida no piso')) ?></div>
			<div class="val" style="color:<?= (float)$precificResultado['margem_liquida_pct'] >= 10 ? 'var(--teal-dark)' : '#7A1822' ?>;"><?= h((string)$precificResultado['margem_liquida_pct']) ?>%</div>
		</div>
		<div class="summary-card" style="border-left:3px solid var(--purple);">
			<div class="lbl"><?= h(__('Impostos (ICMS+PIS+COFINS)')) ?></div>
			<div class="val" style="color:var(--purple-dark);"><?= h($H->brl((float)$precificResultado['valor_impostos'])) ?></div>
		</div>
		<div class="summary-card" style="border-left:3px solid var(--teal-dark);">
			<div class="lbl"><?= h(__('Total com impostos')) ?></div>
			<div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)$precificResultado['preco_total_com_impostos'])) ?></div>
		</div>
	</div>

	<div class="alert-box alert-blue">
		<?= h(__('Os cálculos são estimativas. O custo é deduzido do preço atual usando a margem alvo informada, e os impostos são calculados sobre o preço sugerido.')) ?>
	</div>
<?php endif; ?>
