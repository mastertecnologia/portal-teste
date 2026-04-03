<?php
/**
 * RSS/XML: saída não é HTML — sem CSS nem data-pgm-theme (consumidores leem XML).
 */
if (!isset($channel)):
    $channel = [];
endif;
if (!isset($channel['title'])):
    $channel['title'] = $this->fetch('title');
endif;

echo $this->Rss->document(
    $this->Rss->channel(
        [], $channel, $this->fetch('content')
    )
);
?>
