<?php
/**
 * Tela "Ativar notificações" Web Push.
 *
 * @var \App\View\AppView $this
 * @var string $vapidPublic
 * @var int $subscriptionsAtivas
 * @var bool $hasPublicKey
 */
$csrf = (string)$this->request->getAttribute('csrfToken');
$w = $this->request->getAttribute('webroot');
?>
<div style="max-width:600px;margin:0 auto;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Conta')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">🔔 <?= h(__('Notificações Push')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Receba alertas mesmo com o navegador minimizado')) ?></div>
		</div>
	</div>

	<?php if (!$hasPublicKey) : ?>
		<div class="alert-box alert-amber">
			<strong><?= h(__('Configuração pendente.')) ?></strong>
			<?= h(__('Defina WEB_PUSH_VAPID_PUBLIC no .env (chave VAPID gerada com web-push-libs). Sem ela o browser não consegue se inscrever.')) ?>
		</div>
		<div class="card">
			<div class="sec-title"><?= h(__('Como gerar VAPID keys')) ?></div>
			<pre style="font-family:'SFMono-Regular',Consolas,monospace;font-size:11px;background:var(--bg-surface);padding:14px;border-radius:6px;overflow-x:auto;">
# Node.js (uma vez)
npx web-push generate-vapid-keys

# Coloque no .env do portal:
WEB_PUSH_VAPID_PUBLIC=BNyAaP3...
WEB_PUSH_VAPID_PRIVATE=xMVy...
</pre>
		</div>
	<?php else : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<div class="summary-card" style="border-left:3px solid var(--teal);">
				<div class="lbl"><?= h(__('Inscrições ativas neste usuário')) ?></div>
				<div class="val" style="color:var(--teal-dark);"><?= (int)$subscriptionsAtivas ?></div>
				<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Cada navegador / dispositivo gera uma inscrição')) ?></div>
			</div>
		</div>

		<div class="card">
			<div class="sec-title"><?= h(__('Ativar neste navegador')) ?></div>
			<p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">
				<?= h(__('Ao clicar, o navegador pede permissão e registra esta sessão. Você pode desativar a qualquer momento.')) ?>
			</p>
			<div style="display:flex;gap:8px;flex-wrap:wrap;">
				<button type="button" id="pushSubBtn" class="btn btn-primary"><?= h(__('🔔 Ativar notificações')) ?></button>
				<button type="button" id="pushUnsubBtn" class="btn btn-ghost"><?= h(__('🔕 Desativar')) ?></button>
				<button type="button" id="pushTestBtn" class="btn btn-ghost"<?= (int)$subscriptionsAtivas === 0 ? ' disabled' : '' ?>><?= h(__('🧪 Enviar teste')) ?></button>
			</div>
			<div id="pushMsg" style="margin-top:14px;font-size:12px;"></div>
		</div>

		<div class="alert-box alert-blue">
			<strong><?= h(__('Estado da integração:')) ?></strong>
			<?= h(__('infraestrutura pronta (browser → portal → DB).')) ?>
			<?= h(__('Falta o lado servidor enviando notificações via web-push (PHP minishlink/web-push) — roadmap.')) ?>
		</div>
	<?php endif; ?>
</div>

<?php $this->start('script'); ?>
<script>
(function () {
	var vapid = <?= json_encode($vapidPublic) ?>;
	var csrf = <?= json_encode($csrf) ?>;
	var sw = <?= json_encode($w . 'js/pgm-push-sw.js') ?>;
	var msg = document.getElementById('pushMsg');

	function setMsg(text, color) {
		if (!msg) return;
		msg.innerHTML = '<span style="color:' + (color || '#1a1a18') + '">' + text + '</span>';
	}

	function urlBase64ToUint8Array(base64String) {
		var padding = '='.repeat((4 - base64String.length % 4) % 4);
		var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
		var raw = atob(base64);
		var arr = new Uint8Array(raw.length);
		for (var i = 0; i < raw.length; ++i) arr[i] = raw.charCodeAt(i);
		return arr;
	}

	function jsonFromSubscription(sub) {
		var raw = JSON.parse(JSON.stringify(sub));
		// Cake convencionalmente faz dot-path em getData; achatamos keys.
		return {
			endpoint: raw.endpoint,
			'keys[p256dh]': raw.keys.p256dh,
			'keys[auth]': raw.keys.auth,
			_csrfToken: csrf
		};
	}

	var sub = document.getElementById('pushSubBtn');
	var uns = document.getElementById('pushUnsubBtn');
	var tst = document.getElementById('pushTestBtn');

	tst && tst.addEventListener('click', function () {
		setMsg('⏳ Enviando teste...');
		var fd = new FormData();
		fd.append('_csrfToken', csrf);
		fetch('<?= h($this->Url->build(['controller' => 'WebPush', 'action' => 'test'])) ?>', {
			method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-CSRF-Token': csrf}
		})
			.then(function (r) { return r.json(); })
			.then(function (d) {
				if (!d.ok && (d.sent === 0 && d.dry === 0)) {
					setMsg('✗ Falha: ' + (d.error || 'sem inscrições ativas'), '#7A1822');
					return;
				}
				var modo = d.driver === 'minishlink'
					? '(envio real via minishlink/web-push — verifique seu navegador)'
					: '(dry-run: minishlink/web-push não instalado; logado em logs/debug.log)';
				setMsg('✓ Enviadas: ' + d.sent + ' · Dry-run: ' + d.dry + ' · Erros: ' + d.errors + ' ' + modo, '#0F6E56');
			})
			.catch(function (e) { setMsg('✗ Erro de rede.', '#7A1822'); });
	});

	sub && sub.addEventListener('click', function () {
		if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
			setMsg('⚠ Navegador não suporta Web Push.', '#7A1822');
			return;
		}
		if (!vapid) { setMsg('⚠ VAPID não configurado.', '#7A1822'); return; }
		setMsg('⏳ Registrando service worker...');
		navigator.serviceWorker.register(sw, {scope: '/'})
			.then(function (reg) {
				return Notification.requestPermission().then(function (perm) {
					if (perm !== 'granted') throw new Error('Permissão negada');
					return reg.pushManager.subscribe({
						userVisibleOnly: true,
						applicationServerKey: urlBase64ToUint8Array(vapid)
					});
				});
			})
			.then(function (subscription) {
				var payload = jsonFromSubscription(subscription);
				var fd = new FormData();
				Object.keys(payload).forEach(function (k) { fd.append(k, payload[k]); });
				return fetch('<?= h($this->Url->build(['controller' => 'WebPush', 'action' => 'subscribe'])) ?>', {
					method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-CSRF-Token': csrf}
				});
			})
			.then(function (r) { return r.json(); })
			.then(function (d) {
				if (d.ok) {
					setMsg('✓ Inscrito! Você vai receber notificações neste navegador.', '#0F6E56');
					setTimeout(function () { window.location.reload(); }, 1500);
				} else {
					setMsg('✗ Falha: ' + (d.error || 'desconhecido'), '#7A1822');
				}
			})
			.catch(function (e) { setMsg('✗ ' + e.message, '#7A1822'); });
	});

	uns && uns.addEventListener('click', function () {
		if (!('serviceWorker' in navigator)) return;
		setMsg('⏳ Removendo inscrição...');
		navigator.serviceWorker.getRegistration().then(function (reg) {
			if (!reg) { setMsg('Nenhum service worker.', '#6b6a65'); return; }
			return reg.pushManager.getSubscription().then(function (sub) {
				if (!sub) { setMsg('Nenhuma inscrição ativa.', '#6b6a65'); return; }
				var endpoint = sub.endpoint;
				return sub.unsubscribe().then(function () {
					var fd = new FormData();
					fd.append('endpoint', endpoint);
					fd.append('_csrfToken', csrf);
					return fetch('<?= h($this->Url->build(['controller' => 'WebPush', 'action' => 'unsubscribe'])) ?>', {
						method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-CSRF-Token': csrf}
					});
				}).then(function () { setMsg('✓ Desativado.', '#0F6E56'); setTimeout(function () { window.location.reload(); }, 1000); });
			});
		});
	});
})();
</script>
<?php $this->end(); ?>
