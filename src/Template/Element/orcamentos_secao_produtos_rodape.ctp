<?php
$orcModo = $orcModo ?? 'add';
$isAdd = ($orcModo === 'add');
$isDados = ($orcModo === 'dados');
$role = isset($role) ? (int)$role : 0;
$showItemForm = $isAdd
	|| $isDados
	|| ($orcamento !== null
		&& (int)$orcamento->get('status') !== (int)C_OrcamentoStatusAprovado
		&& $role === 0);
$discValor = 0.0;
$discTipo = 'pct';
if (!$isAdd && isset($orcamento)) {
	$discValor = (float)($orcamento->desconto_valor ?? 0);
	$discTipo = (string)($orcamento->desconto_tipo ?? 'pct');
	if (!in_array($discTipo, ['pct', 'fix'], true)) {
		$discTipo = 'pct';
	}
}
?>
<?php if ($showItemForm) : ?>
	<div class="orc-discount-row">
		<span class="orc-discount-lbl">Desconto no total:</span>
		<input type="number" id="disc-val" class="orc-discount-inp" value="<?= h((string)$discValor) ?>" min="0" step="0.01" />
		<select id="disc-tipo" class="orc-discount-select">
			<option value="pct"<?= $discTipo === 'pct' ? ' selected' : '' ?>>%</option>
			<option value="fix"<?= $discTipo === 'fix' ? ' selected' : '' ?>>R$</option>
		</select>
		<span class="orc-discount-lbl">| Desconto aplicado:</span>
		<span class="orc-discount-applied" id="disc-show">R$ 0,00</span>
	</div>

	<div class="orc-tot-wrap">
		<div class="orc-tot-inner">
			<div class="orc-tot-l"><span>Subtotal</span><span id="t-sub">R$ 0,00</span></div>
			<div class="orc-tot-l"><span>Custo total</span><span class="orc-tot-val--muted" id="t-cus">R$ 0,00</span></div>
			<div class="orc-tot-l"><span>Desconto</span><span class="orc-tot-rd" id="t-disc">— R$ 0,00</span></div>
			<div class="orc-tot-l"><span>Margem após desconto</span><span class="orc-marg-pct--warn" id="t-marg">0%</span></div>
			<div class="orc-tot-l"><span>Total geral</span><span class="orc-tot-g" id="t-tot">R$ 0,00</span></div>
		</div>
	</div>
<?php endif; ?>
