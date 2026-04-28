<?php
/**
 * Form unificado Ativos (add/edit) — layout Clientes (cli-*) + abas; datas dd/mm/aaaa (PT-BR).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Asset $asset
 * @var array $clientesOpts
 * @var array $usersOpts
 * @var array $tiposOpts
 * @var array $statusOpts
 * @var array $propriedadeOpts
 * @var bool $isEdit
 * @var array $ticketsHist
 */
$asset = $asset ?? null;
$isEdit = (bool)($isEdit ?? false);
$ticketsHist = $ticketsHist ?? [];
$clientesOpts = $clientesOpts ?? [];
$usersOpts = $usersOpts ?? [];
$tiposOpts = $tiposOpts ?? [];
$statusOpts = $statusOpts ?? [];
$propriedadeOpts = $propriedadeOpts ?? [];

$idTag = $isEdit ? ($asset->identificador ?: ('ATV-' . str_pad((string)$asset->id, 6, '0', STR_PAD_LEFT))) : 'NOVO';
$qrPayload = $isEdit ? ($asset->codigo_qr ?: ('ATV-' . str_pad((string)$asset->id, 6, '0', STR_PAD_LEFT))) : '';
$qrUrl = $isEdit ? 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($qrPayload) : '';

$atvFmtDateBr = function ($d): string {
	if ($d === null || $d === '') {
		return '';
	}
	if ($d instanceof \DateTimeInterface) {
		return $d->format('d/m/Y');
	}
	$t = strtotime((string)$d);

	return $t ? date('d/m/Y', $t) : '';
};
?>
<div class="col-md-12 p-0">
<div class="cli-form-root cli-layout-unificado">

	<?php if ($isEdit) : ?>
	<div class="cli-page-head">
		<div class="cli-page-head-left">
			<div class="cli-eyebrow">Cadastros &rsaquo; Ativos &rsaquo; Editar</div>
			<h1><?= h($asset->descricao ?: 'Ativo') ?> <span class="cli-page-head-code" translate="no"><?= h($idTag) ?></span></h1>
			<?php if ($asset->cliente) :
				$cliNome = $asset->cliente->razaosocial ?: ($asset->cliente->nomefantasia ?: ($asset->cliente->nome ?: 'Cliente'));
			?>
				<p class="mb-0"><i class="fas fa-building text-muted"></i> <?= h($cliNome) ?></p>
			<?php endif; ?>
		</div>
		<div class="d-flex align-items-center flex-wrap pgm-gap-8">
			<?= $this->Html->link('<i class="fas fa-eye"></i> Ver ficha', ['action' => 'view', $asset->id], ['class' => 'btn-cli-outline', 'escape' => false]) ?>
			<?= $this->Html->link('<i class="fas fa-qrcode"></i> Etiqueta', ['action' => 'qr', $asset->id], ['class' => 'btn-cli-outline', 'escape' => false, 'target' => '_blank']) ?>
			<?php if ($asset->ativo) :
				echo $this->Form->postLink(
					'<i class="fas fa-ban"></i> Inativar',
					['action' => 'inativar', $asset->id],
					['class' => 'btn-cli-outline', 'escape' => false, 'confirm' => 'Inativar este ativo?']
				);
			else :
				echo $this->Form->postLink(
					'<i class="fas fa-check"></i> Reativar',
					['action' => 'reativar', $asset->id],
					['class' => 'btn-cli-outline', 'escape' => false, 'confirm' => 'Reativar este ativo?']
				);
			endif; ?>
			<span class="atv-cli-qr-thumb"><?= $qrUrl ? '<img src="' . h($qrUrl) . '" alt="QR" width="64" height="64"/>' : '' ?></span>
			<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Voltar', ['action' => 'index'], ['class' => 'btn-cli-outline', 'escape' => false]) ?>
		</div>
	</div>
	<?php else : ?>
	<div class="d-flex justify-content-end mb-2">
		<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Voltar', ['action' => 'index'], ['class' => 'btn-cli-outline', 'escape' => false]) ?>
	</div>
	<?php endif; ?>

	<?= $this->Form->create($asset, ['type' => 'post', 'novalidate' => true, 'autocomplete' => 'off', 'id' => 'atv-asset-form', 'class' => 'atv-cli-form']) ?>

	<div class="cli-form-body cli-form-body--cadastro-lead">

		<ul class="nav cli-tabs-nav" role="tablist" id="atv-tabs-nav" aria-label="Seções do cadastro de ativo">
			<li class="nav-item" role="presentation">
				<a class="nav-link active" id="atv-tab-ident" data-toggle="tab" href="#tab-ident" role="tab" aria-controls="tab-ident" aria-selected="true">
					<i class="fas fa-id-card" aria-hidden="true"></i> Identificação
				</a>
			</li>
			<li class="nav-item" role="presentation">
				<a class="nav-link" id="atv-tab-hw" data-toggle="tab" href="#tab-hw" role="tab" aria-controls="tab-hw" aria-selected="false">
					<i class="fas fa-microchip" aria-hidden="true"></i> Hardware / Rede
				</a>
			</li>
			<li class="nav-item" role="presentation">
				<a class="nav-link" id="atv-tab-loc" data-toggle="tab" href="#tab-loc" role="tab" aria-controls="tab-loc" aria-selected="false">
					<i class="fas fa-map-marker-alt" aria-hidden="true"></i> Localização
				</a>
			</li>
			<li class="nav-item" role="presentation">
				<a class="nav-link" id="atv-tab-fin" data-toggle="tab" href="#tab-fin" role="tab" aria-controls="tab-fin" aria-selected="false">
					<i class="fas fa-shield-alt" aria-hidden="true"></i> Garantia &amp; Financeiro
				</a>
			</li>
			<?php if ($isEdit) : ?>
			<li class="nav-item" role="presentation">
				<a class="nav-link" id="atv-tab-hist" data-toggle="tab" href="#tab-hist" role="tab" aria-controls="tab-hist" aria-selected="false">
					<i class="fas fa-history" aria-hidden="true"></i> Histórico
				</a>
			</li>
			<?php endif; ?>
		</ul>

		<div class="tab-content atv-cli-tab-content">
			<div class="tab-pane fade show active" id="tab-ident" role="tabpanel" aria-labelledby="atv-tab-ident">
				<div class="cli-section">
					<div class="cli-section-head">
						<div class="cli-section-icon"><i class="fas fa-id-card"></i></div>
						<div class="cli-section-title">Identificação do CI</div>
					</div>
					<div class="cli-section-body">
						<div class="cli-fg cli-fg-3">
							<div class="cli-fgroup">
								<label>Cliente <span class="cli-req">*</span></label>
								<?= $this->Form->select('idcliente', ['' => '— Selecione —'] + $clientesOpts, ['required' => true, 'empty' => false, 'class' => 'form-control']) ?>
							</div>
							<div class="cli-fgroup">
								<label>Tipo <span class="cli-req">*</span></label>
								<?= $this->Form->select('tipo', $tiposOpts, ['required' => true, 'empty' => false, 'class' => 'form-control']) ?>
							</div>
							<div class="cli-fgroup">
								<label>Categoria</label>
								<?= $this->Form->control('categoria', ['label' => false, 'class' => 'form-control', 'placeholder' => 'Ex.: estação Linux, ponto de venda…', 'maxlength' => 64]) ?>
							</div>
						</div>
						<div class="cli-fg cli-fg-1" style="margin-top:14px">
							<div class="cli-fgroup">
								<label>Descrição <span class="cli-req">*</span></label>
								<?= $this->Form->control('descricao', ['label' => false, 'class' => 'form-control', 'required' => true, 'maxlength' => 255, 'placeholder' => 'Ex.: Notebook Dell Vostro 5410 — Diretoria']) ?>
							</div>
						</div>
						<div class="cli-fg cli-fg-3" style="margin-top:14px">
							<div class="cli-fgroup">
								<label>Identificador (TAG interna)</label>
								<?= $this->Form->control('identificador', ['label' => false, 'class' => 'form-control', 'maxlength' => 128, 'placeholder' => 'ATV-001234']) ?>
							</div>
							<div class="cli-fgroup">
								<label>Patrimônio</label>
								<?= $this->Form->control('patrimonio', ['label' => false, 'class' => 'form-control', 'maxlength' => 64]) ?>
							</div>
							<div class="cli-fgroup">
								<label>Código QR</label>
								<?= $this->Form->control('codigo_qr', ['label' => false, 'class' => 'form-control', 'maxlength' => 128]) ?>
							</div>
						</div>
						<div class="cli-fg cli-fg-3" style="margin-top:14px">
							<div class="cli-fgroup">
								<label>Status operacional <span class="cli-req">*</span></label>
								<?= $this->Form->select('status_operacional', $statusOpts, ['empty' => false, 'required' => true, 'class' => 'form-control']) ?>
							</div>
							<div class="cli-fgroup">
								<label>Propriedade</label>
								<?= $this->Form->select('propriedade', $propriedadeOpts, ['empty' => false, 'class' => 'form-control']) ?>
							</div>
							<div class="cli-fgroup">
								<label>Ativo no cadastro</label>
								<?= $this->Form->select('ativo', [1 => 'Sim', 0 => 'Não'], ['empty' => false, 'class' => 'form-control']) ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="tab-hw" role="tabpanel" aria-labelledby="atv-tab-hw">
				<div class="cli-section">
					<div class="cli-section-head">
						<div class="cli-section-icon"><i class="fas fa-microchip"></i></div>
						<div class="cli-section-title">Marca, modelo e identificadores</div>
					</div>
					<div class="cli-section-body">
						<div class="cli-fg cli-fg-3">
							<div class="cli-fgroup">
								<label>Marca</label>
								<?= $this->Form->control('marca', ['label' => false, 'class' => 'form-control', 'maxlength' => 96]) ?>
							</div>
							<div class="cli-fgroup">
								<label>Modelo</label>
								<?= $this->Form->control('modelo', ['label' => false, 'class' => 'form-control', 'maxlength' => 96]) ?>
							</div>
							<div class="cli-fgroup">
								<label>Número de série</label>
								<?= $this->Form->control('numero_serie', ['label' => false, 'class' => 'form-control', 'maxlength' => 128]) ?>
								<small class="text-muted">Único por empresa quando preenchido.</small>
							</div>
						</div>
					</div>
				</div>
				<div class="cli-section" style="margin-top:14px">
					<div class="cli-section-head">
						<div class="cli-section-icon"><i class="fas fa-network-wired"></i></div>
						<div class="cli-section-title">Rede / sistema</div>
					</div>
					<div class="cli-section-body">
						<div class="cli-fg cli-fg-4">
							<div class="cli-fgroup">
								<label>Hostname</label>
								<?= $this->Form->control('hostname', ['label' => false, 'class' => 'form-control', 'maxlength' => 128]) ?>
							</div>
							<div class="cli-fgroup">
								<label>IP</label>
								<?= $this->Form->control('ip', ['label' => false, 'class' => 'form-control', 'maxlength' => 45, 'placeholder' => '192.168.1.10']) ?>
							</div>
							<div class="cli-fgroup">
								<label>MAC</label>
								<?= $this->Form->control('mac', ['label' => false, 'class' => 'form-control', 'maxlength' => 17, 'placeholder' => 'AA:BB:CC:DD:EE:FF']) ?>
							</div>
							<div class="cli-fgroup">
								<label>Sistema operacional</label>
								<?= $this->Form->control('sistema_operacional', ['label' => false, 'class' => 'form-control', 'maxlength' => 96, 'placeholder' => 'Windows 11 Pro / Ubuntu 22.04 / …']) ?>
							</div>
						</div>
						<div class="cli-fg cli-fg-4" style="margin-top:14px">
							<div class="cli-fgroup">
								<label>Usuário</label>
								<?= $this->Form->control('usuario', ['label' => false, 'class' => 'form-control', 'maxlength' => 128]) ?>
							</div>
							<div class="cli-fgroup">
								<label>Senha</label>
								<div class="input-group">
									<?= $this->Form->control('senha', [
										'type' => 'password',
										'label' => false,
										'class' => 'form-control',
										'autocomplete' => 'new-password',
										'id' => 'atv-senha-input',
										'value' => '',
									]) ?>
									<div class="input-group-append">
										<button type="button" class="btn btn-outline-secondary" id="atv-senha-toggle" aria-label="Mostrar senha" title="Mostrar senha">
											<i class="fas fa-eye" aria-hidden="true"></i>
										</button>
									</div>
								</div>
							</div>
							<div class="cli-fgroup">
								<label>Porta interna</label>
								<?= $this->Form->control('porta_interna', ['type' => 'number', 'label' => false, 'class' => 'form-control', 'min' => 1, 'max' => 65535, 'placeholder' => '22']) ?>
							</div>
							<div class="cli-fgroup">
								<label>Porta externa</label>
								<?= $this->Form->control('porta_externa', ['type' => 'number', 'label' => false, 'class' => 'form-control', 'min' => 1, 'max' => 65535, 'placeholder' => '443']) ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="tab-loc" role="tabpanel" aria-labelledby="atv-tab-loc">
				<div class="cli-section">
					<div class="cli-section-head">
						<div class="cli-section-icon"><i class="fas fa-map-marker-alt"></i></div>
						<div class="cli-section-title">Onde está o ativo</div>
					</div>
					<div class="cli-section-body">
						<div class="cli-fg cli-fg-1" style="margin-bottom:14px">
							<div class="cli-fgroup">
								<label>Localização</label>
								<?= $this->Form->control('localizacao', ['label' => false, 'class' => 'form-control', 'maxlength' => 160, 'placeholder' => 'Ex.: Matriz / Sala TI / Rack 1U-3']) ?>
							</div>
						</div>
						<div class="cli-fg cli-fg-2">
							<div class="cli-fgroup">
								<label>Responsável</label>
								<?= $this->Form->select('responsavel_user_id', $usersOpts, ['empty' => false, 'class' => 'form-control']) ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="tab-fin" role="tabpanel" aria-labelledby="atv-tab-fin">
				<div class="cli-section">
					<div class="cli-section-head">
						<div class="cli-section-icon"><i class="fas fa-calendar-alt"></i></div>
						<div class="cli-section-title">Datas de ciclo de vida</div>
					</div>
					<div class="cli-section-body">
						<div class="cli-fg cli-fg-3">
							<div class="cli-fgroup">
								<label>Aquisição</label>
								<?= $this->Form->text('dt_aquisicao', [
									'class' => 'form-control atv-date-br',
									'value' => $atvFmtDateBr($asset->dt_aquisicao ?? null),
									'placeholder' => 'dd/mm/aaaa',
									'autocomplete' => 'off',
								]) ?>
							</div>
							<div class="cli-fgroup">
								<label>Instalação</label>
								<?= $this->Form->text('dt_instalacao', [
									'class' => 'form-control atv-date-br',
									'value' => $atvFmtDateBr($asset->dt_instalacao ?? null),
									'placeholder' => 'dd/mm/aaaa',
									'autocomplete' => 'off',
								]) ?>
							</div>
							<div class="cli-fgroup">
								<label>Fim da garantia</label>
								<?= $this->Form->text('dt_garantia_fim', [
									'class' => 'form-control atv-date-br',
									'value' => $atvFmtDateBr($asset->dt_garantia_fim ?? null),
									'placeholder' => 'dd/mm/aaaa',
									'autocomplete' => 'off',
								]) ?>
							</div>
						</div>
					</div>
				</div>
				<div class="cli-section" style="margin-top:14px">
					<div class="cli-section-head">
						<div class="cli-section-icon"><i class="fas fa-file-invoice-dollar"></i></div>
						<div class="cli-section-title">Origem e custo</div>
					</div>
					<div class="cli-section-body">
						<div class="cli-fg cli-fg-2">
							<div class="cli-fgroup">
								<label>Fornecedor</label>
								<?= $this->Form->control('fornecedor', ['label' => false, 'class' => 'form-control', 'maxlength' => 160]) ?>
							</div>
							<div class="cli-fgroup">
								<label>Custo de aquisição (R$)</label>
								<?= $this->Form->control('custo_aquisicao', ['label' => false, 'class' => 'form-control', 'type' => 'number', 'step' => '0.01', 'min' => 0]) ?>
							</div>
						</div>
						<div class="cli-fg cli-fg-1" style="margin-top:14px">
							<div class="cli-fgroup">
								<label>Observações</label>
								<?= $this->Form->control('observacoes', ['label' => false, 'class' => 'form-control', 'type' => 'textarea', 'rows' => 4]) ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php if ($isEdit) : ?>
			<div class="tab-pane fade" id="tab-hist" role="tabpanel" aria-labelledby="atv-tab-hist">
				<div class="cli-section">
					<div class="cli-section-head">
						<div class="cli-section-icon"><i class="fas fa-history"></i></div>
						<div class="cli-section-title">Chamados em que este CI apareceu</div>
					</div>
					<div class="cli-section-body">
						<?php if (empty($ticketsHist)) : ?>
							<p class="text-muted mb-0">Sem chamados vinculados ainda.</p>
						<?php else : ?>
							<div class="table-responsive">
								<table class="table table-sm table-hover mb-0">
									<thead>
										<tr>
											<th>Ticket</th>
											<th>Papel</th>
											<th>Data</th>
											<th class="text-right">Abrir</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ($ticketsHist as $ta) :
										$tk = $ta->ticket ?? null;
										$num = $tk ? '#' . (int)$tk->id : '—';
										$titulo = $tk ? ($tk->titulo ?? '') : '';
										$dt = $ta->created instanceof \DateTimeInterface ? $ta->created->format('d/m/Y H:i') : '';
									?>
										<tr>
											<td><code><?= h($num) ?></code></td>
											<td><?= h(ucfirst((string)($ta->papel ?: 'afetado'))) ?></td>
											<td><?= h($dt) ?></td>
											<td class="text-right">
												<?php if ($tk) :
													echo $this->Html->link('<i class="fas fa-external-link-alt"></i>', ['controller' => 'Tickets', 'action' => 'view', $tk->id], ['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false, 'title' => $titulo]);
												endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>

	</div><!-- /cli-form-body -->

	<div class="cli-form-footer">
		<div class="cli-form-footer-left">
			<i class="fas fa-shield-alt cli-icon-teal" style="margin-right:5px;" aria-hidden="true"></i>
			Campos marcados com <span class="cli-req">*</span> são obrigatórios.
		</div>
		<div class="cli-form-footer-right">
			<?= $this->Html->link('<i class="fas fa-times"></i> Cancelar', ['action' => 'index'], ['class' => 'btn-cli-secondary', 'escape' => false]) ?>
			<?php if ($isEdit) : ?>
				<button type="submit" class="btn-cli-primary"><i class="fas fa-save"></i> Salvar Alterações</button>
			<?php else : ?>
				<button type="submit" class="btn-cli-primary"><i class="fas fa-check"></i> Cadastrar ativo</button>
			<?php endif; ?>
		</div>
	</div>

	<?= $this->Form->end() ?>
</div>
</div>

<script>
(function () {
	function atvBindBrDatepickers($ctx) {
		if (typeof jQuery === 'undefined' || !jQuery.fn.bootstrapMaterialDatePicker) {
			return;
		}
		var opts = { format: 'DD/MM/YYYY', lang: 'pt-br', time: false, switchOnClick: true, nowButton: true, cancelText: 'Cancelar', nowText: 'Hoje' };
		$ctx.find('input.atv-date-br').each(function () {
			var $el = jQuery(this);
			if ($el.data('atvDpInit')) {
				return;
			}
			$el.data('atvDpInit', 1);
			$el.bootstrapMaterialDatePicker(opts);
		});
	}
	jQuery(function ($) {
		$('#atv-tabs-nav a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			var href = e.target.getAttribute('href');
			if (href) {
				atvBindBrDatepickers($(href));
			}
		});

		var senhaInput = document.getElementById('atv-senha-input');
		var senhaToggle = document.getElementById('atv-senha-toggle');
		if (senhaInput && senhaToggle) {
			senhaToggle.addEventListener('click', function () {
				var isHidden = senhaInput.getAttribute('type') === 'password';
				senhaInput.setAttribute('type', isHidden ? 'text' : 'password');
				senhaToggle.setAttribute('aria-label', isHidden ? 'Ocultar senha' : 'Mostrar senha');
				senhaToggle.setAttribute('title', isHidden ? 'Ocultar senha' : 'Mostrar senha');
				senhaToggle.innerHTML = isHidden
					? '<i class="fas fa-eye-slash" aria-hidden="true"></i>'
					: '<i class="fas fa-eye" aria-hidden="true"></i>';
			});
		}
	});
})();
</script>
