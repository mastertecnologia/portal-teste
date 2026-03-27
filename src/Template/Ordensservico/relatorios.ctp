<?php
/**
 * Hub de relatórios — OrdensservicoController::relatorios.
 */
$this->Html->css('/dist/css/pages/ordensservico-index-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Relatórios', [], ['class' => 'breadcrumb-item active']);
$this->assign('title', 'Relatórios — Ordens de Serviço');

$qRel = [];
foreach (['cliente', 'situacao', 'problema', 'locacao'] as $k) {
	$v = $$k;
	if ($v === null || $v === '') {
		continue;
	}
	if ($k === 'locacao' && ((string)$v === '-1' || (int)$v === -1)) {
		continue;
	}
	$qRel[$k] = $v;
}
$optsModelo = [];
foreach ($modelosRelatorio as $_m) {
	if (!empty($_m['id'])) {
		$optsModelo[$_m['id']] = $_m['titulo'] ?? $_m['id'];
	}
}
?>
<style>
@media print {
	.no-print, .left-sidebar, .pgm-sidebar-footer, .pgm-sidebar-brand { display: none !important; }
}
</style>

<div class="col-md-12 p-0">
<div class="os-index-shell">
	<header class="os-page-head no-print">
		<div class="os-rel-headline">
			<h1 class="os-page-title">Relatórios — Ordens de Serviço</h1>
			<p class="os-page-sub">Visualização na tela, PDF para download ou envio por e-mail (anexo PDF).</p>
		</div>
		<div class="os-page-head-actions">
			<?= $this->Html->link(
				'<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 4L6 8l4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Voltar à lista</span>',
				['action' => 'index'],
				['class' => 'os-page-head-link', 'escape' => false]
			) ?>
		</div>
	</header>

	<div class="os-rel-inner">
		<div class="os-rel-map no-print">
			<strong>Mapeamento do módulo:</strong>
			Lista principal (index), cadastro (edit/view), nova OS (add), impressão de uma OS (imprimir), impressão em lote (imprimirordens).
			Esta página concentra relatórios com filtros alinhados ao index.
		</div>

		<div class="os-rel-panel os-rel-filtros no-print">
			<h2>Filtros do relatório</h2>
			<p class="os-rel-help">Mesmos critérios da lista de OS. No modelo <strong>Resumo por situação</strong>, o agrupamento é sempre por situação; cliente, problema e tipo limitam quais ordens entram nos totais.</p>
			<?= $this->Form->create(null, ['type' => 'get', 'class' => 'form-material', 'url' => ['action' => 'relatorios']]); ?>
			<div class="row">
				<div class="col-lg-3 col-md-6 col-12">
					<p>Situação</p>
					<?= $this->Form->control('situacao', ['data-live-search' => true, 'title' => 'Todas', 'value' => $situacao, 'id' => 'rel-situacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensSituacao, 'label' => false, 'empty' => false]) ?>
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<p>Problema</p>
					<?= $this->Form->control('problema', ['data-live-search' => true, 'title' => 'Todos', 'value' => $problema, 'id' => 'rel-problema', 'class' => 'form-control selectpicker', 'options' => $problemas, 'label' => false, 'empty' => false]) ?>
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<p>Cliente</p>
					<?= $this->Form->control('cliente', ['data-live-search' => true, 'title' => 'Todos', 'value' => $cliente, 'class' => 'form-control selectpicker', 'id' => 'rel-cliente', 'options' => $clientes, 'label' => false, 'empty' => false]) ?>
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<p>Tipo</p>
					<?= $this->Form->control('locacao', ['data-live-search' => true, 'title' => 'Todos', 'value' => $locacao, 'id' => 'rel-locacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensLocacao, 'label' => false, 'empty' => false]) ?>
				</div>
			</div>
			<div class="m-t-15">
				<?= $this->Form->button('Aplicar filtros', ['class' => 'btn btn-primary']) ?>
			</div>
			<?= $this->Form->end(); ?>
		</div>

		<?php if (empty($modelosRelatorio)) : ?>
		<div class="os-rel-panel no-print">
			<p class="os-rel-help m-b-0">Nenhum modelo de relatório configurado. Verifique o arquivo <code>config/ordens_servico_relatorios.php</code>.</p>
		</div>
		<?php else : ?>
		<div class="os-rel-grid no-print">
			<?php foreach ($modelosRelatorio as $m) :
				$urlVer = ['action' => 'relatorioVer', $m['id'], '?' => $qRel];
				$urlPdf = ['action' => 'relatorioPdf', $m['id'], '?' => $qRel];
				?>
			<div class="os-rel-card">
				<h3><?= h($m['titulo']) ?></h3>
				<p><?= h($m['descricao']) ?></p>
				<div class="os-rel-actions">
					<?= $this->Html->link('Visualizar', $urlVer, ['class' => 'btn btn-primary', 'target' => '_blank', 'rel' => 'noopener']) ?>
					<?= $this->Html->link('PDF', $urlPdf, ['class' => 'btn btn-success']) ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<div class="os-rel-panel os-rel-email no-print">
			<h2>Enviar por e-mail</h2>
			<p class="os-rel-help">Gera o PDF do modelo escolhido e envia como anexo. Usa o perfil <code>Email.default</code> em <code>config/app.php</code>.</p>
			<?= $this->Form->create(null, ['url' => ['action' => 'relatorioEnviarEmail']]); ?>
			<div class="row">
				<div class="col-md-4">
					<?php if ($optsModelo !== []) : ?>
						<?= $this->Form->control('modelo', [
							'type' => 'select',
							'options' => $optsModelo,
							'label' => 'Modelo',
							'class' => 'form-control',
							'required' => true,
						]) ?>
					<?php else : ?>
						<p class="os-rel-warn">Nenhum modelo disponível para envio.</p>
					<?php endif; ?>
				</div>
				<div class="col-md-5">
					<?= $this->Form->control('email_destino', [
						'type' => 'email',
						'label' => 'E-mail destino',
						'class' => 'form-control',
						'required' => true,
						'placeholder' => 'destinatario@empresa.com',
					]) ?>
				</div>
				<div class="col-md-12 m-t-10">
					<?= $this->Form->control('mensagem', [
						'type' => 'textarea',
						'label' => 'Mensagem (opcional)',
						'class' => 'form-control',
						'rows' => 2,
					]) ?>
				</div>
			</div>
			<?= $this->Form->control('cliente', ['type' => 'hidden', 'value' => $cliente ?? '']); ?>
			<?= $this->Form->control('situacao', ['type' => 'hidden', 'value' => $situacao ?? '']); ?>
			<?= $this->Form->control('problema', ['type' => 'hidden', 'value' => $problema ?? '']); ?>
			<?= $this->Form->control('locacao', ['type' => 'hidden', 'value' => $locacao !== null && $locacao !== '' ? $locacao : '-1']); ?>
			<div class="m-t-15">
				<?= $this->Form->button('Enviar relatório por e-mail', ['class' => 'btn btn-primary', 'disabled' => $optsModelo === []]) ?>
			</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
</div>

<script>
$(function () {
	if ($.fn.selectpicker) {
		$('.os-rel-filtros select.selectpicker').selectpicker({ liveSearch: true, style: '', size: 8, container: 'body' });
	}
});
</script>
