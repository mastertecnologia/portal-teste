<?php
/**
 * Form unificado Ativos (add/edit) — 4 abas: Identificação, Hardware/Rede, Localização, Garantia & Financeiro.
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
?>
<div class="col-md-12 p-0">
<div class="atv-form-root">
	<?= $this->Form->create($asset, ['type' => 'post', 'novalidate' => true, 'autocomplete' => 'off']) ?>

	<div class="atv-form-head">
		<div class="atv-form-head-info">
			<div class="atv-eyebrow">Cadastros &rsaquo; CMDB</div>
			<h1 class="atv-h1">
				<?= $isEdit ? 'Editar Ativo' : 'Novo Ativo' ?>
				<?php if ($isEdit) : ?><span class="atv-form-head-id"><?= h($idTag) ?></span><?php endif; ?>
			</h1>
			<?php if ($isEdit && $asset->cliente) :
				$cliNome = $asset->cliente->razaosocial ?: ($asset->cliente->nomefantasia ?: ($asset->cliente->nome ?: 'Cliente'));
			?>
				<div style="color:var(--atv-text2);font-size:13px;margin-top:6px;">
					<i class="fas fa-building"></i> <?= h($cliNome) ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="atv-form-head-actions">
			<?php if ($isEdit) : ?>
				<?= $this->Html->link('<i class="fas fa-qrcode"></i> Etiqueta', ['action' => 'qr', $asset->id], ['class' => 'btn-atv-outline', 'escape' => false, 'target' => '_blank']) ?>
				<?php if ($asset->ativo) :
					echo $this->Form->postLink(
						'<i class="fas fa-ban"></i> Inativar',
						['action' => 'inativar', $asset->id],
						['class' => 'btn-atv-outline', 'escape' => false, 'confirm' => 'Inativar este ativo?']
					);
				else :
					echo $this->Form->postLink(
						'<i class="fas fa-check"></i> Reativar',
						['action' => 'reativar', $asset->id],
						['class' => 'btn-atv-outline', 'escape' => false, 'confirm' => 'Reativar este ativo?']
					);
				endif; ?>
				<div class="atv-form-head-qr"><?= $qrUrl ? '<img src="' . h($qrUrl) . '" alt="QR" style="max-width:74px;max-height:74px"/>' : 'QR' ?></div>
			<?php endif; ?>
		</div>
	</div>

	<nav class="atv-tabs-nav" role="tablist">
		<a href="#tab-ident" class="atv-tab active" data-tab="ident"><i class="fas fa-id-card"></i> Identificação</a>
		<a href="#tab-hw" class="atv-tab" data-tab="hw"><i class="fas fa-microchip"></i> Hardware / Rede</a>
		<a href="#tab-loc" class="atv-tab" data-tab="loc"><i class="fas fa-map-marker-alt"></i> Localização</a>
		<a href="#tab-fin" class="atv-tab" data-tab="fin"><i class="fas fa-shield-alt"></i> Garantia &amp; Financeiro</a>
		<?php if ($isEdit) : ?>
			<a href="#tab-hist" class="atv-tab" data-tab="hist"><i class="fas fa-history"></i> Histórico</a>
		<?php endif; ?>
	</nav>

	<div class="atv-tab-content">
		<!-- Aba Identificação -->
		<div class="atv-tab-pane active" id="tab-ident">
			<div class="atv-section">
				<div class="atv-section-title">Identificação do CI</div>
				<div class="atv-row">
					<div class="atv-field">
						<label>Cliente *</label>
						<?= $this->Form->select('idcliente', ['' => '— Selecione —'] + $clientesOpts, ['required' => true, 'empty' => false]) ?>
					</div>
					<div class="atv-field">
						<label>Tipo *</label>
						<?= $this->Form->select('tipo', $tiposOpts, ['required' => true, 'empty' => false]) ?>
					</div>
					<div class="atv-field">
						<label>Categoria</label>
						<?= $this->Form->control('categoria', ['label' => false, 'placeholder' => 'Ex.: estação Linux, ponto de venda…', 'maxlength' => 64]) ?>
					</div>
				</div>
				<div class="atv-row">
					<div class="atv-field" style="grid-column: 1 / -1">
						<label>Descrição *</label>
						<?= $this->Form->control('descricao', ['label' => false, 'required' => true, 'maxlength' => 255, 'placeholder' => 'Ex.: Notebook Dell Vostro 5410 — Diretoria']) ?>
					</div>
				</div>
				<div class="atv-row">
					<div class="atv-field">
						<label>Identificador (TAG interna)</label>
						<?= $this->Form->control('identificador', ['label' => false, 'maxlength' => 128, 'placeholder' => 'ATV-001234']) ?>
					</div>
					<div class="atv-field">
						<label>Patrimônio</label>
						<?= $this->Form->control('patrimonio', ['label' => false, 'maxlength' => 64]) ?>
					</div>
					<div class="atv-field">
						<label>Código QR</label>
						<?= $this->Form->control('codigo_qr', ['label' => false, 'maxlength' => 128]) ?>
					</div>
				</div>
				<div class="atv-row">
					<div class="atv-field">
						<label>Status operacional *</label>
						<?= $this->Form->select('status_operacional', $statusOpts, ['empty' => false, 'required' => true]) ?>
					</div>
					<div class="atv-field">
						<label>Propriedade</label>
						<?= $this->Form->select('propriedade', $propriedadeOpts, ['empty' => false]) ?>
					</div>
					<div class="atv-field">
						<label>Ativo no cadastro</label>
						<?= $this->Form->select('ativo', [1 => 'Sim', 0 => 'Não'], ['empty' => false]) ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Aba Hardware / Rede -->
		<div class="atv-tab-pane" id="tab-hw">
			<div class="atv-section">
				<div class="atv-section-title">Marca, Modelo e Identificadores</div>
				<div class="atv-row">
					<div class="atv-field">
						<label>Marca</label>
						<?= $this->Form->control('marca', ['label' => false, 'maxlength' => 96]) ?>
					</div>
					<div class="atv-field">
						<label>Modelo</label>
						<?= $this->Form->control('modelo', ['label' => false, 'maxlength' => 96]) ?>
					</div>
					<div class="atv-field">
						<label>Número de série</label>
						<?= $this->Form->control('numero_serie', ['label' => false, 'maxlength' => 128]) ?>
						<span class="atv-field-help">Único por empresa quando preenchido.</span>
					</div>
				</div>
			</div>
			<div class="atv-section">
				<div class="atv-section-title">Rede / Sistema</div>
				<div class="atv-row">
					<div class="atv-field">
						<label>Hostname</label>
						<?= $this->Form->control('hostname', ['label' => false, 'maxlength' => 128]) ?>
					</div>
					<div class="atv-field">
						<label>IP</label>
						<?= $this->Form->control('ip', ['label' => false, 'maxlength' => 45, 'placeholder' => '192.168.1.10']) ?>
					</div>
					<div class="atv-field">
						<label>MAC</label>
						<?= $this->Form->control('mac', ['label' => false, 'maxlength' => 17, 'placeholder' => 'AA:BB:CC:DD:EE:FF']) ?>
					</div>
					<div class="atv-field">
						<label>Sistema operacional</label>
						<?= $this->Form->control('sistema_operacional', ['label' => false, 'maxlength' => 96, 'placeholder' => 'Windows 11 Pro / Ubuntu 22.04 / …']) ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Aba Localização -->
		<div class="atv-tab-pane" id="tab-loc">
			<div class="atv-section">
				<div class="atv-section-title">Onde está o ativo</div>
				<div class="atv-row">
					<div class="atv-field" style="grid-column: 1 / -1">
						<label>Localização</label>
						<?= $this->Form->control('localizacao', ['label' => false, 'maxlength' => 160, 'placeholder' => 'Ex.: Matriz / Sala TI / Rack 1U-3']) ?>
					</div>
					<div class="atv-field">
						<label>Responsável</label>
						<?= $this->Form->select('responsavel_user_id', $usersOpts, ['empty' => false]) ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Aba Garantia / Financeiro -->
		<div class="atv-tab-pane" id="tab-fin">
			<div class="atv-section">
				<div class="atv-section-title">Datas de ciclo de vida</div>
				<div class="atv-row">
					<div class="atv-field">
						<label>Aquisição</label>
						<?= $this->Form->control('dt_aquisicao', ['label' => false, 'type' => 'date', 'empty' => true]) ?>
					</div>
					<div class="atv-field">
						<label>Instalação</label>
						<?= $this->Form->control('dt_instalacao', ['label' => false, 'type' => 'date', 'empty' => true]) ?>
					</div>
					<div class="atv-field">
						<label>Fim da garantia</label>
						<?= $this->Form->control('dt_garantia_fim', ['label' => false, 'type' => 'date', 'empty' => true]) ?>
					</div>
				</div>
			</div>
			<div class="atv-section">
				<div class="atv-section-title">Origem e custo</div>
				<div class="atv-row">
					<div class="atv-field">
						<label>Fornecedor</label>
						<?= $this->Form->control('fornecedor', ['label' => false, 'maxlength' => 160]) ?>
					</div>
					<div class="atv-field">
						<label>Custo de aquisição (R$)</label>
						<?= $this->Form->control('custo_aquisicao', ['label' => false, 'type' => 'number', 'step' => '0.01', 'min' => 0]) ?>
					</div>
				</div>
				<div class="atv-row">
					<div class="atv-field" style="grid-column: 1 / -1">
						<label>Observações</label>
						<?= $this->Form->control('observacoes', ['label' => false, 'type' => 'textarea', 'rows' => 4]) ?>
					</div>
				</div>
			</div>
		</div>

		<?php if ($isEdit) : ?>
		<!-- Aba Histórico -->
		<div class="atv-tab-pane" id="tab-hist">
			<div class="atv-section">
				<div class="atv-section-title">Chamados em que este CI apareceu</div>
				<?php if (empty($ticketsHist)) : ?>
					<div class="atv-empty">Sem chamados vinculados ainda.</div>
				<?php else : ?>
					<table class="atv-table">
						<thead>
							<tr>
								<th>Ticket</th>
								<th>Papel</th>
								<th>Data</th>
								<th class="atv-actions">Abrir</th>
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
								<td class="atv-mono"><?= h($num) ?></td>
								<td><?= h(ucfirst((string)($ta->papel ?: 'afetado'))) ?></td>
								<td><?= h($dt) ?></td>
								<td class="atv-actions">
									<?php if ($tk) :
										echo $this->Html->link('<i class="fas fa-external-link-alt"></i>', ['controller' => 'Tickets', 'action' => 'view', $tk->id], ['class' => 'btn-atv-icon', 'escape' => false, 'title' => $titulo]);
									endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<div class="atv-savebar">
		<?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'btn-atv-outline']) ?>
		<button type="submit" class="btn-atv-new"><i class="fas fa-save"></i> <?= $isEdit ? 'Salvar alterações' : 'Cadastrar ativo' ?></button>
	</div>

	<?= $this->Form->end() ?>
</div>
</div>

<script>
(function () {
	var tabs = document.querySelectorAll('.atv-tabs-nav .atv-tab');
	var panes = document.querySelectorAll('.atv-tab-pane');
	tabs.forEach(function (t) {
		t.addEventListener('click', function (e) {
			e.preventDefault();
			tabs.forEach(function (x) { x.classList.remove('active'); });
			panes.forEach(function (p) { p.classList.remove('active'); });
			t.classList.add('active');
			var id = 'tab-' + t.getAttribute('data-tab');
			var pane = document.getElementById(id);
			if (pane) pane.classList.add('active');
		});
	});
})();
</script>
