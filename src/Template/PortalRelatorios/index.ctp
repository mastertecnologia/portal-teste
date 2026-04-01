<?php
$kpi = $relKpi ?? ['tickets' => '0', 'resolvidos' => '0', 'pendentes' => '0', 'sla' => '—'];
$f = $relFiltros ?? ['periodo' => '', 'unidade' => '', 'contrato' => ''];
$contratos = $relContratos ?? [];
$unidades = $empresasOptSidebar ?? [];
$ra = $relResumoAtendimentos ?? [];
$rc = $relResumoContratos ?? [];
$rf = $relResumoFinanceiro ?? [];
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
						<?= $this->Html->link('CSV (UTF-8)', $exportCsvUrl, ['class' => 'dropdown-item', 'escape' => false]) ?>
						<?= $this->Html->link('Excel (.xlsx)', $exportExcelUrl, ['class' => 'dropdown-item', 'escape' => false]) ?>
					</div>
				</div>
			</div>
		</div>

		<?= $this->Form->create(null, ['type' => 'get', 'url' => ['controller' => 'PortalRelatorios', 'action' => 'index'], 'class' => 'tkcli-filters']) ?>
			<div class="tkcli-filter-group" style="min-width: 200px; flex: 1 1 200px;">
				<label>Período</label>
				<input type="text" name="periodo" class="form-control" placeholder="Ex.: 01/04/2026 a 30/04/2026" value="<?= h($f['periodo']) ?>">
			</div>
			<div class="tkcli-filter-group" style="min-width: 160px;">
				<label>Unidade</label>
				<select name="unidade" class="form-control">
					<option value="">Todas</option>
					<?php foreach ($unidades as $id => $nome): ?>
						<option value="<?= h((string)$id) ?>" <?= ((string)$f['unidade'] === (string)$id) ? 'selected' : '' ?>><?= h($nome) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="tkcli-filter-group" style="min-width: 180px;">
				<label>Contrato</label>
				<select name="contrato" class="form-control">
					<option value="">Todos</option>
					<?php foreach ($contratos as $id => $nome): ?>
						<option value="<?= h((string)$id) ?>" <?= ((string)$f['contrato'] === (string)$id) ? 'selected' : '' ?>><?= h($nome) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="tkcli-filter-group" style="padding-top:18px;">
				<button type="submit" class="tkcli-btn-abrir" style="border:none;">Aplicar</button>
			</div>
			<div class="tkcli-filter-group" style="padding-top:18px;">
				<?= $this->Html->link('Limpar filtros', ['controller' => 'PortalRelatorios', 'action' => 'index'], ['class' => 'tkcli-btn-limpar']) ?>
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
				<h2 id="relcli-chart-title" class="relcli-panel__title">Visão geral</h2>
				<p class="relcli-panel__sub">Evolução e distribuição aparecerão aqui quando os dados estiverem disponíveis.</p>
			</div>
			<div class="relcli-chart-slot" role="img" aria-label="Área reservada para gráfico">
				<span class="relcli-chart-slot__text">Gráfico</span>
				<small class="relcli-chart-slot__hint">Placeholder — sem biblioteca externa; o portal pode injetar SVG ou imagem gerada no servidor.</small>
			</div>
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
