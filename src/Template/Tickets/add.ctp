<?php
	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Abertura', [], ['class' => 'breadcrumb-item active']);
	if($role == 0) $email = null;
?>
<style>
	.tickets-add-wrap {
		width: 100%;
		max-width: none;
		margin: 0;
		padding: 0.5rem 0 2.5rem;
		box-sizing: border-box;
	}
	.card-ticket-add {
		border-radius: 10px;
		border: 1px solid #e8ecef;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
	}
	.card-ticket-add .card-body {
		padding: 1.75rem 2rem 2rem;
	}
	@media (min-width: 1200px) {
		.card-ticket-add .card-body {
			padding: 2rem clamp(1.5rem, 3vw, 3rem) 2.25rem;
		}
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
		min-height: 240px;
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
	.ticket-add-hint {
		font-size: 13px;
		color: #6c757d;
		margin: 0 0 0.65rem;
		line-height: 1.45;
	}
	.ticket-dropzone {
		border: 2px dashed #cfd8dc;
		border-radius: 10px;
		background: #f8fafb;
		min-height: 100px;
		padding: 1rem 1.25rem;
		display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;
		transition: border-color 0.2s, background 0.2s;
	}
	.ticket-dropzone.ticket-dropzone--drag {
		border-color: #1ab394;
		background: #e8faf5;
	}
	.ticket-dropzone:hover {
		border-color: #1ab394;
		background: #f0fdf9;
	}
	.ticket-files-chosen {
		list-style: none;
		margin: 0.75rem 0 0;
		padding: 0;
		font-size: 13px;
		color: #3d4a54;
	}
	.ticket-files-chosen li {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		padding: 6px 10px;
		margin-bottom: 4px;
		background: #f1f5f7;
		border-radius: 6px;
		border: 1px solid #e2e8ec;
	}
	.ticket-files-chosen .ticket-file-remove {
		flex-shrink: 0;
		border: none;
		background: transparent;
		color: #c0392b;
		font-size: 12px;
		cursor: pointer;
		text-decoration: underline;
		padding: 0 4px;
	}
	.ticket-files-chosen .ticket-file-remove:hover {
		color: #922b21;
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
					<div class="col-xl-8 col-lg-7 col-md-12">
						<div class="form-group">
							<label class="control-label text-muted">Anexos (opcional)</label>
							<p class="ticket-add-hint">
								Vários arquivos: na janela de escolha, use <strong>Ctrl+clique</strong> (Windows) ou <strong>Cmd+clique</strong> (Mac) para marcar mais de um.
								Ou clique várias vezes em “Adicionar arquivos” para ir incluindo mais. Também pode arrastar e soltar aqui.
							</p>
							<div class="ticket-dropzone" id="ticket-dropzone">
								<div class="file-drop-area">
									<span class="fake-btn text-muted" id="ticket-file-hint">Adicionar arquivos ou arrastar para cá</span>
									<input class="file-input form-control" name="file-3[]" id="file-3" type="file" multiple>
								</div>
							</div>
							<ul class="ticket-files-chosen" id="ticket-attachments-list" aria-live="polite"></ul>
						</div>
					</div>
					<div class="col-xl-4 col-lg-5 col-md-12 ticket-add-section">
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
	// Vários anexos: lista em memória (fileStore) + DataTransfer só para enviar — evita bug de remover 1 e sumir todos
		(function () {
			var $inp = $('#file-3');
			var $list = $('#ticket-attachments-list');
			var $hint = $('#ticket-file-hint');
			var $zone = $('#ticket-dropzone');
			if (!$inp.length) return;

			var fileStore = [];

			function filesToInput() {
				var dt = new DataTransfer();
				for (var i = 0; i < fileStore.length; i++) {
					dt.items.add(fileStore[i]);
				}
				return dt.files;
			}

			function syncInput() {
				try {
					$inp[0].files = filesToInput();
				} catch (e) {}
			}

			function renderList() {
				$list.empty();
				var n = fileStore.length;
				if (n === 0) {
					$hint.text('Adicionar arquivos ou arrastar para cá');
					return;
				}
				$hint.text(n === 1 ? '1 arquivo selecionado — adicione mais se precisar' : n + ' arquivos — pode adicionar mais');
				for (var i = 0; i < n; i++) {
					(function (idx) {
						var name = fileStore[idx].name;
						var $li = $('<li/>');
						$li.append($('<span/>').text(name).css({overflow: 'hidden', 'text-overflow': 'ellipsis', 'white-space': 'nowrap'}));
						var $rm = $('<button type="button" class="ticket-file-remove"/>').text('Remover');
						$rm.on('click', function () {
							fileStore.splice(idx, 1);
							syncInput();
							renderList();
						});
						$li.append($rm);
						$list.append($li);
					})(i);
				}
			}

			function addFileList(fileList) {
				if (!fileList || !fileList.length) return;
				for (var i = 0; i < fileList.length; i++) {
					fileStore.push(fileList[i]);
				}
				syncInput();
				renderList();
			}

			$inp.on('change', function () {
				addFileList(this.files);
				this.value = '';
			});

			$zone.on('dragover', function (e) {
				e.preventDefault();
				e.stopPropagation();
				$zone.addClass('ticket-dropzone--drag');
			});
			$zone.on('dragleave drop', function (e) {
				e.preventDefault();
				e.stopPropagation();
				$zone.removeClass('ticket-dropzone--drag');
			});
			$zone.on('drop', function (e) {
				var ev = e.originalEvent;
				if (ev.dataTransfer && ev.dataTransfer.files && ev.dataTransfer.files.length) {
					addFileList(ev.dataTransfer.files);
				}
			});
		})();
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
