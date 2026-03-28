<?php
	use Cake\Routing\Router;
	$ticketsListAction = (isset($role) && (int)$role === 1) ? 'indexcliente' : 'index';
	$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => $ticketsListAction], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Abertura', [], ['class' => 'breadcrumb-item active']);
	if($role == 0) $email = null;
?>
<style>
	.tickets-add-wrap {
		width: 100%;
		max-width: none;
		margin: 0;
		padding: 0.5rem 0 2.5rem;
		box-sizing: border-box;
		background: #f1f5f9;
		border-radius: 0;
	}
	.sd-add-page {
		max-width: 72rem;
		margin: 0 auto;
		padding: 0 0.5rem;
	}
	@media (min-width: 768px) {
		.sd-add-page { padding: 0 1.25rem; }
	}
	.sd-add-header {
		display: flex;
		flex-wrap: wrap;
		align-items: flex-start;
		justify-content: space-between;
		gap: 1rem;
		margin-bottom: 1.5rem;
	}
	.sd-add-kicker {
		font-size: 0.875rem;
		font-weight: 600;
		color: #64748b;
		margin: 0;
	}
	.sd-add-title {
		font-size: 1.75rem;
		font-weight: 700;
		color: #0f172a;
		margin: 0.15rem 0 0;
		line-height: 1.2;
	}
	@media (min-width: 768px) {
		.sd-add-title { font-size: 1.875rem; }
	}
	.sd-add-sub {
		margin: 0.35rem 0 0;
		font-size: 0.875rem;
		color: #475569;
		line-height: 1.45;
		max-width: 36rem;
	}
	.sd-add-status {
		width: 100%;
		border-radius: 1rem;
		background: #fff;
		padding: 0.65rem 1rem;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
	}
	@media (min-width: 768px) {
		.sd-add-status { width: auto; max-width: 14rem; }
	}
	.sd-add-status-label {
		font-size: 0.65rem;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #94a3b8;
		margin: 0;
	}
	.sd-add-status-value {
		margin: 0.15rem 0 0;
		font-size: 0.875rem;
		font-weight: 600;
		color: #00c08b;
		transition: color 0.15s ease;
	}
	.sd-add-status-value.is-ok { color: #00c08b; }
	.sd-add-status-value.is-warn { color: #d97706; }
	.sd-add-status-value.is-muted { color: #64748b; }
	.sd-add-status-value.is-danger { color: #dc2626; }
	.sd-sum-field {
		display: flex;
		flex-direction: column;
		gap: 0.35rem;
	}
	.sd-sum-field-label {
		font-size: 0.8125rem;
		font-weight: 600;
		color: #64748b;
	}
	.sd-sum-select {
		width: 100%;
		border-radius: 1rem !important;
		border: 1px solid #e2e8f0 !important;
		background: #f8fafc !important;
		padding: 0.5rem 0.75rem !important;
		font-size: 0.8125rem !important;
		color: #0f172a;
		min-height: 2.5rem;
	}
	.sd-sum-select:focus {
		border-color: #94a3b8 !important;
		outline: none;
		box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.35);
	}
	.sd-add-grid {
		display: grid;
		gap: 1.5rem;
		align-items: start;
	}
	@media (min-width: 1024px) {
		.sd-add-grid {
			grid-template-columns: 1.15fr 0.85fr;
		}
	}
	.sd-add-stack { display: flex; flex-direction: column; gap: 1.5rem; }
	.sd-add-card {
		background: #fff;
		border-radius: 1.5rem;
		padding: 1.35rem 1.25rem;
		border: 1px solid #e2e8f0;
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(226, 232, 240, 0.65);
	}
	@media (min-width: 768px) {
		.sd-add-card { padding: 1.5rem; }
	}
	.sd-add-card-head {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 0.75rem;
		margin-bottom: 1.15rem;
	}
	.sd-add-card-title {
		font-size: 1.05rem;
		font-weight: 600;
		color: #0f172a;
		margin: 0;
	}
	.sd-add-card-desc {
		font-size: 0.8125rem;
		color: #64748b;
		margin: 0.2rem 0 0;
		line-height: 1.4;
	}
	.sd-add-badge {
		flex-shrink: 0;
		border-radius: 9999px;
		background: #f1f5f9;
		padding: 0.25rem 0.65rem;
		font-size: 0.7rem;
		font-weight: 600;
		color: #475569;
	}
	.sd-add-fields {
		display: grid;
		gap: 1rem;
	}
	@media (min-width: 768px) {
		.sd-add-fields.sd-add-fields-2 { grid-template-columns: 1fr 1fr; }
	}
	.sd-add-field label.control-label {
		display: block;
		margin-bottom: 0.4rem;
		font-weight: 600;
		color: #334155;
		font-size: 0.8125rem;
	}
	.sd-add-field .form-control,
	.sd-add-field .bootstrap-select > .dropdown-toggle {
		border-radius: 1rem !important;
		border-color: #e2e8f0 !important;
		background-color: #f8fafc !important;
		min-height: 2.75rem;
		padding-top: 0.5rem;
		padding-bottom: 0.5rem;
		font-size: 0.875rem;
	}
	.sd-add-field .bootstrap-select { width: 100% !important; }
	.ticket-add-textarea.form-control {
		min-height: 9rem;
		border: 1px solid #e2e8f0;
		border-radius: 1rem;
		padding: 0.85rem 1rem;
		font-size: 0.875rem;
		line-height: 1.55;
		resize: vertical;
		background: #f8fafc;
		transition: border-color 0.15s ease, box-shadow 0.15s ease;
	}
	.ticket-add-textarea.form-control:focus {
		border-color: #94a3b8;
		box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.35);
		outline: 0;
	}
	.sd-add-label-block {
		display: block;
		margin-bottom: 0.4rem;
		font-weight: 600;
		color: #334155;
		font-size: 0.8125rem;
	}
	.ticket-add-hint {
		font-size: 0.75rem;
		color: #64748b;
		margin: 0 0 0.5rem;
		line-height: 1.45;
	}
	.ticket-dropzone {
		border: 2px dashed #cbd5e1;
		border-radius: 1rem;
		background: #f8fafc;
		min-height: 5.5rem;
		padding: 1rem 1.15rem;
		display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;
		transition: border-color 0.2s, background 0.2s;
	}
	.ticket-dropzone.ticket-dropzone--drag {
		border-color: #00c08b;
		background: rgba(29, 158, 117, 0.08);
	}
	.ticket-dropzone:hover {
		border-color: #94a3b8;
		background: #f1f5f9;
	}
	.ticket-files-chosen {
		list-style: none;
		margin: 0.65rem 0 0;
		padding: 0;
		font-size: 0.8125rem;
		color: #334155;
	}
	.ticket-files-chosen li {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		padding: 6px 10px;
		margin-bottom: 4px;
		background: #f1f5f9;
		border-radius: 0.65rem;
		border: 1px solid #e2e8f0;
	}
	.ticket-files-chosen .ticket-file-remove {
		flex-shrink: 0;
		border: none;
		background: transparent;
		color: #c0392b;
		font-size: 12px;
		cursor: pointer;
		text-decoration: underline;
		padding: 0 4px;
	}
	.ticket-files-chosen .ticket-file-remove:hover {
		color: #922b21;
	}
	.file-drop-area {
		position: relative;
		width: 100%;
		min-height: 3.5rem;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0;
		border: none;
	}
	.fake-btn {
		flex-shrink: 0;
		border-radius: 0.5rem;
		padding: 0.5rem 0.85rem;
		font-size: 0.8125rem;
		font-weight: 500;
		line-height: 1.4;
		white-space: normal;
		text-align: center;
		color: #64748b;
		pointer-events: none;
	}
	.file-input {
		position: absolute;
		left: 0;
		top: 0;
		height: 100%;
		width: 100%;
		cursor: pointer;
		opacity: 0;
	}
	.ticket-add-email.form-control {
		border-radius: 1rem;
		padding: 0.65rem 1rem;
		font-size: 0.875rem;
		min-height: 2.75rem;
		border: 1px solid #e2e8f0 !important;
		background-color: #f8fafc !important;
		color: #0f172a !important;
		-webkit-box-shadow: 0 0 0 1000px #f8fafc inset !important;
		box-shadow: 0 0 0 1000px #f8fafc inset !important;
	}
	.ticket-add-email.form-control:-webkit-autofill,
	.ticket-add-email.form-control:-webkit-autofill:hover,
	.ticket-add-email.form-control:-webkit-autofill:focus {
		-webkit-text-fill-color: #0f172a !important;
		-webkit-box-shadow: 0 0 0 1000px #f8fafc inset !important;
		box-shadow: 0 0 0 1000px #f8fafc inset !important;
		border-color: #e2e8f0 !important;
		transition: background-color 9999s ease-out 0s;
	}
	.ticket-add-lead {
		font-size: 0.875rem;
		color: #64748b;
		margin: 0 0 1rem;
		line-height: 1.5;
		padding: 0.75rem 1rem;
		background: #f8fafc;
		border-radius: 1rem;
		border: 1px solid #e2e8f0;
	}
	.ticket-add-lead strong {
		color: #0f172a;
	}
	.sd-sum-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 0.5rem;
		border-radius: 1rem;
		background: #f8fafc;
		padding: 0.65rem 0.85rem;
		font-size: 0.8125rem;
	}
	.sd-sum-item span:first-child { color: #64748b; }
	.sd-sum-item span:last-child {
		font-weight: 600;
		color: #0f172a;
		text-align: right;
		max-width: 58%;
		word-break: break-word;
	}
	.sd-sum-stack { display: flex; flex-direction: column; gap: 0.65rem; margin-top: 1rem; }
	.sd-rules-card {
		background: #fffbeb;
		border-radius: 1.5rem;
		padding: 1.35rem 1.25rem;
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
	}
	@media (min-width: 768px) {
		.sd-rules-card { padding: 1.5rem; }
	}
	.sd-rule {
		background: #fff;
		border-radius: 1rem;
		padding: 0.85rem 1rem;
		margin-bottom: 0.65rem;
	}
	.sd-rule:last-child { margin-bottom: 0; }
	.sd-rule-pill {
		display: inline-flex;
		border-radius: 9999px;
		padding: 0.2rem 0.5rem;
		font-size: 0.65rem;
		font-weight: 700;
	}
	.sd-rule-pill--u { background: #fee2e2; color: #b91c1c; }
	.sd-rule-pill--a { background: #ffedd5; color: #c2410c; }
	.sd-rule-pill--m { background: #fef9c3; color: #a16207; }
	.sd-rule-pill--b { background: #e2e8f0; color: #334155; }
	.sd-rule p {
		margin: 0.4rem 0 0;
		font-size: 0.8125rem;
		color: #334155;
		line-height: 1.4;
	}
	.sd-actions-card {
		background: #0f172a;
		color: #fff;
		border-radius: 1.5rem;
		padding: 1.35rem 1.25rem;
		box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
	}
	@media (min-width: 768px) {
		.sd-actions-card { padding: 1.5rem; }
	}
	.sd-actions-card h2 { color: #fff; font-size: 1.05rem; font-weight: 600; margin: 0; }
	.sd-actions-btns {
		display: flex;
		flex-direction: column;
		gap: 0.65rem;
		margin-top: 1.15rem;
	}
	.sd-actions-card .sd-btn-submit {
		width: 100%;
		border: none;
		border-radius: 1rem;
		padding: 0.75rem 1rem;
		font-size: 0.875rem;
		font-weight: 700;
		cursor: pointer;
		transition: filter 0.15s ease, opacity 0.15s;
		background: linear-gradient(135deg, #00c08b 0%, #008f68 100%) !important;
		color: #fff !important;
		box-shadow: 0 2px 10px rgba(29, 158, 117, 0.28);
	}
	.sd-actions-card .sd-btn-submit:hover:not(:disabled):not(.disabled) {
		filter: brightness(1.06);
	}
	.sd-actions-card .sd-btn-submit:disabled,
	.sd-actions-card .sd-btn-submit.disabled {
		opacity: 0.6;
		cursor: not-allowed;
		filter: none;
	}
	.sd-btn-ghost {
		display: block;
		width: 100%;
		text-align: center;
		border-radius: 1rem;
		border: 1px solid rgba(255, 255, 255, 0.22);
		background: transparent;
		color: #fff;
		padding: 0.65rem 1rem;
		font-size: 0.875rem;
		font-weight: 600;
		text-decoration: none;
		transition: background 0.15s;
	}
	.sd-btn-ghost:hover {
		background: rgba(255, 255, 255, 0.1);
		color: #fff;
		text-decoration: none;
	}
	select[multiple] {
		max-width: 100%;
	}
</style>
<div class="col-md-12 tickets-add-wrap">
	<div class="sd-add-page">
		<header class="sd-add-header">
			<div>
				<p class="sd-add-kicker">Central de Atendimento</p>
				<h1 class="sd-add-title">Abertura de Chamado</h1>
				<p class="sd-add-sub">
					Preencha os dados abaixo para registrar um incidente, requisição ou solicitação de acesso.
				</p>
			</div>
			<div class="sd-add-status" id="sd-add-status-card">
				<p class="sd-add-status-label">Status</p>
				<p class="sd-add-status-value is-muted" id="sd-atendimento-status-value">Carregando…</p>
			</div>
		</header>

		<?= $this->Form->create($ticket, ['enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material ticket-add-form']) ?>
		<div class="sd-add-role-holder" data-sd-role="<?= (int)$role ?>" hidden></div>

		<div class="sd-add-grid">
			<div class="sd-add-stack">
				<section class="sd-add-card">
					<div class="sd-add-card-head">
						<div>
							<h2 class="sd-add-card-title">Dados do solicitante</h2>
							<p class="sd-add-card-desc">Informações básicas do usuário.</p>
						</div>
						<span class="sd-add-badge">Etapa 1</span>
					</div>

					<?php if ($role == 1 && !empty($authUserName)) : ?>
						<p class="ticket-add-lead" style="margin-top:0">
							Chamado em nome de <strong><?= h($authUserName) ?></strong>.
						</p>
					<?php endif; ?>

					<div class="sd-add-fields sd-add-fields-2">
						<?php if ($role == 0) { ?>
							<div class="sd-add-field">
								<label class="control-label text-muted">Cliente</label>
								<?= $this->Form->control('idcliente', ['class' => 'selectpicker form-control', 'data-live-search' => true, 'empty' => 'Selecione o cliente', 'options' => $clientes, 'label' => false, 'required' => true]) ?>
							</div>
							<div class="sd-add-field">
								<label class="control-label text-muted">Solicitante</label>
								<?= $this->Form->control('idsolicitante', ['class' => 'selectpicker form-control', 'title' => 'Solicitante (opcional)', 'data-live-search' => true, 'options' => '', 'label' => false, 'required' => false]) ?>
							</div>
							<div class="sd-add-field">
								<label class="control-label text-muted">Nome do solicitante</label>
								<?= $this->Form->control('nomesolicitante', ['class' => 'form-control', 'title' => 'Nome do solicitante', 'label' => false, 'required' => false, 'placeholder' => 'Se não estiver cadastrado']) ?>
							</div>
							<div class="sd-add-field sd-email-main">
								<label class="control-label text-muted" for="email">E-mail para contato</label>
								<?= $this->Form->email('email', ['value' => $email, 'type' => 'text', 'id' => 'email', 'class' => 'email form-control ticket-add-email', 'label' => false, 'placeholder' => 'usuario@empresa.com']) ?>
							</div>
						<?php } else { ?>
							<div class="sd-add-field">
								<label class="control-label text-muted">Nome</label>
								<input type="text" class="form-control" readonly value="<?= h($authUserName) ?>" style="background:#e2e8f0;color:#475569;cursor:default" tabindex="-1" aria-label="Nome do solicitante">
							</div>
							<div class="sd-add-field">
								<label class="control-label text-muted" for="email">E-mail</label>
								<?= $this->Form->email('email', ['value' => $email, 'type' => 'text', 'id' => 'email', 'class' => 'email form-control ticket-add-email', 'label' => false, 'placeholder' => 'usuario@empresa.com']) ?>
							</div>
						<?php } ?>
					</div>
				</section>

				<section class="sd-add-card">
					<div class="sd-add-card-head">
						<div>
							<h2 class="sd-add-card-title">Detalhes do chamado</h2>
							<p class="sd-add-card-desc">Classifique o atendimento para direcionamento correto.</p>
						</div>
						<span class="sd-add-badge">Etapa 2</span>
					</div>

					<div class="sd-add-fields sd-add-fields-2">
						<?php if ($role == 0) { ?>
							<div class="sd-add-field">
								<label class="control-label text-muted">Assunto / Categoria</label>
								<?= $this->Form->control('assunto', ['value' => $assunto, 'class' => 'selectpicker form-control', 'title' => 'Escolha um assunto', 'data-live-search' => true, 'options' => C_TicketCategoriaClienteQuery, 'label' => false, 'required' => true]) ?>
							</div>
							<?php if (!empty($severidadeColumnReady)) : ?>
							<div class="sd-add-field">
								<label class="control-label text-muted">Urgência (severidade)</label>
								<?= $this->Form->control('severidade', [
									'type' => 'select',
									'options' => ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'urgente' => 'Urgente'],
									'class' => 'selectpicker form-control',
									'title' => 'Grau de severidade',
									'label' => false,
									'required' => true,
									'default' => 'media',
									'value' => $ticket->severidade ?? 'media',
								]) ?>
							</div>
							<?php endif; ?>
						<?php } else { ?>
							<div class="sd-add-field">
								<label class="control-label text-muted">Assunto / Categoria</label>
								<?= $this->Form->control('assunto', ['value' => $assunto, 'class' => 'selectpicker form-control', 'title' => 'Escolha um assunto', 'data-live-search' => true, 'options' => C_TicketCategoriaClienteQuery, 'label' => false, 'required' => true]) ?>
							</div>
							<?php if (!empty($severidadeColumnReady)) : ?>
							<div class="sd-add-field">
								<label class="control-label text-muted">Urgência (severidade)</label>
								<?= $this->Form->control('severidade', [
									'type' => 'select',
									'options' => ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'urgente' => 'Urgente'],
									'class' => 'selectpicker form-control',
									'title' => 'Grau de severidade',
									'label' => false,
									'required' => true,
									'default' => 'media',
									'value' => $ticket->severidade ?? 'media',
								]) ?>
							</div>
							<?php endif; ?>
						<?php } ?>
					</div>

					<div class="row hide data m-t-10" style="margin-top:0.75rem">
						<div class="col-md-6 col-xs-12 sd-add-field">
							<label class="control-label text-muted">Data da Visita</label>
							<?= $this->Form->text('data', ['class' => 'form-control datepicker', 'label' => false]) ?>
						</div>
					</div>

					<div style="margin-top:1rem">
						<label class="sd-add-label-block" for="solicitacao">Descrição do problema / solicitação</label>
						<?= $this->Form->textarea('solicitacao', [
							'id' => 'solicitacao',
							'class' => 'form-control ticket-add-textarea',
							'label' => false,
							'required' => true,
							'placeholder' => 'Descreva detalhadamente o problema, mensagem de erro, sistema afetado e impacto no trabalho…',
						]) ?>
					</div>

					<div style="margin-top:1rem">
						<label class="sd-add-label-block">Anexos</label>
						<p class="ticket-add-hint">
							Vários arquivos: <strong>Ctrl+clique</strong> (Windows) ou <strong>Cmd+clique</strong> (Mac). Também pode arrastar e soltar.
						</p>
						<div class="ticket-dropzone" id="ticket-dropzone">
							<div class="file-drop-area">
								<span class="fake-btn text-muted" id="ticket-file-hint">Arraste arquivos ou clique para anexar</span>
								<input class="file-input form-control" name="file-3[]" id="file-3" type="file" multiple>
							</div>
						</div>
						<ul class="ticket-files-chosen" id="ticket-attachments-list" aria-live="polite"></ul>
					</div>
				</section>
			</div>

			<div class="sd-add-stack">
				<section class="sd-add-card">
					<h2 class="sd-add-card-title">Resumo do chamado</h2>
					<p class="sd-add-card-desc" style="margin-top:0.25rem">Altere assunto, urgência ou fila aqui ou na etapa 2 — os campos ficam sincronizados.</p>
					<div class="sd-sum-stack">
						<?php
							$__assuntoCur = ($ticket->assunto !== null && $ticket->assunto !== '') ? $ticket->assunto : $assunto;
							$__sevCur = $ticket->severidade ?? 'media';
						?>
						<div class="sd-sum-field">
							<label class="sd-sum-field-label" for="sd-sum-assunto-select">Assunto</label>
							<select id="sd-sum-assunto-select" class="form-control sd-sum-select" title="Assunto / categoria">
								<?php foreach (C_TicketCategoriaClienteQuery as $__aid => $__alabel) : ?>
									<option value="<?= h($__aid) ?>" <?= ((string)$__aid === (string)$__assuntoCur) ? 'selected' : '' ?>><?= h($__alabel) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php if (!empty($severidadeColumnReady)) : ?>
						<div class="sd-sum-field">
							<label class="sd-sum-field-label" for="sd-sum-severidade-select">Urgência</label>
							<select id="sd-sum-severidade-select" class="form-control sd-sum-select" title="Urgência (severidade)">
								<?php
									$__sevOpts = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'urgente' => 'Urgente'];
									foreach ($__sevOpts as $__sv => $__sl) :
								?>
									<option value="<?= h($__sv) ?>" <?= ((string)$__sv === (string)$__sevCur) ? 'selected' : '' ?>><?= h($__sl) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php endif; ?>
						<?php if (!empty($ticketAddQueueFieldReady) && !empty($ticketAddQueues)) : ?>
						<div class="sd-sum-field">
							<label class="sd-sum-field-label" for="queue_id">Destino (fila)</label>
							<?= $this->Form->control('queue_id', [
								'type' => 'select',
								'options' => $ticketAddQueues,
								'label' => false,
								'id' => 'queue_id',
								'class' => 'form-control sd-sum-select',
								'value' => $ticket->queue_id ?? $ticketAddDefaultQueueId,
								'required' => false,
							]) ?>
						</div>
						<?php else : ?>
						<div class="sd-sum-field">
							<span class="sd-sum-field-label">Destino</span>
							<div class="sd-sum-select" style="display:flex;align-items:center;color:#475569;font-weight:500">
								Triagem — equipe de suporte
							</div>
						</div>
						<?php endif; ?>
					</div>
				</section>

				<section class="sd-rules-card">
					<h2 class="sd-add-card-title">Regras de priorização</h2>
					<div style="margin-top:0.75rem">
						<div class="sd-rule">
							<span class="sd-rule-pill sd-rule-pill--u">Urgente</span>
							<p>Empresa parada ou serviço essencial indisponível.</p>
						</div>
						<div class="sd-rule">
							<span class="sd-rule-pill sd-rule-pill--a">Alta</span>
							<p>Impacta o trabalho do setor.</p>
						</div>
						<div class="sd-rule">
							<span class="sd-rule-pill sd-rule-pill--m">Média</span>
							<p>Afeta o usuário, com alternativa temporária.</p>
						</div>
						<div class="sd-rule">
							<span class="sd-rule-pill sd-rule-pill--b">Baixa</span>
							<p>Solicitações sem urgência imediata.</p>
						</div>
					</div>
				</section>

				<section class="sd-actions-card">
					<h2>Ações</h2>
					<div class="sd-actions-btns">
						<?= $this->Form->button('Enviar chamado', ['id' => 'abrirticket', 'type' => 'submit', 'class' => 'sd-btn-submit btn btn-pgm btn-pgm-salvar btn-success aparecedepois']) ?>
						<a href="<?= h(Router::url(['controller' => 'Tickets', 'action' => $ticketsListAction])) ?>" class="sd-btn-ghost">Cancelar</a>
					</div>
				</section>
			</div>
		</div>

		<?= $this->Form->end() ?>
	</div>
</div>
<script>
	function sdTicketAddGetRole() {
		var r = $('.sd-add-role-holder').attr('data-sd-role');
		return parseInt(r || '1', 10);
	}

	function sdTicketAddMirrorFromMain() {
		var $a = $('#assunto');
		var $sa = $('#sd-sum-assunto-select');
		if ($a.length && $sa.length) {
			$sa.val(String($a.val() != null ? $a.val() : ''));
		}
		var $s = $('#severidade');
		var $ss = $('#sd-sum-severidade-select');
		if ($s.length && $ss.length) {
			$ss.val(String($s.val() != null ? $s.val() : ''));
		}
	}

	function sdTicketAddMirrorSidebarToMain() {
		var v = $('#sd-sum-assunto-select').val();
		var $a = $('#assunto');
		if ($a.length) {
			if (typeof $a.selectpicker === 'function') {
				$a.selectpicker('val', v);
				$a.selectpicker('refresh');
			} else {
				$a.val(v);
			}
		}
		var $s = $('#severidade');
		var $ss = $('#sd-sum-severidade-select');
		if ($s.length && $ss.length) {
			var sv = $ss.val();
			if (typeof $s.selectpicker === 'function') {
				$s.selectpicker('val', sv);
				$s.selectpicker('refresh');
			} else {
				$s.val(sv);
			}
		}
	}

	function sdTicketAddFormBasicsComplete() {
		var role = sdTicketAddGetRole();
		if (role === 0) {
			var cid = $('#idcliente').val();
			if (!cid) return false;
		}
		var assunto = $('#assunto').val();
		if (assunto === null || assunto === '' || assunto === undefined) return false;
		if ($('#severidade').length) {
			var sev = $('#severidade').val();
			if (sev === null || sev === '' || sev === undefined) return false;
		}
		var sol = ($('#solicitacao').val() || '').replace(/^\s+|\s+$/g, '');
		if (!sol) return false;
		return true;
	}

	var sdTicketAddSubmitting = false;

	function sdTicketAddRefreshAtendimentoStatus() {
		var $el = $('#sd-atendimento-status-value');
		if (!$el.length) return;
		if (sdTicketAddSubmitting) {
			$el.removeClass('is-ok is-warn is-danger').addClass('is-muted');
			$el.text('Enviando chamado…');
			return;
		}
		if (!sdTicketAddFormBasicsComplete()) {
			$el.removeClass('is-ok is-muted is-danger').addClass('is-warn');
			$el.text('Aguardando dados do chamado');
			return;
		}
		$el.removeClass('is-warn is-muted is-danger').addClass('is-ok');
		$el.text('Pronto para enviar');
	}

	$(document).ready(function () {
		sdTicketAddMirrorFromMain();
		sdTicketAddRefreshAtendimentoStatus();

		$('#sd-sum-assunto-select').on('change', function () {
			sdTicketAddMirrorSidebarToMain();
			sdTicketAddRefreshAtendimentoStatus();
		});
		$('#sd-sum-severidade-select').on('change', function () {
			sdTicketAddMirrorSidebarToMain();
			sdTicketAddRefreshAtendimentoStatus();
		});

		$('#assunto, #severidade').on('changed.bs.select change', function () {
			sdTicketAddMirrorFromMain();
			sdTicketAddRefreshAtendimentoStatus();
		});

		$('#idcliente').on('changed.bs.select change', sdTicketAddRefreshAtendimentoStatus);
		$('#solicitacao').on('input change', sdTicketAddRefreshAtendimentoStatus);
		$('#queue_id').on('change', sdTicketAddRefreshAtendimentoStatus);

		setTimeout(function () {
			sdTicketAddMirrorFromMain();
			sdTicketAddRefreshAtendimentoStatus();
		}, 300);

		$('form.ticket-add-form').on('submit', function () {
			sdTicketAddSubmitting = true;
			sdTicketAddRefreshAtendimentoStatus();
		});
	});
	// Idcliente
		<?php if(isset($idcliente)) { ?>
			$(document).ready(function() {
				var idcliente = <?= $idcliente ?>;
			});
		<?php } ?>
	// Vários anexos: lista em memória (fileStore) + DataTransfer só para enviar — evita bug de remover 1 e sumir todos
		(function () {
			var $inp = $('#file-3');
			var $list = $('#ticket-attachments-list');
			var $hint = $('#ticket-file-hint');
			var $zone = $('#ticket-dropzone');
			if (!$inp.length) return;

			var fileStore = [];

			function filesToInput() {
				var dt = new DataTransfer();
				for (var i = 0; i < fileStore.length; i++) {
					dt.items.add(fileStore[i]);
				}
				return dt.files;
			}

			function syncInput() {
				try {
					$inp[0].files = filesToInput();
				} catch (e) {}
			}

			function renderList() {
				$list.empty();
				var n = fileStore.length;
				if (n === 0) {
					$hint.text('Arraste arquivos ou clique para anexar');
					return;
				}
				$hint.text(n === 1 ? '1 arquivo selecionado — adicione mais se precisar' : n + ' arquivos — pode adicionar mais');
				for (var i = 0; i < n; i++) {
					(function (idx) {
						var name = fileStore[idx].name;
						var $li = $('<li/>');
						$li.append($('<span/>').text(name).css({overflow: 'hidden', 'text-overflow': 'ellipsis', 'white-space': 'nowrap'}));
						var $rm = $('<button type="button" class="ticket-file-remove"/>').text('Remover');
						$rm.on('click', function () {
							fileStore.splice(idx, 1);
							syncInput();
							renderList();
						});
						$li.append($rm);
						$list.append($li);
					})(i);
				}
			}

			function addFileList(fileList) {
				if (!fileList || !fileList.length) return;
				for (var i = 0; i < fileList.length; i++) {
					fileStore.push(fileList[i]);
				}
				syncInput();
				renderList();
			}

			$inp.on('change', function () {
				addFileList(this.files);
				this.value = '';
			});

			$zone.on('dragover', function (e) {
				e.preventDefault();
				e.stopPropagation();
				$zone.addClass('ticket-dropzone--drag');
			});
			$zone.on('dragleave drop', function (e) {
				e.preventDefault();
				e.stopPropagation();
				$zone.removeClass('ticket-dropzone--drag');
			});
			$zone.on('drop', function (e) {
				var ev = e.originalEvent;
				if (ev.dataTransfer && ev.dataTransfer.files && ev.dataTransfer.files.length) {
					addFileList(ev.dataTransfer.files);
				}
			});
		})();
	// Solicitantes
		function loadSolicitantes(idcliente) {
			$.ajax({
				dataType: "json",
				headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
				url: "<?= Router::url(['controller'=>'Clientes','action'=>'solicitantes']);?>/" + idcliente,
				success: function(data){
					$('#idsolicitante').find('option').remove().end();
					$('#idsolicitante').append("<option value='' selected>Indefinido</option>");
					$.each(data, function(key, array) {
						$('#idsolicitante').append($('<option>', {
							value: key,
							text: array
						}));
					})
					$('#idsolicitante').selectpicker("refresh");
				},
				error: function (tab) { $('#idsolicitante').append("<option value='' selected>Indefinido</option>"); }
			});
		}

		<?php if($role == 0){ ?>
			function loadEmail(idcliente) {
				$.ajax({
					dataType: "json",
					headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
					url: "<?= Router::url(['controller'=>'Clientes','action'=>'cliemail']);?>/" + idcliente,
					success: function(data){ $('.email').val(data.email); },
				});
			}
		<?php } ?>

		$("#idcliente").change(function() {
			var idcliente = $(this).val();
			loadSolicitantes(idcliente);
			loadEmail(idcliente);
		});

		// Só busca a lista de Solicitantes e Contadores se houver um cliente selecionado
		if ($("#idcliente").val() != '' && $("#idcliente").val() != null) {
			loadSolicitantes($("#idcliente").val());
			loadEmail($("#idcliente").val());
		}

	// Email
		<?php if(isset($email)) { ?>
			$('#email').typeAhead({ source: ['<?= $email ?>'], scope: this });
			$('#email').focus();
		<?php } ?>

	// Double submit
		jQuery.fn.preventDoubleSubmission = function() {
			$(this).on('submit',function(e){
				var $form = $(this);
				if ($form.data('submitted') === true) e.preventDefault();
				else $form.data('submitted', true);
			});

			return this;
		};

		$('form.ticket-add-form').preventDoubleSubmission();

		$('form.ticket-add-form').on('submit', function () {
			$(this).find('#abrirticket').prop('disabled', true);
		});


	// Somente número
		function SomenteNumero(e){
			var tecla=(window.event)?event.keyCode:e.which;
			if((tecla>47 && tecla<58)) return true;
			else if (tecla==8 || tecla==0) return true;
			else if (tecla == 46)  return true;
			else if( $('#valor').val().indexOf(',') > -1 && tecla == 44 ) return false
			else if( $('#valor').val().indexOf(',') <= -1 && tecla == 44 ) return true
			else  return false;
		}
	// Assunto
		$('#assunto').change(function(){
			if($(this).val() == 5) $('.data').show();
			else $('.data').hide();
		});
	//
</script>
