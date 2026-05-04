<?php
	use Cake\Routing\Router;
	$this->Html->css('/dist/css/pages/ordensservico-add-shell.css?v=9', ['block' => true]);
    $this->Breadcrumbs->add('Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
    $this->Breadcrumbs->add('Cadastrar', [], ['class' => 'breadcrumb-item active']);
?>
<style>
	/* Grid OS: scroll horizontal só se necessário; encaixe no card. */
	body.os-add-page .os-add-shell #grid_table .jsgrid-grid-header,
	body.os-add-page .os-add-shell #grid_table .jsgrid-grid-body {
		overflow-x: auto;
		overflow-y: auto;
	}
	/* Igual edit.ctp: colunas jsGrid com .hide não reservam largura (inputs seguem no DOM). */
	body.os-add-page .os-add-shell .jsgrid th.hide,
	body.os-add-page .os-add-shell .jsgrid td.hide {
		display: none !important;
	}
	/* Linha de inserção/edição: descrição pode encolher; demais colunas seguem largura fixa do CSS. */
	body.os-add-page .os-add-shell #grid_table .jsgrid-insert-row > .jsgrid-cell,
	body.os-add-page .os-add-shell #grid_table .jsgrid-edit-row > .jsgrid-cell {
		height: auto;
		overflow: hidden;
		vertical-align: middle;
	}
	body.os-add-page .os-add-shell #grid_table .jsgrid-insert-row > .jsgrid-cell.os-cell-desc,
	body.os-add-page .os-add-shell #grid_table .jsgrid-edit-row > .jsgrid-cell.os-cell-desc {
		min-width: 0;
	}
	/* Linhas de dados: corta texto em vez de quebrar linha */
	.jsgrid-row .jsgrid-cell,
	.jsgrid-alt-row .jsgrid-cell {
		overflow: hidden;
		white-space: nowrap;
	}
	.jsgrid-cell > select > option { text-align: left; }
	.os-pesquisa-produto-sem-estoque { background-color: #f8d7da !important; }
	.os-pesquisa-produto-sem-estoque td { color: #721c24; }
	.os-add-bloco { border: 1px solid #e4e9ef; border-radius: 6px; padding: 16px 18px; margin-bottom: 18px; background: #fff; }
	.os-add-bloco-title { font-size: 0.82rem; font-weight: 600; color: #1D9E75; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.04em; }
	.os-add-bloco-origem { border-color: #cfe8de; }
	/* Mantém selects no DOM fora da tela para selectpicker + AJAX (modo ticket). */
	.os-add-shell-body { position: relative; }
	.os-add-ticket-shadow-fields { position: absolute; left: -9999px; top: 0; width: 360px; min-height: 180px; overflow: hidden; opacity: 0; }
</style>
<?php
	$osTicketMode = !empty($osOrigem) && $osOrigem === 'ticket';
	$osDataAberturaVal = date('d/m/Y');
	if (!empty($ordem->dataabertura)) {
		if ($ordem->dataabertura instanceof \DateTimeInterface) {
			$osDataAberturaVal = $ordem->dataabertura->format('d/m/Y');
		} else {
			$osDataAberturaVal = (string)$ordem->dataabertura;
		}
	}
	$osDataPrevisaoVal = date('d/m/Y', strtotime('+7 days'));
	if (!empty($ordem->dataprevisao)) {
		if ($ordem->dataprevisao instanceof \DateTimeInterface) {
			$osDataPrevisaoVal = $ordem->dataprevisao->format('d/m/Y');
		} else {
			$osDataPrevisaoVal = (string)$ordem->dataprevisao;
		}
	}
?>
<div class="col-md-12 p-0">
    <div class="os-add-shell form-material">
        <div class="os-add-shell-body">
			<?php
				$formOsOpts = ['class' => 'form-material', 'id' => 'form-os-add'];
				if (!empty($osFormPostAdd)) {
					$formOsOpts['url'] = ['controller' => 'Ordensservico', 'action' => 'add'];
				}
			?>
            <?= $this->Form->create($ordem, $formOsOpts) ?>
			<?php if (!empty($osOrigemTicketId)): ?>
				<?= $this->Form->hidden('idticket', ['value' => (int)$osOrigemTicketId]) ?>
			<?php endif; ?>
			<?php if ($osTicketMode && !empty($ticketOrigemPanel) && !empty($osOrigemTicketId)): ?>
				<?= $this->element('Ordensservico/os_ticket_origem', ['p' => $ticketOrigemPanel, 'ticketId' => $osOrigemTicketId]) ?>
			<?php endif; ?>
			<div class="row<?= $osTicketMode ? ' os-add-ticket-shadow-fields' : '' ?>">
				<div class="col-lg-6 col-sm-12">
					<label class="control-label m-b-0">Cliente</label>
					<?= $this->Form->control('idcliente', ['data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
				</div>
				<div class="col-lg-6 col-sm-12">
					<label class="control-label m-b-0">Solicitante</label>
					<?= $this->Form->control('idsolicitante', ['class' => 'selectpicker form-control', 'title' => 'Solicitante (opcional)', 'data-live-search' => true, 'options' => '', 'label' => false, 'required' => false]) ?>
					
					<!-- Campo para "Outros" - inicialmente escondido -->
					<div id="solicitante-outros-container" class="pgm-solic-outros-wrap">
						<label class="control-label m-b-0">Nome do Solicitante (Outros)</label>
						<?= $this->Form->control('solicitante_outros', [
							'class' => 'form-control', 
							'label' => false, 
							'placeholder' => 'Digite o nome do solicitante',
							'maxlength' => 255
						]) ?>
					</div>
				</div>
			</div>
			<?php if ($osTicketMode): ?>
				<p class="small text-muted m-b-15">Cliente e solicitante aparecem no bloco <strong>Origem do ticket</strong> acima; os campos permanecem no formulário para envio ao abrir a OS.</p>
			<?php endif; ?>
			<div class="os-add-bloco os-add-bloco-operacional">
				<p class="os-add-bloco-title">Dados operacionais da OS</p>
			<div class="row clienteTelemail m-t-10">
					<div class="col-lg-3 col-sm-6">
						<label class="control-label m-b-0">Telefone para contato</label>
						<?= $this->Form->control('telefone', ['class' => 'telefone form-control clienteTelemail', 'label' => false, 'placeholder' => 'Nenhum telefone']) ?>
					</div>
					<div class="col-lg-3 col-sm-6">
						<label class="control-label m-b-0">Celular para contato</label>
						<?= $this->Form->control('celular', ['class' => 'celular form-control clienteTelemail', 'label' => false, 'placeholder' => 'Nenhum celular']) ?>
					</div>
					<div class="col-lg-6 col-sm-12">
						<label class="control-label m-b-0">E-mail para contato</label>
						<?= $this->Form->email('email', ['type' => 'text', 'class' => 'email form-control clienteTelemail', 'label' => false, 'placeholder' =>'Nenhum email']) ?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-lg-3 col-sm-12">
						<div class="form-group ">
							<label class="control-label m-b-0">Data de Abertura</label>
							<?= $this->Form->text('dataabertura', ['value' => $osDataAberturaVal, 'class' => 'form-control datepicker', 'label' => false, 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-sm-12">
						<div class="form-group ">
							<label class="control-label m-b-0">Data de Previsão</label>
							<?= $this->Form->text('dataprevisao', ['value' => $osDataPrevisaoVal, 'placeholder' => 'Data', 'class' => 'form-control datepicker', 'label' => false, 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-sm-12">
						<label class="control-label m-b-0">Prioridade</label>
						<?= $this->Form->control('prioridade', ['placeholder' => 'Data', 'options' => C_OrdensPrioridade,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-3 col-sm-12">
						<label class="control-label m-b-0">Contrato</label>
						<?= $this->Form->control('contrato', ['placeholder' => 'Data', 'options' => C_OrdensContrato,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6 col-sm-12">
						<label class="control-label m-b-0">Área / status operacional</label>
						<?= $this->Form->control('idarea', ['options' => $areas, 'class' => 'form-control os-add-native-select', 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-6 col-sm-12">
						<label class="control-label m-b-0">Tipo de OS</label>
						<?= $this->Form->control('idproblema', ['data-live-search' => true, 'options' => $problemas, 'title' => 'Selecione um Tipo de OS', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-lg-6 col-md-12">
						<label class="control-label m-b-0">Descrição do Problema</label>
						<?= $this->Form->textarea('relato', ['maxlength' => 200, 'placeholder' => 'Insira a descrição do problema da ordem', 'class' => 'form-control', 'label' => false, 'required' => false]) ?>
					</div>
					<div class="col-lg-6 col-md-12">
						<label class="control-label m-b-0">Observação interna</label>
						<?= $this->Form->textarea('observacao', ['maxlength' => 200, 'placeholder' => 'Observação interna (não fiscal).', 'class' => 'form-control', 'label' => false, 'required' => false]) ?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-lg-4 col-sm-12">
						<label class="control-label m-b-0">Atendimento</label>
						<?= $this->Form->control('atendimento', ['placeholder' => 'Data', 'options' => C_OrdensAtendimento,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
					<div>
						<?= $this->Form->control('idEmpresaAtual', ['id' => 'idEmpresaAtual', 'class' => 'form-control inputMobile', 'label' => false, 'type' => 'hidden', 'value' => (int)($authIdempresa ?? 0)]) ?>
					</div>
				</div>
			</div>
				<hr>
				<div class="os-add-bloco os-add-bloco-produtos">
					<h4 class="text-center os-add-section-title m-t-0">Produtos e serviços</h4>
					<?php if(isMobile()){ ?>
						<div class="row">
							<div class="col-2">
								<label class="form-group ">Tipo</label>
								<?= $this->Form->control('tipo', ['data-live-search' => true, 'options' => $tiposMobile, 'title' => 'Código', 'class' => 'inputMobile form-control selectpicker p-0', 'label' => false]) ?>
							</div>
							<div class="col-2">
								<label class="control-label m-b-0 text-muted">Código</label>
								<?= $this->Form->control('codproduto', ['data-live-search' => true, 'options' => $produtosMobile, 'title' => 'Código', 'class' => 'inputMobile form-control selectpicker p-0', 'label' => false]) ?>
							</div>
							<div class="col-2">
								<label class="control-label m-b-0 text-muted">Qtd. Estoque: <span class="qtdEstoque"></span></label>
							</div>
							<div class="col-7">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Descrição</label>
									<?= $this->Form->control('descricao', ['class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-2">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Unidade</label>
									<?= $this->Form->control('unidade', ['class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Quantidade</label>
									<?= $this->Form->control('quantidade', ['class' => 'aquisicao form-control inputMobile', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Valor Unitário (R$)</label>
									<?= $this->Form->control('valorunitario', ['class' => 'aquisicao form-control inputMobile mascaramonetaria', 'label' => false,]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Valor Desconto (R$)</label>
									<?= $this->Form->control('valordesconto', ['class' => 'mensal form-control inputMobile mascaramonetaria', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Valor Total (R$)</label>
									<?= $this->Form->text('valortotal', ['id' => 'valortotal', 'class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Serial Number</label>
									<?= $this->Form->control('serialnumber', ['list' => 'listaSN', 'id' => 'serialnumber', 'class' => 'form-control inputMobile', 'label' => false]) ?>
									<datalist id="listaSN"> </datalist>
								</div>
							</div>
						</div>
						<?= $this->Html->link('Adicionar item', [], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info btn-additem m-b-20']) ?>
					<?php } ?>
				</div>
				<div class="os-add-bloco m-t-15">
					<p class="os-add-bloco-title">Aprovação do cliente</p>
					<p class="small text-muted m-b-0">Após <strong>Abrir Ordem de Serviço</strong>, use a tela de <strong>edição</strong> da OS para enviar ou acompanhar a aprovação do cliente, conforme o fluxo da sua empresa.</p>
				</div>
				<div class="os-add-bloco">
					<p class="os-add-bloco-title">Dados fiscais, faturamento, liberação financeiro e NF-e / NFS-e</p>
					<p class="small text-muted m-b-0">Tipo de faturamento, natureza da operação, centro de custo, condição de pagamento, observação fiscal, liberação para o financeiro e emissão de NF-e/NFS-e são definidos na <strong>edição</strong> da ordem (abas de pagamento e fiscal). O ticket não preenche nem substitui esses campos.</p>
				</div>
				<?= $this->Form->end() ?>
				<!-- jsGrid fora do form: Enter/botões não submetem "Abrir OS"; campos abaixo usam form="form-os-add" (HTML5). -->
				<div id="grid_table"></div>
				<?= '<h5 class="text-right text-success font-weight-bold m-r-15 valortotalordem"> </h5>' ?>
				<input type="hidden" name="valortotalordem" id="valortotalordem" value="<?= h($ordem->valortotalordem ?? '') ?>" form="form-os-add">
				<p class='m-t-10'><i>O cadastro de horas e parcelas ficará disponível apenas após a abertura da Ordem de Serviço.</i></p>
				<button type="submit" class="btn btn-pgm btn-pgm-salvar btn-success" form="form-os-add"><?= __('Abrir Ordem de Serviço') ?></button>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
<div class="modal fade none-border" id="modal-observacao">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detalhes do Item</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row m-20 m-b-0 form-material">
                    <div class="col-12">
                        <div class="form-group">
                            <label class="control-label m-b-0">Modelo</label>
                            <?= $this->Form->control('modelomodal', ['placeholder' => 'Insira o modelo do item', 'id' => 'modelomodal', 'class' => 'form-control', 'label' => false]);?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="control-label m-b-0">Serial Number (N/S)</label>
                            <?= $this->Form->text('serialnumbermodal', ['list' => 'listaSN', 'maxlength' => 100, 'placeholder' => 'Insira o serial number', 'id' => 'serialnumbermodal', 'class' => 'form-control', 'label' => false]);?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="control-label m-b-0">Product Key</label>
                            <?= $this->Form->text('productkeymodal', ['maxlength' => 100, 'placeholder' => 'Insira a chave do produto', 'id' => 'productkeymodal', 'class' => 'form-control', 'label' => false]);?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="control-label m-b-0">Observação NF-e</label>
                            <?= $this->Form->textarea('observacaomodal', ['placeholder' => 'Insira a observação do item', 'id' => 'observacaomodal', 'class' => 'form-control', 'label' => false]);?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="control-label m-b-0">Observação Interna</label>
                            <?= $this->Form->textarea('observacainternaomodal', ['placeholder' => 'Insira a observação interna', 'id' => 'observacainternaomodal', 'class' => 'form-control', 'label' => false]);?>
                        </div>
                    </div>
                    </div>
            </div>
            <div class="modal-footer">
                <?= $this->Html->link('Salvar Detalhes', ['#'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success text-white btn-observacao m-l-5']) ?>
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
						<label class="control-label m-b-0">Serial Number</label>
						<?= $this->Form->text('serialnumbermodal', ['list' => 'listaSN', 'maxlength' => 100, 'placeholder' => 'Insira o serial number do item', 'id' => 'serialnumbermodal', 'class' => 'form-control', 'label' => false]);?>
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
<!-- Modal para pesquisa de produtos -->
<div class="modal fade" id="modal-pesquisa-produto" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Pesquisar Produto</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="input-group">
                            <input type="text" id="termo-pesquisa-produto" class="form-control" placeholder="Digite o nome ou código do produto...">
                            <span class="input-group-btn">
                                <button class="btn btn-pgm btn-pgm-situacao btn-info" type="button" onclick="buscarProdutos()">Pesquisar</button>
                            </span>
                        </div>
                    </div>
                </div>
                <br>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th>Preço</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="resultado-pesquisa-produtos">
                            </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<?php
$osTiposComEstoqueErp = [];
foreach (['C_ProdutosTipoProduto', 'C_ProdutosTipoLicenca', 'C_ProdutosTipoLocacao'] as $osConst) {
	if (defined($osConst)) {
		$osTiposComEstoqueErp[] = (int)constant($osConst);
	}
}
?>
<script>
	var osTiposComEstoqueErp = <?= json_encode($osTiposComEstoqueErp) ?>;
	var osEstoquesLoteUrl = <?= json_encode(Router::url(['controller' => 'Produtos', 'action' => 'estoquesLote']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
	var pgmAuthIdempresa = <?= json_encode((int)($authIdempresa ?? 0)); ?>;
	var pgmOsOrigemTicket = <?= json_encode(!empty($osOrigem) && $osOrigem === 'ticket'); ?>;
	var pgmOsPrefillClienteId = <?= json_encode(!empty($ordem->idcliente) ? (int)$ordem->idcliente : null); ?>;
	var pgmOsPrefillSolicitanteId = <?= json_encode(isset($idsolicitante) && $idsolicitante !== null && $idsolicitante !== '' ? (int)$idsolicitante : null); ?>;
	function getEmpresaAtual() {
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
		$('#idEmpresaAtual').val(getEmpresaAtual());
		if (pgmOsOrigemTicket && pgmOsPrefillClienteId) {
			var cid = String(pgmOsPrefillClienteId);
			$('#idcliente').val(cid);
			if ($('#idcliente').hasClass('selectpicker')) {
				$('#idcliente').selectpicker('refresh');
			}
			loadSolicitantes(pgmOsPrefillClienteId);
			loadCliTelemail(pgmOsPrefillClienteId);
			if (pgmOsPrefillSolicitanteId) {
				window.setTimeout(function () {
					$('#idsolicitante').val(String(pgmOsPrefillSolicitanteId));
					if ($('#idsolicitante').hasClass('selectpicker')) {
						$('#idsolicitante').selectpicker('refresh');
					}
					loadSolTelemail(pgmOsPrefillSolicitanteId);
				}, 600);
			}
		}
	});
		
	// Solicitantes e telemail
		$(document).ready(function(){
			$('.clienteTelemail').hide();
			$('.clienteTelemail').prop("disabled", true);
		});
		function habilitarCamposContato(habilitar) {
			$('.telefone, .celular, .email').prop("disabled", !habilitar);
		}

		function loadSolicitantes(idcliente) {
			$.ajax({
				dataType: "json",
				url: "<?= Router::url(['controller'=>'Clientes','action'=>'solicitantes']);?>/" + idcliente,
				success: function(data){
					$('#idsolicitante').find('option').remove().end();
					
					// Adiciona a opção "Outros" como primeira opção
					$('#idsolicitante').append($('<option>', {
						value: 0,
						text: 'Outros'
					}));
					
					// Adiciona os demais solicitantes
					$.each(data, function(key, array) {
						$('#idsolicitante').append($('<option>', {
							value: key,
							text: array
						}));
					});
					
					$('#idsolicitante').selectpicker("refresh");
					
					// Esconde o campo "Outros" inicialmente
					$('#solicitante-outros-container').hide();
				},
			});
		}

		$("#idsolicitante").change(function() {
			if($(this).val() == 0) {
				// Se for "Outros", mostra o campo de texto
				$('#solicitante-outros-container').show();
				$('#solicitante-outros').focus();
			} else {
				// Se for um solicitante da lista, esconde o campo
				$('#solicitante-outros-container').hide();
				$('#solicitante-outros').val('');
				loadSolTelemail($(this).val());
			}
		});

		function loadCliTelemail(idcliente) {
			$.ajax({
				dataType: "json",
				url: "<?= Router::url(['controller'=>'Clientes','action'=>'cliemail']);?>/" + idcliente,
				success: function(data){
					$('.clienteTelemail').show();
					habilitarCamposContato(true);
					$('.email').val(data.email);
					$('.telefone').val(data.fone);
					$('.celular').val(data.fone2);
				},
			});
		}

		function loadSolTelemail(idsolicitante) {
			if(idsolicitante == 0) {
				// Se for "Outros", limpa os campos de contato
				$('.clienteTelemail').show();
				habilitarCamposContato(true);
				$('.email').val('');
				$('.telefone').val('');
				$('.celular').val('');
				return;
			}
			
			$.ajax({
				dataType: "json",
				url: "<?= Router::url(['controller'=>'Clientes','action'=>'solemail']);?>/" + idsolicitante,
				success: function(data){
					$('.clienteTelemail').show();
					habilitarCamposContato(true);
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
			if (!pgmOsOrigemTicket) {
				$.ajax({
					url: "<?= Router::url(['controller'=>'Clientes','action'=>'contrato']);?>/" + $(this).val(),
					success:function(data){
						if(data == 1) $('#contrato').val(1);
						else $('#contrato').val(0);
					},
				});
			}
		});

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
					parts.push('<pre class="pgm-pre-json pgm-pre-json--wrap">' +
						pgmOsGridEscapeHtml(JSON.stringify(j.debug, null, 2)) + '</pre>');
				}
				if (osGridAjaxVerbose && j.validation) {
					parts.push('<pre class="pgm-pre-json pgm-pre-json--wrap">' +
						pgmOsGridEscapeHtml(JSON.stringify(j.validation, null, 2)) + '</pre>');
				}
			} else if (xhr.responseText) {
				var t = xhr.responseText;
				if (t.length < 800) {
					parts.push('<pre class="pgm-pre-json pgm-pre-json--wrap pgm-pre-json--h200">' +
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
		function numberToReal(numero) {
			if (!isNaN(numero)) {
				var pa = numero.toFixed(2).split('.');
				pa[0] = pa[0].split(/(?=(?:...)*$)/).join('.');
				return pa.join(',');
			}
			return '';
		}
		/* Valores só numéricos BR (sem prefixo R$) — iguais aos inputs da imagem alvo */
		function osAddFmtNumBrInput(v) {
			if (v === null || v === undefined || v === '') {
				return '';
			}
			var n = parseFloat(String(v).replace(/\./g, '').replace(',', '.'));
			if (isNaN(n)) {
				return String(v);
			}
			return numberToReal(n);
		}
		function osAddReadonlyGridInput(val, alignRight) {
			var $i = $('<input type="text" readonly tabindex="-1">')
				.addClass('form-control form-control-sm os-grid-display-input');
			if (alignRight) {
				$i.addClass('text-right');
			}
			$i.val(osAddFmtNumBrInput(val));
			return $i;
		}
		function osAddBrowseCodProdutoCell(cod) {
			var v = cod != null && cod !== '' ? String(cod) : '';
			var $input = $('<input type="text" readonly tabindex="-1">')
				.addClass('form-control input-codigo-val os-grid-display-input');
			$input.val(v);
			var $btn = $('<button type="button" tabindex="-1">')
				.addClass('btn btn-secondary btn-sm os-grid-cod-search--browse')
				.prop('disabled', true)
				.html('<i class="fa fa-search"></i>')
				.attr('title', 'Edite o item (lápis) para alterar o código ou pesquisar');
			return $('<div class="input-group"/>').append($input).append($('<div class="input-group-append"/>').append($btn));
		}
	// Tipos e Produtos
		var tiposOpt = <?= $tiposOpt ?>;
		function osAddTipoLabelBrowse(tipo) {
			var t = String(tipo);
			var o = tiposOpt;
			if (o && typeof o === 'object' && o[t] !== undefined && o[t] !== null) {
				return String(o[t]);
			}
			if (o && typeof o === 'object' && tipo != null && o[String(tipo)] !== undefined) {
				return String(o[String(tipo)]);
			}
			return tipo != null && t !== '' && t !== 'undefined' ? t : '';
		}
		var osAddMsgNenhumItemTipo = 'Nenhum item encontrado para o tipo selecionado.';
		function osAddTipoLabelNorm(s) {
			return $.trim(String(s || '')).toLowerCase().replace(/\s+/g, ' ');
		}
		function osAddGridRowFromEl($el) {
			var $r = $el.closest('#grid_table tr.jsgrid-insert-row, #grid_table tr.jsgrid-edit-row');
			if ($r.length) {
				return $r;
			}
			$r = $el.closest('tr.jsgrid-insert-row, tr.jsgrid-edit-row');
			return $r.length ? $r : $el.closest('tr');
		}
		function osAddGridLinhaTipoSelecionado($row) {
			if (!$row || !$row.length) {
				return NaN;
			}
			/* Marca aplicada em initOsAddGridInsertRow / onRefreshed: evita confundir com #tipo mobile (form) ou outros selects. */
			var $t = $row.find('select.os-item-tipo, select.os-grid-tipo-select').first();
			if (!$t.length) {
				$t = $row.find('td.inputTipo select, td.editTipo select').first();
			}
			if (!$t.length) {
				$t = $row.find('.inputTipo select, .editTipo select').first();
			}
			if (!$t.length && ($row.hasClass('jsgrid-insert-row') || $row.hasClass('jsgrid-edit-row'))) {
				$t = $row.children('td').first().find('select').first();
			}
			if (!$t.length) {
				return NaN;
			}
			var raw = $t.val();
			if ($t.data('selectpicker')) {
				try {
					var spv = $t.selectpicker('val');
					if (spv !== null && spv !== undefined && spv !== '') {
						raw = spv;
					}
				} catch (eSp) { /* selectpicker indisponível ou não inicializado */ }
			}
			var v = parseInt(String(raw === null || raw === undefined ? '' : raw), 10);
			if (!isNaN(v) && v > 0) {
				return v;
			}
			var label = $.trim($t.find('option:selected').first().text());
			var labelN = osAddTipoLabelNorm(label);
			if (!labelN || labelN === osAddTipoLabelNorm('Tipo')) {
				return NaN;
			}
			var mapped = NaN;
			if (tiposOpt && typeof tiposOpt === 'object') {
				var keys = Object.keys(tiposOpt);
				for (var i = 0; i < keys.length; i++) {
					var k = keys[i];
					if (osAddTipoLabelNorm(tiposOpt[k]) === labelN) {
						var fk = parseInt(String(k), 10);
						if (!isNaN(fk)) {
							mapped = fk;
						}
						break;
					}
				}
			}
			if (!isNaN(mapped) && mapped > 0) {
				return mapped;
			}
			return NaN;
		}
		function osAddProdutoJsonUrl(cod) {
			var base = "<?= Router::url(['controller'=>'Produtos','action'=>'produto']) ?>/" + encodeURIComponent(cod || '');
			return base;
		}
		function osAddProdutoJsonUrlComTipo(cod, tipo) {
			var u = osAddProdutoJsonUrl(cod);
			var ti = parseInt(String(tipo), 10);
			if (!isNaN(ti) && ti > 0) {
				u += (u.indexOf('?') === -1 ? '?' : '&') + 'tipo=' + encodeURIComponent(String(ti));
			}
			return u;
		}
		function osAddLimparCamposItemLinha($row, opts) {
			opts = opts || {};
			var isEdit = !!opts.isEdit;
			$row.find('input.input-codigo-val').val('');
			if (isEdit) {
				$row.find('.editDescricao > input').val('');
				$row.find('.editUnidade > input').val('');
				$row.find('.editValorunitario > input').val('');
				$row.find('.editQuantidade > input').val('');
				$row.find('.editValortotal > input').val('');
				$row.find('.editValordesconto > input').val('');
				$row.find('.editSerialnumber > input').val('');
				$row.find('.editObservacao > input').val('');
				$row.find('.editModelo > input, .editProductKey > input').val('');
			} else {
				$row.find('.inputDescricao > input').val('');
				$row.find('.inputUnidade > input').val('');
				$row.find('.inputValorunitario > input').val('');
				$row.find('.inputQuantidade > input').val('');
				$row.find('.inputValortotal > input').val('');
				$row.find('.inputValordesconto > input').val('');
				$row.find('.inputSerialnumber > input').val('');
				$row.find('.inputObservacao > input').val('');
				$row.find('.inputModelo > input, .inputProductKey > input').val('');
			}
		}
		window.isOsGridBrowseRef = false;
		var produtosOpt = <?= $produtosOpt ?>;
		produtosOpt.sort(function(a, b){
			if(a.descricao < b.descricao) { return -1; }
			if(a.descricao > b.descricao) { return 1; }
			return 0;
		})

	// jsGrid — re-inicializa linha de inserção após cada refresh (loadData)
		window.initOsAddGridInsertRow = function () {
			var $ins = $('#grid_table .jsgrid-insert-row');
			if ($ins.length) {
				var $tipo = $ins.find('.inputTipo select');
				if ($tipo.length) {
					$tipo.addClass('os-item-tipo os-grid-tipo-select').attr('data-os-field', 'tipo').attr('data-field', 'tipo');
					if (!$tipo.find('option[value="0"]').length) {
						$tipo.prepend($('<option>', { value: 0, text: 'Tipo' }));
					}
					if ($tipo.val() === null || $tipo.val() === '') {
						$tipo.val(0);
					}
					if ($tipo.data('selectpicker')) {
						try {
							$tipo.selectpicker('refresh');
						} catch (eR) { /* evita quebrar a grid se o plugin falhar */ }
					}
				}
				$ins.find('.inputValorunitario > input, .inputValordesconto > input').addClass('mascaramonetaria');
			}
			var $edit = $('#grid_table .jsgrid-edit-row');
			if ($edit.length) {
				$edit.find('td.editTipo select, td.inputTipo select').addClass('os-item-tipo os-grid-tipo-select').attr('data-os-field', 'tipo').attr('data-field', 'tipo');
				if ($edit.find('td.editTipo select').data('selectpicker')) {
					try {
						$edit.find('td.editTipo select').selectpicker('refresh');
					} catch (eE) { }
				}
				$edit.find('.editValorunitario > input, .editValordesconto > input').addClass('mascaramonetaria');
				if (typeof calculoEdit === 'function') {
					calculoEdit();
				}
			}
		};

		function osParseDecimalBr(v, fallback) {
			var n = parseFloat(String(v || '').replace(/\./g, '').replace(',', '.'));
			if (isNaN(n)) return (fallback === undefined ? 0 : fallback);
			return n;
		}

		function osTipoExigeEstoque(tipo) {
			var t = parseInt(tipo, 10);
			return !isNaN(t) && osTiposComEstoqueErp.indexOf(t) !== -1;
		}

		function osConfirmPromise(message) {
			var d = $.Deferred();
			if (typeof bootbox !== 'undefined') {
				bootbox.confirm(message, function (ok) { d.resolve(!!ok); });
			} else {
				d.resolve(window.confirm(String(message)));
			}
			return d.promise();
		}

		function osValidarEstoqueAntesInsert(item) {
			var d = $.Deferred();
			if (!item || !osTipoExigeEstoque(item.tipo) || !item.codproduto) {
				d.resolve(true);
				return d.promise();
			}
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']) ?>/" + encodeURIComponent(item.codproduto),
				success: function(resp) {
					var estoque = osParseDecimalBr(resp, -999);
					var qtd = osParseDecimalBr(item.quantidade, 0);
					if (estoque === -999) {
						d.resolve(true);
						return;
					}
					if (estoque <= 0) {
						if (typeof bootbox !== 'undefined') {
							bootbox.alert('Produto com estoque zerado no ERP. Inclusão permitida com alerta.');
						} else {
							alert('Produto com estoque zerado no ERP. Inclusão permitida com alerta.');
						}
						d.resolve(true);
						return;
					}
					if (qtd > estoque) {
						osConfirmPromise('Quantidade (' + qtd + ') maior que o estoque disponível (' + estoque + '). Deseja incluir mesmo assim?')
							.then(function(ok) { d.resolve(ok); });
						return;
					}
					d.resolve(true);
				},
				error: function() {
					d.resolve(true);
				}
			});
			return d.promise();
		}

	// jsGrid
		$('#grid_table').jsGrid({
			// Options
				width: "100%",
				height: "auto",
				filtering: false,
				inserting: true,
				editing: true,
				sorting: true,
				paging: true,
				autoload: true,
				pageSize: 10,
				pageButtonCount: 5,
				deleteConfirm: "Tem certeza que deseja remover o item?",
				onItemEditing: function () {
					window.setTimeout(function () {
						if (typeof window.initOsAddGridInsertRow === 'function') {
							window.initOsAddGridInsertRow();
						}
					}, 0);
				},
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
								$('.valortotalordem').html('<span class="os-add-total-label">Total geral:</span> R$ ' + numberToReal(valortotal)); //lugar que aparece escrito
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
					if (item && typeof item === 'object') {
						delete item.id;
					}
					item['idEmpresaAtual'] = getEmpresaAtual();
					return osValidarEstoqueAntesInsert(item).then(function(okInsert) {
						if (!okInsert) {
							return $.Deferred().reject({ statusText: 'cancelled' }).promise();
						}
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
								bootbox.alert('<p class="text-center pgm-bootbox-msg-md">Este produto já foi adicionado à ordem de serviço, não é possível adicioná-lo novamente.</p>');
								$("#grid_table").jsGrid("loadData");
								return;
							}
						}
						if (data && typeof data === 'object' && data.ok === false) {
							if (data.code === 'os_grid_produto_duplicado' && data.msg) {
								bootbox.alert('<p class="text-center pgm-bootbox-msg-md">' + pgmOsGridEscapeHtml(data.msg) + '</p>');
								$("#grid_table").jsGrid("loadData");
								return;
							}
							var p = ['<p><strong>' + pgmOsGridEscapeHtml('Não foi possível adicionar o item.') + '</strong></p>'];
							if (data.code) p.push('<p><strong>Código:</strong> ' + pgmOsGridEscapeHtml(data.code) + '</p>');
							if (data.msg) p.push('<p>' + pgmOsGridEscapeHtml(data.msg) + '</p>');
							if (osGridAjaxVerbose && data.debug) {
								p.push('<pre class="pgm-pre-json">' +
									pgmOsGridEscapeHtml(JSON.stringify(data.debug, null, 2)) + '</pre>');
							}
							if (osGridAjaxVerbose && data.validation) {
								p.push('<pre class="pgm-pre-json">' +
									pgmOsGridEscapeHtml(JSON.stringify(data.validation, null, 2)) + '</pre>');
							}
							pgmOsGridAlertHtml(p.join(''));
							$("#grid_table").jsGrid("loadData");
							return;
						}
						var snippet = (data === null || data === undefined) ? '(resposta vazia)' : (typeof data === 'string' ? data : JSON.stringify(data));
						pgmOsGridAlertHtml('<p><strong>Resposta inesperada ao incluir item.</strong></p><pre class="pgm-pre-json pgm-pre-json--wrap">' +
							pgmOsGridEscapeHtml(String(snippet).substring(0, 1500)) + '</pre>');
						$("#grid_table").jsGrid("loadData");
					}, 
					error: function(xhr) {
						if (xhr && xhr.statusText === 'cancelled') {
							return;
						}
						pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível adicionar o item. Verifique os dados e tente novamente.'));
						$("#grid_table").jsGrid("loadData");
					}
					});
					});
				},
				updateItem: function(item){
					item['idEmpresaAtual'] = getEmpresaAtual();
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
					item['idEmpresaAtual'] = getEmpresaAtual();
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
				{ name: "id", title: "id", type: "text", css: 'hide', visible: false, validade: 'required', editing: false },
				{
					name: "tipo",
					title: "Tipo",
					type: "select",
					align: "left",
					width: 110,
					items: tiposOpt,
					validade: 'required',
					css: 'os-col-tipo',
					headercss: 'os-col-tipo',
					insertcss: 'cellInput inputTipo os-col-tipo',
					editcss: "editTipo os-col-tipo",
					itemTemplate: function (value) {
						var lbl = osAddTipoLabelBrowse(value);
						return $('<span class="os-grid-ro-plain os-grid-ro-plain--tipo"></span>').text(lbl || '—');
					},
				},
				{
					name: "codproduto",
					title: "Cód. Produto",
					type: "text", 
					align: "left",
					width: 150,
					css: 'inputCodproduto os-col-cod',
					headercss: 'os-col-cod',
					validate: "required",

					itemTemplate: function(value) {
						return osAddBrowseCodProdutoCell(value);
					},
					
					// Template para INSERÇÃO
					insertTemplate: function() {
						var $input = $("<input>").attr("type", "text").addClass("form-control input-codigo-val");
						var $btn = $("<button>").attr("type", "button").addClass("btn btn-secondary btn-sm").html('<i class="fa fa-search"></i>');
						
						$btn.on("click", function(e) {
							e.preventDefault();
							var $rowBtn = $(this).closest('#grid_table tr.jsgrid-insert-row, #grid_table tr.jsgrid-edit-row');
							if (!$rowBtn.length) {
								$rowBtn = osAddGridRowFromEl($(this));
							}
							var tipoModal = osAddGridLinhaTipoSelecionado($rowBtn);
							if (!tipoModal || tipoModal <= 0) {
								if (typeof bootbox !== 'undefined') {
									bootbox.alert('<p class="text-center pgm-bootbox-msg-md">Selecione o tipo do item antes de abrir a pesquisa.</p>');
								}
								return;
							}
							window.osModalPesquisaTipo = tipoModal;
							window.activeInputCode = $input; 
							$('#termo-pesquisa-produto').val('');
							$('#resultado-pesquisa-produtos').html('');
							$('#modal-pesquisa-produto').modal('show');
							buscarProdutos();
							setTimeout(function() { $('#termo-pesquisa-produto').focus(); }, 500);
						});
						var $group = $("<div>").addClass("input-group").append($input).append(
							$("<div>").addClass("input-group-append").append($btn)
						);
						
						this.insertControl = $input;
						return $group;
					},
					insertValue: function() {
						return this.insertControl.val();
					},

					// Template para EDIÇÃO
					editTemplate: function(value) {
						var $input = $("<input>").attr("type", "text").addClass("form-control input-codigo-val").val(value);
						var $btn = $("<button>").attr("type", "button").addClass("btn btn-secondary btn-sm").html('<i class="fa fa-search"></i>');
						
						$btn.on("click", function(e) {
							e.preventDefault();
							var $rowBtn = $(this).closest('#grid_table tr.jsgrid-insert-row, #grid_table tr.jsgrid-edit-row');
							if (!$rowBtn.length) {
								$rowBtn = osAddGridRowFromEl($(this));
							}
							var tipoModal = osAddGridLinhaTipoSelecionado($rowBtn);
							if (!tipoModal || tipoModal <= 0) {
								if (typeof bootbox !== 'undefined') {
									bootbox.alert('<p class="text-center pgm-bootbox-msg-md">Selecione o tipo do item antes de abrir a pesquisa.</p>');
								}
								return;
							}
							window.osModalPesquisaTipo = tipoModal;
							window.activeInputCode = $input;
							$('#termo-pesquisa-produto').val('');
							$('#resultado-pesquisa-produtos').html('');
							$('#modal-pesquisa-produto').modal('show');
							buscarProdutos();
							setTimeout(function() { $('#termo-pesquisa-produto').focus(); }, 500);
						});

						var $group = $("<div>").addClass("input-group").append($input).append(
							$("<div>").addClass("input-group-append").append($btn)
						);

						this.editControl = $input;
						return $group;
					},
					editValue: function() {
						return this.editControl.val();
					}
				},
				{ name: "descricao", title: "Descrição", type: "text", align: "left", width: 380, validate: "required", editing: true, readOnly: true, headercss: "os-col-desc", css: "os-cell-desc os-col-desc", insertcss: "cellInput inputDescricao os-cell-desc os-col-desc", editcss: "editDescricao os-cell-desc os-col-desc", validade: "required",
					itemTemplate: function(value) {
						var t = value != null ? String(value) : '';
						return $('<span class="os-grid-ro-plain os-grid-ro-plain--desc"></span>').text(t);
					}
				},
				{
					name: "observacao",
					title: "Ref.",
					align: "left",
					type: "text",
					width: 120,
					validate: "",
					css: 'os-cell-ref os-col-ref',
					headercss: 'os-col-ref',
					insertcss: 'cellInput inputObservacao os-col-ref',
					editcss: "editObservacao os-col-ref",
					itemTemplate: function(value, item) {
						var raw = '';
						if (value != null && String(value).trim() !== '') {
							raw = String(value).trim();
						}
						var hasMeta = !!(item && (item.modelo || item.serialnumber || item.productkey || item.obsinterna));
						var label = 'Detalhes';
						if (raw.length > 0) {
							label = raw.length > 28 ? raw.substr(0, 25) + '\u2026' : raw;
						} else if (hasMeta) {
							label = 'Detalhes';
						}
						return $('<button type="button" class="btn btn-sm btn-outline-secondary os-grid-ref-pill os-grid-ref-trigger"/>')
							.text(label)
							.attr('title', 'Detalhes do item')
							.attr('aria-label', 'Abrir detalhes do item');
					},
				},
				{
					name: "unidade",
					title: "Unid.",
					width: 70,
					type: "text",
					align: "center",
					editing: true,
					readOnly: true,
					css: 'os-col-unid',
					headercss: 'os-col-unid',
					insertcss: 'cellInput inputUnidade os-col-unid',
					editcss: "editUnidade os-col-unid",
					validade: 'required',
					itemTemplate: function(value) {
						return $('<span class="os-grid-ro-plain os-grid-ro-plain--center"></span>').text(value != null && String(value).trim() !== '' ? String(value).trim() : '—');
					},
				},
				{
					name: "quantidade",
					title: "Qtde",
					width: 80,
					type: "text",
					align: "right",
					css: 'os-col-qtde',
					headercss: 'os-col-qtde',
					insertcss: 'cellInput inputQuantidade os-col-qtde',
					editcss: "editQuantidade os-col-qtde",
					validate: { message: "Informe uma quantidade maior que zero.", validator: function(value) { var n = parseFloat(String(value || '').replace(/\./g, '').replace(',', '.')); return !isNaN(n) && n > 0; }},
					itemTemplate: function(value) {
						return osAddReadonlyGridInput(value, true);
					},
				},
				{
					name: "valorunitario",
					title: "Vl. Unit.",
					type: "text",
					align: "right",
					width: 110,
					css: 'os-col-vlun',
					headercss: 'os-col-vlun',
					insertcss: 'cellInput inputValorunitario os-col-vlun',
					editcss: "editValorunitario os-col-vlun",
					validate: { message: "Informe um valor unitário válido (pode ser zero para serviços cortesia).", validator: function(value) { var n = parseFloat(String(value || '').replace(/\./g, '').replace(',', '.')); return !isNaN(n) && n >= 0; }},
					itemTemplate: function(value) {
						return osAddReadonlyGridInput(value, true);
					},
				},
				{
					name: "valordesconto",
					title: "Vl. Desc.",
					type: "text",
					align: "right",
					width: 110,
					css: 'os-col-vld',
					headercss: 'os-col-vld',
					insertcss: 'cellInput inputValordesconto os-col-vld',
					editcss: "editValordesconto os-col-vld",
					itemTemplate: function(value) {
						return osAddReadonlyGridInput(value, true);
					},
				},
				/* Total é calculado no cliente e recalculado no servidor; validar >0 bloqueava linha válida e rejeitava insertItem sem mensagem. */
				{ name: "valortotal",  title: "Total", width: 110, align: "right", type: "text",  readOnly: true, editing: true, css: 'fieldValortotal os-col-total', insertcss: 'cellInput inputValortotal os-col-total', editcss: "editValortotal os-col-total", headercss: 'os-col-total sai',
					itemTemplate: function(value) {
						return osAddReadonlyGridInput(value, true);
					},
				},
                { name: "modelo", type: "text", width: 0, css: 'hide', insertcss: 'hide inputModelo', editcss: 'hide editModelo' },
                { name: "serialnumber", type: "text", width: 0, css: 'hide', insertcss: 'hide inputSerialnumber', editcss: 'hide editSerialnumber' },
				{ name: "productkey", type: "text", width: 0, css: 'hide', insertcss: 'hide inputProductKey', editcss: 'hide editProductKey'},
				{ name: "obsinterna", type: "text", width: 0, css: 'hide', insertcss: 'hide inputObsInterna', editcss: 'hide editObsInterna'},
				{
					type: "control",
					align: "center",
					width: 100,
					headercss: 'jsgrid-control-field os-col-acoes',
					css: 'jsgrid-control-field os-col-acoes',
					modeSwitchButton: false,
					editButton: false,
					deleteButton: false,
					itemTemplate: function (value, item) {
						var $ed = $('<button type="button" class="btn btn-sm os-grid-act-edit"/>')
							.html('<i class="fa fa-pencil" aria-hidden="true"></i>')
							.attr('title', 'Editar item');
						$ed.on('click', function (e) {
							e.preventDefault();
							e.stopPropagation();
							$('#grid_table').jsGrid('editItem', item);
						});
						var $del = $('<button type="button" class="btn btn-sm os-grid-act-delete"/>')
							.html('<i class="fa fa-trash" aria-hidden="true"></i>')
							.attr('title', 'Excluir item');
						$del.on('click', function (e) {
							e.preventDefault();
							e.stopPropagation();
							$('#grid_table').jsGrid('deleteItem', item);
						});
						return $('<span class="os-grid-actions-cell"></span>').append($ed).append($del);
					},
					editTemplate: function () {
						var $ok = $('<button type="button" class="btn btn-sm btn-success text-white os-grid-act-save"/>')
							.attr('title', 'Salvar edição')
							.html('<i class="fa fa-check"></i>');
						var $cancel = $('<button type="button" class="btn btn-sm btn-light border os-grid-act-cancel"/>')
							.attr('title', 'Cancelar edição')
							.html('<i class="fa fa-times text-dark"></i>');
						$ok.on('click', function (e) {
							e.preventDefault();
							e.stopPropagation();
							$('#grid_table').jsGrid('updateItem');
						});
						$cancel.on('click', function (e) {
							e.preventDefault();
							e.stopPropagation();
							$('#grid_table').jsGrid('cancelEdit');
						});
						return $('<span class="os-grid-actions-edit"></span>').append($ok).append($cancel);
					},
				}
			], 
			onRefreshed: function() {
				try {
					if ($(".jsgrid-select2").length) {
						$(".jsgrid-select2").select2();
					}
				} catch (e) { /* select2 opcional */ }
				if (typeof window.initOsAddGridInsertRow === 'function') {
					window.initOsAddGridInsertRow();
				}
				$('#grid_table td.inputTipo select, #grid_table td.editTipo select').addClass('os-item-tipo os-grid-tipo-select').attr('data-os-field', 'tipo').attr('data-field', 'tipo');
				var $insBtn = $('#grid_table .jsgrid-insert-row .jsgrid-insert-button');
				if ($insBtn.length) {
					$insBtn.val('+').attr('title', 'Adicionar item');
				}
				var $actTh = $('#grid_table').find('.jsgrid-header-row th.jsgrid-control-field');
				if ($actTh.length && $.trim($actTh.text()) === '') {
					$actTh.text('Ações');
				}
				/* Mesma grade em cabeçalho / inclusão / dados / edição — largura fixa + scroll-X */
				var $g = $('#grid_table');
				var osGridMinW = 110 + 150 + 380 + 120 + 70 + 80 + 110 + 110 + 110 + 100;
				$g.css({ width: '100%', maxWidth: '100%', minWidth: 0 });
				$g.find('.jsgrid-grid-header, .jsgrid-grid-body').css({ width: '100%', overflowX: 'auto' });
				$g.find('.jsgrid-grid-header .jsgrid-table, .jsgrid-grid-body .jsgrid-table').css({
					tableLayout: 'fixed',
					width: '100%',
					minWidth: osGridMinW + 'px'
				});
			}
		});
		/* Grid está dentro do form da OS: Enter em qtde/preço submetia o form inteiro (refresh). */
		$('#grid_table').on('keydown', 'input, select, textarea', function (e) {
			if (e.key === 'Enter' || e.which === 13) {
				e.preventDefault();
				return false;
			}
		});

	// Mobile
		<?php if (isMobile()){ ?>
		$(document).on('change', '#tipo', function(){
			$('#codproduto').val('');
			$('#descricao').val('');
			$('#unidade').val('');
			$('#valorunitario').val('');
			$('#quantidade').val('');
			$('#valortotal').val('');
			$('#valordesconto').val('');
			$('#serialnumber').val('');
			$('.qtdEstoque').text('');
			var tv = $(this).val();
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'produtostipo']);?>/" + tv,
				dataType: "json",
				success:function(data){
					$('#codproduto > option').remove();
					$.each(data, function(_idx, row) {
						if (!row || row.codigo === undefined) {
							return;
						}
						$('#codproduto').append($('<option>', {
							value: row.codigo,
							text: row.descricao
						}));
					});
				}
			});
		});
		$(document).on('change', '#codproduto', function(){
			var cod = $.trim($(this).val() || '');
			if (!cod) {
				return;
			}
			var tipoSel = parseInt(String($('#tipo').val() || '0'), 10);
			if (!tipoSel || tipoSel <= 0) {
				if (typeof bootbox !== 'undefined') {
					bootbox.alert('<p class="text-center pgm-bootbox-msg-md">Selecione o tipo do item antes de informar o código.</p>');
				}
				$(this).val('');
				return;
			}
			var urlProd = "<?= Router::url(['controller'=>'Produtos','action'=>'produto']);?>/" + encodeURIComponent(cod) + '?tipo=' + encodeURIComponent(String(tipoSel));
			$.ajax({
				url: urlProd,
				dataType: "json",
				success:function(data){
					if (!data || data.mensagem) {
						if (typeof bootbox !== 'undefined') {
							bootbox.alert('<p class="text-center pgm-bootbox-msg-md">' + osAddMsgNenhumItemTipo + '</p>');
						}
						$('#descricao').val('');
						$('#unidade').val('');
						$('#valorunitario').val('');
						$('#quantidade').val('');
						$('#valortotal').val('');
						$('#valordesconto').val('');
						$('#serialnumber').val('');
						return;
					}
					if (parseInt(data.tipo, 10) !== tipoSel) {
						if (typeof bootbox !== 'undefined') {
							bootbox.alert('<p class="text-center pgm-bootbox-msg-md">' + osAddMsgNenhumItemTipo + '</p>');
						}
						$('#codproduto').val('');
						$('#descricao').val('');
						$('#unidade').val('');
						$('#valorunitario').val('');
						return;
					}
					$('#descricao').val(data.descricao);
					$('#unidade').val(data.unidade);
					$('#valorunitario').val(numberToReal(data.vlunitario));
					$("#quantidade").val("");
					$("#valortotal").val("");
					$("#valordesconto").val("");
					$("#serialnumber").val("");
					serialnumbers(data.codigo);
				},
				error: function (xhr) {
					var m = osAddMsgNenhumItemTipo;
					if (xhr && xhr.responseJSON && xhr.responseJSON.mensagem) {
						m = xhr.responseJSON.mensagem;
					}
					if (typeof bootbox !== 'undefined') {
						bootbox.alert('<p class="text-center pgm-bootbox-msg-md">' + m + '</p>');
					}
					$('#descricao').val('');
					$('#unidade').val('');
					$('#valorunitario').val('');
				}
			});
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']) ?>/" + encodeURIComponent(cod),
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
					if(result == 'naopode') bootbox.alert('<p class="text-center pgm-bootbox-msg-md">Este produto já foi adicionado à ordem de serviço, não é possível adicioná-lo novamente.</p>');
					$("#grid_table").jsGrid("loadData");
					$( ".inputMobile" ).each(function() {
						$( this ).val(''); 
					});
				},
			});
		});
	// Desktop — código do produto é <input> (pesquisa/modal), não <select>
		<?php }else{  ?>
			function calculoAdd(){
				var $row = $('#grid_table .jsgrid-insert-row');
				if (!$row.length) {
					return;
				}
				var qtdeRaw = $row.find('.inputQuantidade > input').val();
				var qtde = (qtdeRaw === undefined || qtdeRaw === '') ? 0 : parseFloat(String(qtdeRaw).replace(/\./g, '').replace(',', '.')) || 0;
				var vldesconto = $row.find('.inputValordesconto > input').val() === '' ? 0 : parseFloat($row.find('.inputValordesconto > input').val().replace(/\./g, '').replace(',', '.')) || 0;
				var vlunidade = $row.find('.inputValorunitario > input').val() === '' ? 0 : parseFloat($row.find('.inputValorunitario > input').val().replace(/\./g, '').replace(',', '.')) || 0;
				var valortotal = (qtde * vlunidade) - vldesconto;
				if (isNaN(valortotal)) {
					valortotal = 0;
				}
				$row.find('.inputValortotal > input').val(numberToReal(valortotal));
			}

			$(document).on('change', '#grid_table .jsgrid-insert-row td.inputTipo select', function(){
				var $row = osAddGridRowFromEl($(this));
				$row.find('input.input-codigo-val').val('');
				$row.find('.inputDescricao > input').val('');
				$row.find('.inputUnidade > input').val('');
				$row.find('.inputValorunitario > input').val('');
				$row.find('.inputQuantidade > input').val('');
				$row.find('.inputValortotal > input').val('');
				$row.find('.inputValordesconto > input').val('');
				$row.find('.inputSerialnumber > input').val('');
				$row.find('.inputObservacao > input').val('');
				$row.find('.inputModelo > input, .inputProductKey > input').val('');
				$('.qtdEstoque').text('');
				calculoAdd();
			});

			$(document).on('change', '#grid_table .jsgrid-insert-row .inputCodproduto input.input-codigo-val', function(){
				var cod = $.trim($(this).val() || '');
				var $row = osAddGridRowFromEl($(this));
				if (!cod) {
					return;
				}
				var tipoSel = osAddGridLinhaTipoSelecionado($row);
				if (!tipoSel || tipoSel <= 0) {
					if (typeof bootbox !== 'undefined') {
						bootbox.alert('<p class="text-center pgm-bootbox-msg-md">Selecione o tipo do item antes de informar o código.</p>');
					}
					$(this).val('');
					return;
				}
				$.ajax({
					url: osAddProdutoJsonUrlComTipo(cod, tipoSel),
					dataType: "json",
					success:function(data){
						if (!data || data.mensagem) {
							if (typeof bootbox !== 'undefined') {
								bootbox.alert('<p class="text-center pgm-bootbox-msg-md">' + osAddMsgNenhumItemTipo + '</p>');
							}
							osAddLimparCamposItemLinha($row, { isEdit: false });
							$row.find('input.input-codigo-val').val('');
							$('.qtdEstoque').text('');
							calculoAdd();
							return;
						}
						if (parseInt(data.tipo, 10) !== tipoSel) {
							if (typeof bootbox !== 'undefined') {
								bootbox.alert('<p class="text-center pgm-bootbox-msg-md">' + osAddMsgNenhumItemTipo + '</p>');
							}
							osAddLimparCamposItemLinha($row, { isEdit: false });
							$row.find('input.input-codigo-val').val('');
							$('.qtdEstoque').text('');
							calculoAdd();
							return;
						}
						if(data.tipo == <?= C_ProdutosTipoProduto ?>) {
							$row.find(".inputSerialnumber > input").prop('disabled', false);
							serialnumbers(data.codigo);
							$.ajax({
								url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']) ?>" + '/' + encodeURIComponent(data.codigo),
								success:function(qtd){ if(qtd != -999) $('.qtdEstoque').text('Qtd. em estoque: ' + qtd); },
							});
						} else {
							$('.qtdEstoque').text('⠀⠀⠀');
							$row.find(".inputSerialnumber > input").prop('disabled', true);
						}
						$row.find('.inputDescricao > input').val(data.descricao);
						$row.find('.inputUnidade > input').val(data.unidade);
						$row.find('.inputValorunitario > input').val(numberToReal(data.vlunitario));
						$row.find(".inputQuantidade > input").val("");
						$row.find(".inputValortotal > input").val("");
						$row.find(".inputValordesconto > input").val("");
						$row.find(".inputSerialnumber > input").val("");
						calculoAdd();
					},
					error: function (xhr) {
						var m = osAddMsgNenhumItemTipo;
						if (xhr && xhr.responseJSON && xhr.responseJSON.mensagem && String(xhr.responseJSON.mensagem).indexOf('Nenhum item') !== -1) {
							m = xhr.responseJSON.mensagem;
						}
						if (typeof bootbox !== 'undefined') {
							bootbox.alert('<p class="text-center pgm-bootbox-msg-md">' + m + '</p>');
						}
						osAddLimparCamposItemLinha($row, { isEdit: false });
						$row.find('input.input-codigo-val').val('');
						$('.qtdEstoque').text('');
						calculoAdd();
					}
				});
			});

			$(document).on('change', '#grid_table .jsgrid-insert-row .inputQuantidade > input', function(){ calculoAdd(); });
			$(document).on('change', '#grid_table .jsgrid-insert-row .inputValordesconto > input', function(){ calculoAdd(); });
			$(document).on('change', '#grid_table .jsgrid-insert-row .inputValorunitario > input', function(){ calculoAdd(); });

			$(document).on('change', '#grid_table .jsgrid-edit-row td.editTipo select, #grid_table .jsgrid-edit-row td.inputTipo select', function(){
				var $row = osAddGridRowFromEl($(this));
				$row.find('input.input-codigo-val').val('');
				$row.find('.editDescricao > input').val('');
				$row.find('.editUnidade > input').val('');
				$row.find('.editValorunitario > input').val('');
				$row.find('.editQuantidade > input').val('');
				$row.find('.editValortotal > input').val('');
				$row.find('.editValordesconto > input').val('');
				$row.find('.editSerialnumber > input').val('');
				$row.find('.editObservacao > input').val('');
				$row.find('.editModelo > input, .editProductKey > input').val('');
				$('.qtdEstoque').text('');
				calculoEdit();
			});

			$(document).on('change', '#grid_table .jsgrid-edit-row .inputCodproduto input.input-codigo-val', function(){
				var cod = $.trim($(this).val() || '');
				var $row = osAddGridRowFromEl($(this));
				if (!cod) {
					return;
				}
				var tipoSel = osAddGridLinhaTipoSelecionado($row);
				if (!tipoSel || tipoSel <= 0) {
					if (typeof bootbox !== 'undefined') {
						bootbox.alert('<p class="text-center pgm-bootbox-msg-md">Selecione o tipo do item antes de informar o código.</p>');
					}
					$(this).val('');
					return;
				}
				$.ajax({
					url: osAddProdutoJsonUrlComTipo(cod, tipoSel),
					dataType: "json",
					success:function(data){
						if (!data || data.mensagem) {
							if (typeof bootbox !== 'undefined') {
								bootbox.alert('<p class="text-center pgm-bootbox-msg-md">' + osAddMsgNenhumItemTipo + '</p>');
							}
							osAddLimparCamposItemLinha($row, { isEdit: true });
							$row.find('input.input-codigo-val').val('');
							$('.qtdEstoque').text('');
							calculoEdit();
							return;
						}
						if (parseInt(data.tipo, 10) !== tipoSel) {
							if (typeof bootbox !== 'undefined') {
								bootbox.alert('<p class="text-center pgm-bootbox-msg-md">' + osAddMsgNenhumItemTipo + '</p>');
							}
							osAddLimparCamposItemLinha($row, { isEdit: true });
							$row.find('input.input-codigo-val').val('');
							$('.qtdEstoque').text('');
							calculoEdit();
							return;
						}
						if(data.tipo == <?= C_ProdutosTipoProduto ?>) {
							$row.find(".inputSerialnumber > input, .editSerialnumber > input").prop('disabled', false);
							serialnumbers(data.codigo);
							$.ajax({
								url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']) ?>" + '/' + encodeURIComponent(data.codigo),
								success:function(qtd){ if(qtd != -999) $('.qtdEstoque').text('Qtd. em estoque: ' + qtd); },
							});
						} else {
							$('.qtdEstoque').text('⠀⠀⠀');
							$row.find(".inputSerialnumber > input, .editSerialnumber > input").prop('disabled', true);
						}
						$row.find('.editDescricao > input').val(data.descricao);
						$row.find('.editUnidade > input').val(data.unidade);
						$row.find('.editValorunitario > input').val(numberToReal(data.vlunitario));
						$row.find(".editQuantidade > input").val("");
						$row.find(".editValortotal > input").val("");
						$row.find(".editValordesconto > input").val("");
						$row.find(".editSerialnumber > input").val("");
						calculoEdit();
					},
					error: function (xhr) {
						var m = osAddMsgNenhumItemTipo;
						if (xhr && xhr.responseJSON && xhr.responseJSON.mensagem && String(xhr.responseJSON.mensagem).indexOf('Nenhum item') !== -1) {
							m = xhr.responseJSON.mensagem;
						}
						if (typeof bootbox !== 'undefined') {
							bootbox.alert('<p class="text-center pgm-bootbox-msg-md">' + m + '</p>');
						}
						osAddLimparCamposItemLinha($row, { isEdit: true });
						$row.find('input.input-codigo-val').val('');
						$('.qtdEstoque').text('');
						calculoEdit();
					}
				});
			});
		<?php }  ?>
	// Cálculo Edit
		$(document).on('change', '#grid_table .jsgrid-edit-row .editQuantidade > input', function(){ calculoEdit(); });
		$(document).on('change', '#grid_table .jsgrid-edit-row .editValordesconto > input', function(){ calculoEdit(); });
		$(document).on('change', '#grid_table .jsgrid-edit-row .editValorunitario > input', function(){ calculoEdit(); });
		function calculoEdit(){
			var $row = $('#grid_table .jsgrid-edit-row');
			if (!$row.length) return;
			var qtdeRaw = $row.find('.editQuantidade > input').val();
			var qtde = (qtdeRaw === undefined || qtdeRaw === '') ? 0 : parseFloat(String(qtdeRaw).replace(/\./g, '').replace(',', '.')) || 0;
			var vldescontoRaw = $row.find('.editValordesconto > input').val();
			var vldesconto = (vldescontoRaw === '' || vldescontoRaw === undefined) ? 0 : parseFloat(String(vldescontoRaw).replace(/\./g, '').replace(',', '.')) || 0;
			var vlunRaw = $row.find('.editValorunitario > input').val();
			var vlunidade = (vlunRaw === '' || vlunRaw === undefined) ? 0 : parseFloat(String(vlunRaw).replace(/\./g, '').replace(',', '.')) || 0;
			var valortotalLinha = qtde * vlunidade - vldesconto;
			if (isNaN(valortotalLinha)) {
				valortotalLinha = 0;
			}
			$row.find('.editValortotal > input').val(numberToReal(valortotalLinha));
		}

		window.targetRow = null;
        window.isEditMode = false;

		function osAddOpenRefModalFromBrowseTrigger($el) {
			var $row = $el.closest('tr');
			if (!$row.hasClass('jsgrid-row') && !$row.hasClass('jsgrid-alt-row')) {
				return;
			}
			window.targetRow = $row;
			window.isOsGridBrowseRef = true;
			window.isEditMode = false;
			var item = $row.data('JSGridItem');
			if (!item) {
				return;
			}
			$('#observacaomodal').val(item.observacao != null ? String(item.observacao) : '');
			$('#modelomodal').val(item.modelo != null ? String(item.modelo) : '');
			$('#serialnumbermodal').val(item.serialnumber != null ? String(item.serialnumber) : '');
			$('#productkeymodal').val(item.productkey != null ? String(item.productkey) : '');
			$('#observacainternaomodal').val(item.obsinterna != null ? String(item.obsinterna) : '');
			$('#modal-observacao').modal('show');
		}
		$(document).on('click', '.os-grid-ref-trigger', function (e) {
			e.preventDefault();
			e.stopPropagation();
			osAddOpenRefModalFromBrowseTrigger($(this));
		});
		$(document).on('keydown', '.os-grid-ref-trigger', function (e) {
			if (e.key === 'Enter' || e.which === 13) {
				e.preventDefault();
				osAddOpenRefModalFromBrowseTrigger($(this));
			}
		});

        $(document).on("click focus", ".inputObservacao > input, .editObservacao > input", function(e){
			window.isOsGridBrowseRef = false;
            window.isEditMode = $(this).parent().hasClass('editObservacao');
            window.targetRow = $(this).closest('tr');

            var currentObs = $(this).val();
            var currentModelo = "";
            var currentSerial = "";
			var currentProductkey = "";
			var currentObsInterna = "";

            if(window.isEditMode){
                currentModelo = window.targetRow.find('.editModelo input').val();
                currentSerial = window.targetRow.find('.editSerialnumber input').val();
                currentProductkey = window.targetRow.find('.editProductKey input').val();
                currentObsInterna = window.targetRow.find('.editObsInterna input').val();
            } else {
                currentModelo = window.targetRow.find('.inputModelo input').val();
                currentSerial = window.targetRow.find('.inputSerialnumber input').val();
				currentProductkey = window.targetRow.find('.inputProductKey input').val();
                currentObsInterna = window.targetRow.find('.inputObsInterna input').val();
            }

            $('#observacaomodal').val(currentObs);
            $('#modelomodal').val(currentModelo);
            $('#serialnumbermodal').val(currentSerial);
            $('#productkeymodal').val(currentProductkey);
            $('#observacainternaomodal').val(currentObsInterna);

            $('#modal-observacao').modal('show');
        });

        $(document).on("click", ".btn-observacao", function(e){ 
            e.preventDefault();
            
            var obs = $('#observacaomodal').val();
            var mod = $('#modelomodal').val();
            var sn  = $('#serialnumbermodal').val();
			var pk 	= $('#productkeymodal').val();
			var obsInt = $('#observacainternaomodal').val();

			if (window.isOsGridBrowseRef && window.targetRow && window.targetRow.length) {
				var cartItem = window.targetRow.data('JSGridItem');
				if (!cartItem) {
					window.isOsGridBrowseRef = false;
					$('#modal-observacao').modal('hide');
					return;
				}
				cartItem.observacao = obs;
				cartItem.modelo = mod;
				cartItem.serialnumber = sn;
				cartItem.productkey = pk;
				cartItem.obsinterna = obsInt;
				cartItem['idEmpresaAtual'] = getEmpresaAtual();
				$.ajax({
					type: "PUT",
					url: urlEdit,
					data: cartItem,
					success: function () {
						$(".qtdEstoque, .vazio").remove();
						window.isOsGridBrowseRef = false;
						$("#grid_table").jsGrid("loadData");
						$('#modal-observacao').modal('hide');
					},
					error: function (xhr) {
						pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível salvar os detalhes do item.'));
						$("#grid_table").jsGrid("loadData");
					}
				});
				return;
			}

            if(window.targetRow){
                if(window.isEditMode){
                    window.targetRow.find('.editObservacao input').val(obs).trigger('change');
                    window.targetRow.find('.editModelo input').val(mod).trigger('change');
                    window.targetRow.find('.editSerialnumber input').val(sn).trigger('change');
                    window.targetRow.find('.editProductKey input').val(pk).trigger('change');
                    window.targetRow.find('.editObsInterna input').val(obsInt).trigger('change');
                } else {
                    window.targetRow.find('.inputObservacao input').val(obs).trigger('change');
                    window.targetRow.find('.inputModelo input').val(mod).trigger('change');
                    window.targetRow.find('.inputSerialnumber input').val(sn).trigger('change');
                    window.targetRow.find('.inputProductKey input').val(pk).trigger('change');
                    window.targetRow.find('.inputObsInterna input').val(obsInt).trigger('change');
                }
            }
			window.isOsGridBrowseRef = false;
            $('#modal-observacao').modal('hide');
        });

        // Função auxiliar para carregar SN se necessário (mantida do original, ajustada se preciso)
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
	// TDs com mto texto (não ligar em .jsgrid-button: corre o insert/update do jsGrid e quebra a linha)
		$('#grid_table').on('click', 'th', function () { tdcommuitotexto(); });
		function tdcommuitotexto () {
			var i = 0;
			$('#grid_table .jsgrid-grid-body').find('.jsgrid-row > .jsgrid-cell, .jsgrid-alt-row > .jsgrid-cell').each(function() {
				var $c = $(this);
				if ($c.hasClass('cellInput') || $c.hasClass('jsgrid-control-field')) {
					return;
				}
				if ($c.find('.os-grid-ref-trigger, .os-grid-ref-pill, .os-grid-actions-cell, .os-grid-ro-plain, .os-grid-display-input, .btn-exapndemuitotexto').length) {
					return;
				}
				var full = $.trim($c.text());
				if (full.length <= 50) {
					return;
				}
				$c.attr('data-textointeiro', full);
				$c.html(full.substr(0, 49) + '... <div class="btn btn-sm btn-pgm btn-pgm-situacao btn-primary btn-exapndemuitotexto btn-tdexp-' + i + '"><i class="fa fa-search "></i></div>');
				$c.find('.btn-tdexp-' + i).attr('data-textointeiro', full);
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




		// Variável global para armazenar qual input está chamando o modal (Insert ou Edit)
		window.activeInputCode = null;

		// Função para buscar produtos (Enter no input ou clique no botão)
		$('#termo-pesquisa-produto').on('keypress', function (e) {
			if(e.which === 13) { buscarProdutos(); }
		});

		function buscarProdutos() {
			var termo = $('#termo-pesquisa-produto').val();
			var tbody = $('#resultado-pesquisa-produtos');
			tbody.html('<tr><td colspan="4" class="text-center">Buscando...</td></tr>');
			var tipoFiltro = window.osModalPesquisaTipo;
			var tipoFiltroOs = parseInt(String(tipoFiltro), 10);
			if (isNaN(tipoFiltroOs) || tipoFiltroOs <= 0) {
				tbody.html('<tr><td colspan="4" class="text-center text-warning">Selecione o tipo na linha da OS antes de pesquisar.</td></tr>');
				return;
			}

			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'pesquisar']);?>", 
				method: "GET",
				data: { termo: termo, tipo: tipoFiltroOs },
				dataType: "json",
				success: function(data) {
					tbody.empty();
					if (!Array.isArray(data)) {
						var msgInv = (data && data.mensagem) ? String(data.mensagem) : 'Resposta inválida da pesquisa.';
						tbody.html('<tr><td colspan="4" class="text-center text-danger">' + msgInv + '</td></tr>');
						return;
					}
					var filtrados = data.filter(function (prod) {
						return parseInt(prod.tipo, 10) === tipoFiltroOs;
					});
					if(filtrados.length > 0) {
						$.each(filtrados, function(index, prod) {
							var tipoLinha = parseInt(prod.tipo, 10);
							var precisaEstoque = (osTiposComEstoqueErp || []).indexOf(tipoLinha) !== -1;
							var tr = $('<tr>').attr('data-codigo', prod.codigo != null ? String(prod.codigo) : '').attr('data-tipo', prod.tipo != null ? String(prod.tipo) : '')
								.attr('data-estoque-status', precisaEstoque ? 'loading' : 'na');
							tr.append($('<td>').text(prod.codigo));
							tr.append($('<td>').text(prod.descricao));
							tr.append($('<td>').text('R$ ' + numberToReal(prod.vlunitario)));
							var btn = $('<button>').attr('type', 'button').addClass('btn btn-pgm btn-pgm-salvar btn-success btn-sm btn-os-modal-add').text('Adicionar à OS');
							btn.on('click', function (e) {
								e.preventDefault();
								e.stopPropagation();
								selecionarProduto(prod.codigo);
							});
							tr.append($('<td>').append(btn));
							tbody.append(tr);
						});
						osModalAplicarEstoqueLinhas(filtrados);
					} else {
						tbody.html('<tr><td colspan="4" class="text-center">' + osAddMsgNenhumItemTipo + '</td></tr>');
					}
				},
				error: function(xhr) {
					var msgErr = 'Erro ao buscar produtos.';
					if (xhr && xhr.responseJSON && xhr.responseJSON.mensagem) {
						msgErr = String(xhr.responseJSON.mensagem);
					}
					tbody.html('<tr><td colspan="4" class="text-center text-danger">' + msgErr + '</td></tr>');
				}
			});
		}

		function osModalTipoConsultaEstoque(tipo) {
			var t = parseInt(tipo, 10);
			if (isNaN(t)) {
				return false;
			}
			return (osTiposComEstoqueErp || []).indexOf(t) !== -1;
		}

		function osModalAplicarEstoqueLinhas(data) {
			if (!data || !data.length) {
				return;
			}
			if (!osEstoquesLoteUrl) {
				$('#resultado-pesquisa-produtos tr[data-estoque-status="loading"]').attr('data-estoque-status', 'err');
				return;
			}
			var cods = [];
			data.forEach(function (prod) {
				if (!osModalTipoConsultaEstoque(prod.tipo)) {
					return;
				}
				var c = (prod.codigo != null && prod.codigo !== '') ? String(prod.codigo).trim() : '';
				if (c && cods.indexOf(c) === -1) {
					cods.push(c);
				}
			});
			if (!cods.length) {
				return;
			}
			if (cods.length > 150) {
				cods = cods.slice(0, 150);
			}
			$.ajax({
				type: 'POST',
				url: osEstoquesLoteUrl,
				data: { codigos: cods.join(',') },
				dataType: 'json',
				success: function (map) {
					if (!map || typeof map !== 'object' || map.erro) {
						$('#resultado-pesquisa-produtos tr[data-estoque-status="loading"]').attr('data-estoque-status', 'err');
						return;
					}
					$('#resultado-pesquisa-produtos tr').each(function () {
						var $tr = $(this);
						var cod = ($tr.attr('data-codigo') || '').trim();
						if (!cod) {
							return;
						}
						$tr.removeClass('os-pesquisa-produto-sem-estoque');
						$tr.find('.btn-os-modal-add').prop('disabled', false);
						if (!osModalTipoConsultaEstoque($tr.attr('data-tipo'))) {
							return;
						}
						if (map[cod] === undefined || map[cod] === null) {
							$tr.attr('data-estoque-status', 'err');
							return;
						}
						var q = map[cod];
						if (q === 0) {
							$tr.addClass('os-pesquisa-produto-sem-estoque').attr('data-estoque-status', 'zero');
						} else if (q === -999 || (typeof q === 'number' && q < 0)) {
							$tr.attr('data-estoque-status', 'err');
						} else {
							$tr.attr('data-estoque-status', 'ok');
						}
					});
				},
				error: function () {
					$('#resultado-pesquisa-produtos tr[data-estoque-status="loading"]').attr('data-estoque-status', 'err');
				}
			});
		}

		function selecionarProduto(codigo) {
			var $tr = $('#resultado-pesquisa-produtos tr').filter(function () {
				return String($(this).attr('data-codigo')) === String(codigo);
			});
			var st = $tr.attr('data-estoque-status');
			if (st === 'loading') {
				if (typeof bootbox !== 'undefined') {
					bootbox.alert('Aguarde a consulta de estoque antes de adicionar o item.');
				} else {
					alert('Aguarde a consulta de estoque antes de adicionar o item.');
				}
				return;
			}
			if (st === 'zero') {
				if (typeof bootbox !== 'undefined') {
					bootbox.alert('Produto com estoque zerado no ERP. Inclusão permitida com alerta.');
				} else {
					alert('Produto com estoque zerado no ERP. Inclusão permitida com alerta.');
				}
			}
			if (st === 'err') {
				if (typeof bootbox !== 'undefined') {
					bootbox.alert('Não foi possível confirmar o estoque deste item. A inclusão não é permitida.');
				} else {
					alert('Não foi possível confirmar o estoque deste item. A inclusão não é permitida.');
				}
				return;
			}
			if(window.activeInputCode) {
				// 1. Define o valor no input do Grid
				window.activeInputCode.val(codigo);
				
				// 2. Fecha o modal
				$('#modal-pesquisa-produto').modal('hide');
				
				// 3. IMPORTANTE: Dispara o evento 'change' manualmente
				// O seu código original escuta "change" para preencher descrição, preço, etc.
				// Como o JSGrid não dispara change nativo ao alterar valor via código, forçamos.
				window.activeInputCode.trigger('change');
			}
		}

</script>
