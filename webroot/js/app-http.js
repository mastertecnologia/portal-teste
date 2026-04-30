/**
 * HTTP helper global para requests CakePHP (CSRF + form-urlencoded).
 */
(function() {
	'use strict';
	if (window.PGMHttp) return;
	let refreshPromise = null;

	function getCsrfToken() {
		var el = document.querySelector('meta[name="csrfToken"]');
		if (el && el.getAttribute('content')) return el.getAttribute('content');
		var inp = document.querySelector('input[name="_csrfToken"]');
		return inp ? inp.value : '';
	}

	function toFormBody(data) {
		var body = new URLSearchParams();
		Object.keys(data || {}).forEach(function(key) {
			var value = data[key];
			body.append(key, value == null ? '' : value);
		});
		return body.toString();
	}

	function parseError(response) {
		var defaultMessage = 'Nao foi possivel concluir a requisicao.';
		if (!response) {
			return Promise.resolve({
				status: 0,
				message: defaultMessage,
				response: response
			});
		}

		var contentType = '';
		try {
			contentType = (response.headers && response.headers.get('content-type')) || '';
		} catch (e) {
			contentType = '';
		}

		var status = response.status || 0;
		var statusFallbacks = {
			400: 'Requisicao invalida.',
			401: 'Sessao expirada. Faca login novamente.',
			403: 'Acesso negado.',
			404: 'Recurso nao encontrado.',
			422: 'Dados invalidos. Revise os campos.',
			500: 'Erro interno. Tente novamente em instantes.',
			503: 'Servico temporariamente indisponivel.'
		};

		function buildError(message) {
			return {
				status: status,
				message: message || statusFallbacks[status] || defaultMessage,
				response: response
			};
		}

		if (contentType.indexOf('application/json') !== -1) {
			return response.clone().json()
				.then(function(payload) {
					var msg = '';
					if (payload && typeof payload === 'object') {
						msg = payload.message || payload.error || payload.title || '';
					}
					return buildError(msg);
				})
				.catch(function() {
					return buildError('');
				});
		}

		return response.clone().text()
			.then(function(text) {
				var msg = (text || '').trim();
				if (!msg) return buildError('');
				if (msg.length > 240) msg = msg.slice(0, 240).trim() + '...';
				return buildError(msg);
			})
			.catch(function() {
				return buildError('');
			});
	}

	function handleGlobalError(err) {
		if (!err || typeof err !== 'object') return;
		if (err._handled) return;

		if (err.status === 401) {
			err.message = err.message || 'Sessao expirada. Faca login novamente.';
		}

		if (window.PGMToast && typeof window.PGMToast.error === 'function') {
			window.PGMToast.error(err.message || 'Nao foi possivel concluir a requisicao.');
			err._handled = true;
			return;
		}

		console.warn('[PGMHttp] HTTP error:', err.message || 'Nao foi possivel concluir a requisicao.', err);
		err._handled = true;
	}

	function ensureOk(res, options) {
		if (res && res.ok) return Promise.resolve(res);
		return parseError(res).then(function(parsed) {
			var deferUnauthorizedHandling = options && options.deferUnauthorizedHandling === true;
			if (!(deferUnauthorizedHandling && parsed && parsed.status === 401)) {
				handleGlobalError(parsed);
			}
			throw parsed;
		});
	}

	function shouldRetry(err, options) {
		return err && err.status === 401 && options && options.retryOnUnauthorized === true;
	}

	function refreshSession() {
		if (refreshPromise) {
			return refreshPromise;
		}

		if (!window.PGMHttpConfig || !window.PGMHttpConfig.refreshSessionUrl) {
			return Promise.reject({
				status: 401,
				message: 'Sessao expirada. Faca login novamente.'
			});
		}

		refreshPromise = fetch(window.PGMHttpConfig.refreshSessionUrl, {
			method: 'POST',
			headers: {
				'X-CSRF-Token': getCsrfToken(),
				'X-Requested-With': 'XMLHttpRequest'
			},
			credentials: 'same-origin'
		}).then(function(res) {
			return ensureOk(res);
		}).then(function(result) {
			refreshPromise = null;
			return result;
		}, function(error) {
			refreshPromise = null;
			throw error;
		});

		return refreshPromise;
	}

	function executeWithRetry(requestFactory, options) {
		var opts = options || {};
		var alreadyRetried = false;

		function runRequest() {
			return requestFactory().then(function(res) {
				return ensureOk(res, {
					deferUnauthorizedHandling: !alreadyRetried && opts.retryOnUnauthorized === true
				});
			}).catch(function(err) {
				if (alreadyRetried || !shouldRetry(err, opts)) {
					throw err;
				}

				alreadyRetried = true;
				return refreshSession()
					.then(function() {
						return runRequest();
					})
					.catch(function(refreshErr) {
						if (refreshErr && !refreshErr._handled) {
							handleGlobalError(refreshErr);
						}
						throw refreshErr || err;
					});
			});
		}

		return runRequest();
	}

	function httpGet(url, options) {
		return executeWithRetry(function() {
			return fetch(url, {
				method: 'GET',
				credentials: 'same-origin',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json, text/plain, */*'
				},
				cache: 'no-store'
			});
		}, options);
	}

	function httpPost(url, data, options) {
		var payload = data || {};
		if (!Object.prototype.hasOwnProperty.call(payload, '_csrfToken')) {
			payload._csrfToken = getCsrfToken();
		}
		return executeWithRetry(function() {
			return fetch(url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
					'X-CSRF-Token': getCsrfToken(),
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json, text/plain, */*'
				},
				body: toFormBody(payload)
			});
		}, options);
	}

	function httpJsonPost(url, data, options) {
		var payload = data || {};
		return executeWithRetry(function() {
			return fetch(url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json; charset=UTF-8',
					'X-CSRF-Token': getCsrfToken(),
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json, text/plain, */*'
				},
				body: JSON.stringify(payload)
			});
		}, options);
	}

	function httpGetJson(url, options) {
		return executeWithRetry(function() {
			return fetch(url, {
				method: 'GET',
				credentials: 'same-origin',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json'
				},
				cache: 'no-store'
			});
		}, options).then(function(res) {
			return res.json();
		});
	}

	window.PGMHttp = {
		getCsrfToken: getCsrfToken,
		httpGet: httpGet,
		httpPost: httpPost,
		httpJsonPost: httpJsonPost,
		httpGetJson: httpGetJson
	};

})();
