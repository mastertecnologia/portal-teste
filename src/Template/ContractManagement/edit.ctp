<?php
$this->assign('title', $title ?? 'Editar');
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
if ($this->request->is(['post', 'put'])) {
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
				'contractId' => (int)$contract->id,
				'podeEditarDadosPasso' => !empty($contractMayEditCore),
			]) ?>
			<?= $this->Form->create($contract, ['class' => 'contract-management-form']) ?>
			<?= $this->Form->control('idcliente', ['options' => $clientesList, 'empty' => __('Selecione…'), 'label' => __('Cliente'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('code', ['label' => __('Código'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('name', ['label' => __('Nome'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('type', ['label' => __('Tipo'), 'class' => 'form-control']) ?>
			<?php
			$__stEdit = [];
			$__map = \App\Model\Entity\Contract::statusLabelMap();
			foreach (\App\Model\Table\ContractsTable::allowedStatusValues() as $__v) {
				$__stEdit[$__v] = $__map[$__v] ?? $__v;
			}
			?>
			<?= $this->Form->control('status', ['label' => __('Status'), 'options' => $__stEdit, 'class' => 'form-control']) ?>
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
			<?= $this->Form->button(__('Salvar'), ['class' => 'btn btn-primary']) ?>
			<?= $this->Html->link(__('Voltar'), ['action' => 'view', $contract->id], ['class' => 'btn btn-default']) ?>
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
	var parsePtParts = function (el) {
		if (!el || !el.value) {
			return null;
		}
		var parts = String(el.value).trim().split('/');
		if (parts.length !== 3) {
			return null;
		}
		var day = parseInt(parts[0], 10);
		var mo = parseInt(parts[1], 10);
		var y = parseInt(parts[2], 10);
		if (isNaN(day) || isNaN(mo) || isNaN(y) || mo < 1 || mo > 12 || day < 1 || day > 31 || y < 1900) {
			return null;
		}
		var cal = new Date(y, mo - 1, day);
		if (cal.getFullYear() !== y || cal.getMonth() !== mo - 1 || cal.getDate() !== day) {
			return null;
		}
		return { day: day, month: mo, year: y };
	};
	var calculateCommercialMonths = function (start, end) {
		if (!start || !end) {
			return 0;
		}
		var sn = start.year * 10000 + start.month * 100 + start.day;
		var en = end.year * 10000 + end.month * 100 + end.day;
		if (en < sn) {
			return 0;
		}
		var months = ((end.year - start.year) * 12) + (end.month - start.month);
		if (end.day > start.day) {
			months++;
		}
		if (months <= 0) {
			months = 1;
		}
		return months;
	};
	var refresh = function () {
		var start = parsePtParts(startEl);
		var end = parsePtParts(endEl);
		monthlyPreview.value = money(monthlyValue);
		if (!start || !end) {
			prazoPreview.value = '—';
			prazoComercialPreview.value = '—';
			if (prazoAlert) {
				prazoAlert.style.display = 'none';
			}
			totalPreview.value = money(0);
			return;
		}
		var monthsCommercial = calculateCommercialMonths(start, end);
		if (monthsCommercial === 0) {
			prazoPreview.value = 'Período inválido';
			prazoComercialPreview.value = '—';
			if (prazoAlert) {
				prazoAlert.style.display = 'none';
			}
			totalPreview.value = money(0);
			return;
		}
		prazoPreview.value = monthsCommercial + ' meses';
		prazoComercialPreview.value = monthsCommercial + ' meses';
		if (prazoAlert) {
			prazoAlert.style.display = standardTerms.indexOf(monthsCommercial) === -1 ? '' : 'none';
		}
		totalPreview.value = money(monthlyValue * monthsCommercial);
	};
	if (!startEl || !endEl) {
		return;
	}
	['input', 'change', 'keyup'].forEach(function (eventName) {
		startEl.addEventListener(eventName, refresh);
		endEl.addEventListener(eventName, refresh);
	});
	/** Material datetimepicker faz $.trigger('change'|'dateSelected') — só jQuery ouve; input nativo já não atualiza o preview */
	if (window.jQuery) {
		window.jQuery(startEl).on('change dateSelected', refresh);
		window.jQuery(endEl).on('change dateSelected', refresh);
	}
	['blur', 'focusout'].forEach(function (eventName) {
		startEl.addEventListener(eventName, refresh);
		endEl.addEventListener(eventName, refresh);
	});
	window.addEventListener('load', refresh);
	refresh();
})();
</script>
