<?php
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);

    // if (isset($params['title'])) $title = h($params['title']);
}
?>
<!-- <div class="message success" onclick="this.classList.add('hidden')"><?= $message ?></div> -->

<div class="alert alert-success">
    <div class="container-fluid">

		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
			<span aria-hidden="true"><i class="ti ti-close"></i></span>
		</button>

    	<b></b> <i class="ti ti-check"></i> <?= $message ?>
    </div>
</div>
