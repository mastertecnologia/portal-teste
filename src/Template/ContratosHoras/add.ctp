<?php
	use Cake\Routing\Router;

	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));
	$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index']);
	$this->Breadcrumbs->add('Contratos de Horas', ['action' => 'index', $idcliente]);
	$this->Breadcrumbs->add('Novo', [], ['class' => 'breadcrumb-item active']);
	$urlLista = Router::url(['action' => 'index', $idcliente]);
	$urlFichaCliente = Router::url(['controller' => 'Clientes', 'action' => 'edit', $idcliente]);
?>
<div class="col-md-12 p-0">
	<div class="cli-form-root cli-layout-unificado">
		<?= $this->Form->create($contrato, ['class' => 'form-material', 'id' => 'form-contratos-horas-add']) ?>
		<?= $this->Form->hidden('idcliente', ['value' => $idcliente]) ?>
		<div class="cli-form-body cli-form-body--cadastro-lead">
			<div class="d-flex justify-content-end flex-wrap mb-2">
				<?= $this->Html->link(
					'<i class="fas fa-arrow-left"></i> Voltar à lista',
					$urlLista,
					['class' => 'btn btn-sm btn-cli-outline m-r-5 m-b-5', 'escape' => false, 'data-turbo' => 'false']
				) ?>
				<?= $this->Html->link(
					'<i class="fas fa-user"></i> Ficha do cliente',
					$urlFichaCliente,
					['class' => 'btn btn-sm btn-cli-outline m-b-5', 'escape' => false, 'data-turbo' => 'false']
				) ?>
			</div>

			<div class="cli-section">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-calendar-check" aria-hidden="true"></i></div>
					<div class="cli-section-title">Vigência e status</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-4">
						<div class="cli-fgroup">
							<label for="data-inicio">Data início</label>
							<?= $this->Form->control('data_inicio', ['type' => 'text', 'class' => 'form-control datepicker', 'label' => false, 'id' => 'data-inicio']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="data-fim">Data fim</label>
							<?= $this->Form->control('data_fim', ['type' => 'text', 'class' => 'form-control datepicker', 'label' => false, 'id' => 'data-fim']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="horas-contratadas">Horas contratadas</label>
							<?= $this->Form->control('horas_contratadas', ['class' => 'form-control', 'label' => false, 'step' => '0.01', 'id' => 'horas-contratadas']) ?>
						</div>
						<div class="cli-fgroup">
							<?= $this->Form->control('ativo', ['type' => 'checkbox', 'checked' => true, 'label' => 'Ativo']) ?>
						</div>
					</div>
					<div class="cli-fg cli-fg-1 mt-2">
						<div class="cli-fgroup">
							<label for="horas-mensais">Horas mensais (plano)</label>
							<?= $this->Form->control('horas_mensais', [
								'class' => 'form-control',
								'label' => false,
								'step' => '0.01',
								'id' => 'horas-mensais',
								'placeholder' => 'Ex.: 10',
							]) ?>
							<small class="form-text text-muted">Opcional. Se preenchido, o resumo do contrato no Service Desk exibe «N horas mensais» em vez de apenas o total contratado.</small>
						</div>
					</div>
				</div>
			</div>

			<div class="cli-section">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-dollar-sign" aria-hidden="true"></i></div>
					<div class="cli-section-title">Valores por tipo de hora</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-3">
						<div class="cli-fgroup">
							<label for="valor-hora-comercial">Valor hora comercial</label>
							<?= $this->Form->control('valor_hora_comercial', ['class' => 'form-control', 'label' => false, 'id' => 'valor-hora-comercial']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="valor-hora-adicional">Valor hora adicional comercial</label>
							<?= $this->Form->control('valor_hora_adicional_comercial', ['class' => 'form-control', 'label' => false, 'id' => 'valor-hora-adicional']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="valor-hora-especial">Valor hora especial</label>
							<?= $this->Form->control('valor_hora_especial', ['class' => 'form-control', 'label' => false, 'id' => 'valor-hora-especial']) ?>
						</div>
					</div>
				</div>
			</div>

			<div class="cli-section">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-envelope" aria-hidden="true"></i></div>
					<div class="cli-section-title">Relatórios</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-1">
						<div class="cli-fgroup">
							<label for="contatos-email-relatorio">E-mails adicionais para relatório (separados por ;)</label>
							<?= $this->Form->control('contatos_email_relatorio', ['class' => 'form-control', 'label' => false, 'type' => 'textarea', 'id' => 'contatos-email-relatorio']) ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="cli-form-footer">
			<div class="cli-form-footer-left text-muted small">
				Novo contrato de horas
			</div>
			<div class="cli-form-footer-right">
				<?= $this->Html->link('Cancelar', ['action' => 'index', $idcliente], ['class' => 'btn-cli-secondary', 'data-turbo' => 'false']) ?>
				<?= $this->Form->button('<i class="fas fa-check"></i> Salvar', ['class' => 'btn-cli-primary', 'escape' => false, 'type' => 'submit']) ?>
			</div>
		</div>

		<?= $this->Form->end() ?>
	</div>
</div>
