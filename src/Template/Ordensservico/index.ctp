<?php
$this->Html->css('/dist/css/pages/ordensservico-index-shell.css', ['block' => true]);

$kpiTotal = count($ordens);
$kpiExec = $kpiSync = $kpiLib = 0;
foreach ($ordens as $reg) {
	$st = trim(strip_tags((string)SituacaoOrdem($reg->situacao)));
	if ($st === 'Em execução') {
		$kpiExec++;
	} elseif (stripos($st, 'Sincronizada') !== false && stripos($st, 'Grid') !== false) {
		$kpiSync++;
	} elseif (stripos($st, 'Liberada') !== false && stripos($st, 'sincroniz') !== false) {
		$kpiLib++;
	}
}

$osStatusPill = function ($situacaoTxt) {
	$s = trim(strip_tags((string)$situacaoTxt));
	$short = $s;
	if (stripos($s, 'Sincronizada') !== false && stripos($s, 'Grid') !== false) {
		$short = 'Sincronizada';
	} elseif (stripos($s, 'Liberada') !== false && stripos($s, 'sincroniz') !== false) {
		$short = 'Liberada';
	}
	$class = 'os-st-default';
	if ($s === 'Em execução') {
		$class = 'os-st-exec';
	} elseif (stripos($s, 'Sincronizada') !== false && stripos($s, 'Grid') !== false) {
		$class = 'os-st-sync';
	} elseif (stripos($s, 'Liberada') !== false && stripos($s, 'sincroniz') !== false) {
		$class = 'os-st-lib';
	} elseif ($s !== '' && (stripos($s, 'pend') !== false || stripos($s, 'abert') !== false)) {
		$class = 'os-st-pen';
	}
	return [$class, h($short)];
};

$osTechInitials = function ($name) {
	$parts = preg_split('/\s+/', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
	$fa = isset($parts[0][0]) ? $parts[0][0] : '';
	$fb = isset($parts[1][0]) ? $parts[1][0] : '';
	return h(strtoupper($fa . $fb));
};

$kpiActive = 'all';
if ((string)$situacao === (string)C_OrdensSituacaoEmExecucao) {
	$kpiActive = 'exec';
} elseif ((string)$situacao === (string)C_OrdensSituacaoSincronizadaPeloGrid) {
	$kpiActive = 'sync';
} elseif ((string)$situacao === (string)C_OrdensSituacaoLiberadaParaFaturamento) {
	$kpiActive = 'lib';
}
?>
<div class="col-md-12 p-0">
	<div class="os-index-shell">
		<header class="os-page-head">
			<div>
				<h1 class="os-page-title">Ordens de Serviço</h1>
				<p class="os-page-sub"><?= h(date('d/m/Y')) ?> — <?= h($nomeempresa ?? '') ?></p>
			</div>
			<div class="os-page-head-actions">
				<button type="button" class="os-icon-btn os-icon-btn--btn" id="os-btn-export-csv" title="Exportar CSV (filtro atual da tabela)">
					<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M9 1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V6L9 1Z" stroke="currentColor" stroke-width="1.3"/><path d="M9 1v5h5" stroke="currentColor" stroke-width="1.3"/><path d="M8 10v4M6 12l2 2 2-2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
				</button>
				<?php if ($role == 0) : ?>
					<button type="button" class="os-btn-primary" id="os-open-modal-nova">Abrir ordem de serviço</button>
				<?php endif; ?>
				<span class="os-icon-btn" title="Notificações" aria-hidden="true">
					<svg width="15" height="15" viewBox="0 0 16 16" fill="none">
						<path d="M8 1a5 5 0 0 1 5 5v3l1 2H2l1-2V6a5 5 0 0 1 5-5ZM6.5 13.5a1.5 1.5 0 0 0 3 0" stroke="currentColor" stroke-width="1.3"/>
					</svg>
				</span>
			</div>
		</header>

		<div class="os-kpi-grid">
			<div class="os-kpi-card os-kpi-blue os-kpi-click<?= $kpiActive === 'all' ? ' is-active' : '' ?>" role="button" tabindex="0" data-os-kpi="all" title="Mostrar todas (limpar filtro de situação)">
				<div class="os-kpi-label">Total de OS</div>
				<div class="os-kpi-value"><?= (int)$kpiTotal ?></div>
				<div class="os-kpi-sub">Todos os registros</div>
			</div>
			<div class="os-kpi-card os-kpi-amber os-kpi-click<?= $kpiActive === 'exec' ? ' is-active' : '' ?>" role="button" tabindex="0" data-os-kpi="exec" title="Filtrar por em execução">
				<div class="os-kpi-label">Em execução</div>
				<div class="os-kpi-value os-kpi-value-accent"><?= (int)$kpiExec ?></div>
				<div class="os-kpi-sub">Aguardando conclusão</div>
			</div>
			<div class="os-kpi-card os-kpi-green os-kpi-click<?= $kpiActive === 'sync' ? ' is-active' : '' ?>" role="button" tabindex="0" data-os-kpi="sync" title="Filtrar por sincronizadas">
				<div class="os-kpi-label">Sincronizadas</div>
				<div class="os-kpi-value os-kpi-value-accent"><?= (int)$kpiSync ?></div>
				<div class="os-kpi-sub">Integradas ao ERP</div>
			</div>
			<div class="os-kpi-card os-kpi-purple os-kpi-click<?= $kpiActive === 'lib' ? ' is-active' : '' ?>" role="button" tabindex="0" data-os-kpi="lib" title="Filtrar por liberadas para sincronização">
				<div class="os-kpi-label">Liberadas</div>
				<div class="os-kpi-value os-kpi-value-accent"><?= (int)$kpiLib ?></div>
				<div class="os-kpi-sub">Aguardando sync</div>
			</div>
		</div>

		<?php if ($role == 0) : ?>
			<div class="os-bulk-bar" id="os-bulk-bar">
				<span class="os-bulk-count" id="os-bulk-count"></span>
				<?= $this->Html->link('Alterar situação', ['#'], ['class' => 'btn-acao os-btn-ghost hide']) ?>
				<?= $this->Html->link('Imprimir', ['#'], ['class' => 'btn-imprimir os-btn-ghost hide', 'target' => '_blank']) ?>
				<button type="button" class="os-btn-ghost" id="os-bulk-clear">Limpar seleção</button>
			</div>
		<?php endif; ?>

		<?= $this->Form->create(null, ['id' => 'formimprimir', 'url' => ['controller' => 'Ordensservico', 'action' => 'imprimirordens'], 'target' => '_blank']); ?>
		<?= $this->Form->control('idsimprimir', ['type' => 'hidden', 'label' => false]); ?>
		<?= $this->Form->end() ?>

		<div class="os-toolbar">
			<?= $this->Form->create(null, ['type' => 'get', 'class' => 'form-material os-filter-form w-100', 'url' => ['controller' => 'Ordensservico', 'action' => 'index']]); ?>
			<div class="row">
				<?php if ($role == 0) { ?>
					<div class="col-lg-2 col-md-3 col-sm-6 col-12">
						<p>Situação</p>
						<?= $this->Form->control('situacao', ['data-live-search' => true, 'title' => 'Todas', 'value' => $situacao, 'id' => 'situacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensSituacao, 'label' => false]) ?>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-6 col-12">
						<p>Problema</p>
						<?= $this->Form->control('problema', ['data-live-search' => true, 'title' => 'Todos', 'value' => $problema, 'id' => 'problema', 'class' => 'form-control selectpicker', 'options' => $problemas, 'label' => false]) ?>
					</div>
					<div class="col-lg-4 col-md-6 col-12">
						<p>Cliente</p>
						<?= $this->Form->control('cliente', ['data-live-search' => true, 'title' => 'Todos', 'value' => strtoupper($cliente), 'class' => 'form-control selectpicker', 'id' => 'cliente', 'options' => $clientes, 'label' => false]) ?>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-6 col-12">
						<p>Tipo</p>
						<?= $this->Form->control('locacao', ['data-live-search' => true, 'title' => 'Todos', 'value' => $locacao, 'id' => 'locacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensLocacao, 'label' => false]) ?>
					</div>
				<?php } else { ?>
					<div class="col-md-4 col-12">
						<p>Situação</p>
						<?= $this->Form->control('situacao', ['data-live-search' => true, 'title' => 'Todas', 'value' => $situacao, 'id' => 'situacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensSituacao, 'label' => false]) ?>
					</div>
				<?php } ?>
			</div>
			<?= $this->Form->end(); ?>
		</div>

		<div class="os-table-outer">
			<div class="table-responsive os-table-responsive">
				<table class="table table-hover table-row-clickable" id="tableOrdens" style="margin:0">
					<thead>
						<tr>
							<th class="os-th-num">
								<?php if ($role == 0) : ?>
								<div class="os-num-wrap">
									<input type="checkbox" id="os-check-all" class="os-check-all" title="Selecionar todas nesta página" aria-label="Selecionar todas as OS visíveis" />
									<span>Nº</span>
								</div>
								<?php else : ?>
								Nº
								<?php endif; ?>
							</th>
							<th>Abertura</th>
							<th>Previsão</th>
							<th>Cliente</th>
							<th>Contrato</th>
							<th>Técnico</th>
							<th class="text-right">Vl. Total</th>
							<th>Situação</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$action = $role == 0 ? 'edit' : 'view';
						foreach ($ordens as $reg) :
							$situacaoTxt = SituacaoOrdem($reg->situacao);
							list($pillClass, $pillShort) = $osStatusPill($situacaoTxt);
							$cliNome = $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial;
							$contratoLbl = OrdensContrato($reg->contrato);
							$contratoPlain = trim(strip_tags((string)$contratoLbl));
							$contratoClass = ($contratoPlain === 'Sim' || stripos($contratoPlain, 'sim') === 0) ? 'contract-yes' : 'contract-no';
							$rowClass = $reg->locacao ? 'os-row-locacao' : '';
							$rowPayload = [
								'id' => (int)$reg->id,
								'abertura' => date_format($reg->dataabertura, 'd/m/Y'),
								'previsao' => date_format($reg->dataprevisao, 'd/m/Y'),
								'cliente' => $cliNome,
								'contrato' => $contratoPlain,
								'tecnico' => $reg->user ? ($reg->user->name ?? '') : '',
								'valor' => number_format($reg->valortotal, 2, ',', '.'),
								'situacao' => trim(strip_tags((string)$situacaoTxt)),
								'url' => $this->Url->build(['action' => $action, $reg->id]),
							];
							$dataOs = htmlspecialchars(json_encode($rowPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
							?>
							<tr class="<?= h($rowClass) ?> os-row-drawer" data-os="<?= $dataOs ?>">
								<td class="os-th-num" data-order="<?= h($reg->id) ?>">
									<div class="os-num-wrap">
										<?php if ($role == 0) : ?>
										<div class="custom-control custom-checkbox os-row-checkbox mb-0">
											<input data-id="<?= h($reg->id) ?>" type="checkbox" class="custom-control-input checkbox" id="checkbox<?= h($reg->id) ?>" value="check">
											<label class="custom-control-label p-0 m-0" for="checkbox<?= h($reg->id) ?>" style="min-height:0"><span class="sr-only">Selecionar OS <?= h($reg->id) ?></span></label>
										</div>
										<?php endif; ?>
										<span class="link os-num-cell" role="button" tabindex="0">#<?= h($reg->id) ?></span>
									</div>
								</td>
								<td data-order="<?= date_format($reg->dataabertura, 'Ymd') ?>">
									<span class="link"><?= h(date_format($reg->dataabertura, 'd/m/Y')) ?></span>
								</td>
								<td data-order="<?= date_format($reg->dataprevisao, 'Ymd') ?>">
									<span class="link"><?= h(date_format($reg->dataprevisao, 'd/m/Y')) ?></span>
								</td>
								<td>
									<span class="link" title="<?= h($cliNome) ?>">
										<span class="os-client-cell"><?= h($cliNome) ?></span>
									</span>
								</td>
								<td>
									<span class="link">
										<span class="contract-badge <?= h($contratoClass) ?>"><?= h($contratoLbl) ?></span>
									</span>
								</td>
								<td>
									<span class="link">
										<span class="os-tech">
											<span class="os-tech-av"><?= $osTechInitials($reg->user ? ($reg->user->name ?? '') : '') ?></span>
											<?= h($reg->user ? ($reg->user->name ?? '—') : '—') ?>
										</span>
									</span>
								</td>
								<td class="text-right">
									<span class="link os-valor-cell"><?= h(number_format($reg->valortotal, 2, ',', '.')) ?></span>
								</td>
								<td>
									<span class="link">
										<span class="os-status <?= h($pillClass) ?>">
											<span class="os-status-dot"></span>
											<?= $pillShort ?>
										</span>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php if ($role == 0) : ?>
<div class="os-modal-nova-overlay" id="os-modal-nova-overlay" aria-hidden="true">
	<div class="os-modal-nova" role="dialog" aria-modal="true" aria-labelledby="os-modal-nova-title" onclick="event.stopPropagation()">
		<div class="os-modal-nova-head">
			<div>
				<div class="os-modal-nova-bc">Ordens de Serviço &rsaquo; <em>Nova Ordem</em></div>
				<h2 id="os-modal-nova-title">Nova Ordem de Serviço</h2>
			</div>
			<button type="button" class="os-modal-nova-x" id="os-modal-nova-close" aria-label="Fechar">×</button>
		</div>
		<form class="os-modal-nova-inner" id="os-modal-nova-form" novalidate>
			<p class="os-modal-nova-hint">Indique o cliente (obrigatório). O cadastro completo da OS abre em nova aba com o fluxo atual do sistema.</p>
			<div class="os-modal-nova-field">
				<label for="os-nova-cliente">Cliente *</label>
				<input type="text" id="os-nova-cliente" name="os_nova_cliente_hint" autocomplete="organization" placeholder="Nome ou razão social" />
			</div>
			<div class="os-modal-nova-field">
				<label for="os-nova-desc">Descrição / problema (opcional)</label>
				<textarea id="os-nova-desc" rows="3" placeholder="Resumo para referência ao abrir o cadastro…"></textarea>
			</div>
			<div class="os-modal-nova-foot">
				<button type="button" class="os-modal-nova-btn os-modal-nova-btn-ghost" id="os-modal-nova-cancel">Cancelar</button>
				<button type="submit" class="os-modal-nova-btn os-modal-nova-btn-primary">Continuar para cadastro completo</button>
			</div>
		</form>
	</div>
</div>
<?php endif; ?>

<div class="os-drawer-backdrop" id="os-drawer-backdrop" aria-hidden="true"></div>
<aside class="os-drawer" id="os-drawer" aria-hidden="true" aria-labelledby="os-drawer-title">
	<div class="os-drawer-head">
		<div class="os-drawer-num" id="os-drawer-title">#—</div>
		<div class="os-drawer-status" id="os-drawer-status"></div>
		<button type="button" class="os-drawer-x" id="os-drawer-close" aria-label="Fechar">×</button>
	</div>
	<div class="os-drawer-body" id="os-drawer-body"></div>
	<div class="os-drawer-foot">
		<button type="button" class="os-drawer-btn os-drawer-btn-ghost" id="os-drawer-close2">Fechar</button>
		<button type="button" class="os-drawer-btn os-drawer-btn-primary" id="os-drawer-edit-btn" data-href="#">Editar OS</button>
	</div>
</aside>

<!-- Modal Ação (fora do shell escuro para manter contraste Bootstrap) -->
<div class="modal fade none-border" id="modal-acao">
	<div class="modal-dialog modal-lg">
		<div class="modal-content m-20">
			<?= $this->Form->create(null, ['class' => 'form-material', 'url' => ['controller' => 'Ordensservico', 'action' => 'acaoindex']]); ?>
			<div class="row m-20">
				<?= $this->Form->control('ids', ['type' => 'hidden', 'label' => false]); ?>
				<div class="col-md-4 col-xs-12">
					<label class="control-label">Situação</label>
					<?= $this->Form->control('situacao', ['value' => C_OrdensSituacaoLiberadaParaFaturamento, 'id' => 'situacaomodal', 'class' => 'form-control', 'options' => C_OrdensSituacaoOpcoes, 'label' => false, 'required' => true]) ?>
				</div>
				<div class="col-md-4 col-xs-12 liberada">
					<label class="control-label">Pagamento</label>
					<?= $this->Form->control('pagamento', ['options' => C_OrdensPagamento, 'class' => 'form-control', 'label' => false]) ?>
				</div>
				<div class="col-md-4 col-xs-12 liberada">
					<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10 m-t-25">
						<?= $this->Form->checkbox('entrada', ['class' => 'custom-control-input', 'id' => 'entrada']); ?>
						<label class="custom-control-label text-muted" for="entrada">Primeira parcela recebida como Entrada</label>
					</div>
				</div>
				<div class="col-md-2 col-xs-12 liberada">
					<label class="control-label">Parcelas</label>
					<?= $this->Form->control('nmrparcelas', ['id' => 'nmrparcelas', 'options' => C_OrdensParcelas, 'class' => 'form-control', 'label' => false]) ?>
				</div>
				<div class="col-md-2 col-xs-12 liberada">
					<label class="control-label text-muted dataval1">Parcela 1 </label>
					<?= $this->Form->text('dataval1', ['id' => 'dataval1', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval1', 'label' => false]) ?>
				</div>
				<div class="col-md-2 col-xs-12 liberada">
					<label class="control-label text-muted dataval2">Parcela 2 </label>
					<?= $this->Form->text('dataval2', ['id' => 'dataval2', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval2', 'label' => false]) ?>
				</div>
				<div class="col-md-2 col-xs-12 liberada">
					<label class="control-label text-muted dataval3">Parcela 3 </label>
					<?= $this->Form->text('dataval3', ['id' => 'dataval3', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval3', 'label' => false]) ?>
				</div>
				<div class="col-md-2 col-xs-12 liberada">
					<label class="control-label text-muted dataval4">Parcela 4 </label>
					<?= $this->Form->text('dataval4', ['id' => 'dataval4', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval4', 'label' => false]) ?>
				</div>
				<div class="col-md-2 col-xs-12 liberada">
					<label class="control-label text-muted dataval5">Parcela 5 </label>
					<?= $this->Form->text('dataval5', ['id' => 'dataval5', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval5', 'label' => false]) ?>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Form->button('Salvar', ['class' => 'btn btn-success text-white m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>

<script>
	var osUserRole = <?= (int)$role ?>;
	var osAddUrl = <?= $role == 0 ? json_encode($this->Url->build(['action' => 'add'])) : 'null' ?>;
	var osDrawerActiveRow = null;
	var osKpiSituacaoMap = {
		exec: <?= json_encode(C_OrdensSituacaoEmExecucao) ?>,
		sync: <?= json_encode(C_OrdensSituacaoSincronizadaPeloGrid) ?>,
		lib: <?= json_encode(C_OrdensSituacaoLiberadaParaFaturamento) ?>
	};

	function osEscapeHtml(text) {
		return $('<div/>').text(text == null ? '' : String(text)).html();
	}

	function osStatusClass(s) {
		s = String(s || '');
		if (s === 'Em execução') return 'os-st-exec';
		if (s.indexOf('Sincronizada') !== -1 && s.indexOf('Grid') !== -1) return 'os-st-sync';
		if (s.indexOf('Liberada') !== -1 && s.indexOf('sincroniz') !== -1) return 'os-st-lib';
		return 'os-st-default';
	}

	function osStatusShort(s) {
		s = String(s || '');
		if (s.indexOf('Sincronizada') !== -1 && s.indexOf('Grid') !== -1) return 'Sincronizada';
		if (s.indexOf('Liberada') !== -1 && s.indexOf('sincroniz') !== -1) return 'Liberada';
		return s;
	}

	function osApplySituacaoToUrl(sit) {
		var u = new URL(window.location.href);
		if (sit === '' || sit == null) {
			u.searchParams.delete('situacao');
		} else {
			u.searchParams.set('situacao', String(sit));
		}
		window.location.href = u.toString();
	}

	function osClearDrawerRowHighlight() {
		if (osDrawerActiveRow && osDrawerActiveRow.length) {
			osDrawerActiveRow.removeClass('os-row-drawer-active');
		}
		osDrawerActiveRow = null;
	}

	function osCloseDrawer() {
		osClearDrawerRowHighlight();
		$('#os-drawer').removeClass('open').attr('aria-hidden', 'true');
		$('#os-drawer-backdrop').removeClass('open');
	}

	function osOpenDrawer(row, $tr) {
		if (!row || !row.id) return;
		osClearDrawerRowHighlight();
		if ($tr && $tr.length) {
			$tr.addClass('os-row-drawer-active');
			osDrawerActiveRow = $tr;
		}
		$('#os-drawer-title').text('#' + row.id);
		var pillClass = osStatusClass(row.situacao);
		var pillShort = osStatusShort(row.situacao);
		$('#os-drawer-status').html('<span class="os-status ' + pillClass + '"><span class="os-status-dot"></span>' + osEscapeHtml(pillShort) + '</span>');
		var contratoHtml = row.contrato === 'Sim' ? '<span style="color:var(--os-green)">Sim</span>' : '<span style="color:var(--os-text3)">Não</span>';
		$('#os-drawer-body').html(
			'<div class="os-dr-sec"><div class="os-dr-sec-t">Informações gerais</div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Cliente</span><span class="os-dr-v">' + osEscapeHtml(row.cliente) + '</span></div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Técnico</span><span class="os-dr-v">' + osEscapeHtml(row.tecnico) + '</span></div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Contrato</span><span class="os-dr-v">' + contratoHtml + '</span></div></div>' +
			'<div class="os-dr-sec"><div class="os-dr-sec-t">Datas</div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Abertura</span><span class="os-dr-v">' + osEscapeHtml(row.abertura) + '</span></div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Previsão</span><span class="os-dr-v">' + osEscapeHtml(row.previsao) + '</span></div></div>' +
			'<div class="os-dr-sec"><div class="os-dr-sec-t">Financeiro</div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Valor total</span><span class="os-dr-v" style="font-family:var(--os-mono);font-size:15px;color:var(--os-text)">R$ ' + osEscapeHtml(row.valor) + '</span></div></div>' +
			'<div class="os-dr-sec"><div class="os-dr-sec-t">Situação</div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Status</span><span class="os-dr-v">' + osEscapeHtml(row.situacao) + '</span></div></div>'
		);
		$('#os-drawer-edit-btn').attr('data-href', row.url).text(osUserRole === 0 ? 'Editar OS' : 'Abrir OS');
		$('#os-drawer').addClass('open').attr('aria-hidden', 'false');
		$('#os-drawer-backdrop').addClass('open');
	}

	$(document).ready(function() {
		$('#situacao, #cliente, #problema, #locacao').on('change', function() {
			this.form.submit();
		});

		$(document).on('click', '[data-os-kpi]', function() {
			var k = $(this).data('os-kpi');
			if (k === 'all') {
				osApplySituacaoToUrl('');
			} else if (osKpiSituacaoMap[k] != null) {
				osApplySituacaoToUrl(osKpiSituacaoMap[k]);
			}
		});
		$(document).on('keydown', '[data-os-kpi]', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				$(this).trigger('click');
			}
		});

		$('#os-drawer-backdrop, #os-drawer-close, #os-drawer-close2').on('click', function() {
			osCloseDrawer();
		});
		$('#os-drawer-edit-btn').on('click', function() {
			var u = $(this).attr('data-href');
			if (u && u !== '#') {
				window.open(u, '_blank', 'noopener');
			}
		});
		$(document).on('keydown', function(e) {
			if (e.key !== 'Escape') return;
			if ($('#os-drawer').hasClass('open')) {
				osCloseDrawer();
				return;
			}
			if ($('#os-modal-nova-overlay').hasClass('open')) {
				osNovaClose();
			}
		});

		function osNovaOpen() {
			$('#os-modal-nova-overlay').addClass('open').attr('aria-hidden', 'false');
			$('#os-nova-cliente').removeClass('os-invalid').focus();
		}
		function osNovaClose() {
			$('#os-modal-nova-overlay').removeClass('open').attr('aria-hidden', 'true');
			$('#os-nova-cliente').removeClass('os-invalid');
		}
		if ($('#os-open-modal-nova').length && osAddUrl) {
			$('#os-open-modal-nova').on('click', osNovaOpen);
			$('#os-modal-nova-close, #os-modal-nova-cancel').on('click', osNovaClose);
			$('#os-modal-nova-overlay').on('click', function() {
				osNovaClose();
			});
			$('#os-modal-nova-form').on('submit', function(ev) {
				ev.preventDefault();
				var c = $('#os-nova-cliente').val().trim();
				if (!c.length) {
					$('#os-nova-cliente').addClass('os-invalid').focus();
					return;
				}
				$('#os-nova-cliente').removeClass('os-invalid');
				window.open(osAddUrl, '_blank', 'noopener');
				osNovaClose();
				$('#os-nova-cliente').val('');
				$('#os-nova-desc').val('');
			});
		}

		function osBulkUi() {
			var $bar = $('#os-bulk-bar');
			if (!$bar.length) return;
			var raw = (window.ids || '').split(',').filter(function (x) { return x !== ''; });
			var n = raw.length;
			if (n) {
				$bar.addClass('visible');
				$('#os-bulk-count').text(n + ' ordem(ns) selecionada(s)');
			} else {
				$bar.removeClass('visible');
				$('#os-bulk-count').text('');
			}
		}

		function osSyncRowSelClass() {
			var set = {};
			(window.ids || '').split(',').forEach(function (id) {
				if (id) {
					set[id] = true;
				}
			});
			$('#tableOrdens tbody tr.os-row-drawer').each(function () {
				var raw = $(this).attr('data-os');
				if (!raw) return;
				try {
					var r = JSON.parse(raw);
					$(this).toggleClass('os-row-sel', !!set[String(r.id)]);
				} catch (e1) {}
			});
		}

		function osRebuildIdsFromCheckboxes() {
			window.ids = '';
			$('#tableOrdens tbody .checkbox:checked').each(function () {
				window.ids += $(this).attr('data-id') + ',';
			});
			if (window.ids !== '') {
				$('.btn-acao, .btn-imprimir').show();
			} else {
				$('.btn-acao, .btn-imprimir').hide();
			}
			$('#ids').val(window.ids);
			$('#idsimprimir').val(window.ids);
			osBulkUi();
			osSyncRowSelClass();
			var $all = $('#os-check-all');
			if ($all.length) {
				var $boxes = $('#tableOrdens tbody .checkbox');
				var n = $boxes.length;
				var c = $boxes.filter(':checked').length;
				$all.prop('checked', n > 0 && c === n);
				$all.prop('indeterminate', c > 0 && c < n);
			}
		}

		$('#os-bulk-clear').on('click', function() {
			window.ids = '';
			$('.checkbox').prop('checked', false);
			$('#os-check-all').prop('checked', false).prop('indeterminate', false);
			$('#ids').val('');
			$('#idsimprimir').val('');
			$('.btn-acao, .btn-imprimir').hide();
			osBulkUi();
			osSyncRowSelClass();
		});

		$('#os-check-all').on('change', function () {
			var on = $(this).prop('checked');
			$('#tableOrdens tbody .checkbox').prop('checked', on);
			osRebuildIdsFromCheckboxes();
		});

		var table = $('#tableOrdens');
		table.on('length.dt', function (e, settings, len) {
			pagelength(len);
		});
		if (typeof $.fn.selectpicker === 'function') {
			$('.os-index-shell .os-filter-form select.selectpicker').each(function () {
				var $s = $(this);
				if ($s.parent().hasClass('bootstrap-select')) {
					return;
				}
				$s.selectpicker({
					liveSearch: true,
					style: '',
					size: 8,
					container: 'body'
				});
			});
		}
		var osDtInitialFilter = (typeof window.filters !== 'undefined' && window.filters != null) ? String(window.filters) : '';
		table.DataTable({
			order: [[0, 'desc']],
			pageLength: <?= (int)$pagelength ?>,
			language: {
				sProcessing: 'Processando…',
				sLengthMenu: 'Mostrar _MENU_ registros',
				sZeroRecords: 'Nenhum registro encontrado',
				sEmptyTable: 'Nenhum dado disponível',
				sInfo: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
				sInfoEmpty: 'Mostrando 0 a 0 de 0 registros',
				sInfoFiltered: '(filtrado de _MAX_ registros)',
				sSearch: 'Buscar:',
				sInfoThousands: '.',
				sLoadingRecords: 'Carregando…',
				oPaginate: { sFirst: '<<', sLast: '>>', sNext: '>', sPrevious: '<' },
				oAria: { sSortAscending: ': ordem ascendente', sSortDescending: ': ordem descendente' }
			},
			initComplete: function () {
				var $f = $('.os-index-shell .dataTables_filter');
				var $host = $('#pgm-sidebar-dt-host');
				if ($host.length) {
					$('#pgm-sidebar-functions-search').hide();
					$host.show().append($f);
					$f.find('input[type="search"]').attr('placeholder', 'Buscar OS, cliente, técnico…');
				}
				$f.find('input[type="search"]').attr('placeholder', 'Buscar OS, cliente, técnico…');
			}
		});
		table.search(osDtInitialFilter).draw();
		osBulkUi();
		if ($('#badge-exec-os').length) {
			$('#badge-exec-os').text('<?= (int)$kpiExec ?>');
		}

		function osRowOpenDrawer($tr) {
			var raw = $tr.attr('data-os');
			if (!raw) return;
			try {
				osOpenDrawer(JSON.parse(raw), $tr);
			} catch (err) {
				return;
			}
		}

		$('#tableOrdens tbody').on('click', 'tr.os-row-drawer', function(e) {
			if ($(e.target).closest('input, label, .custom-control, button, a').length) {
				return;
			}
			osRowOpenDrawer($(this));
		});

		$('#tableOrdens tbody').on('keydown', 'tr.os-row-drawer .link[role="button"]', function(e) {
			if (e.key !== 'Enter' && e.key !== ' ') return;
			e.preventDefault();
			osRowOpenDrawer($(this).closest('tr.os-row-drawer'));
		});

		$('#os-btn-export-csv').on('click', function() {
			var dt = table.DataTable();
			var headers = [];
			$('#tableOrdens thead th').each(function() {
				headers.push($(this).text().trim().replace(/\s+/g, ' '));
			});
			var lines = [headers.join(';')];
			dt.rows({ search: 'applied' }).every(function() {
				var $tr = $(this.node());
				var cols = [];
				$tr.find('td').each(function(i) {
					var cell = $(this);
					var txt;
					if (i === 0) {
						try {
							var row = JSON.parse($tr.attr('data-os'));
							txt = '#' + row.id;
						} catch (e2) {
							txt = cell.text().trim();
						}
					} else {
						txt = cell.text().trim();
					}
					cols.push('"' + String(txt).replace(/"/g, '""') + '"');
				});
				lines.push(cols.join(';'));
			});
			var blob = new Blob(['\ufeff' + lines.join('\n')], { type: 'text/csv;charset=utf-8' });
			var a = document.createElement('a');
			a.href = URL.createObjectURL(blob);
			a.download = 'ordens_servico.csv';
			a.click();
			URL.revokeObjectURL(a.href);
			if (typeof $.toast === 'function') {
				$.toast({
					heading: 'Exportação',
					text: 'CSV gerado com os registros do filtro atual da tabela.',
					icon: 'success',
					position: 'bottom-right',
					hideAfter: 3500,
					loaderBg: '#3d7eff'
				});
			}
		});

		$(document).on('change', '#tableOrdens tbody .checkbox', function () {
			osRebuildIdsFromCheckboxes();
		});
	});

	window.onload = function() {
		var $inp = $('#pgm-sidebar-dt-host input[type="search"]');
		if ($inp.length) {
			$inp.focus();
		}
	};

	window.ids = '';

	$(document).on('click', '.btn-acao', function(e) {
		e.preventDefault();
		$('#modal-acao').modal('toggle');
	});

	$(document).on('click', '.btn-imprimir', function(e) {
		e.preventDefault();
		$('#formimprimir').submit();
	});

	$(function () {
		$('.dataval2, .dataval3, .dataval4, .dataval5').hide();
		$('#situacaomodal').on('change', function () {
			if ($(this).val() == <?= json_encode(C_OrdensSituacaoLiberadaParaFaturamento) ?>) {
				$('.liberada').show();
			} else {
				$('.liberada').hide();
			}
		});
		$('#nmrparcelas').on('change', function () {
			var nmrparcelas = $(this).val();
			switch (nmrparcelas) {
				case '1':
					$('.dataval2, .dataval3, .dataval4, .dataval5').hide();
					break;
				case '2':
					$('.dataval3, .dataval4, .dataval5').hide();
					$('.dataval2').show();
					break;
				case '3':
					$('.dataval4, .dataval5').hide();
					$('.dataval2, .dataval3').show();
					break;
				case '4':
					$('.dataval5').hide();
					$('.dataval2, .dataval3, .dataval4').show();
					break;
				case '5':
					$('.dataval2, .dataval3, .dataval4, .dataval5').show();
					break;
				default:
					break;
			}
		});
	});
</script>
