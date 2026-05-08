<?php
/**
 * Aba SLA na ficha do contrato — dados via GET/POST JSON em contractSlaApi.
 *
 * @var int $contractId
 * @var string $apiUrl
 * @var int $idcliente
 */
$contractId = (int)($contractId ?? 0);
$apiUrl = (string)($apiUrl ?? '');
$idcliente = (int)($idcliente ?? 0);
?>
<div class="adv-cm-sla-wrap">
	<div class="mb-2">
		<?php if ($idcliente > 0): ?>
		<?= $this->Html->link(__('← Voltar ao cliente'), ['controller' => 'Clientes', 'action' => 'view', $idcliente], ['class' => 'btn btn-sm btn-default adv-cm-back-link']) ?>
		<?php else: ?>
		<?= $this->Html->link(__('← Voltar aos clientes'), ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'btn btn-sm btn-default adv-cm-back-link']) ?>
		<?php endif; ?>
	</div>
	<p class="text-muted small mb-2">
		<?= __('Políticas de SLA por estado do workflow, escopo de serviço/problema/fila/nível. Workflow é definido pelos estados cadastrados; cada política refere-se a um estado.') ?>
	</p>
	<div id="cm-sla-alert" class="alert alert-danger" style="display:none;"></div>
	<p id="cm-sla-loading" class="text-muted small" style="display:none;"><?= __('Carregando…') ?></p>
	<div class="mb-2">
		<button type="button" class="btn btn-sm btn-success" id="cm-sla-btn-new"><?= __('Nova política') ?></button>
		<button type="button" class="btn btn-sm btn-default" id="cm-sla-btn-refresh"><?= __('Atualizar') ?></button>
	</div>
	<div class="table-responsive">
		<table class="table table-sm table-striped table-bordered mb-0" id="cm-sla-table">
			<thead>
				<tr>
					<th><?= __('ID') ?></th>
					<th><?= __('Estado') ?></th>
					<th><?= __('Serviço') ?></th>
					<th><?= __('Problema') ?></th>
					<th><?= __('Fila') ?></th>
					<th><?= __('Nível') ?></th>
					<th class="text-right"><?= __('Resp.') ?></th>
					<th class="text-right"><?= __('Resol.') ?></th>
					<th><?= __('Pausa') ?></th>
					<th><?= __('Esc.') ?></th>
					<th><?= __('Ativo') ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</div>

<div class="modal fade" id="cmSlaPolicyModal" tabindex="-1" role="dialog" aria-labelledby="cmSlaPolicyModalTitle">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="cmSlaPolicyModalTitle"><?= __('Política SLA') ?></h4>
			</div>
			<div class="modal-body">
				<input type="hidden" id="cm-sla-edit-id" value="">
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group">
							<label for="cm-sla-workflow_state_id"><?= __('Estado do workflow') ?> <span class="text-danger">*</span></label>
							<select class="form-control input-sm" id="cm-sla-workflow_state_id"></select>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="form-group">
							<label for="cm-sla-contract_service_id"><?= __('Serviço (contrato)') ?></label>
							<select class="form-control input-sm" id="cm-sla-contract_service_id">
								<option value=""><?= __('Qualquer') ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group">
							<label for="cm-sla-problema_id"><?= __('Problema') ?></label>
							<select class="form-control input-sm" id="cm-sla-problema_id">
								<option value=""><?= __('Qualquer') ?></option>
							</select>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="form-group">
							<label for="cm-sla-queue_id"><?= __('Fila') ?></label>
							<select class="form-control input-sm" id="cm-sla-queue_id">
								<option value=""><?= __('Qualquer') ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group">
							<label for="cm-sla-support_level_id"><?= __('Nível de suporte') ?></label>
							<select class="form-control input-sm" id="cm-sla-support_level_id">
								<option value=""><?= __('Qualquer') ?></option>
							</select>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="form-group">
							<label for="cm-sla-resposta_minutos"><?= __('Resposta (min)') ?></label>
							<input type="number" min="0" class="form-control input-sm" id="cm-sla-resposta_minutos" placeholder="">
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group">
							<label for="cm-sla-resolucao_minutos"><?= __('Resolução (min)') ?></label>
							<input type="number" min="0" class="form-control input-sm" id="cm-sla-resolucao_minutos" placeholder="">
						</div>
					</div>
					<div class="col-sm-6" style="padding-top:24px;">
						<label class="checkbox-inline"><input type="checkbox" id="cm-sla-pausa_sla"> <?= __('Pausar SLA no estado (pausa relógio)') ?></label>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<label class="checkbox-inline"><input type="checkbox" id="cm-sla-is_final"> <?= __('Estado final (sem escalonar)') ?></label>
					</div>
				</div>
				<hr>
				<div class="row">
					<div class="col-sm-12">
						<label class="checkbox-inline"><input type="checkbox" id="cm-sla-auto_escalar"> <?= __('Autoescalonamento após vencimento') ?></label>
					</div>
				</div>
				<div id="cm-sla-esc-block" style="display:none;">
					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								<label for="cm-sla-escalate_to_state_id"><?= __('Escalonar para estado') ?></label>
								<select class="form-control input-sm" id="cm-sla-escalate_to_state_id">
									<option value=""><?= __('—') ?></option>
								</select>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-group">
								<label for="cm-sla-escalate_to_queue_id"><?= __('Escalonar para fila') ?></label>
								<select class="form-control input-sm" id="cm-sla-escalate_to_queue_id">
									<option value=""><?= __('—') ?></option>
								</select>
							</div>
						</div>
						<div class="col-sm-4">
							<div class="form-group">
								<label for="cm-sla-escalate_to_support_level_id"><?= __('Escalonar para nível') ?></label>
								<select class="form-control input-sm" id="cm-sla-escalate_to_support_level_id">
									<option value=""><?= __('—') ?></option>
								</select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								<label for="cm-sla-escalate_after_minutos"><?= __('Tolerância após vencimento (min)') ?></label>
								<input type="number" min="0" class="form-control input-sm" id="cm-sla-escalate_after_minutos" value="0">
							</div>
						</div>
						<div class="col-sm-8" style="padding-top:24px;">
							<label class="checkbox-inline"><input type="checkbox" id="cm-sla-notify_manager"> <?= __('Notif. gestor') ?></label>
							<label class="checkbox-inline"><input type="checkbox" id="cm-sla-notify_customer"> <?= __('Notif. cliente') ?></label>
							<label class="checkbox-inline"><input type="checkbox" id="cm-sla-notify_technician"> <?= __('Notif. técnico') ?></label>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?= __('Fechar') ?></button>
				<button type="button" class="btn btn-primary" id="cm-sla-save"><?= __('Salvar') ?></button>
			</div>
		</div>
	</div>
</div>

<?php
$i18n = json_encode([
	'any' => (string)__('Qualquer'),
	'edit' => (string)__('Editar'),
	'activate' => (string)__('Ativar'),
	'deactivate' => (string)__('Inativar'),
	'yes' => (string)__('Sim'),
	'no' => (string)__('Não'),
	'apiMissing' => (string)__('URL da API indisponível.'),
	'loadFail' => (string)__('Falha ao carregar.'),
	'loadErr' => (string)__('Erro ao carregar políticas.'),
	'editTitle' => (string)__('Editar política SLA'),
	'newTitle' => (string)__('Nova política SLA'),
	'selectState' => (string)__('Selecione o estado do workflow.'),
	'saveFail' => (string)__('Não foi possível salvar.'),
	'saveErr' => (string)__('Erro ao salvar.'),
	'toggleFail' => (string)__('Falha ao alterar status.'),
	'netErr' => (string)__('Erro de rede.'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$apiUrlJson = json_encode($apiUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$this->Html->scriptBlock(
	"(function(){\n"
	. "var apiUrl = {$apiUrlJson};\n"
	. "var L = {$i18n};\n"
	. <<<'JSB'
var state = { options: null, policies: [], loaded: false };

function cmSlaShowErr(msg) {
	var jq = window.jQuery;
	if (!jq) return;
	jq("#cm-sla-alert").text(msg || "").show();
}
function cmSlaHideErr() {
	if (window.jQuery) window.jQuery("#cm-sla-alert").hide();
}

function labelFromList(list, id, fieldLabel) {
	if (!id) return "—";
	fieldLabel = fieldLabel || "label";
	var rows = list || [];
	for (var i = 0; i < rows.length; i++) {
		if (String(rows[i].id) === String(id)) {
			return rows[i][fieldLabel] || rows[i].nome || ("#" + id);
		}
	}
	return "#" + id;
}

function fillSelect($sel, rows, extraAny, labelField) {
	if (!$sel || !$sel.length) return;
	var v = $sel.val();
	$sel.empty();
	if (extraAny) {
		$sel.append(window.jQuery("<option>").attr("value", "").text(extraAny === true ? "—" : extraAny));
	}
	labelField = labelField || "label";
	(rows || []).forEach(function (r) {
		var lab = r[labelField] != null ? r[labelField] : (r.nome || ("#" + r.id));
		$sel.append(window.jQuery("<option>").attr("value", r.id).text(lab));
	});
	if (v) { $sel.val(v); }
}

function escHtml(s) {
	return String(s == null ? "" : s)
		.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
		.replace(/"/g,"&quot;");
}

function minStr(m) {
	if (m === null || m === "" || typeof m === "undefined") return "—";
	var n = parseInt(m, 10);
	if (isNaN(n)) return "—";
	return n + " min";
}

function renderTable() {
	var jq = window.jQuery;
	var $tb = jq("#cm-sla-table tbody");
	if (!$tb.length) return;
	$tb.empty();
	var o = state.options || {};
	var svcs = o.contract_services || [];
	var probs = o.problemas || [];
	var queues = o.queues || [];
	var levels = o.support_levels || [];
	(state.policies || []).forEach(function (p) {
		var escParts = [];
		if (p.auto_escalar) {
			if (p.escalate_to_state_id) escParts.push("→ " + escHtml(p.escalate_to_nome || ("#" + p.escalate_to_state_id)));
			if (p.escalate_to_queue_id) escParts.push("Q:" + p.escalate_to_queue_id);
			if (p.escalate_to_support_level_id) escParts.push("L:" + p.escalate_to_support_level_id);
			if (p.escalate_after_minutos) escParts.push("+" + p.escalate_after_minutos + " min");
		} else {
			escParts.push("—");
		}
		var row = "<tr>"
			+ "<td>" + escHtml(p.id) + "</td>"
			+ "<td>" + escHtml(p.estado_nome || ("#" + p.workflow_state_id)) + "</td>"
			+ "<td>" + escHtml(labelFromList(svcs, p.contract_service_id, "label")) + "</td>"
			+ "<td>" + escHtml(labelFromList(probs, p.problema_id, "label")) + "</td>"
			+ "<td>" + escHtml(labelFromList(queues, p.queue_id, "label")) + "</td>"
			+ "<td>" + escHtml(labelFromList(levels, p.support_level_id, "label")) + "</td>"
			+ "<td class=\"text-right\">" + escHtml(minStr(p.resposta_minutos)) + "</td>"
			+ "<td class=\"text-right\">" + escHtml(minStr(p.resolucao_minutos)) + "</td>"
			+ "<td>" + (p.pausa_sla ? "<span class=\"label label-info\">" + escHtml(L.yes) + "</span>" : "—") + "</td>"
			+ "<td class=\"small\">" + escParts.join(" ") + "</td>"
			+ "<td>" + (p.ativo ? "<span class=\"label label-success\">" + escHtml(L.yes) + "</span>" : "<span class=\"label label-default\">" + escHtml(L.no) + "</span>") + "</td>"
			+ "<td class=\"text-nowrap\">"
			+ "<button type=\"button\" class=\"btn btn-xs btn-default cm-sla-edit\" data-id=\"" + p.id + "\">" + escHtml(L.edit) + "</button> "
			+ "<button type=\"button\" class=\"btn btn-xs " + (p.ativo ? "btn-warning" : "btn-success") + " cm-sla-toggle\" data-id=\"" + p.id + "\" data-ativo=\"" + (p.ativo ? "0" : "1") + "\">"
			+ escHtml(p.ativo ? L.deactivate : L.activate) + "</button>"
			+ "</td></tr>";
		$tb.append(row);
	});
}

function reloadOptionSelects() {
	var jq = window.jQuery;
	var o = state.options || {};
	fillSelect(jq("#cm-sla-workflow_state_id"), o.workflow_states || [], false, "nome");
	fillSelect(jq("#cm-sla-contract_service_id"), o.contract_services || [], true, "label");
	jq("#cm-sla-contract_service_id option:first").text(L.any);
	fillSelect(jq("#cm-sla-problema_id"), o.problemas || [], true, "label");
	jq("#cm-sla-problema_id option:first").text(L.any);
	fillSelect(jq("#cm-sla-queue_id"), o.queues || [], true, "label");
	jq("#cm-sla-queue_id option:first").text(L.any);
	fillSelect(jq("#cm-sla-support_level_id"), o.support_levels || [], true, "label");
	jq("#cm-sla-support_level_id option:first").text(L.any);
	fillSelect(jq("#cm-sla-escalate_to_state_id"), o.escalate_states || [], true, "nome");
	jq("#cm-sla-escalate_to_state_id option:first").text("—");
	fillSelect(jq("#cm-sla-escalate_to_queue_id"), o.queues || [], true, "label");
	jq("#cm-sla-escalate_to_queue_id option:first").text("—");
	fillSelect(jq("#cm-sla-escalate_to_support_level_id"), o.support_levels || [], true, "label");
	jq("#cm-sla-escalate_to_support_level_id option:first").text("—");
}

function loadData() {
	if (!apiUrl) { cmSlaShowErr(L.apiMissing); return; }
	var jq = window.jQuery;
	if (!jq) return;
	cmSlaHideErr();
	jq("#cm-sla-loading").show();
	jq.ajax({ url: apiUrl, method: "GET", dataType: "json" })
		.done(function (data) {
			if (!data || !data.ok) {
				cmSlaShowErr((data && data.errors && data.errors[0]) ? data.errors[0] : L.loadFail);
				return;
			}
			state.options = data.options || {};
			state.policies = data.policies || [];
			state.loaded = true;
			reloadOptionSelects();
			renderTable();
		})
		.fail(function (xhr) {
			cmSlaShowErr(xhr.status + " — " + L.loadErr);
		})
		.always(function () { jq("#cm-sla-loading").hide(); });
}

function openModal(policy) {
	var jq = window.jQuery;
	reloadOptionSelects();
	if (policy) {
		jq("#cmSlaPolicyModalTitle").text(L.editTitle);
		jq("#cm-sla-edit-id").val(policy.id);
		jq("#cm-sla-workflow_state_id").val(String(policy.workflow_state_id));
		jq("#cm-sla-contract_service_id").val(policy.contract_service_id ? String(policy.contract_service_id) : "");
		jq("#cm-sla-problema_id").val(policy.problema_id ? String(policy.problema_id) : "");
		jq("#cm-sla-queue_id").val(policy.queue_id ? String(policy.queue_id) : "");
		jq("#cm-sla-support_level_id").val(policy.support_level_id ? String(policy.support_level_id) : "");
		jq("#cm-sla-resposta_minutos").val(policy.resposta_minutos != null ? policy.resposta_minutos : "");
		jq("#cm-sla-resolucao_minutos").val(policy.resolucao_minutos != null ? policy.resolucao_minutos : "");
		jq("#cm-sla-pausa_sla").prop("checked", !!policy.pausa_sla);
		jq("#cm-sla-is_final").prop("checked", !!policy.is_final);
		jq("#cm-sla-auto_escalar").prop("checked", !!policy.auto_escalar);
		jq("#cm-sla-escalate_to_state_id").val(policy.escalate_to_state_id ? String(policy.escalate_to_state_id) : "");
		jq("#cm-sla-escalate_to_queue_id").val(policy.escalate_to_queue_id ? String(policy.escalate_to_queue_id) : "");
		jq("#cm-sla-escalate_to_support_level_id").val(policy.escalate_to_support_level_id ? String(policy.escalate_to_support_level_id) : "");
		jq("#cm-sla-escalate_after_minutos").val(policy.escalate_after_minutos != null ? policy.escalate_after_minutos : 0);
		jq("#cm-sla-notify_manager").prop("checked", !!policy.notify_manager);
		jq("#cm-sla-notify_customer").prop("checked", !!policy.notify_customer);
		jq("#cm-sla-notify_technician").prop("checked", !!policy.notify_technician);
	} else {
		jq("#cmSlaPolicyModalTitle").text(L.newTitle);
		jq("#cm-sla-edit-id").val("");
		jq("#cm-sla-workflow_state_id").val(jq("#cm-sla-workflow_state_id option:first").val());
		jq("#cm-sla-contract_service_id").val("");
		jq("#cm-sla-problema_id").val("");
		jq("#cm-sla-queue_id").val("");
		jq("#cm-sla-support_level_id").val("");
		jq("#cm-sla-resposta_minutos").val("");
		jq("#cm-sla-resolucao_minutos").val("");
		jq("#cm-sla-pausa_sla").prop("checked", false);
		jq("#cm-sla-is_final").prop("checked", false);
		jq("#cm-sla-auto_escalar").prop("checked", false);
		jq("#cm-sla-escalate_to_state_id").val("");
		jq("#cm-sla-escalate_to_queue_id").val("");
		jq("#cm-sla-escalate_to_support_level_id").val("");
		jq("#cm-sla-escalate_after_minutos").val(0);
		jq("#cm-sla-notify_manager").prop("checked", false);
		jq("#cm-sla-notify_customer").prop("checked", false);
		jq("#cm-sla-notify_technician").prop("checked", false);
	}
	jq("#cm-sla-auto_escalar").trigger("change");
	jq("#cmSlaPolicyModal").modal("show");
}

function collectPayload(op) {
	var jq = window.jQuery;
	var p = { op: op };
	if (op === "update") {
		var eid = jq("#cm-sla-edit-id").val();
		if (!eid) return null;
		p.id = parseInt(eid, 10);
	}
	p.workflow_state_id = parseInt(jq("#cm-sla-workflow_state_id").val(), 10) || 0;
	var csv = jq("#cm-sla-contract_service_id").val();
	p.contract_service_id = csv ? parseInt(csv, 10) : null;
	var pb = jq("#cm-sla-problema_id").val();
	p.problema_id = pb ? parseInt(pb, 10) : null;
	var q = jq("#cm-sla-queue_id").val();
	p.queue_id = q ? parseInt(q, 10) : null;
	var lv = jq("#cm-sla-support_level_id").val();
	p.support_level_id = lv ? parseInt(lv, 10) : null;
	var rm = jq("#cm-sla-resposta_minutos").val();
	p.resposta_minutos = rm === "" ? null : parseInt(rm, 10);
	var r2 = jq("#cm-sla-resolucao_minutos").val();
	p.resolucao_minutos = r2 === "" ? null : parseInt(r2, 10);
	p.pausa_sla = jq("#cm-sla-pausa_sla").is(":checked") ? 1 : 0;
	p.is_final = jq("#cm-sla-is_final").is(":checked") ? 1 : 0;
	p.auto_escalar = jq("#cm-sla-auto_escalar").is(":checked") ? 1 : 0;
	if (p.auto_escalar) {
		var es = jq("#cm-sla-escalate_to_state_id").val();
		p.escalate_to_state_id = es ? parseInt(es, 10) : null;
		var eq = jq("#cm-sla-escalate_to_queue_id").val();
		p.escalate_to_queue_id = eq ? parseInt(eq, 10) : null;
		var el = jq("#cm-sla-escalate_to_support_level_id").val();
		p.escalate_to_support_level_id = el ? parseInt(el, 10) : null;
		p.escalate_after_minutos = parseInt(jq("#cm-sla-escalate_after_minutos").val(), 10) || 0;
		p.notify_manager = jq("#cm-sla-notify_manager").is(":checked") ? 1 : 0;
		p.notify_customer = jq("#cm-sla-notify_customer").is(":checked") ? 1 : 0;
		p.notify_technician = jq("#cm-sla-notify_technician").is(":checked") ? 1 : 0;
	} else {
		p.escalate_to_state_id = null;
		p.escalate_to_queue_id = null;
		p.escalate_to_support_level_id = null;
		p.escalate_after_minutos = 0;
		p.notify_manager = 0;
		p.notify_customer = 0;
		p.notify_technician = 0;
	}
	return p;
}

function postJson(payload) {
	return window.jQuery.ajax({
		url: apiUrl,
		method: "POST",
		contentType: "application/json; charset=UTF-8",
		dataType: "json",
		data: JSON.stringify(payload)
	});
}

function cmSlaInit() {
	if (!window.jQuery) return;
	var jq = window.jQuery;
	jq("#cm-sla-auto_escalar").on("change", function () {
		if (jq(this).is(":checked")) jq("#cm-sla-esc-block").show();
		else jq("#cm-sla-esc-block").hide();
	});
	jq("#cm-sla-btn-new").on("click", function () { openModal(null); });
	jq("#cm-sla-btn-refresh").on("click", function () { loadData(); });
	jq("#cm-sla-save").on("click", function () {
		var isEdit = !!jq("#cm-sla-edit-id").val();
		var payload = collectPayload(isEdit ? "update" : "create");
		if (!payload) return;
		if (!payload.workflow_state_id) { alert(L.selectState); return; }
		cmSlaHideErr();
		postJson(payload).done(function (data) {
			if (!data || !data.ok) {
				alert((data && data.errors && data.errors.length) ? data.errors.join(" ") : L.saveFail);
				return;
			}
			jq("#cmSlaPolicyModal").modal("hide");
			loadData();
		}).fail(function (xhr) { alert(L.saveErr + " (" + xhr.status + ")"); });
	});
	jq("#cm-sla-table").on("click", ".cm-sla-edit", function () {
		var id = parseInt(jq(this).data("id"), 10);
		var pol = (state.policies || []).filter(function (x) { return parseInt(x.id, 10) === id; })[0];
		if (pol) openModal(pol);
	});
	jq("#cm-sla-table").on("click", ".cm-sla-toggle", function () {
		var id = parseInt(jq(this).data("id"), 10);
		var raw = jq(this).attr("data-ativo");
		var ativo = raw === "1" || raw === 1;
		postJson({ op: "toggle", id: id, ativo: ativo ? 1 : 0 }).done(function (data) {
			if (!data || !data.ok) {
				alert((data && data.errors && data.errors[0]) ? data.errors[0] : L.toggleFail);
				return;
			}
			loadData();
		}).fail(function () { alert(L.netErr); });
	});
	jq('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
		var target = (e.target && e.target.hash) ? e.target.hash : "";
		if (target === "#cm-tab-sla" && !state.loaded) loadData();
	});
}
if (document.readyState === "loading") {
	document.addEventListener("DOMContentLoaded", cmSlaInit);
} else {
	cmSlaInit();
}
})();
JSB
	,
	['block' => true],
);
