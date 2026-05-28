<?php if (!empty($pgmLoadErpPrototypeAssets)) : ?>
<div class="pgm-erp-shell pgm-erp-shell--in-portal pgm-erp-content">
<div class="pgm-erp-prototype-dynamic tirar-black-mode" id="pgm-dynamic-content">
	<div class="pgm-erp-prototype-flash aquivaiosalert"><?= $this->Flash->render() ?></div>
	<?= $this->fetch('content') ?>
</div>
</div>
<?php else : ?>
<div class="row tirar-black-mode" id="pgm-dynamic-content">
	<div class="col-md-12 aquivaiosalert"><?= $this->Flash->render() ?></div>
	<?= $this->fetch('content') ?>
</div>
<?php endif; ?>
