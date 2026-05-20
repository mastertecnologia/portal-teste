/*!
 * pgm-push-sw.js — Service Worker para notificações Web Push + cache offline.
 *
 * Eventos:
 *  - install:    pré-cacheia assets críticos
 *  - activate:   take-over imediato + limpa caches antigos
 *  - fetch:      network-first para HTML (com fallback /offline), cache-first para CSS/JS/imagens
 *  - push:       exibe notificação (payload JSON {title, body, url, icon, tag})
 *  - notificationclick: abre/foca janela na url do payload
 */
var PGM_SW_CACHE = 'pgm-erp-v1';
var PGM_SW_PRECACHE = [
	'/portal/dist/css/pgm-erp-prototype.css',
	'/portal/dist/css/style.min.css',
	'/portal/offline.html'
];

self.addEventListener('install', function (event) {
	event.waitUntil(
		caches.open(PGM_SW_CACHE).then(function (cache) {
			return cache.addAll(PGM_SW_PRECACHE).catch(function () { /* alguns paths podem falhar */ });
		}).then(function () { return self.skipWaiting(); })
	);
});

self.addEventListener('activate', function (event) {
	event.waitUntil(
		caches.keys().then(function (keys) {
			return Promise.all(keys.filter(function (k) { return k !== PGM_SW_CACHE; }).map(function (k) { return caches.delete(k); }));
		}).then(function () { return self.clients.claim(); })
	);
});

self.addEventListener('fetch', function (event) {
	if (event.request.method !== 'GET') return;
	var url = new URL(event.request.url);
	if (url.origin !== self.location.origin) return;
	// Não cacheia API JSON / dinâmico
	if (url.pathname.indexOf('/api/') !== -1 || url.pathname.indexOf('/web-push/') !== -1) return;

	var isAsset = /\.(css|js|png|jpg|jpeg|svg|woff2?|ttf|ico)$/i.test(url.pathname);
	if (isAsset) {
		// Cache-first para assets estáticos
		event.respondWith(
			caches.match(event.request).then(function (cached) {
				if (cached) return cached;
				return fetch(event.request).then(function (resp) {
					if (resp.ok) {
						var clone = resp.clone();
						caches.open(PGM_SW_CACHE).then(function (c) { c.put(event.request, clone); });
					}
					return resp;
				}).catch(function () { return cached || new Response('', {status: 504}); });
			})
		);
		return;
	}

	// Network-first para HTML com fallback offline
	if (event.request.headers.get('accept') && event.request.headers.get('accept').indexOf('text/html') !== -1) {
		event.respondWith(
			fetch(event.request).catch(function () {
				return caches.match('/portal/offline.html').then(function (off) { return off || new Response('Offline', {status: 504, headers: {'Content-Type': 'text/plain'}}); });
			})
		);
	}
});

self.addEventListener('push', function (event) {
	var data = {};
	if (event.data) {
		try { data = event.data.json(); } catch (e) {
			try { data = {title: 'PGM ERP', body: event.data.text()}; } catch (e2) {}
		}
	}
	var title = data.title || 'PGM ERP';
	var options = {
		body: data.body || '',
		icon: data.icon || '/favicon.ico',
		badge: data.badge || '/favicon.ico',
		tag: data.tag || 'pgm-erp',
		data: {url: data.url || '/'},
		requireInteraction: !!data.requireInteraction
	};
	event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
	event.notification.close();
	var url = (event.notification.data && event.notification.data.url) || '/';
	event.waitUntil(
		self.clients.matchAll({type: 'window', includeUncontrolled: true}).then(function (list) {
			for (var i = 0; i < list.length; i++) {
				var c = list[i];
				if (c.url.indexOf(url) !== -1 && 'focus' in c) return c.focus();
			}
			return self.clients.openWindow(url);
		})
	);
});
