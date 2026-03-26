<?php
  	use Cake\Routing\Router;
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
	$this->Html->script('/js/orcamentos', ['block' => true]);
	// Breadcumbs
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar Orçamento', [], ['class' => 'breadcrumb-item active']);

	$dval = date_format(date_create($orcamento['validoate']), "d/m/Y");
	$orcamento['validoate'] = $dval;
?>
<style>
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
<style>
.orc-premium-wrap.orc-premium-form {
  --orc-teal-light: #e6faf4;
  --orc-teal-mid: #5cdbc0;
  --orc-border: #e5e4e0;
  --orc-border-light: #f0efec;
  --orc-text: #1a1a18;
  --orc-text-muted: #6b6a65;
  --orc-text-hint: #9a9890;
  --orc-bg-card: #ffffff;
  --orc-bg-surface: #f9f9f8;
}
.orc-premium-form .orc-premium-card-inner,
.orc-premium-view .orc-premium-card-inner {
  border: 1px solid #e5e4e0 !important;
  box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04) !important;
}
</style>
<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form">
	<!-- Cabeçalho -->
	<div class="orc-page-head">
		<div>
			<div class="orc-eyebrow">Módulo comercial</div>
			<div style="font-size:11px;color:var(--orc-text-muted,#6b6a65);margin-bottom:3px;">
				<?= $this->Html->link('Orçamentos', ['action' => 'index'], ['escape' => false]) ?> › <span style="color:#1d9e75;">Editar #<?= $orcamento->id ?></span>
			</div>
			<h1 class="orc-h1">
				Orçamento <span style="color:#1d9e75;">#<?= $orcamento->id ?></span>
				<?php if(!empty($orcamento->versao)): ?>
					<span class="badge" style="background:#1d9e75;color:#fff;font-family:monospace;font-size:10px;padding:3px 8px;border-radius:99px;letter-spacing:.3px;font-weight:700;">v<?= $orcamento->versao ?></span>
				<?php endif; ?>
				<span><?= orcamentoStatus($orcamento->status) ?></span>
			</h1>
		</div>
		<div class="orc-page-head-actions">
			<?= $this->Html->link(
				'<i class="fa fa-arrow-left"></i> Voltar',
				['action' => 'index'],
				['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false]
			) ?>
			<?= $this->Html->link(
				'<i class="fa fa-file-text-o"></i> Pré-visualizar PDF',
				['action' => 'imprimir', $orcamento->id],
				['class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]
			) ?>
		</div>
	</div>

	<?= $this->element('orcamentos_stepper') ?>

	<?= $this->Form->create($orcamento, ['class' => 'form-material']); ?>

	<!-- Card: Dados do cliente -->
	<div class="card orc-premium-card-inner" style="margin-bottom:14px;">
		<div class="card-body">
			<div class="orc-sec-title">Dados do cliente</div>
			<div class="row">
				<div class="col-lg-6 col-md-12 col-sm-12">
					<label class="control-label">Cliente</label>
					<div style="font-size:14px;font-weight:500;color:#1a1a18;padding:6px 0;">
						<?= empty($orcamento->cliente->razaosocial) ? $orcamento->cliente->nome : $orcamento->cliente->razaosocial ?>
					</div>
				</div>
				<div class="col-lg-3 col-md-6 col-sm-12">
					<label class="control-label">Pagamento</label>
					<?= $this->Form->control('formapagamento', [
						'type' => 'select',
						'options' => $orcFormaPagamentoOpcoes ?? [],
						'class' => 'form-control selectpicker',
						'label' => false,
						'id' => 'formapagamento',
						'empty' => false,
					]) ?>
				</div>
				<div class="col-lg-3 col-md-6 col-sm-12">
					<label class="control-label">Válido até</label>
					<?= $this->Form->text('validoate', ['class' => 'form-control datepicker', 'id' => 'validoate', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true, 'data-mask' => '99/99/9999']) ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Observações -->
	<div class="orc-obs-block">
		<div class="orc-sec-title">Observações</div>
		<label class="control-label" for="observacoes">Condições, prazos, garantias</label>
		<?= $this->Form->textarea('solicitacao', ['novalidate' => true, 'id' => 'observacoes', 'class' => 'form-control orc-obs-textarea', 'label' => false, 'rows' => 6, 'placeholder' => 'Condições, prazos, garantias...']) ?>
	</div>

	<!-- Card: Status + Fluxo de aprovação -->
	<div class="card orc-premium-card-inner" style="margin-bottom:14px;">
		<div class="card-body">
			<div class="orc-sec-title">Status</div>
			<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
				<span><?= orcamentoStatus($orcamento->status) ?></span>
				<?php if(isset($temordem) && $temordem != 'nao'): ?>
					<?= $this->Html->link(
						'<i class="fa fa-wrench"></i> OS Nº ' . $temordem,
						['controller' => 'Ordensservico', 'action' => 'edit', $temordem],
						['class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]
					); ?>
				<?php else: ?>
					<span style="font-size:12px;color:#9a9890;">Sem ordem de serviço vinculada</span>
				<?php endif; ?>
			</div>
			<div class="orc-wf-list">
				<div class="orc-workflow-step">
					<div class="orc-wf-dot orc-wf-ok"><i class="fa fa-check"></i></div>
					<div class="orc-wf-body">
						<div class="orc-wf-title">Criado</div>
						<div class="orc-wf-sub">Orçamento registrado no sistema</div>
					</div>
				</div>
				<div class="orc-workflow-step">
					<div class="orc-wf-dot <?= $orcamento->status >= C_OrcamentoStatusEnviado ? 'orc-wf-ok' : 'orc-wf-pend' ?>">
						<i class="fa fa-<?= $orcamento->status >= C_OrcamentoStatusEnviado ? 'check' : 'circle-o' ?>"></i>
					</div>
					<div class="orc-wf-body">
						<div class="orc-wf-title" <?= $orcamento->status < C_OrcamentoStatusEnviado ? 'style="color:#9a9890;"' : '' ?>>Enviado ao cliente</div>
						<div class="orc-wf-sub"><?= $orcamento->status >= C_OrcamentoStatusEnviado ? 'Proposta enviada por e-mail' : 'Aguardando envio por e-mail' ?></div>
					</div>
					<?php if($orcamento->status < C_OrcamentoStatusEnviado): ?>
						<button type="button" class="btn btn-orc-outline-teal btn-orc-compact btn-email" style="font-size:11px;margin-left:auto;">
							<i class="fa fa-paper-plane"></i> Enviar agora
						</button>
					<?php endif; ?>
				</div>
				<div class="orc-workflow-step">
					<div class="orc-wf-dot <?= $orcamento->status == C_OrcamentoStatusAprovado ? 'orc-wf-ok' : ($orcamento->status == C_OrcamentoStatusRecusado ? 'orc-wf-pend' : '') ?>">
						<?php if($orcamento->status == C_OrcamentoStatusAprovado): ?><i class="fa fa-check"></i>
						<?php elseif($orcamento->status == C_OrcamentoStatusRecusado): ?><i class="fa fa-times"></i>
						<?php else: ?><div style="width:7px;height:7px;border-radius:50%;background:#e5e4e0;"></div><?php endif; ?>
					</div>
					<div class="orc-wf-body">
						<div class="orc-wf-title" <?= !in_array($orcamento->status, [C_OrcamentoStatusAprovado, C_OrcamentoStatusRecusado]) ? 'style="color:#9a9890;"' : '' ?>>Decisão do cliente</div>
						<div class="orc-wf-sub">
							<?php if($orcamento->status == C_OrcamentoStatusAprovado): ?>
								Aprovado<?php if(!empty($orcamento->ipaprovacao)): ?> · IP: <?= $orcamento->ipaprovacao ?><?php endif; ?>
							<?php elseif($orcamento->status == C_OrcamentoStatusRecusado): ?>
								Recusado pelo cliente
							<?php else: ?>
								Aguardando resposta
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Card: Controle de versões -->
	<div class="card orc-premium-card-inner" style="margin-bottom:14px;">
		<div class="card-body">
			<div class="orc-sec-title orc-sec-title--split">
				<span>Controle de versões</span>
				<?= $this->Html->link(
					'<i class="fa fa-plus"></i> Criar nova versão',
					['action' => 'novaversao', $orcamento->id],
					['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false]
				) ?>
			</div>
			<div class="orc-version-panel" style="margin-bottom:12px;">
				<div class="orc-version-item">
					<div style="display:flex;align-items:center;gap:10px;">
						<span class="orc-version-badge orc-v-current">v<?= !empty($orcamento->versao) ? $orcamento->versao : '1' ?> — atual</span>
						<span style="font-size:12px;font-weight:500;color:#1a1a18;">Versão atual</span>
					</div>
					<div style="display:flex;align-items:center;gap:8px;">
						<span style="font-size:11px;color:#9a9890;"><?= $orcamento->created->format('d/m/Y') ?></span>
						<span><?= orcamentoStatus($orcamento->status) ?></span>
					</div>
				</div>
			</div>
			<div style="padding:10px 12px;background:#f9f9f8;border-radius:8px;border:1px solid #f0efec;font-size:11px;color:#6b6a65;line-height:1.6;">
				<strong style="color:#1a1a18;">Criar nova versão</strong> duplica o orçamento atual para ajustes sem perder o histórico. Cada versão mantém seus próprios itens, valores e status.
			</div>
		</div>
	</div>

	<!-- Card: Produtos e serviços / Itens -->
	<div class="card orc-premium-card-inner" style="margin-bottom:14px;">
		<div class="card-body">
			<?php if($orcamento->status != C_OrcamentoStatusAprovado && $role == 0): ?>
				<div class="orc-sec-title">Produtos e serviços</div>
				<div class="row">
					<div class="col-lg-2 col-md-12">
						<label class="control-label">Código</label>
						<?= $this->Form->control('idproduto', ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $produtos, 'value' => 0, 'label' => false]) ?>
					</div>
					<div class="col-lg-5 col-md-12">
						<div class="form-group">
							<label class="control-label">Produto/Serviço</label>
							<?= $this->Form->control('servico', ['class' => 'form-control', 'label' => false]) ?>
							<small class="qtdEstoque text-muted"></small>
						</div>
					</div>
					<div class="col-lg-1 col-md-6">
						<div class="form-group">
							<label class="control-label">Tipo</label>
							<?= $this->Form->control('tipo', ['class' => 'quantidade form-control', 'options' => ['Unidade', 'Hora'], 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-1 col-md-6">
						<div class="form-group">
							<label class="control-label">Qtde.</label>
							<?= $this->Form->control('quantidade', ['onkeypress' => 'return SomenteNumero(event, "#quantidade")', 'class' => 'quantidade form-control', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-1 col-md-6">
						<div class="form-group">
							<label class="control-label">Vl. Mensal</label>
							<?= $this->Form->control('valormensal', ['onkeypress' => 'return SomenteNumero(event, "#valormensal")', 'class' => 'mensal form-control mascaramonetaria', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-1 col-md-6">
						<div class="form-group">
							<label class="control-label">Vl. Unitário</label>
							<?= $this->Form->control('valoruni', ['onkeypress' => 'return SomenteNumero(event, "#valoruni")', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-1 col-md-12">
						<div class="form-group">
							<label class="control-label">Vl. Total</label>
							<?= $this->Form->control('valordoservico', ['class' => 'form-control', 'label' => false, 'disabled' => true]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group">
							<label class="control-label">Descrição adicional</label>
							<?= $this->Form->control('observacao', ['class' => 'form-control', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<button type="button" class="orc-add-row" id="btn-addservico">
					<i class="fa fa-plus orc-add-row-ic"></i> Adicionar item
				</button>
			<?php else: ?>
				<div class="orc-sec-title">Itens do orçamento</div>
			<?php endif; ?>

			<?php if($orcamento->status == C_OrcamentoStatusAprovado && $role == 0 && !empty($orcamento->ipaprovacao)): ?>
				<div class="orc-alcada-block">
					<div class="orc-alcada-icon"><i class="fa fa-check"></i></div>
					<div>
						<div style="font-size:12px;font-weight:600;color:#0f6e56;margin-bottom:3px;">Aprovado pelo cliente</div>
						<div style="font-size:11px;color:#1d9e75;">
							IP: <?= $orcamento->ipaprovacao ?>
							&nbsp;·&nbsp; Navegador: <?= $orcamento->navegadoraprovacao ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div id="carrinho" class="m-t-10"></div>
		</div>
	</div>

	<!-- Footer de ações -->
	<div class="orc-footer-bar">
		<div class="orc-footer-bar-actions">
		</div>
		<div class="orc-footer-bar-actions">
			<?= $this->Html->link(
				'<i class="fa fa-print"></i> Imprimir',
				['action' => 'imprimir', $orcamento->id],
				['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false]
			) ?>
			<?= $this->Html->link(
				'<i class="fa fa-file-pdf-o"></i> PDF',
				['action' => 'imprimirPdf', $orcamento->id],
				['class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]
			) ?>
			<?= $this->Html->link(
				'<i class="fa fa-envelope"></i> E-mail',
				['#'],
				['class' => 'btn btn-orc-outline-purple btn-orc-compact btn-email', 'escape' => false]
			) ?>
			<?php if(in_array($orcamento->status, [C_OrcamentoStatusPendente, C_OrcamentoStatusEnviado])): ?>
				<?= $this->Form->button(
					'<i class="fa fa-save"></i> Salvar alterações',
					['class' => 'btn btn-orc-premium-primary btn-orc-compact', 'escape' => false]
				) ?>
			<?php endif; ?>
		</div>
	</div>

	<?= $this->Form->end(); ?>
</div>
</div>
<!-- Modal Email -->
<div class="modal fade none-border" id="modal-email">
	<div class="modal-dialog orc-premium-wrap orc-premium-form">
		<div class="modal-content">
			<div class="row m-20">
				<div class="col-12">
					<?= $this->Form->create(null, ['url' => ['controller' => 'Orcamentos', 'action' => 'email'], 'enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material']); //floating-labels?> 
						<div class="form-group">
							<label class="control-label text-muted">Destinatário</label>
							<?= $this->Form->control('emailemail', ['id' => 'emailemail', 'value' => $orcamento->cliente->email, 'class' => 'form-control', 'label' => false, 'required' => true]) ?>
							<br>
							<label class="control-label text-muted">Adicionar Anexo</label>
							<div class="bg">
								<div class="file-drop-area">
									<span class="fake-btn text-muted">Escolha o(s) arquivo(s) ou arraste-o(s) aqui</span>
									<input multiple class="file-input form-control"  name="file[]" id="file" type="file">
								</div>
							</div>
							<br>
							<?= $this->Form->control('idorcamento', ['value' => $orcamento->id, 'label' => false, 'type' => 'hidden']) ?>
						</div>
						<?= $this->Form->button('Enviar', ['class' => 'btn btn-orc-premium-primary btn-orc-compact float-right m-l-10']) ?>
						<button type="button" class="btn btn-orc-form-secondary btn-orc-compact float-right" data-dismiss="modal">Fechar</button>
					<?= $this->Form->end(); ?>
				</div>
			</div>
			<div class="modal-footer">
			</div>
		</div>
	</div>
</div>
<script>
	// Carrinho
		carrinho();
		function carrinho(){
			$.ajax({
				url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'carrinhoedit']);?>/" + <?= $orcamento->id ?>,
				dataType: "html",
				success : function(data) {
					$("#carrinho").html(data);
					$("#carrinho").fadeIn();
					$('#valormensal-modal, #valoruni-modal').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
				},
				error : function(error) { alert(error);}
			});
		}
		$(document).on('shown.bs.modal', '#modal-edit-item', function() {
			$('#valormensal-modal, #valoruni-modal').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
		});
		$(document).on('input focus', '#valormensal-modal, #valoruni-modal', function() {
			$(this).prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
		});

	// Limpa Session
		$(window).bind('beforeunload', function(){
			$.ajax({ url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'limpasession']);?>" });
		});

	// E-mail
		$('.btn-email').click(function(e){
			e.preventDefault();
			$('#modal-email').modal('toggle');
		});
	// Files
		$(document).on('change', '.file-input', function() {
			var filesCount = $(this)[0].files.length;
			var $textContainer = $(this).prev();
			var fileName = $(this).val().split('\\').pop();
			if (filesCount === 1) {
				var fileName = $(this).val().split('\\').pop();
				$textContainer.text(fileName);
			} else $textContainer.text(filesCount + ' arquivos selecionados');
		});
	// Só numero
		function SomenteNumero(e, campo){
			var tecla=(window.event)?event.keyCode:e.which;  

			if((tecla>47 && tecla<58)) return true;
			else if (tecla==8 || tecla==0) return true;
			else if (tecla == 46)  return false;    
			else if( $(campo).val().indexOf(',') > -1 && tecla == 44 ) return false
			else if( $(campo).val().indexOf(',') <= -1 && tecla == 44 ) return true
			else  return false;
		}
	// Produto
		$('#idproduto').change(function(e){
			if( $(this).val() != 0){
				$('#valoruni').attr('disabled', true);
				$('.mensal').attr('disabled', true);
				$.ajax({
					type: "post",
					url: "<?= Router::url(['controller'=>'Produtos','action'=>'produto']);?>/" + $(this).val(),
					dataType: "json",
					success: function(data){
						if (data.mensagem) {
							$('#servico').val('');
							$('#valoruni').val('');
							$('.qtdEstoque').text(data.mensagem).show();
							$('#valoruni').prop('disabled', false);
							$('.mensal').prop('disabled', false);
							return;
						}
						$('#servico').val((data.descricao || '').toString().trim());
						$('#quantidade').val('');
						$('#valordoservico').val('');
						if(data.tipo == <?= C_ProdutosTipoServico ?>) {
							$('#valormensal').prop('disabled', false);
							$('#valoruni').prop('disabled', false);
							$('#valormensal').val('');
							$('#valoruni').val(numberToReal(data.vlunitario));
							$('#tipo').val(1);
							$('#quantidade').mask('99:99');
							$('.qtdEstoque').hide();
						} else if (data.tipo == <?= C_ProdutosTipoProduto  ?>) {
							$('#valormensal').prop('disabled', 'disabled');
							$('#valoruni').prop('disabled', false);
							$('#valoruni').val(numberToReal(data.vlunitario));
							$('#valormensal').val('');
							$('#tipo').val(0);
							$('#quantidade').mask('0000000');
							$.ajax({
								type:"post",
								url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']);?>/" + data.codigo,
								dataType: "json",
								success:function(qtdestoque) {
									var msg = (qtdestoque === -999 || qtdestoque === null || (typeof qtdestoque === 'number' && qtdestoque < 0))
										? 'Estoque: indisponível (consulte o ERP)'
										: ('Qtd. em estoque: ' + qtdestoque);
									$('.qtdEstoque').text(msg).show();
								},
								error: function() { $('.qtdEstoque').text('Estoque: indisponível').show(); }
							});
						} else {
							$('#valormensal').prop('disabled', false);
							$('#valoruni').prop('disabled', false);
							$('#valormensal').val(numberToReal(data.vlunitario));
							$('#valoruni').val(numberToReal(data.vlunitario));
							$('#tipo').val(0);
							$('#quantidade').mask('0000000');
							$('.qtdEstoque').hide();
						}
					},
					error: function (xhr) {
						var msg = 'Produto/serviço não encontrado.';
						if (xhr.responseJSON && xhr.responseJSON.mensagem) msg = xhr.responseJSON.mensagem;
						$('.qtdEstoque').text(msg).show();
						$('#valoruni').val('').prop('disabled', false);
						$('.mensal').prop('disabled', false);
					}
				});
			}else{
				$('#servico').val('');
				$('#valoruni').val('');
				$('#valoruni').attr('disabled', false);
				$('.mensal').attr('disabled', false);
			}
		});

	// Tipo
		$('#tipo').change(function(){
			if($(this).val() == 1) $('#quantidade').mask('99:99');
			else $('#quantidade').mask('0000000');
		});
	// Valores
		$('#valoruni').keydown(function(){
			valor = $(this).val() .replaceAll('.', '').replaceAll(',', '.');
			if(valor > 0) $('#valormensal').val('');
		});

		$('#valormensal').keydown(function(){
			valor = $(this).val() .replaceAll('.', '').replaceAll(',', '.');
			if(valor > 0) $('#valoruni').val('');
		});
	// Cálculos
		$('#quantidade, #valoruni, #idproduto').keyup(function(e){
			if( $('#quantidade').val().indexOf(':') > -1  ) {
				quantidadeArray = $('#quantidade').val().split(':');
				quantidade =( parseFloat(quantidadeArray[0]) + ( parseFloat(quantidadeArray[1]) / 6 / 10 )).toFixed(2);
			}else quantidade = $('#quantidade').val().replaceAll('.', '').replaceAll(',', '.')
			
			valoruni = $('#valoruni').val().replaceAll('.', '').replaceAll(',', '.')
			valormensal = $('#valormensal').val().replaceAll('.', '').replaceAll(',', '.')
			valor = 0;
			if(valoruni != '') valor = valoruni;
			//else valor = valormensal;
			if(quantidade > 0 && valor){
				valortotal = quantidade * valor;
				$('#valordoservico').val(numberToReal(valortotal));
			}
			else $('#valordoservico').val('');
		});

	// Add Serviço
		$('#btn-addservico').click(function(e){
			e.preventDefault();
			servico =       $('#servico').val();
			quantidade =	$('#quantidade').val();
			valoruni =      $('#valoruni').val();
			valordoservico= $('#valordoservico').val();
			observacao = 	$('#observacao').val();
			valormensal = 	$('#valormensal').val();
			idproduto =		$('#idproduto').val();
			tipo =			$('#tipo').val();

			if(servico == ''){
				bootbox.alert('Preencha o campo "Descrição".');
				return false;
			}

			if(quantidade == '' || (valoruni == '' && valormensal == '')){
				bootbox.alert('Preencha o campo "Quantidade" e o campo de valor respectivo.');
				return false;
			}

			if(valoruni == '') valoruni = 0;
			if(valormensal == '') valormensal = 0;
			
			$.ajax({
				url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'addservico']);?>/edit",
				dataType: "html",
				type: 'POST',
				data: { servico: servico, quantidade: quantidade, valoruni: valoruni, valordoservico: valordoservico, observacao: observacao, valormensal: valormensal, idproduto: idproduto, tipo : tipo},
				success : function(data) {
					console.log(data);
					if(data == 'nao pode'){
						bootbox.alert('O serviço já está no carrinho');
						return false;
					}
					carrinho();
					$('#servico').val('');
					$('#quantidade').val('');
					$('#valoruni').val('');
					$('#valordoservico').val('');
					$('#observacao').val('');
					$('#valormensal').val('');
					$('#idproduto').val(0);
					$('#tipo').val(0);
					$('#idproduto').selectpicker('refresh');
					$('.qtdEstoque').text('').hide();
					$('#valormensal').attr('disabled', false);
					$('#valoruni').attr('disabled', false);
					$('#servico').focus();
				},
				error : function(xhr) {
					var msg = 'Erro ao adicionar item. Tente novamente.';
					if (xhr.responseJSON && xhr.responseJSON.mensagem) msg = xhr.responseJSON.mensagem;
					else if (xhr.responseText && xhr.responseText.length < 200) msg = xhr.responseText;
					if (typeof bootbox !== 'undefined') bootbox.alert(msg); else alert(msg);
				}
			});
		});

	// Double Submit
		jQuery.fn.preventDoubleSubmission = function() {
			$(this).on('submit',function(e){
				var $form = $(this);
				if ($form.data('submitted') === true) {
					e.preventDefault();
				} else {
					$form.data('submitted', true);
				}
			});
			return this;
		};

		$('form').preventDoubleSubmission();
	// 
</script>