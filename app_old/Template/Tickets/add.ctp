<?php
	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Abertura', [], ['class' => 'breadcrumb-item active']);
	if($role == 0) $email = null; 
?>
<style>
	#canvas{
		width: 500px;
		height: 500px;
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

	select[multiple]{
		width: 400px;
	}

	.homologacoes{
		border:solid 1px #757575;
		box-shadow: 1px 1px 1px 0.5px #c9c9c9;
	}
</style>
<div class="col-md-12">
	<div class="card" >
		<div class="card-body">
			<?= $this->Form->create($ticket, ['enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material']) ?>
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
						<div class="col-12">
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
				<div class="row m-t-10">
					<div class="col-12">
						<label class="control-label text-muted">Solicitação</label>
						<?= $this->Form->textarea('solicitacao', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' =>'']) ?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted">Adicionar Anexo</label>
							<div class="bg">
								<div class="file-drop-area">
									<span class="fake-btn text-muted">Escolha o(s) arquivo(s) ou arraste-o(s) aqui</span>
									<input class="file-input form-control" name="file-3[]" id="file-3" type="file" multiple>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
						<label class="control-label text-muted">E-mail para contato:</label>
						<?= $this->Form->email('email', ['value' => $email, 'type' => 'text', 'class' => 'email form-control', 'label' => false, 'placeholder' =>'Insira seu e-mail']) ?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-12">
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