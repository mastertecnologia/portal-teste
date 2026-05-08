<?php
$this->assign('title', $title ?? 'Editar');
$__initialMonthly = (float)($contract->monthly_value ?? 0);
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
			<div class="row contract-date-row contract-vigencia-row">
				<div class="col-sm-6">
					<?= $this->Form->control('start_date', ['type' => 'date', 'label' => __('Início'), 'class' => 'form-control']) ?>
				</div>
				<div class="col-sm-6">
					<?= $this->Form->control('end_date', ['type' => 'date', 'label' => __('Fim'), 'class' => 'form-control']) ?>
				</div>
			</div>
			<div class="contract-vigencia-panel">
				<h5 class="mb-2"><?= __('Vigência') ?></h5>
				<div class="row contract-vigencia-info">
					<div class="col-md-4 col-sm-6">
						<div class="form-group">
							<label class="control-label"><?= __('Início') ?></label>
							<input type="text" class="form-control" id="vigencia_inicio_preview" value="—" readonly>
						</div>
					</div>
					<div class="col-md-4 col-sm-6">
						<div class="form-group">
							<label class="control-label"><?= __('Fim') ?></label>
							<input type="text" class="form-control" id="vigencia_fim_preview" value="—" readonly>
						</div>
					</div>
					<div class="col-md-4 col-sm-12">
						<div class="form-group">
							<label class="control-label"><?= __('Prazo da vigência') ?></label>
							<input type="text" class="form-control" id="vigencia_prazo_preview" value="—" readonly>
						</div>
					</div>
				</div>
				<div class="row contract-vigencia-info">
					<div class="col-md-6 col-sm-12">
						<div class="form-group">
							<label class="control-label"><?= __('Prazo comercial') ?></label>
							<input type="text" class="form-control" id="vigencia_prazo_comercial_preview" value="—" readonly>
						</div>
					</div>
				</div>
				<div class="alert alert-warning small contract-vigencia-alert" id="vigencia_prazo_alert" style="display:none;">
					<?= __('Atenção: vigência fora dos prazos comerciais padrão (12, 24, 36, 48 ou 60 meses).') ?>
				</div>
			</div>
			<div class="contract-valores-panel" data-monthly-value="<?= h(number_format($__initialMonthly, 2, '.', '')) ?>">
				<h5 class="mb-2"><?= __('Valores do contrato') ?></h5>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label"><?= __('Valor mensal') ?></label>
							<input type="text" class="form-control" id="contract_monthly_value_preview" readonly>
							<small class="text-muted"><?= __('Calculado automaticamente pela soma da aba Serviços.') ?></small>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label"><?= __('Valor total') ?></label>
							<input type="text" class="form-control" id="contract_total_value_preview" readonly>
							<small class="text-muted"><?= __('Calculado automaticamente pela vigência x valor mensal.') ?></small>
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
	var startInputs = document.querySelectorAll('[name="start_date[year]"], [name="start_date[month]"], [name="start_date[day]"]');
	var endInputs = document.querySelectorAll('[name="end_date[year]"], [name="end_date[month]"], [name="end_date[day]"]');
	var startPreview = document.getElementById('vigencia_inicio_preview');
	var endPreview = document.getElementById('vigencia_fim_preview');
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
	var pad = function (n) {
		return String(n || '').padStart(2, '0');
	};
	var makeDate = function (prefix) {
		var y = document.querySelector('[name="' + prefix + '[year]"]');
		var m = document.querySelector('[name="' + prefix + '[month]"]');
		var d = document.querySelector('[name="' + prefix + '[day]"]');
		if (!y || !m || !d || !y.value || !m.value || !d.value) {
			return null;
		}
		var date = new Date(Number(y.value), Number(m.value) - 1, Number(d.value));
		if (isNaN(date.getTime())) {
			return null;
		}
		return date;
	};
	var fmtDate = function (date) {
		return pad(date.getDate()) + '/' + pad(date.getMonth() + 1) + '/' + date.getFullYear();
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
		var start = makeDate('start_date');
		var end = makeDate('end_date');
		monthlyPreview.value = money(monthlyValue);
		startPreview.value = start ? fmtDate(start) : '—';
		endPreview.value = end ? fmtDate(end) : '—';
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
	Array.prototype.forEach.call(startInputs, function (el) { el.addEventListener('change', refresh); });
	Array.prototype.forEach.call(endInputs, function (el) { el.addEventListener('change', refresh); });
	refresh();
})();
</script>
