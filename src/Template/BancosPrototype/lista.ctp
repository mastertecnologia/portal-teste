<?php
/**
 * Bancos — mockup pg-bancos.
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $bcItems
 * @var array{total:int,ativas:int,inativas:int} $bcKpi
 */
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Bancos')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🏦 <?= h(__('Contas Bancárias')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('%d contas · %d ativas')), (int)$bcKpi['total'], (int)$bcKpi['ativas']) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📊 ' . __('Extrato'), ['controller' => 'BancosPrototype', 'action' => 'view', 'extrato'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('🔄 ' . __('Conciliação'), ['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📈 ' . __('Fluxo de caixa'), ['controller' => 'BancosPrototype', 'action' => 'view', 'fluxo-caixa'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<?php if ($bcItems === []) : ?>
	<div class="card" style="text-align:center;padding:32px 22px;color:var(--text-muted);">
		<?= h(__('Nenhuma conta bancária cadastrada.')) ?>
		<div style="margin-top:14px;"><?= $this->Html->link(__('Cadastrar nos Bancos clássicos'), ['controller' => 'FinanceiroBancos', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm']) ?></div>
	</div>
<?php else : ?>
	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;margin-bottom:14px;">
		<?php foreach ($bcItems as $b) : ?>
			<div style="background:#fff;border:1px solid var(--border-light);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow);">
				<div style="padding:14px 16px;background:linear-gradient(135deg,var(--teal-dark),var(--teal));color:#fff;display:flex;justify-content:space-between;align-items:center;">
					<div>
						<div style="font-size:14px;font-weight:600;"><?= h((string)$b['nome']) ?></div>
						<div style="font-size:11px;opacity:.85;"><?= h(__('Banco {0}', (string)$b['codigo'])) ?></div>
					</div>
					<span class="badge <?= !empty($b['ativo']) ? 'b-paga' : 'b-arq' ?>" style="background:rgba(255,255,255,.18);color:#fff;"><?= h(!empty($b['ativo']) ? __('Ativa') : __('Inativa')) ?></span>
				</div>
				<div style="padding:14px 16px;">
					<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;"><?= h(__('Agência · Conta')) ?></div>
					<div style="font-family:monospace;font-size:14px;font-weight:600;"><?= h((string)$b['agencia'] !== '' ? (string)$b['agencia'] : '—') ?> · <?= h((string)$b['conta'] !== '' ? (string)$b['conta'] : '—') ?></div>
					<?php if (!empty($b['carteira'])) : ?>
						<div style="font-size:11px;color:var(--text-muted);margin-top:8px;"><?= h(__('Carteira')) ?>: <?= h((string)$b['carteira']) ?></div>
					<?php endif; ?>
					<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
						<?= $this->Html->link(__('Editar'), ['controller' => 'FinanceiroBancos', 'action' => 'edit', (int)$b['id']], ['class' => 'btn btn-ghost btn-xs']) ?>
						<?= $this->Html->link(__('Extrato'), ['controller' => 'BancosPrototype', 'action' => 'view', 'extrato', '?' => ['conta' => (string)$b['nome']]], ['class' => 'btn btn-primary btn-xs']) ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
