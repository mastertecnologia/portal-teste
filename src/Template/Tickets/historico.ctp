<?php
/**
 * @var array<string, string> $historicoKpis
 * @var \App\Model\Entity\Ticket[] $ticketsHistorico
 * @var array<int, string> $historicoClientesList
 * @var bool $historicoPeriodoPadrao
 * @var array $historicoQuery
 * @var int $historicoTotalMatched
 * @var int $historicoLimiteLista
 */
$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Histórico de atendimentos', [], ['class' => 'breadcrumb-item active']);

$k = $historicoKpis ?? [
	'total_periodo' => '—',
	'em_execucao' => '—',
	'finalizados' => '—',
	'sla_atendido_pct' => '—',
];
$hq = $historicoQuery ?? [];
$rows = $ticketsHistorico ?? [];
$clientesList = $historicoClientesList ?? [];
$totalMatched = (int)($historicoTotalMatched ?? 0);
$limiteLista = (int)($historicoLimiteLista ?? 500);
$periodoPadrao = (bool)($historicoPeriodoPadrao ?? false);

$sitLabels = [
	(int)C_TicketSituacaoPendente => 'Aguardando técnico',
	(int)C_TicketSituacaoEmandamento => 'Em execução',
	(int)C_TicketSituacaoResolvido => 'Resolvido',
	(int)C_TicketSituacaoFechado => 'Fechado',
];
?>
<?= $this->Html->css('dist/css/dashboard-erp.css') ?>

<div class="col-12 p-0">
	<div class="dash-erp">
		<div class="dash-erp-header">
			<div>
				<h2 class="dash-erp-title">Histórico de atendimentos</h2>
				<p class="dash-erp-subtitle">
					Chamados no seu escopo (ABAC), filtrados por abertura (<code>created</code>).
					<?php if ($periodoPadrao): ?>
						<strong>Sem datas informadas:</strong> considera os últimos 60 dias.
					<?php endif; ?>
				</p>
			</div>
			<div>
				<a class="btn btn-outline-secondary m-r-10" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'dashboard']) ?>">Voltar ao dashboard</a>
				<button type="button" class="btn btn-outline-info" onclick="window.location.reload()">Atualizar</button>
			</div>
		</div>

		<div class="dash-erp-kpis" aria-label="Resumo do período">
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-layer-group"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Total no período</p>
					<p class="dash-erp-kpi-value"><?= h($k['total_periodo']) ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-play-circle"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Em execução</p>
					<p class="dash-erp-kpi-value"><?= h($k['em_execucao']) ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-check-circle"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Finalizados</p>
					<p class="dash-erp-kpi-value"><?= h($k['finalizados']) ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-percentage"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">SLA atendido</p>
					<p class="dash-erp-kpi-value"><?= h($k['sla_atendido_pct']) ?></p>
				</div>
			</div>
		</div>

		<div class="dash-erp-card m-b-10">
			<div class="dash-erp-card-header">
				<h5 class="dash-erp-card-title">Filtros</h5>
			</div>
			<div class="dash-erp-card-body">
				<?= $this->Form->create(null, [
					'class' => 'form-material',
					'type' => 'get',
					'url' => ['controller' => 'Tickets', 'action' => 'historico'],
				]) ?>
				<?php
				$wfKeys = ['fila_suporte', 'nivel_atendimento', 'sem_responsavel', 'idtecnico_responsavel', 'transferidos', 'queue_id'];
				foreach ($wfKeys as $wk) {
					if (isset($hq[$wk]) && $hq[$wk] !== '') {
						echo $this->Form->hidden($wk, ['value' => $hq[$wk], 'id' => false]);
					}
				}
				?>
				<div class="row">
					<div class="col-md-3 col-sm-6 m-b-10">
						<label class="small text-muted m-b-5 d-block">Período — início</label>
						<input type="text" class="form-control" name="periodo_ini" placeholder="dd/mm/aaaa" autocomplete="off" value="<?= h($hq['periodo_ini'] ?? '') ?>">
					</div>
					<div class="col-md-3 col-sm-6 m-b-10">
						<label class="small text-muted m-b-5 d-block">Período — fim</label>
						<input type="text" class="form-control" name="periodo_fim" placeholder="dd/mm/aaaa" autocomplete="off" value="<?= h($hq['periodo_fim'] ?? '') ?>">
					</div>
					<div class="col-md-3 col-sm-6 m-b-10">
						<label class="small text-muted m-b-5 d-block">Situação</label>
						<select class="form-control" name="situacao">
							<option value="">Todas</option>
							<option value="<?= (int)C_TicketSituacaoPendente ?>" <?= ((string)($hq['situacao'] ?? '') === (string)(int)C_TicketSituacaoPendente) ? 'selected' : '' ?>><?= h($sitLabels[(int)C_TicketSituacaoPendente]) ?></option>
							<option value="<?= (int)C_TicketSituacaoEmandamento ?>" <?= ((string)($hq['situacao'] ?? '') === (string)(int)C_TicketSituacaoEmandamento) ? 'selected' : '' ?>><?= h($sitLabels[(int)C_TicketSituacaoEmandamento]) ?></option>
							<option value="<?= (int)C_TicketSituacaoResolvido ?>" <?= ((string)($hq['situacao'] ?? '') === (string)(int)C_TicketSituacaoResolvido) ? 'selected' : '' ?>><?= h($sitLabels[(int)C_TicketSituacaoResolvido]) ?></option>
							<option value="<?= (int)C_TicketSituacaoFechado ?>" <?= ((string)($hq['situacao'] ?? '') === (string)(int)C_TicketSituacaoFechado) ? 'selected' : '' ?>><?= h($sitLabels[(int)C_TicketSituacaoFechado]) ?></option>
						</select>
					</div>
					<div class="col-md-3 col-sm-6 m-b-10">
						<label class="small text-muted m-b-5 d-block">Cliente</label>
						<select class="form-control" name="idcliente">
							<option value="">Todos</option>
							<?php foreach ($clientesList as $cid => $cnome): ?>
								<option value="<?= (int)$cid ?>" <?= ((string)($hq['idcliente'] ?? '') === (string)(int)$cid) ? 'selected' : '' ?>><?= h($cnome) ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-8 col-sm-12 m-b-10">
						<label class="small text-muted m-b-5 d-block">Busca livre</label>
						<input type="text" class="form-control" name="q" placeholder="ID, assunto, descrição, autor, cliente…" autocomplete="off" value="<?= h($hq['q'] ?? '') ?>">
					</div>
					<div class="col-md-4 col-sm-12 m-b-10" style="padding-top: 22px;">
						<button type="submit" class="btn btn-pgm btn-pgm-salvar btn-success m-r-5">Aplicar filtros</button>
						<a class="btn btn-outline-secondary" href="<?= $this->Url->build(['controller' => 'Tickets', 'action' => 'historico']) ?>">Limpar</a>
					</div>
				</div>
				<?= $this->Form->end() ?>
			</div>
		</div>

		<div class="dash-erp-card">
			<div class="dash-erp-card-header">
				<h5 class="dash-erp-card-title">Chamados</h5>
				<span class="dash-erp-card-badge"><?= (int)$totalMatched ?></span>
			</div>
			<div class="dash-erp-card-body">
				<div class="row m-b-10">
					<div class="col-md-6 col-sm-12">
						<input type="text" class="form-control" id="filtro-historico-local" placeholder="Refinar na lista (cliente, assunto, situação…)" />
					</div>
					<div class="col-md-6 col-sm-12 text-right text-muted" style="padding-top:8px;">
						<?php if ($totalMatched > $limiteLista): ?>
							Exibindo os <?= (int)$limiteLista ?> mais recentes entre <strong><?= (int)$totalMatched ?></strong> correspondentes.
						<?php else: ?>
							Exibindo <span id="historico-count"><?= count($rows) ?></span> registros
						<?php endif; ?>
					</div>
				</div>

				<div class="dash-erp-scroll" id="historico-scroll" style="max-height: 70dvh;">
					<div class="table-responsive">
						<table class="dash-erp-table" id="table-historico-atendimentos">
							<thead>
								<tr>
									<th>#</th>
									<th>Abertura</th>
									<th>Cliente</th>
									<th>Assunto</th>
									<th>Situação</th>
									<th style="width: 110px;">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($rows)): ?>
									<tr>
										<td colspan="6" class="text-center text-muted" style="padding: 28px 12px;">
											Nenhum registro para os filtros atuais.
										</td>
									</tr>
								<?php else: ?>
									<?php foreach ($rows as $reg): ?>
										<?php
										$urlTicketEdit = $this->Url->build(['controller' => 'Tickets', 'action' => 'edit', $reg->id]);
										$urlTicketView = $this->Url->build(['controller' => 'Tickets', 'action' => 'viewModal', $reg->id]);
										$urlTicketPrint = $this->Url->build(['controller' => 'Tickets', 'action' => 'imprimir', $reg->id, '?' => ['autoprint' => 1]]);
										$urlTicketEmail = $this->Url->build(['controller' => 'Tickets', 'action' => 'email', $reg->id, 'redirect']);

										$clienteNome = '—';
										if (!empty($reg->cliente)) {
											$clienteNome = ((int)$reg->cliente->tipo === (int)C_ClientesTipoFisica)
												? (string)$reg->cliente->nome
												: (string)$reg->cliente->razaosocial;
										}
										$autorNome = '';
										$uAutor = $reg->users ?? $reg->user ?? null;
										if (is_array($uAutor)) {
											$autorNome = trim((string)($uAutor['name'] ?? ''));
											if ($autorNome === '') {
												$autorNome = trim((string)($uAutor['username'] ?? ''));
											}
										} elseif (is_object($uAutor)) {
											$autorNome = trim((string)($uAutor->name ?? ''));
											if ($autorNome === '') {
												$autorNome = trim((string)($uAutor->username ?? ''));
											}
										}
										$assuntoTxt = strip_tags(AssuntoTicket((string)$reg->assunto));
										if (function_exists('mb_substr') && mb_strlen($assuntoTxt) > 120) {
											$assuntoTxt = mb_substr($assuntoTxt, 0, 117) . '…';
										} elseif (strlen($assuntoTxt) > 120) {
											$assuntoTxt = substr($assuntoTxt, 0, 117) . '…';
										}
										$sitNome = $sitLabels[(int)$reg->situacao] ?? ('#' . (int)$reg->situacao);
										$abertura = '';
										if (!empty($reg->created) && is_object($reg->created) && method_exists($reg->created, 'format')) {
											$abertura = $reg->created->format('d/m/Y H:i');
										} elseif (!empty($reg->created)) {
											$abertura = h((string)$reg->created);
										}
										$searchBlob = implode(' ', [
											(string)(int)$reg->id,
											(string)$clienteNome,
											(string)$autorNome,
											(string)$assuntoTxt,
											(string)$sitNome,
										]);
										?>
										<tr data-search="<?= h($searchBlob) ?>">
											<td><a class="dash-erp-link" target="_blank" href="<?= $urlTicketEdit ?>"><?= (int)$reg->id ?></a></td>
											<td><?= h($abertura) ?></td>
											<td><?= h($clienteNome) ?></td>
											<td><?= h($assuntoTxt) ?></td>
											<td><?= h($sitNome) ?></td>
											<td class="dash-erp-actions">
												<button
													type="button"
													class="btn btn-outline-info btn-sm btn-ver-ticket-hist"
													data-toggle="modal"
													data-target="#modal-ticket-historico"
													data-id="<?= (int)$reg->id ?>"
													data-url-view="<?= h($urlTicketView) ?>"
													data-url-edit="<?= h($urlTicketEdit) ?>"
													data-url-print="<?= h($urlTicketPrint) ?>"
													data-url-email="<?= h($urlTicketEmail) ?>"
												>
													Ver
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-ticket-historico" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<div>
					<h5 class="modal-title m-0">Ticket <span id="modal-ticket-hist-id">-</span></h5>
					<small class="text-muted">Visualização rápida. Use os botões para abrir edição completa, imprimir/PDF ou e-mail.</small>
				</div>
				<div class="ml-auto d-flex align-items-center" style="gap:8px;">
					<a class="btn btn-outline-secondary btn-sm" id="modal-ticket-hist-abrir" target="_blank" href="#">Abrir completo</a>
					<a class="btn btn-outline-info btn-sm" id="modal-ticket-hist-imprimir" target="_blank" href="#">Imprimir / PDF</a>
					<a class="btn btn-outline-success btn-sm" id="modal-ticket-hist-email" target="_blank" href="#">Enviar e-mail</a>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
			</div>
			<div class="modal-body p-0" style="height: 72vh;">
				<iframe
					id="modal-ticket-hist-iframe"
					title="Ticket"
					src="about:blank"
					style="width:100%; height:100%; border:0;"
				></iframe>
			</div>
		</div>
	</div>
</div>

<script>
	$(function () {
		if ($.fn.perfectScrollbar) {
			$('#historico-scroll').perfectScrollbar();
		}
	});

	(function () {
		var $input = $('#filtro-historico-local');
		var $rows = $('#table-historico-atendimentos tbody tr');
		function norm(s) {
			try { return (s || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch (e) { return (s || '').toString(); }
		}
		function apply() {
			var q = norm(($input.val() || '')).toLowerCase().trim();
			var shown = 0;
			$rows.each(function () {
				var $tr = $(this);
				if ($tr.find('td[colspan]').length) {
					return;
				}
				var t = norm($tr.attr('data-search') || '').toLowerCase();
				var ok = q === '' || t.indexOf(q) !== -1;
				$tr.toggle(ok);
				if (ok) shown++;
			});
			var $c = $('#historico-count');
			if ($c.length) $c.text(shown);
		}
		$input.on('input', apply);
	})();

	(function () {
		function setHref(id, href) {
			var el = document.getElementById(id);
			if (el) el.setAttribute('href', href || '#');
		}
		$('.btn-ver-ticket-hist').on('click', function () {
			var $b = $(this);
			$('#modal-ticket-hist-id').text($b.data('id') || '-');
			setHref('modal-ticket-hist-abrir', $b.data('url-edit'));
			setHref('modal-ticket-hist-imprimir', $b.data('url-print'));
			setHref('modal-ticket-hist-email', $b.data('url-email'));
			var iframe = document.getElementById('modal-ticket-hist-iframe');
			if (iframe) iframe.src = $b.data('url-view') || 'about:blank';
		});
		$('#modal-ticket-historico').on('hidden.bs.modal', function () {
			var iframe = document.getElementById('modal-ticket-hist-iframe');
			if (iframe) iframe.src = 'about:blank';
		});
	})();
</script>
