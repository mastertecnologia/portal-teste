<?php
	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Abertura', [], ['class' => 'breadcrumb-item active']);
	if($role == 0) $email = null;
?>
<style>
	/* Integra ao fundo do portal: faixa única branca, sem “card” flutuante */
	.tickets-add-bleed {
		max-width: none;
		margin-left: -15px;
		margin-right: -15px;
		margin-top: -4px;
		padding: 1.25rem 15px 2.5rem;
		background: #fff;
		border-top: 1px solid rgba(0, 0, 0, 0.06);
		box-sizing: border-box;
	}
	@media (min-width: 768px) {
		.tickets-add-bleed {
			margin-left: -20px;
			margin-right: -20px;
			padding-left: 20px;
			padding-right: 20px;
		}
	}
	.ticket-add-shell {
		max-width: 960px;
		margin: 0 auto;
	}
	.ticket-add-shell .ticket-add-title {
		font-size: 1.05rem;
		font-weight: 600;
		color: #2c3e50;
		margin: 0 0 0.35rem;
		letter-spacing: -0.02em;
	}
	.ticket-add-lead {
		font-size: 14px;
		color: #6c757d;
		margin-bottom: 1.35rem;
		line-height: 1.5;
		padding-bottom: 1rem;
		border-bottom: 1px solid #eef1f4;
	}
	.ticket-add-lead strong {
		color: #1ab394;
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
		border: 1px solid #d8dee4;
		border-radius: 6px;
		padding: 14px 16px;
		font-size: 15px;
		line-height: 1.55;
		resize: vertical;
		transition: border-color 0.15s ease, box-shadow 0.15s ease;
		background: #fafbfc;
	}
	.ticket-add-textarea.form-control:focus {
		background: #fff;
		border-color: #1ab394;
		box-shadow: 0 0 0 3px rgba(26, 179, 148, 0.15);
		outline: 0;
	}
	.ticket-add-hint {
		font-size: 13px;
		color: #6c757d;
		margin: 0 0 0.65rem;
		line-height: 1.45;
	}
	.ticket-dropzone {
		border: 1px dashed #b8c5ce;
		border-radius: 8px;
		background: #f4f7f9;
		min-height: 96px;
		padding: 1rem 1.25rem;
		display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;
		transition: border-color 0.2s, background 0.2s;
	}
	.ticket-dropzone.ticket-dropzone--drag {
		border-color: #1ab394;
		background: #e8f8f4;
		border-style: solid;
	}
	.ticket-dropzone:hover {
		border-color: #1ab394;
		background: #eefaf7;
	}
	.ticket-files-chosen {
		list-style: none;
		margin: 0.75rem 0 0;
		padding: 0;
		font-size: 13px;
		color: #2c3e50;
	}
	.ticket-files-chosen li {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
		padding: 8px 12px;
		margin-bottom: 6px;
		background: #fff;
		border-radius: 6px;
		border: 1px solid #e1e8ed;
	}
	.ticket-files-chosen .ticket-file-name {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		min-width: 0;
		flex: 1;
	}
	.ticket-files-chosen .ticket-file-remove {
		flex-shrink: 0;
		border: 1px solid #e74c3c;
		background: #fff;
		color: #c0392b;
		font-size: 12px;
		font-weight: 600;
		cursor: pointer;
		padding: 4px 10px;
		border-radius: 4px;
	}
	.ticket-files-chosen .ticket-file-remove:hover {
		background: #fde8e6;
		color: #922b21;
	}
	.file-drop-area {
		position: relative;
		width: 100%;
		min-height: 64px;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0;
		border: none;
	}
	.fake-btn {
		flex-shrink: 0;
		border-radius: 6px;
		padding: 8px 12px;
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
		border-radius: 6px;
		padding: 10px 14px;
		font-size: 15px;
		min-height: 44px;
		border: 1px solid #d8dee4;
		background: #fafbfc;
	}
	.ticket-add-email.form-control:focus {
		background: #fff;
		border-color: #1ab394;
		box-shadow: 0 0 0 3px rgba(26, 179, 148, 0.12);
	}
	.ticket-add-actions {
		margin-top: 1.75rem;
		padding-top: 1.25rem;
		border-top: 1px solid #eef1f4;
	}
	.ticket-add-actions .btn-abrirticket {
		min-width: 220px;
		padding: 12px 28px;
		font-size: 15px;
		font-weight: 600;
		border-radius: 6px;
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
<div class="col-md-12 tickets-add-bleed">
	<div class="ticket-add-shell">
		<h2 class="ticket-add-title">Novo chamado</h2>
		<?php if ($role == 1 && !empty($authUserName)) : ?>
			<p class="ticket-add-lead">
				Será registrado em nome de <strong><?= h($authUserName) ?></strong>.
			</p>
		<?php else : ?>
			<p class="ticket-add-lead text-muted" style="border: none; padding-bottom: 0; margin-bottom: 0.5rem;">
				Preencha os dados abaixo para abrir o ticket.
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
				<div class="col-12">
					<label class="control-label text-muted">Anexos (opcional)</label>
					<p class="ticket-add-hint">
						Vários arquivos: na janela use <strong>Ctrl+clique</strong> (Windows) ou <strong>Cmd+clique</strong> (Mac), ou clique de novo em “Escolher arquivos” para acrescentar mais.
						Também pode arrastar e soltar na área abaixo. Cada linha tem <strong>Remover</strong> só daquele arquivo.
					</p>
					<div class="ticket-dropzone" id="ticket-dropzone">
						<div class="file-drop-area">
							<span class="fake-btn text-muted" id="ticket-file-hint">Escolher arquivos ou arrastar para cá</span>
							<input class="file-input form-control" name="file-3[]" id="file-3" type="file" multiple>
						</div>
					</div>
					<ul class="ticket-files-chosen" id="ticket-attachments-list" aria-live="polite"></ul>
				</div>
			</div>
			<div class="row ticket-add-section">
				<div class="col-12">
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
<script>
	// Idcliente
		<?php if(isset($idcliente)) { ?>
			$(document).ready(function() {
				var idcliente = <?= $idcliente ?>;
			});
		<?php } ?>
	// Anexos: array em memória + DataTransfer só na sincronização (remove um arquivo por vez de forma confiável)
		(function () {
			var $inp = $('#file-3');
			var $list = $('#ticket-attachments-list');
			var $hint = $('#ticket-file-hint');
			var $zone = $('#ticket-dropzone');
			if (!$inp.length) return;

			var fileStore = [];

			function rebuildInputFromStore() {
				var d = new DataTransfer();
				fileStore.forEach(function (file) {
					try {
						d.items.add(file);
					} catch (err) { /* ignore arquivo inválido */ }
				});
				try {
					$inp[0].files = d.files;
				} catch (e) { /* Safari antigo etc. */ }
			}

			function renderList() {
				$list.empty();
				var n = fileStore.length;
				if (n === 0) {
					$hint.text('Escolher arquivos ou arrastar para cá');
					return;
				}
				$hint.text(n === 1 ? '1 arquivo selecionado' : n + ' arquivos selecionados');
				fileStore.forEach(function (file, idx) {
					var $li = $('<li/>');
					$li.append($('<span class="ticket-file-name"/>').text(file.name));
					var $rm = $('<button type="button" class="ticket-file-remove"/>')
						.attr('data-idx', String(idx))
						.text('Remover');
					$li.append($rm);
					$list.append($li);
				});
			}

			$list.on('click', '.ticket-file-remove', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var idx = parseInt($(this).attr('data-idx'), 10);
				if (isNaN(idx) || idx < 0 || idx >= fileStore.length) return;
				fileStore.splice(idx, 1);
				rebuildInputFromStore();
				renderList();
			});

			function addFileList(fileList) {
				if (!fileList || !fileList.length) return;
				for (var i = 0; i < fileList.length; i++) {
					fileStore.push(fileList[i]);
				}
				rebuildInputFromStore();
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
			$zone.on('dragleave', function (e) {
				e.preventDefault();
				e.stopPropagation();
				$zone.removeClass('ticket-dropzone--drag');
			});
			$zone.on('drop', function (e) {
				e.preventDefault();
				e.stopPropagation();
				$zone.removeClass('ticket-dropzone--drag');
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
				return false;
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
