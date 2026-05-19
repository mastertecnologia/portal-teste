<?php
/**
 * @var \App\View\AppView $this
 * @var string $title
 * @var array<string,mixed> $sdpScreenMeta
 */
$this->assign('title', $title);
$hint = (string)($sdpScreenMeta['hint'] ?? '');
$links = (array)($sdpScreenMeta['links'] ?? []);
?>
<div class="row">
	<div class="col-12 pgm-sd-prototype">
		<div class="sdp-card" style="max-width:720px;">
			<h1 class="sdp-title" style="font-size:20px;"><?= h($title) ?></h1>
			<p class="sdp-muted" style="margin-bottom:16px;"><?= h($hint) ?></p>
			<?php if ($links !== []) : ?>
				<div style="display:flex;flex-direction:column;gap:8px;">
					<?php foreach ($links as $lnk) : ?>
						<?php
						$label = (string)($lnk['label'] ?? '');
						$url = $lnk['url'] ?? '#';
						if ($label === '') {
							continue;
						}
						?>
						<?= $this->Html->link($label, $url, ['class' => 'sdp-btn sdp-ghost sdp-sm', 'style' => 'display:inline-flex;width:fit-content;']) ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<hr style="border-color:var(--sdp-border);margin:18px 0;">
			<?= $this->Html->link(__('Índice do protótipo'), ['controller' => 'ServicedeskPrototype', 'action' => 'index'], ['class' => 'btn btn-link btn-sm', 'style' => 'padding-left:0;']) ?>
		</div>
	</div>
</div>
