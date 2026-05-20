<?php
/**
 * Wizard · 3/5 Impressão (preview do PDF) — mockup pg-print.
 *
 * @var \App\View\AppView $this
 * @var array<int,array{label:string,state:string}> $wizardSteps
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Comercial · Impressão')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🖨 <?= h(__('Pré-visualização')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Revisar itens'), ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'revisao'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="card" style="background:var(--bg-surface);padding:24px;">
	<div style="background:#fff;border-radius:var(--radius-lg);padding:36px 44px;max-width:720px;margin:0 auto;box-shadow:0 4px 18px rgba(0,0,0,.06);">
		<div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2.5px solid var(--teal);padding-bottom:18px;margin-bottom:18px;">
			<div>
				<div style="font-size:18px;font-weight:700;color:var(--teal-dark);">PGM Soluções</div>
				<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Orçamento comercial')) ?></div>
			</div>
			<div style="text-align:right;">
				<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Número')) ?></div>
				<div style="font-size:14px;font-weight:600;font-family:monospace;">ORC-XXXX</div>
			</div>
		</div>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:11px;margin-bottom:16px;">
			<div>
				<div style="color:var(--text-muted);text-transform:uppercase;font-size:10px;letter-spacing:.4px;"><?= h(__('Cliente')) ?></div>
				<div style="font-weight:600;margin-top:3px;"><?= h(__('Selecionar na etapa anterior')) ?></div>
			</div>
			<div>
				<div style="color:var(--text-muted);text-transform:uppercase;font-size:10px;letter-spacing:.4px;"><?= h(__('Validade')) ?></div>
				<div style="font-weight:600;margin-top:3px;"><?= h(date('d/m/Y', strtotime('+30 days'))) ?></div>
			</div>
		</div>
		<p style="font-size:11px;color:var(--text-muted);font-style:italic;text-align:center;margin:32px 0;">
			<?= h(__('Pré-visualização ilustrativa · o PDF real é gerado no fluxo clássico')) ?>
		</p>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Editar itens'), ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'revisao'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link(__('Baixar PDF'), ['controller' => 'Orcamentos', 'action' => 'imprimirPdf'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('Enviar para assinatura') . ' →', ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'esign'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>
