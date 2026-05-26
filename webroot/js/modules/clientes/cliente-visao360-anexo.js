/**
 * Visão 360° — modal de anexo (upload via Tickets/apiAnexoUpload).
 */
(function () {
	'use strict';

	var initialized = false;

	function openModal() {
		var modal = document.getElementById('modal-cli360-anexo');
		if (!modal) {
			return;
		}
		if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.modal) {
			window.jQuery(modal).modal('show');
			return;
		}
		modal.classList.add('show');
		modal.style.display = 'block';
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('modal-open');
	}

	function bindOpenButtons() {
		document.querySelectorAll('[data-cli360-anexo-open]').forEach(function (btn) {
			if (btn.getAttribute('data-cli360-anexo-bound') === '1') {
				return;
			}
			btn.setAttribute('data-cli360-anexo-bound', '1');
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				openModal();
			});
		});
	}

	function initWithJquery(cfg) {
		var $ = window.jQuery;
		var $modal = $('#modal-cli360-anexo');
		var $ticket = $('#cli360-anexo-ticket');
		var $fileInput = $('#cli360-anexo-file');
		var $dropzone = $('#cli360-anexo-dropzone');
		var $pick = $('#cli360-anexo-pick');
		var $list = $('#cli360-anexo-filelist');
		var $submit = $('#cli360-anexo-submit');
		var $err = $('#cli360-anexo-err');
		var fileStore = [];

		function csrfToken() {
			var m = document.querySelector('meta[name="csrfToken"]');
			return m ? m.getAttribute('content') : '';
		}

		function showErr(msg) {
			if (!$err.length) {
				return;
			}
			if (!msg) {
				$err.addClass('d-none').text('');
				return;
			}
			$err.removeClass('d-none').text(msg);
		}

		function formatSize(bytes) {
			if (bytes < 1024) {
				return bytes + ' B';
			}
			if (bytes < 1024 * 1024) {
				return (bytes / 1024).toFixed(1) + ' KB';
			}
			return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
		}

		function syncSubmitState() {
			if ($submit.length) {
				$submit.prop('disabled', fileStore.length === 0);
			}
		}

		function renderFileList() {
			if (!$list.length) {
				return;
			}
			$list.empty();
			fileStore.forEach(function (file, idx) {
				var $li = $('<li class="cli-360-anexo-fileitem"/>');
				$li.append($('<span class="cli-360-anexo-fileitem-name"/>').text(file.name));
				$li.append($('<span class="cli-360-anexo-fileitem-size"/>').text(formatSize(file.size)));
				var $rm = $('<button type="button" class="btn-cli-ghost btn-cli-sm" aria-label="Remover"/>')
					.html('<i class="fas fa-times" aria-hidden="true"></i>')
					.on('click', function () {
						fileStore.splice(idx, 1);
						renderFileList();
						syncSubmitState();
					});
				$li.append($rm);
				$list.append($li);
			});
			syncSubmitState();
		}

		function addFiles(fileList) {
			if (!fileList || !fileList.length) {
				return;
			}
			var max = cfg.maxBytes || 26214400;
			var tooLargeTpl = (cfg.msg && cfg.msg.tooLarge) || 'Arquivo excede 25 MB: {0}';
			for (var i = 0; i < fileList.length; i++) {
				var f = fileList[i];
				if (!f || !f.name) {
					continue;
				}
				if (f.size > max) {
					showErr(tooLargeTpl.replace('{0}', f.name));
					continue;
				}
				var dup = fileStore.some(function (x) {
					return x.name === f.name && x.size === f.size;
				});
				if (!dup) {
					fileStore.push(f);
				}
			}
			showErr('');
			renderFileList();
		}

		function resetForm() {
			fileStore = [];
			if ($fileInput.length) {
				$fileInput.val('');
			}
			renderFileList();
			showErr('');
			$('#cli360-anexo-desc').val('');
			$('#cli360-anexo-categoria').val('outros');
		}

		function uploadOne(ticketId, file) {
			var fd = new FormData();
			fd.append('file', file);
			var token = csrfToken();
			var headers = { Accept: 'application/json' };
			if (token) {
				headers['X-CSRF-Token'] = token;
			}
			var url = cfg.uploadUrlBase + '/' + encodeURIComponent(String(ticketId));
			return fetch(url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: headers,
				body: fd,
			}).then(function (r) {
				return r.json().then(function (json) {
					return { ok: r.ok && json && json.ok, json: json, status: r.status };
				}).catch(function () {
					return { ok: false, json: null, status: r.status };
				});
			});
		}

		document.querySelectorAll('[data-cli360-anexo-open]').forEach(function (btn) {
			if (btn.getAttribute('data-cli360-anexo-bound') === '1') {
				return;
			}
			btn.setAttribute('data-cli360-anexo-bound', '1');
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				resetForm();
				$modal.modal('show');
			});
		});

		if (!$fileInput.length) {
			return;
		}

		var openingFileDialog = false;
		var fileEl = $fileInput[0];

		function openFilePicker() {
			if (openingFileDialog || !fileEl) {
				return;
			}
			openingFileDialog = true;
			fileEl.click();
			window.setTimeout(function () {
				openingFileDialog = false;
			}, 500);
		}

		$fileInput.on('click', function (e) {
			e.stopPropagation();
		});

		if ($pick.length) {
			$pick.on('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				openFilePicker();
			});
		}

		if ($dropzone.length) {
			$dropzone.on('click', function (e) {
				if ($pick.length && ($pick[0] === e.target || $pick[0].contains(e.target))) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				openFilePicker();
			});

			$dropzone.on('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					openFilePicker();
				}
			});
		}

		$fileInput.on('change', function () {
			addFiles(this.files);
			this.value = '';
		});

		$dropzone.on('dragover', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$dropzone.addClass('is-dragover');
		});

		$dropzone.on('dragleave drop', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$dropzone.removeClass('is-dragover');
			if (e.type === 'drop' && e.originalEvent && e.originalEvent.dataTransfer) {
				addFiles(e.originalEvent.dataTransfer.files);
			}
		});

		if ($submit.length) {
			$submit.on('click', function () {
				var ticketId = parseInt($ticket.val(), 10);
				if (!ticketId) {
					showErr((cfg.msg && cfg.msg.noTicket) || 'Selecione o ticket.');
					return;
				}
				if (fileStore.length === 0) {
					showErr((cfg.msg && cfg.msg.noFile) || 'Selecione arquivos.');
					return;
				}
				$submit.prop('disabled', true);
				showErr((cfg.msg && cfg.msg.uploading) || 'Enviando…');

				var chain = Promise.resolve();
				var failed = false;
				fileStore.forEach(function (file) {
					chain = chain.then(function () {
						return uploadOne(ticketId, file).then(function (res) {
							if (!res.ok) {
								failed = true;
							}
						});
					});
				});

				chain.then(function () {
					if (failed) {
						showErr((cfg.msg && cfg.msg.error) || 'Erro no envio.');
						syncSubmitState();
						return;
					}
					$modal.modal('hide');
					if (cfg.returnUrl) {
						window.location.href = cfg.returnUrl;
					} else {
						window.location.reload();
					}
				});
			});
		}

		$modal.on('hidden.bs.modal', resetForm);
	}

	function boot() {
		if (initialized) {
			return;
		}
		var cfg = window.PGM_CLI360_ANEXO;
		if (!cfg) {
			return;
		}
		initialized = true;

		if (!cfg.hasTickets) {
			bindOpenButtons();
			return;
		}

		if (typeof window.jQuery === 'undefined') {
			initialized = false;
			return;
		}

		initWithJquery(cfg);
	}

	function tryBoot() {
		boot();
		if (!initialized && window.PGM_CLI360_ANEXO) {
			bindOpenButtons();
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', tryBoot);
	} else {
		tryBoot();
	}
})();
