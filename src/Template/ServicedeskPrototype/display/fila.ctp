<?php
/**
 * @var \App\View\AppView $this
 * @var string $title
 * @var array<string,mixed> $filaRef
 */
$this->assign('title', $title);
$ref = $filaRef ?? [];
$snap = (array)($ref['snap'] ?? []);
$sla = (array)($ref['sla'] ?? []);
$kpis = (array)($ref['kpis'] ?? []);
$violados = (array)($ref['violados'] ?? []);
$avgByState = (array)($ref['avg_by_state'] ?? []);
$fila = (array)($ref['fila'] ?? []);
$rows = (array)($fila['rows'] ?? []);
$page = (int)($fila['page'] ?? 1);
$pages = (int)($fila['pages'] ?? 1);
$total = (int)($fila['total'] ?? 0);
$totalEmpresa = (int)($ref['total_empresa'] ?? $total);
$gerado = (string)($ref['gerado_em'] ?? '');
$overdue = (int)($sla['overdue'] ?? 0);
$near = (int)($sla['near_due'] ?? 0);
$paused = (int)($sla['paused'] ?? 0);
$assignment = (array)($ref['assignment'] ?? []);
$H = $this->ServicedeskPrototype;
$patchAssignmentUrl = $this->Url->build(['controller' => 'Tickets', 'action' => 'apiPatchAssignment', '_full' => true]);
$apiTecnicosUrl = $this->Url->build(['controller' => 'Tickets', 'action' => 'apiTecnicosLista', '_full' => true]);
$ticketsApiRoot = rtrim($this->Url->build(['controller' => 'Tickets', '_full' => true]), '/');
$prioLabelsJson = json_encode([
	1 => __('Baixo'),
	2 => __('Médio'),
	3 => __('Alto'),
	4 => __('Crítico'),
], JSON_UNESCAPED_UNICODE);
$toolbar = [
	['label' => '📊 ' . __('Dashboard'), 'url' => $H->sdpPage('dashboard')],
	['label' => '🎯 ' . __('Meus tickets'), 'url' => $H->sdpPage('meus')],
	['label' => '📋 ' . __('Kanban'), 'url' => $H->sdpPage('kanban')],
	['label' => '📊 ' . __('Relatórios'), 'url' => $H->sdpPage('relatorios')],
	['label' => '📚 ' . __('KB'), 'url' => $H->sdpPage('kb')],
	['label' => '⚙ ' . __('Config'), 'url' => $H->sdpPage('config')],
	['label' => '+ ' . __('Abrir chamado'), 'url' => ['controller' => 'Servicedesk', 'action' => 'add'], 'class' => 'btn btn-primary btn-sm'],
];
?>
<div class="row">
	<div class="col-12 pgm-sd-prototype" id="pg-sd-fila">
		<?= $this->element('ServicedeskPrototype/ref/header', [
			'title' => __('Fila técnica'),
			'subtitle' => '',
			'toolbar' => $toolbar,
		]) ?>

		<div class="card" style="margin-bottom:14px;padding:18px;">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:6px;">
				<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;">
					<?= h(__('SLA por etapa')) ?>
					<span style="font-weight:400;text-transform:none;letter-spacing:0;margin-left:6px;"><?= h(__('atualização')) ?> <?= h($gerado) ?></span>
				</div>
			</div>
			<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:12px;">
				<div style="padding:14px 16px;background:#FEF2F2;border:1px solid #F8D8DA;border-radius:var(--radius);">
					<div style="font-size:11px;color:#7A1822;text-transform:uppercase;font-weight:600;letter-spacing:.4px;"><?= h(__('SLA Estourado')) ?></div>
					<div style="font-size:32px;font-weight:700;color:#7A1822;line-height:1;margin-top:4px;"><?= h((string)$overdue) ?></div>
				</div>
				<div style="padding:14px 16px;background:#FFFBF0;border:1px solid #FAEEDA;border-radius:var(--radius);">
					<div style="font-size:11px;color:#8A4D02;text-transform:uppercase;font-weight:600;letter-spacing:.4px;"><?= h(__('Próximos do limite')) ?></div>
					<div style="font-size:32px;font-weight:700;color:#8A4D02;line-height:1;margin-top:4px;"><?= h((string)$near) ?></div>
				</div>
				<div style="padding:14px 16px;background:#F0FDF4;border:1px solid #C5F1D8;border-radius:var(--radius);">
					<div style="font-size:11px;color:var(--teal-dark);text-transform:uppercase;font-weight:600;letter-spacing:.4px;"><?= h(__('Pausados')) ?></div>
					<div style="font-size:32px;font-weight:700;color:var(--teal-dark);line-height:1;margin-top:4px;"><?= h((string)$paused) ?></div>
				</div>
			</div>
			<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px;">
				<?php
				$kpiLabels = [
					'escalados_hoje' => __('Escalonados hoje'),
					'criticos_abertos' => __('Tickets críticos'),
					'sem_tecnico' => __('Sem técnico'),
					'aguardando_cliente' => __('Aguard. cliente'),
				];
				foreach ($kpiLabels as $key => $lbl) :
					$val = (int)($kpis[$key] ?? 0);
					?>
					<div style="padding:12px 14px;background:#fff;border:1px solid var(--border-light);border-radius:var(--radius);">
						<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;"><?= h($lbl) ?></div>
						<div style="font-size:24px;font-weight:700;line-height:1;margin-top:4px;"><?= h((string)$val) ?></div>
					</div>
				<?php endforeach; ?>
			</div>
			<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
				<?= $this->Html->link(__('Configurar SLA →'), $H->sdpPage('config'), ['style' => 'color:var(--teal);font-size:13px;font-weight:600;text-decoration:none;']) ?>
			</div>
			<div class="g2">
				<div style="padding:14px 16px;background:#fff;border:1px solid var(--border-light);border-radius:var(--radius);">
					<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;margin-bottom:10px;"><?= h(__('Alertas ativos')) ?></div>
					<div style="display:flex;flex-direction:column;gap:6px;">
						<?php if ($violados === []) : ?>
							<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Nenhum alerta SLA no escopo.')) ?></div>
						<?php else : ?>
							<?php foreach (array_slice($violados, 0, 8) as $v) : ?>
								<?php
								$vid = (int)($v['id'] ?? 0);
								$vlimRaw = $v['data_limite_resolucao'] ?? null;
								$vlim = '—';
								if ($vlimRaw instanceof \DateTimeInterface) {
									$vlim = $vlimRaw->format('d/m/Y H:i');
								} elseif (is_string($vlimRaw) && $vlimRaw !== '') {
									try {
										$vlim = (new \DateTimeImmutable($vlimRaw))->format('d/m/Y H:i');
									} catch (\Exception $e) {
										$vlim = $vlimRaw;
									}
								}
								?>
								<div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;">
									<?= $this->Html->link('#' . $vid, $H->sdpTicketUrl($vid), ['style' => 'font-family:monospace;font-weight:600;color:var(--teal);']) ?>
									<span style="color:#7A1822;font-weight:600;"><?= h(__('SLA estourado')) ?></span>
									<span style="color:var(--text-muted);font-family:monospace;"><?= h($vlim) ?></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
				<div style="padding:14px 16px;background:#fff;border:1px solid var(--border-light);border-radius:var(--radius);">
					<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;margin-bottom:10px;"><?= h(__('Tempo médio por etapa')) ?></div>
					<div style="display:flex;flex-direction:column;gap:6px;">
						<?php if ($avgByState === []) : ?>
							<div style="font-size:12px;color:var(--text-muted);">—</div>
						<?php else : ?>
							<?php foreach ($avgByState as $entry) : ?>
								<?php
								if (!is_array($entry)) {
									continue;
								}
								$stateLabel = (string)($entry['label'] ?? '');
								$seconds = (int)($entry['avg_seconds'] ?? 0);
								if ($stateLabel === '') {
									continue;
								}
								?>
								<div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;">
									<span><?= h($stateLabel) ?></span>
									<span style="font-weight:700;color:var(--teal-dark);"><?= h($H->formatSlaSeconds($seconds)) ?></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<div class="card" style="margin-bottom:14px;padding:12px 14px;">
			<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
				<div style="display:flex;align-items:baseline;gap:6px;font-size:13px;flex-shrink:0;">
					<strong style="font-size:18px;color:var(--teal-dark);"><?= h((string)$totalEmpresa) ?></strong>
					<span style="color:var(--text-muted);"><?= h(__('na empresa')) ?></span>
				</div>
				<input type="text" placeholder="<?= h(__('Buscar nº, cliente ou assunto')) ?>" style="flex:1;min-width:240px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;" disabled />
				<select class="sdp-select" disabled><option><?= h(__('Aguardando + Em execução')) ?></option></select>
				<select class="sdp-select" disabled><option><?= h(__('Todas as filas')) ?></option></select>
				<select class="sdp-select" disabled><option><?= h(__('Todos os níveis')) ?></option></select>
				<select class="sdp-select" disabled><option><?= h(__('Qualquer técnico')) ?></option></select>
			</div>
		</div>

		<div class="card" style="padding:0;overflow:hidden;">
			<div style="overflow-x:auto;">
				<table style="width:100%;border-collapse:collapse;font-size:12px;">
					<thead>
						<tr style="border-bottom:1px solid var(--border);">
							<?php foreach ([__('Ticket'), __('Autor'), __('Data'), __('Assunto'), __('Prioridade'), __('Status'), __('Fila'), __('Nível'), __('Técnico'), __('Tempo'), __('Cliente'), __('Ações')] as $th) : ?>
								<th style="padding:14px 12px;text-align:<?= $th === __('Ações') ? 'right' : 'left' ?>;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;letter-spacing:.4px;"><?= h($th) ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $row) : ?>
							<?= $this->element('ServicedeskPrototype/ref/fila_row', ['row' => $row, 'assignment' => $assignment]) ?>
						<?php endforeach; ?>
						<?php if ($rows === []) : ?>
							<tr><td colspan="12" style="padding:22px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum ticket no escopo atual.')) ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div style="padding:10px 14px;background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;font-size:12px;border-top:1px solid var(--border-light);">
				<span style="color:var(--text-muted);"><?= h(__('Mostrando')) ?> <strong><?= h((string)count($rows)) ?></strong> <?= h(__('de')) ?> <strong><?= h((string)$total) ?></strong> <?= h(__('tickets')) ?></span>
				<?php if ($pages > 1) : ?>
					<div style="display:flex;gap:4px;align-items:center;">
						<?php if ($page > 1) : ?>
							<?= $this->Html->link('‹', ['action' => 'fila', '?' => ['page' => $page - 1]], ['class' => 'btn btn-ghost btn-xs']) ?>
						<?php else : ?>
							<button class="btn btn-ghost btn-xs" disabled>‹</button>
						<?php endif; ?>
						<span class="btn btn-ghost btn-xs" style="background:var(--teal-light);color:var(--teal-dark);"><?= h((string)$page) ?></span>
						<?php if ($page < $pages) : ?>
							<?= $this->Html->link('›', ['action' => 'fila', '?' => ['page' => $page + 1]], ['class' => 'btn btn-ghost btn-xs']) ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<?php
$this->Html->scriptBlock(
	'(function(){' .
	'var csrf=document.querySelector(\'meta[name="csrfToken"]\');' .
	'var token=csrf?csrf.getAttribute(\'content\'):\'\';' .
	'var patchAssign=' . json_encode(rtrim($patchAssignmentUrl, '/')) . ';' .
	'var ticketsRoot=' . json_encode($ticketsApiRoot) . ';' .
	'var apiTecnicos=' . json_encode($apiTecnicosUrl) . ';' .
	'var prioLabels=' . $prioLabelsJson . ';' .
	'var canAssign=' . (!empty($assignment['can_assign']) ? 'true' : 'false') . ';' .
	'var allTecnicos=' . json_encode(array_values((array)($assignment['tecnicos'] ?? [])), JSON_UNESCAPED_UNICODE) . ';' .
	'function msg(row,text,ok){var el=row.querySelector(\'.sdp-fila-save-msg\');if(!el)return;el.textContent=text;el.style.display=\'inline\';el.style.color=ok?\'var(--teal-dark)\':\'#7A1822\';if(text)setTimeout(function(){el.style.display=\'none\';},4000);}' .
	'function patchJson(url,body,row,okMsg){msg(row,' . json_encode(__('A guardar…')) . ',true);return fetch(url,{method:\'PATCH\',credentials:\'same-origin\',headers:{\'Content-Type\':\'application/json\',\'Accept\':\'application/json\',\'X-CSRF-Token\':token},body:JSON.stringify(body)}).then(function(r){return r.json().then(function(j){return{ok:r.ok,body:j};});}).then(function(res){if(res.ok&&res.body&&res.body.ok!==false){msg(row,okMsg||' . json_encode(__('Guardado.')) . ',true);return res;}var m=(res.body&&res.body.message)||(res.body&&res.body.error)||' . json_encode(__('Erro ao guardar.')) . ';msg(row,m,false);return res;}).catch(function(){msg(row,' . json_encode(__('Erro de rede.')) . ',false);});}' .
	'function parseQids(s){if(!s)return[];return String(s).split(\',\').map(function(x){return parseInt(x,10);}).filter(function(x){return x>0;});}' .
	'function nivelFromQueue(sel){var o=sel.options[sel.selectedIndex];return o&&o.getAttribute?o.getAttribute(\'data-nivel\')||\'\':\'\';}' .
	'function syncNivel(row){var q=row.querySelector(\'.sdp-fila-queue\');var cell=row.querySelector(\'.sdp-fila-nivel-cell\');if(!cell)return;var n=row.getAttribute(\'data-sdp-nivel\')||\'\';if(q&&q.value){var qn=nivelFromQueue(q);if(qn)n=qn;}cell.textContent=n||\'—\';}' .
	'function filterQueuesByTecnico(row){var q=row.querySelector(\'.sdp-fila-queue\');var t=row.querySelector(\'.sdp-fila-tecnico\');if(!q||!t)return;var allowed=null;var opt=t.options[t.selectedIndex];if(t.value&&opt)allowed=parseQids(opt.getAttribute(\'data-queue-ids\'));Array.prototype.forEach.call(q.options,function(o){if(!o.value){o.hidden=false;return;}if(!t.value||!allowed||allowed.length===0){o.hidden=false;return;}o.hidden=allowed.indexOf(parseInt(o.value,10))<0;});if(q.value&&q.options[q.selectedIndex]&&q.options[q.selectedIndex].hidden){q.value=\'\';syncNivel(row);}}' .
	'function fillTecnicos(sel,list,selected,keepAttrs){var cur=selected!==undefined&&selected!==null?String(selected):String(sel.value||\'\');sel.innerHTML=\'\';var o0=document.createElement(\'option\');o0.value=\'\';o0.textContent=' . json_encode(__('Sem atribuição')) . ';sel.appendChild(o0);(list||[]).forEach(function(t){var o=document.createElement(\'option\');o.value=String(t.id);o.textContent=t.name;if(keepAttrs&&t.queue_ids&&t.queue_ids.length)o.setAttribute(\'data-queue-ids\',t.queue_ids.join(\',\'));if(String(t.id)===cur)o.selected=true;sel.appendChild(o);});}' .
	'function loadTecnicos(row,qid,keep){var sel=row.querySelector(\'.sdp-fila-tecnico\');if(!sel||!qid)return;var url=apiTecnicos+(apiTecnicos.indexOf(\'?\')>=0?\'&\':\'?\')+\'queue_id=\'+encodeURIComponent(qid);fetch(url,{credentials:\'same-origin\',headers:{\'Accept\':\'application/json\'}}).then(function(r){return r.json();}).then(function(d){if(d&&d.ok){var list=(d.tecnicos||[]).map(function(t){var m=null;for(var i=0;i<allTecnicos.length;i++){if(String(allTecnicos[i].id)===String(t.id)){m=allTecnicos[i];break;}}return{id:t.id,name:t.name,queue_ids:m?m.queue_ids:[]};});fillTecnicos(sel,list,keep,true);}}).catch(function(){});}' .
	'function saveAssignment(row){if(!canAssign)return;var tid=row.getAttribute(\'data-sdp-ticket-id\');var tec=row.querySelector(\'.sdp-fila-tecnico\');var que=row.querySelector(\'.sdp-fila-queue\');if(!tid||!tec)return;var tecnicoId=parseInt(tec.value,10);var filaId=que?parseInt(que.value,10):0;if(!tecnicoId)return;if(que&&!filaId){msg(row,' . json_encode(__('Selecione a fila.')) . ',false);return;}var body={tecnico_id:tecnicoId};if(filaId)body.fila_id=filaId;patchJson(patchAssign+\'/\'+tid+\'/assignment\',body,row,' . json_encode(__('Atribuído.')) . ').then(function(res){if(res&&res.ok&&res.body&&res.body.ok){tec.style.color=\'var(--text-muted)\';tec.style.fontStyle=\'normal\';}});}' .
	'function savePriority(row){var tid=row.getAttribute(\'data-sdp-ticket-id\');var sel=row.querySelector(\'.sdp-fila-prioridade\');if(!tid||!sel)return;var p=parseInt(sel.value,10);var label=prioLabels[p]||sel.options[sel.selectedIndex].textContent;return patchJson(ticketsRoot+\'/\'+tid+\'/priority\',{prioridade:label},row,' . json_encode(__('Prioridade atualizada.')) . ');}' .
	'function saveStatus(row){var tid=row.getAttribute(\'data-sdp-ticket-id\');var sel=row.querySelector(\'.sdp-fila-status\');if(!tid||!sel)return;var o=sel.options[sel.selectedIndex];var label=o?o.getAttribute(\'data-status-api\')||o.textContent:\'\';return patchJson(ticketsRoot+\'/\'+tid+\'/status\',{status:label},row,' . json_encode(__('Status atualizado.')) . ').then(function(res){if(res&&res.ok&&res.body&&res.body.situacaoLabel){var pill=row.querySelector(\'.sdp-fila-status-pill\');if(pill)pill.textContent=res.body.situacaoLabel;}});}' .
	'document.addEventListener(\'click\',function(ev){if(!ev.target.closest(\'.sdp-fila-actions-wrap\')){document.querySelectorAll(\'.sdp-fila-actions-menu\').forEach(function(m){m.style.display=\'none\';});document.querySelectorAll(\'.sdp-fila-actions-btn\').forEach(function(b){b.setAttribute(\'aria-expanded\',\'false\');});}});' .
	'document.querySelectorAll(\'.sdp-fila-row\').forEach(function(row){syncNivel(row);if(canAssign){filterQueuesByTecnico(row);var q=row.querySelector(\'.sdp-fila-queue\');var t=row.querySelector(\'.sdp-fila-tecnico\');if(t){t.addEventListener(\'change\',function(){filterQueuesByTecnico(row);if(!t.value)return;if(q&&!q.value){msg(row,' . json_encode(__('Selecione a fila antes do técnico.')) . ',false);return;}saveAssignment(row);});}if(q){q.addEventListener(\'change\',function(){syncNivel(row);var keep=t?t.value:\'\';if(q.value)loadTecnicos(row,q.value,keep);else if(t)fillTecnicos(t,allTecnicos,keep,true);if(t&&t.value&&q.value)saveAssignment(row);});}}' .
	'var pr=row.querySelector(\'.sdp-fila-prioridade\');if(pr)pr.addEventListener(\'change\',function(){savePriority(row);});' .
	'var st=row.querySelector(\'.sdp-fila-status\');if(st)st.addEventListener(\'change\',function(){saveStatus(row);});' .
	'var ab=row.querySelector(\'.sdp-fila-actions-btn\');var menu=row.querySelector(\'.sdp-fila-actions-menu\');if(ab&&menu){ab.addEventListener(\'click\',function(ev){ev.preventDefault();ev.stopPropagation();var open=menu.style.display===\'block\';document.querySelectorAll(\'.sdp-fila-actions-menu\').forEach(function(m){m.style.display=\'none\';});document.querySelectorAll(\'.sdp-fila-actions-btn\').forEach(function(b){b.setAttribute(\'aria-expanded\',\'false\');});if(!open){menu.style.display=\'block\';ab.setAttribute(\'aria-expanded\',\'true\');}});}});' .
	'})();',
	['block' => 'script']
);
?>
