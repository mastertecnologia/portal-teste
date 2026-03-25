<?php
/**
 * Stepper 6 passos (protótipo pgm_orcamentos_premium.html).
 * @var int $orcStepperStep Passo ativo 1..6 (anteriores = done).
 */
$step = isset($orcStepperStep) ? max(1, min(6, (int)$orcStepperStep)) : 1;
$labels = [
	1 => 'Dados',
	2 => 'Itens',
	3 => 'Aprovação',
	4 => 'Revisão',
	5 => 'Impressão',
	6 => 'Envio & Assinatura',
];
?>
<div class="orc-stepper" role="navigation" aria-label="Etapas do orçamento">
	<?php for ($i = 1; $i <= 6; $i++) :
		$isDone = $i < $step;
		$isActive = $i === $step;
		$cls = 'orc-stp';
		if ($isDone) {
			$cls .= ' done';
		}
		if ($isActive) {
			$cls .= ' active';
		}
		$mark = $isDone ? '✓' : (string)$i;
		?>
		<div class="<?= h($cls) ?>">
			<div class="orc-stp-c" aria-current="<?= $isActive ? 'step' : 'false' ?>"><?= h($mark) ?></div>
			<div class="orc-stp-l"><?= h($labels[$i]) ?></div>
			<?php if ($i < 6) : ?>
				<div class="orc-stp-line"></div>
			<?php endif; ?>
		</div>
	<?php endfor; ?>
</div>
