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
 * @var array<int,array<string,mixed>> $reajusteTabelas
 * @var int $reajusteTabelaId
 */
$H = $this->ErpPrototype;
$tabelas = $reajusteTabelas ?? [];
$tabelaAtiva = (int)($reajusteTabelaId ?? 0);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= h(__('Produtos › Tabela de Preços › Reajuste em massa')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📈 <?= h(__('Reajuste em Massa')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Aplique reajuste por % em vários produtos · com pré-visualização')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Cancelar'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos', '?' => $tabelaAtiva > 0 ? ['tabela' => $tabelaAtiva] : []], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="g2" style="gap:14px;align-items:start;">
	<div>
		<?= $this->Form->create(null, ['url' => ['controller' => 'ProdutosPrototype', 'action' => 'reajusteSave'], 'id' => 'pgm-reajuste-form']) ?>
		<?php if ($tabelaAtiva > 0) : ?>
			<input type="hidden" name="tabela_id" value="<?= (int)$tabelaAtiva ?>">
		<?php endif; ?>

		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">🧮 1. <?= h(__('Tipo de reajuste')) ?></div>
			<?php if ($tabelas !== []) : ?>
				<div class="field" style="margin-bottom:10px;">
					<label><?= h(__('Tabela alvo')) ?></label>
					<select id="reajuste-tabela-select" onchange="window.location.href='<?= h($this->Url->build(['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-reajuste-massa'])) ?>?tabela='+this.value+'&pct='+encodeURIComponent(document.getElementById('reajuste-pct-input').value)">
						<?php foreach ($tabelas as $tb) :
							$sel = (int)$tb['id'] === $tabelaAtiva ? ' selected' : '';
						?>
							<option value="<?= (int)$tb['id'] ?>"<?= $sel ?>><?= h((string)$tb['nome']) ?><?= !empty($tb['vigente']) ? ' · ' . h(__('vigente')) : '' ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>
			<div class="g2" style="gap:10px;margin-top:10px;">
				<div class="field" style="margin:0;">
					<label><?= h(__('Percentual aplicado')) ?></label>
					<div style="display:flex;gap:6px;align-items:center;">
						<input type="number" id="reajuste-pct-input" name="pct" value="<?= h((string)$reajustePct) ?>" step="0.1" style="text-align:right;font-size:15px;font-weight:600;"/>
						<span style="font-size:14px;color:var(--text-muted);">%</span>
					</div>
				</div>
				<div class="field" style="margin:0;">
					<label><?= h(__('Margem mínima (trava)')) ?></label>
					<div style="display:flex;gap:6px;align-items:center;">
						<input type="number" id="reajuste-trava-input" value="<?= h((string)$reajusteTrava) ?>" style="text-align:right;" readonly/>
						<span>%</span>
					</div>
				</div>
			</div>
		</div>

		<div class="card" style="margin-bottom:14px;">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
				<div class="sec-title" style="margin:0;">📦 2. <?= h(__('Produtos afetados')) ?></div>
				<div id="reajuste-sel-label" style="font-size:12px;font-weight:600;color:var(--teal-dark);"><?= sprintf('%d %s %d', (int)$reajusteSel, h(__('de')), (int)$reajusteTotal) ?> <?= h(__('selecionados')) ?></div>
			</div>
			<div id="reajuste-lista" style="display:flex;flex-direction:column;gap:4px;max-height:420px;overflow-y:auto;">
				<?php foreach ($reajusteItens as $it) :
					$venda = (float)$it['venda'];
					$custo = (float)$it['custo'];
					$sel = !empty($it['selecionado']);
					$bg = !empty($it['alerta_trava']) ? '#FFFBEB' : ($sel ? 'var(--teal-light)' : '#fff');
				?>
					<label class="reajuste-item" style="display:flex;align-items:center;gap:8px;padding:8px;background:<?= h($bg) ?>;border-radius:6px;cursor:pointer;font-size:12px;border:1px solid var(--border-light);"
						data-venda="<?= h(number_format($venda, 2, '.', '')) ?>"
						data-custo="<?= h(number_format($custo, 2, '.', '')) ?>"
						data-excluido="<?= !empty($it['excluido']) ? '1' : '0' ?>">
						<?php if ($sel) : ?>
							<input type="checkbox" name="produto_ids[]" value="<?= (int)$it['id'] ?>" class="reajuste-cb" checked/>
						<?php else : ?>
							<input type="checkbox" class="reajuste-cb" disabled/>
						<?php endif; ?>
						<span style="flex:1;"><?= h((string)$it['codigo']) ?> · <?= h(\Cake\Utility\Text::truncate((string)$it['descricao'], 36)) ?></span>
						<span class="reajuste-preco-lbl" style="color:var(--text-muted);">
							<?php if (!empty($it['excluido'])) : ?>
								<?= h(__('excluída')) ?>
							<?php else : ?>
								<?= h($H->brl($venda)) ?> → <strong class="reajuste-novo" style="color:var(--teal-dark);"><?= h($H->brl((float)$it['novo_preco'])) ?></strong>
								<?php if (!empty($it['alerta_trava'])) : ?> ⚠<?php endif; ?>
							<?php endif; ?>
						</span>
					</label>
				<?php endforeach; ?>
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
				<div style="display:flex;justify-content:space-between;"><span><?= h(__('Produtos afetados:')) ?></span><strong id="impacto-afetados"><?= (int)$reajusteSel ?></strong></div>
				<div style="display:flex;justify-content:space-between;"><span><?= h(__('Reajuste médio:')) ?></span><strong id="impacto-pct" style="color:var(--teal-dark);">+<?= h((string)$reajustePct) ?>%</strong></div>
				<div style="display:flex;justify-content:space-between;"><span><?= h(__('Margem média:')) ?></span><strong id="impacto-margem"><?= $reajusteMargemAntes !== null ? h((string)$reajusteMargemAntes) . '%' : '—' ?> → <?= $reajusteMargemDepois !== null ? h((string)$reajusteMargemDepois) . '%' : '—' ?></strong></div>
			</div>
			<div id="impacto-alerta" style="padding:10px;background:#FFFBEB;border-radius:6px;border-left:3px solid var(--amber);font-size:11px;color:#8A4D02;<?= $reajusteAbaixoTrava > 0 ? '' : 'display:none;' ?>">
				⚠ <strong id="impacto-alerta-n"><?= (int)$reajusteAbaixoTrava ?></strong> <?= h(__('produto(s) ficariam abaixo da margem mínima — revise manualmente.')) ?>
			</div>
		</div>
	</div>
</div>
<script>
(function () {
	var pctInput = document.getElementById('reajuste-pct-input');
	var trava = parseFloat(document.getElementById('reajuste-trava-input').value) || 25;
	if (!pctInput) return;

	function brl(v) {
		return 'R$ ' + Number(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
	}
	function margem(venda, custo) {
		if (venda <= 0) return null;
		if (custo <= 0) return 100;
		return Math.round((1 - (custo / venda)) * 100);
	}
	function recalc() {
		var pct = parseFloat(pctInput.value) || 0;
		var sel = 0;
		var abaixo = 0;
		var margensAntes = [];
		var margensDepois = [];
		document.querySelectorAll('.reajuste-item').forEach(function (row) {
			if (row.getAttribute('data-excluido') === '1') return;
			var cb = row.querySelector('.reajuste-cb');
			if (!cb || !cb.checked) return;
			sel++;
			var venda = parseFloat(row.getAttribute('data-venda')) || 0;
			var custo = parseFloat(row.getAttribute('data-custo')) || 0;
			var novo = Math.round(venda * (1 + pct / 100) * 100) / 100;
			var m0 = margem(venda, custo);
			var m1 = margem(novo, custo);
			if (m0 !== null) margensAntes.push(m0);
			if (m1 !== null) margensDepois.push(m1);
			var alerta = m1 !== null && m1 < trava;
			if (alerta) abaixo++;
			row.style.background = alerta ? '#FFFBEB' : 'var(--teal-light)';
			var lbl = row.querySelector('.reajuste-preco-lbl');
			if (lbl) {
				lbl.innerHTML = brl(venda) + ' → <strong class="reajuste-novo" style="color:var(--teal-dark);">' + brl(novo) + '</strong>' + (alerta ? ' ⚠' : '');
			}
		});
		var total = document.querySelectorAll('.reajuste-item[data-excluido="0"]').length;
		var selLbl = document.getElementById('reajuste-sel-label');
		if (selLbl) selLbl.textContent = sel + ' de ' + total + ' selecionados';
		var impA = document.getElementById('impacto-afetados');
		if (impA) impA.textContent = String(sel);
		var impP = document.getElementById('impacto-pct');
		if (impP) impP.textContent = '+' + pct.toFixed(1).replace('.', ',') + '%';
		function media(arr) {
			return arr.length ? Math.round(arr.reduce(function (a, b) { return a + b; }, 0) / arr.length) : null;
		}
		var ma = media(margensAntes);
		var md = media(margensDepois);
		var impM = document.getElementById('impacto-margem');
		if (impM) impM.textContent = (ma !== null ? ma + '%' : '—') + ' → ' + (md !== null ? md + '%' : '—');
		var alertaBox = document.getElementById('impacto-alerta');
		var alertaN = document.getElementById('impacto-alerta-n');
		if (alertaBox && alertaN) {
			alertaN.textContent = String(abaixo);
			alertaBox.style.display = abaixo > 0 ? 'block' : 'none';
		}
	}
	pctInput.addEventListener('input', recalc);
	recalc();
})();
</script>
