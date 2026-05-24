<?php
/**
 * Lista de orçamentos — mockup pg-lista (pgm_erp_completo.html).
 *
 * @var \App\View\AppView $this
 * @var array{pendente:int,enviado:int,aprovado:int,recusado:int,total:int} $orcCounts
 * @var array<int,array<string,mixed>> $orcItems
 * @var array<int,string> $orcStatusLabels
 */
$H = $this->ErpPrototype;
$counts = (array)($orcCounts ?? []);
$items = (array)($orcItems ?? []);
$labels = (array)($orcStatusLabels ?? []);
$badgeMap = ['pend' => 'pend', 'env' => 'env', 'aprov' => 'aprov', 'recus' => 'recus'];
?>
<div id="pg-lista">
<?= $this->element('ErpPrototype/page_header', [
	'eyebrow' => __('Módulo comercial'),
	'title' => __('Orçamentos'),
	'subtitle' => __('Propostas comerciais · revisão · assinatura · faturamento'),
	'actions' => [
		['label' => __('Módulo clássico'), 'url' => ['controller' => 'Orcamentos', 'action' => 'index'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg> ' . __('Gerar Orçamento'), 'url' => ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo'], 'class' => 'btn btn-primary'],
	],
]) ?>

<div class="stats" id="orc-lista-stats">
	<div class="stat active" style="--sc:var(--amber);" data-orc-stat="pend">
		<div class="stat-l"><?= h(__('Andamento')) ?></div>
		<div class="stat-n" id="cnt-pend"><?= (int)($counts['pendente'] ?? 0) ?></div>
	</div>
	<div class="stat" style="--sc:var(--teal);" data-orc-stat="env">
		<div class="stat-l"><?= h(__('Enviados')) ?></div>
		<div class="stat-n" id="cnt-env"><?= (int)($counts['enviado'] ?? 0) ?></div>
	</div>
	<div class="stat" style="--sc:var(--blue);" data-orc-stat="aprov">
		<div class="stat-l"><?= h(__('Aprovados')) ?></div>
		<div class="stat-n" id="cnt-aprov"><?= (int)($counts['aprovado'] ?? 0) ?></div>
	</div>
	<div class="stat" style="--sc:var(--red);" data-orc-stat="recus">
		<div class="stat-l"><?= h(__('Recusados')) ?></div>
		<div class="stat-n" id="cnt-recus"><?= (int)($counts['recusado'] ?? 0) ?></div>
	</div>
	<div class="stat" style="--sc:#888780;" data-orc-stat="all">
		<div class="stat-l"><?= h(__('Total')) ?></div>
		<div class="stat-n" id="cnt-all"><?= (int)($counts['total'] ?? 0) ?></div>
	</div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div class="pg-lista-toolbar">
		<div class="tabs" style="margin:0;">
			<button type="button" class="tab active" data-orc-tab="pend"><?= h(__('Pendentes')) ?></button>
			<button type="button" class="tab" data-orc-tab="env"><?= h(__('Enviados')) ?></button>
			<button type="button" class="tab" data-orc-tab="aprov"><?= h(__('Aprovados')) ?></button>
			<button type="button" class="tab" data-orc-tab="recus"><?= h(__('Recusados')) ?></button>
			<button type="button" class="tab" data-orc-tab="all"><?= h(__('Todos')) ?></button>
		</div>
		<div style="display:flex;align-items:center;gap:8px;">
			<div class="pg-search-box">
				<svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
				<input type="text" id="orc-lista-q" placeholder="<?= h(__('Buscar...')) ?>" autocomplete="off">
			</div>
			<span style="font-size:11px;color:var(--text-muted);" id="lista-count"><?= count($items) ?> <?= h(__('registros')) ?></span>
		</div>
	</div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead>
				<tr>
					<th style="width:65px;"><?= h(__('ID')) ?></th>
					<th><?= h(__('Empresa')) ?></th>
					<th style="width:75px;"><?= h(__('Versão')) ?></th>
					<th style="width:85px;"><?= h(__('Status')) ?></th>
					<th class="r" style="width:100px;"><?= h(__('Total')) ?></th>
					<th class="r" style="width:80px;"><?= h(__('Margem')) ?></th>
					<th style="width:95px;"><?= h(__('Data')) ?></th>
					<th style="width:90px;"><?= h(__('Ações')) ?></th>
				</tr>
			</thead>
			<tbody id="lista-body">
				<?php if ($items === []) : ?>
					<tr class="orc-lista-empty"><td colspan="8" style="text-align:center;padding:28px;color:var(--text-muted);"><?= h(__('Nenhum orçamento encontrado.')) ?></td></tr>
				<?php else : foreach ($items as $it) :
					$stKey = (string)($it['st_key'] ?? 'pend');
					$st = (int)($it['status'] ?? 0);
					$lbl = (string)($labels[$st] ?? '—');
					$badge = $badgeMap[$stKey] ?? 'arq';
					$href = $this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', (int)$it['id']]);
					$m = $it['margem_pct'] ?? null;
					$mc = '#6b6a65';
					if ($m !== null && is_numeric($m)) {
						$m = (int)$m;
						$mc = $m > 30 ? 'var(--teal)' : ($m > 15 ? 'var(--amber)' : 'var(--red)');
						$margTxt = $m . '%';
					} else {
						$margTxt = '—';
					}
					$emp = (string)($it['empresa'] ?? $it['cliente'] ?? '—');
					?>
					<tr class="orc-lista-row" data-st="<?= h($stKey) ?>" data-q="<?= h(mb_strtolower($emp . ' ' . (int)$it['id'])) ?>" data-href="<?= h($href) ?>" tabindex="0">
						<td style="font-weight:600;color:var(--teal);">#<?= (int)$it['id'] ?></td>
						<td>
							<div style="display:flex;align-items:center;gap:8px;">
								<div class="av"><?= h($H->initials($emp)) ?></div>
								<span style="color:var(--text);font-weight:500;"><?= h(\Cake\Utility\Text::truncate($emp, 48, ['ellipsis' => '…'])) ?></span>
							</div>
						</td>
						<td><span class="badge b-v"><?= h((string)($it['versao'] ?? 'v1')) ?></span></td>
						<td><?= $H->badge($lbl, $badge) ?></td>
						<td class="r" style="font-weight:600;"><?= h($H->brl((float)$it['valor'])) ?></td>
						<td class="r"><span style="font-weight:700;color:<?= h($mc) ?>;"><?= h($margTxt) ?></span></td>
						<td class="mu" style="color:var(--text);"><?= h($H->dt($it['modified'])) ?></td>
						<td>
							<div style="display:flex;gap:4px;flex-wrap:nowrap;">
								<?= $this->Html->link(__('Revisar'), ['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', (int)$it['id']], ['class' => 'btn btn-primary btn-xs', 'onclick' => 'event.stopPropagation();', 'data-turbo' => 'false']) ?>
								<?= $this->Html->link(__('Editar'), ['controller' => 'Orcamentos', 'action' => 'edit', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs', 'onclick' => 'event.stopPropagation();', 'data-turbo' => 'false']) ?>
							</div>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
</div>

<script>
(function () {
	var cur = 'pend';
	var rows = Array.prototype.slice.call(document.querySelectorAll('#lista-body .orc-lista-row'));
	var stats = document.querySelectorAll('#orc-lista-stats .stat');
	var tabs = document.querySelectorAll('[data-orc-tab]');
	var q = document.getElementById('orc-lista-q');
	var countEl = document.getElementById('lista-count');

	function applyFilter () {
		var term = q ? q.value.toLowerCase().trim() : '';
		var visible = 0;
		rows.forEach(function (tr) {
			var st = tr.getAttribute('data-st') || '';
			var matchSt = cur === 'all' || st === cur;
			var matchQ = !term || (tr.getAttribute('data-q') || '').indexOf(term) >= 0;
			var show = matchSt && matchQ;
			tr.style.display = show ? '' : 'none';
			if (show) visible++;
		});
		if (countEl) {
			countEl.textContent = visible + ' <?= h(__('registros')) ?>';
		}
		var empty = document.querySelector('#lista-body .orc-lista-empty');
		if (empty) {
			empty.style.display = visible === 0 && rows.length === 0 ? '' : 'none';
		}
	}

	function setFilter (s, statEl, tabEl) {
		cur = s;
		stats.forEach(function (e) { e.classList.remove('active'); });
		if (statEl) statEl.classList.add('active');
		tabs.forEach(function (t) { t.classList.remove('active'); });
		if (tabEl) tabEl.classList.add('active');
		applyFilter();
	}

	stats.forEach(function (el) {
		el.addEventListener('click', function () {
			setFilter(el.getAttribute('data-orc-stat') || 'all', el, document.querySelector('[data-orc-tab="' + (el.getAttribute('data-orc-stat') || '') + '"]'));
		});
	});
	tabs.forEach(function (el) {
		el.addEventListener('click', function () {
			setFilter(el.getAttribute('data-orc-tab') || 'all', document.querySelector('[data-orc-stat="' + (el.getAttribute('data-orc-tab') || '') + '"]'), el);
		});
	});
	if (q) {
		q.addEventListener('input', applyFilter);
	}
	rows.forEach(function (tr) {
		tr.addEventListener('click', function (e) {
			if (e.target.closest('a,button')) return;
			var href = tr.getAttribute('data-href');
			if (href) window.location.href = href;
		});
	});
	applyFilter();
})();
</script>
