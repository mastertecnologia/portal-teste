<?php
/**
 * Card "Dados do cliente" (add / dados).
 *
 * @var array $clientes
 * @var array $orcFormaPagamentoOpcoes
 */
?>
<section class="orcamento-card">
	<div class="orc-sec-title">Dados do cliente</div>
	<div class="orc-cliente-grid orc-cliente-grid--top">
		<div class="orc-field">
			<label class="control-label">Cliente</label>
			<?= $this->Form->control('idcliente', [
				'class' => 'form-control selectpicker',
				'data-live-search' => true,
				'options' => $clientes,
				'title' => 'Selecione...',
				'label' => false,
				'required' => true,
				'id' => 'idcliente',
			]) ?>
		</div>
		<div class="orc-cliente-grid orc-cliente-grid--pag">
			<div class="orc-field">
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
			<div class="orc-field">
				<label class="control-label">Válido até</label>
				<div class="orc-date-field">
					<?php
					$validoateFieldOpts = [
						'class' => 'form-control datepicker',
						'id' => 'validoate',
						'placeholder' => 'dd/mm/aaaa',
						'required' => true,
						'autocomplete' => 'off',
					];
					if (!empty($orcValidoateDefault)) {
						$validoateFieldOpts['default'] = $orcValidoateDefault;
					}
					echo $this->Form->text('validoate', $validoateFieldOpts);
					?>
					<button type="button" class="orc-date-trigger" tabindex="-1" aria-label="Abrir calendário" data-target="#validoate">
						<i class="fa fa-calendar" aria-hidden="true"></i>
					</button>
				</div>
			</div>
		</div>
	</div>
	<div class="orc-cliente-grid orc-cliente-grid--bottom">
		<div class="orc-field">
			<label class="control-label" id="orc-cli-doc-lbl">CNPJ</label>
			<input type="text" class="form-control orc-input-readonly-fill" id="orc-cli-doc" readonly placeholder="Auto-preenchido" />
		</div>
		<div class="orc-field">
			<label class="control-label">E-mail do cliente</label>
			<input type="email" class="form-control" id="orc-cli-email" autocomplete="email" placeholder="Auto-preenchido" />
		</div>
		<div class="orc-field">
			<label class="control-label">Contato / responsável</label>
			<input type="text" class="form-control orc-input-readonly-fill" id="orc-cli-contato" readonly placeholder="Auto-preenchido" />
		</div>
	</div>
</section>
