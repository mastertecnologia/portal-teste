<style>
	.padding-20 {
		padding-left: 20px;
		padding-right: 20px;
	}

	.margin-10-bottom {
		margin-bottom: 10px;
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
</style>
<?php
$this->Breadcrumbs->add('Empresas', ['controller' => 'empresas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Adicionar empresa', [], ['class' => 'breadcrumb-item active']);
$this->Html->css('/dist/css/pages/config-admin-shell.css', ['block' => true]);
?>
<div class="col-md-12">
	<div class="card" >
		<div class="card-body">
			<?= $this->Form->create($empresa, ['class' => 'form-material', 'type' => 'file']) ?>
				<div class="row">
					<div class="col-md-8 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Razão Social</label>
							<?= $this->Form->control('razaosocial', ['class' => 'form-control', 'required' => true, 'label' => false, 'placeholder' => 'Insira a razão social']) ?>
						</div>
					</div>
					<div class="col-md-4 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">CNPJ </label>
							<?= $this->Form->control('cnpj', ['class' => 'form-control', 'required' => true, 'id' => 'cnpj', 'label' => false, 'placeholder' => 'Insira o CNPJ']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-8 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Nome Fantasia</label>
							<?= $this->Form->control('nomefantasia', ['class' => 'form-control', 'required' => true, 'label' => false, 'placeholder' => 'Insira o nome fantasia']) ?>
						</div>
					</div>
					<div class="col-md-4 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">CNAE </label>
							<?= $this->Form->control('cnae', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o CNAE']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-4 col-xs-10">
						<div class="form-group ">
							<label class="control-label text-muted">Endereço</label>
							<?= $this->Form->control('endereco', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o endereço', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-md-2 col-xs-2">
						<div class="form-group ">
							<label class="control-label text-muted">Nro.</label>
							<?= $this->Form->control('nroendereco', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nro.', 'required' => true]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Bairro</label>
							<?= $this->Form->control('bairro', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o bairro', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">CEP <small class="text-muted"></small></label>
							<?= $this->Form->control('cep', ['class' => 'form-control', 'id' => 'cep', 'label' => false, 'placeholder' => 'Insira o CEP', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
						<label class="control-label text-muted">Cidade</label>
						<?= $this->Form->control('idcidade', ['data-live-search' => 'true', 'class' => 'selectpicker form-control', 'options' => $cidades, 'label' => false]) ?>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-4 col-md-4 col-sm-6 col-xs-6">
						<div class="form-group ">
							<label class="control-label text-muted">Telefone</label>
							<?= $this->Form->control('fone', ['class' => 'form-control', 'id' => 'fone', 'label' => false, 'required' => true, 'placeholder' => 'Insira o telefone']) ?>
						</div>
					</div>
					<div class="col-lg-4 col-md-4 col-sm-6 col-xs-6">
						<div class="form-group ">
							<label class="control-label text-muted">Celular  </label>
							<?= $this->Form->control('fone2', ['class' => 'form-control', 'id' => 'fone2', 'label' => false, 'placeholder' => 'Insira o celular']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
						<div class="form-group">
                            <label class="control-label text-muted">Logotipo</label>
                            <div class="bg">
								<div class="file-drop-area">
									<span class="fake-btn text-muted">Escolha o arquivo ou arraste-o aqui</span>
									<input multiple class="file-input form-control"  name="logotipo" id="logotipo" type="file">
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">E-mail faturamento</label>
							<?= $this->Form->email('email', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o e-mail', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Site</label>
							<?= $this->Form->control('site', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o site']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-4">
						<label class="control-label text-muted">Senha Administrativa</label>
						<?= $this->Form->password('senhaadministrativa', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira a senha']) ?>
					</div>
					<div class="col-4">
						<label class="control-label text-muted">Confirmar Senha Administrativa</label>
						<?= $this->Form->password('confirmasenhaadministrativa', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira a senha']) ?>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
						<div class="form-group ">
							<?= $this->Form->button('Adicionar empresa', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-25']) ?>
							<?= $this->Form->end(); ?>
						</div>
					</div>
				</div>						
			</div>
		</div>
	</div>
</div>

<script>

jQuery(function($){
	$("#cnpj").mask("99.999.999/9999-99");
	$("#cpf").mask("999.999.999-99");
	$("#cnae").mask("9999-9/99");
	$("#cep").mask("99999-999");
});

	function SomenteNumero(e){
		var tecla=(window.event)?event.keyCode:e.which;   
		console.log(tecla);
		if((tecla>47 && tecla<58)) return true;
		else{
			if (tecla==8 || tecla==0) return true;
		else  return false;
		}
	}

	function SomenteNumeroValor(e){
		var tecla=(window.event)?event.keyCode:e.which;  
		if((tecla>47 && tecla<58)) return true;
		else if (tecla==8 || tecla==0) return true;
		else if (tecla == 46)  return true;    
		else if( $('#valor').val().indexOf(',') > -1 && tecla == 44 ) return false
		else if( $('#valor').val().indexOf(',') <= -1 && tecla == 44 ) return true
		else  return false;
	}

	$(document).ready(function(){
		$("#cnpj").mask("99.999.999/9999-99");
		$("#fone").mask("(99) 9999-9999");
		$("#fone2").mask("(99) 99999-9999");
		$("#cep").mask("99999-999");
		$("#cpf").mask("999.999.999-99");

		table = $('#tableAtivos, #tableContatos')
		table.on( 'length.dt', function ( e, settings, len ) {
			pagelength(len);
		} )
		table.DataTable({
			"pageLength": 20,
			"lengthChange": false,
			"language": {
				"sProcessing":    "Procesando...",
				"sLengthMenu":    "Mostrar _MENU_ registros",
				"sZeroRecords":   "Nenhum registro encontrado",
				"sEmptyTable":    "Nenhum dado disponível",
				"sInfo":          "Mostrando registros de _START_ até _END_ de um total de _TOTAL_ registros",
				"sInfoEmpty":     "Mostrando registros de 0 a 0 de um total de 0 registros",
				"sInfoFiltered":  "(filtrado de um total de _MAX_ registros)",
				"sInfoPostFix":   "",
				"sSearch":        "Buscar:",
				"sUrl":           "",
				"sInfoThousands":  ",",
				"sLoadingRecords": "Carregando...",
				"oPaginate": {
					"sFirst":    "<<",
					"sLast":    ">>",
					"sNext":    ">",
					"sPrevious": "<"
				},
				"oAria": {
					"sSortAscending":  ": Ordem Ascendente",
					"sSortDescending": ": Ordem descendente"
				}
			},
			"drawCallback": function( settings ) {
				$('td').removeClass('dark-mode');
			},
		});
		table.search(filters).draw();
	});

	$(document).on('change', '.file-input', function() {
        var filesCount = $(this)[0].files.length;
        var $textContainer = $(this).prev();
        var fileName = $(this).val().split('\\').pop();
        if (filesCount === 1) {
            // if single file is selected, show file name
            var fileName = $(this).val().split('\\').pop();
            $textContainer.text(fileName);
        } else {
            // otherwise show number of files
            $textContainer.text(filesCount + ' arquivos selecionados');
        }
            jQuery.each(jQuery('#logotipo')[0].files, function(i, file) {
                console.log(file);
            });
    });

</script>