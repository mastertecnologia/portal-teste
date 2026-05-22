<?php
/**
 * Cliente — Visão 360° (indicadores + histórico, dados reais).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cliente $cliente
 * @var array<string,mixed> $cli360
 * @var string $cli360Tab
 */
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
<div class="col-md-12 p-0">
<div class="cli-layout-unificado cli-360-page">

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
				<span class="cli-360-badge cli-360-badge--seg"><?= h((string)$seg['short']) ?></span>
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

	<?php $finCrm = (array)($c['finance_crm'] ?? []); ?>
	<div class="cli-360-kpis cli-360-kpis--secondary">
		<div class="cli-360-kpi cli-360-kpi--purple<?= empty($finCrm['has_limite']) ? ' cli-360-kpi--muted' : '' ?>">
			<div class="cli-360-kpi-lbl"><?= h(__('Limite de crédito')) ?></div>
			<div class="cli-360-kpi-val"><?= !empty($finCrm['has_limite']) ? h((string)$finCrm['limite_fmt']) : h(__('Não cadastrado')) ?></div>
			<?php if (!empty($finCrm['has_limite'])) : ?>
			<div class="cli-360-kpi-sub"><?= h(__('{0} disponíveis', (string)$finCrm['disponivel_fmt'])) ?></div>
			<div class="cli-360-credito-track"><div class="cli-360-credito-fill" style="width:<?= min(100, (int)($finCrm['limite_pct'] ?? 0)) ?>%"></div></div>
			<?php else : ?>
			<div class="cli-360-kpi-sub"><?= $this->Html->link(__('Definir na ficha'), ['action' => 'edit', $cliente->id, '?' => ['wizard' => 3], '#' => 'cliente'], ['class' => 'cli-360-link', 'data-turbo' => 'false']) ?></div>
			<?php endif; ?>
		</div>
		<div class="cli-360-kpi cli-360-kpi--score<?= empty($finCrm['has_score']) ? ' cli-360-kpi--muted' : '' ?>">
			<div class="cli-360-kpi-lbl"><?= h(__('Score interno')) ?></div>
			<div class="cli-360-kpi-val"><?= !empty($finCrm['has_score']) ? h((string)$finCrm['score_fmt']) . ' / 10' : h(__('—')) ?></div>
			<div class="cli-360-kpi-sub"><?= !empty($finCrm['has_score']) ? h(__('Cadastro do cliente')) : h(__('Não informado')) ?></div>
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

	<div class="cli-360-tab-panel">
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
			<?php $contatos = (array)($c['contatos'] ?? []); ?>
			<div class="cli-360-card">
				<div class="cli-360-card-head">
					<span class="cli-360-card-title"><i class="fas fa-users" aria-hidden="true"></i> <?= h(__('Contatos')) ?></span>
					<?= $this->Html->link(__('Adicionar'), ['action' => 'edit', $cliente->id, '?' => ['wizard' => 2, 'contato' => 1], '#' => 'cliente'], ['class' => 'cli-360-link', 'data-turbo' => 'false']) ?>
				</div>
				<?php if ($contatos === []) : ?>
				<p class="cli-360-empty"><?= h(__('Nenhum contato cadastrado. Inclua responsável e e-mails na ficha.')) ?></p>
				<?php else : ?>
				<ul class="cli-360-contatos-list">
					<?php foreach ($contatos as $ct) : ?>
					<li class="cli-360-contato">
						<div class="cli-av cli-av--<?= h((string)($ct['av_tone'] ?? 'teal')) ?>"><?= h((string)($ct['iniciais'] ?? 'C')) ?></div>
						<div class="cli-360-contato-body">
							<div class="cli-360-contato-name"><?= h((string)$ct['nome']) ?></div>
							<?php if (!empty($ct['cargo'])) : ?><div class="cli-360-contato-role"><?= h((string)$ct['cargo']) ?></div><?php endif; ?>
							<div class="cli-360-contato-meta">
								<?php if (!empty($ct['email'])) : ?><a href="mailto:<?= h((string)$ct['email']) ?>"><?= h((string)$ct['email']) ?></a><?php endif; ?>
								<?php if (!empty($ct['fone'])) : ?><?= !empty($ct['email']) ? ' · ' : '' ?><?= h((string)$ct['fone']) ?><?php endif; ?>
							</div>
						</div>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div>
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
					<?= $this->Html->link('<i class="fas fa-edit"></i> ' . __('Editar cadastro'), ['action' => 'edit', $cliente->id, '#' => 'cliente'], ['class' => 'cli-360-shortcut', 'escape' => false, 'data-turbo' => 'false']) ?>
					<?= $this->Html->link('<i class="fas fa-file-contract"></i> ' . __('Contratos') . ' (' . (int)($counts['contratos'] ?? 0) . ')', ['action' => 'edit', $cliente->id, '#' => 'contratos'], ['class' => 'cli-360-shortcut', 'escape' => false, 'data-turbo' => 'false', 'title' => __('Tabela e botões de contrato na ficha')]) ?>
					<?= $this->Html->link('<i class="fas fa-users"></i> ' . __('Usuários'), ['action' => 'edit', $cliente->id, '#' => 'usuarios'], ['class' => 'cli-360-shortcut', 'escape' => false, 'data-turbo' => 'false']) ?>
					<?= $this->Html->link('<i class="fas fa-desktop"></i> ' . __('Acessos'), ['action' => 'edit', $cliente->id, '#' => 'acessos'], ['class' => 'cli-360-shortcut', 'escape' => false, 'data-turbo' => 'false']) ?>
					<?= $this->Html->link('<i class="fas fa-key"></i> ' . __('Token API'), ['action' => 'edit', $cliente->id, '#' => 'token'], ['class' => 'cli-360-shortcut', 'escape' => false, 'data-turbo' => 'false']) ?>
					<?= $this->Html->link('<i class="fas fa-headset"></i> ' . __('Tickets') . ' (' . (int)($counts['tickets_abertos'] ?? 0) . ')', ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'cli-360-shortcut', 'escape' => false, 'data-turbo' => 'false']) ?>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php if ($tab === 'historico') : ?>
	<div class="cli-360-card cli-360-card--full cli-360-card--in-panel">
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
	<div class="cli-360-card cli-360-card--full cli-360-card--in-panel">
		<?php if ($tab === 'contratos') : ?>
		<p class="cli-360-ficha-link mb-3">
			<?= $this->Html->link(
				'<i class="fas fa-external-link-alt" aria-hidden="true"></i> ' . __('Gerenciar contratos na ficha (cadastrar item, horas técnicas, situação)'),
				['action' => 'edit', $cliente->id, '#' => 'contratos'],
				['class' => 'cli-360-link', 'escape' => false, 'data-turbo' => 'false']
			) ?>
		</p>
		<?php endif; ?>
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
	<?php
		$nArq = (int)($counts['arquivos'] ?? 0);
		$arqList = (array)($c['arquivos_list'] ?? []);
		$arqFiltros = (array)($c['arquivos_filtros'] ?? []);
		$pillDefs = [
			'todos' => ['label' => __('Todos'), 'icon' => 'fa-layer-group'],
			'tickets' => ['label' => __('Tickets'), 'icon' => 'fa-headset'],
			'financeiro' => ['label' => __('Financeiro'), 'icon' => 'fa-coins'],
			'fotos' => ['label' => __('Fotos'), 'icon' => 'fa-image'],
			'pdf' => ['label' => __('PDF'), 'icon' => 'fa-file-pdf'],
			'doc' => ['label' => __('Documentos'), 'icon' => 'fa-file-alt'],
		];
	?>
	<div class="cli-360-arq-head">
		<h2 class="cli-360-arq-title"><i class="fas fa-paperclip" aria-hidden="true"></i> <?= h(__('Arquivos e documentos')) ?> · <?= $nArq ?> <?= h(__('itens')) ?></h2>
		<?= $this->Html->link(
			'<i class="fas fa-paperclip" aria-hidden="true"></i> ' . __('Anexar via ticket'),
			['controller' => 'Tickets', 'action' => 'index'],
			['class' => 'btn-cli-primary btn-cli-sm', 'escape' => false, 'data-turbo' => 'false']
		) ?>
	</div>
	<p class="cli-360-arq-note"><?= h(__('Anexos reais de tickets e comprovantes em lançamentos financeiros deste cliente.')) ?></p>
	<?php if ($nArq > 0) : ?>
	<div class="cli-360-arq-filters" id="cli-360-arq-filters" role="group" aria-label="<?= h(__('Filtrar arquivos')) ?>">
		<?php foreach ($pillDefs as $fKey => $fMeta) :
			$fc = (int)($arqFiltros[$fKey] ?? 0);
			if ($fKey !== 'todos' && $fc === 0) {
				continue;
			}
		?>
		<button type="button" class="cli-360-arq-pill<?= $fKey === 'todos' ? ' active' : '' ?>" data-arq-filter="<?= h($fKey) ?>">
			<i class="fas <?= h($fMeta['icon']) ?>" aria-hidden="true"></i>
			<?= h($fMeta['label']) ?> <span class="cnt">(<?= $fKey === 'todos' ? $nArq : $fc ?>)</span>
		</button>
		<?php endforeach; ?>
	</div>
	<div class="cli-360-arq-grid" id="cli-360-arq-grid">
		<?php foreach ($arqList as $arq) :
			$dataFilter = (string)($arq['filtro'] ?? 'doc');
			$origem = (string)($arq['origem'] ?? '');
			$filterKeys = 'todos ' . $dataFilter . ($origem !== '' ? ' ' . $origem : '');
			$iconTone = (string)($arq['icon_tone'] ?? 'doc');
			$iconClass = (string)($arq['icon'] ?? 'fa-file');
			$url = $arq['url'] ?? null;
		?>
		<?php if ($url) : ?>
		<a href="<?= h($url) ?>" class="cli-360-arq-file" data-arq-cats="<?= h($filterKeys) ?>" data-turbo="false">
		<?php else : ?>
		<div class="cli-360-arq-file" data-arq-cats="<?= h($filterKeys) ?>">
		<?php endif; ?>
			<div class="cli-360-arq-file-icon cli-360-arq-file-icon--<?= h($iconTone) ?>"><i class="fas <?= h($iconClass) ?>" aria-hidden="true"></i></div>
			<div class="cli-360-arq-file-name"><?= h((string)($arq['label'] ?? '')) ?></div>
			<div class="cli-360-arq-file-sub"><?= h((string)($arq['sub'] ?? '')) ?></div>
			<?php if (!empty($arq['data_fmt'])) : ?>
			<div class="cli-360-arq-file-date"><?= h((string)$arq['data_fmt']) ?></div>
			<?php endif; ?>
		<?php if ($url) : ?>
		</a>
		<?php else : ?>
		</div>
		<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php else : ?>
	<p class="cli-360-arq-empty"><?= h(__('Nenhum arquivo vinculado encontrado.')) ?></p>
	<?php endif; ?>
	<p class="mt-3 mb-0">
		<?= $this->Html->link(__('Editar cadastro do cliente'), ['action' => 'edit', $cliente->id], ['class' => 'btn-cli-secondary', 'data-turbo' => 'false']) ?>
	</p>
	<?php endif; ?>

	</div><!-- /cli-360-tab-panel -->

</div>
</div>
<script>
(function () {
	var root = document.getElementById('cli-360-arq-filters');
	var grid = document.getElementById('cli-360-arq-grid');
	if (!root || !grid) return;
	root.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-arq-filter]');
		if (!btn) return;
		var f = btn.getAttribute('data-arq-filter') || 'todos';
		root.querySelectorAll('.cli-360-arq-pill').forEach(function (p) {
			p.classList.toggle('active', p === btn);
		});
		grid.querySelectorAll('.cli-360-arq-file').forEach(function (card) {
			var cats = (card.getAttribute('data-arq-cats') || '').split(/\s+/);
			var show = f === 'todos' || cats.indexOf(f) >= 0;
			if (show) {
				card.removeAttribute('hidden');
			} else {
				card.setAttribute('hidden', 'hidden');
			}
		});
	});
})();
</script>
