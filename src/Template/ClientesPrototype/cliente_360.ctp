<?php
/**
 * Cliente 360º — mockup pg-cliente-360.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $payload360
 */
$H = $this->ErpPrototype;
$c = (array)($payload360['cliente'] ?? []);
$hasCliente = !empty($c);
$initials = $hasCliente ? $H->initials((string)($c['nome'] ?? '')) : '?';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div style="display:flex;align-items:center;gap:14px;">
		<div class="av" style="width:54px;height:54px;font-size:18px;background:linear-gradient(135deg,var(--teal),var(--teal-dark));color:#fff;"><?= h($initials) ?></div>
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Clientes · Visão 360º')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h($hasCliente ? (string)$c['nome'] : __('Selecione um cliente')) ?></h1>
			<?php if ($hasCliente && !empty($c['fantasia'])) : ?>
				<div style="font-size:12px;color:var(--text-muted);"><?= h((string)$c['fantasia']) ?></div>
			<?php endif; ?>
		</div>
	</div>
	<?= $this->Html->link('← ' . __('Lista de clientes'), ['controller' => 'ClientesPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?php if (!$hasCliente) : ?>
	<div class="card" style="text-align:center;padding:48px 22px;">
		<div style="font-size:48px;margin-bottom:14px;">🔍</div>
		<p style="color:var(--text-muted);margin-bottom:18px;"><?= h(__('Para abrir uma visão 360º, escolha um cliente na lista.')) ?></p>
		<?= $this->Html->link(__('Ir para lista'), ['controller' => 'ClientesPrototype', 'action' => 'lista'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
	<?php return; ?>
<?php endif; ?>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('LTV total')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)$payload360['ltv'])) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('Tickets abertos')) ?></div><div class="val" style="color:#0C447C;"><?= (int)$payload360['tickets_abertos'] ?></div></div>
	<div class="summary-card" style="background:<?= (int)$payload360['faturas_vencidas'] > 0 ? '#F8D8DA' : '' ?>;border-left:3px solid <?= (int)$payload360['faturas_vencidas'] > 0 ? 'var(--red)' : 'var(--teal-mid)' ?>;"><div class="lbl"><?= h(__('Faturas vencidas')) ?></div><div class="val" style="color:<?= (int)$payload360['faturas_vencidas'] > 0 ? '#7A1822' : 'var(--teal-dark)' ?>;"><?= (int)$payload360['faturas_vencidas'] ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--purple);"><div class="lbl"><?= h(__('Contato')) ?></div><div class="val" style="font-size:13px;color:var(--text);"><?= h((string)$c['fone']) ?: '—' ?></div></div>
</div>

<div class="g2">
	<div class="card">
		<div class="sec-title"><?= h(__('Identificação')) ?></div>
		<div style="font-size:12px;line-height:1.8;">
			<div><strong><?= h(__('CNPJ/CPF')) ?>:</strong> <span style="font-family:monospace;"><?= h((string)$c['cnpj']) ?: '—' ?></span></div>
			<div><strong><?= h(__('E-mail')) ?>:</strong> <?= h((string)$c['email']) ?: '—' ?></div>
			<div><strong><?= h(__('Endereço')) ?>:</strong> <?= h((string)$c['endereco']) ?></div>
			<div><strong><?= h(__('Cliente desde')) ?>:</strong> <?= h($H->dt($c['desde'])) ?></div>
		</div>
	</div>
	<div class="card">
		<div class="sec-title"><?= h(__('Atalhos')) ?></div>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
			<?= $this->Html->link('📋 ' . __('Editar cliente'), ['controller' => 'Clientes', 'action' => 'edit', (int)$c['id']], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('🛠 ' . __('Abrir OS'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('💼 ' . __('Novo orçamento'), \App\Utility\PortalUi::orcamentosNovoRoute(), ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('💵 ' . __('Faturas'), ['controller' => 'Faturas', 'action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('📚 ' . __('Tickets do cliente'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
			<?= $this->Html->link('📞 ' . __('Histórico'), ['controller' => 'Clientes', 'action' => 'view', (int)$c['id']], ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</div>
</div>

<?php $timeline = (array)($payload360['timeline'] ?? []); ?>
<?php if ($timeline !== []) : ?>
	<div class="card">
		<div class="sec-title">📜 <?= h(__('Linha do tempo')) ?></div>
		<?php foreach ($timeline as $t) :
			$kind = (string)$t['kind'];
			$dot = $kind === 'ticket' ? 'background:var(--blue-light);color:#0C447C;' : ($kind === 'os' ? 'background:var(--amber-light);color:#8A4D02;' : 'background:var(--teal-light);color:var(--teal-dark);');
		?>
			<div class="tl-item">
				<div class="tl-dot" style="<?= $dot ?>"><?= $t['icon'] ?></div>
				<div class="tl-body">
					<div class="tl-title">
						<?php if (!empty($t['url'])) : ?>
							<?= $this->Html->link((string)$t['label'], $t['url'], ['style' => 'color:inherit;font-weight:500;']) ?>
						<?php else : ?>
							<?= h((string)$t['label']) ?>
						<?php endif; ?>
					</div>
					<div class="tl-sub">
						<?= h((string)$t['sub']) ?>
						<?php if ($t['data'] instanceof \DateTimeInterface) : ?> · <?= h($t['data']->format('d/m/Y H:i')) ?><?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<div class="alert-box alert-blue" style="margin-top:14px;">
		<?= h(__('Sem registros recentes (tickets, OS ou faturas) para este cliente.')) ?>
	</div>
<?php endif; ?>
