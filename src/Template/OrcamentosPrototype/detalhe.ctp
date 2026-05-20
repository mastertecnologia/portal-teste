<?php
/**
 * Orçamento · detalhe — mockup pg-revisao com dados reais.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $orc
 * @var array<int,array<string,mixed>> $orcLinhas
 * @var float $orcTotalSub
 * @var float $orcTotalDesc
 */
$H = $this->ErpPrototype;
$statusLabels = [0 => __('Pendente'), 1 => __('Enviado'), 2 => __('Aprovado'), 3 => __('Recusado'), 4 => __('Arquivado')];
$statusBadges = [0 => 'pendente', 1 => 'env', 2 => 'aprov', 3 => 'recus', 4 => 'arq'];
$st = (int)($orc['status'] ?? 0);
$valorFinal = (float)$orc['valortotal'];
if ($valorFinal <= 0 && $orcLinhas !== []) {
	$valorFinal = $orcTotalSub - $orcTotalDesc;
}
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Comercial · Orçamento')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">
			📄 <?= h(sprintf('ORC-%04d', (int)$orc['id'])) ?> · <?= h((string)$orc['cliente']) ?>
		</h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('Criado por %s em %s')), h((string)$orc['autor']), h($H->dt($orc['created'], 'd/m/Y H:i'))) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Voltar à lista'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('🖨 ' . __('PDF'), ['controller' => 'Orcamentos', 'action' => 'imprimirPdf', (int)$orc['id']], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('✍ ' . __('Editar (clássico)'), ['controller' => 'Orcamentos', 'action' => 'edit', (int)$orc['id']], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<?php
$urlStatus = $this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'mudarStatus']);
$csrf2 = (string)$this->request->getAttribute('csrfToken');
$proximos = [
	0 => [['s' => 1, 'lbl' => '📨 ' . __('Marcar como Enviado'), 'cls' => 'btn-primary'], ['s' => 3, 'lbl' => '✗ ' . __('Recusar'), 'cls' => 'btn-red']],
	1 => [['s' => 2, 'lbl' => '✓ ' . __('Aprovar'), 'cls' => 'btn-primary'], ['s' => 3, 'lbl' => '✗ ' . __('Recusar'), 'cls' => 'btn-red'], ['s' => 0, 'lbl' => '↩ ' . __('Voltar a Pendente'), 'cls' => 'btn-ghost']],
	2 => [['s' => 4, 'lbl' => '📦 ' . __('Arquivar'), 'cls' => 'btn-ghost'], ['s' => 0, 'lbl' => '↩ ' . __('Reabrir'), 'cls' => 'btn-ghost']],
	3 => [['s' => 0, 'lbl' => '↩ ' . __('Reabrir'), 'cls' => 'btn-primary']],
	4 => [['s' => 0, 'lbl' => '↩ ' . __('Reabrir'), 'cls' => 'btn-primary']],
];
$opts = (array)($proximos[$st] ?? []);
?>
<?php if ($opts !== []) : ?>
	<div class="card" style="background:var(--bg-surface);padding:14px 18px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
		<strong style="font-size:12px;color:var(--text-muted);"><?= h(__('Próximas ações:')) ?></strong>
		<?php foreach ($opts as $o) : ?>
			<form method="post" action="<?= h($urlStatus) ?>" style="margin:0;display:inline;" onsubmit="return confirm('<?= h(__('Confirmar mudança?')) ?>')">
				<input type="hidden" name="_csrfToken" value="<?= h($csrf2) ?>">
				<input type="hidden" name="orcamento_id" value="<?= (int)$orc['id'] ?>">
				<input type="hidden" name="novo_status" value="<?= (int)$o['s'] ?>">
				<button type="submit" class="btn <?= h((string)$o['cls']) ?> btn-sm"><?= $o['lbl'] ?></button>
			</form>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Status')) ?></div><div class="val"><?= $H->badge($statusLabels[$st] ?? '—', $statusBadges[$st] ?? 'arq') ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('Itens')) ?></div><div class="val" style="color:#0C447C;"><?= count($orcLinhas) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Subtotal')) ?></div><div class="val" style="color:#8A4D02;"><?= h($H->brl($orcTotalSub)) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--teal-dark);"><div class="lbl"><?= h(__('Total final')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl($valorFinal)) ?></div></div>
</div>

<div class="g2">
	<div class="card">
		<div class="sec-title"><?= h(__('Cliente')) ?></div>
		<div style="font-size:13px;line-height:1.8;">
			<div><strong><?= h((string)$orc['cliente']) ?></strong></div>
			<?php if (!empty($orc['cliente_cnpj'])) : ?>
				<div style="font-family:monospace;color:var(--text-muted);font-size:11px;"><?= h((string)$orc['cliente_cnpj']) ?></div>
			<?php endif; ?>
		</div>
	</div>
	<div class="card">
		<div class="sec-title"><?= h(__('Observações')) ?></div>
		<div style="font-size:12px;color:var(--text-muted);line-height:1.6;white-space:pre-wrap;">
			<?= !empty($orc['observacao']) ? h((string)$orc['observacao']) : h(__('Sem observações.')) ?>
		</div>
	</div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
		<strong style="font-size:13px;"><?= h(__('Itens do orçamento')) ?></strong>
		<button type="button" class="btn btn-primary btn-xs" onclick="document.getElementById('orcNovoItem').style.display = document.getElementById('orcNovoItem').style.display==='none'?'block':'none'">+ <?= h(__('Adicionar item')) ?></button>
	</div>
	<div id="orcNovoItem" style="display:none;padding:12px 14px;background:#FAFAF9;border-bottom:1px solid var(--border-light);">
		<div style="display:grid;grid-template-columns:90px 2fr 70px 80px 110px auto;gap:8px;align-items:end;">
			<div class="field"><label><?= h(__('Código')) ?></label><input type="text" id="orcItemCod" placeholder="—"></div>
			<div class="field"><label><?= h(__('Descrição')) ?></label><input type="text" id="orcItemDesc" placeholder="<?= h(__('Produto/serviço...')) ?>"></div>
			<div class="field"><label><?= h(__('Un')) ?></label><input type="text" id="orcItemUn" value="un"></div>
			<div class="field"><label><?= h(__('Qtd')) ?></label><input type="number" step="0.01" min="0.01" id="orcItemQtd" value="1"></div>
			<div class="field"><label><?= h(__('Vlr unit. R$')) ?></label><input type="number" step="0.01" min="0" id="orcItemVlr" value="0"></div>
			<button type="button" id="orcItemBtn" class="btn btn-primary btn-sm">💾 <?= h(__('Salvar')) ?></button>
		</div>
		<div id="orcItemMsg" style="font-size:11px;margin-top:6px;"></div>
	</div>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Código')) ?></th>
					<th><?= h(__('Descrição')) ?></th>
					<th><?= h(__('Un')) ?></th>
					<th class="r"><?= h(__('Qtd')) ?></th>
					<th class="r"><?= h(__('Unit.')) ?></th>
					<th class="r"><?= h(__('Desc.')) ?></th>
					<th class="r"><?= h(__('Subtotal')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($orcLinhas === []) : ?>
					<tr><td colspan="8" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Sem itens neste orçamento.')) ?></td></tr>
				<?php else : foreach ($orcLinhas as $l) :
					$lid = (int)($l['id'] ?? 0);
				?>
					<tr data-orc-row="<?= $lid ?>">
						<td style="font-family:monospace;font-size:11px;"><?= h((string)$l['codigo']) ?></td>
						<td><?= h(\Cake\Utility\Text::truncate((string)$l['descricao'], 80, ['ellipsis' => '…'])) ?></td>
						<td class="mu"><?= h((string)$l['unidade']) ?></td>
						<td class="r">
							<?php if ($lid > 0) : ?>
								<input type="number" step="0.01" min="0.01" data-orc-qtd="<?= $lid ?>" value="<?= h(number_format((float)$l['qtd'], 2, '.', '')) ?>" style="width:78px;padding:4px 6px;border:1px solid var(--border);border-radius:4px;font-size:11px;text-align:right;">
							<?php else : ?>
								<?= number_format((float)$l['qtd'], 2, ',', '.') ?>
							<?php endif; ?>
						</td>
						<td class="r">
							<?php if ($lid > 0) : ?>
								<input type="number" step="0.01" min="0" data-orc-vlr="<?= $lid ?>" value="<?= h(number_format((float)$l['vlr'], 2, '.', '')) ?>" style="width:92px;padding:4px 6px;border:1px solid var(--border);border-radius:4px;font-size:11px;text-align:right;">
							<?php else : ?>
								<?= h($H->brl((float)$l['vlr'])) ?>
							<?php endif; ?>
						</td>
						<td class="r" style="color:#7A1822;"><?php if ((float)$l['desconto'] > 0) : ?>−<?= h($H->brl((float)$l['desconto'])) ?><?php else : ?>—<?php endif; ?></td>
						<td class="r"><strong data-orc-subtotal="<?= $lid ?>"><?= h($H->brl((float)$l['subtotal'])) ?></strong></td>
						<td class="r" style="white-space:nowrap;">
							<?php if ($lid > 0) : ?>
								<button type="button" class="btn btn-primary btn-xs" data-orc-save="<?= $lid ?>" title="<?= h(__('Salvar alterações')) ?>">💾</button>
								<button type="button" class="btn btn-ghost btn-xs" data-orc-del="<?= $lid ?>" title="<?= h(__('Excluir item')) ?>">🗑</button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<div class="tot-wrap" style="padding:14px;">
		<div class="tot-inner">
			<div class="tot-l"><span><?= h(__('Subtotal')) ?></span><span><?= h($H->brl($orcTotalSub)) ?></span></div>
			<div class="tot-l"><span><?= h(__('Descontos')) ?></span><span class="rd">−<?= h($H->brl($orcTotalDesc)) ?></span></div>
			<div class="tot-l"><span><?= h(__('Total')) ?></span><span class="g"><?= h($H->brl($valorFinal)) ?></span></div>
		</div>
	</div>
</div>

<?php $this->start('script'); ?>
<script>
(function () {
	var url = <?= json_encode($this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'apiAdicionarItem'])) ?>;
	var orcId = <?= (int)$orc['id'] ?>;
	var csrf = <?= json_encode((string)$this->request->getAttribute('csrfToken')) ?>;
	var btn = document.getElementById('orcItemBtn');
	var msg = document.getElementById('orcItemMsg');
	// Editar item inline (qtd/preço)
	var urlUpd = <?= json_encode($this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'apiAtualizarItem'])) ?>;
	function brl(v) { return 'R$ ' + Number(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
	document.querySelectorAll('[data-orc-save]').forEach(function (el) {
		el.addEventListener('click', function () {
			var id = this.getAttribute('data-orc-save');
			var qInput = document.querySelector('[data-orc-qtd="' + id + '"]');
			var vInput = document.querySelector('[data-orc-vlr="' + id + '"]');
			var q = parseFloat(qInput.value);
			var v = parseFloat(vInput.value) || 0;
			if (!(q > 0)) { alert('Quantidade inválida.'); return; }
			var fd = new FormData();
			fd.append('_csrfToken', csrf);
			fd.append('item_id', id);
			fd.append('quantidade', q);
			fd.append('valor_unitario', v);
			fetch(urlUpd, {method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-CSRF-Token': csrf}})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (!data.ok) { alert('Falha: ' + (data.error || '?')); return; }
					var sub = document.querySelector('[data-orc-subtotal="' + id + '"]');
					if (sub) sub.textContent = brl(data.item.subtotal);
					el.textContent = '✓';
					setTimeout(function () { el.textContent = '💾'; }, 1500);
				});
		});
	});

	// Excluir item via AJAX
	var urlDel = <?= json_encode($this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'apiExcluirItem'])) ?>;
	document.querySelectorAll('[data-orc-del]').forEach(function (el) {
		el.addEventListener('click', function () {
			var id = this.getAttribute('data-orc-del');
			if (!id || !confirm('Excluir item #' + id + '?')) return;
			var fd = new FormData();
			fd.append('_csrfToken', csrf);
			fd.append('item_id', id);
			fetch(urlDel, {method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-CSRF-Token': csrf}})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (!data.ok) { alert('Falha: ' + (data.error || '?')); return; }
					window.location.reload();
				});
		});
	});

	btn && btn.addEventListener('click', function () {
		var cod = document.getElementById('orcItemCod').value.trim();
		var d = document.getElementById('orcItemDesc').value.trim();
		var un = document.getElementById('orcItemUn').value.trim() || 'un';
		var q = parseFloat(document.getElementById('orcItemQtd').value);
		var v = parseFloat(document.getElementById('orcItemVlr').value) || 0;
		if (!d || !(q > 0)) { msg.innerHTML = '<span style="color:#7A1822">Preencha descrição e quantidade.</span>'; return; }
		msg.innerHTML = '⏳ Salvando...';
		var fd = new FormData();
		fd.append('_csrfToken', csrf);
		fd.append('orcamento_id', orcId);
		fd.append('codigo', cod);
		fd.append('descricao', d);
		fd.append('unidade', un);
		fd.append('quantidade', q);
		fd.append('valor_unitario', v);
		fetch(url, {method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-CSRF-Token': csrf}})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (!data.ok) { msg.innerHTML = '<span style="color:#7A1822">' + (data.error || 'Falha.') + '</span>'; return; }
				msg.innerHTML = '<span style="color:#0F6E56">✓ Item adicionado.</span>';
				document.getElementById('orcItemCod').value = '';
				document.getElementById('orcItemDesc').value = '';
				document.getElementById('orcItemQtd').value = '1';
				document.getElementById('orcItemVlr').value = '0';
				setTimeout(function () { window.location.reload(); }, 800);
			})
			.catch(function (e) { msg.innerHTML = '<span style="color:#7A1822">Erro de rede.</span>'; });
	});
})();
</script>
<?php $this->end(); ?>
