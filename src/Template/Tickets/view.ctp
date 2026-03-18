<?php

use Cake\Routing\Router;

$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => 'indexcliente'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);
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

	.comment-text p {
		overflow: auto !important;
		max-height: none !important;
	}
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<ul class="nav nav-tabs customtab m-b-20" role="tablist">
				<li class="nav-item"> <a class="nav-link active " data-toggle="tab" href="#ticket" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="icon-doc"></i></span> <span class="hidden-xs-down">Ticket</span></a> </li>
				<?php if ($role == 0 || $role == 1 && isset($bMovCancelada)) { ?> <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#movimentacoes" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-reload"></i></span> <span class="hidden-xs-down">Movimentações <?php if ($role == 0) { ?>(<?= count($ticketsmovs) ?>) <?php } ?> </span></a> </li> <?php } ?>
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#arquivos" role="tab" aria-selected="false">
						<span class="hidden-sm-up"><i class="ti-clip"></i></span>
						<span class="hidden-xs-down">Anexos (<?= count($ticketanexos) ?>)</span>
					</a>
				</li>
				<?php if ($role == 0) { ?>
					<li class="nav-item">
						<a class="nav-link" data-toggle="tab" href="#horas" role="tab" aria-selected="false">
							<span class="hidden-sm-up"><i class="ti-time"></i></span>
							<span class="hidden-xs-down">Horas Cadastradas (<?= count($ticketshoras) ?>)</span>
						</a>
					</li>
				<?php } ?>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="ticket">
					<div class="row">
						<!-- Parte da Esquerda -->
						<div class="col-4 bg-secondary">
							<br>
							<h5 class="text-muted">Autor</h5>
							<div class="message-box bg-white">
								<div class="message-widget message-scroll">
									<a href="javascript:void(0)">
										<div class="mail-contnet">
											<h5><?= h($ticket['users']['name']) ?></h5>
										</div>
									</a>
								</div>
							</div>
							<br>
							<h5><span class="text-muted">Cliente: </span> <?= h($cliente) ?></h5>
							<h5><span class="text-muted">Solicitante: </span><?= !empty($solicitante) ? h($solicitante) : "Indefinido"; ?></h5>
							<h5><span class="text-muted">E-mail: </span><?= !empty($ticket->email) ? h($ticket->email) : "Indefinido"; ?></h5>
							<br>
							<h5><span class="text-muted">Assunto: </span><?= AssuntoTicket($ticket->assunto) ?></h5>
							<h5><span class="text-muted">Status: </span><?= SituacaoTicket($ticket->situacao) ?></h5>
							<br>
							<?php
							$canCancel =
								in_array($ticket->situacao, [C_TicketSituacaoEmandamento, C_TicketSituacaoPendente])
								&& ($permissaoacesso || $ticket->idautor == $iduser || $admin);
							if ($canCancel) {
							?>
								<button type="button"
									class="btn btn-danger btn-simple btn-sm m-b-20 m-r-5"
									data-toggle="modal"
									data-target="#modal-cancel-ticket">
									<span> Cancelar </span> <i class="fa fa-times"></i>
								</button>
							<?php } ?>
							?>
						</div>
						<!-- Fim da Parte da Esquerda -->
						<!-- Parte da Direita -->
						<div class="col-8">
							<p class='h3'><?= AssuntoTicket($ticket->assunto) ?> <small class="h5 text-muted"> <?= date_format($ticket->created, 'd/m/Y') ?> </small></p>
							<div class='form-material'>
								<br>
								<p class='h5'> Descrição: </p>
								<div class="row">
									<div class="col-lg-12">
										<?= $this->Form->textarea('solicitacao', ['class' => 'aparecedepois form-control', 'label' => false, 'value' => $ticket->solicitacao, 'placeholder' => '', 'disabled' => true]) ?>
									</div>
								</div>
							</div>
							<?= $this->Form->end(); ?>
							<br>
							<?php if ($ticket->assunto == C_TicketCategoriaVisita) { ?>
								<p class='h6'> Data solicitada para a visita: <b> <?= date_format($ticket->data, 'd/m/Y') ?> </b> </p>
							<?php } ?>
							<?php if ($podecomentar == true) { ?>
								<p class='h5'> Adicionar comentário: </p>
								<?= $this->Form->create(null, ['url' => ['controller' => 'Ticketcomentarios', 'action' => 'add', $ticket->id, 'view'], 'class' => 'form-material']); ?>
								<div class="row">
									<div class="col-lg-12">
										<?= $this->Form->textarea('comentario', ['id' => 'comentario', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => '']) ?>
									</div>
								</div>
								<?= $this->Form->button('Comentar', ['id' => 'submitcomentario', 'class' => 'btn btn-secondary btn-simple btn-sm m-r-5']) ?>
								<?= $this->Form->end(); ?>
							<?php } ?>
							<br />
							<?= $this->Form->create(null, ['url' => ['controller' => 'Ticketsanexos', 'action' => 'add', $ticket->id], 'enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material']) ?>
							<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
								<div class="form-group">
									<p class='h5'> Adicionar anexo (enviar separadamente do comentário): </p>
									<div class="bg">
										<div class="file-drop-area">
											<span class="fake-btn text-muted">Insira ou arraste seus anexos aqui</span>
											<input multiple class="file-input form-control" name="file-3[]" id="file-3" type="file">
										</div>
									</div>
								</div>
							</div>
							<?= $this->Form->button('Enviar arquivos anexados acima', ['id' => 'submitfile', 'class' => 'btn btn-primary', 'style' => 'color:white', 'disabled']) ?>
							<br>
							<div class="clearfix"></div>
							<?= $this->Form->end(); ?>

							<hr>
							<!-- Comentários -->
							<div class="container">
								<div class="content">
									<div class="comment-widgets m-b-20">
										<?php foreach (array_reverse($ticketcomentarios) as $comentario) { ?>
											<!-- Comment Row -->
											<div class="d-flex flex-row comment-row">
												<div class="p-2">
													<span class="round">
														<?php
														// Cliente
														if ($comentario['Users']['role'] == 1) $imagem = "cliente.png";
														else $imagem = "tecnico.png";
														?>
														<img src="<?= $this->request->getAttribute('webroot') . 'assets/images/' . $imagem ?>" alt="user" width="50">
													</span>
												</div>
												<div class="comment-text">
													<h5><?= h($comentario['Users']['name']) ?></h5>
													<div class="comment-footer">
														<span class="date"> <?= date_format($comentario->created, 'd/m/Y') ?>, <?= date_format($comentario->created, 'H:i') ?> </span>
													</div>
													<p class="m-b-5 m-t-10"><?= h($comentario->comentario) ?></p>
												</div>
											</div>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
						<!-- Fim da Parte da Direita -->
					</div>
				</div>
				<?php if ($role == 0 || $role == 1 && isset($bMovCancelada)) { ?>
					<div class="tab-pane" id="movimentacoes">
						<?php
						foreach (array_reverse($ticketsmovs) as $reg):
							if ($role == 0) {
								$data = $reg['datetime'];
								echo "<br><div class='col-lg-12'><strong>" . h($reg['user']->name) . "</strong>";
								echo " - " . $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y') . " às " . $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('H:i') . "<br>";

								echo "<p>";

								if ($reg['sitantiga'] == C_TicketSituacaoPendente && $reg['sitnova'] == 0) echo "Abriu o ticket.";

								else if ($reg['sitnova'] == C_TicketSituacaoPendente && $reg['sitantiga'] == C_TicketSituacaoFechado) echo "Reabriu o ticket'.";

								//0 - Pendente
								else if ($reg['sitnova'] == C_TicketSituacaoPendente && $reg['sitnova'] != $reg['sitantiga']) echo "Alterou a situação do ticket para 'Pendente'.";

								//1 - Respondido
								else if ($reg['sitnova'] == C_TicketSituacaoRespondido && $reg['sitnova'] != $reg['sitantiga']) echo "Publicou um comentário.";

								//2 - Resolvido
								else if ($reg['sitnova'] == C_TicketSituacaoResolvido && $reg['sitnova'] != $reg['sitantiga'] && empty($reg['observacao'])) echo "Resolveu o ticket.";
								//2 - Resolvido pela OS
								else if ($reg['sitnova'] == C_TicketSituacaoResolvido && $reg['sitnova'] != $reg['sitantiga']) echo h($reg['observacao']);

								//3 - Em andamento
								else if ($reg['sitnova'] == C_TicketSituacaoEmandamento && $reg['sitnova'] != $reg['sitantiga']) echo "Alterou a situação do ticket para 'Em andamento'.";

								//4 - Cancelar
								else if ($reg['sitnova'] == C_TicketSituacaoFechado && $reg['sitnova'] != $reg['sitantiga']) echo "Cancelou o ticket. <br> Motivo: " . h($reg['observacao']);
								//4 - Cancelar
								else if ($reg['sitnova'] == C_TicketSituacaoFechado && $reg['sitnova'] != $reg['sitantiga']) echo h($reg['observacao']);

								//5 - Anexo Adicionado
								if ($reg['sitnova'] == C_TicketAnexoAdicionado) echo "O anexo '" . h($reg['observacao']) . "' foi adicionado.";

								//6 - Anexo Deletado
								if ($reg['sitnova'] == C_TicketAnexoDeletado) echo "O anexo '" . h($reg['observacao']) . "' foi deletado.";
							} else {
								$data = $reg['datetime'];
								if ($reg['sitnova'] == C_TicketSituacaoFechado && $reg['sitnova'] != $reg['sitantiga']) {
									echo "<br><div class='col-lg-12'><strong>" . h($reg['user']->name) . "</strong>";
									echo " - " . $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y') . " às " . $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('H:i') . "<br>";
									echo "<p>";
									echo "Cancelou o ticket. <br> Motivo: " . h($reg['observacao']);
								}
							}
							echo "</p></div>";
						endforeach;
						?>
					</div>
				<?php } ?>
				<div class="tab-pane" id="arquivos">
					<div class="card">
						<div class="table-responsive">
							<table class="table table-hover" id="tableLicencas">
								<thead class="text-primary">
									<th>Arquivo</th>
									<th width="10%">Download</th>
								</thead>
								<tbody>
									<?php foreach ($ticketanexos as $reg) { ?>
										<tr>
											<td><?= h($reg->arquivo) ?></td>
											<td class="td-actions">
												<?= $this->Html->link("<i class='fa fa-eye'></i><div class='ripple-container'></div>", ["controller" => "Tickets", "action" => "downloadAnexo", $reg->id], ['rel' => 'tooltip', 'title' => 'Visualizar', 'class' => 'btn btn-info btn-simple btn-xs', 'escape' => false]) ?>
											</td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<?php if ($role == 0) { ?>
					<div class="tab-pane" id="horas">
						<?php if (sizeof($ticketshoras) > 0) { ?>
							<div class="table-responsive">
								<table class="table table-hover" id="tableProgramas">
									<thead class="text-primary">
										<th> Usuário </th>
										<th> Data </th>
										<th> Horário </th>
										<?php if ($admin) { ?> <th width='10%'> Ações </th> <?php } ?>
									</thead>
									<tbody>
										<?php foreach ($ticketshoras as $reg): ?>
											<tr>
												<td> <?= h($reg->user->username) ?> </td>
												<td> <?= date_format($reg->data, 'd/m/Y'); ?> </td>
												<td> <?= date_format($reg->horaini, 'H:i') . " - " . date_format($reg->horafin, 'H:i'); ?> </td>
												<?php if ($admin) { ?>
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
				<?php } ?>
			</div>
		</div>
	</div>
</div>
<!-- Modal: cancelar ticket (cliente) -->
<?php if (!empty($canCancel)) { ?>
	<div class="modal fade" id="modal-cancel-ticket" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title m-0">Motivo do cancelamento</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<?= $this->Form->create(null, ['url' => ['action' => 'cancelar', $ticket->id], 'type' => 'post', 'id' => 'form-cancel-ticket']) ?>
						<div class="form-group">
							<label for="observacao" class="control-label text-muted">Qual o motivo do cancelamento?</label>
							<?= $this->Form->textarea('observacao', [
								'id' => 'observacao',
								'class' => 'form-control',
								'label' => false,
								'rows' => 4,
								'placeholder' => 'Descreva o motivo...',
								'required' => true
							]) ?>
						</div>
						<?= $this->Form->button('Confirmar cancelamento', ['class' => 'btn btn-danger']) ?>
						<button type="button" class="btn btn-outline-secondary m-l-10" data-dismiss="modal">Voltar</button>
					<?= $this->Form->end() ?>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
<div class="modal fade none-border" id="homologacao">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">

		</div>
	</div>
</div>
<script>
	$('#comentario').change(function(e) {
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

	$('.banco').click(function(e) {
		e.preventDefault();
		var url = "<?= Router::url(array('controller' => 'Tickets', 'action' => 'viewhomologacoes')); ?>";
		url = url + '/' + <?= $ticket->id ?>;
		$.ajax({
			type: "get",
			url: url,
			success: function(data) {
				console.log(data);
				$('.modal-content').html(data);
				$('.selectpicker').selectpicker();
				$('#homologacao').modal('toggle');
			},
			error: function(tab) {}
		});
	});

	$('.btn-resolver').click(function(e) {
		id = $(this).attr('id');
		e.preventDefault();
		var url = "<?= Router::url(array('controller' => 'Tickets', 'action' => 'poderesolver')); ?>";
		url = url + '/' + id;
		$.ajax({
			type: "get",
			url: url,
			success: function(data) {
				console.log(data)
				if (data == 'poderesolver') {
					var url = "<?= Router::url(array('controller' => 'Ticketsusers', 'action' => 'resolver')); ?>";
					window.location.href = url + '/' + id;
				} else {
					bootbox.dialog({
						message: '<p class="titulomodal text-center"> Você já está resolvendo outro Ticket!</p> <p class="text-center">Para realizar essa ação é necessário resolver ou pausar o ticket nº ' + data + '.',
						buttons: {
							ver: {
								label: 'Ver',
								className: 'btn-success',
								callback: function() {
									var url = "<?= Router::url(array('controller' => 'Tickets', 'action' => 'edit')); ?>";
									window.open(url + '/' + data);
								}
							},
							pausar: {
								label: 'Pausar',
								className: 'btn-danger',
								callback: function() {
									var url = "<?= Router::url(array('controller' => 'Tickets', 'action' => 'pausar')); ?>";
									window.open(url + '/' + data);
								}
							},
						},
					});
				}
			},
		});
	})

	$('.faturamento').click(function(e) {
		e.preventDefault();
		var url = "<?= Router::url(array('controller' => 'Tickets', 'action' => 'viewfaturas')); ?>";
		url = url + '/' + <?= $ticket->id ?>;
		$.ajax({
			type: "get",
			url: url,
			success: function(data) {
				$('.modal-content').html(data);
				$('.selectpicker').selectpicker();
				$('#homologacao').modal('toggle');
			},
			error: function(tab) {}
		});
	});

	$('.cancelamento').click(function(e) {
		e.preventDefault();
		var url = "<?= Router::url(array('controller' => 'Tickets', 'action' => 'cancelamentoview')); ?>";
		url = url + '/' + <?= $ticket->id ?>;
		$.ajax({
			type: "get",
			url: url,
			success: function(data) {
				$('.modal-content').html(data);
				$('.selectpicker').selectpicker();
				$('#homologacao').modal('toggle');
			},
			error: function(tab) {}
		});
	});

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
</script>