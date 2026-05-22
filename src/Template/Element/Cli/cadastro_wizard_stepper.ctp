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
<nav class="cli-wizard-stepper" aria-label="<?= h(__('Etapas do cadastro')) ?>">
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
		?>
	<button type="button" class="<?= h($cls) ?>" data-cli-wizard-goto="<?= $i ?>" aria-current="<?= $active ? 'step' : 'false' ?>">
		<span class="cli-wiz-stp-c"><?= h($mark) ?></span>
		<span class="cli-wiz-stp-l"><?= h($labels[$i]) ?></span>
	</button>
	<?php if ($i < 4) : ?><span class="cli-wiz-stp-line" aria-hidden="true"></span><?php endif; ?>
	<?php endfor; ?>
</nav>
