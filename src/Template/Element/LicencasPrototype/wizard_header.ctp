<?php
/**
 * Cabeçalho do wizard nova licença (passos 1–4).
 *
 * @var int $wizardStepNum
 * @var array<int,array{label:string,state:string}> $wizardSteps
 * @var int $licId
 */
$stepNum = (int)($wizardStepNum ?? 1);
$licId = (int)($licId ?? 0);
$cancelUrl = ['action' => 'licencas'];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			PGM ERP › <?= $this->Html->link(__('Licenciamento'), ['action' => 'dashboard'], ['style' => 'color:var(--teal)']) ?>
			› <?= h(__('Nova licença')) ?><?= $stepNum > 1 ? ' · ' . h(__('{0} de 4', $stepNum)) : '' ?>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">+ <?= h(__('Cadastrar nova licença')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Wizard em 4 passos · você pode salvar como rascunho a qualquer momento')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Cancelar'), $cancelUrl, ['class' => 'btn btn-ghost btn-sm']) ?>
		<button type="submit" form="lic-wizard-form" class="btn btn-ghost btn-sm">💾 <?= h(__('Salvar rascunho')) ?></button>
	</div>
</div>

<div class="card" style="margin-bottom:14px;padding:16px;">
	<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
		<?php
		$labels = [
			__('Cliente & Produto'),
			__('Quantidade & Datas'),
			__('Atribuir'),
			__('Cofre & Documentos'),
		];
		foreach ($labels as $i => $label) :
			$n = $i + 1;
			$active = $n === $stepNum;
			$done = $n < $stepNum;
			$circleBg = $active || $done ? 'var(--teal)' : '#CBD5E1';
			?>
		<?php if ($i > 0) :
			$lineBg = $n <= $stepNum ? 'var(--teal)' : 'var(--border)';
			?><div style="flex:1;min-width:12px;height:2px;background:<?= h($lineBg) ?>;"></div><?php endif; ?>
		<div style="display:flex;align-items:center;gap:8px;flex:1;min-width:100px;">
			<div style="width:32px;height:32px;border-radius:50%;background:<?= h($circleBg) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;"><?= $done ? '✓' : (string)$n ?></div>
			<div>
				<strong style="font-size:12px;<?= !$active && !$done ? 'color:var(--text-muted);' : '' ?>"><?= h($label) ?></strong>
				<?php if ($active) : ?><div style="font-size:10px;color:var(--text-muted);"><?= h(__('passo atual')) ?></div><?php elseif ($done) : ?><div style="font-size:10px;color:var(--teal-dark);"><?= h(__('concluído')) ?></div><?php endif; ?>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
</div>
