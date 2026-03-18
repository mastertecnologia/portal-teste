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
												<div class="mail-contnet" style="width:100%">
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
								<h4> Alterar Situação: </h4>
								<?php
									if ($ticket->situacao != C_TicketSituacaoFechado) {
										if ($ticket->situacao != C_TicketSituacaoPendente) {
											$url = $this->Url->build(["action" => "alterarsituacao", $ticket->id, C_TicketSituacaoPendente]);
											echo $this->Html->link('<span> Aguardando técnico </span> <i class="fas fa-reply"></i>', $url, ['rel' => 'tooltip', 'title' => 'Pendente', 'class' => 'btn btn-warning btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
										}
										if ($ticket->situacao != C_TicketSituacaoEmandamento) {
											$url = $this->Url->build(["controller" => "Ticketsusers", "action" => "resolver", $ticket->id]);
											echo $this->Html->link('<span> Em execução </span> <i class="fas fa-reply"></i>', $url, ['rel' => 'tooltip', 'title' => 'Em andamento', 'class' => 'btn btn-info btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
										}
										if ($ticket->situacao != C_TicketSituacaoResolvido) {
											$url = $this->Url->build(["action" => "alterarsituacao", $ticket->id, C_TicketSituacaoResolvido]);
											echo $this->Html->link('<span> Resolvido </span> <i class="fas fa-check"></i>', $url, ['rel' => 'tooltip', 'title' => 'Resolvido', 'class' => 'btn btn-success btn-simple btn-xs m-b-20 m-r-5', 'id' => $ticket->id, 'escape' => false, 'hx-get' => $url, 'hx-target' => '#ticket-panel-left', 'hx-swap' => 'innerHTML']);
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
									if(in_array($ticket->situacao, [C_TicketSituacaoEmandamento, C_TicketSituacaoResolvido])) echo $this->Html->link('Cadastrar Horas', ["action" => "cadhoras", $ticket->id], ['class' => 'btn btn-orange text-white m-r-5']); 
									if($ticket->situacao == C_TicketSituacaoResolvido) echo $this->Html->link('Enviar e-mail', ["action" => "email", $ticket->id, null, 'redirect'], ['class' => 'btn btn-email btn-purple text-white m-r-5']); 
									if(!$ordem) echo $this->Html->link('Gerar Ordem de Serviço', ["controller" => "ordensservico", "action" => "ticketordem", $ticket->id], ['class' => 'btn btn-warning text-white m-r-5', 'target' => '_blank']);
									else echo $this->Html->link("Ordem nº $ordem gerada", ["controller" => "ordensservico", "action" => "edit", $ordem], ['class' => 'btn btn-warning text-white m-r-5', 'target' => '_blank']);
									echo $this->Html->link("Imprimir", ["controller" => "Tickets", "action" => "imprimir", $ticket->id, "?" => ["autoprint" => 1]], ['class' => 'btn btn-purple text-white m-r-5', 'target' => '_blank']);
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
												<p class="form-control" style="border: none; background: transparent; height: auto;">
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
											<div id="quill-editor" style="height: 200px; margin-bottom: 50px;"></div>
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

						//1 - Respondido
						else if ($reg['sitnova'] == C_TicketSituacaoRespondido && $reg['sitnova'] != $reg['sitantiga']) echo "Publicou um comentário.";

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
							<?= $this->Form->button('Adicionar Anexo', ['id' => 'submitfile', 'class' => 'btn btn-primary', 'style' => 'color:white', 'disabled']) ?>
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
											<td><?= $reg->arquivo ?></td>
											<td class="td-actions">
											<?= $this->Html->link("<i class='fa fa-eye'></i><div class='ripple-container'></div>", ["controller" => "Tickets", "action" => "downloadAnexo", $reg->id], ['rel' => 'tooltip', 'title' => 'Visualizar', 'class' => 'btn btn-info btn-simple btn-xs', 'escape' => false]) ?>
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
						<?= $this->Form->button('Enviar', ['class' => 'btn btn-purple text-white float-right m-l-10']) ?>
						<button type="button" class="btn btn-info btn-add-destinatario waves-effect float-right m-l-10">Adicionar destinatário</button>
						<button type="button" class="btn btn-danger waves-effect float-right" data-dismiss="modal">Fechar</button>
					<?= $this->Form->end(); ?>
				</div>
			</div>
			<div class="modal-footer">
			</div>
		</div>
	</div>
</div>
<script>
	// Cronômetro do timer (reiniciado após swap HTMX no painel esquerdo)
	function initTimerCronometro() {
		var el = document.getElementById('timer-cronometro');
		if (el && el.getAttribute('data-hora-inicio')) {
			var horaInicio = el.getAttribute('data-hora-inicio');
			var inicio = new Date(horaInicio.replace(' ', 'T'));
			function formatar(segundos) {
				var h = Math.floor(segundos / 3600);
				var m = Math.floor((segundos % 3600) / 60);
				var s = segundos % 60;
				return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
			}
			function atualizar() {
				var segundos = Math.max(0, Math.floor((Date.now() - inicio.getTime()) / 1000));
				el.textContent = formatar(segundos);
			}
			atualizar();
			setInterval(atualizar, 1000);
		}
	}
	initTimerCronometro();
	document.body.addEventListener('htmx:afterSwap', function(ev) {
		if (ev.detail.target.id === 'ticket-panel-left') initTimerCronometro();
		// Após adicionar comentário: limpar campo e editor Quill para nova mensagem
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
