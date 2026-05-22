/**
 * CRUD de contatos do cliente — modal Bootstrap + API JSON.
 */
(function () {
	'use strict';
	var cfg = window.PgmClienteContatosConfig;
	if (!cfg || !cfg.ready) {
		return;
	}
	var root = document.querySelector('[data-cli-contatos-root]');
	if (!root) {
		return;
	}
	var listEl = document.getElementById('cli-contatos-crm-list');
	var emptyEl = document.getElementById('cli-contatos-crm-empty');
	var modalEl = document.getElementById('modal-cli-contato');
	var delModalEl = document.getElementById('modal-cli-contato-del');
	var fldId = document.getElementById('cli-contato-id');
	var fldNome = document.getElementById('cli-contato-nome');
	var fldCargo = document.getElementById('cli-contato-cargo');
	var fldEmail = document.getElementById('cli-contato-email');
	var fldFone = document.getElementById('cli-contato-fone');
	var fldPrincipal = document.getElementById('cli-contato-principal');
	var fldErr = document.getElementById('cli-contato-err');
	var btnSave = document.getElementById('cli-contato-save');
	var modalTitle = document.getElementById('modal-cli-contato-title');
	var delIdInput = document.getElementById('cli-contato-del-id');
	var btnDelConfirm = document.getElementById('cli-contato-del-confirm');
	var L = cfg.labels || {};

	function csrfToken() {
		var m = document.querySelector('meta[name="csrfToken"]');
		return m ? m.getAttribute('content') : '';
	}

	function showModal(el) {
		if (!el) {
			return;
		}
		if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.modal) {
			window.jQuery(el).modal('show');
			return;
		}
		el.classList.add('show');
		el.style.display = 'block';
		el.setAttribute('aria-hidden', 'false');
		document.body.classList.add('modal-open');
	}

	function hideModal(el) {
		if (!el) {
			return;
		}
		if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.modal) {
			window.jQuery(el).modal('hide');
			return;
		}
		el.classList.remove('show');
		el.style.display = 'none';
		el.setAttribute('aria-hidden', 'true');
	}

	function postJson(url, body) {
		var fd = new FormData();
		Object.keys(body).forEach(function (k) {
			if (body[k] !== undefined && body[k] !== null) {
				fd.append(k, body[k]);
			}
		});
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-CSRF-Token': csrfToken() },
			body: fd,
		}).then(function (r) {
			return r.json();
		});
	}

	function setErr(msg) {
		if (!fldErr) {
			return;
		}
		if (msg) {
			fldErr.textContent = msg;
			fldErr.classList.remove('d-none');
		} else {
			fldErr.textContent = '';
			fldErr.classList.add('d-none');
		}
	}

	function readFromLi(li) {
		if (!li) {
			return {};
		}
		return {
			id: li.getAttribute('data-contato-id') || '',
			nome: li.getAttribute('data-contato-nome') || '',
			cargo: li.getAttribute('data-contato-cargo') || '',
			email: li.getAttribute('data-contato-email') || '',
			fone: li.getAttribute('data-contato-fone') || '',
			principal: li.getAttribute('data-contato-principal') === '1',
		};
	}

	function openFormModal(existing) {
		existing = existing || {};
		if (fldId) {
			fldId.value = existing.id ? String(existing.id) : '';
		}
		if (fldNome) {
			fldNome.value = existing.nome || '';
		}
		if (fldCargo) {
			fldCargo.value = existing.cargo || '';
		}
		if (fldEmail) {
			fldEmail.value = existing.email || '';
		}
		if (fldFone) {
			fldFone.value = existing.fone || '';
		}
		if (fldPrincipal) {
			fldPrincipal.checked = !!existing.principal;
		}
		setErr('');
		if (modalTitle) {
			modalTitle.textContent = existing.id ? (L.editTitle || 'Editar contato') : (L.addTitle || 'Novo contato');
		}
		showModal(modalEl);
		if (fldNome) {
			setTimeout(function () {
				fldNome.focus();
			}, 300);
		}
	}

	function collectPayload() {
		var nome = fldNome ? fldNome.value.trim() : '';
		if (nome === '') {
			setErr(L.errNome || 'Informe o nome.');
			return null;
		}
		setErr('');
		var payload = {
			nome: nome,
			cargo: fldCargo ? fldCargo.value.trim() : '',
			email: fldEmail ? fldEmail.value.trim() : '',
			fone: fldFone ? fldFone.value.trim() : '',
			principal: fldPrincipal && fldPrincipal.checked ? '1' : '0',
		};
		if (fldId && fldId.value) {
			payload.id = fldId.value;
		}
		return payload;
	}

	function escapeHtml(s) {
		var d = document.createElement('div');
		d.textContent = s;
		return d.innerHTML;
	}

	function escAttr(s) {
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/</g, '&lt;');
	}

	function renderList(contatos) {
		if (!listEl) {
			return;
		}
		listEl.innerHTML = '';
		contatos.forEach(function (c) {
			var li = document.createElement('li');
			li.className = 'cli-contatos-crm-item';
			li.setAttribute('data-contato-id', String(c.id));
			li.setAttribute('data-contato-nome', c.nome || '');
			li.setAttribute('data-contato-cargo', c.cargo || '');
			li.setAttribute('data-contato-email', c.email || '');
			li.setAttribute('data-contato-fone', c.fone || '');
			li.setAttribute('data-contato-principal', c.principal ? '1' : '0');
			li.innerHTML =
				'<div class="cli-av cli-av--' + escapeHtml(c.av_tone || 'teal') + '">' + escapeHtml(c.iniciais || 'C') + '</div>' +
				'<div class="cli-contatos-crm-body"><strong>' + escapeHtml(c.nome) + '</strong>' +
				(c.principal ? ' <span class="cli-contatos-crm-badge">Principal</span>' : '') +
				(c.cargo ? '<div class="cli-contatos-crm-meta">' + escapeHtml(c.cargo) + '</div>' : '') +
				(c.email ? '<div class="cli-contatos-crm-meta">' + escapeHtml(c.email) + '</div>' : '') +
				(c.fone ? '<div class="cli-contatos-crm-meta">' + escapeHtml(c.fone) + '</div>' : '') +
				'</div>' +
				'<div class="cli-contatos-crm-act">' +
				'<button type="button" class="btn-cli-ghost btn-cli-sm" data-cli-contato-edit="' + c.id + '" title="Editar"><i class="fas fa-pen" aria-hidden="true"></i></button>' +
				'<button type="button" class="btn-cli-ghost btn-cli-sm text-danger" data-cli-contato-del="' + c.id + '" title="Excluir"><i class="fas fa-trash" aria-hidden="true"></i></button>' +
				'</div>';
			listEl.appendChild(li);
		});
		if (emptyEl) {
			emptyEl.classList.toggle('d-none', contatos.length > 0);
		}
	}

	function reload() {
		return fetch(cfg.urls.list, { credentials: 'same-origin' })
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				if (data.ok && data.contatos) {
					renderList(data.contatos);
				}
			});
	}

	if (btnSave) {
		btnSave.addEventListener('click', function () {
			var payload = collectPayload();
			if (!payload) {
				return;
			}
			btnSave.disabled = true;
			postJson(cfg.urls.save, payload)
				.then(function (data) {
					btnSave.disabled = false;
					if (data.ok) {
						hideModal(modalEl);
						reload();
					} else {
						setErr(data.error || L.errSave || 'Erro ao salvar.');
					}
				})
				.catch(function () {
					btnSave.disabled = false;
					setErr(L.errSave || 'Erro ao salvar.');
				});
		});
	}

	if (btnDelConfirm) {
		btnDelConfirm.addEventListener('click', function () {
			var id = delIdInput ? delIdInput.value : '';
			if (!id) {
				return;
			}
			btnDelConfirm.disabled = true;
			postJson(cfg.urls.delete, { id: id })
				.then(function (data) {
					btnDelConfirm.disabled = false;
					if (data.ok) {
						hideModal(delModalEl);
						reload();
					} else {
						window.alert(data.error || 'Erro ao excluir.');
					}
				})
				.catch(function () {
					btnDelConfirm.disabled = false;
				});
		});
	}

	root.addEventListener('click', function (ev) {
		var addBtn = ev.target.closest('[data-cli-contato-add]');
		if (addBtn) {
			ev.preventDefault();
			openFormModal({});
			return;
		}
		var editBtn = ev.target.closest('[data-cli-contato-edit]');
		if (editBtn) {
			ev.preventDefault();
			var li = editBtn.closest('[data-contato-id]');
			openFormModal(readFromLi(li));
			return;
		}
		var delBtn = ev.target.closest('[data-cli-contato-del]');
		if (delBtn) {
			ev.preventDefault();
			var delId = delBtn.getAttribute('data-cli-contato-del');
			if (delIdInput) {
				delIdInput.value = delId;
			}
			showModal(delModalEl);
		}
	});

	/** Abrir modal novo contato quando URL tem #cliente e query contato=1 (link da 360°). */
	if (typeof URLSearchParams !== 'undefined') {
		var params = new URLSearchParams(window.location.search);
		if (params.get('contato') === '1' && modalEl) {
			setTimeout(function () {
				openFormModal({});
			}, 600);
		}
	}
})();
