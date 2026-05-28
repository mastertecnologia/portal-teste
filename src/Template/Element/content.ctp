<?php if (!empty($pgmLoadErpPrototypeAssets)) : ?><div class="pgm-erp-shell pgm-erp-shell--in-portal pgm-erp-content"><?php endif; ?>
<div class="row tirar-black-mode" id="pgm-dynamic-content">
	<div class="col-md-12 aquivaiosalert"><?= $this->Flash->render() ?></div>
	<?= $this->fetch('content') ?>
</div>
<?php if (!empty($pgmLoadErpPrototypeAssets)) : ?></div><?php endif; ?>
