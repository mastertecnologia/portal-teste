<?php
/**
 * Fragmento HTMX: apenas o conteúdo interno do painel esquerdo do ticket (timer, horas de contrato, ações).
 * Usado quando as ações do timer são chamadas via HTMX para atualizar só o painel.
 */
echo $this->Flash->render();
?>
<br>
<h5 class="text-muted">Autor</h5>
<div class="message-box bg-white">
	<div class="message-widget message-scroll">
		<a href="javascript:void(0)">
			<div class="mail-contnet">
				<h5><?= $ticket['user']['name'] ?? '' ?></h5>
			</div>
		</a>
	</div>
</div>
<h5 class="text-muted">Membros do Ticket</h5>
<?php if (!empty($ticketsusers)) foreach ($ticketsusers as $user): ?>
	<div class="message-box bg-white">
		<div class="message-widget message-scroll">
			<a href="#" class='add-darkmode'>
				<div class="mail-contnet mail-contnet--full">
					<div class="row">
						<div class="col-12">
							<h5><?= h($user['Users']['name'] ?? '') ?></h5>
						</div>
					</div>
				</div>
			</a>
		</div>
	</div>
<?php endforeach; ?>
<br>
<h5><span class="text-muted"> Setor: </span><?= !empty($ticket['user']['setor']) ? h($ticket['user']['setor']) : "Indefinido"; ?></h5>
<h5><span class="text-muted"> Celular: </span><?= !empty($ticket['user']['celular']) ? h($ticket['user']['celular']) : "Indefinido"; ?></h5>
<h5><span class="text-muted"> Telefone: </span><?= !empty($ticket['user']['telefone']) ? h($ticket['user']['telefone']) : "Indefinido"; ?></h5>
<br>
<h5><span class="text-muted"> Cliente: </span> <?= h($cliente ?? '') ?></h5>
<h5><span class='text-muted'> Solicitante: </span> <?php if (!empty($solicitante)) echo h($solicitante); else echo empty($ticket->nomesolicitante) ? "Indefinido" : h($ticket->nomesolicitante); ?></h5>
<h5><span class="text-muted"> E-mail: </span><?= !empty($ticket->email) ? h($ticket->email) : "Indefinido"; ?></h5>
<br>
<h5><span class="text-muted"> Assunto: </span><?= AssuntoTicket($ticket->assunto) ?></h5>
<h5><span class="text-muted"> Status: </span><?= SituacaoTicket($ticket->situacao) ?></h5>
<br>
<?= $this->element('ticket_tecnico_responsavel', ['tecnicoResponsavelLabel' => $tecnicoResponsavelLabel ?? null]) ?>
<h4> Alterar Situação: </h4>
<?php
$hx = ' hx-target="#ticket-panel-left" hx-swap="innerHTML" ';
if ($ticket->situacao != C_TicketSituacaoFechado) {
	if ($ticket->situacao != C_TicketSituacaoPendente) {
		$url = $this->Url->build(["controller" => "Tickets", "action" => "alterarsituacao", $ticket->id, C_TicketSituacaoPendente]);
		echo $this->Html->link('<span> Aguardando técnico </span> <i class="fas fa-reply"></i>', $url, ['rel' => 'tooltip', 'title' => 'Pendente', 'class' => 'btn btn-warning btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
	}
	if ($ticket->situacao != C_TicketSituacaoEmandamento) {
		$url = $this->Url->build(["controller" => "Ticketsusers", "action" => "resolver", $ticket->id]);
		echo $this->Html->link('<span> Em execução </span> <i class="fas fa-reply"></i>', $url, ['rel' => 'tooltip', 'title' => 'Em andamento', 'class' => 'btn btn-pgm btn-pgm-situacao btn-info btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
	}
	if ($ticket->situacao != C_TicketSituacaoResolvido) {
		$url = $this->Url->build(["controller" => "Tickets", "action" => "alterarsituacao", $ticket->id, C_TicketSituacaoResolvido]);
		echo $this->Html->link('<span> Resolvido </span> <i class="fas fa-check"></i>', $url, ['rel' => 'tooltip', 'title' => 'Resolvido', 'class' => 'btn btn-pgm btn-pgm-salvar btn-success btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
	}
}
if (in_array($ticket->situacao, [C_TicketSituacaoEmandamento, C_TicketSituacaoPendente])) echo $this->Html->link('<span> Cancelado </span> <i class="fa fa-times"></i>', ["controller" => "Tickets", "action" => "cancelar", $ticket->id], ['rel' => 'tooltip', 'title' => 'Fechado', 'class' => 'btn btn-danger btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false]);
?>
<br>
<h5 class="text-muted"> Horas de contrato (cliente) </h5>
<p class="small">
	<strong>Consumidas neste ticket:</strong> <?= number_format(($minutosTicket ?? 0) / 60, 1, ',', '.') ?> h<br>
	<strong>Consumidas no mês (cliente):</strong> <?= number_format(($minutosClienteMes ?? 0) / 60, 1, ',', '.') ?> h<br>
	<?php if (!empty($horasContratoTexto)): ?>
		<strong>Contrato:</strong> <?= h($horasContratoTexto) ?>
	<?php else: ?>
		<strong>Contrato:</strong> — (cadastre em Contratos de Horas)
	<?php endif; ?>
</p>
<br>
<h4> Rastreamento de Tempo: </h4>
<?php
$urlIniciar = $this->Url->build(['controller' => 'Tickets', 'action' => 'timerIniciar', $ticket->id]);
$urlPausar = $this->Url->build(['controller' => 'Tickets', 'action' => 'timerPausar', $ticket->id]);
$urlRetomar = $this->Url->build(['controller' => 'Tickets', 'action' => 'timerRetomar', $ticket->id]);
$urlFinalizar = $this->Url->build(['controller' => 'Tickets', 'action' => 'timerFinalizar', $ticket->id]);
$urlTimeEntriesApi = $this->Url->build(['controller' => 'Tickets', 'action' => 'apiTimeEntries', $ticket->id]);
$hxAttrs = ' hx-target="#ticket-panel-left" hx-swap="innerHTML" ';
$horaInicioTimer = null;
if (!empty($timerAtivo)) {
	$horaInicioTimer = $timerAtivo->get('hora_inicio');
	if (is_object($horaInicioTimer) && method_exists($horaInicioTimer, 'format')) {
		$horaInicioTimer = $horaInicioTimer->format('Y-m-d H:i:s');
	}
	$horaInicioTimer = (string)$horaInicioTimer;
}
?>
<div class="sd-time-tracker-card" data-ticket-id="<?= (int)$ticket->id ?>" data-api-time-entries="<?= h($urlTimeEntriesApi) ?>">
	<div class="sd-time-display bg-info text-white rounded p-2 mb-2 sd-timer-precision"
		data-hora-inicio="<?= h((string)$horaInicioTimer) ?>"
		data-pausado="<?= !empty($timerPausado) ? '1' : '0' ?>"
		data-pausado-elapsed="<?= h((string)($timerPausadoElapsedTexto ?? '')) ?>">
		00:00:00
		<span class="float-right">
			<?php if (empty($timerAtivo)): ?>
				<form method="post" action="<?= h($urlIniciar) ?>" class="d-inline" hx-post="<?= h($urlIniciar) ?>" <?= $hxAttrs ?>><button type="submit" class="btn btn-link p-0 text-white" title="Iniciar">&#9658;</button></form>
			<?php elseif (!empty($timerPausado)): ?>
				<form method="post" action="<?= h($urlRetomar) ?>" class="d-inline" hx-post="<?= h($urlRetomar) ?>" <?= $hxAttrs ?>><button type="submit" class="btn btn-link p-0 text-dark" title="Retomar">&#9658;</button></form>
			<?php else: ?>
				<form method="post" action="<?= h($urlPausar) ?>" class="d-inline" hx-post="<?= h($urlPausar) ?>" <?= $hxAttrs ?>><button type="submit" class="btn btn-link p-0 text-white" title="Pausar">&#10074;&#10074;</button></form>
			<?php endif; ?>
			<?php if (!empty($timerAtivo)): ?>
				<form method="post" action="<?= h($urlFinalizar) ?>" class="d-inline ml-2" hx-post="<?= h($urlFinalizar) ?>" <?= $hxAttrs ?> hx-confirm="Finalizar o timer e registrar as horas?"><button type="submit" class="btn btn-link p-0 text-white" title="Finalizar">&#9632;</button></form>
			<?php endif; ?>
		</span>
	</div>
	<div class="sd-time-links">
		<a href="javascript:void(0)" class="js-time-entry-manual" data-ticket-id="<?= (int)$ticket->id ?>">Entrada Manual de Tempo</a>
	</div>
	<div class="sd-time-links m-t-5">
		<a href="javascript:void(0)" class="js-time-entry-list" data-ticket-id="<?= (int)$ticket->id ?>">Ver todas as entradas</a>
	</div>
	<div class="sd-time-subtle m-t-5">
		12h clock
	</div>
</div>
<br>
<h4> Ações: </h4>
<?php
	if (in_array($ticket->situacao, [C_TicketSituacaoEmandamento, C_TicketSituacaoResolvido])) echo $this->Html->link('Cadastrar Horas', ["action" => "cadhoras", $ticket->id], ['class' => 'btn btn-pgm btn-pgm-salvar text-white m-r-5']);
	if ($ticket->situacao == C_TicketSituacaoResolvido) echo $this->Html->link('Enviar e-mail', ["action" => "email", $ticket->id, null, 'redirect'], ['class' => 'btn btn-pgm btn-pgm-email btn-email btn-purple text-white m-r-5']);
	if (empty($ordem)) echo $this->Html->link('Gerar Ordem de Serviço', ["controller" => "ordensservico", "action" => "ticketordem", $ticket->id], ['class' => 'btn btn-warning text-white m-r-5', 'target' => '_blank']);
	else echo $this->Html->link("Ordem nº $ordem gerada", ["controller" => "ordensservico", "action" => "edit", $ordem], ['class' => 'btn btn-warning text-white m-r-5', 'target' => '_blank']);
	echo $this->Html->link("Imprimir", ["controller" => "Tickets", "action" => "imprimir", $ticket->id, "?" => ["autoprint" => 1]], ['class' => 'btn btn-pgm btn-pgm-imprimir btn-orange text-white m-r-5', 'target' => '_blank']);
?>
