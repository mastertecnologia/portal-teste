<?php
/**
 * Rodapé do fluxo proposta (add/dados) — alinhado a .footer-bar em pgm_orcamentos_premium.html.
 *
 * @var array|string $cancelUrl URL do link Cancelar
 * @var string $submitLabel Texto do botão principal
 * @var bool $showLimpar Exibe botão Limpar (somente novo orçamento)
 */
$showLimpar = !empty($showLimpar);
$submitLabel = $submitLabel ?? 'Avançar →';
$cancelTurbo = isset($cancelTurbo) ? (bool)$cancelTurbo : false;
$cancelOpts = ['class' => 'btn btn-orc-form-secondary', 'escape' => false];
if ($cancelTurbo) {
	$cancelOpts['data-turbo'] = 'false';
}
?>
<div class="orc-footer-bar orc-footer-bar--proposta">
	<?php if ($showLimpar) : ?>
		<button type="button" class="btn btn-orc-outline-danger" id="btn-orc-limpar-novo">
			<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" aria-hidden="true">
				<polyline points="3 6 4 14 12 14 13 6"/>
				<path d="M1 6h14M6 6V3h4v3"/>
			</svg>
			Limpar
		</button>
	<?php endif; ?>
	<div class="orc-footer-bar-actions">
		<?= $this->Html->link('Cancelar', $cancelUrl, $cancelOpts) ?>
		<?= $this->Form->button($submitLabel, [
			'type' => 'submit',
			'class' => 'btn btn-orc-premium-primary',
			'escape' => false,
		]) ?>
	</div>
</div>
