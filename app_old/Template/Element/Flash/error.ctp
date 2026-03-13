<?php
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="alert alert-danger">
    <div class="container-fluid">

		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
			<span aria-hidden="true"><i class="ti ti-close"></i></span>
		</button>

    	<b></b> <i class="ti ti-na"></i> <?= $message ?>
    </div>
</div>