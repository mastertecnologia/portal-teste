<?php
/**
 * Integrações SD — placeholder com lista de provedores possíveis.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Integrações')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">🔌 <?= h(__('Integrações')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Conectores ativos e roteiros para novas integrações')) ?></div>
		</div>
		<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>

	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;">
		<?php foreach ([
			['icon' => '📧', 'name' => 'E-mail (SMTP/POP)', 'status' => 'ativo', 'desc' => 'Abre tickets a partir de e-mails recebidos · usado em produção'],
			['icon' => '🏭', 'name' => 'ERP Grid (SOAP)', 'status' => 'ativo', 'desc' => 'Sincroniza clientes/produtos/contratos via WsProdutos, WSPGMPessoas, WSPGMContratos'],
			['icon' => '📱', 'name' => 'WhatsApp Business', 'status' => 'roadmap', 'desc' => 'Recepção de mensagens via Cloud API; abertura automática de ticket'],
			['icon' => '🧠', 'name' => 'IA · classificação automática', 'status' => 'roadmap', 'desc' => 'Categorizar assunto e prioridade ao abrir o ticket'],
			['icon' => '📊', 'name' => 'Webhook → BI', 'status' => 'roadmap', 'desc' => 'Disparo de eventos a cada mudança de estado'],
			['icon' => '🔔', 'name' => 'PagerDuty / Opsgenie', 'status' => 'roadmap', 'desc' => 'Notificação on-call para P1 fora do horário comercial'],
		] as $i) :
			$st = (string)$i['status'];
			$badge = $st === 'ativo' ? 'b-paga' : 'b-pendente';
			$lbl = $st === 'ativo' ? __('Ativo') : __('Roadmap');
		?>
			<div class="card" style="margin:0;">
				<div style="display:flex;justify-content:space-between;align-items:start;gap:10px;">
					<div style="font-size:28px;line-height:1;"><?= $i['icon'] ?></div>
					<span class="badge <?= $badge ?>"><?= h($lbl) ?></span>
				</div>
				<strong style="display:block;margin-top:10px;font-size:13px;"><?= h((string)$i['name']) ?></strong>
				<div style="font-size:11px;color:var(--text-muted);margin-top:4px;line-height:1.6;"><?= h((string)$i['desc']) ?></div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
