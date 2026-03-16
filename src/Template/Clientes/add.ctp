<!-- breadcrumb -->
<?php 
  	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Clientes', ['controller' => 'clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Adicionar cliente', [], ['class' => 'breadcrumb-item active']);

	// Seleciona PGM Soluções em TI como empresa dominante padrão, quando existir
	$defaultEmpresaDominanteId = null;
	if (!empty($empresasOptSidebar)) {
		foreach ($empresasOptSidebar as $idEmpresa => $nomeEmpresa) {
			if (stripos($nomeEmpresa, 'PGM') !== false) {
				$defaultEmpresaDominanteId = $idEmpresa;
				break;
			}
		}
		if ($defaultEmpresaDominanteId === null) {
			$keys = array_keys($empresasOptSidebar);
			$defaultEmpresaDominanteId = reset($keys);
		}
	}
?>

<div class="col-md-12">
	<div class="card" >
		<div class="card-body">
			<?=  $this->Form->create($cliente, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<label class="control-label text-muted">Tipo</label>
						<?= $this->Form->control('tipo', ['title' => 'Tipo do cliente', 'options' => C_ClientesTipo, 'required' => true, 'class' => 'selectpicker form-control', 'label' => false, ]) ?>
					</div>
				</div>
				<br>
				<div class="row pessoaJuridica">
					<div class="col-lg-5 col-md-4 col-sm-12 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted">Razão Social</label>
							<?= $this->Form->control('razaosocial', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira a razão social']) ?>
						</div>
					</div>
					<div class="col-lg-5 col-md-4 col-sm-12 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted">Nome Fantasia</label>
							<?= $this->Form->control('nomefantasia', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome fantasia']) ?>
						</div>
					</div>
					<div class="col-lg-2 col-md-4 col-sm-12 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted d-flex justify-content-between align-items-center">
								<span>CNPJ</span>
								<button type="button" class="btn btn-sm btn-outline-info" id="btn-buscar-cnpj">
									Buscar CNPJ
								</button>
							</label>
							<?= $this->Form->control('cnpj', ['class' => 'form-control', 'id' => 'cnpj', 'label' => false, 'placeholder' => 'Insira o CNPJ']) ?>
						</div>
					</div>
                </div>
				<div class="row pessoaJuridica">
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Nome do Responsável</label>
							<?= $this->Form->control('nomeresponsavel', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome']) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">CPF</label>
							<?= $this->Form->control('cpf', ['id' => 'cpfresponsavel', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o CPF']) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">RG</label>
							<?= $this->Form->control('rg', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o RG']) ?>
						</div>
					</div>
				</div>
                <div class="row pessoaFisica">
					<div class="col-lg-9 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Nome</label>
							<?= $this->Form->control('nome', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome']) ?>
						</div>
					</div>
					<div class="col-lg-3 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">CPF</label>
							<?= $this->Form->control('cpf', ['id' => 'cpffisica', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o CPF']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-4 col-md-6 col-sm-12">
						<div class="form-group ">
							<label class="control-label text-muted">Endereço</label>
							<?= $this->Form->control('endereco', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o endereço', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-md-6 col-sm-12">
						<div class="form-group ">
							<label class="control-label text-muted">Nro.</label>
							<?= $this->Form->control('nroendereco', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nro.', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-md-6 col-sm-12">
						<div class="form-group ">
							<label class="control-label text-muted">Bairro</label>
							<?= $this->Form->control('bairro', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o bairro', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-md-6 col-sm-12">
						<div class="form-group ">
							<label class="control-label text-muted">Complemento</label>
							<?= $this->Form->control('complemento', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o complemento']) ?>
						</div>
					</div>
					<div class="col-lg-2 col-md-12 col-sm-12">
						<div class="form-group ">
							<label class="control-label text-muted">CEP <small class="text-muted"></small></label>
							<?= $this->Form->control('cep', ['class' => 'form-control', 'id' => 'cep', 'label' => false, 'placeholder' => 'Insira o CEP', 'required' => true]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<label class="control-label text-muted">Cidade</label>
						<?= $this->Form->control('idcidade', ['data-live-search' => 'true', 'class' => 'selectpicker form-control', 'options' => $cidades, 'label' => false]) ?>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
						<div class="form-group ">
							<label class="control-label text-muted">Telefone</label>
							<?= $this->Form->control('fone', ['class' => 'form-control', 'id' => 'fone', 'label' => false, 'placeholder' => 'Insira o telefone']) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
						<div class="form-group ">
							<label class="control-label text-muted">Celular</label>
							<?= $this->Form->control('fone2', ['class' => 'form-control', 'id' => 'fone2', 'label' => false, 'placeholder' => 'Insira o celular']) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">E-mail faturamento</label>
							<?= $this->Form->email('email', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o e-mail']) ?>
						</div>
					</div>
				</div>
				<div class="row pessoaJuridica">	
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Inscrição Municipal <small>(somente números)</small> </label>
							<?= $this->Form->control('inscricaomunicipal', ['onkeypress' => 'return SomenteNumero(event)', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira a inscrição municipal']) ?>
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Inscrição Estadual <small>(somente números)</small></label>
							<?= $this->Form->control('inscricaoestadual', ['onkeypress' => 'return SomenteNumero(event)', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira a inscrição estadual']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
						<div class="form-group">
							<label class='control-label text-muted'> Empresa dominante: </label>
							<?= $this->Form->control('empresadominante', ['class' => 'form-control', 'label' => false, 'options' => $empresasOptSidebar, 'default' => $defaultEmpresaDominanteId]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
						<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10 m-t-30">
							<?= $this->Form->checkbox('contrato', ['class' => 'custom-control-input', 'id' => 'contrato']); ?>
							<label class="custom-control-label text-muted" for="contrato">Contrato</label>
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
						<div class="form-group ">
							<?= $this->Form->button('Cadastrar cliente', ['class' => 'btn btn-success m-t-25 float-right']) ?>
						</div>
					</div>
				</div>		
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>

<script>
	jQuery(function($){
		$("#cnpj").mask("99.999.999/9999-99");
		$("#cpffisica").mask("999.999.999-99");
		$("#cpfresponsavel").mask("999.999.999-99");
		$("#fone").mask("(999) 9999-9999");
		$("#fone2").mask("(999) 99999-9999");
		$("#cep").mask("99999-999");
	});

	function SomenteNumero(e){
		var tecla=(window.event)?event.keyCode:e.which;   
		if((tecla>44 && tecla<58)) return true;
		else{
			if (tecla==8 || tecla==0) return true;
		else  return false;
		}
	}

	$('#inscricaoestadual').change(function(e) {
		var url = "<?= Router::url(array('controller'=>'Clientes','action'=>'cidadesestado'));?>";
		url = url + '/' + $('#idcidade').val();
		$.ajax({
			type:"get",
			url: url,
			success: function(data){
				checkInscEstadual( $('#inscricaoestadual').val(), data );
			},
			error: function (tab) {
				alert('Inscrição Estadual Inválida');
			}
		});
	});

	$('.pessoaFisica').hide();
	$('.pessoaJuridica').hide();

	$("#tipo").change(function(){
		if($(this).val() == 2){
			// Campos obrigatórios pessoa jurídica
			$("#razaosocial, #nomefantasia, #cnpj").prop('disabled', false);
			$("#nome, #cpffisica").prop('disabled', true);

			$('.pessoaFisica').hide();
			$('.pessoaJuridica').fadeIn();
		} else {
			// Campos obrigatórios pessoa física
			$("#nome, #cpffisica").prop('disabled', false);
			$("#razaosocial, #nomefantasia, #cnpj").prop('disabled', true);

			$('.pessoaJuridica').hide();
			$('.pessoaFisica').fadeIn();
		}
	})

	$('#razaosocial').change(function(){
		issoemmaiusculo = $(this).val().toUpperCase();
		$(this).val(issoemmaiusculo);
	});

	$('#nome').change(function(){
		issoemmaiusculo = $(this).val().toUpperCase();
		$(this).val(issoemmaiusculo);
	});

	// Consulta CNPJ e preenche automaticamente os dados principais
	$('#btn-buscar-cnpj').click(function(e){
		e.preventDefault();
		var cnpj = ($('#cnpj').val() || '').replace(/\D/g, '');
		if (cnpj.length !== 14) {
			alert('Informe um CNPJ válido com 14 dígitos para buscar na Receita.');
			return;
		}

		var url = "<?= Router::url(['controller' => 'Clientes', 'action' => 'consultacnpj']); ?>/" + cnpj;

		$.getJSON(url, function(data){
			if (!data || data.status === 'ERROR') {
				alert(data && data.message ? data.message : 'Não foi possível consultar o CNPJ na Receita.');
				return;
			}

			// Dados básicos da empresa
			if (data.nome) $('#razaosocial').val(data.nome.toUpperCase());
			if (data.fantasia) $('#nomefantasia').val(data.fantasia.toUpperCase());
			if (data.email) $('#email').val(String(data.email).toLowerCase());

			// Endereço
			if (data.cep) $('#cep').val((data.cep || '').replace(/\D/g, ''));
			if (data.bairro) $('#bairro').val(data.bairro.toUpperCase());
			if (data.logradouro) $('#endereco').val(data.logradouro.toUpperCase());
			if (data.numero) $('#nroendereco').val(data.numero);
			if (data.complemento) $('#complemento').val(data.complemento.toUpperCase());

			// Cidade (quando o backend conseguiu mapear para idcidade)
			if (data.idcidade) {
				$('#idcidade').val(data.idcidade);
				if ($('#idcidade').data('live-search')) {
					$('#idcidade').selectpicker('val', data.idcidade);
				}
			}

			// IE (inscrição estadual)
			if (data.ie) {
				$('#inscricaoestadual').val(data.ie.replace(/\D/g, ''));
			}

			// Telefone
			if (data.telefone) $('#fone').val(data.telefone);

			// Responsável: tenta pegar sócio administrador, senão primeiro sócio
			if (Array.isArray(data.qsa) && data.qsa.length > 0) {
				var socioAdm = data.qsa.find(function(s){ return String(s.qual || '').indexOf('Administrador') !== -1; }) || data.qsa[0];
				if (socioAdm && socioAdm.nome) {
					$('#nomeresponsavel').val(socioAdm.nome.toUpperCase());
				}
			}
		}).fail(function(){
			alert('Erro ao acessar o serviço de consulta de CNPJ. Tente novamente em instantes.');
		});
	});
</script>