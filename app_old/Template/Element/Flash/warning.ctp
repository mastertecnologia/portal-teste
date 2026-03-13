<?php
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);

    //if (isset($params['title'])) $title = h($params['title']);
}
?>

<div class="alert alert-warning">
    <div class="container-fluid">

		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
			<span aria-hidden="true"><i class="ti ti-close"></i></span>
		</button>

    	<b></b> <i class="ti ti-alert"></i> <?= $message ?>
    </div>
</div>
