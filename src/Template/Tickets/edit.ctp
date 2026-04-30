<?php
	use Cake\Routing\Router;
	// Formata data com segurança (objeto ou string) para evitar erro ao exibir
	$formatarData = function($v, $fmt = 'd/m/Y') {
		if (empty($v)) return '';
		if (is_object($v) && method_exists($v, 'format')) return $v->format($fmt);
		if (is_string($v)) { $t = strtotime($v); return $t ? date($fmt, $t) : $v; }
		return (string)$v;
	};
	// Breadcumbs
	$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);

	$atualizados = 0;

	$disabled = $admin != 1 ? true : false;
	$disableCheckCliente = $ticket->situacao == C_TicketSituacaoEmandamento || $ticket->situacao == C_TicketSituacaoResolvido ? true : false;
	echo $this->Html->script('https://cdn.quilljs.com/1.3.6/quill.min.js');
	echo $this->Html->css('https://cdn.quilljs.com/1.3.6/quill.snow.css');
	echo $this->Html->script('https://unpkg.com/htmx.org@1.9.10');
?>

<style>
	.container {
		height: 300px;
		position: relative;
		overflow: auto;
	}

	.bg {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: space-between;
		font-family: 'Lato', sans-serif;
    }
    .file-drop-area {
		position: relative;
		display: flex;
		align-items: center;
		width: 100%;
		max-width: 100%;
		padding: 4px;
		border-bottom: 1px solid #E9ECEF;
		/* border-radius: 3px; */
		transition: 0.2s;
    }
    .fake-btn {
		flex-shrink: 0;
		border-radius: 3px;
		padding: 5px;
		margin-right: 30px;
		font-size: 12px;
		text-transform: uppercase;
    }
    .file-msg {
		font-size: small;
		font-weight: 300;
		line-height: 1.4;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
    }
    .file-input {
		position: absolute;
		left: 0;
		top: 0;
		height: 100%;
		width: 100%;
		cursor: pointer;
		opacity: 0;
    }
	.comment-text p{
		overflow: auto !important;
		max-height: none !important;
	}
	.sd-time-modal .modal-dialog { max-width: 860px; }
	.sd-time-modal .modal-content { border-radius: 14px; border: 1px solid #dce3ea; }
	.sd-time-modal .modal-header { padding: 14px 18px; border-bottom: 1px solid #edf1f5; }
	.sd-time-modal .modal-title { font-size: 15px; font-weight: 700; }
	.sd-time-modal .modal-body { padding: 14px 18px; }
	.sd-time-modal .modal-footer { padding: 12px 18px; border-top: 1px solid #edf1f5; }
	.sd-time-table-wrap { max-height: 320px; overflow: auto; }
	.sd-time-clickable { cursor: pointer; }
	.sd-time-display { font-size: 22px; font-weight: 700; letter-spacing: 0.6px; min-height: 48px; }
	.sd-time-display .btn-link { text-decoration: none !important; opacity: 0.95; }
	.sd-time-display .btn-link:hover { opacity: 1; }
	.sd-time-muted { color: #6c757d; font-size: 11px; }
	.sd-time-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
	.sd-time-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
	.sd-time-links a { color: #1f2d3d; font-size: 12px; text-decoration: none; }
	.sd-time-links a:hover { text-decoration: underline; }
	.sd-time-subtle { color: #7b8794; font-size: 11px; }
	.sd-time-divider { display: flex; align-items: center; gap: 8px; margin: 4px 0 8px; }
	.sd-time-divider:before,
	.sd-time-divider:after { content: ""; height: 1px; background: #e7edf3; flex: 1; }
	.sd-time-divider button { border: 0; background: transparent; color: #44515d; font-size: 11px; padding: 0; }
	.sd-time-modal label { font-size: 12px; color: #61707f; margin-bottom: 4px; }
	.sd-time-modal .form-control { height: 38px; }
	.sd-time-modal textarea.form-control { height: auto; min-height: 88px; }
	.sd-time-table-wrap .table thead th { font-size: 11px; font-weight: 700; text-transform: uppercase; }
	.sd-time-table-wrap .table tbody td { font-size: 12px; vertical-align: middle; }
	@media (max-width: 768px) {
		.sd-time-grid,
		.sd-time-grid-3 { grid-template-columns: 1fr; }
	}
</style>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
			<ul class="nav nav-tabs customtab m-b-20" role="tablist">
                <li class="nav-item"> <a class="nav-link active " data-toggle="tab" href="#ticket" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="icon-doc"></i></span> <span class="hidden-xs-down">Ticket</span></a> </li>
				<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#arquivos" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-clip"></i></span> <span class="hidden-xs-down">Anexos (<?= count($ticketanexos) ?>)</span></a> </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#movimentacoes" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-reload"></i></span> <span class="hidden-xs-down">Movimentações (<?= count($ticketsmovs) ?>)</span></a> </li>
				<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#horas" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-time"></i></span> <span class="hidden-xs-down">Horas Cadastradas (<?= count($ticketshoras) ?>)</span></a> </li>
            </ul>
			<div class="tab-content">
				<div class="tab-pane active" id="ticket">
                    <div class="row">
                        <!-- Parte da Esquerda -->
							<div class="col-4 bg-secondary " id="ticket-panel-left">
								<br>
								<h5 class="text-muted">Autor</h5>
								<div class="message-box bg-white">
									<div class="message-widget message-scroll">
										<a href="javascript:void(0)">
											<div class="mail-contnet">
												<h5><?= $ticket['user']['name'] ?></h5>
											</div>
										</a>
									</div>
								</div>
								<h5 class="text-muted">Membros do Ticket</h5>
								<?php foreach($ticketsusers as $user){ ?>
									<div class="message-box bg-white">
										<div class="message-widget message-scroll">
											<a href="#" class='add-darkmode'>
												<div class="mail-contnet mail-contnet--full">
													<div class="row">
														<div class="col-12">
															<h5><?= $user['Users']['name'] ?></h5>
														</div>
													</div>
												</div>
											</a>
										</div>
									</div>
								<?php } ?>
								<br>
								<h5><span class="text-muted"> Setor: </span><?= !empty($ticket['user']['setor']) ? $ticket['user']['setor'] : "Indefinido"; ?></h5>
								<h5><span class="text-muted"> Celular: </span><?= !empty($ticket['user']['celular']) ? $ticket['user']['celular'] : "Indefinido"; ?></h5>
								<h5><span class="text-muted"> Telefone: </span><?= !empty($ticket['user']['telefone']) ? $ticket['user']['telefone'] : "Indefinido"; ?></h5>
								<br> 
								<h5><span class="text-muted"> Cliente: </span> <?= $cliente ?></h5>
								<h5><span class='text-muted'> Solicitante: </span> <?php if(!empty($solicitante)) echo $solicitante; else echo empty($ticket->nomesolicitante) ? "Indefinido" : $ticket->nomesolicitante; ?></h5>
								<h5><span class="text-muted"> E-mail: </span><?php if(!empty($ticket->email)) echo $ticket->email; else echo "Indefinido"; ?></h5>
								<br> 
								<h5><span class="text-muted"> Assunto: </span><?= AssuntoTicket($ticket->assunto) ?></h5>
								<h5><span class="text-muted"> Status: </span><?= SituacaoTicket($ticket->situacao) ?></h5>
								<br>
								<?= $this->element('ticket_tecnico_responsavel', ['tecnicoResponsavelLabel' => $tecnicoResponsavelLabel ?? null]) ?>
								<h4> Alterar Situação: </h4>
								<?php
									if ($ticket->situacao != C_TicketSituacaoFechado) {
										if ($ticket->situacao != C_TicketSituacaoPendente) {
											$url = $this->Url->build(["action" => "alterarsituacao", $ticket->id, C_TicketSituacaoPendente]);
											echo $this->Html->link('<span> Aguardando técnico </span> <i class="fas fa-reply"></i>', $url, ['rel' => 'tooltip', 'title' => 'Pendente', 'class' => 'btn btn-warning btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
										}
										if ($ticket->situacao != C_TicketSituacaoEmandamento) {
											$url = $this->Url->build(["controller" => "Ticketsusers", "action" => "resolver", $ticket->id]);
											echo $this->Html->link('<span> Em execução </span> <i class="fas fa-reply"></i>', $url, ['rel' => 'tooltip', 'title' => 'Em andamento', 'class' => 'btn btn-pgm btn-pgm-situacao btn-info btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
										}
										if ($ticket->situacao != C_TicketSituacaoResolvido) {
											$url = $this->Url->build(["action" => "alterarsituacao", $ticket->id, C_TicketSituacaoResolvido]);
											echo $this->Html->link('<span> Resolvido </span> <i class="fas fa-check"></i>', $url, ['rel' => 'tooltip', 'title' => 'Resolvido', 'class' => 'btn btn-pgm btn-pgm-salvar btn-success btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
										}
									}
									if (in_array($ticket->situacao, [C_TicketSituacaoEmandamento, C_TicketSituacaoPendente])) echo $this->Html->link('<span> Cancelado </span> <i class="fa fa-times"></i>', ["action" => "cancelar", $ticket->id], ['rel' => 'tooltip', 'title' => 'Fechado', 'class' => 'btn btn-danger btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false]);
								?>
								<br>
								<h5 class="text-muted"> Horas de contrato (cliente) </h5>
								<p class="small">
									<strong>Consumidas neste ticket:</strong> <?= number_format($minutosTicket / 60, 1, ',', '.') ?> h<br>
									<strong>Consumidas no mês (cliente):</strong> <?= number_format($minutosClienteMes / 60, 1, ',', '.') ?> h<br>
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
									if(in_array($ticket->situacao, [C_TicketSituacaoEmandamento, C_TicketSituacaoResolvido])) echo $this->Html->link('Cadastrar Horas', ["action" => "cadhoras", $ticket->id], ['class' => 'btn btn-pgm btn-pgm-salvar text-white m-r-5']); 
									if($ticket->situacao == C_TicketSituacaoResolvido) echo $this->Html->link('Enviar e-mail', ["action" => "email", $ticket->id, null, 'redirect'], ['class' => 'btn btn-pgm btn-pgm-email btn-email btn-purple text-white m-r-5']); 
									if (empty($ordem)) {
										echo $this->Html->link(
											'Gerar Ordem de Serviço',
											['_name' => 'ticketsGerarOs', 'id' => (int)$ticket->id],
											['class' => 'btn btn-warning text-white m-r-5', 'target' => '_blank']
										);
									} else {
										echo $this->Html->link(
											"Ver OS #$ordem",
											["controller" => "ordensservico", "action" => "edit", $ordem],
											['class' => 'btn btn-warning text-white m-r-5', 'target' => '_blank']
										);
									}
									echo $this->Html->link("Imprimir", ["controller" => "Tickets", "action" => "imprimir", $ticket->id, "?" => ["autoprint" => 1]], ['class' => 'btn btn-pgm btn-pgm-imprimir btn-orange text-white m-r-5', 'target' => '_blank']);
								?>
							</div>
                        <!-- Fim da Parte da Esquerda -->
                        <!-- Parte da Direita -->
							<div class="col-8">
								<p class='h3'><?= AssuntoTicket($ticket->assunto) ?> <small class="h5 text-muted"> <?= $formatarData($ticket->created, 'd/m/Y') ?> </small></p>
								<?php if($admin ){ ?>
									<div class='form-material'>
										<br><p class='h5'> Descrição:  </p>
										<div class="row">
											<div class="col-lg-12">
												<p class="form-control ticket-reply-plain">
													<?= nl2br($ticket->solicitacao) ?>
												</p>
											</div>
										</div>
									</div>
									<br>
								<?php } else { ?>
									<p class='h5'> Descrição:  </p>
									<p> <?= nl2br($ticket->solicitacao) ?></p>
									<br>
								<?php  } if($ticket->assunto == C_TicketCategoriaVisita) { ?>
									<p class='h6'> Data solicitada para a visita: <b> <?= $formatarData($ticket->data, 'd/m/Y') ?> </b> </p>
								<?php } ?>
								<br><p class='h5'> Adicionar comentário:  </p>
								<?php
									$urlComentario = $this->Url->build(['controller' => 'Ticketcomentarios', 'action' => 'add', $ticket->id]);
								?>
								<?= $this->Form->create(null, ['url' => ['controller' => 'Ticketcomentarios', 'action' => 'add', $ticket->id], 'class' => 'form-material', 'id' => 'comment-form', 'data-hx-url' => $urlComentario]); ?>
									<div class="row">
										<div class="col-lg-12">
											<!-- Editor Quill -->
											<div id="quill-editor" class="ticket-quill-editor-mount"></div>
											<?= $this->Form->hidden('comentario', ['id' => 'comentario-hidden']); ?>
											<?= $this->Form->hidden('comentario_texto', ['id' => 'comentario-texto']); ?>
										</div>
									</div>
									<?= $this->Form->button('Comentar', ['id' => 'submitcomentario', 'class' => 'btn btn-secondary btn-simple btn-sm m-r-5']) ?>
								<?= $this->Form->end(); ?>
								<hr>
								<!-- Comentários (atualizado via HTMX) -->
								<div class="container">
									<div class="content">
										<div class="comment-widgets m-b-20" id="comments-list">
											<?php foreach(array_reverse($ticketcomentarios) as $comentario){?>
												<div class="d-flex comment-row">
													<div class="p-2">
														<span class="round">
															<?php
																if ($comentario['Users']['role'] == 1) $imagem = "cliente.png";
																else $imagem = "tecnico.png";
															?>
															<img src="<?=$this->request->getAttribute('webroot') . 'assets/images/' . $imagem ?>" alt="user" width="50">
														</span>
													</div>
													<div class="comment-text">
														<h5><?= $comentario['Users']['name'] ?></h5>
														<div class="comment-footer">
															<span class="date"> <?= $formatarData($comentario->created, 'd/m/Y') ?>, <?= $formatarData($comentario->created, 'H:i') ?> </span>
														</div>
														<p class="m-b-5 m-t-10"><?= $comentario->comentario ?></p>
													</div>
												</div>
											<?php }?>
										</div>
									</div>
								</div>
							</div>
                        <!-- Fim da Parte da Direita -->
                    </div>
				</div>
				<div class="tab-pane" id="movimentacoes">
				    <?php foreach (array_reverse($ticketsmovs) as $reg):
						$data = $reg['datetime'];
						echo "<br><div class='col-lg-12'><strong>" . $reg['user']->name . "</strong>";
						echo " - " . $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y') . " às " . $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('H:i') . "<br>";
						echo "<p>";

						if ($reg['sitantiga'] == C_TicketSituacaoPendente && $reg['sitnova'] == 0) echo "Abriu o ticket.";

						else if ($reg['sitnova'] == C_TicketSituacaoPendente && $reg['sitantiga'] == C_TicketSituacaoFechado) echo "Reabriu o ticket'.";

						//0 - Pendente
						else if ($reg['sitnova'] == C_TicketSituacaoPendente && $reg['sitnova'] != $reg['sitantiga']) echo "Alterou a situação do ticket para 'Aguardando técnico'.";

						//1 - Respondido (observacao = trecho do comentário)
						else if ($reg['sitnova'] == C_TicketSituacaoRespondido && $reg['sitnova'] != $reg['sitantiga']) {
							echo "Publicou um comentário.";
							if (!empty($reg['observacao'])) {
								echo ' <em class="text-muted">' . h($reg['observacao']) . '</em>';
							}
						}

						//2 - Resolvido
						else if ($reg['sitnova'] == C_TicketSituacaoResolvido && $reg['sitnova'] != $reg['sitantiga'] && empty($reg['observacao'])) echo "Resolveu o ticket.";
						//2 - Resolvido pela OS
						else if ($reg['sitnova'] == C_TicketSituacaoResolvido && $reg['sitnova'] != $reg['sitantiga']) echo $reg['observacao'];

						//3 - Em andamento
						else if ($reg['sitnova'] == C_TicketSituacaoEmandamento && $reg['sitnova'] != $reg['sitantiga']) echo "Alterou a situação do ticket para 'Em execução'.";

						//4 - Fechado
						else if ($reg['sitnova'] == C_TicketSituacaoFechado && $reg['sitnova'] != $reg['sitantiga']) echo "Cancelou o ticket. <br> Motivo: " . $reg['observacao'];
						//4 - Fechado
						else if ($reg['sitnova'] == C_TicketSituacaoFechado && $reg['sitnova'] != $reg['sitantiga']) echo $reg['observacao'];

						//5 - Anexo Adicionado
						if ($reg['sitnova'] == C_TicketAnexoAdicionado) echo "O anexo '".$reg['observacao']."' foi adicionado.";

						//6 - Anexo Deletado
						if ($reg['sitnova'] == C_TicketAnexoDeletado) echo "O anexo '".$reg['observacao']."' foi deletado.";

						else if (isset($reg['sitnova']) && $reg['sitnova'] == C_TicketMovTransferencia) echo nl2br(h($reg['observacao'] ?? ''));
						else if (isset($reg['sitnova']) && $reg['sitnova'] == C_TicketMovMudancaFila) echo nl2br(h($reg['observacao'] ?? ''));

						//10/11/12 - Timer horas técnicas
						else if (isset($reg['sitnova']) && $reg['sitnova'] == C_TicketTimerIniciado) echo !empty($reg['observacao']) ? $reg['observacao'] : 'Iniciou o timer de horas técnicas.';
						else if (isset($reg['sitnova']) && $reg['sitnova'] == C_TicketTimerPausado) echo !empty($reg['observacao']) ? $reg['observacao'] : 'Pausou o timer de horas técnicas.';
						else if (isset($reg['sitnova']) && $reg['sitnova'] == C_TicketTimerFinalizado) echo !empty($reg['observacao']) ? $reg['observacao'] : 'Finalizou o timer de horas técnicas.';

						echo "</p><hr></div>";
					endforeach; ?>
				</div>
				<div class="tab-pane" id="arquivos">
					<div class="card">
						<div class="row">
							<?php $urlAnexo = $this->Url->build(['controller' => 'Ticketsanexos', 'action' => 'add', $ticket->id]); ?>
							<?= $this->Form->create(null, ['url' => ['controller' => 'Ticketsanexos', 'action' => 'add', $ticket->id], 'enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material', 'id' => 'form-anexos', 'data-hx-url' => $urlAnexo]) ?>
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<div class="form-group">
										<label class="control-label text-muted">Adicionar Anexo</label>
										<div class="bg">
											<div class="file-drop-area">
												<span class="fake-btn text-muted">Escolha o(s) arquivo(s) ou arraste-o(s) aqui</span>
												<input multiple class="file-input form-control"  name="file-3[]" id="file-3" type="file">
											</div>
										</div>
									</div>
								</div>
							<?= $this->Form->button('Adicionar Anexo', ['id' => 'submitfile', 'class' => 'btn btn-pgm btn-pgm-salvar btn-primary text-white', 'disabled']) ?>
							<br>
							<div class="clearfix"></div>
							<?= $this->Form->end(); ?>
						</div>
						<br>
						<div class="table-responsive">
							<table class="table table-hover" id="tableLicencas">
								<thead class="text-primary">
									<th>Arquivo</th>
									<th width="10%">Ações</th>
								</thead>
								<tbody id="anexos-tbody">
									<?php foreach ($ticketanexos as $reg){?>
										<tr>
											<td><?= h($reg->arquivo) ?></td>
											<td class="td-actions">
											<?= $this->Html->link(
												'<i class="fa fa-eye"></i> Visualizar',
												['controller' => 'Tickets', 'action' => 'downloadAnexo', $reg->id, '?' => ['inline' => '1']],
												['target' => '_blank', 'rel' => 'noopener noreferrer', 'class' => 'btn btn-pgm btn-pgm-situacao btn-info btn-simple btn-xs m-r-5', 'escape' => false, 'title' => 'Abrir no navegador']
											) ?>
											<?= $this->Html->link(
												'<i class="fa fa-download"></i> Baixar',
												['controller' => 'Tickets', 'action' => 'downloadAnexo', $reg->id],
												['class' => 'btn btn-secondary btn-simple btn-xs m-r-5', 'escape' => false, 'title' => 'Download']
											) ?>
											<?php if ($admin == 1) echo $this->Html->link('<i class="fa fa-times"></i>', ["controller" => "Tickets", "action" => "deleteAnexo", $reg->id], ['rel' => 'tooltip', 'title' => 'Excluir', 'class' => 'btn btn-danger btn-simple btn-xs', 'escape' => false]) ?>
											</td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<div class="tab-pane" id="horas">
					<?php if (sizeof($ticketshoras) > 0) { ?>
						<div class="table-responsive">
							<table class="table table-hover" id="tableProgramas">
								<thead class="text-primary">
									<th>Usuário</th>
									<th>Data</th>
									<th>Horário</th>
									<?php if($admin == 1) echo "<th width='10%'>Ações</th>"; ?>
								</thead>
								<tbody>
									<?php foreach ($ticketshoras as $reg): ?>
										<tr>
											<td><?= $reg->user->username ?></td>
											<td><?= date_format($reg->data, 'd/m/Y'); ?></td>
											<td><?= date_format($reg->horaini, 'H:i') . " - " . date_format($reg->horafin, 'H:i'); ?></td>
											<?php if($admin == 1) { ?>
												<td class="td-actions">
													<?= $this->Html->link('<i class="fa fa-times"></i>', ['controller' => 'ticketshoras', "action" => "delete", $reg->id, 1], ['confirm' => 'Corfirmar deleção?', 'rel' => 'tooltip', 'title' => 'Excluir', 'class' => 'btn btn-danger btn-simple btn-xs', 'escape' => false]) ?>
												</td>
											<?php } ?>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php } else echo "<br><center><h4>Nenhum registro de horas encontrado</h4></center><br>"; ?>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- Modal Email -->
<div class="modal fade none-border" id="modal-email">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="row m-20">
				<div class="col-12">
					<?= $this->Form->create(null, ['url' => ['controller' => 'Tickets', 'action' => 'emailvarios'], 'class' => 'form-material']);?> 
						<div class="form-group destinatarios" >
							<label class="control-label text-muted">Destinatário</label>
							<?= $this->Form->control('email', ['name' => 'email[]', 'value' => $ticket->email, 'class' => 'form-control', 'label' => false, 'required' => true]) ?>
						</div>
						<?= $this->Form->control('idticket', ['value' => $ticket->id, 'label' => false, 'type' => 'hidden']) ?>
						<?= $this->Form->button('Enviar', ['class' => 'btn btn-pgm btn-pgm-email btn-purple text-white float-right m-l-10']) ?>
						<button type="button" class="btn btn-pgm btn-pgm-situacao btn-info btn-add-destinatario waves-effect float-right m-l-10">Adicionar destinatário</button>
						<button type="button" class="btn btn-danger waves-effect float-right" data-dismiss="modal">Fechar</button>
					<?= $this->Form->end(); ?>
				</div>
			</div>
			<div class="modal-footer">
			</div>
		</div>
	</div>
</div>
<!-- Modal Tempo -->
<div class="modal fade none-border sd-time-modal" id="modal-time-entries" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="sd-time-modal-title">Entradas de Tempo</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="sd-time-error" class="alert alert-danger d-none"></div>
				<div id="sd-time-list-view">
					<div class="sd-time-table-wrap table-responsive">
						<table class="table table-hover table-sm">
							<thead class="text-primary">
								<tr>
									<th>ID</th>
									<th>Técnico</th>
									<th>Duração</th>
									<th>Faturável</th>
									<th>Taxa</th>
									<th>Observação</th>
									<th width="120">Ações</th>
								</tr>
							</thead>
							<tbody id="sd-time-entries-body">
								<tr><td colspan="7" class="text-center text-muted">Carregando...</td></tr>
							</tbody>
						</table>
					</div>
				</div>
				<div id="sd-time-form-view" class="d-none">
					<form id="sd-time-form">
						<input type="hidden" id="sd-time-id" value="">
						<div class="form-group">
							<label>Duração</label>
							<input type="text" id="sd-time-duration" class="form-control" readonly>
							<small class="sd-time-muted">hh:mm:ss</small>
						</div>
						<div class="form-group">
							<label>Descrição</label>
							<textarea id="sd-time-note" class="form-control" rows="3" maxlength="4000" placeholder="Digite sua descrição"></textarea>
						</div>
						<div class="form-check m-b-10">
							<input type="checkbox" class="form-check-input" id="sd-time-billable" checked>
							<label class="form-check-label" for="sd-time-billable">Faturável</label>
						</div>
						<div class="form-group">
							<label>Taxa</label>
							<select id="sd-time-rate" class="form-control">
								<option value="">Nada selecionado</option>
								<option value="padrao">Padrão</option>
							</select>
						</div>
						<div class="sd-time-divider">
							<button type="button" id="sd-time-toggle-advanced">Mostrar mais</button>
						</div>
						<div id="sd-time-advanced-fields" class="d-none">
						<div class="sd-time-grid">
							<div class="form-group">
								<label>Data de início</label>
								<input type="date" id="sd-time-start-date" class="form-control" required>
							</div>
							<div class="form-group">
								<label>Hora de início</label>
								<input type="time" id="sd-time-start-time" class="form-control" step="1" required>
							</div>
						</div>
						<div class="sd-time-grid">
							<div class="form-group">
								<label>Data de término</label>
								<input type="date" id="sd-time-end-date" class="form-control" required>
							</div>
							<div class="form-group">
								<label>Hora de término</label>
								<input type="time" id="sd-time-end-time" class="form-control" step="1" required>
							</div>
						</div>
						<div class="sd-time-grid-3">
							<div class="form-group">
								<label>Técnico (ID)</label>
								<input type="number" id="sd-time-tech-id" min="1" class="form-control">
							</div>
							<div class="form-group sd-time-audit-only d-none">
								<label>Motivo da alteração</label>
								<input type="text" id="sd-time-audit-reason" class="form-control">
							</div>
							<div class="form-group sd-time-audit-only d-none">
								<label>Senha de auditoria</label>
								<input type="password" id="sd-time-audit-auth" class="form-control">
							</div>
						</div>
						<div class="text-right sd-time-subtle">12h clock</div>
						</div>
					</form>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" id="sd-time-close" class="btn btn-danger" data-dismiss="modal">Fechar</button>
				<button type="button" id="sd-time-back" class="btn btn-secondary d-none">Voltar</button>
				<button type="button" id="sd-time-add" class="btn btn-pgm btn-pgm-salvar text-white">Adicionar entrada de tempo</button>
				<button type="button" id="sd-time-save" class="btn btn-pgm btn-pgm-salvar text-white d-none">Salvar entrada</button>
			</div>
		</div>
	</div>
</div>
<script>
	(function () {
		var timeState = {
			ticketId: <?= (int)$ticket->id ?>,
			apiUrl: <?= json_encode($this->Url->build(['controller' => 'Tickets', 'action' => 'apiTimeEntries', $ticket->id]), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) ?>,
			entries: [],
			editingId: 0
		};
		var rafByElement = new WeakMap();
		function pad2(v) { return v < 10 ? '0' + v : '' + v; }
		function toHms(totalSeconds) {
			var sec = Math.max(0, Math.floor(totalSeconds));
			var h = Math.floor(sec / 3600);
			var m = Math.floor((sec % 3600) / 60);
			var s = sec % 60;
			return pad2(h) + ':' + pad2(m) + ':' + pad2(s);
		}
		function isoFromLocal(datePart, timePart) {
			if (!datePart || !timePart) return '';
			return new Date(datePart + 'T' + timePart).toISOString();
		}
		function splitIso(iso) {
			if (!iso) return { date: '', time: '' };
			var d = new Date(iso);
			if (isNaN(d.getTime())) return { date: '', time: '' };
			var local = new Date(d.getTime() - (d.getTimezoneOffset() * 60000));
			var as = local.toISOString();
			return { date: as.slice(0, 10), time: as.slice(11, 19) };
		}
		function showTimeError(msg) {
			var box = document.getElementById('sd-time-error');
			if (!box) return;
			if (!msg) {
				box.classList.add('d-none');
				box.textContent = '';
				return;
			}
			box.classList.remove('d-none');
			box.textContent = msg;
		}
		function initPrecisionTimers() {
			document.querySelectorAll('.sd-timer-precision').forEach(function (el) {
				var startRaw = String(el.getAttribute('data-hora-inicio') || '').trim();
				var paused = String(el.getAttribute('data-pausado') || '') === '1';
				var pausedElapsed = String(el.getAttribute('data-pausado-elapsed') || '').trim();
				if (rafByElement.has(el)) {
					cancelAnimationFrame(rafByElement.get(el));
					rafByElement.delete(el);
				}
				if (!startRaw) {
					if (paused && pausedElapsed) {
						var holder = el.firstChild;
						if (holder && holder.nodeType === Node.TEXT_NODE) holder.textContent = pausedElapsed + ' ';
						else el.insertAdjacentText('afterbegin', pausedElapsed + ' ');
					}
					return;
				}
				if (paused && pausedElapsed) {
					var pausedHolder = el.firstChild;
					if (pausedHolder && pausedHolder.nodeType === Node.TEXT_NODE) pausedHolder.textContent = pausedElapsed + ' ';
					else el.insertAdjacentText('afterbegin', pausedElapsed + ' ');
					return;
				}
				var startMs = Date.parse(startRaw.replace(' ', 'T'));
				if (!isFinite(startMs)) return;
				var perfStart = performance.now();
				var wallStart = Date.now();
				var elapsedStart = Math.max(0, wallStart - startMs);
				var draw = function () {
					var elapsedSeconds = (elapsedStart + (performance.now() - perfStart)) / 1000;
					var txt = toHms(elapsedSeconds);
					var textNode = el.firstChild;
					if (textNode && textNode.nodeType === Node.TEXT_NODE) textNode.textContent = txt + ' ';
					else el.insertAdjacentText('afterbegin', txt + ' ');
					rafByElement.set(el, requestAnimationFrame(draw));
				};
				draw();
			});
		}
		function setTimeView(listView) {
			document.getElementById('sd-time-list-view').classList.toggle('d-none', !listView);
			document.getElementById('sd-time-form-view').classList.toggle('d-none', listView);
			document.getElementById('sd-time-add').classList.toggle('d-none', !listView);
			document.getElementById('sd-time-back').classList.toggle('d-none', listView);
			document.getElementById('sd-time-save').classList.toggle('d-none', listView);
			document.getElementById('sd-time-modal-title').textContent = listView ? 'Entradas de Tempo' : (timeState.editingId > 0 ? 'Editar entrada de tempo' : 'Adicionar entrada de tempo');
		}
		async function fetchEntries() {
			showTimeError('');
			var body = document.getElementById('sd-time-entries-body');
			body.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Carregando...</td></tr>';
			try {
				var res = await fetch(timeState.apiUrl, { credentials: 'same-origin' });
				var json = await res.json();
				if (!json || !json.ok) throw new Error((json && json.message) || 'Falha ao carregar entradas.');
				timeState.entries = Array.isArray(json.entries) ? json.entries : [];
				if (!timeState.entries.length) {
					body.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Nenhuma entrada encontrada.</td></tr>';
					return;
				}
				body.innerHTML = timeState.entries.map(function (row) {
					var rate = row.rate ? String(row.rate) : '-';
					var note = row.note ? String(row.note) : '-';
					return '<tr>' +
						'<td>' + Number(row.id || 0) + '</td>' +
						'<td>' + (row.technicianName ? String(row.technicianName) : ('ID ' + Number(row.technicianContactId || 0))) + '</td>' +
						'<td>' + toHms(Number(row.durationSeconds || 0)) + '</td>' +
						'<td>' + (row.billable ? 'Sim' : 'Não') + '</td>' +
						'<td>' + rate + '</td>' +
						'<td>' + note + '</td>' +
						'<td>' +
							'<button type="button" class="btn btn-info btn-simple btn-xs js-time-edit" data-id="' + Number(row.id || 0) + '"><i class="fa fa-pencil"></i></button> ' +
							'<button type="button" class="btn btn-danger btn-simple btn-xs js-time-delete" data-id="' + Number(row.id || 0) + '"><i class="fa fa-times"></i></button>' +
						'</td>' +
					'</tr>';
				}).join('');
			} catch (err) {
				body.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Erro ao carregar.</td></tr>';
				showTimeError(err && err.message ? err.message : 'Erro ao carregar entradas.');
			}
		}
		function resetForm(entry) {
			timeState.editingId = entry && entry.id ? Number(entry.id) : 0;
			var now = splitIso(new Date().toISOString());
			var start = splitIso(entry && entry.startWorkHour ? entry.startWorkHour : new Date().toISOString());
			var end = splitIso(entry && entry.endWorkHour ? entry.endWorkHour : new Date(Date.now() + 60000).toISOString());
			document.getElementById('sd-time-id').value = timeState.editingId ? String(timeState.editingId) : '';
			document.getElementById('sd-time-note').value = entry && entry.note ? String(entry.note) : '';
			document.getElementById('sd-time-billable').checked = entry ? !!entry.billable : true;
			document.getElementById('sd-time-rate').value = entry && entry.rate ? String(entry.rate) : '';
			document.getElementById('sd-time-tech-id').value = entry && entry.technicianContactId ? String(entry.technicianContactId) : '';
			document.getElementById('sd-time-start-date').value = start.date || now.date;
			document.getElementById('sd-time-start-time').value = start.time || now.time;
			document.getElementById('sd-time-end-date').value = end.date || now.date;
			document.getElementById('sd-time-end-time').value = end.time || now.time;
			document.getElementById('sd-time-audit-reason').value = '';
			document.getElementById('sd-time-audit-auth').value = '';
			document.getElementById('sd-time-advanced-fields').classList.add('d-none');
			document.getElementById('sd-time-toggle-advanced').textContent = 'Mostrar mais';
			document.querySelectorAll('.sd-time-audit-only').forEach(function (el) {
				el.classList.toggle('d-none', !timeState.editingId);
			});
			updateDurationPreview();
		}
		function updateDurationPreview() {
			var s = isoFromLocal(document.getElementById('sd-time-start-date').value, document.getElementById('sd-time-start-time').value);
			var e = isoFromLocal(document.getElementById('sd-time-end-date').value, document.getElementById('sd-time-end-time').value);
			var out = document.getElementById('sd-time-duration');
			if (!s || !e) {
				out.value = '00:00:00';
				return;
			}
			var diff = Math.max(0, Math.floor((new Date(e).getTime() - new Date(s).getTime()) / 1000));
			out.value = toHms(diff);
		}
		async function saveEntry() {
			showTimeError('');
			var id = Number(document.getElementById('sd-time-id').value || 0);
			var payload = {
				id: id,
				TicketID: timeState.ticketId,
				StartWorkHour: isoFromLocal(document.getElementById('sd-time-start-date').value, document.getElementById('sd-time-start-time').value),
				EndWorkHour: isoFromLocal(document.getElementById('sd-time-end-date').value, document.getElementById('sd-time-end-time').value),
				TechnicianContactID: Number(document.getElementById('sd-time-tech-id').value || 0),
				Billable: !!document.getElementById('sd-time-billable').checked,
				Rate: String(document.getElementById('sd-time-rate').value || ''),
				Description: String(document.getElementById('sd-time-note').value || ''),
				auditReason: String(document.getElementById('sd-time-audit-reason').value || ''),
				auditAuthKey: String(document.getElementById('sd-time-audit-auth').value || '')
			};
			if (!payload.StartWorkHour || !payload.EndWorkHour) {
				showTimeError('Informe data/hora inicial e final.');
				return;
			}
			if (id > 0 && (!payload.auditReason.trim() || !payload.auditAuthKey.trim())) {
				showTimeError('Edição exige motivo e senha de auditoria.');
				return;
			}
			try {
				var res = await fetch(timeState.apiUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(payload)
				});
				var json = await res.json();
				if (!json || !json.ok) throw new Error((json && json.message) || 'Falha ao salvar entrada.');
				setTimeView(true);
				await fetchEntries();
				$('#ticket-panel-left').load(window.location.href + ' #ticket-panel-left > *', function () {
					initPrecisionTimers();
				});
			} catch (err) {
				showTimeError(err && err.message ? err.message : 'Falha ao salvar entrada.');
			}
		}
		async function deleteEntry(id) {
			var reason = window.prompt('Motivo da alteração (auditoria):', '');
			if (!reason || !String(reason).trim()) {
				showTimeError('Motivo obrigatório para excluir.');
				return;
			}
			var auth = window.prompt('Senha de auditoria:', '');
			if (!auth || !String(auth).trim()) {
				showTimeError('Senha de auditoria obrigatória para excluir.');
				return;
			}
			try {
				var res = await fetch(timeState.apiUrl, {
					method: 'DELETE',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ id: Number(id || 0), auditReason: String(reason).trim(), auditAuthKey: String(auth).trim() })
				});
				var json = await res.json();
				if (!json || !json.ok) throw new Error((json && json.message) || 'Falha ao excluir entrada.');
				await fetchEntries();
				$('#ticket-panel-left').load(window.location.href + ' #ticket-panel-left > *', function () {
					initPrecisionTimers();
				});
			} catch (err) {
				showTimeError(err && err.message ? err.message : 'Falha ao excluir entrada.');
			}
		}
		function openListModal() {
			setTimeView(true);
			showTimeError('');
			fetchEntries();
			$('#modal-time-entries').modal('show');
		}
		function openManualModal(entry) {
			resetForm(entry || null);
			setTimeView(false);
			showTimeError('');
			$('#modal-time-entries').modal('show');
		}
		document.addEventListener('click', function (ev) {
			var listBtn = ev.target.closest('.js-time-entry-list');
			if (listBtn) { ev.preventDefault(); openListModal(); return; }
			var manualBtn = ev.target.closest('.js-time-entry-manual');
			if (manualBtn) { ev.preventDefault(); openManualModal(null); return; }
			var editBtn = ev.target.closest('.js-time-edit');
			if (editBtn) {
				ev.preventDefault();
				var id = Number(editBtn.getAttribute('data-id') || 0);
				var row = timeState.entries.find(function (r) { return Number(r.id || 0) === id; });
				openManualModal(row || null);
				return;
			}
			var delBtn = ev.target.closest('.js-time-delete');
			if (delBtn) { ev.preventDefault(); deleteEntry(Number(delBtn.getAttribute('data-id') || 0)); }
		});
		document.getElementById('sd-time-add').addEventListener('click', function () { openManualModal(null); });
		document.getElementById('sd-time-back').addEventListener('click', function () { setTimeView(true); });
		document.getElementById('sd-time-save').addEventListener('click', saveEntry);
		document.getElementById('sd-time-toggle-advanced').addEventListener('click', function () {
			var panel = document.getElementById('sd-time-advanced-fields');
			var isHidden = panel.classList.contains('d-none');
			panel.classList.toggle('d-none', !isHidden);
			this.textContent = isHidden ? 'Mostrar menos' : 'Mostrar mais';
		});
		['sd-time-start-date', 'sd-time-start-time', 'sd-time-end-date', 'sd-time-end-time'].forEach(function (id) {
			var el = document.getElementById(id);
			if (el) el.addEventListener('change', updateDurationPreview);
		});
		initPrecisionTimers();
		document.body.addEventListener('htmx:afterSwap', function(ev) {
			if (ev.detail.target.id === 'ticket-panel-left') initPrecisionTimers();
			if (ev.detail.target.id === 'comments-list' && ev.detail.successful) {
				var form = document.getElementById('comment-form');
				if (form) form.reset();
				var q = window.quillCommentEditor;
				if (q && q.root) {
					q.setText('');
					q.root.innerHTML = '<p><br></p>';
				}
				var hid = document.getElementById('comentario-hidden');
				if (hid) hid.value = '';
				var txt = document.getElementById('comentario-texto');
				if (txt) txt.value = '';
			}
		});
	})();
	// Form comentário: desativado HTMX para evitar erro 2026; usa POST normal e redirect
	// (Se quiser reativar HTMX depois, descomente o bloco abaixo.)
	/*
	(function(){
		var f = document.getElementById('comment-form');
		if (f && f.getAttribute('data-hx-url')) {
			f.setAttribute('hx-post', f.getAttribute('data-hx-url'));
			f.setAttribute('hx-target', '#comments-list');
			f.setAttribute('hx-swap', 'innerHTML');
		}
	})();
	*/
	// Form anexos: HTMX para atualizar só a tabela
	(function(){
		var f = document.getElementById('form-anexos');
		if (f && f.getAttribute('data-hx-url')) {
			f.setAttribute('hx-post', f.getAttribute('data-hx-url'));
			f.setAttribute('hx-target', '#anexos-tbody');
			f.setAttribute('hx-swap', 'innerHTML');
			f.setAttribute('hx-encoding', 'multipart/form-data');
		}
	})();
	// Comentário
		$('#comentario').change(function(e){
			if ($('#comentario').val().length > 5000) {
			e.preventDefault();
				$('#submitcomentario').prop("disabled", true);
				alert('O comentário não pode ter mais de 5000 caracteres');
			} else {
			e.preventDefault();
				$('#submitcomentario').prop("disabled", false);
			}
		});
		$('.container').perfectScrollbar();
	// Files
		$(document).on('change', '.file-input', function(e) {
			var filesCount = $(this)[0].files.length;
			var $textContainer = $(this).prev();
			var fileName = $(this).val().split('\\').pop();
			if (filesCount > 0) {
				e.preventDefault();
				$('#submitfile').prop("disabled", false);
			} else {
				e.preventDefault();
				$('#submitfile').prop("disabled", true);
			}
			if (filesCount === 1) {
				// if single file is selected, show file name
				var fileName = $(this).val().split('\\').pop();
				$textContainer.text(fileName);
			} else {
				// otherwise show number of files
				$textContainer.text(filesCount + ' arquivos selecionados');
			}
			jQuery.each(jQuery('#file-3')[0].files, function(i, file) {
				console.log(file);
			});
		});
	// Email
		$('.btn-email').click(function(e){
			e.preventDefault();
			$('#modal-email').modal('toggle');
		});

		$('.btn-add-destinatario').click(function() {
			$('.destinatarios').append('<div class="input email required"><input type="email" name="email[]" class="form-control" id="" placeholder="Insira o e-mail do destinatário"></div>');
		})
	// 


	var quill = new Quill('#quill-editor', {
		theme: 'snow',
		modules: {
			toolbar: [
				['bold', 'italic', 'underline', 'strike'],
				['blockquote', 'code-block'],
				[{ 'header': 1 }, { 'header': 2 }],
				[{ 'list': 'ordered'}, { 'list': 'bullet' }],
				[{ 'script': 'sub'}, { 'script': 'super' }],
				[{ 'indent': '-1'}, { 'indent': '+1' }],
				[{ 'direction': 'rtl' }],
				[{ 'size': ['small', false, 'large', 'huge'] }],
				[{ 'header': [1, 2, 3, 4, 5, 6, false] }],
				[{ 'color': [] }, { 'background': [] }],
				[{ 'font': [] }],
				[{ 'align': [] }],
				['clean'],
				['link', 'image']
			]
		},
		placeholder: 'Digite seu comentário...',
	});
	window.quillCommentEditor = quill;

	// Atualiza os campos hidden quando o editor muda
	quill.on('text-change', function() {
		var htmlContent = quill.root.innerHTML;
		var textContent = quill.getText();
		
		$('#comentario-hidden').val(htmlContent);
		$('#comentario-texto').val(textContent);
	});

	// Validação do formulário
	$('#comment-form').on('submit', function(e) {
		var htmlContent = quill.root.innerHTML;
		var textContent = quill.getText().trim();
		
		// Valida se há conteúdo
		if (textContent === '' || textContent === '\n') {
			e.preventDefault();
			alert('Por favor, digite um comentário antes de enviar.');
			return false;
		}
		
		// Valida o tamanho máximo
		if (htmlContent.length > 5000) {
			e.preventDefault();
			alert('O comentário não pode ter mais de 5000 caracteres');
			return false;
		}
		
		// Atualiza os campos antes do envio
		$('#comentario-hidden').val(htmlContent);
		$('#comentario-texto').val(textContent);
	});

	// Comentários existentes - mantém a funcionalidade de scroll
	$('.container').perfectScrollbar();

	// Files
	$(document).on('change', '.file-input', function(e) {
		var filesCount = $(this)[0].files.length;
		var $textContainer = $(this).prev();
		var fileName = $(this).val().split('\\').pop();
		if (filesCount > 0) {
			e.preventDefault();
			$('#submitfile').prop("disabled", false);
		} else {
			e.preventDefault();
			$('#submitfile').prop("disabled", true);
		}
		if (filesCount === 1) {
			var fileName = $(this).val().split('\\').pop();
			$textContainer.text(fileName);
		} else {
			$textContainer.text(filesCount + ' arquivos selecionados');
		}
		jQuery.each(jQuery('#file-3')[0].files, function(i, file) {
			console.log(file);
		});
	});

	// Email
	$('.btn-email').click(function(e){
		e.preventDefault();
		$('#modal-email').modal('toggle');
	});

	$('.btn-add-destinatario').click(function() {
		$('.destinatarios').append('<div class="input email required"><input type="email" name="email[]" class="form-control" id="" placeholder="Insira o e-mail do destinatário"></div>');
	});
</script>
