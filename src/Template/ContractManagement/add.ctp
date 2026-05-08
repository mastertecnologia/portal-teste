<?php
$this->assign('title', $title ?? 'Novo contrato');
$__initialMonthly = (float)($contract->monthly_value ?? 0);
$__contractDatePtBr = static function ($v) {
	if ($v instanceof \DateTimeInterface) {
		return $v->format('d/m/Y');
	}
	if (is_string($v)) {
		$v = trim($v);
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\s|T|$)/', $v, $m)) {
			$y = (int)$m[1];
			$mo = (int)$m[2];
			$d = (int)$m[3];

			return sprintf('%02d/%02d/%04d', $d, $mo, $y);
		}
		if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $v)) {
			return $v;
		}
	}

	return '';
};
$__startDisp = $__contractDatePtBr($contract->start_date ?? null);
$__endDisp = $__contractDatePtBr($contract->end_date ?? null);
if ($this->request->is('post')) {
	$tIni = $this->request->getData('start_date');
	if ($tIni !== null && $tIni !== '') {
		$__startDisp = is_scalar($tIni) ? (string)$tIni : $__startDisp;
	}
	$tFim = $this->request->getData('end_date');
	if ($tFim !== null && $tFim !== '') {
		$__endDisp = is_scalar($tFim) ? (string)$tFim : $__endDisp;
	}
}
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<?= $this->element('ContractManagement/wizard_steps', [
				'step' => 'add',
				'contractId' => (int)($contract->id ?? 0),
				'podeEditarDadosPasso' => !empty($contractMayEditCore),
			]) ?>
			<div class="alert alert-info small mb-3">
				<strong><?= __('Fluxo sugerido') ?>:</strong>
				<?= __('Ao gravar com "Continuar", segue para serviços; use "Abrir ficha" para ir direto à ficha do contrato.') ?>
				<?= $this->Html->link(__('Modelos'), '/contract-templates', ['class' => 'alert-link']) ?>.
			</div>
			<?= $this->Form->create($contract, ['class' => 'contract-management-form']) ?>
			<?= $this->Form->hidden('idempresa') ?>
			<?= $this->Form->control('idcliente', ['options' => $clientesList, 'empty' => __('Selecione…'), 'label' => __('Cliente'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('code', ['label' => __('Código'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('name', ['label' => __('Nome'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('type', ['label' => __('Tipo'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('template_id', ['options' => $templatesList, 'empty' => true, 'label' => __('Modelo'), 'class' => 'form-control']) ?>
			<div class="contract-form-inner-stack">
				<div class="pgm-adv-panel card contract-form-inner-card mb-3">
					<div class="card-body">
						<h5 class="contract-form-section-title"><?= __('Vigência') ?></h5>
						<div class="row contract-vigencia-info">
							<div class="col-md-4 col-sm-12">
								<div class="form-group">
									<label class="control-label" for="contract_start_date_pt"><?= __('Data de início') ?></label>
									<input type="text"
										name="start_date"
										id="contract_start_date_pt"
										class="form-control datepicker"
										placeholder="<?= __('dd/mm/aaaa') ?>"
										value="<?= h($__startDisp) ?>"
										autocomplete="off"
									>
								</div>
							</div>
							<div class="col-md-4 col-sm-12">
								<div class="form-group">
									<label class="control-label" for="contract_end_date_pt"><?= __('Data de fim') ?></label>
									<input type="text"
										name="end_date"
										id="contract_end_date_pt"
										class="form-control datepicker"
										placeholder="<?= __('dd/mm/aaaa') ?>"
										value="<?= h($__endDisp) ?>"
										autocomplete="off"
									>
								</div>
							</div>
							<div class="col-md-4 col-sm-12">
								<div class="form-group">
									<label class="control-label"><?= __('Prazo comercial') ?></label>
									<input type="text" class="form-control contract-readonly-soft" id="vigencia_prazo_comercial_preview" value="—" readonly>
								</div>
							</div>
						</div>
						<div class="row contract-vigencia-info">
							<div class="col-md-12 col-sm-12">
								<div class="form-group">
									<label class="control-label"><?= __('Prazo da vigência') ?></label>
									<input type="text" class="form-control contract-readonly-soft" id="vigencia_prazo_preview" value="—" readonly>
								</div>
							</div>
						</div>
						<div class="alert alert-warning small contract-vigencia-alert mb-0" id="vigencia_prazo_alert" style="display:none;">
							<?= __('Atenção: vigência fora dos prazos comerciais padrão (12, 24, 36, 48 ou 60 meses).') ?>
						</div>
					</div>
				</div>
				<div class="pgm-adv-panel card contract-form-inner-card mb-3 contract-valores-panel" data-monthly-value="<?= h(number_format($__initialMonthly, 2, '.', '')) ?>">
					<div class="card-body">
						<h5 class="contract-form-section-title"><?= __('Valores do contrato') ?></h5>
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label class="control-label"><?= __('Valor mensal') ?></label>
									<input type="text" class="form-control contract-readonly-soft" id="contract_monthly_value_preview" readonly>
									<small class="text-muted"><?= __('Calculado automaticamente pela soma da aba Serviços.') ?></small>
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label class="control-label"><?= __('Valor total') ?></label>
									<input type="text" class="form-control contract-readonly-soft" id="contract_total_value_preview" readonly>
									<small class="text-muted"><?= __('Calculado automaticamente pela vigência x valor mensal.') ?></small>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?= $this->Form->control('nivel_sla', ['label' => __('Nível SLA'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('auto_renew', ['type' => 'checkbox', 'label' => __('Renovação automática')]) ?>
			<?= $this->Form->control('observacoes_cli', ['type' => 'textarea', 'label' => __('Obs. cliente'), 'class' => 'form-control', 'rows' => 4]) ?>
			<?= $this->Form->control('notes', ['type' => 'textarea', 'label' => __('Notas internas'), 'class' => 'form-control', 'rows' => 4]) ?>
			<div class="btn-toolbar contract-form-actions">
			<?= $this->Form->button(__('Gravar e continuar'), ['class' => 'btn btn-primary', 'name' => 'gravar_destino', 'value' => 'wizard']) ?>
			<?= $this->Form->button(__('Gravar e abrir ficha'), ['class' => 'btn btn-default', 'name' => 'gravar_destino', 'value' => 'ficha']) ?>
			<?= $this->Html->link(__('Cancelar'), ['action' => 'index'], ['class' => 'btn btn-default']) ?>
			</div>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
<script>
(function () {
	var startEl = document.getElementById('contract_start_date_pt');
	var endEl = document.getElementById('contract_end_date_pt');
	var prazoPreview = document.getElementById('vigencia_prazo_preview');
	var prazoComercialPreview = document.getElementById('vigencia_prazo_comercial_preview');
	var prazoAlert = document.getElementById('vigencia_prazo_alert');
	var monthlyPreview = document.getElementById('contract_monthly_value_preview');
	var totalPreview = document.getElementById('contract_total_value_preview');
	var valuesWrap = document.querySelector('.contract-valores-panel');
	var standardTerms = [12, 24, 36, 48, 60];
	var monthlyValue = parseFloat(valuesWrap ? valuesWrap.getAttribute('data-monthly-value') : '0') || 0;
	var money = function (v) {
		return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0);
	};
	var parsePtDateInput = function (el) {
		if (!el || !el.value) {
			return null;
		}
		var m = String(el.value).trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
		if (!m) {
			return null;
		}
		var day = parseInt(m[1], 10);
		var mo = parseInt(m[2], 10) - 1;
		var y = parseInt(m[3], 10);
		var date = new Date(y, mo, day);
		if (date.getFullYear() !== y || date.getMonth() !== mo || date.getDate() !== day) {
			return null;
		}
		return date;
	};
	var calcMonthsAndDays = function (start, end) {
		var months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
		var anchor = new Date(start.getTime());
		anchor.setMonth(anchor.getMonth() + months);
		while (anchor > end && months > 0) {
			months--;
			anchor = new Date(start.getTime());
			anchor.setMonth(anchor.getMonth() + months);
		}
		var days = Math.floor((end - anchor) / 86400000);
		var billingMonths = months + (days > 0 ? 1 : 0);
		if (billingMonths <= 0) {
			billingMonths = 1;
		}
		return { months: billingMonths, days: days };
	};
	var refresh = function () {
		var start = parsePtDateInput(startEl);
		var end = parsePtDateInput(endEl);
		monthlyPreview.value = money(monthlyValue);
		if (!start || !end) {
			prazoPreview.value = '—';
			prazoComercialPreview.value = '—';
			prazoAlert.style.display = 'none';
			totalPreview.value = money(0);
			return;
		}
		if (end < start) {
			prazoPreview.value = 'Período inválido';
			prazoComercialPreview.value = '—';
			prazoAlert.style.display = 'none';
			totalPreview.value = money(0);
			return;
		}
		var diff = calcMonthsAndDays(start, end);
		prazoPreview.value = diff.days > 0 ? (diff.months + ' meses e ' + diff.days + ' dias') : (diff.months + ' meses');
		prazoComercialPreview.value = diff.months + ' meses';
		prazoAlert.style.display = standardTerms.indexOf(diff.months) === -1 ? '' : 'none';
		totalPreview.value = money(monthlyValue * diff.months);
	};
	if (!startEl || !endEl) {
		return;
	}
	startEl.addEventListener('change', refresh);
	endEl.addEventListener('change', refresh);
	startEl.addEventListener('keyup', refresh);
	endEl.addEventListener('keyup', refresh);
	refresh();
})();
</script>
