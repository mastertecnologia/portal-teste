/**
 * Ficha Clientes/edit — token portal, modal de dados, senha administrativa nos acessos, inativar.
 * Carregar depois de cliente-edit.js, PgmClienteEditConfig e cliente-edit-ficha.js.
 */
(function() {
	'use strict';
	if (window.__pgmClienteEditFichaAcessosInit) return;
	window.__pgmClienteEditFichaAcessosInit = true;

	function conf() {
		return window.PgmClienteEditConfig || {};
	}

	function byId(id) {
		return document.getElementById(id);
	}

	function bootboxAlert(html) {
		if (window.bootbox && typeof window.bootbox.alert === 'function') {
			window.bootbox.alert(html);
		} else {
			var div = document.createElement('div');
			div.innerHTML = html || '';
			alert((div.textContent || div.innerText || '').trim());
		}
	}

	function setModalVisible(modalEl, visible) {
		if (!modalEl) return;
		if (window.bootstrap && window.bootstrap.Modal) {
			var instance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
			if (visible) instance.show(); else instance.hide();
			return;
		}
		// Fallback sem plugin: mantém visual/interação mínima sem quebrar o fluxo.
		var backdrop = document.getElementById('pgm-modal-fallback-backdrop');
		if (visible) {
			if (!backdrop) {
				backdrop = document.createElement('div');
				backdrop.id = 'pgm-modal-fallback-backdrop';
				backdrop.className = 'modal-backdrop fade show';
				document.body.appendChild(backdrop);
			}
			document.body.classList.add('modal-open');
		} else {
			if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
			document.body.classList.remove('modal-open');
		}
		modalEl.style.display = visible ? 'block' : 'none';
		modalEl.setAttribute('aria-hidden', visible ? 'false' : 'true');
		if (visible) modalEl.classList.add('show'); else modalEl.classList.remove('show');
	}

	document.addEventListener('DOMContentLoaded', function() {
		if (!window.PGMHttp || typeof window.PGMHttp.httpPost !== 'function') {
			if (window.console && console.warn) console.warn('PGM: PGMHttp indisponível');
			return;
		}
		var C = conf();
		if (!C.clienteId) return;

		var urls = C.urls || {};
		var U = window.PgmClienteEditUtils || {};
		var formEditCliente = byId('form-edit-cliente');
		var modalConfirmaToken = byId('modal-confirmaToken');
		var modalSenha = byId('modal-senha');
		var inputIdSenha = byId('idsenha');
		var inputSenhaAdm = byId('senhaadministrativa');
		var inputSenhaCliente = byId('senha');
		var inputInativo = byId('inativo');
		var inputConfirmaNome = byId('confirmaNomeResponsavel');
		var inputConfirmaCpf = byId('confirmaCpfResponsavel');
		var inputConfirmaRg = byId('confirmaRgResponsavel');
		var inputExibirSenha = byId('exibirsenha');
		var inputExibirSenhaCliente = byId('exibirsenhacliente');

		document.addEventListener('click', function(e) {
			var btnAtualizaToken = e.target.closest('.btn-atualizaToken');
			if (btnAtualizaToken) {
				e.preventDefault();
				var urlTok = urls.updateToken || '';
				if (!C.isClientePortal) {
					window.location = urlTok;
				} else {
					setModalVisible(modalConfirmaToken, true);
				}
				return;
			}

			var btnAtualizaModal = e.target.closest('.btn-atualizaDentroDoModal');
			if (btnAtualizaModal) {
				e.preventDefault();
				var tok = U.pgmCsrfToken ? U.pgmCsrfToken() : '';
				window.PGMHttp.httpPost(urls.verificadadoscliente || '', {
					idcliente: C.clienteId,
					nomeresponsavel: inputConfirmaNome ? inputConfirmaNome.value : '',
					cpf: inputConfirmaCpf ? inputConfirmaCpf.value : '',
					rg: inputConfirmaRg ? inputConfirmaRg.value : '',
					_csrfToken: tok
				}).then(function(res) {
					return res.text();
				}).then(function(data) {
					if (String(data).trim() === 'tudocerto') {
						window.location = urls.updateToken || '';
					} else {
						bootboxAlert('<p class="text-center" style="font-size: 1.2rem">Os dados inseridos não conferem com os cadastrados no bando de dados.</p>');
					}
				}).catch(function() {
					bootboxAlert('<p class="text-center" style="font-size: 1.2rem">Não foi possível validar os dados agora.</p>');
				});
				return;
			}

			var btnSenha = e.target.closest('.senha');
			if (btnSenha) {
				var id = btnSenha.getAttribute('data-id') || '';
				if (inputIdSenha) inputIdSenha.value = id;
				setModalVisible(modalSenha, true);
				return;
			}

			var btnVerificaSenha = e.target.closest('.btn-verificasenha');
			if (btnVerificaSenha) {
				e.preventDefault();
				var csrf = U.pgmCsrfToken ? U.pgmCsrfToken() : '';
				window.PGMHttp.httpPost((urls.verificasenha || '') + '/', {
					id: inputIdSenha ? inputIdSenha.value : '',
					senhaadm: inputSenhaAdm ? inputSenhaAdm.value : '',
					idcliente: C.clienteId,
					_csrfToken: csrf
				}).then(function(res) {
					return res.text();
				}).then(function(data) {
					setModalVisible(modalSenha, false);
					bootboxAlert(data);
				}).catch(function() {
					alert('erro');
				});
				return;
			}

			var btnInativar = e.target.closest('.btn-inativar-cliente');
			if (btnInativar) {
				e.preventDefault();
				if (confirm('Você confirma a inativação deste cliente no portal e no ERP?')) {
					if (inputInativo) inputInativo.checked = true;
					if (formEditCliente) formEditCliente.submit();
				}
			}
		});

		if (inputExibirSenha) {
			inputExibirSenha.addEventListener('change', function() {
				if (inputSenhaAdm) inputSenhaAdm.type = inputExibirSenha.checked ? 'text' : 'password';
			});
		}

		if (inputExibirSenhaCliente) {
			inputExibirSenhaCliente.addEventListener('change', function() {
				if (inputSenhaCliente) inputSenhaCliente.type = inputExibirSenhaCliente.checked ? 'text' : 'password';
			});
		}
	});
})();
