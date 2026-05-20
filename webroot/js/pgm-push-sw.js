/*!
 * pgm-push-sw.js — Service Worker para notificações Web Push.
 *
 * Eventos:
 *  - install / activate: take-over imediato
 *  - push: exibe notificação (payload JSON {title, body, url, icon, tag})
 *  - notificationclick: abre/foca janela na url do payload
 */
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });

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
