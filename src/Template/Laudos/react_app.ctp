<?php
/**
 * Shell React do módulo Laudos / Parecer Técnico.
 * Reutiliza o bundle dashboard-react (webroot/tickets-app/) com screen laudos_list | laudos_edit.
 *
 * Variáveis esperadas do controller:
 *   $reactBoot  — array com: screen, parecerId (para edit)
 */
$w = $this->request->getAttribute('webroot');

$bootJson = json_encode(
    $reactBoot ?? [],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$ticketsCssFs = defined('WWW_ROOT') ? WWW_ROOT . 'tickets-app' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'tickets.css' : '';
$ticketsJsFs  = defined('WWW_ROOT') ? WWW_ROOT . 'tickets-app' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'tickets.js'  : '';

$ticketsAssetV = '1';
if ($ticketsJsFs !== '' && is_file($ticketsJsFs)) {
    $md5f = @md5_file($ticketsJsFs);
    $jsHash = (is_string($md5f) && strlen($md5f) === 32) ? substr($md5f, 0, 8) : '0';
    $mt = filemtime($ticketsJsFs);
    if ($ticketsCssFs !== '' && is_file($ticketsCssFs)) {
        $mt = max($mt, filemtime($ticketsCssFs));
    }
    $ticketsAssetV = (string)$mt . '-' . $jsHash;
}

$this->append(
    'css',
    '<link rel="stylesheet" href="' . h($w . 'tickets-app/assets/tickets.css?v=' . $ticketsAssetV) . '">'
);

$ticketsJsSrc = $w . 'tickets-app/assets/tickets.js?v=' . $ticketsAssetV;
?>
<div class="col-md-12 p-0" style="min-height:80vh">
    <link rel="stylesheet" href="<?= h($w . 'tickets-app/assets/tickets.css?v=' . $ticketsAssetV) ?>">
    <div id="tickets-react-root" class="w-100"></div>
</div>
<script>
(function () {
    var ticketsAppAssetV = <?= json_encode($ticketsAssetV, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) ?>;
    if (window.__TICKETS_APP_ASSET_V__ && window.__TICKETS_APP_ASSET_V__ !== ticketsAppAssetV) {
        window.__TICKETS_APP_ASSET_V__ = ticketsAppAssetV;
        window.location.reload();
        return;
    }
    window.__TICKETS_APP_ASSET_V__ = ticketsAppAssetV;
    window.__TICKETS_BOOT__ = <?= $bootJson ?>;

    var ticketsJsSrc = <?= json_encode($ticketsJsSrc, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) ?>;

    function mountWhenReady(attemptsLeft) {
        if (typeof window.__pgmTicketsReactMount !== 'function') return;
        var el = document.getElementById('tickets-react-root');
        if (el) { window.__pgmTicketsReactMount(); return; }
        if (attemptsLeft > 0) requestAnimationFrame(function () { mountWhenReady(attemptsLeft - 1); });
    }

    function boot() {
        if (window.__pgmTicketsReactMount) { mountWhenReady(12); return; }
        var s = document.createElement('script');
        s.type = 'module';
        s.src = ticketsJsSrc;
        document.head.appendChild(s);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
</script>
