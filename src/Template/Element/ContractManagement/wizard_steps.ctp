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

// Estilos explícitos para funcionar em tema claro e escuro
$styleActive   = 'display:inline-block;padding:3px 10px;border-radius:3px;font-size:11px;font-weight:600;background:#2979ff;color:#fff;text-decoration:none;white-space:nowrap;';
$styleInactive = 'display:inline-block;padding:3px 10px;border-radius:3px;font-size:11px;font-weight:600;background:#546e7a;color:#fff;text-decoration:none;white-space:nowrap;';
$styleDisabled = 'display:inline-block;padding:3px 10px;border-radius:3px;font-size:11px;font-weight:600;background:#37474f;color:#90a4ae;white-space:nowrap;cursor:default;';
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
	<ol class="list-inline mb-0" style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;list-style:none;padding:0;margin:0;">
		<?php for ($i = 1; $i <= 4; $i++): ?>
		<?php
		$isCurrent = ($i === $cur);
		$style = $isCurrent ? $styleActive : $styleInactive;
		$text = $i . '. ' . h($labels[$i]);
		?>
		<li style="margin:0;">
			<?php if ($contractId <= 0 && $i > 1): ?>
			<span style="<?= $styleDisabled ?>"><?= $text ?></span>
			<?php elseif ($i === 1 && $contractId > 0): ?>
				<?php if ($podeEditarDadosPasso): ?>
				<?= $this->Html->link($text, ['action' => 'edit', $contractId], ['escape' => false, 'style' => $style]) ?>
				<?php else: ?>
				<span style="<?= $styleDisabled ?>" title="<?= h(__('Edição dos dados principais não disponível para o seu perfil neste estado.')) ?>"><?= $text ?></span>
				<?php endif; ?>
			<?php elseif ($i === 2 && $contractId > 0): ?>
			<?= $this->Html->link($text, ['action' => 'addServicos',    $contractId], ['escape' => false, 'style' => $style]) ?>
			<?php elseif ($i === 3 && $contractId > 0): ?>
			<?= $this->Html->link($text, ['action' => 'addSignatarios', $contractId], ['escape' => false, 'style' => $style]) ?>
			<?php elseif ($i === 4 && $contractId > 0): ?>
			<?= $this->Html->link($text, ['action' => 'view',           $contractId], ['escape' => false, 'style' => $style]) ?>
			<?php else: ?>
			<span style="<?= $style ?>"><?= $text ?></span>
			<?php endif; ?>
		</li>
		<?php endfor; ?>
	</ol>
</nav>
