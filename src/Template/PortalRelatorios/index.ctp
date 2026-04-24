<?php
$kpi = $relKpi ?? ['tickets' => '0', 'resolvidos' => '0', 'pendentes' => '0', 'sla' => '—'];
$f = $relFiltros ?? ['periodo' => '', 'unidade' => '', 'contrato' => ''];
$contratos = $relContratos ?? [];
$unidades = $empresasOptSidebar ?? [];
$ra = $relResumoAtendimentos ?? [];
$rc = $relResumoContratos ?? [];
$rf = $relResumoFinanceiro ?? [];
$relChartTemporal = $relChartTemporal ?? ['mode' => 'week', 'points' => []];
$relTicketsAmostra = $relTicketsAmostra ?? [];
$exportQuery = array_filter([
	'periodo' => $f['periodo'],
	'unidade' => $f['unidade'],
	'contrato' => $f['contrato'],
], function ($v) {
	return $v !== null && $v !== '';
});
$exportCsvUrl = $this->Url->build([
	'controller' => 'PortalRelatorios',
	'action' => 'exportar',
	'?' => $exportQuery,
]);
$exportExcelUrl = $this->Url->build([
	'controller' => 'PortalRelatorios',
	'action' => 'exportarExcel',
	'?' => $exportQuery,
]);
?>

<div class="col-lg-12">
	<div class="tkcli-wrap relcli-page">
		<div class="tkcli-head">
			<div class="tkcli-head-left">
				<div class="tkcli-eyebrow">Portal do Cliente</div>
				<h1>Relatórios</h1>
				<p>Indicadores resumidos da sua empresa — sem detalhes operacionais internos.</p>
			</div>
			<div class="relcli-head-actions">
				<div class="btn-group relcli-export-dd" role="group">
					<button type="button" class="tkcli-btn-limpar dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Baixar resumo nos formatos disponíveis">
						<i class="fa fa-download"></i> Exportar
					</button>
					<div class="dropdown-menu dropdown-menu-right">
						<?= $this->Html->link('CSV (UTF-8)', $exportCsvUrl, ['class' => 'dropdown-item', 'escape' => false, 'data-turbo' => 'false']) ?>
						<?= $this->Html->link('Excel (.xlsx)', $exportExcelUrl, ['class' => 'dropdown-item', 'escape' => false, 'data-turbo' => 'false']) ?>
					</div>
				</div>
			</div>
		</div>

		<?= $this->Form->create(null, ['type' => 'get', 'url' => ['controller' => 'PortalRelatorios', 'action' => 'index'], 'class' => 'tkcli-filters', 'data-turbo' => 'false']) ?>
			<div class="tkcli-filter-group tkcli-filter-group--grow">
				<label>Período</label>
				<input type="text" name="periodo" class="form-control" placeholder="Ex.: 01/04/2026 a 30/04/2026 (vazio = últimos 90 dias)" value="<?= h($f['periodo']) ?>">
			</div>
			<div class="tkcli-filter-group tkcli-filter-group--w160">
				<label>Unidade</label>
				<select name="unidade" class="form-control">
					<option value="">Todas</option>
					<?php foreach ($unidades as $id => $nome): ?>
						<option value="<?= h((string)$id) ?>" <?= ((string)$f['unidade'] === (string)$id) ? 'selected' : '' ?>><?= h($nome) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="tkcli-filter-group tkcli-filter-group--w180">
				<label>Contrato</label>
				<select name="contrato" class="form-control">
					<option value="">Todos</option>
					<?php foreach ($contratos as $id => $nome): ?>
						<option value="<?= h((string)$id) ?>" <?= ((string)$f['contrato'] === (string)$id) ? 'selected' : '' ?>><?= h($nome) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="tkcli-filter-group tkcli-filter-group--actions">
				<button type="submit" class="tkcli-btn-abrir">Aplicar</button>
			</div>
			<div class="tkcli-filter-group tkcli-filter-group--actions">
				<?= $this->Html->link('Limpar filtros', ['controller' => 'PortalRelatorios', 'action' => 'index'], ['class' => 'tkcli-btn-limpar', 'data-turbo' => 'false']) ?>
			</div>
		<?= $this->Form->end() ?>

		<section class="relcli-kpis" aria-label="Indicadores do período">
			<article class="relcli-kpi">
				<span class="relcli-kpi__label">Tickets</span>
				<strong class="relcli-kpi__value"><?= h($kpi['tickets']) ?></strong>
				<span class="relcli-kpi__hint">Total no intervalo filtrado</span>
			</article>
			<article class="relcli-kpi">
				<span class="relcli-kpi__label">Resolvidos</span>
				<strong class="relcli-kpi__value"><?= h($kpi['resolvidos']) ?></strong>
				<span class="relcli-kpi__hint">Encerrados ou resolvidos</span>
			</article>
			<article class="relcli-kpi">
				<span class="relcli-kpi__label">Pendentes</span>
				<strong class="relcli-kpi__value"><?= h($kpi['pendentes']) ?></strong>
				<span class="relcli-kpi__hint">Em aberto ou em andamento</span>
			</article>
			<article class="relcli-kpi">
				<span class="relcli-kpi__label">SLA</span>
				<strong class="relcli-kpi__value"><?= h($kpi['sla']) ?></strong>
				<span class="relcli-kpi__hint">Percentual no prazo (visão cliente)</span>
			</article>
		</section>

		<section class="relcli-panel relcli-panel--chart" aria-labelledby="relcli-chart-title">
			<div class="relcli-panel__head">
				<h2 id="relcli-chart-title" class="relcli-panel__title">Chamados no período</h2>
				<p class="relcli-panel__sub">Distribuição pelo tempo de abertura, com os mesmos filtros dos indicadores acima.</p>
			</div>
			<div class="relcli-chart-body">
				<?= $this->element('PortalRelatorios/chart_temporal', ['chart' => $relChartTemporal]) ?>
			</div>
		</section>

		<section class="relcli-panel relcli-panel--amostra" aria-labelledby="relcli-amostra-title">
			<div class="relcli-panel__head">
				<h2 id="relcli-amostra-title" class="relcli-panel__title">Últimos chamados</h2>
				<p class="relcli-panel__sub">Assunto e situação — sem notas internas nem dados operacionais.</p>
			</div>
			<?php if (empty($relTicketsAmostra)) : ?>
				<p class="relcli-amostra-empty text-muted mb-0">Nenhum chamado no período com os filtros atuais.</p>
			<?php else : ?>
				<div class="table-responsive">
					<table class="table relcli-amostra-table mb-0">
						<thead>
							<tr>
								<th scope="col">Abertura</th>
								<th scope="col">Assunto</th>
								<th scope="col">Situação</th>
								<th scope="col" class="text-right">Ação</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($relTicketsAmostra as $t) : ?>
								<tr>
									<td class="text-nowrap"><?= h($t['abertura']) ?></td>
									<td><?= h($t['assunto']) ?></td>
									<td><?= h($t['situacao']) ?></td>
									<td class="text-right">
										<?= $this->Html->link('Abrir', ['controller' => 'Tickets', 'action' => 'view', $t['id']], ['class' => 'relcli-amostra-link', 'data-turbo' => 'false']) ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>

		<div class="relcli-blocks">
			<section class="relcli-panel" aria-labelledby="relcli-bloco-atend">
				<div class="relcli-panel__head">
					<h2 id="relcli-bloco-atend" class="relcli-panel__title">Atendimentos</h2>
					<p class="relcli-panel__sub">Resumo de chamados (apenas o que o seu perfil pode ver).</p>
				</div>
				<div class="table-responsive">
					<table class="table relcli-metric-table mb-0">
						<thead>
							<tr>
								<th scope="col">Descrição</th>
								<th scope="col" class="text-right">Valor</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($ra as $row): ?>
								<tr>
									<td>
										<span class="relcli-metric-label"><?= h($row['label']) ?></span>
										<?php if (!empty($row['hint'])): ?>
											<br><small class="text-muted"><?= h($row['hint']) ?></small>
										<?php endif; ?>
									</td>
									<td class="text-right relcli-metric-value"><?= h($row['valor']) ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>

			<section class="relcli-panel" aria-labelledby="relcli-bloco-cont">
				<div class="relcli-panel__head">
					<h2 id="relcli-bloco-cont" class="relcli-panel__title">Contratos</h2>
					<p class="relcli-panel__sub">Indicadores consolidados por contrato, sem detalhes internos.</p>
				</div>
				<div class="table-responsive">
					<table class="table relcli-metric-table mb-0">
						<thead>
							<tr>
								<th scope="col">Descrição</th>
								<th scope="col" class="text-right">Valor</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rc as $row): ?>
								<tr>
									<td>
										<span class="relcli-metric-label"><?= h($row['label']) ?></span>
										<?php if (!empty($row['hint'])): ?>
											<br><small class="text-muted"><?= h($row['hint']) ?></small>
										<?php endif; ?>
									</td>
									<td class="text-right relcli-metric-value"><?= h($row['valor']) ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>

			<section class="relcli-panel" aria-labelledby="relcli-bloco-fin">
				<div class="relcli-panel__head">
					<h2 id="relcli-bloco-fin" class="relcli-panel__title">Financeiro</h2>
					<p class="relcli-panel__sub">Totais e situação geral autorizados para o portal.</p>
				</div>
				<div class="table-responsive">
					<table class="table relcli-metric-table mb-0">
						<thead>
							<tr>
								<th scope="col">Descrição</th>
								<th scope="col" class="text-right">Valor</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rf as $row): ?>
								<tr>
									<td>
										<span class="relcli-metric-label"><?= h($row['label']) ?></span>
										<?php if (!empty($row['hint'])): ?>
											<br><small class="text-muted"><?= h($row['hint']) ?></small>
										<?php endif; ?>
									</td>
									<td class="text-right relcli-metric-value"><?= h($row['valor']) ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>
		</div>
	</div>
</div>
