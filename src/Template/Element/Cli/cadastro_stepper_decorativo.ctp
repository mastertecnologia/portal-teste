<?php
/**
 * Stepper decorativo — paridade pg-cliente-novo (passos 1–2 ativos, 3–4 inativos).
 */
?>
<div class="card" style="margin-bottom:14px;padding:14px 16px;">
	<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
		<div style="display:flex;align-items:center;gap:8px;">
			<div style="width:28px;height:28px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">1</div>
			<span style="font-size:12px;font-weight:600;"><?= h(__('Identificação')) ?></span>
		</div>
		<div style="flex:1;min-width:12px;height:2px;background:var(--teal);"></div>
		<div style="display:flex;align-items:center;gap:8px;">
			<div style="width:28px;height:28px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">2</div>
			<span style="font-size:12px;font-weight:600;"><?= h(__('Endereço & Contato')) ?></span>
		</div>
		<div style="flex:1;min-width:12px;height:2px;background:var(--border);"></div>
		<div style="display:flex;align-items:center;gap:8px;opacity:.55;">
			<div style="width:28px;height:28px;border-radius:50%;background:var(--gray-100,#f1f5f9);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">3</div>
			<span style="font-size:12px;font-weight:600;"><?= h(__('Fiscal & Financeiro')) ?></span>
		</div>
		<div style="flex:1;min-width:12px;height:2px;background:var(--border);"></div>
		<div style="display:flex;align-items:center;gap:8px;opacity:.55;">
			<div style="width:28px;height:28px;border-radius:50%;background:var(--gray-100,#f1f5f9);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">4</div>
			<span style="font-size:12px;font-weight:600;"><?= h(__('Comercial & CRM')) ?></span>
		</div>
	</div>
</div>
