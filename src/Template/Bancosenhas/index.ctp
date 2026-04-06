<?php
use Cake\Routing\Router;

$this->Breadcrumbs->add('Banco de Senhas', [], ['class' => 'breadcrumb-item active']);

$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap', ['fullBase' => true]);
echo $this->Html->css('/css/vault-cofre.css');
$this->end();

$vaultRevealUrl = Router::url(['controller' => 'Bancosenhas', 'action' => 'vaultReveal']);
$vaultEditBase = Router::url(['controller' => 'Bancosenhas', 'action' => 'edit']);
$vaultDeleteBase = Router::url(['controller' => 'Bancosenhas', 'action' => 'delete']);
$vaultEntries = isset($vaultMetaJson) ? $vaultMetaJson : '[]';
?>
<div class="col-12 p-0">
	<div class="vault-cofre" id="vaultCofre" data-reveal-url="<?= h($vaultRevealUrl) ?>">
		<header class="vault-cofre-header">
			<h1>
				<i class="fa fa-lock" aria-hidden="true"></i>
				Cofre de senhas
				<span class="vault-badge">Criptografado</span>
			</h1>
			<div class="vault-header-actions">
				<?= $this->Html->link(
					'<i class="fa fa-plus-circle"></i> Nova credencial',
					['action' => 'add'],
					['class' => 'btn btn-vault-primary', 'escape' => false]
				) ?>
			</div>
		</header>

		<div class="vault-cofre-body">
			<aside class="vault-sidebar">
				<div class="vault-search-wrap">
					<i class="fa fa-search" aria-hidden="true"></i>
					<input type="search" id="vaultSearch" placeholder="Buscar serviço, provedor ou usuário…" autocomplete="off" />
				</div>
				<div class="vault-list" id="vaultList" role="listbox" aria-label="Lista de credenciais"></div>
			</aside>

			<section class="vault-detail" id="vaultDetail">
				<div class="vault-detail-empty" id="vaultEmpty">
					<i class="fa fa-shield-alt" aria-hidden="true"></i>
					<p><strong>Selecione uma credencial</strong> na lista à esquerda para ver os detalhes.</p>
					<p class="text-muted small">As senhas permanecem criptografadas no banco até você confirmar com a <strong>senha administrativa da empresa</strong>.</p>
				</div>

				<div id="vaultDetailContent" class="vault-is-hidden">
					<div class="vault-detail-header">
						<div>
							<h2 id="vaultTitle">—</h2>
							<p class="text-muted small mb-0" id="vaultSubtitle"></p>
						</div>
						<div class="vault-detail-actions">
							<span id="vaultEditWrap"></span>
							<span id="vaultDeleteWrap"></span>
						</div>
					</div>

					<div class="vault-field-grid">
						<div class="vault-field">
							<label>Provedor</label>
							<div class="vault-value" id="vProvedor">—</div>
						</div>
						<div class="vault-field">
							<label>Usuário</label>
							<div class="vault-value" id="vUsuario">—</div>
						</div>
						<div class="vault-field">
							<label>IP</label>
							<div class="vault-value" id="vIp">—</div>
						</div>
						<div class="vault-field">
							<label>Porta</label>
							<div class="vault-value" id="vPorta">—</div>
						</div>
						<div class="vault-field">
							<label>Protocolo</label>
							<div class="vault-value" id="vProtocolo">—</div>
						</div>
						<div class="vault-field">
							<label>URL</label>
							<div class="vault-value" id="vUrl">—</div>
						</div>
					</div>

					<div class="vault-secret-row">
						<label>Senha</label>
						<div class="vault-value vault-secret-mask" id="vSecretDisplay">••••••••••••</div>
						<div class="vault-secret-actions">
							<button type="button" class="btn vault-btn-reveal" id="btnVaultReveal">
								<i class="fa fa-eye"></i> Revelar
							</button>
							<button type="button" class="btn vault-btn-copy" id="btnVaultCopy" disabled>
								<i class="fa fa-copy"></i> Copiar
							</button>
							<button type="button" class="btn btn-default btn-sm vault-is-hidden" id="btnVaultHide">
								<i class="fa fa-eye-slash"></i> Ocultar
							</button>
						</div>
					</div>

					<p class="vault-security-note">
						<strong>Segurança:</strong> o segredo é armazenado criptografado (legado PGM ou AES-256-CBC se
						<code>VAULT_ENCRYPTION_KEY</code> estiver no servidor). A confirmação usa apenas <strong>POST</strong>
						— a senha administrativa não vai na URL. Use HTTPS em produção. Evite revelar em telas compartilhadas.
					</p>
				</div>
			</section>
		</div>
	</div>
</div>

<div class="modal fade vault-modal" id="vaultModalAdmin" tabindex="-1" role="dialog" aria-labelledby="vaultModalAdminLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="vaultModalAdminLabel">Confirmar acesso ao cofre</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<p class="small text-muted">Informe a <strong>senha administrativa da empresa</strong> (cadastro da empresa no sistema).</p>
				<div class="form-group">
					<label for="vaultAdminPass">Senha administrativa</label>
					<input type="password" class="form-control" id="vaultAdminPass" autocomplete="current-password" />
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="vaultShowAdminPass" />
					<label class="custom-control-label" for="vaultShowAdminPass">Mostrar ao digitar</label>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-success" id="vaultModalConfirm"><i class="fa fa-unlock"></i> Desbloquear</button>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	var entries = <?= $vaultEntries ?>;
	var root = document.getElementById('vaultCofre');
	var revealUrl = root.getAttribute('data-reveal-url');
	var listEl = document.getElementById('vaultList');
	var searchEl = document.getElementById('vaultSearch');
	var emptyEl = document.getElementById('vaultEmpty');
	var contentEl = document.getElementById('vaultDetailContent');
	var selectedId = null;
	var revealedPlain = null;
	var revealTimer = null;

	function esc(s) {
		if (!s) return '';
		var d = document.createElement('div');
		d.textContent = s;
		return d.innerHTML;
	}

	function renderList(filter) {
		var q = (filter || '').toLowerCase().trim();
		listEl.innerHTML = '';
		var frag = document.createDocumentFragment();
		var n = 0;
		entries.forEach(function (e) {
			var hay = [e.nomeservico, e.provedor, e.usuario, e.url, e.ip].join(' ').toLowerCase();
			if (q && hay.indexOf(q) === -1) return;
			n++;
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'vault-item' + (selectedId === e.id ? ' is-active' : '');
			btn.setAttribute('role', 'option');
			btn.setAttribute('data-id', String(e.id));
			btn.innerHTML = '<div class="vault-item-title">' + esc(e.nomeservico || 'Sem nome') + '</div>' +
				'<div class="vault-item-meta">' + esc(e.provedor || '') + (e.usuario ? ' · ' + esc(e.usuario) : '') + '</div>';
			frag.appendChild(btn);
		});
		listEl.appendChild(frag);
		if (n === 0) {
			listEl.innerHTML = '<p class="vault-list-empty-msg">Nenhuma credencial encontrada.</p>';
		}
	}

	function findEntry(id) {
		for (var i = 0; i < entries.length; i++) {
			if (entries[i].id === id) return entries[i];
		}
		return null;
	}

	function clearReveal() {
		revealedPlain = null;
		if (revealTimer) clearTimeout(revealTimer);
		revealTimer = null;
		document.getElementById('vSecretDisplay').textContent = '••••••••••••';
		document.getElementById('vSecretDisplay').classList.add('vault-secret-mask');
		document.getElementById('btnVaultCopy').disabled = true;
		document.getElementById('btnVaultHide').classList.add('vault-is-hidden');
		document.getElementById('btnVaultHide').classList.remove('vault-btn-hide-reveal');
	}

	function selectEntry(id) {
		selectedId = id;
		var e = findEntry(id);
		if (!e) return;
		renderList(searchEl.value);
		emptyEl.classList.add('vault-is-hidden');
		contentEl.classList.remove('vault-is-hidden');
		document.getElementById('vaultTitle').textContent = e.nomeservico || 'Credencial';
		document.getElementById('vaultSubtitle').textContent = e.provedor ? e.provedor : '';
		document.getElementById('vProvedor').textContent = e.provedor || '—';
		document.getElementById('vUsuario').textContent = e.usuario || '—';
		document.getElementById('vIp').textContent = e.ip || '—';
		document.getElementById('vPorta').textContent = e.porta || '—';
		document.getElementById('vProtocolo').textContent = e.protocolo || '—';
		var urlEl = document.getElementById('vUrl');
		urlEl.textContent = '';
		if (e.url) {
			if (/^https?:\/\//i.test(e.url)) {
				var a = document.createElement('a');
				a.href = e.url;
				a.target = '_blank';
				a.rel = 'noopener noreferrer';
				a.textContent = e.url;
				urlEl.appendChild(a);
			} else {
				urlEl.textContent = e.url;
			}
		} else {
			urlEl.textContent = '—';
		}
		var editBase = <?= json_encode($vaultEditBase) ?>;
		var delBase = <?= json_encode($vaultDeleteBase) ?>;
		document.getElementById('vaultEditWrap').innerHTML =
			'<a class="btn btn-warning btn-sm" href="' + editBase + '/' + id + '"><i class="fa fa-edit"></i> Editar</a>';
		document.getElementById('vaultDeleteWrap').innerHTML =
			'<a class="btn btn-danger btn-sm" href="' + delBase + '/' + id + '" onclick="return confirm(\'Excluir esta credencial do cofre?\');"><i class="fa fa-times"></i> Excluir</a>';
		clearReveal();
	}

	listEl.addEventListener('click', function (ev) {
		var t = ev.target.closest('.vault-item');
		if (!t) return;
		selectEntry(parseInt(t.getAttribute('data-id'), 10));
	});

	searchEl.addEventListener('input', function () {
		renderList(searchEl.value);
	});

	document.getElementById('vaultShowAdminPass').addEventListener('change', function () {
		var inp = document.getElementById('vaultAdminPass');
		inp.type = this.checked ? 'text' : 'password';
	});

	document.getElementById('btnVaultReveal').addEventListener('click', function () {
		if (!selectedId) return;
		document.getElementById('vaultAdminPass').value = '';
		$('#vaultModalAdmin').modal('show');
		setTimeout(function () { document.getElementById('vaultAdminPass').focus(); }, 400);
	});

	document.getElementById('vaultModalConfirm').addEventListener('click', function () {
		var pass = document.getElementById('vaultAdminPass').value;
		if (!pass) {
			if (typeof bootbox !== 'undefined') bootbox.alert('Informe a senha administrativa.');
			else alert('Informe a senha administrativa.');
			return;
		}
		var btn = this;
		btn.disabled = true;
		var body = 'id=' + encodeURIComponent(selectedId) + '&senha_administrativa=' + encodeURIComponent(pass);
		fetch(revealUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: body,
			credentials: 'same-origin'
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				btn.disabled = false;
				$('#vaultModalAdmin').modal('hide');
				document.getElementById('vaultAdminPass').value = '';
				if (data && data.ok && data.password != null) {
					revealedPlain = data.password;
					var el = document.getElementById('vSecretDisplay');
					el.textContent = data.password;
					el.classList.remove('vault-secret-mask');
					document.getElementById('btnVaultCopy').disabled = false;
					document.getElementById('btnVaultHide').classList.remove('vault-is-hidden');
					document.getElementById('btnVaultHide').classList.add('vault-btn-hide-reveal');
					if (revealTimer) clearTimeout(revealTimer);
					revealTimer = setTimeout(function () { clearReveal(); }, 120000);
				} else {
					var err = (data && data.error) ? data.error : 'Não foi possível revelar a senha.';
					if (typeof bootbox !== 'undefined') bootbox.alert(err);
					else alert(err);
				}
			})
			.catch(function () {
				btn.disabled = false;
				if (typeof bootbox !== 'undefined') bootbox.alert('Erro de rede ao contactar o servidor.');
				else alert('Erro de rede ao contactar o servidor.');
			});
	});

	document.getElementById('btnVaultCopy').addEventListener('click', function () {
		if (!revealedPlain) return;
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(revealedPlain).then(function () {
				if (typeof $.toast !== 'undefined') {
					$.toast({ text: 'Copiado para a área de transferência.', position: 'top-right', loaderBg: '#00c08b', bgColor: '#1a1a18' });
				}
			});
		} else {
			var ta = document.createElement('textarea');
			ta.value = revealedPlain;
			document.body.appendChild(ta);
			ta.select();
			try { document.execCommand('copy'); } catch (e) {}
			document.body.removeChild(ta);
		}
	});

	document.getElementById('btnVaultHide').addEventListener('click', function () {
		clearReveal();
	});

	renderList('');
	if (entries.length === 1) {
		selectEntry(entries[0].id);
	}
})();
</script>
