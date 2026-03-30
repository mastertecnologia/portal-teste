<?php
	use Cake\Routing\Router;
    // Breadcumbs
    $this->Breadcrumbs->add('Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
    $this->Breadcrumbs->add('Cadastrar', [], ['class' => 'breadcrumb-item active']);
?>
<style>
	.jsgrid-grid-header, .jsgrid-grid-body {
		overflow: auto; 
	} 
	.jsgrid-cell > select > option { text-align: left; }
</style>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <div class="row">
				<div class="col-12">
					<legend>Ticket nº: <?= $idticket ?></legend>
				</div>
			</div>
            <?= $this->Form->create($ordem, ['class' => 'form-material', 'id' => 'form-os-ticket']) ?>
				<div class="row">
					<div class="col-lg-6 col-sm-12">
						<label class="control-label">Cliente</label>
						<?= $this->Form->control('idcliente', ['disabled', 'data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
						<?= $this->Form->text('idcliente', ['type' => 'hidden', 'value' => $ordem->idcliente, 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-6 col-sm-12">
						<label class="control-label">Solicitante</label>
						<?= $this->Form->control('idsolicitante', ['disabled', 'class' => 'selectpicker form-control', 'title' => 'Solicitante (opcional)', 'data-live-search' => true, 'options' => '', 'label' => false, 'required' => false]) ?>
					</div>
				</div>
				<br>
				<div class="row clienteTelemail">
					<div class="col-lg-3 col-sm-6">
						<label class="control-label">Telefone para contato</label>
						<?= $this->Form->control('telefone', ['class' => 'telefone form-control clienteTelemail', 'label' => false, 'placeholder' => 'Nenhum telefone']) ?>
					</div>
					<div class="col-lg-3 col-sm-6">
						<label class="control-label">Celular para contato</label>
						<?= $this->Form->control('celular', ['class' => 'celular form-control clienteTelemail', 'label' => false, 'placeholder' => 'Nenhum celular']) ?>
					</div>
					<div class="col-lg-6 col-sm-12">
						<label class="control-label">E-mail para contato</label>
						<?= $this->Form->email('email', ['type' => 'text', 'class' => 'email form-control clienteTelemail', 'label' => false, 'placeholder' =>'Nenhum email']) ?>
					</div><br><br><br><br>
				</div>
				<div class="row">
					<div class="col-lg-3 col-sm-12">
						<div class="form-group ">
							<label class="control-label">Data de Abertura</label>
							<?= $this->Form->text('dataabertura', ['readonly', 'value' => date('d/m/Y'), 'class' => 'form-control datepicker', 'label' => false, 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-sm-12">
						<div class="form-group ">
							<label class="control-label">Data de Previsão</label>
							<?= $this->Form->text('dataprevisao', ['placeholder' => 'Data', 'class' => 'form-control datepicker', 'label' => false, 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-sm-12">
						<label class="control-label">Prioridade</label>
						<?= $this->Form->control('prioridade', ['placeholder' => 'Data', 'options' => C_OrdensPrioridade,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-3 col-sm-12">
						<label class="control-label">Contrato</label>
						<?= $this->Form->control('contrato', ['readonly', 'placeholder' => 'Data', 'options' => C_OrdensContrato,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-lg-6 col-sm-12">
						<label class="control-label">Status</label>
						<?= $this->Form->control('idarea', ['disabled', 'data-live-search' => true, 'options' => $areas, 'title' => 'Selecione um status', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
						<?= $this->Form->text('idarea', ['type' => 'hidden', 'value' => $ordem->idarea, 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-6 col-sm-12">
						<label class="control-label">Tipo de OS</label>
						<?= $this->Form->control('idproblema', ['disabled', 'data-live-search' => true, 'options' => $problemas, 'title' => 'Selecione um Tipo de OS', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
						<?= $this->Form->text('idproblema', ['type' => 'hidden', 'value' => $ordem->idproblema, 'label' => false, 'required' => true]) ?>
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-lg-6 col-md-12">
						<label class="control-label">Descrição do Problema</label>
						<?= $this->Form->textarea('relato', ['placeholder' => 'Insira a descrição do problema da ordem', 'class' => 'form-control', 'label' => false, 'required' => false]) ?>
					</div>
					<div class="col-lg-6 col-md-12">
						<label class="control-label">Observação</label>
						<?= $this->Form->textarea('observacao', ['id' => 'ordem-observacao', 'placeholder' => 'Observação', 'class' => 'form-control', 'label' => false, 'required' => false]) ?>
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-lg-4 col-sm-12">
						<label class="control-label">Atendimento</label>
						<?= $this->Form->control('atendimento', ['placeholder' => 'Data', 'options' => C_OrdensAtendimento,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
					<div class="d-none">
						<?= $this->Form->control('idEmpresaAtual', ['id' => 'idEmpresaAtual', 'type' => 'hidden', 'value' => (int)($authIdempresa ?? 0), 'label' => false]) ?>
					</div>
					<div class="col-lg-4 col-sm-12">
						<label class="control-label">Modelo</label>
						<?= $this->Form->control('modelo', ['placeholder' => 'Insira o modelo',  'class' => 'form-control', 'label' => false, 'required' => false]) ?>
					</div>
					<div class="col-lg-4 col-sm-12">
						<label class="control-label">N/S</label>
						<?= $this->Form->control('nmrserie', ['placeholder' => 'Insira o número de série', 'class' => 'form-control', 'label' => false, 'required' => false]) ?>
					</div>
				</div>
				<hr>
				<!-- Campos pro mobile  -->
					<br><h4 class='text-center'>Adicionar Produtos/Serviços</h4><br>
					<?php if(isMobile()){ ?>
						<div class="row">
							<div class="col-2">
								<label class="form-group ">Tipo</label>
								<?= $this->Form->control('tipo', ['data-live-search' => true, 'options' => $tiposMobile, 'title' => 'Código', 'class' => 'inputMobile form-control selectpicker p-0', 'label' => false]) ?>
							</div>
							<div class="col-2">
								<label class="control-label text-muted">Código</label>
								<?= $this->Form->control('codproduto', ['data-live-search' => true, 'options' => $produtosMobile, 'title' => 'Código', 'class' => 'inputMobile form-control selectpicker p-0', 'label' => false]) ?>
							</div>
							<div class="col-8">
								<div class="form-group ">
									<label class="control-label text-muted">Descrição</label>
									<?= $this->Form->control('descricao', ['class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-2">
								<div class="form-group ">
									<label class="control-label text-muted">Unidade</label>
									<?= $this->Form->control('unidade', ['class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group ">
									<label class="control-label text-muted">Quantidade</label>
									<?= $this->Form->control('quantidade', ['class' => 'aquisicao form-control inputMobile', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group ">
									<label class="control-label text-muted">Valor Unitário (R$)</label>
									<?= $this->Form->control('valorunitario', ['class' => 'aquisicao form-control inputMobile mascaramonetaria', 'label' => false,]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label text-muted">Valor Desconto (R$)</label>
									<?= $this->Form->control('valordesconto', ['class' => 'mensal form-control inputMobile mascaramonetaria', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label text-muted">Valor Total (R$)</label>
									<?= $this->Form->text('valortotal', ['id' => 'valortotal', 'class' => 'mensal form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
						</div>
						<?= $this->Html->link('Adicionar item', [], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info btn-additem m-b-20']) ?>
					<?php } ?>
				<?= $this->Form->end() ?>
				<div id="grid_table"></div>
				<?= '<h5 class="text-right text-success font-weight-bold m-r-15 valortotalordem"> </h5>' ?>
				<input type="hidden" name="valortotalordem" id="valortotalordem" value="<?= h($ordem->valortotalordem ?? '') ?>" form="form-os-ticket">
				<br><p><i>O cadastro de horas e parcelas ficará disponível apenas após a abertura da Ordem de Serviço.</i></p>
				<button type="submit" class="btn btn-pgm btn-pgm-salvar btn-success" form="form-os-ticket"><?= __('Abrir Ordem de Serviço') ?></button>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
<!-- Modal Observacao -->
<div class="modal fade none-border" id="modal-observacao">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="row m-20 m-b-0 form-material">
				<div class="col-12">
					<div class="form-group ">
						<label class="control-label">Observação</label>
						<?= $this->Form->textarea('observacaomodal', ['placeholder' => 'Insira a observação do item', 'id' => 'observacaomodal', 'class' => 'form-control', 'label' => false]);?>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Html->link('Salvar observação', ['#'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success text-white btn-observacao m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<!-- Modal Serial Number -->
<div class="modal fade none-border" id="modal-serialnumber">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="row m-20 m-b-0 form-material">
				<div class="col-12">
					<div class="form-group ">
						<label class="control-label">Serial Number</label>
						<?= $this->Form->text('serialnumbermodal', ['list' => 'listaSN', 'maxlength' => 100, 'placeholder' => 'Insira o serial number do item', 'id' => 'serialnumbermodal', 'class' => 'form-control', 'label' => false]);?>
						<datalist id="listaSN"> </datalist>
						<small>*Informe apenas um Serial Number. Para mais códigos, adicione o produto novamente.</small>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Html->link('Salvar serial number', ['#'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success text-white btn-serialnumber m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>


<script>
	var pgmAuthIdempresa = <?= json_encode((int)($authIdempresa ?? 0)); ?>;
	function getEmpresaAtualTicket() {
		var v = $('#empresaSidebar').val();
		if (v !== undefined && v !== null && v !== '') {
			var n = parseInt(String(v), 10);
			if (!isNaN(n) && n > 0) {
				return n;
			}
		}
		return pgmAuthIdempresa;
	}
	$(function () {
		$('#idEmpresaAtual').val(getEmpresaAtualTicket());
	});
	// Solicitantes e telemail
		$(document).ready(function(){
			$('.clienteTelemail').hide();
			$('.clienteTelemail').prop("disabled", true);
		});

		function loadSolicitantes(idcliente) {
			$.ajax({
				dataType: "json",
				url: "<?= Router::url(['controller'=>'Clientes','action'=>'solicitantes']);?>/" + idcliente,
				success: function(data){
					$('#idsolicitante').find('option').remove().end();
					$.each(data, function(key, array) {
						$('#idsolicitante').append($('<option>', {
							value: key,
							text: array
						}));
					})
					$('#idsolicitante').selectpicker("refresh");
				},
			});
		}

		function loadCliTelemail(idcliente) {
			$.ajax({
				dataType: "json",
				url: "<?= Router::url(['controller'=>'Clientes','action'=>'cliemail']);?>/" + idcliente,
				success: function(data){
					$('.clienteTelemail').show();
					$('.email').val(data.email);
					$('.telefone').val(data.fone);
					$('.celular').val(data.fone2);
				},
			});
		}

		function loadSolTelemail(idsolicitante) {
			$.ajax({
				dataType: "json",
				url: "<?= Router::url(['controller'=>'Clientes','action'=>'solemail']);?>/" + idsolicitante,
				success: function(data){
					$('.clienteTelemail').show();
					$('.email').val(data.email);
					$('.telefone').val(data.telefone);
					$('.celular').val(data.celular);
				},
			});
		}

		$("#idcliente").change(function() {
			var idcliente = $(this).val();
			loadSolicitantes(idcliente);
			loadCliTelemail(idcliente);
			$.ajax({
				url: "<?= Router::url(['controller'=>'Clientes','action'=>'contrato']);?>/" + $(this).val(),
				success:function(data){
					if(data == 1) $('#contrato').val(1); 
					else $('#contrato').val(0); 
				},
			});
		});

		$("#idsolicitante").change(function() {
			loadSolTelemail($(this).val());
		})

	// URLsF
		var urlLoadData = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinho']);?>";
		var urlAdd = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinhoadd']);?>";
		var urlEdit = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinhoedititem']);?>";
		var urlDelete = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinhodelitem']);?>";
		var osGridAjaxVerbose = <?= !empty($osGridAjaxVerbose) ? 'true' : 'false' ?>;
		function pgmOsGridEscapeHtml(s) {
			if (s == null || s === '') return '';
			return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		}
		function pgmOsGridExplainXhr(xhr, defaultMsg) {
			var parts = [];
			parts.push('<p><strong>' + pgmOsGridEscapeHtml(defaultMsg || 'Erro na requisição.') + '</strong></p>');
			parts.push('<p>HTTP <code>' + pgmOsGridEscapeHtml(String(xhr.status || '?')) + '</code>' +
				(xhr.statusText ? ' — ' + pgmOsGridEscapeHtml(xhr.statusText) : '') + '</p>');
			var j = xhr.responseJSON;
			if (j) {
				if (j.code) parts.push('<p><strong>Código:</strong> ' + pgmOsGridEscapeHtml(j.code) + '</p>');
				if (j.msg) parts.push('<p>' + pgmOsGridEscapeHtml(j.msg) + '</p>');
				if (j.warning) parts.push('<p><strong>Aviso:</strong> ' + pgmOsGridEscapeHtml(j.warning) + '</p>');
				if (osGridAjaxVerbose && j.debug) {
					parts.push('<pre style="text-align:left;font-size:11px;max-height:220px;overflow:auto;white-space:pre-wrap">' +
						pgmOsGridEscapeHtml(JSON.stringify(j.debug, null, 2)) + '</pre>');
				}
				if (osGridAjaxVerbose && j.validation) {
					parts.push('<pre style="text-align:left;font-size:11px;max-height:220px;overflow:auto;white-space:pre-wrap">' +
						pgmOsGridEscapeHtml(JSON.stringify(j.validation, null, 2)) + '</pre>');
				}
			} else if (xhr.responseText) {
				var t = xhr.responseText;
				if (t.length < 800) {
					parts.push('<pre style="text-align:left;font-size:11px;max-height:200px;overflow:auto;white-space:pre-wrap">' +
						pgmOsGridEscapeHtml(t) + '</pre>');
				} else {
					parts.push('<p>' + pgmOsGridEscapeHtml(t.substring(0, 300)) + '…</p>');
				}
			}
			return parts.join('');
		}
		function pgmOsGridAlertHtml(html) {
			if (typeof bootbox !== 'undefined') {
				bootbox.alert({ message: html });
			} else {
				alert($('<div/>').html(html).text());
			}
		}
	// Tipos e Produtos
		var tiposOpt = <?= $tiposOpt ?>;
		var produtosOpt = <?= $produtosOpt ?>;
		produtosOpt.sort(function(a, b){
			if(a.descricao < b.descricao) { return -1; }
			if(a.descricao > b.descricao) { return 1; }
			return 0;
		})

	// jsGrid
		$('#grid_table').jsGrid({
			// Options
				width: "100%",
				height: "600px",
				filtering: false,
				inserting: true,
				editing: true,
				sorting: true,
				paging: true,
				autoload: true,
				pageSize: 10,
				pageButtonCount: 5,
				deleteConfirm: "Tem certeza que deseja remover o item?",
				noDataContent: "Nenhum item adicionado",
				pagerFormat: "Pages: {prev} {pages} {next} {pageIndex} of {pageCount}",
				pagePrevText: "Anterior",
				pageNextText: "Próxima",
				pageFirstText: "Primeira",
				pageLastText: "Última",
				invalidNotify: function(args) {
					var lines = $.map(args.errors || [], function(e) {
						return (e.field && e.field.name ? e.field.name + ': ' : '') + (e.message || '');
					}).filter(Boolean);
					var msg = '<p><strong>Preencha os campos obrigatórios do item.</strong></p>';
					if (lines.length) {
						msg += '<ul><li>' + lines.map(pgmOsGridEscapeHtml).join('</li><li>') + '</li></ul>';
					}
					pgmOsGridAlertHtml(msg);
				},
			// 
			controller: {
				loadData: function(){
					return $.ajax({
					url: urlLoadData,
					dataType: "json",
					success: function(result){
						var url = "<?= Router::url(['controller'=>'Ordensservico','action'=>'valortotal']);?>";
						$.ajax({
							url: url,
							dataType: "json",
							success:function(data){
								valortotal = parseFloat(data.valortotal);
								$('#valortotalordem').val(valortotal); //formulário hidden
								$('.valortotalordem').html( '<font color="#212529"> Total geral:</font> R$ ' + numberToReal(valortotal)); //lugar que aparece escrito
								if (data && data.warning === 'sessao_carrinho' && data.msg) {
									console.warn('[OS grid valortotal]', data.msg);
								}
								tdcommuitotexto();
								$('.inputTipo, .inputValordesconto, .inputValorunitario, .inputQuantidade, .inputValordesconto, .inputDescricao, .inputQuantidade, .inputUnidade, .inputObservacao, .inputValortotal, .inputSerialnumber').append('<small class="vazio">⠀⠀⠀</small>')
								$('.inputCodproduto').append('<small class="qtdEstoque"> ⠀⠀⠀</small>')
							},
							error: function(xhr) {
								pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível obter o valor total da ordem.'));
							}
						});
					},
					error: function(xhr) {
						pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível carregar os itens da ordem (carrinho).'));
					}
					});
				},
				insertItem: function(item){
					item['idEmpresaAtual'] = getEmpresaAtualTicket();
					return $.ajax({
					type: "POST",
					dataType: "json",
					url: urlAdd + '/null/' + encodeURIComponent(item.codproduto || ''),
					data: item,
					success: function(data){
						$(".qtdEstoque, .vazio").remove();
						if (data && data.ok === true) {
							$("#grid_table").jsGrid("loadData");
							return;
						}
						if (typeof data === 'string') {
							var t = $.trim(data);
							if (t === 'boa') {
								$("#grid_table").jsGrid("loadData");
								return;
							}
							if (t === 'naopode') {
								bootbox.alert('<p style="font-weight: 300; font-size: 1.1rem" class="text-center">Este produto já foi adicionado à ordem de serviço, não é possível adicioná-lo novamente.</p>');
								$("#grid_table").jsGrid("loadData");
								return;
							}
						}
						if (data && typeof data === 'object' && data.ok === false) {
							if (data.code === 'os_grid_produto_duplicado' && data.msg) {
								bootbox.alert('<p class="text-center" style="font-weight:300;font-size:1.1rem">' + pgmOsGridEscapeHtml(data.msg) + '</p>');
								$("#grid_table").jsGrid("loadData");
								return;
							}
							var p = ['<p><strong>' + pgmOsGridEscapeHtml('Não foi possível adicionar o item.') + '</strong></p>'];
							if (data.code) p.push('<p><strong>Código:</strong> ' + pgmOsGridEscapeHtml(data.code) + '</p>');
							if (data.msg) p.push('<p>' + pgmOsGridEscapeHtml(data.msg) + '</p>');
							if (osGridAjaxVerbose && data.debug) {
								p.push('<pre style="text-align:left;font-size:11px;max-height:220px;overflow:auto">' +
									pgmOsGridEscapeHtml(JSON.stringify(data.debug, null, 2)) + '</pre>');
							}
							if (osGridAjaxVerbose && data.validation) {
								p.push('<pre style="text-align:left;font-size:11px;max-height:220px;overflow:auto">' +
									pgmOsGridEscapeHtml(JSON.stringify(data.validation, null, 2)) + '</pre>');
							}
							pgmOsGridAlertHtml(p.join(''));
							$("#grid_table").jsGrid("loadData");
							return;
						}
						var snippet = (data === null || data === undefined) ? '(resposta vazia)' : (typeof data === 'string' ? data : JSON.stringify(data));
						pgmOsGridAlertHtml('<p><strong>Resposta inesperada ao incluir item.</strong></p><pre style="text-align:left;font-size:11px;max-height:220px;overflow:auto;white-space:pre-wrap">' +
							pgmOsGridEscapeHtml(String(snippet).substring(0, 1500)) + '</pre>');
						$("#grid_table").jsGrid("loadData");
					},
					error: function(xhr) {
						pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível adicionar o item.'));
						$("#grid_table").jsGrid("loadData");
					}
					});
				},
				updateItem: function(item){
					item['idEmpresaAtual'] = getEmpresaAtualTicket();
					return $.ajax({
					type: "PUT",
					url: urlEdit,
					data: item,
					success: function(data){
						$(".qtdEstoque, .vazio").remove();
						$("#grid_table").jsGrid("loadData");
					},
					error: function(xhr) {
						pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível atualizar o item.'));
						$("#grid_table").jsGrid("loadData");
					}
					});
				},
				deleteItem: function(item){
					item['idEmpresaAtual'] = getEmpresaAtualTicket();
					return $.ajax({
						type: "DELETE",
						url: urlDelete,
						data: item,
						success: function(){
							$(".qtdEstoque, .vazio").remove();
							$("#grid_table").jsGrid("loadData");
						},
						error: function(xhr) {
							pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível remover o item.'));
							$("#grid_table").jsGrid("loadData");
						}
					});
				},
			},
			fields: [
				{ name: "id", title: "id", type: "text", css: 'hide', validade: 'required',  editing: false,},
				{ name: "tipo", title: "Tipo", type: "select", items: tiposOpt, validade: 'required',  editing: false, insertcss: 'cellInput inputTipo', editcss: "editTipo",},
				{
					name: "codproduto",
					title: "Código do Produto",
					type: "select",
					items: produtosOpt,
					valueField: "codigo",
					textField: "descricao",
					insertcss: 'cellInput inputCodproduto',
					editcss: "editCodproduto",
					validate: "required",
					width: 300,
					insertTemplate: function() {
						var $select = $("<select>").addClass("jsgrid-select2").select2({
							placeholder: "Selecione um produto",
							allowClear: true,
							data: produtosOpt.map(function(item) {
								return { id: item.codigo, text: item.descricao };
							})
						});
						this.insertControl = $select;
						return $select;
					},
					editTemplate: function(value) {
						var $select = $("<select>").addClass("jsgrid-select2");
						$select.select2({
							placeholder: "Selecione um produto",
							allowClear: true,
							data: produtosOpt.map(function(item) {
								return { id: item.codigo, text: item.descricao };
							})
						});

						// Verifica se o valor fornecido está presente nas opções do Select2
						var optionExists = produtosOpt.some(function(item) {
							return item.codigo == value;
						});

						// Define o valor apenas se ele estiver presente nas opções do Select2
						if (optionExists) {
							$select.val(value).trigger("change");
						}

						this.editControl = $select;
						return $select;
					},
					insertValue: function() {
						var selectedData = this.insertControl.select2('data');
						if (selectedData && selectedData.length > 0) {
							return selectedData[0].id; // Retorna o ID do item selecionado
						} else {
							// Nenhuma opção selecionada
							return null;
						}
					},
					editValue: function() {
						var selectedData = this.editControl.select2('data');
						if (selectedData && selectedData.length > 0) {
							return selectedData[0].id; // Retorna o ID do item selecionado
						} else {
							// Nenhuma opção selecionada
							return null;
						}
					},
				},
				{ name: "descricao",  fixed: true, title: "Descrição", type: "text",  validate: "required", editing: false, readOnly: true, insertcss: 'cellInput inputDescricao', editcss: "editDescricao", validade: 'required', },
				{ name: "observacao",  title: "Observação", type: "text",  validate: "", insertcss: 'cellInput inputObservacao', editcss: "editObservacao",},
				{ name: "unidade",  title: "Unidade", type: "text",  editing: false, readOnly: true, insertcss: 'cellInput inputUnidade', editcss: "editUnidade", validade: 'required', },
				{ name: "quantidade",  title: "Qtde", type: "text",  insertcss: 'cellInput inputQuantidade', editcss: "editQuantidade", validate: { message: "A quantidade não pode ser igual ou inferior a 0!", validator: function(value) { return value.replace('.', '').replace(',', '.') > 0; }},},
				{ name: "valorunitario",  title: "Vl. Unitário", type: "text",  insertcss: 'cellInput inputValorunitario', editcss: "editValorunitario", validate: { message: "O valor unitário não pode ser igual ou inferior a 0!", validator: function(value) { return value.replace('.', '').replace(',', '.') > 0; }},},
				{ name: "valordesconto",  title: "Vl. Desconto", type: "text",  insertcss: 'cellInput inputValordesconto', editcss: "editValordesconto",},
				{ name: "valortotal",  title: "Vl. Total", type: "text",  readOnly: true, insertcss: 'cellInput inputValortotal', editcss: "editValortotal", headercss: 'sai', css: 'fieldValortotal', validate: { message: "O valor total não pode ser igual ou inferior a 0!", validator: function(value) { return value.replace('.', '').replace(',', '.') > 0; }},},
				{ name: "serialnumber",  title: "Serial Number", type: "text", insertcss: 'cellInput inputSerialnumber', editcss: "editSerialnumber", headercss: 'sai', css: 'fieldSerialnumber',},
				{ type: "control" }
			], 
			onRefreshed: function(args) {
				$(".jsgrid-select2").select2();
			}
		});
		$('#grid_table').on('keydown', 'input, select, textarea', function (e) {
			if (e.key === 'Enter' || e.which === 13) {
				e.preventDefault();
				return false;
			}
		});
	// numberToReal
		function numberToReal(numero) {
			if(!isNaN(numero)){
				var numero = numero.toFixed(2).split('.');
				numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
				return numero.join(',');
			}
		}

	// Mobile
		<?php if (isMobile()){ ?>
		$(document).on('change', '#tipo', function(){
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'produtostipo']);?>/" + $(this).val(),
				dataType: "json",
				success:function(data){
					$('#codproduto > option').remove();
					$.each(data, function(key, array) {
						$('#codproduto').append($('<option>', {
							value: key,
							text: array
						}));
					})
				}
			});
		});
		$(document).on('change', '#codproduto', function(){
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'produto']);?>/" + $(this).val(),
				dataType: "json",
				success:function(data){
					$('#tipo').val(data.tipo);
					$('#descricao').val(data.descricao);
					$('#unidade').val(data.unidade);
					$('#valorunitario').val(numberToReal(data.vlunitario));
					$("#quantidade").val("");
					$("#valortotal").val("");
					$("#valordesconto").val("");
					$("#serialnumber").val("");
					serialnumbers(data.codigo);
				}
			});
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']) ?>/" + $(this).val(),
				success:function(data){
					$('.qtdEstoque').text('Qtd. em estoque: ' + data);
				},
			});
		});
		$(document).on('change', '#tipo', function(){calculoAddMobile(); });
		$(document).on('change', '#codproduto', function(){calculoAddMobile(); });
		$(document).on('change', '#quantidade', function(){calculoAddMobile(); });
		$(document).on('change', '#valordesconto', function(){calculoAddMobile(); });
		$(document).on('change', '#valorunitario', function(){calculoAddMobile(); });

		function calculoAddMobile(){
			var qtde = $('#quantidade') == "" ? 0 : $('#quantidade').val();
			var vldesconto = $('#valordesconto') == "" ? 0 :  $('#valordesconto').val().replace('.', '').replace(',', '.');
			var vlunidade = $('#valorunitario') == "" ? 0 :  $('#valorunitario').val().replace('.', '').replace(',', '.');
			valortotal = qtde * vlunidade - vldesconto;
			$('#valortotal').val(numberToReal(valortotal));
		}

		$(".btn-additem").click(function(e) {
			e.preventDefault();
			$.ajax({
				url: urlAdd,
				type: 'POST',
				contentType:false,
				processData: false,
				data: function(){
					var data = new FormData();
					var j = 1;
					$( ".inputMobile" ).each(function() {
						data.append( $( this ).attr('id'), $( this ).val() ); 
						j++;
					});
					return data;
				}(),
				success: function(result) {
					if(result == 'naopode') bootbox.alert('<p style="font-weight: 300; font-size: 1.1rem" class="text-center">Este produto já foi adicionado à ordem de serviço, não é possível adicioná-lo novamente.</p>');
					$("#grid_table").jsGrid("loadData");
					$( ".inputMobile" ).each(function() {
						$( this ).val(''); 
					});
				},
			});
		});
	// Desktop
		<?php }else{  ?>
			$(document).ready(function() {
				$('.inputTipo > select').append($('<option>', {value: 0,text: 'Tipo',class: 'hide',}));
				$('.inputTipo > select').val(0);
				//$('.inputCodproduto > select').append($('<option>', {value: 0,text: 'Código',class: 'hide',}));
				$('.inputCodproduto > select').val(0);
				$(document).on('change', '.inputTipo > select', function(){
					$.ajax({
						url: "<?= Router::url(['controller'=>'Produtos','action'=>'produtostipo']);?>/" + $(this).val(),
						dataType: "json",
						success:function(data){
							data.sort(function(a, b){
								if(a.descricao < b.descricao) { return -1; }
								if(a.descricao > b.descricao) { return 1; }
								return 0;
							})
							$('.inputCodproduto option').remove();
							$.each(data, function(key, array) {
								$('.inputCodproduto > select').append($('<option>', {
									value: array.codigo,
									text: array.descricao
								}));
							})
						}
					});
				});
				
				$(document).on('change', '.inputCodproduto > select', function(){
					$.ajax({
						url: "<?= Router::url(['controller'=>'Produtos','action'=>'produto']) ?>/" + $(this).val(),
						dataType: "json",
						success:function(data){
							if(data.tipo == <?= C_ProdutosTipoProduto ?>) {
								$(".inputSerialnumber > input").prop('disabled', false);
								serialnumbers(data.codigo);
								$.ajax({
									url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']) ?>" + '/' + data.codigo,
									success:function(qtd){ if(qtd != -999) $('.qtdEstoque').text('Qtd. em estoque: ' + qtd); },
								});
							}else {
								$('.qtdEstoque').text('⠀⠀⠀');
								$(".inputSerialnumber > input").prop('disabled', 'disabled');
							}
							$('.inputTipo > select').val(data.tipo);
							$('.inputDescricao > input').val(data.descricao);
							$('.inputUnidade > input').val(data.unidade);
							$('.inputValorunitario > input').val(numberToReal(data.vlunitario));
							$(".inputQuantidade > input").val("");
							$(".inputValortotal > input").val("");
							$(".inputValordesconto > input").val("");
							$(".inputSerialnumber > input").val("");
						},
					});
				});
				
				$('.inputValordesconto > input').addClass('mascaramonetaria');
				$('.inputValorunitario > input').addClass('mascaramonetaria');

				$(document).on('change', '.inputTipo > select', function(){calculoAdd(); });
				$(document).on('change', '.inputCodproduto > select', function(){calculoAdd(); });
				$(document).on('change', '.inputQuantidade > input', function(){calculoAdd(); });
				$(document).on('change', '.inputValordesconto > input', function(){calculoAdd(); });
				$(document).on('change', '.inputValorunitario > input', function(){calculoAdd(); });

				function calculoAdd(){
					var qtde = $('.inputQuantidade > input') == "" ? 0 : $('.inputQuantidade > input').val() ;
					var vldesconto = $('.inputValordesconto > input').val() == "" ? 0 :  $('.inputValordesconto > input').val().replace('.', '').replace(',', '.');
					var vlunidade = $('.inputValorunitario > input').val() == "" ? 0 :  $('.inputValorunitario > input').val().replace('.', '').replace(',', '.');
					valortotal = qtde * vlunidade - vldesconto;
					$('.inputValortotal > input').val(numberToReal(valortotal));
				}
			});
		<?php }  ?>
	// Cálculo Edit
		$(document).on('change', '.editQuantidade > input', function(){   calculoEdit(); });
		$(document).on('change', '.editValordesconto > input', function(){calculoEdit(); });
		$(document).on('change', '.editValorunitario > input', function(){calculoEdit(); });
		function calculoEdit(){
			var qtde = $('.editQuantidade > input').val() == "" ? 0 : $('.editQuantidade > input').val() ;
			var vldesconto = $('.editValordesconto > input').val() == "" ? 0 :  $('.editValordesconto > input').val().replace('.', '').replace(',', '.');
			var vlunidade = $('.editValorunitario > input').val() == "" ? 0 :  $('.editValorunitario > input').val().replace('.', '').replace(',', '.');
			valortotal = qtde * vlunidade - vldesconto;
			$('.editValortotal > input').val(numberToReal(valortotal));
		}

	// Observação
		$(document).on("click, focus", ".editObservacao > input", function(e){ modalObs($(this)) });
		$(document).on("click, focus", ".inputObservacao > input", function(e){ modalObs($(this)) });

		window.inputObservacao = null;
		function modalObs(input) {
			window.inputObservacao = input;
			$('#observacaomodal').val(input.val()).focus();
			$('#modal-observacao').modal('toggle');
		}
		$(document).on("click", ".btn-observacao", function(e){ 
			e.preventDefault();
			window.inputObservacao.val($('#observacaomodal').val());
			$('#modal-observacao').modal('toggle');
		});

	// Serialnumber
		$(document).on("click, focus", ".editSerialnumber > input", function(e){ modalSerial($(this)) });
		$(document).on("click, focus", ".inputSerialnumber > input", function(e){ modalSerial($(this)) });

		window.inputSerialnumber = null;
		function modalSerial(input) {
			window.inputSerialnumber = input;
			$('#serialnumbermodal').val(input.val()).focus();
			$('#modal-serialnumber').modal('toggle');
		}
		$(document).on("click", ".btn-serialnumber", function(e){ 
			e.preventDefault();
			window.inputSerialnumber.val($('#serialnumbermodal').val());
			$('#modal-serialnumber').modal('toggle');
		});

		function serialnumbers(codproduto) {
			$('#listaSN').html('');
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'serialnumberproduto']);?>/" + codproduto,
				dataType: "json",
				success: function(data){
					$.each(data, function(key, reg) {
						$('#listaSN').append('<option value="'+reg.sSerialNumber+'">');
					})
				},
				error: function (error) { console.log(error); }
			});
		}
	// TDs com mto texto
		$('#grid_table').on('click', 'th', function () { tdcommuitotexto(); });
		function tdcommuitotexto () {
			i = 0;
			$('.jsgrid-cell').each(function() {
				if(!$(this).hasClass('cellInput')){
					if($(this).text().length > 50) {
						$(this).attr('data-textointeiro', $(this).text());
						$(this).html($(this).text().substr(0, 49))
						$(this).append('... <div class="btn btn-sm btn-pgm btn-pgm-situacao btn-primary btn-exapndemuitotexto btn-'+i+'"><i class="fa fa-search "></i></div>')
					}
				}
				$('.btn-'+i).attr('data-textointeiro', $(this).attr('data-textointeiro'));
				i++;
			});
		}
		$(document).on('click', '.btn-exapndemuitotexto', function(e) {
			e.preventDefault();
			bootbox.alert({ 
				message: $(this).attr('data-textointeiro'), 
				size: 'xl',
			})
		})

</script>
