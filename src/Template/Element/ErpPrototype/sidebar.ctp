<?php
/**
 * Sidebar premium compartilhada — 8 módulos do mockup pgm_erp_completo.html.
 *
 * Cada módulo aponta para sua rota `*-prototype` quando existir, senão para a
 * tela legado correspondente. PCP fica oculto até decisão final.
 *
 * @var \App\View\AppView $this
 * @var string $active   Identificador da view atual (ex.: 'sd-fila', 'orc-lista').
 * @var array<string,int> $erpNavBadges  Contagens (ex.: ['sd-aprovacoes' => 5, 'empresas' => 4]).
 */
$u = function (array $url) {
	return $this->Url->build($url);
};
$active = (string)($active ?? '');
$badges = (array)($erpNavBadges ?? []);
$cls = static function (string $key) use ($active): string {
	return $key === $active ? ' active' : '';
};
$badge = static function (string $key, string $style = '') use ($badges): string {
	$n = (int)($badges[$key] ?? 0);
	$extra = $style !== '' ? ' ' . h($style) : '';
	$visClass = $n <= 0 ? ' nav-badge-hidden' : '';
	$dataAttrs = ' data-nav-badge="' . h($key) . '"';

	return ' <span class="nav-badge' . $extra . $visClass . '"' . $dataAttrs . ' style="' . ($n <= 0 ? 'display:none;' : '') . '">' . (int)$n . '</span>';
};

/**
 * Estrutura: cada item ['key', 'label', url, indent?, icon?, badgeStyle?].
 * Itens com `url = null` são placeholders (rota ainda não criada).
 */
$sections = [
	[
		'title' => __('Principal'),
		'items' => [
			['key' => 'dashboard', 'label' => __('Dashboard'), 'url' => ['controller' => 'Users', 'action' => 'dashboard']],
			['key' => 'clientes', 'label' => __('Clientes'), 'url' => ['controller' => 'ClientesPrototype', 'action' => 'lista']],
			['key' => 'produtos', 'label' => __('Produtos'), 'url' => ['controller' => 'ProdutosPrototype', 'action' => 'lista']],
			['key' => 'estoque', 'label' => __('Estoque'), 'url' => ['controller' => 'ProdutosPrototype', 'action' => 'estoque'], 'indent' => true],
			['key' => 'precos', 'label' => __('Tabela de preços'), 'url' => ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], 'indent' => true],
			['key' => 'historico-precos', 'label' => __('Histórico preços'), 'url' => ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos'], 'indent' => true],
			['key' => 'fornecedores', 'label' => __('Fornecedores'), 'url' => ['controller' => 'FornecedoresPrototype', 'action' => 'lista'], 'indent' => true],
		],
	],
	[
		'title' => __('Operações'),
		'items' => [
			['key' => 'orc-lista', 'label' => __('Orçamentos'), 'url' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista']],
			['key' => 'vendedores', 'label' => __('Vendedores · Ranking'), 'url' => null, 'indent' => true],
			['key' => 'relatorios-vendas', 'label' => __('Relatórios vendas'), 'url' => null, 'indent' => true],
			['key' => 'os-lista', 'label' => __('Ordens de Serviço'), 'url' => ['controller' => 'OrdensservicoPrototype', 'action' => 'lista']],
			['key' => 'os-kanban', 'label' => __('OS · Kanban'), 'url' => ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'kanban'], 'indent' => true],
			['key' => 'sd', 'label' => __('Service Desk'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'index']],
			['key' => 'sd-dashboard', 'label' => __('Dashboard'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'index'], 'indent' => true],
			['key' => 'sd-fila', 'label' => __('Fila técnica'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], 'indent' => true],
			['key' => 'sd-meus', 'label' => __('Meus tickets'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'meus'], 'indent' => true],
			['key' => 'sd-grupo', 'label' => __('Meu grupo'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'grupo'], 'indent' => true],
			['key' => 'sd-kanban', 'label' => __('Kanban'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'kanban'], 'indent' => true],
			['key' => 'sd-aprovacoes', 'label' => __('Aprovações'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'aprovacoes'], 'indent' => true, 'badgeStyle' => ''],
			['key' => 'sd-cmdb', 'label' => __('CMDB Ativos'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'cmdb'], 'indent' => true],
			['key' => 'sd-problemas', 'label' => __('Problemas'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'problemas'], 'indent' => true],
			['key' => 'sd-mudancas', 'label' => __('Mudanças'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'mudancas'], 'indent' => true],
			['key' => 'sd-contratos', 'label' => __('Contratos SLA'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'contratos'], 'indent' => true],
			['key' => 'sd-fat', 'label' => __('Faturamento'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'fat'], 'indent' => true],
			['key' => 'sd-kb', 'label' => __('Base conhecimento'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'kb'], 'indent' => true],
			['key' => 'sd-portal', 'label' => __('Portal cliente'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'portal'], 'indent' => true],
			['key' => 'sd-calendar', 'label' => __('Plantões'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'calendar'], 'indent' => true],
			['key' => 'sd-csat', 'label' => __('CSAT & NPS'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'csat'], 'indent' => true],
			['key' => 'sd-relatorios', 'label' => __('Relatórios'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'relatorios'], 'indent' => true],
			['key' => 'sd-config', 'label' => __('SLA & Config'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'config'], 'indent' => true],
			['key' => 'sd-perm', 'label' => __('Permissões'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'perm'], 'indent' => true],
			['key' => 'sd-integracoes', 'label' => __('Integrações'), 'url' => ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'integracoes'], 'indent' => true],
		],
	],
	[
		'title' => __('Indústria · PCP'),
		'items' => [
			['key' => 'pcp', 'label' => __('PCP · Visão geral'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'lista']],
			['key' => 'pcp-dashboard', 'label' => __('Dashboard'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'view', 'dashboard'], 'indent' => true],
			['key' => 'engenharia', 'label' => __('Engenharia · Fichas'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'view', 'engenharia'], 'indent' => true],
			['key' => 'bom', 'label' => __('Estrutura BOM'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'view', 'bom'], 'indent' => true],
			['key' => 'roteiro', 'label' => __('Roteiros'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'view', 'roteiro'], 'indent' => true],
			['key' => 'mrp', 'label' => __('MRP'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'view', 'mrp'], 'indent' => true],
			['key' => 'op-lista', 'label' => __('Ordens de Produção'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'view', 'op-lista'], 'indent' => true],
			['key' => 'apontamento', 'label' => __('Apontamento'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'view', 'apontamento'], 'indent' => true],
			['key' => 'qualidade-ind', 'label' => __('Qualidade'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'view', 'qualidade-ind'], 'indent' => true],
			['key' => 'expedicao', 'label' => __('Expedição'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'view', 'expedicao'], 'indent' => true],
		],
	],
	[
		'title' => __('Financeiro'),
		'items' => [
			['key' => 'financeiro', 'label' => __('Financeiro'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista']],
			['key' => 'orc-faturamento', 'label' => __('Faturamento'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'orc-faturamento']],
			['key' => 'titulos', 'label' => __('Contas a Receber'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'titulos']],
			['key' => 'cobranca', 'label' => __('Cobrança'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'cobranca']],
			['key' => 'contas-pagar', 'label' => __('Contas a Pagar'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'contasPagar']],
			['key' => 'nfe', 'label' => __('NF-e / NFS-e'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'nfe']],
			['key' => 'dre', 'label' => __('DRE Gerencial'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'dre']],
			['key' => 'relatorios-fin', 'label' => __('Relatórios financ.'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'relatorios-fin']],
		],
	],
	[
		'title' => __('Bancos'),
		'items' => [
			['key' => 'bancos', 'label' => __('Bancos'), 'url' => ['controller' => 'BancosPrototype', 'action' => 'lista']],
			['key' => 'contas', 'label' => __('Contas Bancárias'), 'url' => ['controller' => 'BancosPrototype', 'action' => 'view', 'contas']],
			['key' => 'extrato', 'label' => __('Extrato'), 'url' => ['controller' => 'BancosPrototype', 'action' => 'view', 'extrato']],
			['key' => 'conciliacao', 'label' => __('Conciliação'), 'url' => ['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao']],
			['key' => 'transferencias', 'label' => __('Transferências / PIX'), 'url' => ['controller' => 'BancosPrototype', 'action' => 'view', 'transferencias']],
			['key' => 'fluxo-caixa', 'label' => __('Fluxo de Caixa'), 'url' => ['controller' => 'BancosPrototype', 'action' => 'view', 'fluxo-caixa']],
		],
	],
	[
		'title' => __('Sistema'),
		'items' => [
			['key' => 'empresas', 'label' => __('Empresas · multi-empresa'), 'url' => ['controller' => 'EmpresasPrototype', 'action' => 'lista'], 'badgeStyle' => 'teal'],
			['key' => 'config', 'label' => __('Configurações'), 'url' => ['controller' => 'SistemaPrototype', 'action' => 'config']],
			['key' => 'empresa', 'label' => __('Empresa'), 'url' => ['controller' => 'SistemaPrototype', 'action' => 'view', 'empresa'], 'indent' => true],
			['key' => 'usuarios', 'label' => __('Usuários ERP'), 'url' => ['controller' => 'SistemaPrototype', 'action' => 'usuarios'], 'indent' => true],
			['key' => 'acesso-central', 'label' => __('Controle de Acesso'), 'url' => ['controller' => 'SistemaPrototype', 'action' => 'acessoCentral'], 'indent' => true],
			['key' => 'acesso-papeis', 'label' => __('Papéis'), 'url' => ['controller' => 'SistemaPrototype', 'action' => 'acessoPapeis'], 'indent2' => true],
			['key' => 'acesso-auditoria', 'label' => __('Auditoria de Acessos'), 'url' => ['controller' => 'SistemaPrototype', 'action' => 'view', 'acesso-auditoria'], 'indent2' => true],
			['key' => 'auditoria', 'label' => __('Auditoria · LGPD'), 'url' => ['controller' => 'SistemaPrototype', 'action' => 'auditoria'], 'indent' => true],
		],
	],
];
?>
<div class="sidebar">
	<div class="sidebar-logo">
		<div class="logo-box" aria-hidden="true">
			<svg viewBox="0 0 20 20" fill="none" stroke="#fff" stroke-width="2">
				<rect x="3" y="3" width="6" height="6" rx="1.5"/>
				<rect x="11" y="3" width="6" height="6" rx="1.5"/>
				<rect x="3" y="11" width="6" height="6" rx="1.5"/>
				<rect x="11" y="11" width="6" height="6" rx="1.5"/>
			</svg>
		</div>
		<div class="logo-name"><?= h(__('PGM Soluções')) ?></div>
		<div class="logo-sub"><?= h(__('ERP Enterprise')) ?></div>
	</div>
	<nav class="sidebar-nav" aria-label="<?= h(__('Menu principal')) ?>">
		<?php foreach ($sections as $section) : ?>
			<div class="nav-section"><?= h($section['title']) ?></div>
			<?php foreach ($section['items'] as $item) :
				$itemKey = (string)$item['key'];
				$indentCls = '';
				if (!empty($item['indent2'])) {
					$indentCls = ' indent2';
				} elseif (!empty($item['indent'])) {
					$indentCls = ' indent';
				}
				$badgeStyle = (string)($item['badgeStyle'] ?? '');
				$content = h($item['label']) . $badge($itemKey, $badgeStyle);
				if (!empty($item['url'])) :
				?>
					<a class="nav-item<?= $cls($itemKey) ?><?= $indentCls ?>" href="<?= h($u($item['url'])) ?>"><?= $content ?></a>
				<?php else : ?>
					<span class="nav-item disabled<?= $indentCls ?>" title="<?= h(__('Em construção')) ?>" style="opacity:.45;cursor:not-allowed;"><?= $content ?></span>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</nav>
	<div class="sidebar-user">
		<?php
		$userName = trim((string)$this->getRequest()->getSession()->read('Auth.User.name'));
		if ($userName === '') {
			$userName = (string)$this->getRequest()->getSession()->read('Auth.User.username');
		}
		$initials = '?';
		if ($userName !== '') {
			$parts = preg_split('/\s+/', trim($userName));
			$initials = strtoupper(substr((string)($parts[0] ?? ''), 0, 1) . substr((string)($parts[1] ?? ''), 0, 1));
			if ($initials === '') {
				$initials = strtoupper(substr($userName, 0, 2));
			}
		}
		?>
		<div class="user-av" aria-hidden="true"><?= h($initials) ?></div>
		<div>
			<div class="user-name"><?= h($userName !== '' ? $userName : __('Usuário')) ?></div>
			<div class="user-role"><?= h($this->getRequest()->getSession()->read('Auth.User.admin') ? __('Administrador') : __('Operador')) ?></div>
		</div>
	</div>
</div>
