<?php
/**
 * Cabeçalho fora dos cards (protótipo pg-novo).
 *
 * @var array|string $orcListRoute
 * @var string|null $crumbMiddleHtml HTML entre "Orçamentos" e o passo atual (ex.: link Revisão)
 * @var string $crumbCurrent Rótulo do passo atual
 * @var int|string $orcNumero Número exibido no título
 * @var array|string $cancelUrl
 * @var bool $cancelTurbo
 */
$orcListRoute = $orcListRoute ?? ['controller' => 'Orcamentos', 'action' => 'index'];
$crumbCurrent = $crumbCurrent ?? 'Novo';
$cancelTurbo = !empty($cancelTurbo);
$cancelOpts = ['class' => 'btn btn-orc-form-secondary', 'escape' => false];
if ($cancelTurbo) {
	$cancelOpts['data-turbo'] = 'false';
}
?>
<div class="orcamento-topbar orc-page-head">
	<div class="orcamento-topbar-main">
		<div class="orc-form-crumb">
			<?= $this->Html->link('Orçamentos', $orcListRoute, ['escape' => false]) ?>
			<?php if (!empty($crumbMiddleHtml)) : ?>
				› <?= $crumbMiddleHtml ?>
			<?php endif; ?>
			› <span class="orc-form-crumb-current"><?= h($crumbCurrent) ?></span>
		</div>
		<h1 class="orc-h1" id="orc-novo-proposta-title">
			Proposta de Orçamento <span class="orc-id-accent">#<?= (int)$orcNumero ?></span>
		</h1>
	</div>
	<div class="orcamento-topbar-actions orc-page-head-actions">
		<?= $this->Html->link('Cancelar', $cancelUrl, $cancelOpts) ?>
		<?= $this->Form->button('Avançar para revisão →', [
			'type' => 'submit',
			'class' => 'btn btn-orc-premium-primary',
			'escape' => false,
		]) ?>
	</div>
</div>
