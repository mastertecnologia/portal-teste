<?php
use App\Utility\PortalUi;
use Cake\Routing\Router;

$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
$orcListRoute = PortalUi::listRoute('orcamentos') ?? ['controller' => 'Orcamentos', 'action' => 'index'];
$versaoLbl = (string)($orcVersaoLabel ?? 'v1');
$margem = (array)($orcRevisaoMargem ?? ['subVenda' => 0, 'subCusto' => 0, 'lucro' => 0, 'margemPct' => 0, 'descontoAbs' => 0, 'totalLiquido' => 0]);
$brl = function ($v) {
	return 'R$ ' . number_format((float)$v, 2, ',', '.');
};
$nomeCliente = empty($orcamento->cliente->razaosocial) ? ($orcamento->cliente->nome ?? '—') : $orcamento->cliente->razaosocial;
$cliTipo = (int)($orcamento->cliente->tipo ?? 0);
$cliDocLbl = ($cliTipo === (int)C_ClientesTipoJuridica) ? 'CNPJ' : 'CPF';
$cliDocRaw = ($cliTipo === (int)C_ClientesTipoJuridica) ? ($orcamento->cliente->cnpj ?? '') : ($orcamento->cliente->cpf ?? '');
$cliDocFmt = function_exists('formatCnpjCpf') ? formatCnpjCpf($cliDocRaw) : (string)$cliDocRaw;
$validoateFmt = function_exists('pgm_format_date_br') ? pgm_format_date_br($orcamento->validoate ?? null) : (string)($orcamento->validoate ?? '—');
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
$descontoAbs = (float)($margem['descontoAbs'] ?? $orcDescontoAbs ?? 0);
$totalLiquido = (float)($margem['totalLiquido'] ?? $orcTotalLiquido ?? $margem['subVenda'] ?? 0);
$ai = (string)($orcAprovacaoInterna ?? C_OrcamentoAprovacaoInternaPendente);
$gerOk = ($ai === C_OrcamentoAprovacaoInternaAprovado);
$gerReprov = ($ai === C_OrcamentoAprovacaoInternaReprovado);
$envOk = $st >= (int)C_OrcamentoStatusEnviado;
$versoesLista = $orcVersoesLista ?? [];
$discValor = (float)($orcDescontoValor ?? 0);
$discTipo = (string)($orcDescontoTipo ?? 'pct');
$podeAprovar = !empty($orcPodeAprovarInterno);
$mostrarBotoesAi = $podeAprovar
	&& $ai === C_OrcamentoAprovacaoInternaPendente
	&& $st === (int)C_OrcamentoStatusPendente;

$versaoStatusBadge = function ($versaoEnt) {
	$vs = (int)$versaoEnt->status;
	if ($vs === (int)C_OrcamentoStatusEnviado) {
		return ['env', 'Enviado'];
	}
	if ($vs === (int)C_OrcamentoStatusAprovado) {
		return ['aprov', 'Aprovado'];
	}
	if ($vs === (int)C_OrcamentoStatusRecusado) {
		return ['recus', 'Recusado'];
	}

	return ['pend', 'Rascunho'];
};
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
				<?= $this->Html->link('← Editar proposta', ['action' => 'edit', $orcamento->id], ['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false, 'data-turbo' => 'false']) ?>
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
				<?php if ($versoesLista === []) : ?>
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
				<?php else : ?>
					<?php foreach ($versoesLista as $vEnt) :
						$vNum = max(1, (int)($vEnt->versao ?? 1));
						$vLbl = 'v' . $vNum;
						$isAtual = ((int)$vEnt->id === (int)$orcamento->id);
						$vAutor = ($vEnt->user && !empty($vEnt->user->name)) ? $vEnt->user->name : '—';
						$vData = $vEnt->created ? $vEnt->created->format('d/m/Y') : '—';
						list($vBadge, $vStLbl) = $versaoStatusBadge($vEnt);
						$titulo = $vNum === 1 ? 'Proposta original' : 'Revisão v' . $vNum;
						?>
						<div class="orc-version-item">
							<div class="orc-version-item-left">
								<span class="orc-version-badge <?= $isAtual ? 'orc-version-badge--current' : 'orc-v-old' ?>"><?= h($vLbl) ?><?= $isAtual ? ' — atual' : '' ?></span>
								<?php if ($isAtual) : ?>
									<span class="orc-version-title"><?= h($titulo) ?></span>
								<?php else : ?>
									<?= $this->Html->link(h($titulo), ['action' => 'view', $vEnt->id], ['class' => 'orc-version-title orc-version-link', 'escape' => false]) ?>
								<?php endif; ?>
							</div>
							<div class="orc-version-item-right">
								<span class="orc-version-meta"><?= h($vData) ?> · <?= h($vAutor) ?></span>
								<span class="orc-badge-status orc-badge-status--<?= h($vBadge) ?>"><?= h($vStLbl) ?></span>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<?php if (isset($role) && (int)$role === 0 && $st !== (int)C_OrcamentoStatusAprovado) : ?>
				<?= $this->Form->postLink(
					'<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" width="13" height="13" aria-hidden="true"><path d="M12 2H4a1 1 0 00-1 1v10a1 1 0 001 1h8a1 1 0 001-1V3a1 1 0 00-1-1z"/><line x1="8" y1="5" x2="8" y2="11"/><line x1="5" y1="8" x2="11" y2="8"/></svg> Criar nova versão (revisão)',
					['action' => 'novaversao', $orcamento->id],
					[
						'class' => 'btn btn-orc-form-secondary btn-orc-compact orc-btn-nova-versao',
						'escape' => false,
						'confirm' => 'Duplicar esta proposta como nova versão? O orçamento atual permanece no histórico.',
					]
				) ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="orc-revisao-cols">
		<div class="orc-revisao-col-left">
			<div class="card orc-premium-card-inner orc-card-mb-14">
				<div class="card-body">
					<div class="orc-sec-title">Dados do cliente</div>
					<div class="orc-kv-list">
						<div class="orc-kv-row"><span class="orc-kv-lbl">Cliente</span><span class="orc-kv-val"><?= h($nomeCliente) ?></span></div>
						<div class="orc-kv-row"><span class="orc-kv-lbl"><?= h($cliDocLbl) ?></span><span class="orc-kv-val"><?= h($cliDocFmt !== '' ? $cliDocFmt : '—') ?></span></div>
						<div class="orc-kv-row"><span class="orc-kv-lbl">Pagamento</span><span class="orc-kv-val"><?= !empty($orcamento->formapagamento) ? h($orcamento->formapagamento) : '—' ?></span></div>
						<div class="orc-kv-row"><span class="orc-kv-lbl">Válido até</span><span class="orc-kv-val orc-kv-val--amber"><?= h($validoateFmt !== '' ? $validoateFmt : '—') ?></span></div>
					</div>
				</div>
			</div>
			<div class="card orc-premium-card-inner orc-card-mb-14">
				<div class="card-body">
					<div class="orc-sec-title">Análise de margem</div>
					<div class="orc-margin-summary orc-margin-summary--2col">
						<div class="orc-margin-card">
							<div class="orc-margin-card-val"><?= h($brl($totalLiquido)) ?></div>
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
							<div class="orc-wf-dot <?= $gerOk ? 'orc-wf-ok' : ($gerReprov ? 'orc-wf-pend' : 'orc-wf-wait') ?>">
								<?php if ($gerOk) : ?><i class="fa fa-check"></i><?php else : ?><span class="orc-wf-dot-inner"></span><?php endif; ?>
							</div>
							<div class="orc-wf-body">
								<div class="orc-wf-title<?= !$gerOk && !$gerReprov ? ' orc-wf-title--muted' : '' ?>">Gerente comercial</div>
								<div class="orc-wf-sub">
									<?php if ($gerOk) : ?>
										Aprovado<?php if (!empty($orcamento->aprovacao_interna_em)) : ?> · <?= h(is_object($orcamento->aprovacao_interna_em) ? $orcamento->aprovacao_interna_em->format('d/m/Y') : (string)$orcamento->aprovacao_interna_em) ?><?php endif; ?>
									<?php elseif ($gerReprov) : ?>
										Reprovado<?php if (!empty($orcamento->aprovacao_interna_motivo)) : ?> — <?= h($orcamento->aprovacao_interna_motivo) ?><?php endif; ?>
									<?php else : ?>
										Pendente de verificação
									<?php endif; ?>
								</div>
							</div>
						</div>
						<div class="orc-workflow-step">
							<div class="orc-wf-dot <?= $envOk ? 'orc-wf-ok' : 'orc-wf-wait' ?>">
								<?php if ($envOk) : ?><i class="fa fa-check"></i><?php else : ?><span class="orc-wf-dot-inner"></span><?php endif; ?>
							</div>
							<div class="orc-wf-body">
								<div class="orc-wf-title<?= !$envOk ? ' orc-wf-title--muted' : '' ?>">Envio ao cliente</div>
								<div class="orc-wf-sub">
									<?php if ($envOk) : ?>
										Proposta enviada
									<?php elseif ($gerReprov) : ?>
										Aguardando correção após reprovação interna
									<?php elseif (!$gerOk) : ?>
										Aguardando aprovações internas
									<?php else : ?>
										Liberado para envio ao cliente
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
					<?php if ($mostrarBotoesAi) : ?>
						<div class="orc-wf-actions">
							<?= $this->Form->postLink(
								'✓ Aprovar (gerente)',
								['action' => 'aprovarInterno', $orcamento->id],
								['class' => 'btn btn-orc-premium-primary btn-orc-compact', 'escape' => false]
							) ?>
							<?= $this->Form->create(null, [
								'url' => ['action' => 'reprovarInterno', $orcamento->id],
								'class' => 'orc-wf-reprovar-form',
							]) ?>
								<input type="text" name="motivo" class="form-control orc-wf-motivo-inp" placeholder="Motivo (opcional)" maxlength="500" />
								<button type="submit" class="btn btn-orc-outline-danger btn-orc-compact">✗ Reprovar</button>
							<?= $this->Form->end() ?>
						</div>
					<?php elseif ($gerReprov && isset($role) && (int)$role === 0) : ?>
						<p class="orc-wf-hint-reprov">
							<?= $this->Html->link('Editar proposta', ['action' => 'edit', $orcamento->id], ['class' => 'orc-id-accent']) ?>
							para corrigir e solicitar nova aprovação interna.
						</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="card orc-premium-card-inner orc-card-mb-14">
		<div class="card-body">
			<div class="orc-sec-title">Itens da proposta</div>
			<div id="carrinho" class="orc-carrinho-slot"></div>

			<?php if (isset($role) && (int)$role === 0) : ?>
				<?= $this->Form->create(null, [
					'url' => ['action' => 'salvarDesconto', $orcamento->id],
					'class' => 'orc-discount-form-revisao',
				]) ?>
				<input type="hidden" name="redirect" value="view" />
				<div class="orc-discount-row">
					<span class="orc-discount-lbl">Desconto:</span>
					<input type="number" name="desconto_valor" id="disc-val-revisao" class="orc-discount-inp" value="<?= h((string)$discValor) ?>" min="0" step="0.01" />
					<select name="desconto_tipo" id="disc-tipo-revisao" class="orc-discount-select">
						<option value="pct"<?= $discTipo === 'pct' ? ' selected' : '' ?>>%</option>
						<option value="fix"<?= $discTipo === 'fix' ? ' selected' : '' ?>>R$</option>
					</select>
					<span class="orc-discount-lbl">| Desconto aplicado:</span>
					<span class="orc-discount-applied"><?= h($brl($descontoAbs)) ?></span>
					<button type="submit" class="btn btn-orc-outline-teal btn-orc-compact">Salvar desconto</button>
				</div>
				<?= $this->Form->end() ?>
			<?php else : ?>
				<div class="orc-discount-row">
					<span class="orc-discount-lbl">Desconto aplicado:</span>
					<span class="orc-discount-applied"><?= h($brl($descontoAbs)) ?></span>
				</div>
			<?php endif; ?>

			<div class="orc-tot-wrap">
				<div class="orc-tot-inner">
					<div class="orc-tot-l"><span>Subtotal</span><span><?= h($brl($margem['subVenda'] ?? 0)) ?></span></div>
					<div class="orc-tot-l"><span>Desconto</span><span class="orc-tot-rd">— <?= h($brl($descontoAbs)) ?></span></div>
					<div class="orc-tot-l"><span>Total geral</span><span class="orc-tot-g"><?= h($brl($totalLiquido)) ?></span></div>
				</div>
			</div>
		</div>
	</div>

	<div class="orc-footer-bar">
		<?= $this->Html->link('← Voltar', $orcListRoute, ['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false]) ?>
		<div class="orc-footer-bar-actions">
			<?php if (isset($role) && (int)$role === 0) : ?>
				<?= $this->Html->link('Pré-visualizar PDF', ['action' => 'imprimir', $orcamento->id], ['class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]) ?>
				<?php if ($gerOk || $st >= (int)C_OrcamentoStatusEnviado) : ?>
					<?= $this->Html->link('Avançar para assinatura →', ['action' => 'envioassinatura', $orcamento->id], ['class' => 'btn btn-orc-premium-primary btn-orc-compact', 'escape' => false]) ?>
				<?php endif; ?>
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
