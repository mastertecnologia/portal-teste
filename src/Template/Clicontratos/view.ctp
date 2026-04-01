<?php
	$this->Breadcrumbs->add('Cliente', ['controller' => 'Clientes', 'action' => 'edit', $idcliente], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Detalhe do contrato', [], ['class' => 'breadcrumb-item active']);

	$fmtValor = function ($v) {
		return number_format((float)$v, 2, ',', '.');
	};
	$fmtData = function ($d) {
		if (empty($d)) {
			return '—';
		}
		if ($d instanceof \DateTimeInterface) {
			return $d->format('d/m/Y');
		}

		return h((string)$d);
	};
	$nomeCliente = '';
	if (!empty($contrato->cliente)) {
		$nomeCliente = trim((string)($contrato->cliente->razaosocial ?? '') ?: (string)($contrato->cliente->nome ?? ''));
	}

	$faturasRelacionadas = $faturasRelacionadas ?? [];
	$auditoriaContrato = $auditoriaContrato ?? [];
	$contratoDocumentos = $contratoDocumentos ?? [];

	$faturaUrlAction = (isset($role) && (int)$role === 0) ? 'edit' : 'view';
	$statusFaturaLabels = [];
	if (defined('C_LocacaoStatusPendente')) {
		$statusFaturaLabels = [
			C_LocacaoStatusPendente => 'Pendente',
			C_LocacaoStatusAprovado => 'Aprovado',
			C_LocacaoStatusRejeitado => 'Rejeitado',
			C_LocacaoStatusFinalizado => 'Finalizado',
		];
	}
	$rotuloAcaoAuditoria = function ($act) {
		$map = [
			'add' => 'Inclusão',
			'edit' => 'Alteração',
			'delete' => 'Exclusão',
			'view' => 'Consulta',
			'renovar' => 'Renovação',
			'index' => 'Listagem',
		];
		$a = strtolower((string)$act);

		return $map[$a] ?? h((string)$act);
	};
?>
<style>
	.clicontrato-view-tabs .nav-tabs.customtab { margin-bottom: 0; }
	.clicontrato-view-tabs .tab-content { padding-top: 1.25rem; }
</style>
<div class="col-md-12">
	<?php /* Cabeçalho — resumo do contrato */ ?>
	<div class="card mb-3">
		<div class="card-body">
			<div class="d-flex flex-wrap justify-content-between align-items-start">
				<div class="mb-2 mb-md-0 pr-md-3" style="min-width: 0;">
					<h4 class="card-title mb-1"><?= h($title) ?></h4>
					<p class="mb-1 text-muted small text-uppercase">Item #<?= (int)$contrato->id ?></p>
					<p class="mb-1 font-weight-bold"><?= h((string)$contrato->descricao) ?></p>
					<ul class="list-unstyled mb-0 small text-muted">
						<li><strong>Cód. produto:</strong> <?= h((string)$contrato->codproduto) ?></li>
						<li><strong>Vigência:</strong> <?= $fmtData($contrato->dtcontratacao ?? null) ?> &nbsp;→&nbsp; <?= $fmtData($contrato->dtvalidade ?? null) ?></li>
						<?php if ($nomeCliente !== '') : ?>
						<li><strong>Cliente:</strong> <?= h($nomeCliente) ?></li>
						<?php endif; ?>
					</ul>
				</div>
				<div class="d-flex flex-wrap align-items-center">
					<?= $this->Html->link('<i class="fa fa-edit"></i> Editar', ['action' => 'edit', $contrato->id], ['class' => 'btn btn-warning btn-sm m-r-5 m-b-5', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-sync-alt"></i> Renovar', ['action' => 'renovar', $contrato->id], ['class' => 'btn btn-success btn-sm m-r-5 m-b-5', 'escape' => false]) ?>
					<?= $this->Html->link('<i class="fa fa-arrow-left"></i> Voltar ao cliente', ['controller' => 'Clientes', 'action' => 'edit', $idcliente], ['class' => 'btn btn-secondary btn-sm m-b-5', 'escape' => false]) ?>
				</div>
			</div>
		</div>
	</div>

	<div class="card mb-3 clicontrato-view-tabs">
		<div class="card-body">
			<ul class="nav nav-tabs customtab" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" data-toggle="tab" href="#clicontrato-tab-resumo" role="tab" aria-selected="true">
						<span class="hidden-xs-down">Resumo</span>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#clicontrato-tab-faturas" role="tab" aria-selected="false">
						<span class="hidden-xs-down">Faturas</span>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#clicontrato-tab-docs" role="tab" aria-selected="false">
						<span class="hidden-xs-down">Documentos</span>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#clicontrato-tab-audit" role="tab" aria-selected="false">
						<span class="hidden-xs-down">Auditoria</span>
					</a>
				</li>
			</ul>

			<div class="tab-content">
				<div class="tab-pane active" id="clicontrato-tab-resumo" role="tabpanel">
					<h5 class="border-bottom pb-2 mb-3">Dados gerais</h5>
					<div class="table-responsive mb-4">
						<table class="table table-bordered table-sm mb-0">
							<tbody>
								<tr>
									<th class="text-muted" style="width: 220px;">Cód. produto</th>
									<td><?= h((string)$contrato->codproduto) ?></td>
								</tr>
								<tr>
									<th class="text-muted">Descrição</th>
									<td><?= h((string)$contrato->descricao) ?></td>
								</tr>
								<tr>
									<th class="text-muted">Inf. adicional</th>
									<td><?= nl2br(h((string)$contrato->infadicional)) ?></td>
								</tr>
								<tr>
									<th class="text-muted">Vl. unitário</th>
									<td><?= h($fmtValor($contrato->vlunit)) ?></td>
								</tr>
								<tr>
									<th class="text-muted">Qtde.</th>
									<td><?= h((string)$contrato->qtde) ?></td>
								</tr>
								<tr>
									<th class="text-muted">Vl. total</th>
									<td><?= h($fmtValor($contrato->vltotal)) ?></td>
								</tr>
								<tr>
									<th class="text-muted">Dt. contratação</th>
									<td><?= $fmtData($contrato->dtcontratacao ?? null) ?></td>
								</tr>
								<tr>
									<th class="text-muted">Dt. validade</th>
									<td><?= $fmtData($contrato->dtvalidade ?? null) ?></td>
								</tr>
								<tr>
									<th class="text-muted">Dt. cancelamento</th>
									<td><?= $fmtData($contrato->dtcancelamento ?? null) ?></td>
								</tr>
								<?php if (isset($contrato->iderp) && $contrato->iderp !== '' && $contrato->iderp !== null) : ?>
								<tr>
									<th class="text-muted">ID ERP</th>
									<td><?= h((string)$contrato->iderp) ?></td>
								</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>

					<h5 class="border-bottom pb-2 mb-3">SLA</h5>
					<div class="table-responsive mb-4">
						<table class="table table-bordered table-sm mb-0">
							<tbody>
								<tr>
									<th class="text-muted" style="width: 220px;">Metas de atendimento</th>
									<td class="text-muted">—</td>
								</tr>
								<tr>
									<th class="text-muted">Observação</th>
									<td class="text-muted small">Parâmetros de SLA deste item podem ser exibidos aqui quando estiverem disponíveis no cadastro ou na integração.</td>
								</tr>
							</tbody>
						</table>
					</div>

					<h5 class="border-bottom pb-2 mb-3">Serviços</h5>
					<div class="table-responsive mb-4">
						<table class="table table-bordered table-sm mb-0">
							<thead class="text-muted small">
								<tr>
									<th>Código</th>
									<th>Descrição</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><?= h((string)$contrato->codproduto) ?></td>
									<td><?= h((string)$contrato->descricao) ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<h5 class="border-bottom pb-2 mb-3">Consumo</h5>
					<div class="table-responsive">
						<table class="table table-bordered table-sm mb-0">
							<tbody>
								<tr>
									<th class="text-muted" style="width: 220px;">Quantidade contratada</th>
									<td><?= h((string)$contrato->qtde) ?></td>
								</tr>
								<tr>
									<th class="text-muted">Valor total contratado</th>
									<td><?= h($fmtValor($contrato->vltotal)) ?></td>
								</tr>
								<tr>
									<th class="text-muted">Consumo utilizado</th>
									<td class="text-muted">—</td>
								</tr>
								<tr>
									<th class="text-muted">Saldo / disponível</th>
									<td class="text-muted">—</td>
								</tr>
							</tbody>
						</table>
					</div>
					<p class="text-muted small mb-0 mt-2">Medição de consumo efetivo pode ser integrada posteriormente (ex.: horas, licenças).</p>
				</div>

				<div class="tab-pane" id="clicontrato-tab-faturas" role="tabpanel">
					<p class="text-muted small mb-3">Locações/faturas do <strong>mesmo cliente</strong> deste item. Quando houver vínculo direto com o contrato no cadastro, a lista poderá ser filtrada automaticamente.</p>
					<div class="table-responsive">
						<table class="table table-hover table-bordered table-sm mb-0">
							<thead class="text-primary">
								<tr>
									<th>Número</th>
									<th>Abertura</th>
									<th>Vencimento</th>
									<th>Valor</th>
									<th>Situação</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($faturasRelacionadas)) : ?>
									<tr>
										<td colspan="5" class="text-center text-muted">Nenhuma fatura encontrada para este cliente.</td>
									</tr>
								<?php else : ?>
									<?php foreach ($faturasRelacionadas as $fat) : ?>
										<?php
											$st = isset($fat->status) ? $fat->status : '';
											$stLabel = $statusFaturaLabels[$st] ?? ($st !== '' && $st !== null ? (string)$st : '—');
										?>
										<tr>
											<td>
												<?= $this->Html->link(
													h((string)$fat->nro),
													['controller' => 'Faturas', 'action' => $faturaUrlAction, $fat->id],
													['target' => '_blank', 'class' => 'link']
												) ?>
											</td>
											<td><?= !empty($fat->created) ? h($fat->created->format('d/m/Y')) : '—' ?></td>
											<td><?= !empty($fat->vencimento) ? h($fat->vencimento->format('d/m/Y')) : '—' ?></td>
											<td><?= h($fmtValor($fat->valor ?? 0)) ?></td>
											<td><?= h($stLabel) ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

				<div class="tab-pane" id="clicontrato-tab-docs" role="tabpanel">
					<p class="text-muted small mb-3">Contratos, anexos e arquivos vinculados a este item (quando o módulo de documentos estiver disponível).</p>
					<div class="table-responsive">
						<table class="table table-bordered table-sm mb-0">
							<thead class="text-primary">
								<tr>
									<th>Documento</th>
									<th>Tipo</th>
									<th>Data</th>
									<th width="120">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($contratoDocumentos)) : ?>
									<tr>
										<td colspan="4" class="text-center text-muted">Nenhum documento anexado.</td>
									</tr>
								<?php else : ?>
									<?php foreach ($contratoDocumentos as $doc) : ?>
										<tr>
											<td><?= h($doc['nome'] ?? '') ?></td>
											<td><?= h($doc['tipo'] ?? '') ?></td>
											<td><?= h($doc['data'] ?? '') ?></td>
											<td>—</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

				<div class="tab-pane" id="clicontrato-tab-audit" role="tabpanel">
					<p class="text-muted small mb-3">Registros de uso do cadastro deste item (controller Clicontratos).</p>
					<div class="table-responsive">
						<table class="table table-hover table-bordered table-sm mb-0">
							<thead class="text-primary">
								<tr>
									<th>Data</th>
									<th>Hora</th>
									<th>Usuário</th>
									<th>Ação</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($auditoriaContrato)) : ?>
									<tr>
										<td colspan="4" class="text-center text-muted">Nenhum registro de auditoria para este item.</td>
									</tr>
								<?php else : ?>
									<?php foreach ($auditoriaContrato as $aud) : ?>
										<?php
											$u = $aud->user ?? $aud->users ?? null;
											$nomeUser = $u ? trim((string)($u->name ?? '') ?: (string)($u->username ?? '')) : '—';
										?>
										<tr>
											<td><?= h((string)($aud->data ?? '—')) ?></td>
											<td><?= h((string)($aud->hora ?? '—')) ?></td>
											<td><?= h($nomeUser) ?></td>
											<td><?= $rotuloAcaoAuditoria($aud->action ?? '') ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
