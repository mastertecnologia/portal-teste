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
				<div class="mail-contnet" style="width:100%">
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
		echo $this->Html->link('<span> Em execução </span> <i class="fas fa-reply"></i>', $url, ['rel' => 'tooltip', 'title' => 'Em andamento', 'class' => 'btn btn-info btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
	}
	if ($ticket->situacao != C_TicketSituacaoResolvido) {
		$url = $this->Url->build(["controller" => "Tickets", "action" => "alterarsituacao", $ticket->id, C_TicketSituacaoResolvido]);
		echo $this->Html->link('<span> Resolvido </span> <i class="fas fa-check"></i>', $url, ['rel' => 'tooltip', 'title' => 'Resolvido', 'class' => 'btn btn-success btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
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
<h4> Timer (Horas Técnicas): </h4>
<?php if (!empty($timerAtivo) && !$timerPausado): ?>
	<?php
	$horaInicioTimer = $timerAtivo->get('hora_inicio');
	if (is_object($horaInicioTimer) && method_exists($horaInicioTimer, 'format')) {
		$horaInicioTimer = $horaInicioTimer->format('Y-m-d H:i:s');
	}
	$horaInicioTimer = (string)$horaInicioTimer;
	?>
	<div id="timer-cronometro" class="mb-2 p-2 bg-info text-white rounded" data-hora-inicio="<?= h($horaInicioTimer) ?>" style="font-family: monospace; font-size: 1.4rem;">00:00:00</div>
<?php elseif (!empty($timerAtivo) && $timerPausado && !empty($timerPausadoElapsedTexto)): ?>
	<div id="timer-cronometro-pausado" class="mb-2 p-2 bg-warning text-dark rounded" style="font-family: monospace; font-size: 1.4rem;"><?= h($timerPausadoElapsedTexto) ?> <small>(pausado)</small></div>
<?php endif; ?>
<?php
$urlIniciar = $this->Url->build(['controller' => 'Tickets', 'action' => 'timerIniciar', $ticket->id]);
$urlPausar = $this->Url->build(['controller' => 'Tickets', 'action' => 'timerPausar', $ticket->id]);
$urlRetomar = $this->Url->build(['controller' => 'Tickets', 'action' => 'timerRetomar', $ticket->id]);
$urlFinalizar = $this->Url->build(['controller' => 'Tickets', 'action' => 'timerFinalizar', $ticket->id]);
$hxAttrs = ' hx-target="#ticket-panel-left" hx-swap="innerHTML" ';
if (in_array($ticket->situacao, [C_TicketSituacaoEmandamento, C_TicketSituacaoPendente])) {
	if (empty($timerAtivo)) {
		echo '<form method="post" action="' . h($urlIniciar) . '" class="d-inline" hx-post="' . h($urlIniciar) . '"' . $hxAttrs . '><button type="submit" class="btn btn-info btn-simple btn-xs m-r-5">Iniciar</button></form>';
	} else {
		if ($timerPausado) {
			echo '<form method="post" action="' . h($urlRetomar) . '" class="d-inline" hx-post="' . h($urlRetomar) . '"' . $hxAttrs . '><button type="submit" class="btn btn-warning btn-simple btn-xs m-r-5">Retomar</button></form>';
		} else {
			echo '<form method="post" action="' . h($urlPausar) . '" class="d-inline" hx-post="' . h($urlPausar) . '"' . $hxAttrs . '><button type="submit" class="btn btn-warning btn-simple btn-xs m-r-5">Pausar</button></form>';
		}
		echo ' ';
		echo '<form method="post" action="' . h($urlFinalizar) . '" class="d-inline" hx-post="' . h($urlFinalizar) . '"' . $hxAttrs . ' hx-confirm="Finalizar o timer e registrar as horas?"><button type="submit" class="btn btn-success btn-simple btn-xs m-r-5">Finalizar</button></form>';
	}
}
?>
<br>
<h4> Ações: </h4>
<?php
	if (in_array($ticket->situacao, [C_TicketSituacaoEmandamento, C_TicketSituacaoResolvido])) echo $this->Html->link('Cadastrar Horas', ["action" => "cadhoras", $ticket->id], ['class' => 'btn btn-orange text-white m-r-5']);
	if ($ticket->situacao == C_TicketSituacaoResolvido) echo $this->Html->link('Enviar e-mail', ["action" => "email", $ticket->id, null, 'redirect'], ['class' => 'btn btn-email btn-purple text-white m-r-5']);
	if (empty($ordem)) echo $this->Html->link('Gerar Ordem de Serviço', ["controller" => "ordensservico", "action" => "ticketordem", $ticket->id], ['class' => 'btn btn-warning text-white m-r-5', 'target' => '_blank']);
	else echo $this->Html->link("Ordem nº $ordem gerada", ["controller" => "ordensservico", "action" => "edit", $ordem], ['class' => 'btn btn-warning text-white m-r-5', 'target' => '_blank']);
echo $this->Html->link("Imprimir", ["controller" => "Tickets", "action" => "imprimir", $ticket->id, "?" => ["autoprint" => 1]], ['class' => 'btn btn-purple text-white m-r-5', 'target' => '_blank']);
?>
