<?php
/**
 * Base de conhecimento (mockup pg-sd-kb).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$kb = (array)($screen['kb'] ?? []);
$stats = (array)($kb['stats'] ?? []);
$articles = (array)($kb['articles'] ?? []);
$filterCats = (array)($kb['filter_categorias'] ?? []);
$title = (string)($screen['title'] ?? __('Base de conhecimento'));
$subtitle = (string)($screen['subtitle'] ?? '');
$links = (array)($screen['links'] ?? []);
$H = $this->ServicedeskPrototype;

$toolbar = [];
foreach ($links as $lnk) {
	if (!empty($lnk['label']) && !empty($lnk['url'])) {
		$toolbar[] = [
			'label' => (string)$lnk['label'],
			'url' => $lnk['url'],
			'class' => (string)($lnk['class'] ?? 'btn btn-ghost btn-sm'),
		];
	}
}

$kpis = [
	[
		'lbl' => __('Total publicados'),
		'val' => (string)($stats['total_publicados'] ?? count($articles)),
		'border' => 'var(--teal)',
		'val_color' => 'var(--teal-dark)',
	],
	[
		'lbl' => __('Visualizações 30d'),
		'val' => (string)($stats['visualizacoes_30d'] ?? '—'),
		'border' => 'var(--blue)',
		'val_color' => '#0C447C',
	],
	[
		'lbl' => __('Aplicados em tickets'),
		'val' => (string)($stats['aplicados_tickets'] ?? '—'),
		'border' => '#D946A0',
		'val_color' => '#7A1B5C',
	],
	[
		'lbl' => __('Avaliação média'),
		'val' => (string)($stats['avaliacao_media'] ?? '—'),
		'border' => '#6B5B95',
		'val_color' => '#3D2D63',
	],
	[
		'lbl' => __('Pendentes revisão'),
		'val' => (string)($stats['pendentes_revisao'] ?? '0'),
		'border' => 'var(--amber)',
		'val_color' => '#8A4D02',
		'bg' => '#FAEEDA',
	],
	[
		'lbl' => __('% auto-resolução'),
		'val' => (string)($stats['auto_resolucao_pct'] ?? '—'),
		'border' => 'var(--teal-mid)',
		'val_color' => 'var(--teal-dark)',
	],
];
?>
<div id="pg-sd-kb" class="sdp-kb-page">
	<?= $this->element('ServicedeskPrototype/ref/header', compact('title', 'subtitle', 'toolbar') + ['eyebrow' => __('Service Desk')]) ?>

	<div class="summary-grid sdp-kb-kpis">
		<?php foreach ($kpis as $k) : ?>
			<?php
			$style = 'border-left:3px solid ' . ($k['border'] ?? 'var(--teal)') . ';';
			if (!empty($k['bg'])) {
				$style .= 'background:' . $k['bg'] . ';';
			}
			?>
			<div class="summary-card" style="<?= h($style) ?>">
				<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
				<div class="val" style="color:<?= h((string)($k['val_color'] ?? 'var(--teal-dark)')) ?>;"><?= h((string)($k['val'] ?? '')) ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="card sdp-kb-filters">
		<div class="sdp-kb-filters-row">
			<input type="search" class="sdp-kb-search" placeholder="<?= h(__('🔍 Buscar por título, tag ou conteúdo...')) ?>" autocomplete="off" />
			<select class="sdp-kb-select" aria-label="<?= h(__('Categoria')) ?>">
				<?php foreach ($filterCats as $cat) : ?>
					<option><?= h((string)$cat) ?></option>
				<?php endforeach; ?>
			</select>
			<select class="sdp-kb-select" aria-label="<?= h(__('Visibilidade')) ?>">
				<option><?= h(__('Todos · público + interno')) ?></option>
				<option><?= h(__('🌐 Público (cliente vê)')) ?></option>
				<option><?= h(__('🔒 Interno (só técnicos)')) ?></option>
			</select>
			<select class="sdp-kb-select" aria-label="<?= h(__('Ordenação')) ?>">
				<option><?= h(__('Mais usados')) ?></option>
				<option><?= h(__('Mais recentes')) ?></option>
				<option><?= h(__('Melhor avaliados')) ?></option>
			</select>
		</div>
	</div>

	<div class="sdp-kb-articles">
		<?php foreach ($articles as $art) : ?>
			<?php
			$code = (string)($art['code'] ?? '');
			$interno = (($art['visibilidade'] ?? '') === 'interno');
			$cardBg = (string)($art['card_bg'] ?? '');
			$url = $H->sdpPage('detalhe-kb');
			$tags = (array)($art['tags'] ?? []);
			$revisar = (string)($art['revisar'] ?? '');
			$rating = (string)($art['rating'] ?? '');
			$votos = (int)($art['votos'] ?? 0);
			$views = (int)($art['views'] ?? 0);
			$ticketUses = (int)($art['tickets'] ?? 0);
			$linkOpts = ['class' => 'card sdp-kb-article-card', 'escape' => false];
			if ($cardBg !== '') {
				$linkOpts['style'] = 'background:' . $cardBg;
			}
			$body = '<div class="sdp-kb-card-head">'
				. '<span class="sdp-kb-badge' . ($interno ? ' sdp-kb-badge-internal' : ' sdp-kb-badge-public') . '">'
				. ($interno ? '🔒 ' . h(__('Interno')) : '🌐 ' . h(__('Público')))
				. '</span>'
				. '<span class="sdp-kb-code">' . h($code) . '</span>'
				. '</div>'
				. '<div class="sdp-kb-card-title">' . h((string)($art['titulo'] ?? '')) . '</div>'
				. '<div class="sdp-kb-card-resumo">' . h((string)($art['resumo'] ?? '')) . '</div>';
			if ($tags !== []) {
				$body .= '<div class="sdp-kb-tags">';
				foreach ($tags as $t) {
					$body .= '<span class="sdp-kb-tag">' . h((string)$t) . '</span>';
				}
				$body .= '</div>';
			}
			$body .= '<div class="sdp-kb-card-foot"><span>';
			if ($revisar !== '') {
				$body .= '<span class="sdp-kb-revisar">⚠ ' . h($revisar) . '</span>';
			} elseif ($rating !== '') {
				$body .= '⭐ ' . h($rating) . ' (' . h((string)$votos) . ' ' . h(__('votos')) . ')';
			}
			$body .= '</span><span>👁 ' . h((string)$views) . ' · 🔗 ' . h((string)$ticketUses) . ' ' . h(__('tickets')) . '</span></div>';
			?>
			<?= $this->Html->link($body, $url, $linkOpts) ?>
		<?php endforeach; ?>
	</div>
</div>
