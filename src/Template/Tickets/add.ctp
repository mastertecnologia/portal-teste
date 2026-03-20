<?php
	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Abertura', [], ['class' => 'breadcrumb-item active']);
	if($role == 0) $email = null;
?>
<style>
	.tickets-add-wrap {
		max-width: 920px;
		margin: 0 auto;
		padding: 0.5rem 0 2.5rem;
	}
	.card-ticket-add {
		border-radius: 10px;
		border: 1px solid #e8ecef;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
	}
	.card-ticket-add .card-body {
		padding: 1.75rem 2rem 2rem;
	}
	@media (max-width: 767px) {
		.card-ticket-add .card-body {
			padding: 1.25rem 1rem 1.5rem;
		}
	}
	.ticket-add-lead {
		font-size: 14px;
		color: #6c757d;
		margin-bottom: 1.25rem;
		line-height: 1.5;
	}
	.ticket-add-lead strong {
		color: #3d4a54;
	}
	.ticket-add-section {
		margin-top: 1.35rem;
	}
	.ticket-add-section > label.control-label {
		display: block;
		margin-bottom: 0.45rem;
		font-weight: 600;
		color: #495057;
		font-size: 13px;
	}
	.ticket-add-textarea.form-control {
		min-height: 200px;
		border: 1px solid #ced4da;
		border-radius: 8px;
		padding: 14px 16px;
		font-size: 15px;
		line-height: 1.55;
		resize: vertical;
		transition: border-color 0.15s ease, box-shadow 0.15s ease;
	}
	.ticket-add-textarea.form-control:focus {
		border-color: #1ab394;
		box-shadow: 0 0 0 0.2rem rgba(26, 179, 148, 0.18);
		outline: 0;
	}
	.ticket-dropzone {
		border: 2px dashed #cfd8dc;
		border-radius: 10px;
		background: #f8fafb;
		min-height: 120px;
		padding: 1rem 1.25rem;
		display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;
		transition: border-color 0.2s, background 0.2s;
	}
	.ticket-dropzone:hover {
		border-color: #1ab394;
		background: #f0fdf9;
	}
	.file-drop-area {
		position: relative;
		width: 100%;
		min-height: 72px;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0;
		border: none;
	}
	.fake-btn {
		flex-shrink: 0;
		border-radius: 6px;
		padding: 10px 14px;
		font-size: 13px;
		font-weight: 500;
		line-height: 1.4;
		white-space: normal;
		text-align: center;
		color: #495057;
		pointer-events: none;
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
	.ticket-add-email.form-control {
		border-radius: 8px;
		padding: 10px 14px;
		font-size: 15px;
		min-height: 44px;
	}
	.ticket-add-actions {
		margin-top: 1.75rem;
		padding-top: 1.25rem;
		border-top: 1px solid #eef1f3;
	}
	.ticket-add-actions .btn-abrirticket {
		min-width: 200px;
		padding: 12px 28px;
		font-size: 15px;
		font-weight: 600;
		border-radius: 8px;
	}
	@media (max-width: 767px) {
		.ticket-add-actions .btn-abrirticket {
			width: 100%;
			min-width: 0;
		}
	}
	select[multiple] {
		max-width: 100%;
	}
</style>
<div class="col-md-12 tickets-add-wrap">
	<div class="card card-ticket-add">
		<div class="card-body">
			<?php if ($role == 1 && !empty($authUserName)) : ?>
				<p class="ticket-add-lead">
					O chamado será registrado em nome de <strong><?= h($authUserName) ?></strong>.
				</p>
			<?php endif; ?>
			<?= $this->Form->create($ticket, ['enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material ticket-add-form']) ?>
				<div class="row">
					<?php if($role == 0) { ?>
						<div class="col-md-4 col-xs-12">
							<label class="control-label text-muted">Cliente</label>
							<?= $this->Form->control('idcliente', [ 'class' => 'selectpicker form-control', 'data-live-search' => true, 'empty' => 'Selecione o cliente', 'options' => $clientes, 'label' => false, 'required' => true]) ?>
						</div>
						<div class="col-md-2 col-xs-12">
							<label class="control-label text-muted">Solicitante</label>
							<?= $this->Form->control('idsolicitante', ['class' => 'selectpicker form-control', 'title' => 'Solicitante (opcional)', 'data-live-search' => true, 'options' => '', 'label' => false, 'required' => false]) ?>
						</div>
						<div class="col-md-2 col-xs-12">
							<label class="control-label text-muted"></label>
							<?= $this->Form->control('nomesolicitante', ['class' => 'form-control m-t-5', 'title' => 'Nome do solicitante', 'label' => false, 'required' => false, 'placeholder' => 'Solicitante (caso não cadastrado)']) ?>
						</div>
						<div class="col-md-3 col-xs-12">
							<label class="control-label text-muted">Assunto</label>
							<?= $this->Form->control('assunto', ['value' => $assunto, 'class' => 'selectpicker form-control', 'title' => 'Escolha um assunto', 'data-live-search' => true, 'options' => C_TicketCategoriaClienteQuery, 'label' => false, 'required' => true]) ?>
						</div>
					<?php } else { ?>
						<div class="col-12 ticket-add-section">
							<label class="control-label text-muted">Assunto</label>
							<?= $this->Form->control('assunto', ['value' => $assunto, 'class' => 'selectpicker form-control', 'title' => 'Escolha um assunto', 'data-live-search' => true, 'options' => C_TicketCategoriaClienteQuery, 'label' => false, 'required' => true]) ?>
						</div>
					<?php } ?>
				</div>
				<div class="row hide data m-t-10">
					<div class="col-4">
						<label class="control-label text-muted">Data da Visita</label>
						<?= $this->Form->text('data', ['class' => 'form-control datepicker', 'label' => false,]) ?>
					</div>
				</div>
				<div class="row ticket-add-section">
					<div class="col-12">
						<label class="control-label text-muted" for="solicitacao">Solicitação</label>
						<?= $this->Form->textarea('solicitacao', [
							'id' => 'solicitacao',
							'class' => 'form-control ticket-add-textarea',
							'label' => false,
							'required' => true,
							'placeholder' => 'Descreva o problema ou a solicitação com o máximo de detalhes possível (passos, mensagens de erro, telas afetadas, etc.).',
						]) ?>
					</div>
				</div>
				<div class="row ticket-add-section">
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted">Anexos (opcional)</label>
							<div class="ticket-dropzone">
								<div class="file-drop-area">
									<span class="fake-btn text-muted">Escolha arquivo(s) ou arraste para esta área</span>
									<input class="file-input form-control" name="file-3[]" id="file-3" type="file" multiple>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 ticket-add-section">
						<label class="control-label text-muted" for="email">E-mail para contato</label>
						<?= $this->Form->email('email', ['value' => $email, 'type' => 'text', 'id' => 'email', 'class' => 'email form-control ticket-add-email', 'label' => false, 'placeholder' => 'E-mail para retorno do suporte']) ?>
					</div>
				</div>
				<div class="row ticket-add-actions">
					<div class="col-12 text-right">
						<?= $this->Form->button('Abrir Ticket', ['id' => 'abrirticket', 'class' => 'btn btn-success aparecedepois btn-abrirticket']) ?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<script>
	// Idcliente
		<?php if(isset($idcliente)) { ?>
			$(document).ready(function() {
				var idcliente = <?= $idcliente ?>;
			});
		<?php } ?>
	// File input
		$(document).on('change', '.file-input', function() {
			var filesCount = $(this)[0].files.length;
			var $textContainer = $(this).prev();
			var fileName = $(this).val().split('\\').pop();
			if (filesCount === 1) {
				var fileName = $(this).val().split('\\').pop();
				$textContainer.text(fileName);
			} else  $textContainer.text(filesCount + ' arquivos selecionados');
		});
	// Solicitantes
		function loadSolicitantes(idcliente) {
			$.ajax({
				dataType: "json",
				url: "<?= Router::url(['controller'=>'Clientes','action'=>'solicitantes']);?>/" + idcliente,
				success: function(data){
					$('#idsolicitante').find('option').remove().end();
					$('#idsolicitante').append("<option value='' selected>Indefinido</option>");
					$.each(data, function(key, array) {
						$('#idsolicitante').append($('<option>', {
							value: key,
							text: array
						}));
					})
					$('#idsolicitante').selectpicker("refresh");
				},
				error: function (tab) { $('#idsolicitante').append("<option value='' selected>Indefinido</option>"); }
			});
		}

		<?php if($role == 0){ ?>
			function loadEmail(idcliente) {
				$.ajax({
					dataType: "json",
					url: "<?= Router::url(['controller'=>'Clientes','action'=>'cliemail']);?>/" + idcliente,
					success: function(data){ $('.email').val(data.email); },
				});
			}
		<?php } ?>

		$("#idcliente").change(function() {
			var idcliente = $(this).val();
			loadSolicitantes(idcliente);
			loadEmail(idcliente);
		});

		// Só busca a lista de Solicitantes e Contadores se houver um cliente selecionado
		if ($("#idcliente").val() != '' && $("#idcliente").val() != null) {
			loadSolicitantes($("#idcliente").val());
			loadEmail($("#idcliente").val());
		}

	// Email
		<?php if(isset($email)) { ?>
			$('#email').typeAhead({ source: ['<?= $email ?>'], scope: this });
			$('#email').focus();
		<?php } ?>

	// Double submit
		jQuery.fn.preventDoubleSubmission = function() {
			$(this).on('submit',function(e){
				var $form = $(this);
				if ($form.data('submitted') === true) e.preventDefault();
				else $form.data('submitted', true);
			});

			return this;
		};

		$('form').preventDoubleSubmission();

		$('form').submit(function (e) {
			var botao = $(this);
			if (botao.hasClass('disabled')) {
				setTimeout(function(){
					botao.removeClass('disabled');
				}, 2000);
				return false; // Do something else in here if required
			}
			else window.location.href = $('form').submit();
			botao.addClass('disabled');
		});


	// Somente número
		function SomenteNumero(e){
			var tecla=(window.event)?event.keyCode:e.which;
			if((tecla>47 && tecla<58)) return true;
			else if (tecla==8 || tecla==0) return true;
			else if (tecla == 46)  return true;
			else if( $('#valor').val().indexOf(',') > -1 && tecla == 44 ) return false
			else if( $('#valor').val().indexOf(',') <= -1 && tecla == 44 ) return true
			else  return false;
		}
	// Assunto
		$('#assunto').change(function(){
			if($(this).val() == 5) $('.data').show();
			else $('.data').hide();
		});
	//
</script>
