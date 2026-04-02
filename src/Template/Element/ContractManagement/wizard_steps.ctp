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
<nav class="pgm-contract-wizard small mb-3" aria-label="<?= h(__('Passos do contrato')) ?>">
	<ol class="list-inline mb-0 d-flex flex-wrap align-items-center" style="gap:6px;">
		<?php for ($i = 1; $i <= 4; $i++): ?>
		<?php
		$isCurrent = ($i === $cur);
		$lblClass = $isCurrent ? 'label label-primary' : 'label label-default';
		$text = $i . '. ' . h($labels[$i]);
		?>
		<li class="list-inline-item">
			<?php if ($contractId <= 0 && $i > 1): ?>
			<span class="label label-default" style="opacity:0.65;"><?= $text ?></span>
			<?php elseif ($i === 1 && $contractId > 0): ?>
			<?php if ($podeEditarDadosPasso): ?>
			<?= $this->Html->link($text, ['action' => 'edit', $contractId], ['class' => $lblClass, 'escape' => false]) ?>
			<?php else: ?>
			<span class="<?= h($lblClass) ?>" style="opacity:0.65;cursor:not-allowed;" title="<?= h(__('Edição dos dados principais não disponível para o seu perfil neste estado.')) ?>"><?= $text ?></span>
			<?php endif; ?>
			<?php elseif ($i === 2 && $contractId > 0): ?>
			<?= $this->Html->link($text, ['action' => 'addServicos', $contractId], ['class' => $lblClass, 'escape' => false]) ?>
			<?php elseif ($i === 3 && $contractId > 0): ?>
			<?= $this->Html->link($text, ['action' => 'addSignatarios', $contractId], ['class' => $lblClass, 'escape' => false]) ?>
			<?php elseif ($i === 4 && $contractId > 0): ?>
			<?= $this->Html->link($text, ['action' => 'view', $contractId], ['class' => $lblClass, 'escape' => false]) ?>
			<?php else: ?>
			<span class="<?= h($lblClass) ?>"><?= $text ?></span>
			<?php endif; ?>
		</li>
		<?php endfor; ?>
	</ol>
</nav>
