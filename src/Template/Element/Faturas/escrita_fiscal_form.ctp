<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FaturasEscritaFiscal $escritaFiscal
 * @var \App\Model\Entity\Faturas $fatura
 */
$view = $this;

$feMoney = function ($field, $label) use ($view) {
	echo '<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12 m-b-10">';
	echo '<label class="control-label text-muted" style="font-size:11px;">' . h($label) . '</label>';
	echo $view->Form->control($field, [
		'class'      => 'form-control mascaramonetaria',
		'label'      => false,
		'onkeypress' => 'return SomenteNumero(event, "#' . h($field) . '")',
		'id'         => $field,
	]);
	echo '</div>';
};
$fePct = function ($field, $label) use ($view) {
	echo '<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12 m-b-10">';
	echo '<label class="control-label text-muted" style="font-size:11px;">' . h($label) . '</label>';
	echo $view->Form->control($field, [
		'class' => 'form-control',
		'label' => false,
		'id'    => $field,
	]);
	echo '</div>';
};

$section = function ($title, $icon = '') {
	echo '<div class="erp-ef-section">';
	echo '<div class="erp-ef-section-title">' . ($icon ? '<span style="margin-right:5px;">' . $icon . '</span>' : '') . h($title) . '</div>';
	echo '<div class="erp-ef-section-body"><div class="row">';
};
$sectionEnd = function () {
	echo '</div></div></div>';
};
?>

<style>
.erp-ef-section {
    border: 1px solid #e8ecef;
    border-radius: 4px;
    margin-bottom: 12px;
    overflow: hidden;
}
.erp-ef-section-title {
    background: #f5f7f9;
    border-bottom: 1px solid #e8ecef;
    padding: 6px 14px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #455a64;
}
.erp-ef-section-body {
    padding: 12px 14px 4px;
}
</style>

<?= $this->Form->create($escritaFiscal, ['url' => ['action' => 'edit', $fatura->id, 3], 'class' => 'form-material', 'id' => 'form-escrita-fiscal']) ?>
<input type="hidden" name="salvar_escrita_fiscal" value="1"/>
<?= $this->Form->hidden('idfatura') ?>
<?= $this->Form->hidden('idempresa') ?>
<?php if (!empty($escritaFiscal->id)) : ?>
	<?= $this->Form->hidden('id') ?>
<?php endif; ?>

<p class="text-muted small m-b-15">
	Os tributos aproximados (Lei 12.741) podem ser gravados automaticamente ao abrir
	<strong>Imprimir</strong> na aba Contrato. Demais campos servem para lançamento manual ou integração futura (ICMS, ISS, retenções, etc.).
</p>

<?php $section('Totais da nota'); ?>
	<?php $feMoney('valor_produtos',    'Valor produtos'); ?>
	<?php $feMoney('valor_total_nota',  'Valor total nota'); ?>
	<?php $feMoney('valor_servicos',    'Valor serviços'); ?>
	<?php $feMoney('valor_desconto',    'Desconto'); ?>
	<?php $feMoney('valor_frete',       'Frete / outros'); ?>
	<?php $feMoney('valor_seguro',      'Seguro'); ?>
<?php $sectionEnd(); ?>

<?php $section('ICMS'); ?>
	<?php $feMoney('icms_bc',            'BC ICMS'); ?>
	<?php $feMoney('icms_valor',         'Valor ICMS'); ?>
	<?php $fePct('icms_cred_simples_pct','% Créd. Simples Nac.'); ?>
	<?php $feMoney('icms_st_bc',         'BC ICMS ST'); ?>
	<?php $feMoney('icms_st_valor',      'Valor ICMS ST'); ?>
<?php $sectionEnd(); ?>

<?php $section('Tributos aproximados (IBPT / transparência)'); ?>
	<?php $feMoney('valor_trib_aprox_total',     'Valor trib. aprox. total'); ?>
	<?php $feMoney('valor_trib_aprox_federal',   'Federal'); ?>
	<?php $feMoney('valor_trib_aprox_estadual',  'Estadual'); ?>
	<?php $feMoney('valor_trib_aprox_municipal', 'Municipal'); ?>
<?php $sectionEnd(); ?>

<?php $section('IPI, PIS e COFINS'); ?>
	<?php $feMoney('ipi_bc',      'BC IPI'); ?>
	<?php $feMoney('ipi_valor',   'Valor IPI'); ?>
	<?php $feMoney('pis_bc',      'BC PIS'); ?>
	<?php $feMoney('pis_valor',   'Valor PIS'); ?>
	<?php $feMoney('cofins_bc',   'BC COFINS'); ?>
	<?php $feMoney('cofins_valor','Valor COFINS'); ?>
<?php $sectionEnd(); ?>

<?php $section('Serviços / previdência'); ?>
	<?php $feMoney('iss_bc',    'BC ISS'); ?>
	<?php $feMoney('iss_valor', 'Valor ISS'); ?>
	<?php $feMoney('inss_bc',   'BC INSS'); ?>
	<?php $feMoney('inss_valor','Valor INSS'); ?>
<?php $sectionEnd(); ?>

<?php $section('IRRF e CSLL'); ?>
	<?php $feMoney('irrf_bc',     'BC IRRF'); ?>
	<?php $fePct('irrf_aliquota', 'Alíquota IRRF %'); ?>
	<?php $feMoney('irrf_valor',  'Valor IRRF'); ?>
	<?php $feMoney('csll_bc',     'BC CSLL'); ?>
	<?php $feMoney('csll_valor',  'Valor CSLL'); ?>
<?php $sectionEnd(); ?>

<?php $section('Importação / IOF'); ?>
	<?php $feMoney('ii_valor',  'Valor II'); ?>
	<?php $feMoney('iof_valor', 'Valor IOF'); ?>
<?php $sectionEnd(); ?>

<?php $section('Rastreabilidade'); ?>
	<div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 m-b-10">
		<label class="control-label text-muted" style="font-size:11px;">Fonte do cálculo</label>
		<?= $this->Form->control('fonte_calculo', ['class' => 'form-control', 'label' => false, 'placeholder' => 'ex.: IBPT_LEI12741, MANUAL, ERP_DB9']) ?>
	</div>
	<div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 m-b-10">
		<label class="control-label text-muted" style="font-size:11px;">Versão IBPT (se aplicável)</label>
		<?= $this->Form->control('fonte_ibpt_versao', ['class' => 'form-control', 'label' => false]) ?>
	</div>
<?php $sectionEnd(); ?>

<div class="row m-t-10">
	<div class="col-12">
		<?= $this->Form->button('Salvar escrita fiscal', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
		<?= $this->Html->link('Abrir impressão (atualiza IBPT)', ['action' => 'imprimir', $fatura->id], ['target' => '_blank', 'class' => 'btn btn-pgm btn-pgm-imprimir btn-orange m-l-5']) ?>
	</div>
</div>
<?= $this->Form->end() ?>
