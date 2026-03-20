<?php
/**
 * Shell da UI React dos tickets: injeta boot JSON e assets estáticos do Vite (public/tickets-app).
 */
$this->assign('title', $title ?? 'Tickets');
$w = $this->request->getAttribute('webroot');
$bootJson = json_encode(
	$reactBoot ?? [],
	JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
$this->append(
	'css',
	'<link rel="stylesheet" href="' . h($w . 'tickets-app/assets/tickets.css') . '">'
);
$this->append(
	'script',
	'<script>window.__TICKETS_BOOT__ = ' . $bootJson . ';</script>'
	. '<script type="module" src="' . h($w . 'tickets-app/assets/tickets.js') . '"></script>'
);
?>
<div id="tickets-react-root" class="tickets-react-host w-100"></div>
