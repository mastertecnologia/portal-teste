<?php
/**
 * Layout AJAX (CakePHP): apenas o fragmento HTML da view — sem documento completo,
 * sem html[data-pgm-theme]. Views que precisem de estilos de tema devem usar
 * $this->Html->css(...) no bloco 'css' ou classes já definidas no app.
 */
?>
<?= $this->fetch('content') ?>
