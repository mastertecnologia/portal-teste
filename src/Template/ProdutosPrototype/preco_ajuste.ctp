<?php
/**
 * Ajuste de preço individual — pg-preco-ajuste.
 *
 * @var array<string,mixed> $produto
 * @var array<int,array<string,mixed>> $historico
 * @var array<int,array<string,mixed>> $ajusteTabelas
 * @var int $ajusteTabelaId
 * @var string $ajusteVigenciaData
 */
$H = $this->ErpPrototype;
$p = $produto;
$venda = (float)$p['venda'];
$custo = (float)$p['custo'];
$margemAtual = $p['margem'];
$pctDefault = 4.0;
$novoDefault = round($venda * (1 + ($pctDefault / 100)), 2);
$tabelas = $ajusteTabelas ?? [];
$tabelaAtiva = (int)($ajusteTabelaId ?? 0);
$vigenciaData = (string)($ajusteVigenciaData ?? date('Y-m-d'));
$precosUrl = ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'];
$histUrl = ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos', '?' => ['q' => (string)$p['codigo']]];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= h(__('Produtos › Tabela de Preços › Ajustar preço')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">💰 <?= sprintf(h(__('Ajustar Preço · %s')), h((string)$p['codigo'])) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h((string)$p['descricao']) ?> · <?= h(__('ajuste com vigência e histórico preservado')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Cancelar'), $precosUrl, ['class' => 'btn btn-ghost btn-sm']) ?>
		<button type="submit" form="pgm-preco-ajuste-form" class="btn btn-primary btn-sm">💾 <?= h(__('Salvar ajuste')) ?></button>
	</div>
</div>

<div class="g2" style="gap:14px;align-items:start;">
	<div>
		<?= $this->Form->create(null, [
			'url' => ['controller' => 'ProdutosPrototype', 'action' => 'precoSave'],
			'id' => 'pgm-preco-ajuste-form',
		]) ?>
		<input type="hidden" name="produto_id" value="<?= (int)$p['id'] ?>">
		<input type="hidden" name="redirect" value="ajuste">
		<input type="hidden" name="metodo" id="ajuste-metodo" value="pct">
		<input type="hidden" name="vlunitario" id="ajuste-vl-hidden" value="<?= h(number_format($novoDefault, 2, '.', '')) ?>">

		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">💰 <?= h(__('Novo preço')) ?></div>
			<div class="g2" style="gap:10px;">
				<div class="field" style="margin:0;">
					<label><?= h(__('Tabela')) ?></label>
					<select name="tabela_id" id="ajuste-tabela">
						<?php foreach ($tabelas as $tb) :
							$tid = (int)$tb['id'];
							$sel = $tid === $tabelaAtiva || ($tabelaAtiva === 0 && $tid === (int)($tabelas[0]['id'] ?? 0));
						?>
							<option value="<?= $tid ?>" data-venda="<?= h(number_format((float)$tb['venda'], 2, '.', '')) ?>"<?= $sel ? ' selected' : '' ?>>
								<?= h((string)$tb['nome']) ?> (<?= sprintf(h(__('atual: %s')), h($H->brl((float)$tb['venda']))) ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="field" style="margin:0;">
					<label><?= h(__('Método')) ?></label>
					<select id="ajuste-metodo-ui">
						<option value="pct"><?= h(__('% sobre o atual')) ?></option>
						<option value="valor"><?= h(__('Valor final direto')) ?></option>
					</select>
				</div>
			</div>
			<div class="field" id="ajuste-pct">
				<label><?= h(__('Reajuste percentual')) ?></label>
				<div style="display:flex;gap:6px;align-items:center;">
					<input type="number" id="ajuste-pct-input" name="reajuste_pct" value="<?= h((string)$pctDefault) ?>" step="0.5" style="text-align:right;font-size:16px;font-weight:600;"/>
					<span style="font-size:14px;color:var(--text-muted);">%</span>
				</div>
			</div>
			<div class="field" id="ajuste-val" style="display:none;">
				<label><?= h(__('Novo valor final')) ?></label>
				<input type="text" id="ajuste-val-input" value="<?= h(number_format($novoDefault, 2, ',', '.')) ?>" style="text-align:right;font-family:monospace;font-size:16px;font-weight:600;"/>
			</div>
			<div style="padding:12px;background:var(--teal-light);border-radius:8px;margin-top:8px;" id="ajuste-preview">
				<div style="display:flex;justify-content:space-between;font-size:13px;"><span><?= h(__('De:')) ?></span><span id="ajuste-de" style="text-decoration:line-through;color:var(--text-muted);"><?= h($H->brl($venda)) ?></span></div>
				<div style="display:flex;justify-content:space-between;font-size:15px;font-weight:600;"><span><?= h(__('Para:')) ?></span><span id="ajuste-para" style="color:var(--teal-dark);"><?= h($H->brl($novoDefault)) ?> (+<?= (int)$pctDefault ?>%)</span></div>
				<div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-top:4px;"><span><?= h(__('Margem resultante:')) ?></span><span id="ajuste-margem"><?php if ($margemAtual !== null) : ?><?= (int)$margemAtual ?>% → <?php endif; ?>—</span></div>
			</div>
		</div>

		<div class="card">
			<div class="sec-title">📅 <?= h(__('Vigência & motivo')) ?></div>
			<div class="g2" style="gap:10px;">
				<div class="field" style="margin:0;">
					<label><?= h(__('Vigência')) ?></label>
					<select name="vigencia_tipo" id="ajuste-vigencia-tipo">
						<option value="imediata"><?= h(__('Imediata')) ?></option>
						<option value="futura"><?= h(__('Data futura')) ?></option>
					</select>
				</div>
				<div class="field" style="margin:0;">
					<label><?= h(__('A partir de')) ?></label>
					<input type="date" name="vigencia_data" id="ajuste-vigencia-data" value="<?= h($vigenciaData) ?>"/>
				</div>
			</div>
			<div class="field"><label><?= h(__('Motivo (histórico)')) ?> *</label><input type="text" name="motivo" required placeholder="<?= h(__('Ex: ajuste de margem · negociação fornecedor')) ?>"/></div>
		</div>
		<?= $this->Form->end() ?>
	</div>

	<div>
		<div class="card" style="position:sticky;top:14px;">
			<div class="sec-title">📜 <?= h(__('Histórico deste produto')) ?></div>
			<div style="display:flex;flex-direction:column;gap:6px;font-size:11px;">
				<?php foreach ($historico as $h) :
					$border = (string)($h['border'] ?? 'var(--teal)');
					$pctColor = (string)($h['pct_color'] ?? 'var(--text-muted)');
				?>
					<div style="padding:8px;background:var(--bg-surface);border-radius:6px;border-left:2px solid <?= h($border) ?>;">
						<div style="display:flex;justify-content:space-between;">
							<strong><?= h((string)$h['dia']) ?></strong>
							<?php if (!empty($h['pct'])) : ?>
								<span style="color:<?= h($pctColor) ?>;"><?= h((string)$h['pct']) ?></span>
							<?php endif; ?>
						</div>
						<div style="color:var(--text-muted);">
							<?php if ($h['de'] !== null) : ?>
								<?= h($H->brl((float)$h['de'])) ?> → <?= h($H->brl((float)$h['para'])) ?>
							<?php else : ?>
								<?= h($H->brl((float)$h['para'])) ?>
							<?php endif; ?>
							<?php if (!empty($h['motivo'])) : ?> · <?= h((string)$h['motivo']) ?><?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?= $this->Html->link(__('Ver histórico completo →'), $histUrl, ['class' => 'btn btn-ghost btn-xs', 'style' => 'margin-top:4px;']) ?>
		</div>
	</div>
</div>
<script>
(function () {
	var custo = <?= json_encode($custo) ?>;
	var tabela = document.getElementById('ajuste-tabela');
	var metodoUi = document.getElementById('ajuste-metodo-ui');
	var metodoHidden = document.getElementById('ajuste-metodo');
	var pctBox = document.getElementById('ajuste-pct');
	var valBox = document.getElementById('ajuste-val');
	var pctInput = document.getElementById('ajuste-pct-input');
	var valInput = document.getElementById('ajuste-val-input');
	var vlHidden = document.getElementById('ajuste-vl-hidden');
	var deEl = document.getElementById('ajuste-de');
	var paraEl = document.getElementById('ajuste-para');
	var margemEl = document.getElementById('ajuste-margem');
	var vigTipo = document.getElementById('ajuste-vigencia-tipo');
	var vigData = document.getElementById('ajuste-vigencia-data');

	function brl(v) {
		return 'R$ ' + Number(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
	}
	function margem(venda) {
		if (venda <= 0) return null;
		if (custo <= 0) return 100;
		return Math.round((1 - (custo / venda)) * 100);
	}
	function parseBrl(s) {
		var t = String(s || '').replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.');
		return parseFloat(t) || 0;
	}
	function vendaAtual() {
		if (!tabela || !tabela.options.length) return <?= json_encode($venda) ?>;
		var opt = tabela.options[tabela.selectedIndex];
		return parseFloat(opt.getAttribute('data-venda') || '0') || 0;
	}
	function atualizarPreview() {
		var de = vendaAtual();
		var para = de;
		var pctLabel = '';
		if (metodoUi.value === 'valor') {
			para = parseBrl(valInput.value);
		} else {
			var pct = parseFloat(pctInput.value) || 0;
			para = Math.round(de * (1 + pct / 100) * 100) / 100;
			pctLabel = ' (+' + pct.toFixed(1).replace('.', ',') + '%)';
		}
		vlHidden.value = para.toFixed(2);
		deEl.textContent = brl(de);
		paraEl.textContent = brl(para) + (metodoUi.value === 'pct' ? pctLabel : '');
		var m0 = margem(de);
		var m1 = margem(para);
		if (m0 !== null && m1 !== null) {
			margemEl.textContent = m0 + '% → ' + m1 + '%';
		}
	}
	function toggleMetodo() {
		var valor = metodoUi.value === 'valor';
		metodoHidden.value = metodoUi.value;
		pctBox.style.display = valor ? 'none' : 'block';
		valBox.style.display = valor ? 'block' : 'none';
		atualizarPreview();
	}
	if (tabela) tabela.addEventListener('change', atualizarPreview);
	if (metodoUi) metodoUi.addEventListener('change', toggleMetodo);
	if (pctInput) pctInput.addEventListener('input', atualizarPreview);
	if (valInput) valInput.addEventListener('input', atualizarPreview);
	if (vigTipo && vigData) {
		vigTipo.addEventListener('change', function () {
			vigData.disabled = vigTipo.value === 'imediata';
			if (vigTipo.value === 'imediata') {
				vigData.value = <?= json_encode($vigenciaData) ?>;
			}
		});
		vigData.disabled = vigTipo.value === 'imediata';
	}
	toggleMetodo();
})();
</script>
