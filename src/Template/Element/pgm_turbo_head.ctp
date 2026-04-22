<?php
/**
 * Hotwired Turbo (UMD) + Drive desligado — navegação por turbo-frame.
 * Incluir no <head> após jQuery se páginas dependerem de ordem de script global.
 */
?>
<script src="<?= h($this->Url->build('/js/turbo.es2017-umd.js')) ?>"></script>
<script>try { if (window.Turbo) { Turbo.session.drive = false; } } catch (e) {}</script>
