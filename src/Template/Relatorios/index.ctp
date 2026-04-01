<?php
/**
 * Relatórios e Indicadores — KPIs, filtros, abas e dados.
 *
 * @var array<string, string> $relatoriosKpis
 * @var string $relatoriosAbaAtiva
 */
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Relatórios e Indicadores', [], ['class' => 'breadcrumb-item active']);

$k = $relatoriosKpis ?? ['tickets' => '—', 'sla' => '—', 'receita' => '—', 'inadimplencia' => '—'];
$aba = $relatoriosAbaAtiva ?? 'atendimento';
$fq = $this->request->getQueryParams();
$qExp = array_merge($fq, ['aba' => $aba]);
$cliList = $relatoriosClientesList ?? [];
$tecList = $relatoriosTecnicosList ?? [];
$ctrList = $relatoriosContratosList ?? [];
$selCli = $relatoriosSelCliente;
$selTec = $relatoriosSelTecnico;
$selCtr = $relatoriosSelContrato;
$ticketsAmostra = $relatoriosTicketsAmostra ?? [];
$sitLabels = $relatoriosSitLabels ?? [];
$finLinhas = $relatoriosFinanceiroLinhas ?? [];
$ctrRows = $relatoriosContratosRows ?? [];
$tecRows = $relatoriosTecnicosRows ?? [];
$periodoLabel = $relatoriosPeriodoLabel ?? '';
$periodoPadrao = !empty($relatoriosPeriodoPadrao);
?>
<?= $this->Html->css('dist/css/dashboard-erp.css') ?>
<style>
	.dash-erp-relatorios .rel-section-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--erp-muted,#8b92a8);margin:0 0 10px;padding-left:2px;}
	.dash-erp-relatorios .rel-section-label--spaced{margin-top:6px;}
	.dash-erp-relatorios .rel-tabnav{display:flex;flex-wrap:wrap;gap:4px;padding:8px 10px 0;background:rgba(0,0,0,.14);border-radius:var(--erp-radius,12px) var(--erp-radius,12px) 0 0;border:1px solid var(--erp-border,rgba(255,255,255,.10));border-bottom:none;}
	.dash-erp-relatorios .rel-tabbtn{padding:9px 16px;font-size:13px;font-weight:700;color:var(--erp-muted,#8b92a8);background:transparent;border:none;border-bottom:2px solid transparent;margin-bottom:-1px;cursor:pointer;border-radius:8px 8px 0 0;}
	.dash-erp-relatorios .rel-tabbtn:hover{color:var(--erp-text,#f0f2f8);background:rgba(255,255,255,.04);}
	.dash-erp-relatorios .rel-tabbtn[aria-selected="true"]{color:var(--erp-teal,#3d7eff);border-bottom-color:var(--erp-teal,#3d7eff);background:rgba(61,126,255,.08);}
	.dash-erp-relatorios .rel-tabpanels-shell{border:1px solid var(--erp-border,rgba(255,255,255,.10));border-top:none;border-radius:0 0 var(--erp-radius,12px) var(--erp-radius,12px);background:var(--erp-card,#1a1e28);padding:14px 16px 16px;margin-bottom:14px;}
	.dash-erp-relatorios .rel-panel-inner{border-top:1px solid var(--erp-border,rgba(255,255,255,.08));padding-top:14px;margin-top:4px;}
	.dash-erp-relatorios .rel-tabpanel-wrap:first-of-type .rel-panel-inner{border-top:none;padding-top:0;margin-top:0;}
	.dash-erp-relatorios .rel-block-label{margin:0 0 8px;font-size:12px;font-weight:700;color:var(--erp-muted,#8b92a8);}
	.dash-erp-relatorios .rel-block-label--gap{margin-top:18px;}
	.dash-erp-relatorios .rel-placeholder{min-height:170px;border:1px dashed var(--erp-border,rgba(255,255,255,.14));border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--erp-muted,#8b92a8);font-size:13px;text-align:center;padding:16px;background:rgba(0,0,0,.06);}
	.dash-erp-relatorios .rel-export-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;}
	.dash-erp-relatorios .rel-export-hint{font-size:12px;color:var(--erp-muted,#8b92a8);margin:0 0 12px;line-height:1.45;}
	.dash-erp-relatorios .m-b-10{margin-bottom:10px;}
	.dash-erp-relatorios .rel-dropdown{position:relative;display:inline-block;}
	.dash-erp-relatorios .rel-dropdown__menu{display:none;position:absolute;right:0;top:100%;margin-top:4px;min-width:160px;z-index:50;background:var(--erp-card,#1a1e28);border:1px solid var(--erp-border,rgba(255,255,255,.12));border-radius:10px;box-shadow:var(--erp-shadow,0 10px 30px rgba(0,0,0,.35));padding:4px 0;}
	.dash-erp-relatorios .rel-dropdown.is-open .rel-dropdown__menu{display:block;}
	.dash-erp-relatorios .rel-dropdown__item{display:block;width:100%;padding:8px 14px;font-size:13px;color:var(--erp-text,#f0f2f8);text-align:left;border:none;background:none;cursor:pointer;text-decoration:none;}
	.dash-erp-relatorios .rel-dropdown__item:hover{background:rgba(61,126,255,.12);}
</style>

<div class="col-12 p-0">
	<div class="dash-erp dash-erp-relatorios">
		<div class="dash-erp-header">
			<div>
				<h2 class="dash-erp-title">Relatórios e Indicadores</h2>
				<p class="dash-erp-subtitle">Indicadores consolidados por período (criação do ticket), escopo ABAC e empresa. Receita e inadimplência vêm dos lançamentos financeiros.</p>
			</div>
			<div class="d-flex flex-wrap align-items-center" style="gap:8px;">
				<a class="btn btn-outline-secondary" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'dashboard']) ?>">Voltar ao dashboard</a>
				<button type="button" class="btn btn-outline-info" onclick="window.location.reload()">Atualizar</button>
				<button type="button" class="btn btn-outline-secondary" onclick="window.print()">Imprimir</button>
				<div class="rel-dropdown" id="rel-export-dropdown">
					<button type="button" class="btn btn-outline-secondary" id="rel-export-btn" aria-expanded="false" aria-haspopup="true">Exportar ▾</button>
					<div class="rel-dropdown__menu" role="menu" aria-labelledby="rel-export-btn">
						<a class="rel-dropdown__item" role="menuitem" href="<?= h($this->Url->build(['controller' => 'Relatorios', 'action' => 'exportar', '?' => array_merge($qExp, ['formato' => 'pdf'])])) ?>">PDF</a>
						<a class="rel-dropdown__item" role="menuitem" href="<?= h($this->Url->build(['controller' => 'Relatorios', 'action' => 'exportar', '?' => array_merge($qExp, ['formato' => 'xlsx'])])) ?>">Excel</a>
						<a class="rel-dropdown__item" role="menuitem" href="<?= h($this->Url->build(['controller' => 'Relatorios', 'action' => 'exportar', '?' => array_merge($qExp, ['formato' => 'csv'])])) ?>">CSV</a>
					</div>
				</div>
			</div>
		</div>

		<p class="rel-section-label">Resumo do período</p>
		<div class="dash-erp-kpis" aria-label="Indicadores">
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-ticket-alt"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Tickets</p>
					<p class="dash-erp-kpi-value"><?= h($k['tickets']) ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-percentage"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">SLA</p>
					<p class="dash-erp-kpi-value"><?= h($k['sla']) ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-dollar-sign"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Receita</p>
					<p class="dash-erp-kpi-value"><?= h($k['receita']) ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-exclamation-triangle"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Inadimplência</p>
					<p class="dash-erp-kpi-value"><?= h($k['inadimplencia']) ?></p>
				</div>
			</div>
		</div>

		<p class="rel-section-label rel-section-label--spaced">Critérios</p>
		<div class="dash-erp-card m-b-10">
			<div class="dash-erp-card-header">
				<h5 class="dash-erp-card-title">Filtros</h5>
			</div>
			<div class="dash-erp-card-body">
				<?= $this->Form->create(null, [
					'class' => 'form-material',
					'type' => 'get',
					'url' => ['controller' => 'Relatorios', 'action' => 'index'],
				]) ?>
				<?= $this->Form->hidden('aba', ['value' => $aba, 'id' => 'rel-filtro-aba']) ?>
				<div class="row">
					<div class="col-md-3 col-sm-6 m-b-10">
						<label class="small text-muted m-b-5 d-block">Período — início</label>
						<input type="text" class="form-control" name="periodo_ini" placeholder="dd/mm/aaaa" value="<?= h($fq['periodo_ini'] ?? '') ?>">
					</div>
					<div class="col-md-3 col-sm-6 m-b-10">
						<label class="small text-muted m-b-5 d-block">Período — fim</label>
						<input type="text" class="form-control" name="periodo_fim" placeholder="dd/mm/aaaa" value="<?= h($fq['periodo_fim'] ?? '') ?>">
					</div>
					<div class="col-md-3 col-sm-6 m-b-10">
						<label class="small text-muted m-b-5 d-block">Cliente</label>
						<select class="form-control" name="idcliente">
							<option value="">Todos</option>
							<?php foreach ($cliList as $cid => $cnome) { ?>
								<option value="<?= (int)$cid ?>"<?= ($selCli !== null && (int)$selCli === (int)$cid) ? ' selected' : '' ?>><?= h($cnome) ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="col-md-3 col-sm-6 m-b-10">
						<label class="small text-muted m-b-5 d-block">Técnico</label>
						<select class="form-control" name="idtecnico">
							<option value="">Todos</option>
							<?php foreach ($tecList as $tid => $tnome) { ?>
								<option value="<?= (int)$tid ?>"<?= ($selTec !== null && (int)$selTec === (int)$tid) ? ' selected' : '' ?>><?= h($tnome) ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3 col-sm-6 m-b-10">
						<label class="small text-muted m-b-5 d-block">Contrato</label>
						<select class="form-control" name="idcontrato">
							<option value="">Todos</option>
							<?php foreach ($ctrList as $ctid => $clabel) { ?>
								<option value="<?= (int)$ctid ?>"<?= ($selCtr !== null && (int)$selCtr === (int)$ctid) ? ' selected' : '' ?>><?= h($clabel) ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="col-md-3 col-sm-6 m-b-10" style="padding-top:22px;">
						<button type="submit" class="btn btn-pgm btn-pgm-salvar btn-success m-r-5">Aplicar</button>
						<a href="<?= $this->Url->build(['controller' => 'Relatorios', 'action' => 'index']) ?>" class="btn btn-outline-secondary">Limpar</a>
					</div>
				</div>
				<?= $this->Form->end() ?>
				<?php if ($periodoLabel !== '') { ?>
					<p class="small text-muted m-b-0 m-t-10">Período ativo: <strong><?= h($periodoLabel) ?></strong><?= $periodoPadrao ? ' (padrão: últimos 60 dias)' : '' ?>.</p>
				<?php } ?>
			</div>
		</div>

		<p class="rel-section-label rel-section-label--spaced">Detalhamento</p>
		<div class="rel-det-card">
			<div class="rel-tabnav" role="tablist" aria-label="Áreas de relatório">
				<button type="button" class="rel-tabbtn" role="tab" data-rel-tab="atendimento" aria-selected="<?= $aba === 'atendimento' ? 'true' : 'false' ?>">Atendimento</button>
				<button type="button" class="rel-tabbtn" role="tab" data-rel-tab="contratos" aria-selected="<?= $aba === 'contratos' ? 'true' : 'false' ?>">Contratos</button>
				<button type="button" class="rel-tabbtn" role="tab" data-rel-tab="financeiro" aria-selected="<?= $aba === 'financeiro' ? 'true' : 'false' ?>">Financeiro</button>
				<button type="button" class="rel-tabbtn" role="tab" data-rel-tab="tecnicos" aria-selected="<?= $aba === 'tecnicos' ? 'true' : 'false' ?>">Técnicos</button>
			</div>
			<div class="rel-tabpanels-shell">
				<?php
				$labels = ['atendimento' => 'Atendimento', 'contratos' => 'Contratos', 'financeiro' => 'Financeiro', 'tecnicos' => 'Técnicos'];
				foreach ($labels as $tabKey => $tit) {
					$hidden = $aba !== $tabKey;
					?>
					<div class="rel-tabpanel-wrap" data-rel-panel="<?= h($tabKey) ?>" <?= $hidden ? 'hidden' : '' ?>>
						<div class="rel-panel-inner">
							<p class="dash-erp-card-title" style="font-size:14px;margin:0 0 12px;"><?= h($tit) ?></p>
							<?php if ($tabKey === 'atendimento') { ?>
								<p class="rel-block-label">Gráfico</p>
								<div class="rel-placeholder m-b-10">Gráfico será adicionado em etapa seguinte.</div>
								<p class="rel-block-label rel-block-label--gap">Tickets (amostra, até 50)</p>
								<div class="dash-erp-scroll" style="max-height:260px;">
									<div class="table-responsive">
										<table class="dash-erp-table">
											<thead><tr><th>ID</th><th>Abertura</th><th>Cliente</th><th>Assunto</th><th>Situação</th></tr></thead>
											<tbody>
												<?php if (empty($ticketsAmostra)) { ?>
													<tr><td colspan="5" class="text-center text-muted" style="padding:20px;">Nenhum ticket no período com os filtros atuais.</td></tr>
												<?php } else { ?>
													<?php foreach ($ticketsAmostra as $reg) {
														$clienteNome = '—';
														$c = $reg->cliente ?? $reg->clientes ?? null;
														if ($c) {
															$clienteNome = ((int)$c->tipo === (int)C_ClientesTipoFisica)
																? (string)$c->nome
																: (string)$c->razaosocial;
														}
														$assuntoTxt = strip_tags(AssuntoTicket((string)$reg->assunto));
														if (function_exists('mb_substr') && mb_strlen($assuntoTxt) > 80) {
															$assuntoTxt = mb_substr($assuntoTxt, 0, 77) . '…';
														}
														$sitNome = $sitLabels[(int)$reg->situacao] ?? ('#' . (int)$reg->situacao);
														$abertura = '';
														if (!empty($reg->created) && is_object($reg->created) && method_exists($reg->created, 'format')) {
															$abertura = $reg->created->format('d/m/Y H:i');
														}
														$urlTicket = $this->Url->build(['controller' => 'Tickets', 'action' => 'edit', $reg->id]);
														?>
														<tr>
															<td><a class="dash-erp-link" href="<?= h($urlTicket) ?>"><?= (int)$reg->id ?></a></td>
															<td><?= h($abertura) ?></td>
															<td><?= h($clienteNome) ?></td>
															<td><?= h($assuntoTxt) ?></td>
															<td><?= h($sitNome) ?></td>
														</tr>
													<?php } ?>
												<?php } ?>
											</tbody>
										</table>
									</div>
								</div>
							<?php } elseif ($tabKey === 'contratos') { ?>
								<p class="rel-block-label">Contratos no escopo (amostra)</p>
								<div class="dash-erp-scroll" style="max-height:260px;">
									<div class="table-responsive">
										<table class="dash-erp-table">
											<thead><tr><th>ID</th><th>Cliente</th><th>Descrição</th><th>Validade</th></tr></thead>
											<tbody>
												<?php if (empty($ctrRows)) { ?>
													<tr><td colspan="4" class="text-center text-muted" style="padding:20px;">Nenhum contrato listável ou tabela indisponível.</td></tr>
												<?php } else { ?>
													<?php foreach ($ctrRows as $cr) {
														$cn = '—';
														$cl = $cr->cliente ?? $cr->clientes ?? null;
														if ($cl) {
															$cn = ((int)$cl->tipo === (int)C_ClientesTipoFisica) ? (string)$cl->nome : (string)$cl->razaosocial;
														}
														$dv = '';
														if (!empty($cr->dtvalidade) && is_object($cr->dtvalidade) && method_exists($cr->dtvalidade, 'format')) {
															$dv = $cr->dtvalidade->format('d/m/Y');
														}
														$descFull = (string)($cr->descricao ?? '');
														$descLen = function_exists('mb_strlen') ? mb_strlen($descFull) : strlen($descFull);
														if ($descLen > 60) {
															$descShow = (function_exists('mb_substr') ? mb_substr($descFull, 0, 57) : substr($descFull, 0, 57)) . '…';
														} else {
															$descShow = $descFull;
														}
														?>
														<tr>
															<td><?= (int)$cr->id ?></td>
															<td><?= h($cn) ?></td>
															<td><?= h($descShow) ?></td>
															<td><?= h($dv) ?></td>
														</tr>
													<?php } ?>
												<?php } ?>
											</tbody>
										</table>
									</div>
								</div>
							<?php } elseif ($tabKey === 'financeiro') { ?>
								<p class="rel-block-label">Resumo (mesmos números dos KPIs)</p>
								<div class="dash-erp-scroll" style="max-height:260px;">
									<div class="table-responsive">
										<table class="dash-erp-table">
											<thead><tr><th>Indicador</th><th>Valor</th></tr></thead>
											<tbody>
												<?php foreach ($finLinhas as $fl) { ?>
													<tr><td><?= h($fl['label']) ?></td><td><?= h($fl['valor']) ?></td></tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
								</div>
							<?php } else { ?>
								<p class="rel-block-label">Tickets por técnico responsável (período e filtros atuais)</p>
								<div class="dash-erp-scroll" style="max-height:260px;">
									<div class="table-responsive">
										<table class="dash-erp-table">
											<thead><tr><th>Técnico</th><th>Tickets</th></tr></thead>
											<tbody>
												<?php if (empty($tecRows)) { ?>
													<tr><td colspan="2" class="text-center text-muted" style="padding:20px;">Sem agrupamento (sem responsável ou sem dados).</td></tr>
												<?php } else { ?>
													<?php foreach ($tecRows as $tr) { ?>
														<tr><td><?= h($tr['nome']) ?></td><td><?= (int)$tr['tickets'] ?></td></tr>
													<?php } ?>
												<?php } ?>
											</tbody>
										</table>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>

		<p class="rel-section-label rel-section-label--spaced">Saída de dados</p>
		<div class="dash-erp-card m-b-10" id="rel-exportacao">
			<div class="dash-erp-card-header">
				<h5 class="dash-erp-card-title">Exportação</h5>
				<span class="dash-erp-card-badge">Mesmos filtros</span>
			</div>
			<div class="dash-erp-card-body">
				<p class="rel-export-hint">Mesmos critérios da tela: PDF (mPDF), CSV UTF-8, Excel (.xlsx via PhpSpreadsheet; se a lib não estiver instalada, fallback .xls XML).</p>
				<div class="rel-export-actions">
					<a class="btn btn-outline-secondary btn-sm" href="<?= h($this->Url->build(['controller' => 'Relatorios', 'action' => 'exportar', '?' => array_merge($qExp, ['formato' => 'pdf'])])) ?>" title="Rota preparada — implementar no controller">PDF</a>
					<a class="btn btn-outline-secondary btn-sm" href="<?= h($this->Url->build(['controller' => 'Relatorios', 'action' => 'exportar', '?' => array_merge($qExp, ['formato' => 'xlsx'])])) ?>">Excel</a>
					<a class="btn btn-outline-secondary btn-sm" href="<?= h($this->Url->build(['controller' => 'Relatorios', 'action' => 'exportar', '?' => array_merge($qExp, ['formato' => 'csv'])])) ?>">CSV</a>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	var aba = <?= json_encode($aba) ?>;
	var root = document.querySelector('.dash-erp-relatorios');
	var tabs = root ? root.querySelectorAll('.rel-tabbtn[data-rel-tab]') : [];
	var wraps = root ? root.querySelectorAll('.rel-tabpanel-wrap[data-rel-panel]') : [];
	var hid = document.getElementById('rel-filtro-aba');
	function go(a) {
		tabs.forEach(function (t) { t.setAttribute('aria-selected', t.getAttribute('data-rel-tab') === a ? 'true' : 'false'); });
		wraps.forEach(function (w) { w.hidden = w.getAttribute('data-rel-panel') !== a; });
		if (hid) hid.value = a;
	}
	tabs.forEach(function (t) { t.addEventListener('click', function () { go(t.getAttribute('data-rel-tab')); }); });
	go(aba);

	var drop = document.getElementById('rel-export-dropdown');
	var btn = document.getElementById('rel-export-btn');
	if (drop && btn) {
		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			var o = drop.classList.toggle('is-open');
			btn.setAttribute('aria-expanded', o ? 'true' : 'false');
		});
		document.addEventListener('click', function () {
			drop.classList.remove('is-open');
			btn.setAttribute('aria-expanded', 'false');
		});
	}
})();
</script>
