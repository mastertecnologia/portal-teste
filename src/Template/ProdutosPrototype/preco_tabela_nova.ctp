<?php
/**
 * Nova tabela de preços — pg-preco-tabela-nova.
 *
 * @var int $novaTotalProdutos
 * @var array<int,array<string,mixed>> $novaSimulacao
 * @var float|null $novaMargemMedia
 */
$H = $this->ErpPrototype;
$abaixo = 0;
foreach ($novaSimulacao as $s) {
	if (!empty($s['alerta'])) {
		$abaixo++;
	}
}
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= h(__('Produtos › Tabela de Preços › Nova')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📋 <?= h(__('Nova Tabela de Preços')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Crie uma tabela personalizada · base, regra de cálculo, vínculo a clientes/segmentos')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Cancelar'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<button type="button" class="btn btn-primary btn-sm" onclick="alert('<?= h(__('Em breve: persistência de tabelas múltiplas no banco.')) ?>')">💾 <?= h(__('Criar tabela')) ?></button>
	</div>
</div>

<div class="g2" style="gap:14px;align-items:start;">
	<div>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">📋 1. <?= h(__('Identificação')) ?></div>
			<div class="field"><label><?= h(__('Nome da tabela *')) ?></label><input type="text" placeholder="<?= h(__('Ex: Tabela Distribuidor 2026 · Black Friday · Cliente VIP')) ?>"/></div>
			<div class="g2" style="gap:10px;">
				<div class="field" style="margin:0;"><label><?= h(__('Código')) ?></label><input type="text" placeholder="<?= h(__('auto-gerado')) ?>" style="font-family:monospace;" readonly/></div>
				<div class="field" style="margin:0;"><label><?= h(__('Moeda')) ?></label><select><option>🇧🇷 BRL (R$)</option></select></div>
			</div>
			<div class="field"><label><?= h(__('Descrição')) ?></label><textarea rows="2" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;font-size:12px;" placeholder="<?= h(__('Para que serve esta tabela e quando aplicar...')) ?>"></textarea></div>
		</div>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">🎯 2. <?= h(__('Base da tabela')) ?></div>
			<div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;"><?= h(__('De onde partem os preços desta nova tabela?')) ?></div>
			<label style="display:block;padding:12px;background:var(--teal-light);border:1px solid var(--teal-mid);border-radius:8px;margin-bottom:8px;">
				<input type="radio" name="base" checked style="margin-right:6px;"/>
				<strong style="font-size:12px;color:var(--teal-dark);">📋 <?= h(__('Copiar tabela atual')) ?></strong>
				<div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= sprintf(h(__('Parte da Tabela Padrão vigente (%d produtos)')), (int)$novaTotalProdutos) ?></div>
			</label>
		</div>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">🧮 3. <?= h(__('Regra de cálculo')) ?></div>
			<div class="g2" style="gap:10px;">
				<div class="field" style="margin:0;"><label><?= h(__('Tipo de ajuste')) ?></label><select><option><?= h(__('% de desconto sobre a base')) ?></option></select></div>
				<div class="field" style="margin:0;"><label><?= h(__('Percentual')) ?></label><div style="display:flex;gap:6px;align-items:center;"><input type="number" value="10" style="text-align:right;font-weight:600;"/><span>%</span></div></div>
			</div>
		</div>
	</div>
	<div>
		<div class="card" style="position:sticky;top:14px;">
			<div class="sec-title">🧪 <?= h(__('Simulação')) ?></div>
			<div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;"><?= h(__('Prévia do efeito da regra (10% desconto sobre a tabela atual):')) ?></div>
			<?php foreach ($novaSimulacao as $s) : ?>
				<div style="padding:8px;background:<?= !empty($s['alerta']) ? '#FFFBEB' : 'var(--bg-surface)' ?>;border-radius:6px;font-size:11px;margin-bottom:6px;<?= !empty($s['alerta']) ? 'border-left:3px solid var(--amber);' : '' ?>">
					<div><?= h(\Cake\Utility\Text::truncate((string)$s['descricao'], 32)) ?></div>
					<div style="display:flex;justify-content:space-between;color:var(--text-muted);"><span><?= h($H->brl((float)$s['antigo'])) ?></span><span style="color:var(--teal-dark);font-weight:600;">→ <?= h($H->brl((float)$s['novo'])) ?></span></div>
					<?php if (!empty($s['alerta']) && $s['margem'] !== null) : ?>
						<div style="color:#8A4D02;margin-top:2px;">⚠ <?= sprintf(h(__('margem cai para %s%%')), (int)$s['margem']) ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<div style="margin-top:12px;padding:10px;background:var(--teal-light);border-radius:6px;font-size:12px;">
				<div style="display:flex;justify-content:space-between;"><span><?= h(__('Produtos afetados:')) ?></span><strong><?= (int)$novaTotalProdutos ?></strong></div>
				<div style="display:flex;justify-content:space-between;"><span><?= h(__('Margem média resultante:')) ?></span><strong style="color:var(--teal-dark);"><?= $novaMargemMedia !== null ? h((string)$novaMargemMedia) . '%' : '—' ?></strong></div>
				<div style="display:flex;justify-content:space-between;"><span><?= h(__('Abaixo da mínima:')) ?></span><strong style="color:#7A1822;"><?= (int)$abaixo ?> <?= h(__('itens')) ?></strong></div>
			</div>
		</div>
	</div>
</div>
