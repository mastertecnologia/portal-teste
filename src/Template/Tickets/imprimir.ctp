<?php
/**
 * @var \App\Model\Entity\Ticket $ticket
 * @var string $cliente
 * @var string|null $solicitante
 * @var array $ticketcomentarios
 * @var array $ticketanexos
 * @var array $ticketsmovs
 * @var string|null $severidadeLabel
 */
$autoPrint = (bool)$this->request->getQuery('autoprint');
$statusTxt = trim(strip_tags((string)SituacaoTicket($ticket->situacao)));
$autorNome = $ticket->users->name ?? $ticket['users']['name'] ?? '—';
$dataAbertura = '—';
if (!empty($ticket->created)) {
	$__tc = $ticket->created;
	if ($__tc instanceof \DateTimeInterface) {
		$dataAbertura = $__tc->format('d/m/Y H:i');
	} else {
		$dataAbertura = h((string)$__tc);
	}
}
$emitidoEm = date('d/m/Y H:i');
?>
<div class="ticket-print-document">
<style>
	/* ── Impressão PGM — relatório completo de chamado ───────────── */
	.ticket-print-actions {
		background: #f1f5f9;
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		padding: 12px 16px;
		z-index: 9999;
		border-bottom: 1px solid #e2e8f0;
	}
	.ticket-print-root {
		max-width: 100%;
		width: 100%;
		margin: 0 auto;
		padding: 12px 16px;
		font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
		font-size: 11pt;
		line-height: 1.45;
		color: #0f172a;
		background: #fff;
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
		box-sizing: border-box;
	}
	body.ticket-autoprint-screen .ticket-print-root {
		margin-top: 0;
		padding-top: 20px;
	}
	.ticket-print-banner {
		background: linear-gradient(135deg, #1d9e75 0%, #0f6e56 100%);
		color: #fff;
		padding: 18px 22px;
		border-radius: 8px 8px 0 0;
		border: 1px solid #0f6e56;
		border-bottom: none;
	}
	.ticket-print-banner h1 {
		margin: 0;
		font-size: 1.35rem;
		font-weight: 800;
		letter-spacing: -0.02em;
	}
	.ticket-print-banner p {
		margin: 6px 0 0;
		font-size: 0.82rem;
		opacity: 0.95;
	}
	.ticket-print-mark {
		display: inline-block;
		font-size: 0.7rem;
		font-weight: 700;
		letter-spacing: 0.12em;
		text-transform: uppercase;
		opacity: 0.9;
		margin-bottom: 4px;
	}
	.ticket-print-sheet {
		border: 1px solid #cbd5e1;
		border-top: none;
		border-radius: 0 0 8px 8px;
		overflow: hidden;
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
	}
	.ticket-print-section {
		padding: 16px 20px;
		border-bottom: 1px solid #e2e8f0;
	}
	.ticket-print-section:last-child {
		border-bottom: none;
	}
	.ticket-print-section h2 {
		margin: 0 0 10px;
		font-size: 0.72rem;
		font-weight: 800;
		letter-spacing: 0.08em;
		text-transform: uppercase;
		color: #1d9e75;
	}
	.ticket-print-meta {
		width: 100%;
		border-collapse: collapse;
		font-size: 10pt;
	}
	.ticket-print-meta th,
	.ticket-print-meta td {
		border: 1px solid #e2e8f0;
		padding: 8px 10px;
		vertical-align: top;
		text-align: left;
	}
	.ticket-print-meta th {
		width: 22%;
		background: #f8fafc;
		font-weight: 700;
		color: #475569;
		font-size: 9pt;
	}
	.ticket-print-meta td {
		background: #fff;
		color: #0f172a;
	}
	.ticket-print-status {
		display: inline-block;
		padding: 3px 10px;
		border-radius: 999px;
		font-size: 9pt;
		font-weight: 700;
		background: rgba(29, 158, 117, 0.12);
		color: #0f6e56;
		border: 1px solid rgba(29, 158, 117, 0.35);
	}
	.ticket-print-box {
		border: 1px solid #cbd5e1;
		border-radius: 6px;
		padding: 12px 14px;
		background: #fafafa;
		min-height: 2.5em;
	}
	.ticket-print-box.ticket-print-box--tech {
		background: rgba(29, 158, 117, 0.06);
		border-color: rgba(29, 158, 117, 0.25);
	}
	.descricao img {
		max-width: 100%;
		height: auto;
	}
	.ticket-print-table {
		width: 100%;
		border-collapse: collapse;
		font-size: 9.5pt;
	}
	.ticket-print-table th,
	.ticket-print-table td {
		border: 1px solid #e2e8f0;
		padding: 7px 9px;
		text-align: left;
		vertical-align: top;
	}
	.ticket-print-table thead th {
		background: #f1f5f9;
		font-weight: 700;
		color: #334155;
		font-size: 8.5pt;
		text-transform: uppercase;
		letter-spacing: 0.04em;
	}
	.ticket-print-table tbody tr:nth-child(even) td {
		background: #fafafa;
	}
	.ticket-print-foot {
		padding: 12px 20px 18px;
		font-size: 8.5pt;
		color: #64748b;
		border-top: 1px dashed #cbd5e1;
		text-align: center;
	}
	@page {
		size: A4;
		margin: 12mm;
	}
	@media print {
		html,
		body {
			width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
			background: #fff !important;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}
		.main-wrapper,
		.container-fluid {
			max-width: none !important;
			width: 100% !important;
			padding: 0 !important;
			margin: 0 !important;
		}
		.ticket-print-actions {
			display: none !important;
		}
		.ticket-print-root {
			max-width: 100% !important;
			width: 100% !important;
			padding: 0 !important;
			margin: 0 !important;
			box-shadow: none;
			font-size: 10.5pt;
		}
		.ticket-print-banner {
			border-radius: 0;
		}
		.ticket-print-sheet {
			box-shadow: none;
			border-radius: 0;
			border-left: none;
			border-right: none;
		}
		.ticket-print-meta th {
			width: 24%;
		}
	}
	body.ticket-autoprint-screen {
		background: #fff !important;
	}
	body.ticket-autoprint-screen .ticket-print-actions {
		display: none !important;
	}
	/* Só o relatório na área útil; o restante do layout some na impressão */
	.ticket-print-document {
		box-sizing: border-box;
	}
	@media print {
		/* Conteúdo do layout print: row com Flash + este template */
		.row.tirar-black-mode > *:not(.ticket-print-document),
		.row.tirar-black-mode > script {
			display: none !important;
			height: 0 !important;
			overflow: hidden !important;
			visibility: hidden !important;
		}
		.row.tirar-black-mode {
			display: block !important;
			margin: 0 !important;
			padding: 0 !important;
			width: 100% !important;
			max-width: none !important;
		}
		.ticket-print-document {
			display: block !important;
			width: 100% !important;
			max-width: none !important;
			margin: 0 !important;
			padding: 0 !important;
			background: #fff !important;
		}
		.main-wrapper {
			background: #fff !important;
			box-shadow: none !important;
		}
	}
	/*
	 * Enquanto o diálogo de impressão está aberto, o Chrome ainda mostra a página (media screen).
	 * Fixamos o relatório em tela cheia por cima para não aparecer “duplicado” / fundo atrás.
	 */
	@media screen {
		html.ticket-print-ui-active,
		html.ticket-print-ui-active body {
			background: #fff !important;
			overflow: hidden !important;
		}
		html.ticket-print-ui-active .ticket-print-actions {
			display: none !important;
		}
		html.ticket-print-ui-active .ticket-print-document {
			position: fixed !important;
			inset: 0 !important;
			width: 100% !important;
			max-width: none !important;
			height: 100% !important;
			margin: 0 !important;
			padding: 12px 16px !important;
			z-index: 2147483646 !important;
			overflow: auto !important;
			background: #fff !important;
			-webkit-overflow-scrolling: touch;
			box-sizing: border-box !important;
		}
		html.ticket-print-ui-active .ticket-print-root {
			margin-top: 0 !important;
			max-height: none !important;
		}
	}
	.ticket-print-root--screen {
		margin-top: 52px;
	}
	.ticket-print-visita-date {
		margin: 10px 0 0;
		font-size: 10pt;
	}
	.ticket-print-table th.ticket-print-th-w55 {
		width: 55%;
	}
	.ticket-print-table th.ticket-print-th-w130 {
		width: 130px;
	}
	.ticket-print-table th.ticket-print-th-w160 {
		width: 160px;
	}
	.ticket-print-table th.ticket-print-th-w90 {
		width: 90px;
	}
	.ticket-print-empty-note {
		margin: 0;
		color: #64748b;
		font-size: 10pt;
	}
	@media print {
		html.ticket-print-ui-active .ticket-print-document {
			position: static !important;
			inset: auto !important;
			width: 100% !important;
			height: auto !important;
			z-index: auto !important;
			overflow: visible !important;
			padding: 0 !important;
		}
	}

	/* Tema escuro: só na tela; @media print acima mantém papel claro */
	@media screen {
		html[data-pgm-theme="dark"] .ticket-print-actions {
			background: #1a1f28;
			border-bottom-color: #2d3544;
			color: #e8eaed;
		}
		html[data-pgm-theme="dark"] .ticket-print-root {
			background: #13161d;
			color: #e8eaed;
		}
		html[data-pgm-theme="dark"] .ticket-print-root a {
			color: #7dd3fc;
		}
		html[data-pgm-theme="dark"] .ticket-print-sheet {
			border-color: #3d4554;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
		}
		html[data-pgm-theme="dark"] .ticket-print-section {
			border-bottom-color: #2d3544;
		}
		html[data-pgm-theme="dark"] .ticket-print-section h2 {
			color: #5cdbc0;
		}
		html[data-pgm-theme="dark"] .ticket-print-meta th,
		html[data-pgm-theme="dark"] .ticket-print-meta td {
			border-color: #3d4554;
		}
		html[data-pgm-theme="dark"] .ticket-print-meta th {
			background: #1e2430;
			color: #a8b0bd;
		}
		html[data-pgm-theme="dark"] .ticket-print-meta td {
			background: #13161d;
			color: #e8eaed;
		}
		html[data-pgm-theme="dark"] .ticket-print-status {
			background: rgba(92, 219, 192, 0.15);
			color: #9ef5e0;
			border-color: rgba(92, 219, 192, 0.4);
		}
		html[data-pgm-theme="dark"] .ticket-print-box {
			background: #1a1f28;
			border-color: #3d4554;
			color: #e8eaed;
		}
		html[data-pgm-theme="dark"] .ticket-print-box.ticket-print-box--tech {
			background: rgba(29, 158, 117, 0.14);
			border-color: rgba(92, 219, 192, 0.35);
		}
		html[data-pgm-theme="dark"] .ticket-print-table th,
		html[data-pgm-theme="dark"] .ticket-print-table td {
			border-color: #3d4554;
		}
		html[data-pgm-theme="dark"] .ticket-print-table thead th {
			background: #1e2430;
			color: #c5cad3;
		}
		html[data-pgm-theme="dark"] .ticket-print-table tbody td {
			background: #13161d;
			color: #e8eaed;
		}
		html[data-pgm-theme="dark"] .ticket-print-table tbody tr:nth-child(even) td {
			background: #181c24;
		}
		html[data-pgm-theme="dark"] .ticket-print-foot {
			color: #8b93a0;
			border-top-color: #3d4554;
		}
		html[data-pgm-theme="dark"] .ticket-print-empty-note {
			color: #8b93a0;
		}
		html[data-pgm-theme="dark"] body.ticket-autoprint-screen {
			background: #0f1218 !important;
		}
		html[data-pgm-theme="dark"] html.ticket-print-ui-active,
		html[data-pgm-theme="dark"] html.ticket-print-ui-active body {
			background: #0f1218 !important;
		}
		html[data-pgm-theme="dark"] html.ticket-print-ui-active .ticket-print-document {
			background: #0f1218 !important;
		}
	}
</style>
<?php if (!$autoPrint) { ?>
	<div class="ticket-print-actions">
		<?= $this->Html->link('Imprimir / PDF', [], ['id' => 'btn-imprimir', 'class' => 'btn btn-pgm btn-pgm-imprimir btn-sm']) ?>
	</div>
<?php } ?>
<div class="ticket-print-root <?= $autoPrint ? '' : 'ticket-print-root--screen' ?>">
	<div class="ticket-print-banner">
		<span class="ticket-print-mark">PGM Soluções em TI</span>
		<h1>Relatório de chamado · Ticket nº <?= (int)$ticket->id ?></h1>
		<p>Documento gerado para arquivo e acompanhamento · Emissão: <?= h($emitidoEm) ?></p>
	</div>
	<div class="ticket-print-sheet">
		<div class="ticket-print-section">
			<h2>Dados gerais</h2>
			<table class="ticket-print-meta">
				<tbody>
					<tr>
						<th>Cliente</th>
						<td><?= h($cliente) ?></td>
					</tr>
					<tr>
						<th>Autor do chamado</th>
						<td><?= h($autorNome) ?></td>
					</tr>
					<?php if (!empty($solicitante)) : ?>
					<tr>
						<th>Solicitante</th>
						<td><?= h($solicitante) ?></td>
					</tr>
					<?php endif; ?>
					<?php if (!empty($ticket->email)) : ?>
					<tr>
						<th>E-mail</th>
						<td><?= h($ticket->email) ?></td>
					</tr>
					<?php endif; ?>
					<tr>
						<th>Assunto</th>
						<td><?= AssuntoTicket($ticket->assunto) ?></td>
					</tr>
					<tr>
						<th>Status</th>
						<td><span class="ticket-print-status"><?= h($statusTxt) ?></span></td>
					</tr>
					<?php if (!empty($severidadeLabel)) : ?>
					<tr>
						<th>Severidade</th>
						<td><?= h($severidadeLabel) ?></td>
					</tr>
					<?php endif; ?>
					<tr>
						<th>Data de abertura</th>
						<td><?= h($dataAbertura) ?></td>
					</tr>
					<?php if (!empty($ticket->datafinalizado)) : ?>
					<tr>
						<th>Finalizado</th>
						<td><?= h($ticket->datafinalizado) ?></td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="ticket-print-section">
			<h2>Descrição do chamado</h2>
			<div class="ticket-print-box descricao">
				<?= $ticket->solicitacao ?>
			</div>
			<?php if ($ticket->assunto == C_TicketCategoriaVisita && !empty($ticket->data)) :
				$__dv = $ticket->data;
				$__dvs = $__dv instanceof \DateTimeInterface ? $__dv->format('d/m/Y') : h((string)$__dv);
				?>
				<p class="ticket-print-visita-date"><strong>Data solicitada para visita:</strong> <?= $__dvs ?></p>
			<?php endif; ?>
		</div>

		<?php if (!empty($ticket->descricao_atendimento)) : ?>
		<div class="ticket-print-section">
			<h2>Atendimento técnico (o que foi feito)</h2>
			<div class="ticket-print-box ticket-print-box--tech">
				<?= nl2br(h($ticket->descricao_atendimento)) ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="ticket-print-section">
			<h2>Anexos (<?= count($ticketanexos ?? []) ?>)</h2>
			<?php if (!empty($ticketanexos)) : ?>
				<table class="ticket-print-table">
					<thead>
						<tr>
							<th class="ticket-print-th-w55">Arquivo</th>
							<th>Links (acesso no portal)</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($ticketanexos as $an) : ?>
							<tr>
								<td><?= h($an->arquivo ?? '') ?></td>
								<td>
									<?= $this->Html->link('Visualizar', ['action' => 'downloadAnexo', $an->id, '?' => ['inline' => '1']], ['target' => '_blank', 'rel' => 'noopener']) ?>
									&nbsp;·&nbsp;
									<?= $this->Html->link('Baixar', ['action' => 'downloadAnexo', $an->id], ['target' => '_blank', 'rel' => 'noopener']) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="ticket-print-empty-note">Nenhum anexo registrado neste chamado.</p>
			<?php endif; ?>
		</div>

		<div class="ticket-print-section">
			<h2>Histórico da conversa (<?= count($ticketcomentarios ?? []) ?>)</h2>
			<?php if (!empty($ticketcomentarios)) : ?>
				<table class="ticket-print-table">
					<thead>
						<tr>
							<th class="ticket-print-th-w130">Data / hora</th>
							<th class="ticket-print-th-w160">Participante</th>
							<th class="ticket-print-th-w90">Papel</th>
							<th>Mensagem</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($ticketcomentarios as $comentario) : ?>
							<?php
								$u = $comentario->users ?? $comentario->user ?? null;
								$nomeC = $u ? (string)$u->name : '—';
								$roleC = $u ? (int)$u->role : C_RoleCliente;
								$papelC = ($roleC === C_RoleCliente) ? 'Cliente' : 'Suporte';
								$dataC = !empty($comentario->created) ? $comentario->created->format('d/m/Y H:i') : '—';
								$txtC = trim((string)($comentario->comentario ?? ''));
							?>
							<tr>
								<td><?= h($dataC) ?></td>
								<td><?= h($nomeC) ?></td>
								<td><?= h($papelC) ?></td>
								<td><?= nl2br(h($txtC)) ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="ticket-print-empty-note">Nenhum comentário registrado.</p>
			<?php endif; ?>
		</div>

		<div class="ticket-print-section">
			<h2>Movimentações e trilha de auditoria (<?= count($ticketsmovs ?? []) ?>)</h2>
			<?php if (!empty($ticketsmovs)) : ?>
				<table class="ticket-print-table">
					<thead>
						<tr>
							<th class="ticket-print-th-w130">Data / hora</th>
							<th class="ticket-print-th-w160">Usuário</th>
							<th>Registro</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($ticketsmovs as $reg) : ?>
							<?php
								$dataM = $reg['datetime'] ?? null;
								$dataTexto = '';
								if ($dataM instanceof \DateTimeInterface) {
									try {
										$dataTexto = $dataM->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i');
									} catch (\Throwable $e) {
										$dataTexto = (string)$dataM;
									}
								} elseif ($dataM !== null && $dataM !== '') {
									$dataTexto = (string)$dataM;
								}
								$movEnt = $reg['users'] ?? $reg->users ?? $reg['user'] ?? $reg->user ?? null;
								$autorMov = $movEnt ? (string)$movEnt->name : '';
								$obs = trim((string)($reg['observacao'] ?? ''));
								$sitantiga = $reg['sitantiga'] ?? null;
								$sitnova = $reg['sitnova'] ?? null;
								$msg = '';
								if ($sitantiga == C_TicketSituacaoPendente && (int)$sitnova === 0) {
									$msg = 'Abriu o ticket.';
								} elseif ($sitnova == C_TicketSituacaoPendente && $sitantiga == C_TicketSituacaoFechado) {
									$msg = 'Reabriu o ticket.';
								} elseif ($sitnova == C_TicketSituacaoPendente && $sitnova != $sitantiga) {
									$msg = 'Alterou a situação do ticket para \'Pendente\'.';
								} elseif ($sitnova == C_TicketSituacaoRespondido && $sitnova != $sitantiga) {
									$msg = 'Publicou um comentário.';
									if ($obs !== '') {
										$msg .= ' ' . $obs;
									}
								} elseif ($sitnova == C_TicketSituacaoResolvido && $sitnova != $sitantiga && $obs === '') {
									$msg = 'Resolveu o ticket.';
								} elseif ($sitnova == C_TicketSituacaoResolvido && $sitnova != $sitantiga) {
									$msg = $obs;
								} elseif ($sitnova == C_TicketSituacaoEmandamento && $sitnova != $sitantiga) {
									$msg = 'Alterou a situação do ticket para \'Em andamento\'.';
								} elseif ($sitnova == C_TicketSituacaoFechado && $sitnova != $sitantiga) {
									$msg = 'Cancelou o ticket. Motivo: ' . $obs;
								} elseif (isset($sitnova) && (int)$sitnova === (int)C_TicketMovTransferencia) {
									$msg = $obs !== '' ? $obs : 'Transferência de atendimento registrada.';
								} elseif (isset($sitnova) && (int)$sitnova === (int)C_TicketMovMudancaFila) {
									$msg = $obs !== '' ? $obs : 'Mudança de fila registrada.';
								} elseif ((int)$sitnova === (int)C_TicketAnexoAdicionado) {
									$msg = 'O anexo \'' . $obs . '\' foi adicionado.';
								} elseif ((int)$sitnova === (int)C_TicketAnexoDeletado) {
									$msg = 'O anexo \'' . $obs . '\' foi deletado.';
								} elseif ($sitantiga !== null && $sitnova !== null) {
									$sitAntTxt = trim(strip_tags((string)SituacaoTicket($sitantiga)));
									$sitNovTxt = trim(strip_tags((string)SituacaoTicket($sitnova)));
									if ($sitAntTxt !== '' && $sitNovTxt !== '' && $sitAntTxt !== $sitNovTxt) {
										$msg = 'Situação: ' . $sitAntTxt . ' → ' . $sitNovTxt;
									} elseif ($obs !== '') {
										$msg = $obs;
									} else {
										$msg = 'Atualização registrada: ' . ($sitNovTxt !== '' ? $sitNovTxt : 'situação mantida');
									}
									if ($obs !== '' && $msg !== $obs && strpos($msg, $obs) === false) {
										$msg .= "\nObs.: " . $obs;
									}
								} else {
									$msg = $obs !== '' ? $obs : 'Movimentação registrada.';
								}
							?>
							<tr>
								<td><?= h($dataTexto) ?></td>
								<td><?= h($autorMov) ?></td>
								<td><?= nl2br(h($msg)) ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="ticket-print-empty-note">Nenhuma movimentação registrada.</p>
			<?php endif; ?>
		</div>

		<div class="ticket-print-foot">
			Ticket nº <?= (int)$ticket->id ?> · PGM Soluções em TI · Documento confidencial · Impresso em <?= h($emitidoEm) ?>
		</div>
	</div>
</div>
</div>
<script>
	(function () {
		var autoPrint = <?= $autoPrint ? 'true' : 'false' ?>;
		var root = document.documentElement;

		function setPrintUiActive(on) {
			try {
				if (on) {
					root.classList.add('ticket-print-ui-active');
				} else {
					root.classList.remove('ticket-print-ui-active');
				}
			} catch (e) {}
		}

		window.addEventListener('beforeprint', function () {
			setPrintUiActive(true);
		});
		window.addEventListener('afterprint', function () {
			setPrintUiActive(false);
		});

		/* Fallback (ex.: alguns Chromium / extensões) */
		if (window.matchMedia) {
			var mqPrint = window.matchMedia('print');
			function onPrintMq(e) {
				setPrintUiActive(!!(e && e.matches));
			}
			if (mqPrint.addEventListener) {
				mqPrint.addEventListener('change', onPrintMq);
			} else if (mqPrint.addListener) {
				mqPrint.addListener(onPrintMq);
			}
		}

		function doPrint() {
			setPrintUiActive(true);
			window.print();
		}

		var btn = document.getElementById('btn-imprimir');
		if (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				doPrint();
			});
		}
		if (autoPrint) {
			try {
				document.body.classList.add('ticket-autoprint-screen');
			} catch (e) {}
			setPrintUiActive(true);
			setTimeout(function () {
				doPrint();
			}, 80);
			window.addEventListener(
				'afterprint',
				function () {
					try {
						window.close();
					} catch (e) {}
				},
				{ once: true }
			);
		}
	})();
</script>
