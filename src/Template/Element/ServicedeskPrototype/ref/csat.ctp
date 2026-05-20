<?php
/**
 * CSAT & NPS — placeholder pro com KPIs + roteiro de integração.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$kpis = (array)($screen['kpis'] ?? []);
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Satisfação')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">⭐ <?= h(__('CSAT & NPS')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Pesquisa de satisfação pós-atendimento e Net Promoter Score')) ?></div>
		</div>
		<?= $this->Html->link('← ' . __('Service Desk'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) : ?>
				<div class="summary-card" style="border-left:3px solid var(--teal);">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:var(--teal-dark);"><?= h((string)($k['val'] ?? '')) ?></div>
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="alert-box alert-amber">
		<strong><?= h(__('Pesquisa CSAT/NPS ainda não configurada.')) ?></strong>
		<?= h(__('Roadmap: enviar e-mail automático após status Fechado, registrar resposta 1-5, agregar NPS (Promotores − Detratores).')) ?>
	</div>

	<div class="g2">
		<div class="card">
			<div class="sec-title">⭐ <?= h(__('CSAT (Customer Satisfaction)')) ?></div>
			<p style="font-size:12px;color:var(--text-muted);">
				<?= h(__('Pergunta única: "Como você avalia o atendimento?" (1 a 5 estrelas). Cálculo: (notas 4+5) ÷ total × 100. Meta padrão: 90%.')) ?>
			</p>
		</div>
		<div class="card">
			<div class="sec-title">📊 <?= h(__('NPS (Net Promoter Score)')) ?></div>
			<p style="font-size:12px;color:var(--text-muted);">
				<?= h(__('Pergunta: "Em escala 0-10, você indicaria a PGM a um colega?" Cálculo: %Promotores (9-10) − %Detratores (0-6). Meta: NPS > 50.')) ?>
			</p>
		</div>
	</div>
</div>
