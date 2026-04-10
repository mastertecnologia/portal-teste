<?php
  	use Cake\Routing\Router;
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
	$this->Html->script('/js/orcamentos', ['block' => true]);
	// Breadcumbs
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar Orçamento', [], ['class' => 'breadcrumb-item active']);

	$dval = pgm_format_date_br($orcamento['validoate'] ?? null);
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
		border-bottom: 1px solid #3d4554;
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
		color: #c4c9d1;
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
  --orc-teal-light: rgba(29, 158, 117, 0.15);
  --orc-teal-mid: #5cecc4;
  --orc-border: #3d4554;
  --orc-border-light: #4f5869;
  --orc-text: #e8eaed;
  --orc-text-muted: #9aa0a8;
  --orc-text-hint: #9aa0a8;
  --orc-bg-card: #1e2329;
  --orc-bg-surface: #262c35;
}
.orc-premium-form .orc-premium-card-inner,
.orc-premium-view .orc-premium-card-inner {
  border: 1px solid #3d4554 !important;
  box-shadow: 0 4px 20px rgba(0,0,0,.28) !important;
}
</style>
<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form">
	<!-- Cabeçalho -->
	<div class="orc-page-head">
		<div>
			<div class="orc-eyebrow">Módulo comercial</div>
			<div class="orc-form-crumb">
				<?= $this->Html->link('Orçamentos', ['action' => 'index'], ['escape' => false]) ?> › <span class="orc-form-crumb-current">Editar #<?= $orcamento->id ?></span>
			</div>
			<h1 class="orc-h1">
				Orçamento <span class="orc-id-accent">#<?= $orcamento->id ?></span>
				<?php if(!empty($orcamento->versao)): ?>
					<span class="badge orc-ver-pill">v<?= $orcamento->versao ?></span>
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
	<?= $this->Form->hidden('item_edit_id', ['id' => 'item_edit_id']); ?>

	<!-- Card: Dados do cliente -->
	<div class="card orc-premium-card-inner orc-card-mb-14">
		<div class="card-body">
			<div class="orc-sec-title">Dados do cliente</div>
			<div class="row">
				<div class="col-lg-6 col-md-12 col-sm-12">
					<label class="control-label">Cliente</label>
					<div class="orc-readonly-val">
						<?= empty($orcamento->cliente->razaosocial) ? $orcamento->cliente->nome : $orcamento->cliente->razaosocial ?>
					</div>
				</div>
				<div class="col-lg-3 col-md-6 col-sm-12">
					<label class="control-label">Pagamento</label>
					<?= $this->Form->control('formapagamento', [
						'type' => 'select',
						'options' => $orcFormaPagamentoOpcoes ?? [],
						'class' => 'form-control orc-native-select',
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
	<div class="card orc-premium-card-inner orc-card-mb-14">
		<div class="card-body">
			<div class="orc-sec-title">Status</div>
			<div class="orc-flex-mb-12">
				<span><?= orcamentoStatus($orcamento->status) ?></span>
				<?php if(isset($temordem) && $temordem != 'nao'): ?>
					<?= $this->Html->link(
						'<i class="fa fa-wrench"></i> OS Nº ' . $temordem,
						['controller' => 'Ordensservico', 'action' => 'edit', $temordem],
						['class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]
					); ?>
				<?php else: ?>
					<span class="orc-muted-12">Sem ordem de serviço vinculada</span>
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
						<div class="orc-wf-title<?= $orcamento->status < C_OrcamentoStatusEnviado ? ' orc-wf-title--muted' : '' ?>">Enviado ao cliente</div>
						<div class="orc-wf-sub"><?= $orcamento->status >= C_OrcamentoStatusEnviado ? 'Proposta enviada por e-mail' : 'Aguardando envio por e-mail' ?></div>
					</div>
					<?php if($orcamento->status < C_OrcamentoStatusEnviado): ?>
						<button type="button" class="btn btn-orc-outline-teal btn-orc-compact btn-email orc-btn-email-step">
							<i class="fa fa-paper-plane"></i> Enviar agora
						</button>
					<?php endif; ?>
				</div>
				<div class="orc-workflow-step">
					<div class="orc-wf-dot <?= $orcamento->status == C_OrcamentoStatusAprovado ? 'orc-wf-ok' : ($orcamento->status == C_OrcamentoStatusRecusado ? 'orc-wf-pend' : '') ?>">
						<?php if($orcamento->status == C_OrcamentoStatusAprovado): ?><i class="fa fa-check"></i>
						<?php elseif($orcamento->status == C_OrcamentoStatusRecusado): ?><i class="fa fa-times"></i>
						<?php else: ?><div class="orc-wf-dot-inner" role="presentation"></div><?php endif; ?>
					</div>
					<div class="orc-wf-body">
						<div class="orc-wf-title<?= !in_array($orcamento->status, [C_OrcamentoStatusAprovado, C_OrcamentoStatusRecusado]) ? ' orc-wf-title--muted' : '' ?>">Decisão do cliente</div>
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
	<div class="card orc-premium-card-inner orc-card-mb-14">
		<div class="card-body">
			<div class="orc-sec-title orc-sec-title--split">
				<span>Controle de versões</span>
				<?= $this->Html->link(
					'<i class="fa fa-plus"></i> Criar nova versão',
					['action' => 'novaversao', $orcamento->id],
					['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false]
				) ?>
			</div>
			<div class="orc-version-panel orc-version-panel-mb">
				<div class="orc-version-item">
					<div class="orc-flex-gap-10">
						<span class="orc-version-badge orc-v-current">v<?= !empty($orcamento->versao) ? $orcamento->versao : '1' ?> — atual</span>
						<span class="orc-version-lbl">Versão atual</span>
					</div>
					<div class="orc-flex-gap-8">
						<span class="orc-version-date"><?= $orcamento->created->format('d/m/Y') ?></span>
						<span><?= orcamentoStatus($orcamento->status) ?></span>
					</div>
				</div>
			</div>
			<div class="orc-hint-panel">
				<strong>Criar nova versão</strong> duplica o orçamento atual para ajustes sem perder o histórico. Cada versão mantém seus próprios itens, valores e status.
			</div>
		</div>
	</div>

	<!-- Card: Produtos e serviços / Itens (mesmo bloco que Novo orçamento) -->
	<div class="card orc-premium-card-inner orc-card-mb-14">
		<div class="card-body">
			<?= $this->element('orcamentos_secao_produtos_form', [
				'orcModo' => 'edit',
				'orcamento' => $orcamento,
				'role' => $role,
			]); ?>

			<?php if ($orcamento->status == C_OrcamentoStatusAprovado && $role == 0 && !empty($orcamento->ipaprovacao)) : ?>
				<div class="orc-alcada-block">
					<div class="orc-alcada-icon"><i class="fa fa-check"></i></div>
					<div>
						<div class="orc-alcada-kicker">Aprovado pelo cliente</div>
						<div class="orc-alcada-detail">
							IP: <?= $orcamento->ipaprovacao ?>
							&nbsp;·&nbsp; Navegador: <?= $orcamento->navegadoraprovacao ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div id="carrinho" class="m-t-10"></div>

			<?= $this->element('orcamentos_secao_produtos_rodape', [
				'orcModo' => 'edit',
				'orcamento' => $orcamento,
				'role' => $role,
			]); ?>
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

<!-- Catálogo (igual Novo orçamento) -->
<div class="orc-catalog-overlay" id="orc-catalog-overlay" onclick="if(event.target===this)$(this).removeClass('open');">
	<div class="orc-catalog-modal" onclick="event.stopPropagation();">
		<div class="orc-catalog-header">
			<div class="orc-catalog-header-text">
				<h2 class="orc-catalog-h2">Catálogo de produtos</h2>
				<p class="orc-catalog-sub">Clique para adicionar ao orçamento</p>
			</div>
			<button type="button" class="btn btn-orc-catalog-fechar" onclick="$('#orc-catalog-overlay').removeClass('open');" aria-label="Fechar">
				<i class="fa fa-times"></i> Fechar
			</button>
		</div>
		<div class="orc-catalog-search">
			<div class="orc-catalog-search-inner">
				<i class="fa fa-search orc-catalog-search-ic" aria-hidden="true"></i>
				<input type="text" id="orc-catalog-search-input" placeholder="Buscar produto, código ou descrição..." autocomplete="off" oninput="orcCatalogFilter(this.value)" />
			</div>
		</div>
		<div class="orc-catalog-body" id="orc-catalog-body">
			<div class="orc-catalog-loading">
				<i class="fa fa-spinner fa-spin"></i> Carregando catálogo...
			</div>
		</div>
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
	$('.btn-email').click(function(e){
		e.preventDefault();
		$('#modal-email').modal('toggle');
	});
	$(document).on('change', '.file-input', function() {
		var filesCount = $(this)[0].files.length;
		var $textContainer = $(this).prev();
		var fileName = $(this).val().split('\\').pop();
		if (filesCount === 1) {
			var fileName = $(this).val().split('\\').pop();
			$textContainer.text(fileName);
		} else $textContainer.text(filesCount + ' arquivos selecionados');
	});
</script>
<?= $this->element('orcamentos_form_shared_js', [
	'mode' => 'edit',
	'orcamentoId' => $orcamento->id,
	'clientesMetaJson' => '{}',
	'produtosCatalogoJson' => $produtosCatalogoJson ?? '[]',
]) ?>

