<?php
/**
 * Stepper 4 passos — cadastro cliente (mock pg-cliente-novo).
 *
 * @var int|null $wizardStep Passo ativo 1..4
 */
$wizardStep = isset($wizardStep) ? max(1, min(4, (int)$wizardStep)) : 1;
$labels = [
	1 => __('Identificação'),
	2 => __('Endereço & Contato'),
	3 => __('Fiscal & Financeiro'),
	4 => __('Comercial & CRM'),
];
?>
<div class="card cli-wizard-stepper-card" style="margin-bottom:14px;padding:14px 16px;">
<nav class="cli-wizard-stepper" aria-label="<?= h(__('Etapas do cadastro')) ?>" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:0;padding:0;border:none;background:transparent;box-shadow:none;">
	<?php for ($i = 1; $i <= 4; $i++) :
		$done = $i < $wizardStep;
		$active = $i === $wizardStep;
		$cls = 'cli-wiz-stp';
		if ($done) {
			$cls .= ' cli-wiz-stp--done';
		}
		if ($active) {
			$cls .= ' cli-wiz-stp--active';
		}
		$mark = $done ? '✓' : (string)$i;
		$circleStyle = ($active || $done)
			? 'background:var(--teal);color:#fff;border:none;'
			: 'background:var(--gray-100,#f1f5f9);color:var(--text-muted);border:none;';
		$labelStyle = ($active || $done) ? 'font-weight:600;color:var(--text);' : 'font-weight:600;color:var(--text-muted);opacity:.65;';
		$lineStyle = ($i < $wizardStep) ? 'background:var(--teal);' : 'background:var(--border);';
		?>
	<?php if ($i > 1) : ?>
	<span class="cli-wiz-stp-line" aria-hidden="true" style="flex:1;min-width:12px;height:2px;max-width:none;margin:0;<?= h($lineStyle) ?>"></span>
	<?php endif; ?>
	<button type="button" class="<?= h($cls) ?>" data-cli-wizard-goto="<?= $i ?>" aria-current="<?= $active ? 'step' : 'false' ?>" style="display:flex;align-items:center;gap:8px;border:none;background:transparent;padding:0;cursor:pointer;font:inherit;">
		<span class="cli-wiz-stp-c" style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;<?= h($circleStyle) ?>"><?= h($mark) ?></span>
		<span class="cli-wiz-stp-l" style="font-size:12px;white-space:nowrap;<?= h($labelStyle) ?>"><?= h($labels[$i]) ?></span>
	</button>
	<?php endfor; ?>
</nav>
</div>
