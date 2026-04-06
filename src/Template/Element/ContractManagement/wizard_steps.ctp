<?php
/**
 * Wizard leve — novo contrato (Fase 4).
 *
 * @var string $step add|servicos|signatarios|ficha
 * @var int $contractId 0 se ainda sem id (só passo 1)
 * @var bool $podeEditarDadosPasso mostrar link do passo «Dados» → edit (ex.: não-admin em ativo/a vencer)
 */
$step = $step ?? 'add';
$contractId = (int)($contractId ?? 0);
$podeEditarDadosPasso = $podeEditarDadosPasso ?? true;
$order = ['add' => 1, 'servicos' => 2, 'signatarios' => 3, 'ficha' => 4];
$cur = $order[$step] ?? 1;

$labels = [
	1 => __('Dados'),
	2 => __('Serviços'),
	3 => __('Signatários'),
	4 => __('Ficha'),
];
?>
<style>
/* ── Módulo Contratos: corrige btn-default e label-default invisíveis no tema escuro ── */
.pgm-adv-page .btn-default,
.pgm-adv-page a.btn-default {
    background-color: #546e7a !important;
    border-color:     #546e7a !important;
    color:            #fff    !important;
}
.pgm-adv-page .btn-default:hover,
.pgm-adv-page a.btn-default:hover {
    background-color: #607d8b !important;
    border-color:     #607d8b !important;
    color:            #fff    !important;
}
.pgm-adv-page .label-default,
.pgm-adv-page span.label-default {
    background-color: #546e7a !important;
    color:            #fff    !important;
}
.pgm-adv-page .well {
    background-color: rgba(255,255,255,.05) !important;
    border-color:     rgba(255,255,255,.1)  !important;
    color: inherit !important;
}
</style>
<nav class="pgm-contract-wizard small mb-3" aria-label="<?= h(__('Passos do contrato')) ?>">
	<ol class="list-inline mb-0 pgm-contract-wizard-steps">
		<?php for ($i = 1; $i <= 4; $i++): ?>
		<?php
		$isCurrent = ($i === $cur);
		$pillClass = 'pgm-contract-wiz-pill' . ($isCurrent ? ' pgm-contract-wiz-pill--current' : '');
		$text = $i . '. ' . h($labels[$i]);
		?>
		<li>
			<?php if ($contractId <= 0 && $i > 1): ?>
			<span class="pgm-contract-wiz-pill pgm-contract-wiz-pill--disabled"><?= $text ?></span>
			<?php elseif ($i === 1 && $contractId > 0): ?>
				<?php if ($podeEditarDadosPasso): ?>
				<?= $this->Html->link($text, ['action' => 'edit', $contractId], ['escape' => false, 'class' => $pillClass]) ?>
				<?php else: ?>
				<span class="pgm-contract-wiz-pill pgm-contract-wiz-pill--disabled" title="<?= h(__('Edição dos dados principais não disponível para o seu perfil neste estado.')) ?>"><?= $text ?></span>
				<?php endif; ?>
			<?php elseif ($i === 2 && $contractId > 0): ?>
			<?= $this->Html->link($text, ['action' => 'addServicos',    $contractId], ['escape' => false, 'class' => $pillClass]) ?>
			<?php elseif ($i === 3 && $contractId > 0): ?>
			<?= $this->Html->link($text, ['action' => 'addSignatarios', $contractId], ['escape' => false, 'class' => $pillClass]) ?>
			<?php elseif ($i === 4 && $contractId > 0): ?>
			<?= $this->Html->link($text, ['action' => 'view',           $contractId], ['escape' => false, 'class' => $pillClass]) ?>
			<?php else: ?>
			<span class="<?= h($pillClass) ?>"><?= $text ?></span>
			<?php endif; ?>
		</li>
		<?php endfor; ?>
	</ol>
</nav>
