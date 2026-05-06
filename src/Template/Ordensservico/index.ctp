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
				<?= $this->Html->link(
					'<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 2h5l3 3v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.2"/><path d="M9 2v3h3" stroke="currentColor" stroke-width="1.2"/></svg><span>Relatórios</span>',
					['action' => 'relatorios'],
					['class' => 'os-page-head-link', 'escape' => false, 'title' => 'Relatórios: visualizar, PDF e e-mail']
				) ?>
				<button type="button" class="os-icon-btn os-icon-btn--btn" id="os-btn-export-csv" title="Exportar CSV (filtro atual da tabela)">
					<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M9 1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V6L9 1Z" stroke="currentColor" stroke-width="1.3"/><path d="M9 1v5h5" stroke="currentColor" stroke-width="1.3"/><path d="M8 10v4M6 12l2 2 2-2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
				</button>
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
					<div class="col-lg-3 col-md-6 col-sm-6 col-12">
						<p>Situação</p>
						<?= $this->Form->control('situacao', ['title' => 'Todas', 'empty' => 'Todos', 'value' => $situacao, 'id' => 'situacao', 'class' => 'form-control os-filter-native-select', 'options' => C_OrdensSituacao, 'label' => false]) ?>
					</div>
					<div class="col-lg-3 col-md-6 col-sm-6 col-12">
						<p>Problema</p>
						<?= $this->Form->control('problema', ['data-live-search' => true, 'title' => 'Todos', 'value' => $problema, 'id' => 'problema', 'class' => 'form-control selectpicker', 'options' => $problemas, 'label' => false]) ?>
					</div>
					<div class="col-lg-3 col-md-6 col-12">
						<p>Cliente</p>
						<?= $this->Form->control('cliente', ['data-live-search' => true, 'title' => 'Todos', 'value' => $cliente, 'class' => 'form-control selectpicker', 'id' => 'cliente', 'options' => $clientes, 'label' => false]) ?>
					</div>
					<div class="col-lg-3 col-md-6 col-sm-6 col-12">
						<p>Tipo</p>
						<?= $this->Form->control('locacao', ['title' => 'Todos', 'value' => $locacao, 'id' => 'locacao', 'class' => 'form-control os-filter-native-select', 'options' => C_OrdensLocacao, 'label' => false]) ?>
					</div>
				<?php } else { ?>
					<div class="col-md-4 col-12">
						<p>Situação</p>
						<?= $this->Form->control('situacao', ['title' => 'Todas', 'empty' => 'Todos', 'value' => $situacao, 'id' => 'situacao', 'class' => 'form-control os-filter-native-select', 'options' => C_OrdensSituacao, 'label' => false]) ?>
					</div>
				<?php } ?>
			</div>
			<?= $this->Form->end(); ?>
		</div>

		<div class="os-table-outer">
			<div class="os-table-head">
				<div class="os-table-head-l">Mostrando <strong id="os-showing">0</strong> de <strong id="os-total">0</strong> registros</div>
				<div class="os-table-head-r">
					<label class="os-per-page" for="os-per-page">
						Por página:
						<select id="os-per-page">
							<option value="10"<?= (int)$pagelength === 10 ? ' selected' : '' ?>>10</option>
							<option value="25"<?= (int)$pagelength === 25 ? ' selected' : '' ?>>25</option>
							<option value="50"<?= (int)$pagelength === 50 ? ' selected' : '' ?>>50</option>
							<option value="100"<?= (int)$pagelength === 100 ? ' selected' : '' ?>>100</option>
						</select>
					</label>
				</div>
			</div>
			<div class="table-responsive os-table-responsive">
				<table class="table table-hover table-row-clickable os-table-flush" id="tableOrdens">
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
						$osRowsById = [];
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
								'idcliente' => (int)$reg->idcliente,
								'situacao_id' => (int)$reg->situacao,
								'idproblema' => (int)$reg->idproblema,
								'locacao' => (int)$reg->locacao,
								'url' => $this->Url->build(['action' => $action, $reg->id]),
							];
							$osRowsById[(int)$reg->id] = $rowPayload;
							?>
							<tr class="<?= h($rowClass) ?> os-row-drawer" data-os-id="<?= (int)$reg->id ?>">
								<td class="os-th-num" data-order="<?= h($reg->id) ?>">
									<div class="os-num-wrap">
										<?php if ($role == 0) : ?>
										<div class="custom-control custom-checkbox os-row-checkbox mb-0">
											<input data-id="<?= h($reg->id) ?>" type="checkbox" class="custom-control-input checkbox" id="checkbox<?= h($reg->id) ?>" value="check">
											<label class="custom-control-label p-0 m-0 os-checkbox-label-flush" for="checkbox<?= h($reg->id) ?>"><span class="sr-only">Selecionar OS <?= h($reg->id) ?></span></label>
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
				<button type="submit" class="os-modal-nova-btn os-modal-nova-btn-primary btn btn-pgm btn-pgm-salvar">Continuar para cadastro completo</button>
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
		<button type="button" class="os-drawer-btn os-drawer-btn-ghost" id="os-drawer-close2" title="Volta à listagem de ordens">Fechar</button>
		<button type="button" class="os-drawer-btn os-drawer-btn-primary btn btn-pgm btn-pgm-situacao" id="os-drawer-edit-btn" data-href="#" title="Abre o cadastro completo da OS">Editar OS</button>
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
				<?= $this->Form->button('Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success text-white m-l-5']) ?>
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
		open: <?= json_encode(C_OrdensSituacaoAberta) ?>,
		exec: <?= json_encode(C_OrdensSituacaoEmExecucao) ?>,
		sync: <?= json_encode(C_OrdensSituacaoSincronizadaPeloGrid) ?>,
		lib: <?= json_encode(C_OrdensSituacaoLiberadaParaFaturamento) ?>
	};
	var osInitialFilters = {
		situacao: <?= json_encode((string)$situacao) ?>,
		cliente: <?= json_encode((string)$cliente) ?>,
		problema: <?= json_encode((string)$problema) ?>,
		locacao: <?= json_encode((string)$locacao) ?>
	};
	var osUseDefaultOperationalFilter = false;
	<?php
	$_osIndexJson = json_encode(!empty($osRowsById) ? $osRowsById : new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
	if ($_osIndexJson === false) {
		$_osIndexJson = '{}';
	}
	?>
	var osIndexRowsById = <?= $_osIndexJson ?>;

	/** ID da linha: data-os-id ou fallback da 1ª célula (DataTables pode remover data-os-id ao redesenhar). */
	function osResolveOsRowId($tr) {
		if (!$tr || !$tr.length) return '';
		var rid = $tr.attr('data-os-id');
		if (rid != null && rid !== '') return String(rid);
		var order = $tr.find('td').first().attr('data-order');
		if (order != null && order !== '') {
			$tr.attr('data-os-id', order);
			return String(order);
		}
		return '';
	}

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

	function osExtractRowDisplay($tr, row) {
		var $td = $tr.find('td');
		var openTxt = $.trim($td.eq(1).text());
		var prevTxt = $.trim($td.eq(2).text());
		var cliTxt = $.trim($td.eq(3).text());
		var conTxt = $.trim($td.eq(4).text());
		var tecTxt = $.trim($td.eq(5).text());
		var valTxt = $.trim($td.eq(6).text());
		var sitTxt = $.trim($td.eq(7).text());
		return {
			id: row && row.id ? row.id : osResolveOsRowId($tr),
			abertura: openTxt,
			previsao: prevTxt,
			cliente: cliTxt,
			contrato: conTxt,
			tecnico: tecTxt,
			valor: valTxt,
			situacao: sitTxt
		};
	}

	function osSyncQueryFromFilters() {
		var u = new URL(window.location.href);
		var params = {
			situacao: $('#situacao').val(),
			cliente: $('#cliente').val(),
			problema: $('#problema').val(),
			locacao: $('#locacao').val()
		};
		Object.keys(params).forEach(function(k) {
			var v = params[k];
			if (v == null || v === '' || v === '-1' || v === '0') {
				u.searchParams.delete(k);
			} else {
				u.searchParams.set(k, String(v));
			}
		});
		window.history.replaceState({}, '', u.toString());
	}

	function osClearDrawerRowHighlight() {
		if (osDrawerActiveRow && osDrawerActiveRow.length) {
			osDrawerActiveRow.removeClass('os-row-drawer-active');
		}
		osDrawerActiveRow = null;
	}

	function osCloseDrawer() {
		osClearDrawerRowHighlight();
		$('body').removeClass('os-drawer-active');
		$('#os-drawer').removeClass('open').attr('aria-hidden', 'true');
		$('#os-drawer-backdrop').removeClass('open');
	}

	function osOpenDrawer(row, $tr) {
		if (!row || !row.id) return;
		var disp = osExtractRowDisplay($tr, row);
		osClearDrawerRowHighlight();
		if ($tr && $tr.length) {
			$tr.addClass('os-row-drawer-active');
			osDrawerActiveRow = $tr;
		}
		$('#os-drawer-title').text('#' + disp.id);
		var pillClass = osStatusClass(disp.situacao);
		var pillShort = osStatusShort(disp.situacao);
		$('#os-drawer-status').html('<span class="os-status ' + pillClass + '"><span class="os-status-dot"></span>' + osEscapeHtml(pillShort) + '</span>');
		var contratoHtml = disp.contrato === 'Sim' ? '<span class="os-dr-contract-yes">Sim</span>' : '<span class="os-dr-contract-no">Não</span>';
		$('#os-drawer-body').html(
			'<div class="os-dr-sec"><div class="os-dr-sec-t">Informações gerais</div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Cliente</span><span class="os-dr-v">' + osEscapeHtml(disp.cliente) + '</span></div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Técnico</span><span class="os-dr-v">' + osEscapeHtml(disp.tecnico) + '</span></div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Contrato</span><span class="os-dr-v">' + contratoHtml + '</span></div></div>' +
			'<div class="os-dr-sec"><div class="os-dr-sec-t">Datas</div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Abertura</span><span class="os-dr-v">' + osEscapeHtml(disp.abertura) + '</span></div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Previsão</span><span class="os-dr-v">' + osEscapeHtml(disp.previsao) + '</span></div></div>' +
			'<div class="os-dr-sec"><div class="os-dr-sec-t">Financeiro</div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Valor total</span><span class="os-dr-v os-dr-v-valor">R$ ' + osEscapeHtml(disp.valor) + '</span></div></div>' +
			'<div class="os-dr-sec"><div class="os-dr-sec-t">Situação</div>' +
			'<div class="os-dr-row"><span class="os-dr-k">Status</span><span class="os-dr-v">' + osEscapeHtml(disp.situacao) + '</span></div></div>'
		);
		$('#os-drawer-edit-btn').attr('data-href', row.url).text(osUserRole === 0 ? 'Editar OS' : 'Abrir OS');
		$('body').addClass('os-drawer-active');
		$('#os-drawer').addClass('open').attr('aria-hidden', 'false');
		$('#os-drawer-backdrop').addClass('open');
	}

	$(document).ready(function() {
		$('body').addClass('os-index-page');
		/* Drawer no body: evita ficar atrás da sidebar (stacking context do .page-wrapper) */
		$('#os-drawer-backdrop, #os-drawer').appendTo('body');

		$('#situacao, #cliente, #problema, #locacao').on('change', function() {
			if (this && this.id === 'situacao') {
				osUseDefaultOperationalFilter = false;
			}
			osApplyClientFilters();
		});

		$(document).on('click', '[data-os-kpi]', function() {
			var k = $(this).data('os-kpi');
			osUseDefaultOperationalFilter = false;
			if (k === 'all') {
				$('#situacao').val('');
			} else if (osKpiSituacaoMap[k] != null) {
				$('#situacao').val(String(osKpiSituacaoMap[k]));
			}
			osApplyClientFilters();
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
				osCloseDrawer();
				var w = window.open(u, '_blank');
				if (w) {
					w.opener = null;
				}
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
			$('#tableOrdens tbody tr').each(function () {
				var rid = osResolveOsRowId($(this));
				if (rid === '') return;
				$(this).toggleClass('os-row-sel', !!set[rid]);
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

		var $osTable = $('#tableOrdens');
		$osTable.on('length.dt', function (e, settings, len) {
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
		function osRowPassesFilters(row) {
			if (!row) return false;
			var fSituacao = $('#situacao').val();
			var fCliente = $('#cliente').val();
			var fProblema = $('#problema').val();
			var fLocacao = $('#locacao').val();
			if (fSituacao) {
				if (String(row.situacao_id) !== String(fSituacao)) return false;
			} else if (osUseDefaultOperationalFilter) {
				var isOpen = String(row.situacao_id) === String(osKpiSituacaoMap.open);
				var isExec = String(row.situacao_id) === String(osKpiSituacaoMap.exec);
				if (!isOpen && !isExec) return false;
			}
			if (fCliente && fCliente !== '0' && String(row.idcliente) !== String(fCliente)) return false;
			if (fProblema && fProblema !== '0' && String(row.idproblema) !== String(fProblema)) return false;
			if (fLocacao && fLocacao !== '-1' && String(row.locacao) !== String(fLocacao)) return false;
			return true;
		}
		function osRefreshKpiActive() {
			var sit = $('#situacao').val();
			var active = 'all';
			if (sit && String(sit) === String(osKpiSituacaoMap.exec)) active = 'exec';
			if (sit && String(sit) === String(osKpiSituacaoMap.sync)) active = 'sync';
			if (sit && String(sit) === String(osKpiSituacaoMap.lib)) active = 'lib';
			$('[data-os-kpi]').removeClass('is-active');
			$('[data-os-kpi="' + active + '"]').addClass('is-active');
		}
		$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
			if (settings.nTable !== $osTable[0]) return true;
			var api = new $.fn.dataTable.Api(settings);
			var node = api.row(dataIndex).node();
			if (!node) return true;
			var $tr = $(node);
			var rid = osResolveOsRowId($tr);
			var row = osIndexRowsById && (osIndexRowsById[rid] || osIndexRowsById[String(rid)]);
			return osRowPassesFilters(row);
		});
		var osDt = $osTable.DataTable({
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
					$host.removeClass('pgm-sidebar-dt-host--pending').append($f);
					$f.find('input[type="search"]').attr('placeholder', 'Buscar OS, cliente, técnico…');
				}
				$f.find('input[type="search"]').attr('placeholder', 'Buscar OS, cliente, técnico…');
			},
			drawCallback: function () {
				$('#tableOrdens tbody tr').each(function () {
					var $tr = $(this);
					if (!$tr.find('td').length) return;
					var order = $tr.find('td').first().attr('data-order');
					if (order != null && order !== '' && (!$tr.attr('data-os-id') || $tr.attr('data-os-id') === '')) {
						$tr.attr('data-os-id', order);
					}
				});
			}
		});
		osDt.search(osDtInitialFilter).draw();

		function osRefreshTableMeta() {
			var info = osDt.page.info();
			$('#os-showing').text(info.recordsDisplay);
			$('#os-total').text(info.recordsTotal);
			$('#os-per-page').val(String(info.length));
			$('#tableOrdens_paginate').prev('.dataTables_info').hide();
		}
		osRefreshTableMeta();
		$osTable.on('draw.dt search.dt page.dt length.dt', osRefreshTableMeta);
		$('#os-per-page').on('change', function() {
			var len = parseInt($(this).val(), 10) || <?= (int)$pagelength ?>;
			osDt.page.len(len).draw(false);
		});
		function osApplyClientFilters() {
			osSyncQueryFromFilters();
			osRefreshKpiActive();
			osDt.draw();
		}

		/* Primeira abertura sem query aplica filtro operacional: Aberta + Em execução. */
		if (osInitialFilters.situacao) {
			$('#situacao').val(osInitialFilters.situacao);
			osUseDefaultOperationalFilter = false;
		} else {
			osUseDefaultOperationalFilter = true;
			$('#situacao').val('');
		}
		if (osInitialFilters.cliente) $('#cliente').val(osInitialFilters.cliente);
		if (osInitialFilters.problema) $('#problema').val(osInitialFilters.problema);
		if (osInitialFilters.locacao) $('#locacao').val(osInitialFilters.locacao);
		if (typeof $.fn.selectpicker === 'function') {
			$('#cliente, #problema').selectpicker('refresh');
		}
		osApplyClientFilters();

		osBulkUi();
		if ($('#badge-exec-os').length) {
			$('#badge-exec-os').text('<?= (int)$kpiExec ?>');
		}

		function osRowOpenDrawer($tr) {
			var rid = osResolveOsRowId($tr);
			if (rid === '') return;
			var row = osIndexRowsById && (osIndexRowsById[rid] || osIndexRowsById[String(rid)]);
			if (!row || !row.id) return;
			osOpenDrawer(row, $tr);
		}

		/* Clique no texto (#text) não funciona com $(e.target).closest — normalizar para o elemento */
		function osEventTargetEl(e) {
			var t = e.target;
			if (t && t.nodeType === 3 && t.parentElement) {
				return t.parentElement;
			}
			return t;
		}

		/* Delegar no document: tbody/linhas podem ser recriados pelo DataTables (tr pode perder data-os-id) */
		$(document).on('click', '#tableOrdens tbody tr', function(e) {
			var $tr = $(this);
			if (!osResolveOsRowId($tr)) return;
			var el = osEventTargetEl(e);
			if (!el || typeof el.closest !== 'function') return;
			if (el.closest('.os-row-checkbox')) return;
			if (el.closest('label.custom-control-label')) return;
			if (el.closest('a[href]')) return;
			if (el.closest('button')) return;
			if (el.closest('input[type="checkbox"]')) return;
			osRowOpenDrawer($tr);
		});

		$(document).on('keydown', '#tableOrdens tbody tr .link[role="button"]', function(e) {
			if (e.key !== 'Enter' && e.key !== ' ') return;
			e.preventDefault();
			var $tr = $(this).closest('#tableOrdens tbody tr');
			if (osResolveOsRowId($tr)) osRowOpenDrawer($tr);
		});

		$('#os-btn-export-csv').on('click', function() {
			var dt = osDt;
			var headers = [];
			$('#tableOrdens thead th').each(function() {
				headers.push($(this).text().trim().replace(/\s+/g, ' '));
			});
			var lines = [headers.join(';')];
			dt.rows({ search: 'applied' }).every(function() {
				var $tr = $(this.node());
				var cols = [];
				var rid = osResolveOsRowId($tr);
				var rowMeta = rid !== '' && osIndexRowsById && (osIndexRowsById[rid] || osIndexRowsById[String(rid)]);
				$tr.find('td').each(function(i) {
					var cell = $(this);
					var txt;
					if (i === 0) {
						txt = rowMeta && rowMeta.id ? '#' + rowMeta.id : cell.text().trim();
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
