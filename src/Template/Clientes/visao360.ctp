<?php
/**
 * Cliente — Visão 360° (indicadores + histórico, dados reais).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cliente $cliente
 * @var array<string,mixed> $cli360
 * @var string $cli360Tab
 */
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));

$this->Breadcrumbs->add(__('Clientes'), ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add(__('Visão 360°'), [], ['class' => 'breadcrumb-item active']);

function cli360Initials($str) {
	$parts = preg_split('/\s+/', trim((string)$str), -1, PREG_SPLIT_NO_EMPTY);
	$a = strtoupper(substr($parts[0] ?? 'C', 0, 1));
	$b = strtoupper(substr($parts[1] ?? '', 0, 1));
	return $a . $b;
}

function cli360Mask($mask, $str) {
	if ($str === null || $str === '') {
		return '';
	}
	$mask = (string)$mask;
	$str = str_replace(' ', '', (string)$str);
	$len = strlen($str);
	for ($i = 0; $i < $len; $i++) {
		$pos = strpos($mask, '#');
		if ($pos === false) {
			break;
		}
		$mask[$pos] = $str[$i];
	}
	return $mask;
}

$c = $cli360;
$kpis = (array)($c['kpis'] ?? []);
$counts = (array)($c['counts'] ?? []);
$tab = $cli360Tab;
$isPj = (int)$cliente->tipo === (int)C_ClientesTipoJuridica;
$docFmt = $isPj ? cli360Mask('##.###.###/####-##', (string)$c['doc']) : cli360Mask('###.###.###-##', (string)$c['doc']);
$seg = (array)($c['segmento'] ?? []);
$avTone = ['teal', 'blue', 'rose', 'orange', 'purple', 'navy', 'wine'][(int)$cliente->id % 7];
$tabs = [
	'geral' => ['label' => __('Visão geral'), 'icon' => 'fa-th-large', 'count' => null],
	'orcamentos' => ['label' => __('Orçamentos'), 'icon' => 'fa-file-invoice', 'count' => (int)($counts['orcamentos'] ?? 0)],
	'os' => ['label' => __('OS'), 'icon' => 'fa-wrench', 'count' => (int)($counts['os'] ?? 0)],
	'financeiro' => ['label' => __('Financeiro'), 'icon' => 'fa-coins', 'count' => null],
	'contratos' => ['label' => __('Contratos'), 'icon' => 'fa-file-contract', 'count' => (int)($counts['contratos'] ?? 0)],
	'historico' => ['label' => __('Histórico'), 'icon' => 'fa-history', 'count' => null],
	'arquivos' => ['label' => __('Arquivos'), 'icon' => 'fa-paperclip', 'count' => (int)($counts['arquivos'] ?? 0)],
];
?>
<div class="col-md-12 p-0 cli-layout-unificado cli-360-root">
<div class="cli-form-root cli-ficha-page-pad">

	<header class="cli-360-toolbar">
		<div class="cli-360-toolbar-back">
			<?= $this->Html->link(
				'<i class="fas fa-arrow-left" aria-hidden="true"></i> ' . __('Clientes'),
				['action' => 'index'],
				['class' => 'cli-360-back-link', 'escape' => false, 'data-turbo' => 'false']
			) ?>
			<span class="cli-360-back-sep">›</span>
			<span class="cli-code-badge cli-360-code" translate="no"><?= h((string)$c['codigo']) ?></span>
			<span class="cli-360-back-name"><?= h((string)$c['nome']) ?></span>
		</div>
		<div class="cli-360-toolbar-actions">
			<?= $this->Html->link(__('Editar'), ['action' => 'edit', $cliente->id], ['class' => 'btn-cli-secondary btn-cli-sm', 'data-turbo' => 'false']) ?>
			<?= $this->Html->link(
				'<i class="fas fa-plus" aria-hidden="true"></i> ' . __('Novo orçamento'),
				['controller' => 'Orcamentos', 'action' => 'add'],
				['class' => 'btn-cli-primary btn-cli-sm', 'escape' => false, 'data-turbo' => 'false']
			) ?>
		</div>
	</header>

	<section class="cli-360-hero">
		<div class="cli-360-hero-av cli-av cli-av--<?= h($avTone) ?> cli-av--lg"><?= h(cli360Initials((string)$c['nome'])) ?></div>
		<div class="cli-360-hero-body">
			<h1 class="cli-360-hero-title"><?= h((string)$c['nome']) ?></h1>
			<div class="cli-360-hero-badges">
				<?php if (!empty($c['is_vip'])) : ?>
				<span class="cli-360-badge cli-360-badge--vip"><i class="fas fa-star" aria-hidden="true"></i> <?= h(__('Cliente VIP')) ?></span>
				<?php endif; ?>
				<?php if (!empty($seg['short'])) : ?>
				<span class="cli-360-badge cli-360-badge--seg cli-seg-pill cli-seg-pill--<?= h((string)($seg['tone'] ?? 'teal')) ?>"><?= h((string)$seg['short']) ?></span>
				<?php endif; ?>
				<?php if (!empty($c['inativo'])) : ?>
				<span class="cli-360-badge cli-360-badge--blocked"><?= h(__('Bloqueado')) ?></span>
				<?php endif; ?>
			</div>
			<p class="cli-360-hero-meta">
				<?php if ($docFmt !== '') : ?><span><?= $isPj ? 'CNPJ' : 'CPF' ?>: <?= h($docFmt) ?></span><?php endif; ?>
				<?php if (!empty($c['ie'])) : ?><span>IE: <?= h((string)$c['ie']) ?></span><?php endif; ?>
				<?php if (!empty($seg['label'])) : ?><span><?= h((string)$seg['label']) ?></span><?php endif; ?>
			</p>
			<?php if (!empty($c['endereco'])) : ?>
			<p class="cli-360-hero-addr"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?= h((string)$c['endereco']) ?></p>
			<?php endif; ?>
			<div class="cli-360-hero-contacts">
				<?php if (!empty($c['fone'])) : ?>
				<span><i class="fas fa-phone" aria-hidden="true"></i> <?= h((string)$c['fone']) ?></span>
				<?php endif; ?>
				<?php if (!empty($c['fone2'])) : ?>
				<span><i class="fas fa-mobile-alt" aria-hidden="true"></i> <?= h((string)$c['fone2']) ?></span>
				<?php endif; ?>
				<?php if (!empty($c['email'])) : ?>
				<span><i class="fas fa-envelope" aria-hidden="true"></i> <?= h((string)$c['email']) ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php if (!empty($c['membro_label'])) : ?>
		<div class="cli-360-hero-side">
			<div class="cli-360-hero-since"><?= h((string)$c['membro_label']) ?></div>
			<?php if (!empty($c['anos_cliente'])) : ?>
			<div class="cli-360-hero-tenure"><?= h((string)$c['anos_cliente']) ?> · <?= (int)($counts['contratos'] ?? 0) ?> <?= h(__('contratos')) ?></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</section>

	<div class="cli-360-kpis">
		<div class="cli-360-kpi cli-360-kpi--teal">
			<div class="cli-360-kpi-lbl"><?= h(__('Receita 12 meses')) ?></div>
			<div class="cli-360-kpi-val"><?= h((string)($kpis['receita12_fmt'] ?? '—')) ?></div>
			<?php if ($kpis['receita12_pct'] !== null) : ?>
			<div class="cli-360-kpi-sub <?= (int)$kpis['receita12_pct'] >= 0 ? 'cli-360-kpi-sub--up' : 'cli-360-kpi-sub--down' ?>">
				<?= (int)$kpis['receita12_pct'] >= 0 ? '↑' : '↓' ?> <?= abs((int)$kpis['receita12_pct']) ?>% <?= h(__('vs período anterior')) ?>
			</div>
			<?php elseif (empty($kpis['has_fin'])) : ?>
			<div class="cli-360-kpi-sub"><?= h(__('Sem lançamentos financeiros')) ?></div>
			<?php endif; ?>
		</div>
		<div class="cli-360-kpi cli-360-kpi--blue">
			<div class="cli-360-kpi-lbl"><?= h(__('Receita total')) ?></div>
			<div class="cli-360-kpi-val"><?= h((string)($kpis['receita_total_fmt'] ?? '—')) ?></div>
			<?php if (!empty($kpis['desde_hint'])) : ?>
			<div class="cli-360-kpi-sub"><?= h((string)$kpis['desde_hint']) ?></div>
			<?php endif; ?>
		</div>
		<div class="cli-360-kpi cli-360-kpi--orange">
			<div class="cli-360-kpi-lbl"><?= h(__('A receber')) ?></div>
			<div class="cli-360-kpi-val"><?= h((string)($kpis['a_receber_fmt'] ?? '—')) ?></div>
			<?php if (!empty($kpis['a_receber_hint'])) : ?>
			<div class="cli-360-kpi-sub"><?= h((string)$kpis['a_receber_hint']) ?></div>
			<?php endif; ?>
		</div>
		<div class="cli-360-kpi cli-360-kpi--rose">
			<div class="cli-360-kpi-lbl"><?= h(__('Ticket médio')) ?></div>
			<div class="cli-360-kpi-val"><?= h((string)($kpis['ticket_medio_fmt'] ?? '—')) ?></div>
			<div class="cli-360-kpi-sub"><?= (int)($counts['contratos'] ?? 0) > 0 ? h(__('por contrato')) : h(__('estimativa')) ?></div>
		</div>
	</div>

	<nav class="cli-360-tabs" role="tablist">
		<?php foreach ($tabs as $slug => $meta) :
			$url = $this->Url->build(['action' => 'visao360', $cliente->id, '?' => ['tab' => $slug]]);
			$active = $tab === $slug;
		?>
		<a href="<?= h($url) ?>" class="cli-360-tab<?= $active ? ' active' : '' ?>" role="tab" aria-selected="<?= $active ? 'true' : 'false' ?>" data-turbo="false">
			<i class="fas <?= h($meta['icon']) ?>" aria-hidden="true"></i>
			<?= h($meta['label']) ?><?php if ($meta['count'] !== null && $meta['count'] > 0) : ?> <span class="cli-360-tab-n">(<?= (int)$meta['count'] ?>)</span><?php endif; ?>
		</a>
		<?php endforeach; ?>
	</nav>

	<?php if ($tab === 'geral') : ?>
	<div class="cli-360-grid">
		<div class="cli-360-col-main">
			<div class="cli-360-card">
				<div class="cli-360-card-head">
					<span class="cli-360-card-title"><i class="fas fa-bolt" aria-hidden="true"></i> <?= h(__('Atividade recente')) ?></span>
					<?= $this->Html->link(__('Ver tudo'), ['action' => 'visao360', $cliente->id, '?' => ['tab' => 'historico']], ['class' => 'cli-360-link', 'data-turbo' => 'false']) ?>
				</div>
				<?= $this->element('Cli/visao360_timeline', ['items' => (array)($c['timeline_preview'] ?? []), 'empty' => __('Sem registros recentes para este cliente.')]) ?>
			</div>
			<?php if (!empty($c['receita_mensal'])) :
				$chart = (array)($c['receita_chart'] ?? []);
			?>
			<div class="cli-360-card">
				<div class="cli-360-card-head">
					<span class="cli-360-card-title"><i class="fas fa-chart-bar" aria-hidden="true"></i> <?= h(__('Evolução da receita')) ?></span>
				</div>
				<div class="cli-360-bars" aria-hidden="true">
					<?php foreach ((array)$c['receita_mensal'] as $bar) : ?>
					<div class="cli-360-bar-wrap" title="<?= h($this->Number->currency((float)$bar['valor'], 'BRL')) ?>">
						<div class="cli-360-bar" style="height:<?= max(4, (int)$bar['pct']) ?>%"></div>
						<span class="cli-360-bar-lbl"><?= h((string)$bar['label']) ?></span>
					</div>
					<?php endforeach; ?>
				</div>
				<div class="cli-360-chart-foot">
					<div><span class="cli-360-foot-lbl"><?= h(__('Média mensal')) ?></span><strong><?= h((string)($chart['media_fmt'] ?? '—')) ?></strong></div>
					<div><span class="cli-360-foot-lbl"><?= h(__('Pico')) ?></span><strong><?= h((string)($chart['pico_fmt'] ?? '—')) ?> (<?= h((string)($chart['pico_label'] ?? '')) ?>)</strong></div>
					<div><span class="cli-360-foot-lbl"><?= h(__('Tendência')) ?></span><strong class="cli-360-trend"><?= h((string)($chart['tendencia'] ?? '—')) ?></strong></div>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<div class="cli-360-col-side">
			<?php if (!empty($c['saude'])) : ?>
			<div class="cli-360-card">
				<div class="cli-360-card-head">
					<span class="cli-360-card-title"><i class="fas fa-heartbeat" aria-hidden="true"></i> <?= h(__('Saúde do relacionamento')) ?></span>
				</div>
				<?php foreach ((array)$c['saude'] as $sh) : ?>
				<div class="cli-360-saude-row">
					<div class="cli-360-saude-top">
						<span><?= h((string)$sh['label']) ?></span>
						<strong><?= h((string)$sh['valor']) ?></strong>
					</div>
					<div class="cli-360-saude-track"><div class="cli-360-saude-fill" style="width:<?= min(100, (int)($sh['pct'] ?? 0)) ?>%"></div></div>
					<?php if (!empty($sh['hint'])) : ?><small class="cli-360-saude-hint"><?= h((string)$sh['hint']) ?></small><?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<div class="cli-360-card">
				<div class="cli-360-card-head">
					<span class="cli-360-card-title"><i class="fas fa-bolt" aria-hidden="true"></i> <?= h(__('Atalhos rápidos')) ?></span>
				</div>
				<div class="cli-360-shortcuts">
					<?= $this->Html->link('<i class="fas fa-file-invoice"></i> ' . __('Novo orçamento'), ['controller' => 'Orcamentos', 'action' => 'add'], ['class' => 'cli-360-shortcut', 'escape' => false, 'data-turbo' => 'false']) ?>
					<?= $this->Html->link('<i class="fas fa-wrench"></i> ' . __('Abrir OS'), ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'cli-360-shortcut', 'escape' => false, 'data-turbo' => 'false']) ?>
					<?= $this->Html->link('<i class="fas fa-edit"></i> ' . __('Editar cadastro'), ['action' => 'edit', $cliente->id], ['class' => 'cli-360-shortcut', 'escape' => false, 'data-turbo' => 'false']) ?>
					<?= $this->Html->link('<i class="fas fa-headset"></i> ' . __('Tickets') . ' (' . (int)($counts['tickets_abertos'] ?? 0) . ')', ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'cli-360-shortcut', 'escape' => false, 'data-turbo' => 'false']) ?>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php if ($tab === 'historico') : ?>
	<div class="cli-360-card cli-360-card--full">
		<div class="cli-360-card-head">
			<span class="cli-360-card-title"><i class="fas fa-history" aria-hidden="true"></i> <?= h(__('Histórico completo')) ?></span>
		</div>
		<?= $this->element('Cli/visao360_timeline', ['items' => (array)($c['timeline'] ?? []), 'empty' => __('Nenhum evento, OS, orçamento ou lançamento encontrado.')]) ?>
		<?php if (!empty($c['domain_events_ready']) && !empty($c['domain_events'])) : ?>
		<h3 class="cli-360-subhead"><?= h(__('Eventos de domínio')) ?></h3>
		<div class="table-responsive">
			<table class="table table-sm cli-360-events-table">
				<thead><tr><th><?= h(__('Quando')) ?></th><th><?= h(__('Tipo')) ?></th><th><?= h(__('Descrição')) ?></th></tr></thead>
				<tbody>
				<?php foreach ((array)$c['domain_events'] as $ev) : ?>
				<tr>
					<td><?= $ev->created ? h($ev->created->i18nFormat('dd/MM/yyyy HH:mm')) : '—' ?></td>
					<td><code><?= h($ev->event_type) ?></code></td>
					<td><?= nl2br(h($ev->description ?? '')) ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php elseif (empty($c['domain_events_ready'])) : ?>
		<p class="text-muted small mb-0"><?= h(__('Eventos de auditoria disponíveis após migration client_domain_events.')) ?></p>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<?php
	$listTabs = [
		'orcamentos' => ['key' => 'orcamentos', 'empty' => __('Nenhum orçamento para este cliente.')],
		'os' => ['key' => 'os_list', 'empty' => __('Nenhuma ordem de serviço.')],
		'financeiro' => ['key' => 'financeiro', 'empty' => __('Nenhum lançamento financeiro.')],
		'contratos' => ['key' => 'contratos', 'empty' => __('Nenhum contrato vinculado.')],
	];
	if (isset($listTabs[$tab])) :
		$lk = $listTabs[$tab]['key'];
		$rows = (array)($c[$lk] ?? []);
	?>
	<div class="cli-360-card cli-360-card--full">
		<?php if ($rows === []) : ?>
		<p class="cli-360-empty"><?= h($listTabs[$tab]['empty']) ?></p>
		<?php else : ?>
		<ul class="cli-360-list">
			<?php foreach ($rows as $row) : ?>
			<li class="cli-360-list-item">
				<?php if (!empty($row['url'])) : ?>
				<?= $this->Html->link(h((string)$row['label']), $row['url'], ['class' => 'cli-360-list-link', 'data-turbo' => 'false']) ?>
				<?php else : ?>
				<span class="cli-360-list-link"><?= h((string)$row['label']) ?></span>
				<?php endif; ?>
				<?php if (!empty($row['sub'])) : ?><span class="cli-360-list-sub"><?= h((string)$row['sub']) ?></span><?php endif; ?>
				<?php if (!empty($row['valor_fmt'])) : ?><span class="cli-360-list-val"><?= h((string)$row['valor_fmt']) ?></span><?php endif; ?>
				<?php if (!empty($row['status'])) : ?><span class="cli-360-list-badge"><?= h((string)$row['status']) ?></span><?php endif; ?>
				<?php if (!empty($row['vencimento'])) : ?><span class="cli-360-list-sub"><?= h((string)$row['vencimento']) ?></span><?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<?php if ($tab === 'arquivos') : ?>
	<div class="cli-360-card cli-360-card--full">
		<p class="cli-360-empty"><?= h(__('Anexos do cliente: use a aba correspondente na ficha de edição ou módulo de documentos quando disponível.')) ?></p>
		<?= $this->Html->link(__('Abrir ficha do cliente'), ['action' => 'edit', $cliente->id], ['class' => 'btn-cli-secondary', 'data-turbo' => 'false']) ?>
	</div>
	<?php endif; ?>

</div>
</div>
