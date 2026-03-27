<?php
/**
 * Hub de relatórios — OrdensservicoController::relatorios.
 */
$this->Html->css('/dist/css/pages/ordensservico-index-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Relatórios', [], ['class' => 'breadcrumb-item active']);
$this->assign('title', 'Relatórios — Ordens de Serviço');

$qRel = [];
foreach (['cliente', 'situacao', 'problema', 'locacao', 'solicitante', 'data_ini', 'data_fim', 'mes'] as $k) {
	$v = $$k;
	if ($v === null || $v === '') {
		continue;
	}
	if ($k === 'locacao' && ((string)$v === '-1' || (int)$v === -1)) {
		continue;
	}
	$qRel[$k] = $v;
}
$idsMarcados = [];
foreach ((array)($idsSelecionados ?? []) as $_idSel) {
	$_idSel = (int)$_idSel;
	if ($_idSel > 0) {
		$idsMarcados[$_idSel] = true;
	}
}
$optsModelo = [];
foreach ($modelosRelatorio as $_m) {
	if (!empty($_m['id'])) {
		$optsModelo[$_m['id']] = $_m['titulo'] ?? $_m['id'];
	}
}
?>
<style>
@media print {
	.no-print, .left-sidebar, .pgm-sidebar-footer, .pgm-sidebar-brand { display: none !important; }
}
</style>

<div class="col-md-12 p-0">
<div class="os-index-shell">
	<header class="os-page-head no-print">
		<div class="os-rel-headline">
			<h1 class="os-page-title">Relatórios — Ordens de Serviço</h1>
		</div>
		<div class="os-page-head-actions">
			<?= $this->Html->link(
				'<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 4L6 8l4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Voltar à lista</span>',
				['action' => 'index'],
				['class' => 'os-page-head-link', 'escape' => false]
			) ?>
		</div>
	</header>

	<div class="os-rel-inner">
		<div class="os-rel-map no-print">
			<strong>Mapeamento do módulo:</strong>
			Lista principal (index), cadastro (edit/view), nova OS (add), impressão de uma OS (imprimir), impressão em lote (imprimirordens).
			Esta página concentra relatórios com filtros alinhados ao index.
		</div>

		<div class="os-rel-panel os-rel-filtros no-print">
			<h2>Filtros do relatório</h2>
			<?= $this->Form->create(null, ['type' => 'get', 'class' => 'form-material', 'url' => ['action' => 'relatorios']]); ?>
			<div class="row">
				<div class="col-lg-3 col-md-6 col-12">
					<p>Situação</p>
					<?= $this->Form->control('situacao', ['data-live-search' => true, 'title' => 'Todas', 'value' => $situacao, 'id' => 'rel-situacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensSituacao, 'label' => false, 'empty' => false]) ?>
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<p>Problema</p>
					<?= $this->Form->control('problema', ['data-live-search' => true, 'title' => 'Todos', 'value' => $problema, 'id' => 'rel-problema', 'class' => 'form-control selectpicker', 'options' => $problemas, 'label' => false, 'empty' => false]) ?>
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<p>Cliente</p>
					<?= $this->Form->control('cliente', ['data-live-search' => true, 'title' => 'Todos', 'value' => $cliente, 'class' => 'form-control selectpicker', 'id' => 'rel-cliente', 'options' => $clientes, 'label' => false, 'empty' => false]) ?>
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<p>Tipo</p>
					<?= $this->Form->control('locacao', ['data-live-search' => true, 'title' => 'Todos', 'value' => $locacao, 'id' => 'rel-locacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensLocacao, 'label' => false, 'empty' => false]) ?>
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<p>Técnico responsável</p>
					<?= $this->Form->control('solicitante', ['data-live-search' => true, 'title' => 'Todos', 'value' => $solicitante, 'id' => 'rel-solicitante', 'class' => 'form-control selectpicker', 'options' => $usuarios, 'label' => false, 'empty' => false]) ?>
				</div>
				<div class="col-lg-2 col-md-6 col-12">
					<p>De</p>
					<input type="date" name="data_ini" id="rel-data-ini" class="form-control" value="<?= h($data_ini ?? '') ?>" />
				</div>
				<div class="col-lg-2 col-md-6 col-12">
					<p>Até</p>
					<input type="date" name="data_fim" id="rel-data-fim" class="form-control" value="<?= h($data_fim ?? '') ?>" />
				</div>
				<div class="col-lg-2 col-md-6 col-12">
					<p>Mês</p>
					<input type="month" name="mes" id="rel-mes" class="form-control" value="<?= h($mes ?? '') ?>" />
				</div>
			</div>
			<div class="m-t-15">
				<?= $this->Form->button('Aplicar filtros', ['class' => 'btn btn-primary']) ?>
			</div>
			<?= $this->Form->end(); ?>
		</div>

		<?php if (empty($modelosRelatorio)) : ?>
		<div class="os-rel-panel no-print">
			<p class="os-rel-help m-b-0">Nenhum modelo de relatório configurado. Verifique o arquivo <code>config/ordens_servico_relatorios.php</code>.</p>
		</div>
		<?php else : ?>
		<div class="os-rel-grid no-print">
			<?php foreach ($modelosRelatorio as $m) :
				$urlVer = ['action' => 'relatorioVer', $m['id'], '?' => $qRel];
				$urlPdf = ['action' => 'relatorioPdf', $m['id'], '?' => $qRel];
				?>
			<div class="os-rel-card">
				<h3><?= h($m['titulo']) ?></h3>
				<p><?= h($m['descricao']) ?></p>
				<div class="os-rel-actions">
					<?= $this->Html->link('Visualizar', $urlVer, ['class' => 'btn btn-primary', 'target' => '_blank', 'rel' => 'noopener']) ?>
					<?= $this->Html->link('PDF', $urlPdf, ['class' => 'btn btn-success']) ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<div class="os-rel-panel no-print">
			<h2>Selecionar ordens para imprimir/PDF/e-mail</h2>
			<div class="table-responsive">
				<table class="table table-sm table-hover" style="margin-bottom:0;">
					<thead>
						<tr>
							<th style="width:40px;">
								<input type="checkbox" id="rel-check-all" />
							</th>
							<th style="width:80px;">Nº</th>
							<th style="width:110px;">Abertura</th>
							<th>Cliente</th>
							<th>Técnico</th>
							<th>Situação</th>
							<th class="text-right" style="width:110px;">Valor</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($ordensSelecionaveis)) : ?>
							<tr><td colspan="7"><em>Nenhuma ordem encontrada com os filtros.</em></td></tr>
						<?php else : ?>
							<?php foreach ($ordensSelecionaveis as $_o) :
								$_oid = (int)$_o->id;
								$_cli = !empty($_o->cliente) ? ($_o->cliente->tipo == C_ClientesTipoFisica ? $_o->cliente->nome : $_o->cliente->razaosocial) : '—';
								$_tec = !empty($_o->user) ? (string)($_o->user->name ?? '—') : '—';
								$_sit = trim(strip_tags((string)SituacaoOrdem($_o->situacao)));
								$_ab = $_o->dataabertura ? date_format($_o->dataabertura, 'd/m/Y') : '';
								?>
							<tr>
								<td><input type="checkbox" class="rel-check-os" value="<?= $_oid ?>"<?= isset($idsMarcados[$_oid]) ? ' checked' : '' ?>></td>
								<td>#<?= $_oid ?></td>
								<td><?= h($_ab) ?></td>
								<td><?= h($_cli) ?></td>
								<td><?= h($_tec) ?></td>
								<td><?= h($_sit) ?></td>
								<td class="text-right"><?= h(number_format((float)$_o->valortotal, 2, ',', '.')) ?></td>
							</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="m-t-10 d-flex" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
				<button type="button" class="btn btn-success" id="rel-btn-pdf-selecionadas">PDF selecionadas</button>
				<span class="os-rel-help m-b-0" id="rel-count-selected">0 selecionada(s)</span>
			</div>
		</div>

		<div class="os-rel-panel os-rel-email no-print">
			<h2>Enviar por e-mail</h2>
			<?= $this->Form->create(null, ['url' => ['action' => 'relatorioEnviarEmail']]); ?>
			<div class="row">
				<div class="col-md-4">
					<?php if ($optsModelo !== []) : ?>
						<?= $this->Form->control('modelo', [
							'type' => 'select',
							'options' => $optsModelo,
							'label' => 'Modelo',
							'class' => 'form-control',
							'required' => true,
						]) ?>
					<?php else : ?>
						<p class="os-rel-warn">Nenhum modelo disponível para envio.</p>
					<?php endif; ?>
				</div>
				<div class="col-md-5">
					<?= $this->Form->control('email_destino', [
						'type' => 'email',
						'label' => 'E-mail destino',
						'class' => 'form-control',
						'required' => true,
						'placeholder' => 'destinatario@empresa.com',
					]) ?>
				</div>
				<div class="col-md-12 m-t-10">
					<?= $this->Form->control('mensagem', [
						'type' => 'textarea',
						'label' => 'Mensagem (opcional)',
						'class' => 'form-control',
						'rows' => 2,
					]) ?>
				</div>
			</div>
			<?= $this->Form->control('cliente', ['type' => 'hidden', 'value' => $cliente ?? '']); ?>
			<?= $this->Form->control('situacao', ['type' => 'hidden', 'value' => $situacao ?? '']); ?>
			<?= $this->Form->control('problema', ['type' => 'hidden', 'value' => $problema ?? '']); ?>
			<?= $this->Form->control('locacao', ['type' => 'hidden', 'value' => $locacao !== null && $locacao !== '' ? $locacao : '-1']); ?>
			<?= $this->Form->control('solicitante', ['type' => 'hidden', 'value' => $solicitante ?? '']); ?>
			<?= $this->Form->control('data_ini', ['type' => 'hidden', 'value' => $data_ini ?? '']); ?>
			<?= $this->Form->control('data_fim', ['type' => 'hidden', 'value' => $data_fim ?? '']); ?>
			<?= $this->Form->control('mes', ['type' => 'hidden', 'value' => $mes ?? '']); ?>
			<?= $this->Form->control('ids', ['type' => 'hidden', 'value' => '', 'id' => 'rel-ids-hidden']); ?>
			<div class="m-t-15">
				<?= $this->Form->button('Enviar relatório por e-mail', ['class' => 'btn btn-primary', 'disabled' => $optsModelo === []]) ?>
			</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
</div>

<script>
$(function () {
	var $mes = $('#rel-mes');
	var $ini = $('#rel-data-ini');
	var $fim = $('#rel-data-fim');
	var manualRangeEdit = false;
	function pad2(n) { return (n < 10 ? '0' : '') + n; }
	function currentYm() {
		var d = new Date();
		return d.getFullYear() + '-' + pad2(d.getMonth() + 1);
	}
	function monthBounds(ym) {
		if (!/^\d{4}-\d{2}$/.test(ym)) return null;
		var y = parseInt(ym.slice(0, 4), 10);
		var m = parseInt(ym.slice(5, 7), 10);
		if (!y || !m || m < 1 || m > 12) return null;
		var last = new Date(y, m, 0).getDate();
		return {
			ini: y + '-' + pad2(m) + '-01',
			fim: y + '-' + pad2(m) + '-' + pad2(last)
		};
	}
	function syncRangeFromMonth(force) {
		var ym = ($mes.val() || '').trim();
		var b = monthBounds(ym);
		if (!b) return;
		if (force || !manualRangeEdit || !$ini.val() || !$fim.val()) {
			$ini.val(b.ini);
			$fim.val(b.fim);
		}
	}
	if (!$mes.val()) {
		$mes.val(currentYm());
	}
	if (!$ini.val() && !$fim.val()) {
		syncRangeFromMonth(true);
	}
	$mes.on('change', function () {
		manualRangeEdit = false;
		syncRangeFromMonth(true);
	});
	$ini.add($fim).on('change', function () {
		manualRangeEdit = true;
	});
	if ($.fn.selectpicker) {
		$('.os-rel-filtros select.selectpicker').selectpicker({ liveSearch: true, style: '', size: 8, container: 'body' });
	}
	function relGetSelectedIds() {
		var ids = [];
		$('.rel-check-os:checked').each(function () {
			var n = parseInt($(this).val(), 10);
			if (n > 0) ids.push(n);
		});
		return ids;
	}
	function relSyncSelectedUi() {
		var ids = relGetSelectedIds();
		$('#rel-ids-hidden').val(ids.join(','));
		$('#rel-count-selected').text(ids.length + ' selecionada(s)');
		var total = $('.rel-check-os').length;
		$('#rel-check-all').prop('checked', total > 0 && ids.length === total);
	}
	$('#rel-check-all').on('change', function () {
		var on = $(this).is(':checked');
		$('.rel-check-os').prop('checked', on);
		relSyncSelectedUi();
	});
	$(document).on('change', '.rel-check-os', relSyncSelectedUi);
	$('#rel-btn-pdf-selecionadas').on('click', function () {
		var ids = relGetSelectedIds();
		if (!ids.length) {
			alert('Selecione ao menos uma OS.');
			return;
		}
		var base = <?= json_encode($this->Url->build(['action' => 'relatorioPdf', 'lista_filtrada'])) ?>;
		var query = <?= json_encode(http_build_query($qRel)) ?>;
		var sep = query ? '&' : '';
		var url = base + (query ? ('?' + query) : '?') + sep + 'ids=' + encodeURIComponent(ids.join(','));
		window.open(url, '_blank', 'noopener');
	});
	$('form[action$="relatorioEnviarEmail"]').on('submit', function () {
		relSyncSelectedUi();
	});
	relSyncSelectedUi();
});
</script>
