<?php
	use Cake\Routing\Router;
	$ticketsListAction = (isset($role) && (int)$role === 1) ? 'indexcliente' : 'index';
	$this->Breadcrumbs->add('Tickets', ['controller' => 'Tickets', 'action' => $ticketsListAction], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Abertura', [], ['class' => 'breadcrumb-item active']);
	if($role == 0) $email = null;

	// GET: urgência deve iniciar vazia (o schema da coluna tem DEFAULT 'media', que o Form ainda pode aplicar ao entity).
	$__sdAddIsPost = $this->request->is('post');
	$__sdSevFormValue = '';
	if ($__sdAddIsPost && isset($ticket->severidade) && $ticket->severidade !== null && (string)$ticket->severidade !== '') {
		$__sdSevFormValue = (string)$ticket->severidade;
	}

	$sdServicedeskAddShell = ($this->request->getParam('controller') === 'Servicedesk');
	if ($sdServicedeskAddShell) {
		$this->start('sd_topbar_actions');
		?>
	<div class="sd-topbar-add-actions">
		<button type="submit" form="sd-ticket-add-form" id="abrirticket" class="sd-topbar__btn sd-topbar__btn--enviar-chamado btn btn-pgm btn-pgm-salvar btn-success aparecedepois"><?= h('Enviar chamado') ?></button>
		<a href="<?= h(Router::url(['controller' => 'Tickets', 'action' => $ticketsListAction])) ?>" class="sd-topbar__btn sd-topbar__btn--cancelar-chamado"><?= h('Cancelar') ?></a>
	</div>
		<?php
		$this->end();
	}
?>
<style>
	.tickets-add-wrap {
		width: 100%;
		max-width: none;
		margin: 0;
		padding: 0.5rem 0 1.75rem;
		box-sizing: border-box;
		background: transparent;
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
	body.tickets-add-page .sd-topbar-add-actions {
		display: inline-flex;
		align-items: center;
		flex-wrap: wrap;
		gap: 0.5rem;
		justify-content: flex-end;
	}
	body.tickets-add-page .sd-topbar__btn--enviar-chamado {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 0.35rem;
		padding: 0.5rem 1.15rem;
		font-size: 0.875rem;
		font-weight: 700;
		letter-spacing: 0.01em;
		font-family: inherit;
		line-height: 1.2;
		color: #fff !important;
		background: linear-gradient(
			135deg,
			var(--pgm-primary, #1d9e75) 0%,
			var(--pgm-erp-teal-active, #0f6e56) 100%
		);
		border: none;
		border-radius: 999px;
		cursor: pointer;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.18),
			0 2px 12px var(--pgm-primary-muted, rgba(29, 158, 117, 0.22));
		transition: filter 0.15s ease, opacity 0.15s ease;
	}
	body.tickets-add-page .sd-topbar__btn--enviar-chamado:hover:not(:disabled):not(.disabled) {
		filter: brightness(1.06);
	}
	body.tickets-add-page .sd-topbar__btn--enviar-chamado:disabled,
	body.tickets-add-page .sd-topbar__btn--enviar-chamado.disabled {
		opacity: 0.6;
		cursor: not-allowed;
		filter: none;
	}
	body.tickets-add-page .sd-topbar__btn--enviar-chamado:focus-visible {
		outline: 2px solid rgba(0, 109, 91, 0.45);
		outline-offset: 2px;
	}
	body.tickets-add-page .sd-topbar__btn--cancelar-chamado {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		padding: 0.5rem 1.1rem;
		font-size: 0.875rem;
		font-weight: 600;
		color: var(--pgm-text-secondary, #334155) !important;
		background: var(--pgm-bg-surface, #ffffff);
		border: 1px solid var(--pgm-border, #cbd5e1);
		border-radius: 999px;
		text-decoration: none !important;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
		transition: background 0.15s, border-color 0.15s, color 0.15s;
	}
	body.tickets-add-page .sd-topbar__btn--cancelar-chamado:hover {
		background: var(--pgm-bg-overlay, #e2e8f0);
		color: var(--pgm-text, #0f172a) !important;
		border-color: var(--pgm-border-strong, #94a3b8);
		text-decoration: none !important;
	}
	body.tickets-add-page .sd-topbar__btn--cancelar-chamado:focus-visible {
		outline: 2px solid var(--pgm-border-strong, #94a3b8);
		outline-offset: 2px;
	}
	.sd-add-header {
		display: flex;
		flex-wrap: wrap;
		align-items: flex-start;
		justify-content: space-between;
		gap: 0.75rem;
		margin-bottom: 1rem;
	}
	.sd-add-kicker {
		font-size: 0.875rem;
		font-weight: 600;
		color: var(--pgm-text-muted, #64748b);
		margin: 0;
	}
	.sd-add-title {
		font-size: 1.75rem;
		font-weight: 700;
		color: var(--pgm-text, #0f172a);
		margin: 0.15rem 0 0;
		line-height: 1.2;
	}
	@media (min-width: 768px) {
		.sd-add-title { font-size: 1.875rem; }
	}
	.sd-add-sub {
		margin: 0.35rem 0 0;
		font-size: 0.875rem;
		color: var(--pgm-text-muted, #64748b);
		line-height: 1.45;
		max-width: 36rem;
	}
	.sd-add-status {
		width: 100%;
		border-radius: 0.75rem;
		padding: 0.45rem 0.75rem;
		border: 1px solid transparent;
		box-shadow: none;
		transition: background 0.15s ease, border-color 0.15s ease;
	}
	@media (min-width: 768px) {
		.sd-add-status { width: auto; max-width: 11.5rem; }
	}
	.sd-add-status-label {
		font-size: 0.6rem;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		margin: 0;
		opacity: 0.92;
	}
	.sd-add-status-value {
		margin: 0.1rem 0 0;
		font-size: 0.8125rem;
		font-weight: 600;
		line-height: 1.25;
		transition: color 0.15s ease;
	}
	.sd-add-status.is-muted {
		background: #475569;
		border-color: rgba(255, 255, 255, 0.12);
	}
	.sd-add-status.is-warn {
		background: #c2410c;
		border-color: rgba(255, 255, 255, 0.15);
	}
	.sd-add-status.is-ok {
		background: #0f766e;
		border-color: rgba(255, 255, 255, 0.15);
	}
	.sd-add-status.is-danger {
		background: #b91c1c;
		border-color: rgba(255, 255, 255, 0.15);
	}
	.sd-add-status.is-muted .sd-add-status-label,
	.sd-add-status.is-muted .sd-add-status-value,
	.sd-add-status.is-warn .sd-add-status-label,
	.sd-add-status.is-warn .sd-add-status-value,
	.sd-add-status.is-ok .sd-add-status-label,
	.sd-add-status.is-ok .sd-add-status-value,
	.sd-add-status.is-danger .sd-add-status-label,
	.sd-add-status.is-danger .sd-add-status-value {
		color: #fff !important;
	}
	.sd-sum-field {
		display: flex;
		flex-direction: column;
		gap: 0.35rem;
	}
	.sd-sum-field-label {
		font-size: 0.8125rem;
		font-weight: 600;
		color: var(--pgm-text-secondary, #334155);
	}
	.sd-sum-select {
		width: 100%;
		border-radius: 1rem !important;
		border: 1px solid var(--pgm-border, #cbd5e1) !important;
		background: var(--pgm-bg-surface, #ffffff) !important;
		padding: 0.5rem 0.75rem !important;
		font-size: 0.8125rem !important;
		color: var(--pgm-text, #0f172a);
		min-height: 2.5rem;
	}
	.sd-sum-select:focus {
		border-color: var(--pgm-primary, #1d9e75) !important;
		outline: none;
		box-shadow: 0 0 0 3px var(--pgm-focus-ring, rgba(29, 158, 117, 0.35));
	}
	.sd-add-grid {
		display: grid;
		gap: 1rem;
		align-items: start;
	}
	@media (min-width: 1024px) {
		.sd-add-grid {
			grid-template-columns: 1.15fr 0.85fr;
		}
	}
	.sd-add-stack { display: flex; flex-direction: column; gap: 1rem; }
	.sd-add-card {
		background: var(--pgm-bg-surface, #ffffff);
		border-radius: 1.5rem;
		padding: 1.15rem 1.1rem;
		border: 1px solid var(--pgm-border-subtle, #e2e8f0);
		box-shadow: var(--pgm-shadow-md, 0 4px 12px rgba(15, 23, 42, 0.08));
	}
	@media (min-width: 768px) {
		.sd-add-card { padding: 1.25rem; }
	}
	.sd-add-card-head {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 0.75rem;
		margin-bottom: 0.9rem;
	}
	.sd-add-card-title {
		font-size: 1.05rem;
		font-weight: 600;
		color: var(--pgm-text, #0f172a);
		margin: 0;
	}
	.sd-add-card-desc {
		font-size: 0.8125rem;
		color: var(--pgm-text-muted, #64748b);
		margin: 0.2rem 0 0;
		line-height: 1.4;
	}
	.sd-add-badge {
		flex-shrink: 0;
		border-radius: 9999px;
		background: var(--pgm-bg-elevated, #f1f5f9);
		border: 1px solid var(--pgm-border-subtle, #e2e8f0);
		padding: 0.25rem 0.65rem;
		font-size: 0.7rem;
		font-weight: 600;
		color: var(--pgm-text-secondary, #334155);
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
		color: var(--pgm-text-secondary, #334155);
		font-size: 0.8125rem;
	}
	.sd-add-field .form-control,
	.sd-add-field .bootstrap-select > .dropdown-toggle {
		border-radius: 1rem !important;
		border: 1px solid var(--pgm-border, #cbd5e1) !important;
		background-color: var(--pgm-bg-surface, #ffffff) !important;
		color: var(--pgm-text, #0f172a) !important;
		min-height: 2.75rem;
		padding-top: 0.5rem;
		padding-bottom: 0.5rem;
		font-size: 0.875rem;
		box-shadow: none !important;
		background-image: none !important;
	}
	/* form-material / .btn do bootstrap-select podem zerar borda — mesma caixa do e-mail */
	.tickets-add-wrap form.form-material .sd-add-field input.form-control:not([type="hidden"]):not(.ticket-add-readonly-name),
	.tickets-add-wrap form.form-material .sd-add-field select.form-control,
	.tickets-add-wrap form.form-material .sd-add-field textarea.form-control {
		border: 1px solid var(--pgm-border, #cbd5e1) !important;
		border-radius: 1rem !important;
		background-color: var(--pgm-bg-surface, #ffffff) !important;
		color: var(--pgm-text, #0f172a) !important;
		box-shadow: none !important;
	}
	.tickets-add-wrap form.form-material .sd-add-field input.form-control:not([type="hidden"]):not(.ticket-add-readonly-name):focus,
	.tickets-add-wrap form.form-material .sd-add-field select.form-control:focus,
	.tickets-add-wrap form.form-material .sd-add-field textarea.form-control:focus {
		border-color: var(--pgm-primary, #1d9e75) !important;
		box-shadow: 0 0 0 3px var(--pgm-focus-ring, rgba(29, 158, 117, 0.25)) !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select > .dropdown-toggle.btn,
	.tickets-add-wrap .sd-add-field .bootstrap-select > .dropdown-toggle.btn-default,
	.tickets-add-wrap .sd-add-field .bootstrap-select > .dropdown-toggle.btn-light {
		border: 1px solid var(--pgm-border, #cbd5e1) !important;
		background-color: var(--pgm-bg-surface, #ffffff) !important;
		background-image: none !important;
		text-shadow: none !important;
		box-shadow: none !important;
	}
	.sd-add-field .bootstrap-select { width: 100% !important; }
	/* bootstrap-select: wrapper não herda “caixa” clara do .form-control */
	.tickets-add-wrap .sd-add-field .bootstrap-select.form-control {
		background: transparent !important;
		border: none !important;
		box-shadow: none !important;
		padding: 0 !important;
		min-height: 0 !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select > .dropdown-toggle .filter-option-inner-inner {
		color: var(--pgm-text, #0f172a) !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select > .dropdown-toggle.bs-placeholder .filter-option-inner-inner {
		color: var(--pgm-text-muted, #64748b) !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select > .dropdown-toggle:focus,
	.tickets-add-wrap .sd-add-field .bootstrap-select.show > .dropdown-toggle,
	.tickets-add-wrap .sd-add-field .bootstrap-select.open > .dropdown-toggle {
		border: 1px solid var(--pgm-primary, #1d9e75) !important;
		box-shadow: 0 0 0 3px var(--pgm-focus-ring, rgba(29, 158, 117, 0.35)) !important;
		outline: none !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select > .dropdown-toggle .caret {
		border-top-color: var(--pgm-text-muted, #64748b) !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select .dropdown-menu {
		background: var(--pgm-bg-surface, #ffffff) !important;
		border: 1px solid var(--pgm-border-subtle, #e2e8f0) !important;
		box-shadow: var(--pgm-shadow-lg, 0 8px 32px rgba(15, 23, 42, 0.1)) !important;
		border-radius: 0.75rem !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select .dropdown-menu li a {
		color: var(--pgm-text, #0f172a) !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select .dropdown-menu li.active a,
	.tickets-add-wrap .sd-add-field .bootstrap-select .dropdown-menu li.selected a,
	.tickets-add-wrap .sd-add-field .bootstrap-select .dropdown-menu li a:hover,
	.tickets-add-wrap .sd-add-field .bootstrap-select .dropdown-menu li a:focus {
		background: var(--pgm-primary-muted, rgba(29, 158, 117, 0.1)) !important;
		color: var(--pgm-primary-hover, #0f6e56) !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select .dropdown-menu li.disabled a,
	.tickets-add-wrap .sd-add-field .bootstrap-select .dropdown-menu li.disabled a:hover {
		color: #6b7280 !important;
		background: transparent !important;
		opacity: 0.65;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select .dropdown-menu .notify {
		background: var(--pgm-bg-elevated, #f1f5f9) !important;
		border-color: var(--pgm-border-subtle, #e2e8f0) !important;
		color: var(--pgm-text-muted, #64748b) !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select .no-results {
		background: var(--pgm-bg-elevated, #f1f5f9) !important;
		color: var(--pgm-text-muted, #64748b) !important;
	}
	.tickets-add-wrap .sd-add-field .bootstrap-select .bs-searchbox .form-control,
	.tickets-add-wrap .sd-add-field .bootstrap-select .bs-actionsbox .form-control {
		background: var(--pgm-bg-surface, #ffffff) !important;
		border: 1px solid var(--pgm-border, #cbd5e1) !important;
		color: var(--pgm-text, #0f172a) !important;
		border-radius: 0.5rem !important;
	}
	/* Menu anexado ao body: wrapper .bs-container não desenha caixa (evita “duas molduras” com .dropdown-menu) */
	body.tickets-add-page .bootstrap-select.bs-container {
		background: transparent !important;
		border: none !important;
		margin: 0 !important;
		padding: 0 !important;
		outline: none !important;
		box-shadow: none !important;
		-webkit-box-shadow: none !important;
	}
	/* Menu anexado ao body (.bs-container) — mesmo tema */
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu {
		background: var(--pgm-bg-surface, #ffffff) !important;
		border: 1px solid var(--pgm-border-subtle, #e2e8f0) !important;
		box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12) !important;
		border-radius: 0.75rem !important;
		margin: 0 !important;
		-webkit-box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12) !important;
		/* BS aplica padding em .dropdown-menu; o <ul> interno TAMBÉM é .dropdown-menu → sobra e cantos partidos */
		padding: 0 !important;
		overflow: hidden !important;
	}
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu > .inner {
		border: 0 !important;
		box-shadow: none !important;
		-webkit-box-shadow: none !important;
		margin: 0 !important;
		padding: 0 !important;
	}
	/* ul.dropdown-menu.inner herdava padding (ex.: 0.5rem 0) = faixa vazia topo/base */
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu ul.dropdown-menu,
	body.tickets-add-page .bootstrap-select.bs-container ul.dropdown-menu.inner {
		padding: 0 !important;
		margin: 0 !important;
		border: none !important;
		border-radius: 0 !important;
		box-shadow: none !important;
		min-width: 100% !important;
	}
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu li a {
		color: var(--pgm-text, #0f172a) !important;
	}
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu li.active a,
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu li.selected a,
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu li a:hover,
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu li a:focus {
		background: var(--pgm-primary-muted, rgba(29, 158, 117, 0.1)) !important;
		color: var(--pgm-primary-hover, #0f6e56) !important;
	}
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu li.disabled a,
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu li.disabled a:hover {
		color: #6b7280 !important;
		background: transparent !important;
		opacity: 0.65;
	}
	body.tickets-add-page .bootstrap-select.bs-container .dropdown-menu .notify {
		background: var(--pgm-bg-elevated, #f1f5f9) !important;
		border-color: var(--pgm-border-subtle, #e2e8f0) !important;
		color: var(--pgm-text-muted, #64748b) !important;
	}
	body.tickets-add-page .bootstrap-select.bs-container .no-results {
		background: var(--pgm-bg-elevated, #f1f5f9) !important;
		color: var(--pgm-text-muted, #64748b) !important;
	}
	body.tickets-add-page .bootstrap-select.bs-container .bs-searchbox .form-control,
	body.tickets-add-page .bootstrap-select.bs-container .bs-actionsbox .form-control {
		background: var(--pgm-bg-surface, #ffffff) !important;
		border: 1px solid var(--pgm-border, #cbd5e1) !important;
		color: var(--pgm-text, #0f172a) !important;
		border-radius: 0.5rem !important;
	}
	.ticket-add-textarea.form-control {
		min-height: 9rem;
		border: 1px solid var(--pgm-border, #cbd5e1);
		border-radius: 1rem;
		padding: 0.85rem 1rem;
		font-size: 0.875rem;
		line-height: 1.55;
		resize: vertical;
		background: var(--pgm-bg-surface, #ffffff);
		color: var(--pgm-text, #0f172a);
		transition: border-color 0.15s ease, box-shadow 0.15s ease;
	}
	.ticket-add-textarea.form-control:focus {
		border-color: var(--pgm-primary, #1d9e75);
		box-shadow: 0 0 0 3px var(--pgm-focus-ring, rgba(29, 158, 117, 0.35));
		outline: 0;
	}
	.sd-add-label-block {
		display: block;
		margin-bottom: 0.4rem;
		font-weight: 600;
		color: var(--pgm-text-secondary, #334155);
		font-size: 0.8125rem;
	}
	.ticket-add-hint {
		font-size: 0.75rem;
		color: var(--pgm-text-muted, #64748b);
		margin: 0 0 0.5rem;
		line-height: 1.45;
	}
	.ticket-dropzone {
		border: 2px dashed var(--pgm-border-strong, #94a3b8);
		border-radius: 1rem;
		background: var(--pgm-bg-elevated, #f1f5f9);
		min-height: 5.5rem;
		padding: 1rem 1.15rem;
		display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;
		transition: border-color 0.2s, background 0.2s;
	}
	.ticket-dropzone.ticket-dropzone--drag {
		border-color: var(--pgm-primary, #1d9e75);
		background: var(--pgm-primary-muted, rgba(29, 158, 117, 0.14));
	}
	.ticket-dropzone:hover {
		border-color: var(--pgm-primary, #1d9e75);
		background: var(--pgm-bg-overlay, #e2e8f0);
	}
	.ticket-files-chosen {
		list-style: none;
		margin: 0.65rem 0 0;
		padding: 0;
		font-size: 0.8125rem;
		color: var(--pgm-text-secondary, #334155);
	}
	.ticket-files-chosen li > span.ticket-file-name {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		min-width: 0;
		flex: 1 1 auto;
	}
	.ticket-files-chosen li {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		padding: 6px 10px;
		margin-bottom: 4px;
		background: var(--pgm-bg-elevated, #f1f5f9);
		border-radius: 0.65rem;
		border: 1px solid var(--pgm-border-subtle, #e2e8f0);
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
		color: var(--pgm-text-muted, #64748b);
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
		border: 1px solid var(--pgm-border, #cbd5e1) !important;
		background-color: var(--pgm-bg-surface, #ffffff) !important;
		color: var(--pgm-text, #0f172a) !important;
		-webkit-box-shadow: 0 0 0 1000px var(--pgm-bg-surface, #ffffff) inset !important;
		box-shadow: 0 0 0 1000px var(--pgm-bg-surface, #ffffff) inset !important;
	}
	.ticket-add-email.form-control:-webkit-autofill,
	.ticket-add-email.form-control:-webkit-autofill:hover,
	.ticket-add-email.form-control:-webkit-autofill:focus {
		-webkit-text-fill-color: var(--pgm-text, #0f172a) !important;
		-webkit-box-shadow: 0 0 0 1000px var(--pgm-bg-elevated, #f1f5f9) inset !important;
		box-shadow: 0 0 0 1000px var(--pgm-bg-elevated, #f1f5f9) inset !important;
		border-color: var(--pgm-border, #cbd5e1) !important;
		transition: background-color 9999s ease-out 0s;
	}
	.ticket-add-lead {
		font-size: 0.875rem;
		color: var(--pgm-text-secondary, #334155);
		margin: 0 0 1rem;
		line-height: 1.5;
		padding: 0.75rem 1rem;
		background: var(--pgm-bg-elevated, #f1f5f9);
		border-radius: 1rem;
		border: 1px solid var(--pgm-border-subtle, #e2e8f0);
	}
	.ticket-add-lead strong {
		color: var(--pgm-text, #0f172a);
	}
	.sd-sum-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 0.5rem;
		border-radius: 1rem;
		background: var(--pgm-bg-elevated, #f1f5f9);
		border: 1px solid var(--pgm-border-subtle, #e2e8f0);
		padding: 0.65rem 0.85rem;
		font-size: 0.8125rem;
	}
	.sd-sum-item span:first-child { color: var(--pgm-text-muted, #64748b); }
	.sd-sum-item span:last-child {
		font-weight: 600;
		color: var(--pgm-text, #0f172a);
		text-align: right;
		max-width: 58%;
		word-break: break-word;
	}
	.sd-sum-stack { display: flex; flex-direction: column; gap: 0.65rem; margin-top: 1rem; }
	.sd-rules-card {
		background: var(--pgm-badge-amber-bg, rgba(217, 119, 6, 0.08));
		border: 1px solid var(--pgm-badge-amber-ring, rgba(217, 119, 6, 0.22));
		border-radius: 1.5rem;
		padding: 1.15rem 1.1rem;
		box-shadow: var(--pgm-shadow-sm, 0 1px 3px rgba(15, 23, 42, 0.06));
	}
	@media (min-width: 768px) {
		.sd-rules-card { padding: 1.25rem; }
	}
	.sd-rule {
		background: var(--pgm-bg-surface, #ffffff);
		border: 1px solid var(--pgm-border-subtle, #e2e8f0);
		border-radius: 1rem;
		padding: 0.85rem 1rem;
		margin-bottom: 0.5rem;
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
	.sd-rule-pill--b {
		background: var(--pgm-badge-muted-bg, rgba(15, 23, 42, 0.06));
		color: var(--pgm-badge-muted-text, #475569);
		box-shadow: inset 0 0 0 1px var(--pgm-badge-muted-ring, rgba(15, 23, 42, 0.1));
	}
	.sd-rule p {
		margin: 0.4rem 0 0;
		font-size: 0.8125rem;
		color: var(--pgm-text-secondary, #334155);
		line-height: 1.4;
	}
	.sd-actions-card {
		background: var(--pgm-bg-surface, #ffffff);
		color: var(--pgm-text, #0f172a);
		border: 1px solid var(--pgm-border-subtle, #e2e8f0);
		border-radius: 1.5rem;
		padding: 1.35rem 1.25rem;
		box-shadow: var(--pgm-shadow-md, 0 4px 12px rgba(15, 23, 42, 0.08));
	}
	@media (min-width: 768px) {
		.sd-actions-card { padding: 1.5rem; }
	}
	.sd-actions-card h2 { color: var(--pgm-text, #0f172a); font-size: 1.05rem; font-weight: 600; margin: 0; }
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
		transition: filter 0.15s ease, opacity 0.15s ease;
		background: linear-gradient(
			135deg,
			var(--pgm-primary, #1d9e75) 0%,
			var(--pgm-erp-teal-active, #0f6e56) 100%
		) !important;
		color: #fff !important;
		box-shadow: 0 2px 12px var(--pgm-primary-muted, rgba(29, 158, 117, 0.22));
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
		border: 1px solid var(--pgm-border, #cbd5e1);
		background: transparent;
		color: var(--pgm-text-secondary, #334155);
		padding: 0.65rem 1rem;
		font-size: 0.875rem;
		font-weight: 600;
		text-decoration: none;
		transition: background 0.15s, border-color 0.15s;
	}
	.sd-btn-ghost:hover {
		background: var(--pgm-bg-overlay, #e2e8f0);
		color: var(--pgm-text, #0f172a);
		border-color: var(--pgm-border-strong, #94a3b8);
		text-decoration: none;
	}
	select[multiple] {
		max-width: 100%;
	}
	.ticket-add-lead--flush {
		margin-top: 0;
	}
	.ticket-add-readonly-name {
		background: var(--pgm-bg-elevated, #f1f5f9);
		color: var(--pgm-text-secondary, #334155);
		border: 1px solid var(--pgm-border-subtle, #e2e8f0) !important;
		cursor: default;
	}
	.sd-add-mt-075 {
		margin-top: 0.75rem;
	}
	.sd-add-mt-1 {
		margin-top: 1rem;
	}
	.sd-add-card-desc--tight {
		margin-top: 0.25rem;
	}
	.sd-sum-select--static {
		display: flex;
		align-items: center;
		color: var(--pgm-text-muted, #64748b);
		font-weight: 500;
	}
</style>
<div class="col-md-12 tickets-add-wrap" data-sd-add-is-post="<?= $__sdAddIsPost ? '1' : '0' ?>">
	<div class="sd-add-page">
		<header class="sd-add-header">
			<div>
				<p class="sd-add-kicker">Central de Atendimento</p>
				<h1 class="sd-add-title">Abertura de Chamado</h1>
				<p class="sd-add-sub">
					Preencha os dados abaixo para registrar um incidente, requisição ou solicitação de acesso.
				</p>
			</div>
			<div class="sd-add-status is-muted" id="sd-add-status-card">
				<p class="sd-add-status-label">Status</p>
				<p class="sd-add-status-value is-muted" id="sd-atendimento-status-value">Carregando…</p>
			</div>
		</header>

		<?= $this->Form->create($ticket, ['id' => 'sd-ticket-add-form', 'enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material ticket-add-form']) ?>
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
						<p class="ticket-add-lead ticket-add-lead--flush">
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
								<?= $this->Form->control('idsolicitante', ['class' => 'selectpicker form-control', 'title' => 'Solicitante (opcional)', 'data-live-search' => true, 'options' => [], 'label' => false, 'required' => false]) ?>
							</div>
							<div class="sd-add-field">
								<label class="control-label text-muted">Nome do solicitante</label>
								<?= $this->Form->control('nomesolicitante', ['class' => 'form-control', 'title' => 'Nome do solicitante', 'label' => false, 'required' => false, 'placeholder' => 'Se não estiver cadastrado']) ?>
							</div>
							<div class="sd-add-field sd-email-main">
								<label class="control-label text-muted" for="email">E-mail para contato</label>
								<?= $this->Form->email('email', ['value' => $email, 'type' => 'text', 'id' => 'email', 'class' => 'email form-control ticket-add-email', 'label' => false, 'required' => true, 'placeholder' => 'usuario@empresa.com']) ?>
							</div>
						<?php } else { ?>
							<div class="sd-add-field">
								<label class="control-label text-muted">Nome</label>
								<input type="text" class="form-control ticket-add-readonly-name" readonly value="<?= h($authUserName) ?>" tabindex="-1" aria-label="Nome do solicitante">
							</div>
							<div class="sd-add-field">
								<label class="control-label text-muted" for="email">E-mail</label>
								<?= $this->Form->email('email', ['value' => $email, 'type' => 'text', 'id' => 'email', 'class' => 'email form-control ticket-add-email', 'label' => false, 'required' => true, 'placeholder' => 'usuario@empresa.com']) ?>
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
								<?= $this->Form->control('assunto', ['class' => 'selectpicker form-control', 'title' => 'Escolha um assunto', 'data-live-search' => true, 'options' => $ticketAssuntoOptions, 'label' => false, 'required' => true, 'empty' => 'Escolha o assunto']) ?>
							</div>
							<?php if (!empty($severidadeColumnReady)) : ?>
							<div class="sd-add-field">
								<label class="control-label text-muted">Urgência (severidade)</label>
								<?= $this->Form->control('severidade', [
									'type' => 'select',
									'options' => ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'urgente' => 'Urgente'],
									'class' => 'selectpicker form-control',
									'title' => 'Grau de severidade',
									'empty' => 'Escolha a urgência',
									'value' => $__sdSevFormValue,
									'label' => false,
									'required' => true,
								]) ?>
							</div>
							<?php endif; ?>
						<?php } else { ?>
							<div class="sd-add-field">
								<label class="control-label text-muted">Assunto / Categoria</label>
								<?= $this->Form->control('assunto', ['class' => 'selectpicker form-control', 'title' => 'Escolha um assunto', 'data-live-search' => true, 'options' => $ticketAssuntoOptions, 'label' => false, 'required' => true, 'empty' => 'Escolha o assunto']) ?>
							</div>
							<?php if (!empty($severidadeColumnReady)) : ?>
							<div class="sd-add-field">
								<label class="control-label text-muted">Urgência (severidade)</label>
								<?= $this->Form->control('severidade', [
									'type' => 'select',
									'options' => ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'urgente' => 'Urgente'],
									'class' => 'selectpicker form-control',
									'title' => 'Grau de severidade',
									'empty' => 'Escolha a urgência',
									'value' => $__sdSevFormValue,
									'label' => false,
									'required' => true,
								]) ?>
							</div>
							<?php endif; ?>
						<?php } ?>
					</div>

					<div class="row hide data sd-add-mt-075">
						<div class="col-md-6 col-xs-12 sd-add-field">
							<label class="control-label text-muted">Data da Visita</label>
							<?= $this->Form->text('data', ['class' => 'form-control datepicker', 'label' => false]) ?>
						</div>
					</div>

					<div class="sd-add-mt-1">
						<label class="sd-add-label-block" for="solicitacao">Descrição do problema / solicitação</label>
						<?= $this->Form->textarea('solicitacao', [
							'id' => 'solicitacao',
							'class' => 'form-control ticket-add-textarea',
							'label' => false,
							'required' => true,
							'placeholder' => 'Descreva detalhadamente o problema, mensagem de erro, sistema afetado e impacto no trabalho…',
						]) ?>
					</div>

					<div class="sd-add-mt-1">
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
					<p class="sd-add-card-desc sd-add-card-desc--tight">Altere assunto, urgência ou fila aqui ou na etapa 2 — os campos ficam sincronizados.</p>
					<div class="sd-sum-stack">
						<?php
							$__assuntoCur = (isset($ticket->assunto) && $ticket->assunto !== null && (string)$ticket->assunto !== '') ? (string)$ticket->assunto : '';
							$__sevCur = $__sdSevFormValue;
						?>
						<div class="sd-add-field sd-sum-field">
							<label class="control-label text-muted" for="sd-sum-assunto-select">Assunto</label>
							<select id="sd-sum-assunto-select" class="selectpicker form-control sd-sum-select" data-live-search="true" title="Assunto / categoria">
								<option value=""><?= h('Escolha o assunto') ?></option>
								<?php foreach ($ticketAssuntoOptions as $__aid => $__alabel) : ?>
									<option value="<?= h($__aid) ?>" <?= ($__assuntoCur !== '' && (string)$__aid === (string)$__assuntoCur) ? 'selected' : '' ?>><?= h($__alabel) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php if (!empty($severidadeColumnReady)) : ?>
						<div class="sd-add-field sd-sum-field">
							<label class="control-label text-muted" for="sd-sum-severidade-select">Urgência</label>
							<select id="sd-sum-severidade-select" class="selectpicker form-control sd-sum-select" title="Urgência (severidade)">
								<option value=""><?= h('Escolha a urgência') ?></option>
								<?php
									$__sevOpts = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'urgente' => 'Urgente'];
									foreach ($__sevOpts as $__sv => $__sl) :
								?>
									<option value="<?= h($__sv) ?>" <?= ($__sevCur !== '' && (string)$__sv === (string)$__sevCur) ? 'selected' : '' ?>><?= h($__sl) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php endif; ?>
						<?php if (!empty($ticketAddQueueFieldReady) && !empty($ticketAddQueues)) : ?>
						<div class="sd-add-field sd-sum-field">
							<label class="control-label text-muted" for="queue_id">Destino (fila)</label>
							<?= $this->Form->control('queue_id', [
								'type' => 'select',
								'options' => $ticketAddQueues,
								'label' => false,
								'id' => 'queue_id',
								'class' => 'selectpicker form-control sd-sum-select',
								'empty' => 'Escolha a fila',
								'required' => true,
							]) ?>
						</div>
						<?php else : ?>
						<div class="sd-add-field sd-sum-field">
							<span class="control-label text-muted">Destino</span>
							<div class="sd-sum-select sd-sum-select--static">
								Triagem — equipe de suporte
							</div>
						</div>
						<?php endif; ?>
					</div>
				</section>

				<section class="sd-rules-card">
					<h2 class="sd-add-card-title">Regras de priorização</h2>
					<div class="sd-add-mt-075">
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

				<?php if (!$sdServicedeskAddShell) : ?>
				<section class="sd-actions-card">
					<h2>Ações</h2>
					<div class="sd-actions-btns">
						<?= $this->Form->button('Enviar chamado', ['id' => 'abrirticket', 'type' => 'submit', 'class' => 'sd-btn-submit btn btn-pgm btn-pgm-salvar btn-success aparecedepois']) ?>
						<a href="<?= h(Router::url(['controller' => 'Tickets', 'action' => $ticketsListAction])) ?>" class="sd-btn-ghost">Cancelar</a>
					</div>
				</section>
				<?php endif; ?>

			</div>
		</div>

		<?= $this->Form->end() ?>
	</div>
</div>
<script>
	// #region agent log
	(function () {
		if (window.__sdDbgH1Err) { return; } window.__sdDbgH1Err = true;
		window.addEventListener('error', function (e) {
			try {
				fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '62ce4c' }, body: JSON.stringify({ sessionId: '62ce4c', hypothesisId: 'H1', location: 'add.ctp:window.onerror', message: String(e.message || 'error'), data: { file: String(e.filename || ''), line: e.lineno || 0, col: e.colno || 0 }, timestamp: Date.now() }) }).catch(function () {});
			} catch (ex) {}
		});
	}());
	// #endregion
	function sdTicketAddGetRole() {
		var r = $('.sd-add-role-holder').attr('data-sd-role');
		return parseInt(r || '1', 10);
	}

	function sdTicketAddBsSetVal($sel, v) {
		if (!$sel || !$sel.length) return;
		var s = (v != null && v !== undefined) ? String(v) : '';
		if (String($sel.val() != null ? $sel.val() : '') === s) {
			return;
		}
		if (typeof $ !== 'undefined' && $.fn && $.fn.selectpicker && $sel.data('selectpicker')) {
			$sel.selectpicker('val', s);
		} else {
			$sel.val(s);
		}
	}

	function sdTicketAddMirrorFromMain() {
		var $a = $('#assunto');
		var $sa = $('#sd-sum-assunto-select');
		if ($a.length && $sa.length) {
			sdTicketAddBsSetVal($sa, $a.val());
		}
		var $s = $('#severidade');
		var $ss = $('#sd-sum-severidade-select');
		if ($s.length && $ss.length) {
			sdTicketAddBsSetVal($ss, $s.val());
		}
	}

	function sdTicketAddMirrorSidebarToMain() {
		var v = $('#sd-sum-assunto-select').val();
		var $a = $('#assunto');
		if ($a.length) {
			sdTicketAddBsSetVal($a, v != null ? v : '');
		}
		var $s = $('#severidade');
		var $ss = $('#sd-sum-severidade-select');
		if ($s.length && $ss.length) {
			sdTicketAddBsSetVal($s, $ss.val());
		}
	}

	/** GET: reforço contra DEFAULT do schema / bootstrap-select mostrando "Média". */
	function sdTicketAddForceBlankSeveridadeIfNewForm() {
		var $wrap = $('.tickets-add-wrap');
		if (!$wrap.length || $wrap.attr('data-sd-add-is-post') === '1') {
			return;
		}
		var $s = $('#severidade');
		var $ss = $('#sd-sum-severidade-select');
		if ($s.length) {
			sdTicketAddBsSetVal($s, '');
		}
		if ($ss.length) {
			sdTicketAddBsSetVal($ss, '');
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
		if ($('#queue_id').length) {
			var qv = $('#queue_id').val();
			if (qv === null || qv === '' || qv === undefined || String(qv) === '0') return false;
		}
		var sol = ($('#solicitacao').val() || '').replace(/^\s+|\s+$/g, '');
		if (!sol) return false;
		return true;
	}

	var sdTicketAddSubmitting = false;

	function sdTicketAddRefreshAtendimentoStatus() {
		var $el = $('#sd-atendimento-status-value');
		var $card = $('#sd-add-status-card');
		if (!$el.length) return;
		function syncCard(state) {
			if (!$card.length) return;
			$card.removeClass('is-ok is-warn is-muted is-danger').addClass(state);
		}
		if (sdTicketAddSubmitting) {
			$el.removeClass('is-ok is-warn is-danger').addClass('is-muted');
			syncCard('is-muted');
			$el.text('Enviando chamado…');
			return;
		}
		if (!sdTicketAddFormBasicsComplete()) {
			$el.removeClass('is-ok is-muted is-danger').addClass('is-warn');
			syncCard('is-warn');
			$el.text('Aguardando dados do chamado');
			return;
		}
		$el.removeClass('is-warn is-muted is-danger').addClass('is-ok');
		syncCard('is-ok');
		$el.text('Pronto para enviar');
	}

	$(document).ready(function () {
		// #region agent log
		try {
			fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '62ce4c' }, body: JSON.stringify({ sessionId: '62ce4c', hypothesisId: 'H1', location: 'add.ctp:ready:init', message: 'libs', data: { jquery: typeof jQuery, selectpicker: !!(typeof $ !== 'undefined' && $ && $.fn && $.fn.selectpicker), role: (function () { var r = $('.sd-add-role-holder').attr('data-sd-role'); return parseInt(r || '1', 10); })() }, timestamp: Date.now() }) }).catch(function () {});
		} catch (ex) {}
		// #endregion
		if (typeof $ !== 'undefined' && $.fn && $.fn.selectpicker) {
			$('.tickets-add-wrap select.selectpicker').each(function () {
				var $el = $(this);
				if ($el.data('selectpicker')) return;
				$el.selectpicker({
					container: 'body',
					style: '',
					size: 8,
					width: '100%'
				});
			});
			setTimeout(function () {
				$('.tickets-add-wrap select.selectpicker').selectpicker('refresh');
			}, 0);
		}
		sdTicketAddMirrorFromMain();
		sdTicketAddForceBlankSeveridadeIfNewForm();
		sdTicketAddRefreshAtendimentoStatus();

		var sdOnPickerOrChange = 'change changed.bs.select';

		$('#sd-sum-assunto-select, #sd-sum-severidade-select').on(sdOnPickerOrChange, function () {
			sdTicketAddMirrorSidebarToMain();
			sdTicketAddRefreshAtendimentoStatus();
		});
		$('#assunto, #severidade').on(sdOnPickerOrChange, function () {
			sdTicketAddMirrorFromMain();
			sdTicketAddRefreshAtendimentoStatus();
		});

		$('#idcliente, #idsolicitante, #queue_id').on(sdOnPickerOrChange, sdTicketAddRefreshAtendimentoStatus);
		$('#solicitacao').on('input change', sdTicketAddRefreshAtendimentoStatus);

		setTimeout(function () {
			sdTicketAddMirrorFromMain();
			sdTicketAddForceBlankSeveridadeIfNewForm();
			sdTicketAddRefreshAtendimentoStatus();
		}, 300);

		$('form.ticket-add-form').on('submit', function () {
			sdTicketAddMirrorSidebarToMain();
			sdTicketAddSubmitting = true;
			sdTicketAddRefreshAtendimentoStatus();
		});
		// #region agent log
		$(document).on('shown.bs.select', '.tickets-add-wrap select.selectpicker', function () {
			var $m = $(this).closest('.bootstrap-select').find('.dropdown-menu').first();
			var sd = document.querySelector('.sd-add-page');
			var bsc = document.querySelector('.sd-add-page .bootstrap-select.bs-container');
			try {
				fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '62ce4c' }, body: JSON.stringify({ sessionId: '62ce4c', hypothesisId: 'H2', location: 'add.ctp:shown.bs.select', message: 'dropdown open', data: { id: this.id || '', nLi: $m.length ? $m.find('li').length : -1, overflowParent: sd ? window.getComputedStyle(sd).overflow : 'none', hasBsContainer: !!bsc, zMenu: $m.length ? $m.css('z-index') : 'na' }, timestamp: Date.now() }) }).catch(function () {});
			} catch (ex) {}
		});
		setTimeout(function () {
			var c = document.getElementById('idcliente');
			var a = document.getElementById('assunto');
			var q = document.getElementById('queue_id');
			var nC = c ? c.querySelectorAll('option').length : -1;
			var nA = a ? a.querySelectorAll('option').length : -1;
			var nQ = q ? q.querySelectorAll('option').length : -1;
			var sdp = document.querySelector('.sd-add-page');
			try {
				fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '62ce4c' }, body: JSON.stringify({ sessionId: '62ce4c', hypothesisId: 'H4', location: 'add.ctp:domCounts', message: 'native select options', data: { nIdclienteOpts: nC, nAssuntoOpts: nA, nQueueOpts: nQ, sdAddPage: !!sdp }, timestamp: Date.now() }) }).catch(function () {});
			} catch (ex) {}
		}, 200);
		// #endregion
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
						$li.append($('<span/>').addClass('ticket-file-name').text(name));
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
					// #region agent log
					try {
						var nk = (data && typeof data === 'object' && !Array.isArray(data)) ? Object.keys(data).length : -1;
						fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '62ce4c' }, body: JSON.stringify({ sessionId: '62ce4c', hypothesisId: 'H5', location: 'add.ctp:loadSolicitantes:success', message: 'ajax solicitantes', data: { idcliente: idcliente, nKeys: nk }, timestamp: Date.now() }) }).catch(function () {});
					} catch (ex) {}
					// #endregion
					$('#idsolicitante').find('option').remove().end();
					$('#idsolicitante').append("<option value='' selected>Indefinido</option>");
					$.each(data, function(key, array) {
						$('#idsolicitante').append($('<option>', {
							value: key,
							text: array
						}));
					})
					if ($.fn.selectpicker && $('#idsolicitante').hasClass('selectpicker')) {
						$('#idsolicitante').selectpicker('refresh');
					}
				},
				error: function (xhr) {
					// #region agent log
					try {
						fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '62ce4c' }, body: JSON.stringify({ sessionId: '62ce4c', hypothesisId: 'H5', location: 'add.ctp:loadSolicitantes:error', message: 'ajax failed', data: { idcliente: idcliente, status: xhr && xhr.status, len: (xhr && xhr.responseText) ? String(xhr.responseText).length : 0 }, timestamp: Date.now() }) }).catch(function () {});
					} catch (ex) {}
					// #endregion
					$('#idsolicitante').append("<option value='' selected>Indefinido</option>");
				}
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
			if (typeof loadEmail === 'function') {
				loadEmail(idcliente);
			}
		});

		// Só busca a lista de Solicitantes e Contadores se houver um cliente selecionado
		if ($("#idcliente").val() != '' && $("#idcliente").val() != null) {
			loadSolicitantes($("#idcliente").val());
			if (typeof loadEmail === 'function') {
				loadEmail($("#idcliente").val());
			}
		}

	// Email
		<?php if(isset($email)) { ?>
			if (typeof $ !== 'undefined' && $.fn && typeof $.fn.typeAhead === 'function') {
				$('#email').typeAhead({ source: <?= json_encode([$email], JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>, scope: this });
			}
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
			$('#abrirticket').prop('disabled', true);
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
	// Assunto (nativo ou bootstrap-select)
		$('#assunto').on('change changed.bs.select', function(){
			if($(this).val() == 5) $('.data').show();
			else $('.data').hide();
		});
	//
</script>
