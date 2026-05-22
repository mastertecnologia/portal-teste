/**
 * Wizard 4 passos — cadastro/editar cliente (aba Cliente e tela add).
 */
(function () {
	'use strict';
	function initWizard(root) {
		if (!root || root.getAttribute('data-cli-wizard-inited') === '1') {
			return;
		}
		root.setAttribute('data-cli-wizard-inited', '1');
		var panes = root.querySelectorAll('.cli-wizard-pane[data-cli-wizard-step]');
		if (!panes.length) {
			return;
		}
		var maxStep = panes.length;
		var step = 1;
		var stored = parseInt(root.getAttribute('data-cli-wizard-initial'), 10);
		if (!isNaN(stored) && stored >= 1 && stored <= maxStep) {
			step = stored;
		}
		var prevBtn = root.querySelector('[data-cli-wizard-prev]');
		var nextBtn = root.querySelector('[data-cli-wizard-next]');
		var saveBtn = root.querySelector('[data-cli-wizard-save]');
		var labelEl = root.querySelector('[data-cli-wizard-label]');
		var stepper = root.querySelector('.cli-wizard-stepper');

		function syncStepper() {
			if (!stepper) {
				return;
			}
			var buttons = stepper.querySelectorAll('[data-cli-wizard-goto]');
			for (var i = 0; i < buttons.length; i++) {
				var btn = buttons[i];
				var n = parseInt(btn.getAttribute('data-cli-wizard-goto'), 10);
				btn.classList.remove('cli-wiz-stp--active', 'cli-wiz-stp--done');
				if (n < step) {
					btn.classList.add('cli-wiz-stp--done');
					btn.querySelector('.cli-wiz-stp-c').textContent = '✓';
				} else if (n === step) {
					btn.classList.add('cli-wiz-stp--active');
					btn.querySelector('.cli-wiz-stp-c').textContent = String(n);
				} else {
					btn.querySelector('.cli-wiz-stp-c').textContent = String(n);
				}
			}
		}

		function showStep(n) {
			step = Math.max(1, Math.min(maxStep, n));
			for (var p = 0; p < panes.length; p++) {
				var pane = panes[p];
				var ps = parseInt(pane.getAttribute('data-cli-wizard-step'), 10);
				if (ps === step) {
					pane.classList.add('cli-wizard-pane--active');
					pane.removeAttribute('hidden');
				} else {
					pane.classList.remove('cli-wizard-pane--active');
					pane.setAttribute('hidden', 'hidden');
				}
			}
			if (prevBtn) {
				prevBtn.disabled = step <= 1;
			}
			if (nextBtn) {
				nextBtn.classList.toggle('d-none', step >= maxStep);
			}
			if (saveBtn) {
				saveBtn.classList.toggle('d-none', step < maxStep);
			}
			if (labelEl) {
				var activePane = root.querySelector('.cli-wizard-pane[data-cli-wizard-step="' + step + '"]');
				var title = activePane ? activePane.getAttribute('data-cli-wizard-title') : '';
				labelEl.textContent = title || ('Etapa ' + step + ' de ' + maxStep);
			}
			syncStepper();
		}

		if (prevBtn) {
			prevBtn.addEventListener('click', function () {
				showStep(step - 1);
			});
		}
		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				showStep(step + 1);
			});
		}
		if (stepper) {
			stepper.addEventListener('click', function (ev) {
				var t = ev.target.closest('[data-cli-wizard-goto]');
				if (!t) {
					return;
				}
				ev.preventDefault();
				showStep(parseInt(t.getAttribute('data-cli-wizard-goto'), 10));
			});
		}
		showStep(step);
	}

	function boot() {
		var roots = document.querySelectorAll('[data-cli-wizard-root]');
		for (var i = 0; i < roots.length; i++) {
			initWizard(roots[i]);
		}
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
