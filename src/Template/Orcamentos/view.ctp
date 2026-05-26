<?php
use App\Utility\PortalUi;
use Cake\Routing\Router;

$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
$orcListRoute = PortalUi::listRoute('orcamentos') ?? ['controller' => 'Orcamentos', 'action' => 'index'];
$versaoLbl = (string)($orcVersaoLabel ?? 'v1');
$margem = (array)($orcRevisaoMargem ?? ['subVenda' => 0, 'subCusto' => 0, 'lucro' => 0, 'margemPct' => 0]);
$brl = function ($v) {
	return 'R$ ' . number_format((float)$v, 2, ',', '.');
};
$nomeCliente = empty($orcamento->cliente->razaosocial) ? ($orcamento->cliente->nome ?? '—') : $orcamento->cliente->razaosocial;
$autorNome = ($orcamento->user && !empty($orcamento->user->name)) ? $orcamento->user->name : '—';
$createdFmt = $orcamento->created ? $orcamento->created->format('d/m/Y') : '—';
$st = (int)$orcamento->status;
$stBadge = 'pend';
$stLbl = 'Rascunho';
if ($st === (int)C_OrcamentoStatusEnviado) { $stBadge = 'env'; $stLbl = 'Enviado'; }
elseif ($st === (int)C_OrcamentoStatusAprovado) { $stBadge = 'aprov'; $stLbl = 'Aprovado'; }
elseif ($st === (int)C_OrcamentoStatusRecusado) { $stBadge = 'recus'; $stLbl = 'Recusado'; }
$margemPct = (int)($margem['margemPct'] ?? 0);
$margemColor = $margemPct > 30 ? 'var(--orc-teal)' : ($margemPct > 15 ? 'var(--orc-amber)' : 'var(--orc-red)');
?>
<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form orc-premium-view orc-revisao-page">

	<div class="orc-page-head">
		<div>
			<div class="orc-form-crumb">
				<?= $this->Html->link('Orçamentos', $orcListRoute, ['escape' => false]) ?> › <?= $this->Html->link('Novo', ['action' => 'add'], ['escape' => false]) ?> › <span class="orc-form-crumb-current">Revisão</span>
			</div>
			<h1 class="orc-h1">
				Revisão — <span class="orc-id-accent">#<?= (int)$orcamento->id ?></span> <span class="orc-id-accent"><?= h($versaoLbl) ?></span>
			</h1>
		</div>
		<div class="orc-page-head-actions">
			<?php if (isset($role) && (int)$role === 0) : ?>
				<?= $this->Html->link('← Editar', ['action' => 'edit', $orcamento->id], ['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false]) ?>
				<?= $this->Html->link('Pré-visualizar PDF', ['action' => 'imprimir', $orcamento->id], ['class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]) ?>
				<?= $this->Html->link('Gerar e assinar →', ['action' => 'envioassinatura', $orcamento->id], ['class' => 'btn btn-orc-premium-primary btn-orc-compact', 'escape' => false]) ?>
			<?php endif; ?>
		</div>
	</div>

	<?= $this->element('orcamentos_stepper') ?>

	<div class="card orc-premium-card-inner orc-card-mb-14">
		<div class="card-body">
			<div class="orc-sec-title">Controle de versões</div>
			<div class="orc-version-panel">
				<div class="orc-version-item">
					<div class="orc-version-item-left">
						<span class="orc-version-badge orc-version-badge--current"><?= h($versaoLbl) ?> — atual</span>
						<span class="orc-version-title">Proposta original</span>
					</div>
					<div class="orc-version-item-right">
						<span class="orc-version-meta"><?= h($createdFmt) ?> · <?= h($autorNome) ?></span>
						<span class="orc-badge-status orc-badge-status--<?= h($stBadge) ?>"><?= h($stLbl) ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="orc-revisao-cols">
		<div class="orc-revisao-col-left">
			<div class="card orc-premium-card-inner orc-card-mb-14">
				<div class="card-body">
					<div class="orc-sec-title">Dados do cliente</div>
					<div class="orc-kv-list">
						<div class="orc-kv-row"><span class="orc-kv-lbl">Cliente</span><span class="orc-kv-val"><?= h($nomeCliente) ?></span></div>
						<div class="orc-kv-row"><span class="orc-kv-lbl">Pagamento</span><span class="orc-kv-val"><?= !empty($orcamento->formapagamento) ? h($orcamento->formapagamento) : '—' ?></span></div>
						<div class="orc-kv-row"><span class="orc-kv-lbl">Válido até</span><span class="orc-kv-val orc-kv-val--amber"><?= h($orcamento->validoate) ?></span></div>
					</div>
				</div>
			</div>
			<div class="card orc-premium-card-inner orc-card-mb-14">
				<div class="card-body">
					<div class="orc-sec-title">Análise de margem</div>
					<div class="orc-margin-summary orc-margin-summary--2col">
						<div class="orc-margin-card">
							<div class="orc-margin-card-val"><?= h($brl($margem['subVenda'] ?? 0)) ?></div>
							<div class="orc-margin-card-lbl">Total venda</div>
						</div>
						<div class="orc-margin-card">
							<div class="orc-margin-card-val orc-margin-card-val--teal"><?= h($brl($margem['lucro'] ?? 0)) ?></div>
							<div class="orc-margin-card-lbl">Lucro bruto</div>
						</div>
						<div class="orc-margin-card">
							<div class="orc-margin-card-val orc-margin-card-val--muted"><?= h($brl($margem['subCusto'] ?? 0)) ?></div>
							<div class="orc-margin-card-lbl">Custo total</div>
						</div>
						<div class="orc-margin-card">
							<div class="orc-margin-card-val" style="color:<?= h($margemColor) ?>;"><?= (int)$margemPct ?>%</div>
							<div class="orc-margin-card-lbl">Margem bruta</div>
							<div class="orc-margin-bar"><div class="orc-margin-fill" style="--orc-margin-pct:<?= min(100, max(0, $margemPct)) ?>%;"></div></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="orc-revisao-col-right">
			<div class="card orc-premium-card-inner orc-card-mb-14">
				<div class="card-body">
					<div class="orc-sec-title">Workflow de aprovação interna</div>
					<div class="orc-wf-list">
						<div class="orc-workflow-step">
							<div class="orc-wf-dot orc-wf-ok"><i class="fa fa-check"></i></div>
							<div class="orc-wf-body">
								<div class="orc-wf-title">Vendedor</div>
								<div class="orc-wf-sub"><?= h($autorNome) ?> · <?= h($createdFmt) ?> · Criado</div>
							</div>
						</div>
						<div class="orc-workflow-step">
							<div class="orc-wf-dot <?= $st >= (int)C_OrcamentoStatusEnviado ? 'orc-wf-ok' : 'orc-wf-wait' ?>">
								<?php if ($st >= (int)C_OrcamentoStatusEnviado) : ?><i class="fa fa-check"></i><?php else : ?><span class="orc-wf-dot-inner"></span><?php endif; ?>
							</div>
							<div class="orc-wf-body">
								<div class="orc-wf-title<?= $st < (int)C_OrcamentoStatusEnviado ? ' orc-wf-title--muted' : '' ?>">Gerente comercial</div>
								<div class="orc-wf-sub"><?= $st >= (int)C_OrcamentoStatusEnviado ? 'Verificado' : 'Pendente de verificação' ?></div>
							</div>
						</div>
						<div class="orc-workflow-step">
							<div class="orc-wf-dot <?= $st >= (int)C_OrcamentoStatusEnviado ? 'orc-wf-ok' : 'orc-wf-wait' ?>">
								<?php if ($st >= (int)C_OrcamentoStatusEnviado) : ?><i class="fa fa-check"></i><?php else : ?><span class="orc-wf-dot-inner"></span><?php endif; ?>
							</div>
							<div class="orc-wf-body">
								<div class="orc-wf-title<?= $st < (int)C_OrcamentoStatusEnviado ? ' orc-wf-title--muted' : '' ?>">Envio ao cliente</div>
								<div class="orc-wf-sub"><?= $st >= (int)C_OrcamentoStatusEnviado ? 'Proposta enviada' : 'Aguardando aprovações internas' ?></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="card orc-premium-card-inner orc-card-mb-14">
		<div class="card-body">
			<div class="orc-sec-title">Itens da proposta</div>
			<div id="carrinho" class="orc-carrinho-slot"></div>
			<div class="orc-tot-wrap">
				<div class="orc-tot-inner">
					<div class="orc-tot-l"><span>Subtotal</span><span><?= h($brl($margem['subVenda'] ?? 0)) ?></span></div>
					<div class="orc-tot-l"><span>Desconto</span><span class="orc-tot-rd">— <?= h($brl(0)) ?></span></div>
					<div class="orc-tot-l"><span>Total geral</span><span class="orc-tot-g"><?= h($brl($margem['subVenda'] ?? 0)) ?></span></div>
				</div>
			</div>
		</div>
	</div>

	<div class="orc-footer-bar">
		<?= $this->Html->link('← Voltar', $orcListRoute, ['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false]) ?>
		<div class="orc-footer-bar-actions">
			<?php if (isset($role) && (int)$role === 0) : ?>
				<?= $this->Html->link('Pré-visualizar PDF', ['action' => 'imprimir', $orcamento->id], ['class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]) ?>
				<?= $this->Html->link('Avançar para assinatura →', ['action' => 'envioassinatura', $orcamento->id], ['class' => 'btn btn-orc-premium-primary btn-orc-compact', 'escape' => false]) ?>
			<?php endif; ?>
		</div>
	</div>

</div>
</div>
<script>
	carrinho();
	function carrinho(){
		$.ajax({
			type: "POST",
			url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'carrinhoedit']);?>/" + <?= (int)$orcamento->id ?> + "?layout=revisao",
			dataType: "html",
			success : function(data) { $("#carrinho").html(data); },
			error : function(error) { alert(error); }
		});
	}
</script>
