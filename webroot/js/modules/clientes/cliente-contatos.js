/**
 * CRUD de contatos do cliente (API JSON).
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

	function csrfToken() {
		var m = document.querySelector('meta[name="csrfToken"]');
		return m ? m.getAttribute('content') : '';
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

	function promptContato(existing) {
		existing = existing || {};
		var nome = window.prompt('Nome do contato:', existing.nome || '');
		if (nome === null) {
			return null;
		}
		nome = nome.trim();
		if (nome === '') {
			window.alert('Informe o nome.');
			return null;
		}
		var cargo = window.prompt('Cargo / função:', existing.cargo || '') || '';
		var email = window.prompt('E-mail:', existing.email || '') || '';
		var fone = window.prompt('Telefone:', existing.fone || '') || '';
		var principal = window.confirm('Definir como contato principal?');
		return { nome: nome, cargo: cargo, email: email, fone: fone, principal: principal ? '1' : '0' };
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
			li.innerHTML =
				'<div class="cli-av cli-av--' + (c.av_tone || 'teal') + '">' + (c.iniciais || 'C') + '</div>' +
				'<div class="cli-contatos-crm-body"><strong>' + escapeHtml(c.nome) + '</strong>' +
				(c.principal ? ' <span class="cli-contatos-crm-badge">Principal</span>' : '') +
				(c.cargo ? '<div class="cli-contatos-crm-meta">' + escapeHtml(c.cargo) + '</div>' : '') +
				(c.email ? '<div class="cli-contatos-crm-meta">' + escapeHtml(c.email) + '</div>' : '') +
				(c.fone ? '<div class="cli-contatos-crm-meta">' + escapeHtml(c.fone) + '</div>' : '') +
				'</div>' +
				'<div class="cli-contatos-crm-act">' +
				'<button type="button" class="btn-cli-ghost btn-cli-sm" data-cli-contato-edit="' + c.id + '"><i class="fas fa-pen"></i></button>' +
				'<button type="button" class="btn-cli-ghost btn-cli-sm text-danger" data-cli-contato-del="' + c.id + '"><i class="fas fa-trash"></i></button>' +
				'</div>';
			listEl.appendChild(li);
		});
		if (emptyEl) {
			emptyEl.classList.toggle('d-none', contatos.length > 0);
		}
	}

	function escapeHtml(s) {
		var d = document.createElement('div');
		d.textContent = s;
		return d.innerHTML;
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

	root.addEventListener('click', function (ev) {
		var addBtn = ev.target.closest('[data-cli-contato-add]');
		if (addBtn) {
			ev.preventDefault();
			var payload = promptContato();
			if (!payload) {
				return;
			}
			postJson(cfg.urls.save, payload).then(function (data) {
				if (data.ok) {
					reload();
				} else {
					window.alert(data.error || 'Erro ao salvar.');
				}
			});
			return;
		}
		var editBtn = ev.target.closest('[data-cli-contato-edit]');
		if (editBtn) {
			ev.preventDefault();
			var id = editBtn.getAttribute('data-cli-contato-edit');
			var item = editBtn.closest('[data-contato-id]');
			var nome = item ? item.querySelector('strong') : null;
			var payload = promptContato({ nome: nome ? nome.textContent : '' });
			if (!payload) {
				return;
			}
			payload.id = id;
			postJson(cfg.urls.save, payload).then(function (data) {
				if (data.ok) {
					reload();
				} else {
					window.alert(data.error || 'Erro ao salvar.');
				}
			});
			return;
		}
		var delBtn = ev.target.closest('[data-cli-contato-del]');
		if (delBtn) {
			ev.preventDefault();
			if (!window.confirm('Excluir este contato?')) {
				return;
			}
			postJson(cfg.urls.delete, { id: delBtn.getAttribute('data-cli-contato-del') }).then(function (data) {
				if (data.ok) {
					reload();
				} else {
					window.alert(data.error || 'Erro ao excluir.');
				}
			});
		}
	});
})();
