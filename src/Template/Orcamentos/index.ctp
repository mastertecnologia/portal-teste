<?php
$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));

$totais = isset($totais) && is_array($totais) ? $totais : [];
$tEm = (int)($totais['em_andamento'] ?? count($orcamentosPendentes ?? []));
$tEn = (int)($totais['enviados'] ?? count($orcamentosEnviados ?? []));
$tAp = (int)($totais['aprovados'] ?? count($orcamentosAprovados ?? []));
$tRe = (int)($totais['recusados'] ?? count($orcamentosRecusados ?? []));
$tAr = (int)($totais['arquivados'] ?? count($orcamentosArquivados ?? []));
$valorTotalPorOrcamentoId = isset($valorTotalPorOrcamentoId) && is_array($valorTotalPorOrcamentoId) ? $valorTotalPorOrcamentoId : [];
$versaoRotuloPorOrcamentoId = isset($versaoRotuloPorOrcamentoId) && is_array($versaoRotuloPorOrcamentoId) ? $versaoRotuloPorOrcamentoId : [];
$margemBrutaPctPorOrcamentoId = isset($margemBrutaPctPorOrcamentoId) && is_array($margemBrutaPctPorOrcamentoId) ? $margemBrutaPctPorOrcamentoId : [];

$orcPremiumIniciais = function ($text) {
	$text = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$text)));
	if ($text === '') {
		return '—';
	}
	$parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
	$first = $parts[0] ?? '';
	$second = $parts[1] ?? '';
	$sub = function ($str, $i, $len) {
		if (function_exists('mb_substr')) {
			return mb_substr($str, $i, $len, 'UTF-8');
		}

		return substr($str, $i, $len);
	};
	$a = strtoupper($sub($first, 0, 1));
	$b = $second !== '' ? strtoupper($sub($second, 0, 1)) : strtoupper($sub($first, 1, 1));

	return $a . $b;
};

$orcPremiumBadge = function ($status) {
	$map = [
		0 => ['orc-badge--pend', 'Em andamento'],
		1 => ['orc-badge--env', 'Enviado'],
		2 => ['orc-badge--aprov', 'Aprovado'],
		3 => ['orc-badge--recus', 'Recusado'],
		4 => ['orc-badge--arq', 'Arquivado'],
	];
	$s = (int)$status;
	$row = $map[$s] ?? ['orc-badge--arq', '—'];

	return '<span class="orc-badge ' . $row[0] . '">' . h($row[1]) . '</span>';
};

$orcPremiumFmtValor = function ($id) use ($valorTotalPorOrcamentoId) {
	$v = isset($valorTotalPorOrcamentoId[$id]) ? (float)$valorTotalPorOrcamentoId[$id] : 0.0;
	if ($v <= 0) {
		return '—';
	}

	return 'R$ ' . number_format($v, 2, ',', '.');
};

$orcPremiumVersao = function ($id) use ($versaoRotuloPorOrcamentoId) {
	$id = (int)$id;
	$v = $versaoRotuloPorOrcamentoId[$id] ?? 'v1';

	return is_string($v) && $v !== '' ? $v : 'v1';
};

$orcPremiumMargemHtml = function ($id) use ($margemBrutaPctPorOrcamentoId) {
	$id = (int)$id;
	$m = $margemBrutaPctPorOrcamentoId[$id] ?? null;
	if ($m === null) {
		return '<span class="orc-premium-muted">—</span>';
	}
	$cls = $m > 30 ? 'orc-premium-margem--good' : ($m > 15 ? 'orc-premium-margem--warn' : 'orc-premium-margem--bad');

	return '<span class="orc-premium-margem ' . $cls . '">' . h((string)$m) . '%</span>';
};

$orcPremiumMargemOrder = function ($id) use ($margemBrutaPctPorOrcamentoId) {
	$m = $margemBrutaPctPorOrcamentoId[(int)$id] ?? null;

	return $m === null ? -1 : (int)$m;
};

$totalListaAdmin = $tEm + $tEn + $tAp + $tRe + $tAr;
?>
<div class="col-md-12 orc-premium-page-root">
<div id="orc-premium-container" class="orc-premium-wrap orc-premium-index">
	<?php if ((int)($role ?? 1) === 0) : ?>
		<header class="orc-premium-page-head">
			<div class="orc-premium-page-head-text">
				<p class="orc-premium-eyebrow">Módulo comercial</p>
				<h1 class="orc-premium-h1">Orçamentos</h1>
			</div>
			<?= $this->Html->link(
				'<svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg> + Gerar Orçamento',
				['action' => 'add'],
				['class' => 'btn orc-premium-btn-primary', 'escape' => false, 'target' => '_blank', 'rel' => 'noopener noreferrer']
			) ?>
		</header>

		<div class="orc-premium-stats" role="tablist">
			<button type="button" class="orc-premium-stat active" style="--orc-sc:#FFC107;" data-orc-tab="pendentes" aria-selected="true">
				<span class="orc-premium-stat-l">Andamento</span>
				<span class="orc-premium-stat-n"><?= $tEm ?></span>
			</button>
			<button type="button" class="orc-premium-stat" style="--orc-sc:#00C08B;" data-orc-tab="enviados" aria-selected="false">
				<span class="orc-premium-stat-l">Enviados</span>
				<span class="orc-premium-stat-n"><?= $tEn ?></span>
			</button>
			<button type="button" class="orc-premium-stat" style="--orc-sc:#33CCFF;" data-orc-tab="aprovados" aria-selected="false">
				<span class="orc-premium-stat-l">Aprovados</span>
				<span class="orc-premium-stat-n"><?= $tAp ?></span>
			</button>
			<button type="button" class="orc-premium-stat" style="--orc-sc:#E24B4A;" data-orc-tab="recusados" aria-selected="false">
				<span class="orc-premium-stat-l">Recusados</span>
				<span class="orc-premium-stat-n"><?= $tRe ?></span>
			</button>
			<button type="button" class="orc-premium-stat" style="--orc-sc:#888780;" data-orc-tab="arquivados" aria-selected="false">
				<span class="orc-premium-stat-l">Arquivados</span>
				<span class="orc-premium-stat-n"><?= $tAr ?></span>
			</button>
		</div>

		<div class="orc-premium-list-card orc-premium-list-card--admin">
			<div class="orc-premium-list-toolbar">
				<div class="orc-premium-tabs-strip" role="tablist">
					<a class="orc-premium-tab-pill active" data-toggle="tab" href="#pendentes" role="tab" aria-controls="pendentes" aria-selected="true">Pendentes</a>
					<a class="orc-premium-tab-pill" data-toggle="tab" href="#enviados" role="tab" aria-controls="enviados" aria-selected="false">Enviados</a>
					<a class="orc-premium-tab-pill" data-toggle="tab" href="#aprovados" role="tab" aria-controls="aprovados" aria-selected="false">Aprovados</a>
					<a class="orc-premium-tab-pill" data-toggle="tab" href="#recusados" role="tab" aria-controls="recusados" aria-selected="false">Recusados</a>
					<a class="orc-premium-tab-pill" data-toggle="tab" href="#arquivados" role="tab" aria-controls="arquivados" aria-selected="false">Arquivados</a>
				</div>
				<div class="orc-premium-toolbar-right">
					<label class="orc-premium-search" for="orc-list-search">
						<svg class="orc-premium-search-ic" width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
						<input type="search" id="orc-list-search" placeholder="Buscar…" autocomplete="off" />
					</label>
					<span class="orc-premium-list-meta"><?= (int)$totalListaAdmin ?> registro(s) no total</span>
				</div>
			</div>

			<div class="tab-content orc-premium-tab-panels">
				<div class="tab-pane fade show active" id="pendentes" role="tabpanel">
					<div class="table-responsive orc-premium-tbl-wrap">
						<table class="table orc-premium-tbl" id="tablePendentes">
							<thead>
								<tr>
									<th style="width:65px;">ID</th>
									<th>Empresa</th>
									<th style="width:75px;">Versão</th>
									<th style="width:85px;">Status</th>
									<th class="text-right orc-premium-th-r" style="width:100px;">Total</th>
									<th class="text-right orc-premium-th-r" style="width:80px;">Margem</th>
									<th style="width:95px;">Data</th>
									<th style="width:90px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($orcamentosPendentes as $reg) :
									$nomeCli = !empty($reg->cliente->razaosocial) ? $reg->cliente->razaosocial : $reg->cliente->nome;
									$editUrl = $this->Url->build(['action' => 'edit', $reg->id]);
									$ini = $orcPremiumIniciais($nomeCli);
									?>
									<tr>
										<td data-order="<?= (int)$reg->id ?>"><a class="orc-premium-link orc-premium-id" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>">#<?= h($reg->id) ?></a></td>
										<td>
											<div class="orc-premium-empresa-cell">
												<span class="orc-premium-av" aria-hidden="true"><?= h($ini) ?></span>
												<a class="orc-premium-link orc-premium-empresa-name" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>"><?= h($nomeCli) ?></a>
											</div>
										</td>
										<td><span class="orc-premium-ver"><?= h($orcPremiumVersao($reg->id)) ?></span></td>
										<td><?= $orcPremiumBadge($reg->status) ?></td>
										<td class="text-right orc-premium-valor"><?= h($orcPremiumFmtValor($reg->id)) ?></td>
										<td class="text-right orc-premium-margem-cell" data-order="<?= (int)$orcPremiumMargemOrder($reg->id) ?>"><?= $orcPremiumMargemHtml($reg->id) ?></td>
										<td class="orc-premium-muted"><a class="orc-premium-link" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>"><?= @date_format($reg->created, 'd/m/Y') ?></a></td>
										<td>
											<?= $this->Html->link('Editar', ['action' => 'edit', $reg->id], ['class' => 'btn btn-sm orc-premium-btn-ghost', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane fade" id="enviados" role="tabpanel">
					<div class="table-responsive orc-premium-tbl-wrap">
						<table class="table orc-premium-tbl" id="tableEnviados">
							<thead>
								<tr>
									<th style="width:65px;">ID</th>
									<th>Empresa</th>
									<th style="width:75px;">Versão</th>
									<th style="width:85px;">Status</th>
									<th class="text-right orc-premium-th-r" style="width:100px;">Total</th>
									<th class="text-right orc-premium-th-r" style="width:80px;">Margem</th>
									<th style="width:95px;">Data</th>
									<th style="width:90px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($orcamentosEnviados as $reg) :
									$nomeCli = !empty($reg->cliente->razaosocial) ? $reg->cliente->razaosocial : $reg->cliente->nome;
									$editUrl = $this->Url->build(['action' => 'edit', $reg->id]);
									$ini = $orcPremiumIniciais($nomeCli);
									?>
									<tr>
										<td data-order="<?= (int)$reg->id ?>"><a class="orc-premium-link orc-premium-id" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>">#<?= h($reg->id) ?></a></td>
										<td>
											<div class="orc-premium-empresa-cell">
												<span class="orc-premium-av" aria-hidden="true"><?= h($ini) ?></span>
												<a class="orc-premium-link orc-premium-empresa-name" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>"><?= h($nomeCli) ?></a>
											</div>
										</td>
										<td><span class="orc-premium-ver"><?= h($orcPremiumVersao($reg->id)) ?></span></td>
										<td><?= $orcPremiumBadge($reg->status) ?></td>
										<td class="text-right orc-premium-valor"><?= h($orcPremiumFmtValor($reg->id)) ?></td>
										<td class="text-right orc-premium-margem-cell" data-order="<?= (int)$orcPremiumMargemOrder($reg->id) ?>"><?= $orcPremiumMargemHtml($reg->id) ?></td>
										<td class="orc-premium-muted"><a class="orc-premium-link" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>"><?= @date_format($reg->created, 'd/m/Y') ?></a></td>
										<td>
											<?= $this->Html->link('Editar', ['action' => 'edit', $reg->id], ['class' => 'btn btn-sm orc-premium-btn-ghost', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane fade" id="aprovados" role="tabpanel">
					<div class="table-responsive orc-premium-tbl-wrap">
						<table class="table orc-premium-tbl" id="tableAprovados">
							<thead>
								<tr>
									<th style="width:65px;">ID</th>
									<th>Empresa</th>
									<th style="width:75px;">Versão</th>
									<th style="width:85px;">Status</th>
									<th class="text-right orc-premium-th-r" style="width:100px;">Total</th>
									<th class="text-right orc-premium-th-r" style="width:80px;">Margem</th>
									<th style="width:95px;">Data</th>
									<th style="width:90px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($orcamentosAprovados as $reg) :
									$nomeCli = !empty($reg->cliente->razaosocial) ? $reg->cliente->razaosocial : $reg->cliente->nome;
									$editUrl = $this->Url->build(['action' => 'edit', $reg->id]);
									$ini = $orcPremiumIniciais($nomeCli);
									?>
									<tr>
										<td data-order="<?= (int)$reg->id ?>"><a class="orc-premium-link orc-premium-id" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>">#<?= h($reg->id) ?></a></td>
										<td>
											<div class="orc-premium-empresa-cell">
												<span class="orc-premium-av" aria-hidden="true"><?= h($ini) ?></span>
												<a class="orc-premium-link orc-premium-empresa-name" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>"><?= h($nomeCli) ?></a>
											</div>
										</td>
										<td><span class="orc-premium-ver"><?= h($orcPremiumVersao($reg->id)) ?></span></td>
										<td><?= $orcPremiumBadge($reg->status) ?></td>
										<td class="text-right orc-premium-valor"><?= h($orcPremiumFmtValor($reg->id)) ?></td>
										<td class="text-right orc-premium-margem-cell" data-order="<?= (int)$orcPremiumMargemOrder($reg->id) ?>"><?= $orcPremiumMargemHtml($reg->id) ?></td>
										<td class="orc-premium-muted"><a class="orc-premium-link" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>"><?= @date_format($reg->created, 'd/m/Y') ?></a></td>
										<td>
											<?= $this->Html->link('Editar', ['action' => 'edit', $reg->id], ['class' => 'btn btn-sm orc-premium-btn-ghost', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane fade" id="recusados" role="tabpanel">
					<div class="table-responsive orc-premium-tbl-wrap">
						<table class="table orc-premium-tbl" id="tableRecusados">
							<thead>
								<tr>
									<th style="width:65px;">ID</th>
									<th>Empresa</th>
									<th style="width:75px;">Versão</th>
									<th style="width:85px;">Status</th>
									<th class="text-right orc-premium-th-r" style="width:100px;">Total</th>
									<th class="text-right orc-premium-th-r" style="width:80px;">Margem</th>
									<th style="width:95px;">Data</th>
									<th style="width:90px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($orcamentosRecusados as $reg) :
									$nomeCli = !empty($reg->cliente->razaosocial) ? $reg->cliente->razaosocial : $reg->cliente->nome;
									$editUrl = $this->Url->build(['action' => 'edit', $reg->id]);
									$ini = $orcPremiumIniciais($nomeCli);
									?>
									<tr>
										<td data-order="<?= (int)$reg->id ?>"><a class="orc-premium-link orc-premium-id" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>">#<?= h($reg->id) ?></a></td>
										<td>
											<div class="orc-premium-empresa-cell">
												<span class="orc-premium-av" aria-hidden="true"><?= h($ini) ?></span>
												<a class="orc-premium-link orc-premium-empresa-name" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>"><?= h($nomeCli) ?></a>
											</div>
										</td>
										<td><span class="orc-premium-ver"><?= h($orcPremiumVersao($reg->id)) ?></span></td>
										<td><?= $orcPremiumBadge($reg->status) ?></td>
										<td class="text-right orc-premium-valor"><?= h($orcPremiumFmtValor($reg->id)) ?></td>
										<td class="text-right orc-premium-margem-cell" data-order="<?= (int)$orcPremiumMargemOrder($reg->id) ?>"><?= $orcPremiumMargemHtml($reg->id) ?></td>
										<td class="orc-premium-muted"><a class="orc-premium-link" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>"><?= @date_format($reg->created, 'd/m/Y') ?></a></td>
										<td>
											<?= $this->Html->link('Editar', ['action' => 'edit', $reg->id], ['class' => 'btn btn-sm orc-premium-btn-ghost', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane fade" id="arquivados" role="tabpanel">
					<div class="table-responsive orc-premium-tbl-wrap">
						<table class="table orc-premium-tbl" id="tableArquivados">
							<thead>
								<tr>
									<th style="width:65px;">ID</th>
									<th>Empresa</th>
									<th style="width:75px;">Versão</th>
									<th style="width:85px;">Status</th>
									<th class="text-right orc-premium-th-r" style="width:100px;">Total</th>
									<th class="text-right orc-premium-th-r" style="width:80px;">Margem</th>
									<th style="width:95px;">Data</th>
									<th style="width:90px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($orcamentosArquivados as $reg) :
									$nomeCli = !empty($reg->cliente->razaosocial) ? $reg->cliente->razaosocial : $reg->cliente->nome;
									$editUrl = $this->Url->build(['action' => 'edit', $reg->id]);
									$ini = $orcPremiumIniciais($nomeCli);
									?>
									<tr>
										<td data-order="<?= (int)$reg->id ?>"><a class="orc-premium-link orc-premium-id" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>">#<?= h($reg->id) ?></a></td>
										<td>
											<div class="orc-premium-empresa-cell">
												<span class="orc-premium-av" aria-hidden="true"><?= h($ini) ?></span>
												<a class="orc-premium-link orc-premium-empresa-name" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>"><?= h($nomeCli) ?></a>
											</div>
										</td>
										<td><span class="orc-premium-ver"><?= h($orcPremiumVersao($reg->id)) ?></span></td>
										<td><?= $orcPremiumBadge($reg->status) ?></td>
										<td class="text-right orc-premium-valor"><?= h($orcPremiumFmtValor($reg->id)) ?></td>
										<td class="text-right orc-premium-margem-cell" data-order="<?= (int)$orcPremiumMargemOrder($reg->id) ?>"><?= $orcPremiumMargemHtml($reg->id) ?></td>
										<td class="orc-premium-muted"><a class="orc-premium-link" target="_blank" rel="noopener noreferrer" href="<?= h($editUrl) ?>"><?= @date_format($reg->created, 'd/m/Y') ?></a></td>
										<td>
											<?= $this->Html->link('Editar', ['action' => 'edit', $reg->id], ['class' => 'btn btn-sm orc-premium-btn-ghost', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

	<?php else : ?>
		<header class="orc-premium-page-head">
			<div class="orc-premium-page-head-text">
				<p class="orc-premium-eyebrow">Portal do Cliente</p>
				<h1 class="orc-premium-h1">Meus Orçamentos</h1>
			</div>
			<?= $this->Html->link(
				'<svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg> Solicitar Orçamento',
				['action' => 'solicitar'],
				['class' => 'btn orc-premium-btn-primary', 'escape' => false]
			) ?>
		</header>
		<div class="orc-premium-list-card">
			<div class="orc-premium-list-toolbar orc-premium-list-toolbar--simple">
				<label class="orc-premium-search" for="orc-list-search-cli">
					<i class="ti-search" aria-hidden="true"></i>
					<input type="search" id="orc-list-search-cli" placeholder="Buscar por ID ou nome…" autocomplete="off" />
				</label>
			</div>
			<div class="table-responsive orc-premium-tbl-wrap">
				<table class="table orc-premium-tbl" id="tableCliente">
					<thead>
						<tr>
							<th style="width:65px;">ID</th>
							<th>Consultor</th>
							<th style="width:75px;">Versão</th>
							<th style="width:85px;">Status</th>
							<th class="text-right orc-premium-th-r" style="width:110px;">Total</th>
							<th style="width:95px;">Data</th>
							<th style="width:90px;">Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($orcamentosCliente as $reg) :
							$viewUrl = $this->Url->build(['action' => 'view', $reg->id]);
							$nomeAutor = $reg->user->name ?? '';
							$ini = $orcPremiumIniciais($nomeAutor);
							?>
							<tr>
								<td data-order="<?= (int)$reg->id ?>"><a class="orc-premium-link orc-premium-id" target="_blank" rel="noopener noreferrer" href="<?= h($viewUrl) ?>">#<?= h($reg->id) ?></a></td>
								<td>
									<div class="orc-premium-empresa-cell">
										<span class="orc-premium-av" aria-hidden="true"><?= h($ini) ?></span>
										<a class="orc-premium-link orc-premium-empresa-name" target="_blank" rel="noopener noreferrer" href="<?= h($viewUrl) ?>"><?= h($nomeAutor) ?></a>
									</div>
								</td>
								<td><span class="orc-premium-ver"><?= h($orcPremiumVersao($reg->id)) ?></span></td>
								<td><?= $orcPremiumBadge($reg->status) ?></td>
								<td class="text-right orc-premium-valor"><?= h($orcPremiumFmtValor($reg->id)) ?></td>
								<td class="orc-premium-muted"><a class="orc-premium-link" target="_blank" rel="noopener noreferrer" href="<?= h($viewUrl) ?>"><?= @date_format($reg->created, 'd/m/Y') ?></a></td>
								<td>
									<?= $this->Html->link('Abrir', ['action' => 'view', $reg->id], ['class' => 'btn btn-sm orc-premium-btn-ghost', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>
</div>
</div>

<script>
$(document).ready(function() {
	var table = $('#tableCliente, #tablePendentes, #tableEnviados, #tableAprovados, #tableRecusados, #tableArquivados');
	table.on('length.dt', function(e, settings, len) {
		if (typeof pagelength === 'function') pagelength(len);
	});
	table.DataTable({
		order: [[0, 'desc']],
		pageLength: <?= isset($pagelength) ? (int)$pagelength : 25 ?>,
		language: {
			sProcessing: 'Procesando...',
			sLengthMenu: 'Mostrar _MENU_ registros',
			sZeroRecords: 'Nenhum registro encontrado',
			sEmptyTable: 'Nenhum dado disponível',
			sInfo: 'Mostrando registros de _START_ até _END_ de um total de _TOTAL_ registros',
			sInfoEmpty: 'Mostrando registros de 0 a 0 de um total de 0 registros',
			sInfoFiltered: '(filtrado de um total de _MAX_ registros)',
			sInfoPostFix: '',
			sSearch: 'Buscar:',
			sUrl: '',
			sInfoThousands: ',',
			sLoadingRecords: 'Carregando...',
			oPaginate: { sFirst: '<<', sLast: '>>', sNext: '>', sPrevious: '<' },
			oAria: { sSortAscending: ': Ordem Ascendente', sSortDescending: ': Ordem descendente' }
		},
		drawCallback: function() {
			// Não aplicar células .dark-mode dentro da lista premium (senão o DataTables força o tema escuro do ERP).
			$('td').each(function() {
				var $td = $(this);
				if ($td.closest('#orc-premium-container').length) {
					$td.removeClass('dark-mode');
				} else if ($('body').hasClass('dark-mode')) {
					$td.addClass('dark-mode');
				} else {
					$td.removeClass('dark-mode');
				}
			});
		}
	});
	if (typeof filters !== 'undefined' && filters) {
		table.search(filters).draw();
	}

	function activeTableSelector() {
		var id = $('.orc-premium-tab-panels .tab-pane.active table').attr('id');
		if (id) return '#' + id;
		return $('#tableCliente').length ? '#tableCliente' : null;
	}

	$('#orc-list-search').on('input', function() {
		var sel = activeTableSelector();
		if (!sel || !$.fn.DataTable.isDataTable(sel)) return;
		$(sel).DataTable().search(this.value).draw();
	});

	$('#orc-list-search-cli').on('input', function() {
		if (!$.fn.DataTable.isDataTable('#tableCliente')) return;
		$('#tableCliente').DataTable().search(this.value).draw();
	});

	$('a[data-toggle="tab"][href^="#"]').on('shown.bs.tab', function() {
		$('#orc-list-search').val('');
		var sel = activeTableSelector();
		if (sel && $.fn.DataTable.isDataTable(sel)) {
			$(sel).DataTable().search('').draw();
		}
		var href = $(this).attr('href') || '';
		var pane = href.replace('#', '');
		$('.orc-premium-stat').removeClass('active').attr('aria-selected', 'false');
		$('.orc-premium-stat[data-orc-tab="' + pane + '"]').addClass('active').attr('aria-selected', 'true');
	});

	$('.orc-premium-stat').on('click', function() {
		var pane = $(this).data('orc-tab');
		if (!pane) return;
		$('.orc-premium-stat').removeClass('active').attr('aria-selected', 'false');
		$(this).addClass('active').attr('aria-selected', 'true');
		$('a[href="#' + pane + '"]').tab('show');
	});
});

window.onload = function() {
	var $adm = $('#admins [type="search"]');
	if ($adm.length) $adm.focus();
};
</script>
