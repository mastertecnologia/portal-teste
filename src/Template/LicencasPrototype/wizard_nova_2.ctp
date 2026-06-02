<?php
/**
 * Wizard 2/4 — Quantidade & Datas.
 *
 * @var array<string,mixed>|null $lic
 * @var int $licId
 */
$csrf = (string)$this->request->getAttribute('csrfToken');
$row = (array)($lic ?? []);
$ini = $row['inicio'] ?? '';
$fim = $row['fim'] ?? '';
if (is_object($ini) && method_exists($ini, 'format')) {
	$ini = $ini->format('Y-m-d');
}
if (is_object($fim) && method_exists($fim, 'format')) {
	$fim = $fim->format('Y-m-d');
}
if ((string)$ini === '') {
	$ini = date('Y-m-d');
}
if ((string)$fim === '') {
	$fim = date('Y-m-d', strtotime('+1 year'));
}
$valor = $row['valor_anual'] ?? '';
$valorNum = is_numeric($valor) ? (float)$valor : 0.0;
$assentos = max(1, (int)($row['assentos'] ?? 1));
$valorPorAssento = $assentos > 0 && $valorNum > 0 ? $valorNum / $assentos : 0.0;
$modelo = (string)($row['modelo'] ?? 'assinatura');
?>
<?= $this->element('LicencasPrototype/wizard_header', ['wizardStepNum' => 2, 'wizardSteps' => $wizardSteps ?? [], 'licId' => (int)$licId]) ?>
<p style="font-size:12px;color:var(--text-muted);margin:-8px 0 14px;"><?= h(__('Passo 2 de 4 · Quantidade, datas e valores')) ?> · <strong><?= h($row['codigo'] ?? '') ?></strong></p>

<form id="lic-wizard-form" method="post" action="<?= h($this->Url->build(['action' => 'salvarWizard'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<input type="hidden" name="wizard_step" value="2">
<input type="hidden" name="id" value="<?= (int)$licId ?>">

<div class="g2" style="gap:14px;align-items:start;">
	<div>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">📦 <?= h(__('Quantidade & Modelo de Licenciamento')) ?></div>
			<div class="g2" style="gap:10px;">
				<div class="field" style="margin:0;">
					<label><?= h(__('Quantidade de assentos')) ?> *</label>
					<input type="number" name="assentos" value="<?= $assentos ?>" min="1" max="9999" style="text-align:right;font-size:16px;font-weight:600;" id="lic-wiz-assentos">
				</div>
				<div class="field" style="margin:0;">
					<label><?= h(__('Unidade da licença')) ?></label>
					<select disabled style="background:var(--bg-surface);"><option><?= h(__('usuário')) ?></option></select>
				</div>
			</div>
			<div class="field" style="margin-top:10px;">
				<label><?= h(__('Modelo de licenciamento')) ?></label>
				<select name="modelo">
					<option value="assinatura"<?= $modelo === 'assinatura' ? ' selected' : '' ?>><?= h(__('Assinatura / anual')) ?></option>
					<option value="perpetua"<?= $modelo === 'perpetua' ? ' selected' : '' ?>><?= h(__('Perpétua')) ?></option>
					<option value="trial"<?= $modelo === 'trial' ? ' selected' : '' ?>><?= h(__('Trial')) ?></option>
				</select>
			</div>
		</div>

		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">📅 <?= h(__('Datas')) ?></div>
			<div class="g2" style="gap:10px;">
				<div class="field" style="margin:0;">
					<label><?= h(__('Início da vigência')) ?> *</label>
					<input type="date" name="inicio" value="<?= h((string)$ini) ?>" id="lic-wiz-inicio">
				</div>
				<div class="field" style="margin:0;">
					<label><?= h(__('Vencimento')) ?> *</label>
					<input type="date" name="fim" value="<?= h((string)$fim) ?>" id="lic-wiz-fim">
				</div>
			</div>
		</div>

		<div class="card">
			<div class="sec-title">💰 <?= h(__('Valores')) ?></div>
			<div class="field">
				<label><?= h(__('Valor anual total (R$)')) ?> *</label>
				<input type="text" name="valor_anual" value="<?= h((string)$valor) ?>" placeholder="0,00" id="lic-wiz-valor" style="text-align:right;font-family:monospace;font-size:16px;font-weight:600;">
				<div style="font-size:10px;color:var(--text-muted);margin-top:3px;" id="lic-wiz-valor-hint"></div>
			</div>
		</div>
	</div>

	<div>
		<div class="card" style="position:sticky;top:14px;">
			<div class="sec-title">👁 <?= h(__('Resumo até aqui')) ?></div>
			<div style="font-size:12px;line-height:1.7;">
				<div style="padding:8px;background:var(--bg-surface);border-radius:6px;margin-bottom:6px;">
					<div style="color:var(--text-muted);font-size:10px;"><?= h(__('CLIENTE')) ?></div>
					<strong><?= h($row['cliente'] ?? '—') ?></strong>
				</div>
				<div style="padding:8px;background:var(--teal-light);border-radius:6px;margin-bottom:6px;">
					<div style="color:var(--teal-dark);font-size:10px;"><?= h(__('PRODUTO')) ?></div>
					<strong><?= h($row['produto'] ?? '—') ?></strong>
				</div>
				<div style="padding:8px;background:var(--bg-surface);border-radius:6px;margin-bottom:6px;">
					<div style="color:var(--text-muted);font-size:10px;"><?= h(__('QUANTIDADE')) ?></div>
					<strong id="lic-prev-qtd"><?= h(__('{0} assentos', $assentos)) ?></strong>
				</div>
				<div style="padding:8px;background:var(--bg-surface);border-radius:6px;margin-bottom:6px;">
					<div style="color:var(--text-muted);font-size:10px;"><?= h(__('VIGÊNCIA')) ?></div>
					<strong id="lic-prev-vig"><?= h($this->ErpPrototype->dt($ini) . ' → ' . $this->ErpPrototype->dt($fim)) ?></strong>
				</div>
				<div style="padding:8px;background:#D1FAE5;border-radius:6px;border-left:3px solid #10B981;">
					<div style="color:#065F46;font-size:10px;"><?= h(__('TOTAL ANUAL')) ?></div>
					<strong style="font-size:16px;color:#065F46;" id="lic-prev-total"><?= $this->ErpPrototype->brl($valorNum) ?></strong>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="card" style="margin-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
	<?= $this->Html->link('← ' . __('Voltar (Cliente & Produto)'), ['action' => 'view', 'nova', '?' => ['id' => (int)$licId]], ['class' => 'btn btn-ghost btn-sm']) ?>
	<button type="submit" class="btn btn-primary btn-sm"><?= h(__('Próximo: Atribuir')) ?> →</button>
</div>
</form>
<script>
(function () {
	var $a = document.getElementById('lic-wiz-assentos');
	var $v = document.getElementById('lic-wiz-valor');
	var $i = document.getElementById('lic-wiz-inicio');
	var $f = document.getElementById('lic-wiz-fim');
	function upd() {
		var q = document.getElementById('lic-prev-qtd');
		var vig = document.getElementById('lic-prev-vig');
		var tot = document.getElementById('lic-prev-total');
		if (q && $a) q.textContent = $a.value + ' assentos';
		if (vig && $i && $f) vig.textContent = ($i.value || '—') + ' → ' + ($f.value || '—');
	}
	if ($a) $a.addEventListener('input', upd);
	if ($i) $i.addEventListener('change', upd);
	if ($f) $f.addEventListener('change', upd);
})();
</script>
