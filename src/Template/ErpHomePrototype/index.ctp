<?php
/**
 * pg-home — alinhado a docs/reference/pgm_erp_completo.html (#pg-home).
 *
 * @var array<string,mixed> $homeKpi
 * @var array<int,array<string,mixed>> $homeRecentOrc
 * @var array<int,array<string,mixed>> $homeRecentOs
 * @var array<int,array<string,mixed>> $homeActivity
 * @var string $homeDataLabel
 * @var string $homeUserFirstName
 */
use App\Utility\PortalUi;

$k = (array)($homeKpi ?? []);
$brl = static function ($v): string {
	return 'R$ ' . number_format((float)$v, 2, ',', '.');
};

$tile = function (
	string $emoji,
	string $title,
	string $sub,
	array $route,
	string $hover = ''
) {
	$cls = 'pgm-home-tile' . ($hover !== '' ? ' pgm-home-tile--' . $hover : '');
	return $this->Html->link(
		'<div style="font-size:24px;margin-bottom:4px;">' . $emoji . '</div>'
		. '<div style="font-size:13px;font-weight:600;">' . h($title) . '</div>'
		. '<div style="font-size:11px;color:var(--text-muted);">' . h($sub) . '</div>',
		$route,
		['escape' => false, 'class' => $cls]
	);
};
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h(sprintf(__('Bom dia, %s 👋'), $homeUserFirstName ?? '')) ?></h1>
		<div style="font-size:13px;color:var(--text-muted);margin-top:2px;"><?= h($homeDataLabel ?? '') ?> · <?= h(__('Aqui está o resumo do seu ERP hoje')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('🔄 ' . __('Sincronizar tudo'), PortalUi::listRoute('bancos') ?? ['controller' => 'BancosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Novo orçamento'), PortalUi::orcamentosNovoRoute(), ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="background:linear-gradient(135deg,var(--teal-light),#fff);border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Orçamentos no mês')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= (int)($k['orcamentos_mes'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h($brl((float)($k['orcamentos_valor'] ?? 0))) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid #1D9E75;">
		<div class="lbl"><?= h(__('A receber')) ?></div>
		<div class="val" style="color:var(--teal);"><?= h($brl((float)($k['cr_receber'] ?? 0))) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Faturas em aberto')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--blue);">
		<div class="lbl"><?= h(__('Ordens de serviço')) ?></div>
		<div class="val" style="color:#0C447C;"><?= (int)($k['os_abertas'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Total na empresa')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--amber);">
		<div class="lbl"><?= h(__('Tickets (escopo)')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= (int)($k['tickets_abertos'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Service Desk')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid #D946A0;">
		<div class="lbl"><?= h(__('Clientes ativos')) ?></div>
		<div class="val" style="color:#7A1B5C;"><?= (int)($k['clientes_ativos'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--teal-dark);"><?= h(__('Cadastro ativo')) ?></div>
	</div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);">
		<div class="lbl"><?= h(__('Ticket médio orçamentos')) ?></div>
		<?php
		$mediaOrc = (int)($k['orcamentos_mes'] ?? 0) > 0
			? (float)($k['orcamentos_valor'] ?? 0) / (int)$k['orcamentos_mes']
			: 0.0;
		?>
		<div class="val" style="color:#8A4D02;"><?= h($brl($mediaOrc)) ?></div>
		<div style="font-size:11px;color:#8A4D02;"><?= h(__('Mês corrente')) ?></div>
	</div>
</div>

<div class="g2" style="margin-bottom:14px;">
	<div class="card">
		<div class="sec-title"><?= h(__('Comercial')) ?></div>
		<div class="pgm-home-tile-grid">
			<?= $tile('📋', __('Novo Orçamento'), __('Criar proposta comercial'), PortalUi::orcamentosNovoRoute()) ?>
			<?= $tile('📑', __('Orçamentos'), sprintf(__('%d no mês · ver lista'), (int)($k['orcamentos_mes'] ?? 0)), PortalUi::listRoute('orcamentos') ?? ['controller' => 'OrcamentosPrototype', 'action' => 'lista']) ?>
			<?= $tile('🔧', __('Abrir OS'), __('Nova ordem de serviço'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura']) ?>
			<?= $tile('👥', __('Clientes'), sprintf(__('%d ativos · CRM 360º'), (int)($k['clientes_ativos'] ?? 0)), PortalUi::listRoute('clientes') ?? ['controller' => 'ClientesPrototype', 'action' => 'lista']) ?>
			<?= $tile('📦', __('Produtos'), __('Estoque & preços'), PortalUi::listRoute('produtos') ?? ['controller' => 'ProdutosPrototype', 'action' => 'lista']) ?>
			<?= $tile('🎫', __('Service Desk'), sprintf(__('%d tickets no escopo'), (int)($k['tickets_abertos'] ?? 0)), PortalUi::servicedeskHomeRoute()) ?>
		</div>
	</div>

	<div class="card">
		<div class="sec-title"><?= h(__('Financeiro & Bancos')) ?></div>
		<div class="pgm-home-tile-grid">
			<?= $tile('🏦', __('Bancos'), __('Contas e saldos'), PortalUi::listRoute('bancos') ?? ['controller' => 'BancosPrototype', 'action' => 'lista'], 'blue') ?>
			<?= $tile('📋', __('Contas a Receber'), $brl((float)($k['cr_receber'] ?? 0)), ['controller' => 'FinanceiroPrototype', 'action' => 'titulos'], 'blue') ?>
			<?= $tile('🔗', __('Conciliação'), __('Revisar matches'), ['controller' => 'BancosPrototype', 'action' => 'conciliar'], 'blue') ?>
			<?= $tile('📈', __('Fluxo de Caixa'), __('Projeção financeira'), ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'fluxo-caixa'], 'blue') ?>
			<?= $tile('💸', __('Contas a Pagar'), __('Títulos de saída'), ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'contas-pagar'], 'blue') ?>
			<?= $tile('📊', __('Financeiro'), __('Visão geral'), PortalUi::listRoute('financeiro') ?? ['controller' => 'FinanceiroPrototype', 'action' => 'lista'], 'blue') ?>
		</div>
	</div>

	<div class="card">
		<div class="sec-title"><?= h(__('Indústria & Produção')) ?></div>
		<div class="pgm-home-tile-grid">
			<?= $tile('🏭', __('Dashboard PCP'), __('Visão da fábrica'), ['controller' => 'PcpPrototype', 'action' => 'view', 'dashboard'], 'purple') ?>
			<?= $tile('📋', __('Ordens de Produção'), __('Lista de OPs'), ['controller' => 'PcpPrototype', 'action' => 'view', 'op-lista'], 'purple') ?>
			<?= $tile('⏱', __('Apontamento'), __('Chão de fábrica'), ['controller' => 'PcpPrototype', 'action' => 'view', 'apontamento'], 'purple') ?>
			<?= $tile('📦', __('MRP'), __('Necessidade de compra'), ['controller' => 'PcpPrototype', 'action' => 'view', 'mrp'], 'purple') ?>
			<?= $tile('⚙', __('Configurador'), __('Produtos sob medida'), ['controller' => 'PcpPrototype', 'action' => 'view', 'configurador'], 'purple') ?>
			<?= $tile('🚚', __('Expedição'), __('Entregas'), ['controller' => 'PcpPrototype', 'action' => 'view', 'expedicao'], 'purple') ?>
		</div>
	</div>
</div>

<div class="g2">
	<div class="card">
		<div class="sec-title">⚡ <?= h(__('Atividade recente · todos os módulos')) ?></div>
		<?php if (!empty($homeActivity)) : ?>
			<?php foreach ((array)$homeActivity as $ev) : ?>
				<div class="tl-item">
					<div class="tl-dot" style="background:<?= h((string)($ev['bg'] ?? 'var(--teal-light)')) ?>;"><?= h((string)($ev['icon'] ?? '•')) ?></div>
					<div class="tl-body">
						<div class="tl-title"><?= h((string)($ev['title'] ?? '')) ?></div>
						<div class="tl-sub"><?= h((string)($ev['sub'] ?? '')) ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p style="font-size:12px;color:var(--text-muted);margin:0;"><?= h(__('Nenhuma atividade recente no período.')) ?></p>
		<?php endif; ?>
	</div>

	<div class="card">
		<div class="sec-title">⏰ <?= h(__('Pendências & próximas ações')) ?></div>
		<?php if ((float)($k['cr_receber'] ?? 0) > 0) : ?>
			<div style="padding:10px 12px;background:#FAEEDA;border-radius:var(--radius);border-left:3px solid var(--amber);margin-bottom:8px;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
					<div>
						<div style="font-size:13px;font-weight:600;color:#8A4D02;"><?= h(__('Contas a receber em aberto')) ?></div>
						<div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= h($brl((float)($k['cr_receber'] ?? 0))) ?></div>
					</div>
					<?= $this->Html->link(__('Ver'), ['controller' => 'FinanceiroPrototype', 'action' => 'titulos'], ['class' => 'btn btn-amber btn-xs']) ?>
				</div>
			</div>
		<?php endif; ?>
		<?php if ((int)($k['tickets_abertos'] ?? 0) > 0) : ?>
			<div style="padding:10px 12px;background:var(--blue-light);border-radius:var(--radius);border-left:3px solid var(--blue);margin-bottom:8px;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
					<div>
						<div style="font-size:13px;font-weight:600;color:#0C447C;"><?= h(__('Tickets no seu escopo')) ?></div>
						<div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= h(sprintf(__('%d aguardando atenção'), (int)($k['tickets_abertos'] ?? 0))) ?></div>
					</div>
					<?= $this->Html->link(__('Abrir'), PortalUi::servicedeskHomeRoute(), ['class' => 'btn btn-blue btn-xs']) ?>
				</div>
			</div>
		<?php endif; ?>
		<?php if ((int)($k['os_abertas'] ?? 0) > 0) : ?>
			<div style="padding:10px 12px;background:var(--teal-light);border-radius:var(--radius);border-left:3px solid var(--teal);">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
					<div>
						<div style="font-size:13px;font-weight:600;color:var(--teal-dark);"><?= h(__('Ordens de serviço')) ?></div>
						<div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= h(sprintf(__('%d registros na empresa'), (int)($k['os_abertas'] ?? 0))) ?></div>
					</div>
					<?= $this->Html->link(__('Ver OS'), ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'], ['class' => 'btn btn-primary btn-xs']) ?>
				</div>
			</div>
		<?php endif; ?>
		<?php if ((float)($k['cr_receber'] ?? 0) <= 0 && (int)($k['tickets_abertos'] ?? 0) <= 0 && (int)($k['os_abertas'] ?? 0) <= 0) : ?>
			<p style="font-size:12px;color:var(--text-muted);margin:0;"><?= h(__('Nenhuma pendência crítica no momento.')) ?></p>
		<?php endif; ?>
	</div>
</div>
