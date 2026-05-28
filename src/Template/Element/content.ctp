<?php if (!empty($pgmLoadErpPrototypeAssets)) : ?><div class="pgm-erp-content pgm-erp-shell-inner"><?php endif; ?>
<div class="row tirar-black-mode" id="pgm-dynamic-content">
	<div class="col-md-12 aquivaiosalert"><?= $this->Flash->render() ?></div>
	<?= $this->fetch('content') ?>
</div>
<?php if (!empty($pgmLoadErpPrototypeAssets)) : ?></div><?php endif; ?>
