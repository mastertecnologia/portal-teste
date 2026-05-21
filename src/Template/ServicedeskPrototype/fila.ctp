<?php
/**
 * @var \App\View\AppView $this
 * @var string $title
 */
$this->assign('title', $title);
$this->Breadcrumbs->add('Gestão de Incidentes', ['controller' => 'Servicedesk', 'action' => 'index']);
$this->Breadcrumbs->add('Service Desk protótipo', ['controller' => 'ServicedeskPrototype', 'action' => 'index']);
$this->Breadcrumbs->add('Fila técnica', ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'breadcrumb-item active']);

?>
<div class="row">
	<div class="col-12 pgm-sd-prototype">
		<div class="sdp-card">
			<h2 class="sdp-title" style="margin-bottom:8px;">Fila técnica (protótipo)</h2>
			<p class="sdp-muted" style="margin-bottom:12px;">
				Placeholder para testes. A fila real continua em
				<?= $this->Html->link('Service Desk', ['controller' => 'Servicedesk', 'action' => 'index']) ?>.
			</p>
			<a class="sdp-btn sdp-ghost sdp-sm" href="<?= h($this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'index'])) ?>">← Dashboard protótipo</a>
		</div>
	</div>
</div>
