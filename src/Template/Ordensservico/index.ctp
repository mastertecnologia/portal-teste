<?php
use Cake\Routing\Router;

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
?>
<div class="col-md-12 p-0">
	<div class="os-index-shell">
		<header class="os-topbar">
			<h1 class="os-page-title">Ordens de Serviço</h1>
			<div class="os-topbar-search-slot" id="os-dt-search-slot" aria-label="Busca na tabela"></div>
			<div class="os-topbar-actions">
				<?php if ($role == 0) : ?>
					<?= $this->Html->link('Abrir ordem de serviço', ['action' => 'add'], ['class' => 'os-btn-primary', 'target' => '_blank']) ?>
				<?php endif; ?>
			</div>
		</header>

		<div class="os-kpi-grid">
			<div class="os-kpi-card os-kpi-blue">
				<div class="os-kpi-label">Total OSs</div>
				<div class="os-kpi-value"><?= (int)$kpiTotal ?></div>
				<div class="os-kpi-sub">Lista filtrada atual</div>
			</div>
			<div class="os-kpi-card os-kpi-amber">
				<div class="os-kpi-label">Em execução</div>
				<div class="os-kpi-value"><?= (int)$kpiExec ?></div>
				<div class="os-kpi-sub">Nesta visão</div>
			</div>
			<div class="os-kpi-card os-kpi-green">
				<div class="os-kpi-label">Sincronizadas</div>
				<div class="os-kpi-value"><?= (int)$kpiSync ?></div>
				<div class="os-kpi-sub">Integradas ao ERP</div>
			</div>
			<div class="os-kpi-card os-kpi-purple">
				<div class="os-kpi-label">Liberadas</div>
				<div class="os-kpi-value"><?= (int)$kpiLib ?></div>
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
			<?= $this->Form->create('Ordem', ['type' => 'get', 'class' => 'form-material os-filter-form w-100']); ?>
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
							<th>Número</th>
							<th>Abertura</th>
							<th>Previsão</th>
							<th>Cliente</th>
							<th>Contrato</th>
							<th>Técnico</th>
							<th class="text-right">Vl. Total</th>
							<th>Situação</th>
							<th><i class="fa fa-check-square"></i></th>
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
							?>
							<tr class="<?= h($rowClass) ?>">
								<td>
									<a class="link os-num-cell" target="_blank" href="<?= $this->Url->build(['action' => $action, $reg->id]) ?>"><?= h($reg->id) ?></a>
								</td>
								<td data-order="<?= date_format($reg->dataabertura, 'Ymd') ?>">
									<a class="link" target="_blank" href="<?= $this->Url->build(['action' => $action, $reg->id]) ?>"><?= h(date_format($reg->dataabertura, 'd/m/Y')) ?></a>
								</td>
								<td data-order="<?= date_format($reg->dataprevisao, 'Ymd') ?>">
									<a class="link" target="_blank" href="<?= $this->Url->build(['action' => $action, $reg->id]) ?>"><?= h(date_format($reg->dataprevisao, 'd/m/Y')) ?></a>
								</td>
								<td>
									<a class="link" target="_blank" href="<?= $this->Url->build(['action' => $action, $reg->id]) ?>" title="<?= h($cliNome) ?>">
										<span class="os-client-cell"><?= h($cliNome) ?></span>
									</a>
								</td>
								<td>
									<a class="link" target="_blank" href="<?= $this->Url->build(['action' => $action, $reg->id]) ?>">
										<span class="contract-badge <?= h($contratoClass) ?>"><?= h($contratoLbl) ?></span>
									</a>
								</td>
								<td>
									<a class="link" target="_blank" href="<?= $this->Url->build(['action' => $action, $reg->id]) ?>">
										<span class="os-tech">
											<span class="os-tech-av"><?= $osTechInitials($reg->user ? ($reg->user->name ?? '') : '') ?></span>
											<?= h($reg->user ? ($reg->user->name ?? '—') : '—') ?>
										</span>
									</a>
								</td>
								<td class="text-right">
									<a class="link os-valor-cell" target="_blank" href="<?= $this->Url->build(['action' => $action, $reg->id]) ?>"><?= h(number_format($reg->valortotal, 2, ',', '.')) ?></a>
								</td>
								<td>
									<a class="link" target="_blank" href="<?= $this->Url->build(['action' => $action, $reg->id]) ?>">
										<span class="os-status <?= h($pillClass) ?>">
											<span class="os-status-dot"></span>
											<?= $pillShort ?>
										</span>
									</a>
								</td>
								<td>
									<div class="custom-control custom-checkbox mr-sm-2 mb-0">
										<input data-id="<?= h($reg->id) ?>" type="checkbox" class="custom-control-input checkbox" id="checkbox<?= h($reg->id) ?>" value="check">
										<label class="custom-control-label" for="checkbox<?= h($reg->id) ?>"></label>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

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
	$(document).ready(function() {
		$('#situacao, #cliente, #problema, #locacao').on('change', function() {
			this.form.submit();
		});

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

		$('#os-bulk-clear').on('click', function() {
			window.ids = '';
			$('.checkbox').prop('checked', false);
			$('#ids').val('');
			$('#idsimprimir').val('');
			$('.btn-acao, .btn-imprimir').hide();
			osBulkUi();
		});

		var $window = $(window);
		var table = $('#tableOrdens');
		table.on('length.dt', function (e, settings, len) {
			pagelength(len);
		});
		var filters = typeof filters !== 'undefined' ? filters : '';
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
				$('#os-dt-search-slot').append($f);
				$f.find('input').attr('placeholder', 'Buscar por número, cliente, técnico…');
			}
		});
		table.search(filters).draw();
		osBulkUi();
	});

	window.onload = function() {
		$('.os-index-shell .dataTables_filter input[type="search"]').focus();
	};

	window.ids = '';

	$(document).on('click', '.checkbox', function() {
		var id = $(this).attr('data-id');
		if (!$(this).is(':checked')) {
			var array = window.ids.split(',');
			if (array.indexOf(id) > -1) array.splice(array.indexOf(id), 1);
			window.ids = '';
			array.forEach(function(value) {
				if (value !== '') window.ids += value + ',';
			});
		} else {
			window.ids += id + ',';
		}
		if (window.ids !== '') {
			$('.btn-acao, .btn-imprimir').show();
		} else {
			$('.btn-acao, .btn-imprimir').hide();
		}
		$('#ids').val(window.ids);
		$('#idsimprimir').val(window.ids);
		if ($('#os-bulk-bar').length) {
			if (window.ids !== '') {
				$('#os-bulk-bar').addClass('visible');
				var n = window.ids.split(',').filter(function (x) { return x !== ''; }).length;
				$('#os-bulk-count').text(n + ' ordem(ns) selecionada(s)');
			} else {
				$('#os-bulk-bar').removeClass('visible');
				$('#os-bulk-count').text('');
			}
		}
	});

	$(document).on('click', '.btn-acao', function(e) {
		e.preventDefault();
		$('#modal-acao').modal('toggle');
	});

	$(document).on('click', '.btn-imprimir', function(e) {
		e.preventDefault();
		$('#formimprimir').submit();
	});

	$('.dataval2, .dataval3, .dataval4, .dataval5').hide();
	$('#situacaomodal').change(function() {
		if ($(this).val() == <?= json_encode(C_OrdensSituacaoLiberadaParaFaturamento) ?>) {
			$('.liberada').show();
		} else {
			$('.liberada').hide();
		}
	});
	$('#nmrparcelas').change(function() {
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
</script>
