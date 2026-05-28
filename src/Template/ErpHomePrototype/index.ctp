<?php
/**
 * pg-home — paridade visual com docs/reference/pgm_erp_completo.html (#pg-home).
 *
 * @var array<string,mixed> $homeKpi
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
	string $hover = '',
	bool $subDanger = false
) {
	$cls = 'pgm-home-tile' . ($hover !== '' ? ' pgm-home-tile--' . $hover : '');
	$subStyle = $subDanger ? 'font-size:11px;color:#7A1822;font-weight:600;' : 'font-size:11px;color:var(--text-muted);';
	return $this->Html->link(
		'<div style="font-size:24px;margin-bottom:4px;">' . $emoji . '</div>'
		. '<div style="font-size:13px;font-weight:600;">' . h($title) . '</div>'
		. '<div style="' . $subStyle . '">' . h($sub) . '</div>',
		$route,
		['escape' => false, 'class' => $cls]
	);
};

$kpiCard = function (string $label, string $value, string $sub, array $route, string $style = '') use ($brl) {
	return $this->Html->link(
		'<div class="lbl">' . h($label) . '</div>'
		. '<div class="val">' . $value . '</div>'
		. '<div style="font-size:11px;margin-top:4px;">' . $sub . '</div>',
		$route,
		['escape' => false, 'class' => 'summary-card', 'style' => 'text-decoration:none;color:inherit;display:block;' . $style]
	);
};
?>
<div class="pgm-erp-home">
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

<div class="summary-grid pgm-erp-home-kpis" style="margin-bottom:14px;">
	<?= $kpiCard(
		__('Saldo total bancos'),
		'<span style="color:var(--teal-dark);">' . h($brl((float)($k['saldo_bancos'] ?? 0))) . '</span>',
		'<span style="color:var(--text-muted);">' . h(__('Contas ativas consolidadas')) . '</span>',
		PortalUi::listRoute('bancos') ?? ['controller' => 'BancosPrototype', 'action' => 'lista'],
		'background:linear-gradient(135deg,var(--teal-light),#fff);border-left:3px solid var(--teal);'
	) ?>
	<?= $kpiCard(
		__('A receber'),
		'<span style="color:var(--teal);">' . h($brl((float)($k['cr_receber'] ?? 0))) . '</span>',
		'<span style="color:var(--text-muted);">' . h(sprintf(__('%d títulos pendentes'), (int)($k['titulos_pendentes'] ?? 0))) . '</span>',
		['controller' => 'FinanceiroPrototype', 'action' => 'titulos'],
		'border-left:3px solid #1D9E75;'
	) ?>
	<?= $kpiCard(
		__('Orçamentos abertos'),
		'<span style="color:#0C447C;">' . h($brl((float)($k['orcamentos_abertos_valor'] ?? 0))) . '</span>',
		'<span style="color:var(--text-muted);">' . h(sprintf(__('%d em negociação'), (int)($k['orcamentos_negociacao'] ?? 0))) . '</span>',
		PortalUi::listRoute('orcamentos') ?? ['controller' => 'OrcamentosPrototype', 'action' => 'lista'],
		'border-left:3px solid var(--blue);'
	) ?>
	<?= $kpiCard(
		__('OS em andamento'),
		'<span style="color:#8A4D02;">' . (int)($k['os_abertas'] ?? 0) . '</span>',
		'<span style="color:var(--text-muted);">' . h(__('Ordens registradas na empresa')) . '</span>',
		['controller' => 'OrdensservicoPrototype', 'action' => 'lista'],
		'border-left:3px solid var(--amber);'
	) ?>
	<?= $kpiCard(
		__('Faturado mês'),
		'<span style="color:#7A1B5C;">' . h($brl((float)($k['faturado_mes'] ?? 0))) . '</span>',
		'<span style="color:var(--teal-dark);">' . h(sprintf(__('%d orçamentos no mês'), (int)($k['orcamentos_mes'] ?? 0))) . '</span>',
		['controller' => 'FinanceiroPrototype', 'action' => 'lista'],
		'border-left:3px solid #D946A0;'
	) ?>
	<?= $kpiCard(
		__('Vencendo em 7 dias'),
		'<span style="color:#8A4D02;">' . h($brl((float)($k['vencendo_7d'] ?? 0))) . '</span>',
		'<span style="color:#8A4D02;">' . h(sprintf(__('%d títulos · agir agora'), (int)($k['vencendo_7d_qtd'] ?? 0))) . '</span>',
		['controller' => 'FinanceiroPrototype', 'action' => 'titulos'],
		'background:#FAEEDA;border-left:3px solid var(--amber);'
	) ?>
</div>

<div class="pgm-erp-home-modules" style="margin-bottom:14px;">
	<div class="card">
		<div class="sec-title"><?= h(__('Comercial')) ?></div>
		<div class="pgm-home-tile-grid">
			<?= $tile('📋', __('Novo Orçamento'), __('Criar proposta comercial'), PortalUi::orcamentosNovoRoute()) ?>
			<?= $tile('📑', __('Orçamentos'), sprintf(__('%d ativos · ver lista'), (int)($k['orcamentos_negociacao'] ?? 0)), PortalUi::listRoute('orcamentos') ?? ['controller' => 'OrcamentosPrototype', 'action' => 'lista']) ?>
			<?= $tile('🔧', __('Abrir OS'), __('Nova ordem de serviço'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura']) ?>
			<?= $tile('👥', __('Clientes'), sprintf(__('%d ativos · CRM 360º'), (int)($k['clientes_ativos'] ?? 0)), PortalUi::listRoute('clientes') ?? ['controller' => 'ClientesPrototype', 'action' => 'lista']) ?>
			<?= $tile('📦', __('Produtos'), __('Estoque & preços'), PortalUi::listRoute('produtos') ?? ['controller' => 'ProdutosPrototype', 'action' => 'lista']) ?>
			<?php
			$sdSub = (int)($k['tickets_abertos'] ?? 0) > 0
				? sprintf(__('%d tickets no escopo'), (int)$k['tickets_abertos'])
				: __('Fila e aprovações');
			echo $tile('🎫', __('Service Desk'), $sdSub, PortalUi::servicedeskHomeRoute());
			?>
		</div>
	</div>

	<div class="card">
		<div class="sec-title"><?= h(__('Financeiro & Bancos')) ?></div>
		<div class="pgm-home-tile-grid">
			<?= $tile('🏦', __('Bancos'), __('Contas e saldos'), PortalUi::listRoute('bancos') ?? ['controller' => 'BancosPrototype', 'action' => 'lista'], 'blue') ?>
			<?= $tile('📋', __('Contas a Receber'), sprintf(__('%d títulos · %s'), (int)($k['titulos_pendentes'] ?? 0), $brl((float)($k['cr_receber'] ?? 0))), ['controller' => 'FinanceiroPrototype', 'action' => 'titulos'], 'blue') ?>
			<?= $tile('🔗', __('Conciliação'), __('Revisar matches'), ['controller' => 'BancosPrototype', 'action' => 'conciliar'], 'blue') ?>
			<?= $tile('📈', __('Fluxo de Caixa'), __('Projeção financeira'), ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'fluxo-caixa'], 'blue') ?>
			<?= $tile('💸', __('Contas a Pagar'), __('Títulos de saída'), ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'contas-pagar'], 'blue') ?>
			<?= $tile('📊', __('DRE'), __('Resultado do mês'), ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'dre'], 'blue') ?>
			<?= $tile('🧾', __('NF-e'), __('Emissão fiscal'), ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'nfe'], 'blue') ?>
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
			<?= $tile('📝', __('Requisições de Compra'), __('Compras urgentes'), ['controller' => 'PcpPrototype', 'action' => 'view', 'requisicoes'], 'purple') ?>
			<?= $tile('📊', __('Custos de Produção'), __('Análise de custos'), ['controller' => 'PcpPrototype', 'action' => 'view', 'custos-producao'], 'purple') ?>
		</div>
	</div>
</div>
</div>
