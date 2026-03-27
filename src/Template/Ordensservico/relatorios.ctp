<?php
/**
 * Hub de relatórios — OrdensservicoController::relatorios.
 * Ações: ver (HTML), PDF, enviar por e-mail (POST relatorioEnviarEmail).
 */
$this->Breadcrumbs->add('Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Relatórios', [], ['class' => 'breadcrumb-item active']);
$this->assign('title', 'Relatórios — Ordens de Serviço');
?>
<style>
.os-rel-root { max-width: 1100px; margin: 0 auto; padding: 16px 8px 32px; }
.os-rel-head h1 { font-size: 1.35rem; font-weight: 700; margin: 0 0 4px; }
.os-rel-head p { color: #6e7781; font-size: 0.85rem; margin: 0 0 16px; }
.os-rel-map { background: #f6f8fa; border: 1px solid #d0d7de; border-radius: 8px; padding: 12px 14px; font-size: 0.8rem; color: #57606a; margin-bottom: 20px; }
.os-rel-map strong { color: #24292f; }
.os-rel-grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); margin-bottom: 24px; }
.os-rel-card { border: 1px solid #d0d7de; border-radius: 10px; padding: 14px; background: #fff; }
.os-rel-card h3 { font-size: 0.95rem; margin: 0 0 6px; }
.os-rel-card p { font-size: 0.8rem; color: #57606a; margin: 0 0 12px; line-height: 1.4; }
.os-rel-card .os-rel-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.os-rel-card .btn { font-size: 0.78rem; padding: 6px 10px; border-radius: 6px; }
.os-rel-filtros { border: 1px solid #d0d7de; border-radius: 10px; padding: 16px; background: #fff; margin-bottom: 20px; }
.os-rel-filtros h2 { font-size: 1rem; margin: 0 0 12px; }
.os-rel-email { border: 1px solid #d0d7de; border-radius: 10px; padding: 16px; background: #fff; }
.os-rel-email h2 { font-size: 1rem; margin: 0 0 12px; }
@media print { .no-print, .left-sidebar, .pgm-sidebar-footer { display: none !important; } }
</style>

<div class="col-md-12 p-0">
<div class="os-rel-root">
	<div class="os-rel-head">
		<h1>Relatórios — Ordens de Serviço</h1>
		<p>Gerar visualização na tela, PDF para download ou envio por e-mail (anexo PDF).</p>
	</div>

	<div class="os-rel-map no-print">
		<strong>Mapeamento do módulo:</strong>
		Lista principal (index), cadastro (edit/view), nova OS (add), impressão de uma OS (imprimir), impressão em lote (imprimirordens).
		Esta página concentra relatórios tabulares/resumo com filtros alinhados ao index.
	</div>

	<div class="os-rel-filtros no-print">
		<h2>Filtros do relatório</h2>
		<p class="text-muted small">Os mesmos critérios da lista de OS (situação, problema, cliente, tipo). Em “Resumo por situação”, o agrupamento é sempre por situação; os demais filtros restringem quais ordens entram no total.</p>
		<?= $this->Form->create(null, ['type' => 'get', 'class' => 'form-material', 'url' => ['action' => 'relatorios']]); ?>
		<div class="row">
			<div class="col-lg-3 col-md-6 col-12">
				<p>Situação</p>
				<?= $this->Form->control('situacao', ['data-live-search' => true, 'title' => 'Todas', 'value' => $situacao, 'id' => 'rel-situacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensSituacao, 'label' => false]) ?>
			</div>
			<div class="col-lg-3 col-md-6 col-12">
				<p>Problema</p>
				<?= $this->Form->control('problema', ['data-live-search' => true, 'title' => 'Todos', 'value' => $problema, 'id' => 'rel-problema', 'class' => 'form-control selectpicker', 'options' => $problemas, 'label' => false]) ?>
			</div>
			<div class="col-lg-3 col-md-6 col-12">
				<p>Cliente</p>
				<?= $this->Form->control('cliente', ['data-live-search' => true, 'title' => 'Todos', 'value' => $cliente, 'class' => 'form-control selectpicker', 'id' => 'rel-cliente', 'options' => $clientes, 'label' => false]) ?>
			</div>
			<div class="col-lg-3 col-md-6 col-12">
				<p>Tipo</p>
				<?= $this->Form->control('locacao', ['data-live-search' => true, 'title' => 'Todos', 'value' => $locacao, 'id' => 'rel-locacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensLocacao, 'label' => false]) ?>
			</div>
		</div>
		<div class="m-t-15">
			<?= $this->Form->button('Aplicar filtros', ['class' => 'btn btn-primary']) ?>
			<?= $this->Html->link('Voltar à lista de OS', ['action' => 'index'], ['class' => 'btn btn-default m-l-5']) ?>
		</div>
		<?= $this->Form->end(); ?>
	</div>

	<div class="os-rel-grid no-print">
		<?php foreach ($modelosRelatorio as $m) :
			$qBase = ['cliente' => $cliente, 'situacao' => $situacao, 'problema' => $problema, 'locacao' => $locacao];
			$urlVer = ['action' => 'relatorioVer', 'modelo' => $m['id'], '?' => $qBase];
			$urlPdf = ['action' => 'relatorioPdf', 'modelo' => $m['id'], '?' => $qBase];
			?>
		<div class="os-rel-card">
			<h3><?= h($m['titulo']) ?></h3>
			<p><?= h($m['descricao']) ?></p>
			<div class="os-rel-actions">
				<?= $this->Html->link('Visualizar', $urlVer, ['class' => 'btn btn-primary', 'target' => '_blank']) ?>
				<?= $this->Html->link('PDF', $urlPdf, ['class' => 'btn btn-success']) ?>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<div class="os-rel-email no-print">
		<h2>Enviar por e-mail</h2>
		<p class="text-muted small">Gera o PDF do modelo escolhido e envia como anexo. Utiliza a configuração de e-mail do CakePHP (<code>Email</code> / SMTP conforme <code>app.php</code>).</p>
		<?= $this->Form->create(null, ['url' => ['action' => 'relatorioEnviarEmail']]); ?>
		<div class="row">
			<div class="col-md-4">
				<?php
				$optsModelo = [];
				foreach ($modelosRelatorio as $_m) {
					$optsModelo[$_m['id']] = $_m['titulo'];
				}
				echo $this->Form->control('modelo', [
					'type' => 'select',
					'options' => $optsModelo,
					'label' => 'Modelo',
					'class' => 'form-control',
					'required' => true,
				]);
				?>
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
		<?= $this->Form->control('cliente', ['type' => 'hidden', 'value' => $cliente]); ?>
		<?= $this->Form->control('situacao', ['type' => 'hidden', 'value' => $situacao]); ?>
		<?= $this->Form->control('problema', ['type' => 'hidden', 'value' => $problema]); ?>
		<?= $this->Form->control('locacao', ['type' => 'hidden', 'value' => $locacao]); ?>
		<div class="m-t-15">
			<?= $this->Form->button('Enviar relatório por e-mail', ['class' => 'btn btn-primary']) ?>
		</div>
		<?= $this->Form->end(); ?>
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
