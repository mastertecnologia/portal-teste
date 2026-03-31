<?php
$orcModo = $orcModo ?? 'add';
$isAdd = ($orcModo === 'add');
$role = isset($role) ? (int)$role : 0;
$showItemForm = $isAdd
	|| ($orcamento !== null
		&& (int)$orcamento->get('status') !== (int)C_OrcamentoStatusAprovado
		&& $role === 0);
?>
<?php if ($showItemForm) : ?>
	<div class="orc-discount-row" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px;padding:10px 14px;background:var(--orc-bg-surface,#f9f9f8);border-radius:8px;border:1px solid var(--orc-border-light,#f0efec);">
		<span style="font-size:11px;color:var(--orc-text-muted,#6b6a65);">Desconto:</span>
		<input type="number" id="disc-val" value="0" min="0" step="0.01" style="width:70px;padding:5px 8px;border:1px solid #e5e4e0;border-radius:6px;font-size:12px;text-align:right;" />
		<select id="disc-tipo" style="padding:5px 8px;border:1px solid #e5e4e0;border-radius:6px;font-size:12px;">
			<option value="pct">%</option>
			<option value="fix">R$</option>
		</select>
		<span style="font-size:11px;color:var(--orc-text-muted,#6b6a65);">| Desconto aplicado:</span>
		<span style="font-size:12px;font-weight:600;color:#E24B4A;" id="disc-show">R$ 0,00</span>
	</div>

	<div class="orc-tot-wrap">
		<div class="orc-tot-inner">
			<div class="orc-tot-l"><span>Subtotal</span><span id="t-sub">R$ 0,00</span></div>
			<div class="orc-tot-l"><span>Custo total</span><span id="t-cus" style="color:#6b6a65;">R$ 0,00</span></div>
			<div class="orc-tot-l"><span>Desconto</span><span class="orc-tot-rd" id="t-disc">— R$ 0,00</span></div>
			<div class="orc-tot-l"><span>Margem após desconto</span><span id="t-marg" style="color:#00c08b;">0%</span></div>
			<div class="orc-tot-l"><span>Total geral</span><span class="orc-tot-g" id="t-tot">R$ 0,00</span></div>
		</div>
	</div>
<?php endif; ?>
