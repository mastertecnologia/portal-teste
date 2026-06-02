<?php
/**
 * Papel no cadastro mestre — checkboxes (cliente / fornecedor).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cliente $cliente
 * @var bool $cliPapelColumns
 * @var bool $showFornecedorExtras
 */
use App\Utility\ClientesPapelCadastro;

if (empty($cliPapelColumns)) {
	return;
}
$ehCliente = !empty($cliente->eh_cliente);
$ehFornecedor = !empty($cliente->eh_fornecedor);
$stHom = (string)($cliente->fornecedor_status_homologacao ?? ClientesPapelCadastro::STATUS_CADASTRADO);
$showExtras = !empty($showFornecedorExtras) || $ehFornecedor;
$categorias = [
	'hardware' => __('Hardware / Eletrônicos'),
	'software' => __('Software / Licenças'),
	'insumos' => __('Matéria-prima / Insumos'),
	'embalagens' => __('Embalagens'),
	'componentes' => __('Componentes industriais'),
	'servicos' => __('Serviços terceirizados'),
	'utilities' => __('Utilities (energia, água, internet)'),
	'logistica' => __('Transporte / Logística'),
	'manutencao' => __('Manutenção'),
	'aluguel' => __('Aluguel / Locação'),
];
?>
<div class="card cli-papel-cadastro" id="cli-papel-cadastro" style="margin-bottom:14px;">
	<div class="sec-title">🏷 <?= h(__('Papel no cadastro')) ?></div>
	<p style="font-size:12px;color:var(--text-muted);margin:0 0 12px;"><?= h(__('Marque uma ou mais opções. O mesmo cadastro pode ser cliente e fornecedor.')) ?></p>
	<div class="cli-papel-check-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:12px;">
		<label class="cli-papel-check" style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius,8px);background:var(--bg-surface,#fff);margin:0;">
			<input type="checkbox" name="eh_cliente" value="1" id="eh-cliente"<?= $ehCliente ? ' checked' : '' ?> style="width:16px;height:16px;margin-top:2px;accent-color:var(--teal);flex-shrink:0;">
			<span>
				<strong style="font-size:13px;display:block;"><?= h(__('Cliente')) ?></strong>
				<span style="font-size:11px;color:var(--text-muted);"><?= h(__('Vendas, CRM, orçamentos, faturamento')) ?></span>
			</span>
		</label>
		<label class="cli-papel-check" style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius,8px);background:var(--bg-surface,#fff);margin:0;">
			<input type="checkbox" name="eh_fornecedor" value="1" id="eh-fornecedor"<?= $ehFornecedor ? ' checked' : '' ?> style="width:16px;height:16px;margin-top:2px;accent-color:var(--teal);flex-shrink:0;">
			<span>
				<strong style="font-size:13px;display:block;"><?= h(__('Fornecedor')) ?></strong>
				<span style="font-size:11px;color:var(--text-muted);"><?= h(__('Compras, contas a pagar, NF de entrada')) ?></span>
			</span>
		</label>
	</div>
	<div id="cli-fornecedor-extras" style="<?= $showExtras ? '' : 'display:none;' ?>">
		<div class="g2" style="margin-top:8px;">
			<div class="field">
				<label><?= h(__('Categoria de fornecimento')) ?></label>
				<select name="fornecedor_categoria" class="form-control">
					<option value=""><?= h(__('Selecione…')) ?></option>
					<?php foreach ($categorias as $slug => $label) : ?>
					<option value="<?= h($slug) ?>"<?= (string)($cliente->fornecedor_categoria ?? '') === $slug ? ' selected' : '' ?>><?= h($label) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field">
				<label><?= h(__('Status de homologação')) ?></label>
				<div style="display:flex;flex-direction:column;gap:6px;margin-top:4px;">
					<label style="display:flex;align-items:center;gap:8px;font-size:12px;cursor:pointer;text-transform:none;letter-spacing:0;">
						<input type="radio" name="fornecedor_status_homologacao" value="<?= h(ClientesPapelCadastro::STATUS_CADASTRADO) ?>"<?= $stHom === ClientesPapelCadastro::STATUS_CADASTRADO ? ' checked' : '' ?>>
						<?= h(__('Cadastrado · aguarda homologação')) ?>
					</label>
					<label style="display:flex;align-items:center;gap:8px;font-size:12px;cursor:pointer;text-transform:none;letter-spacing:0;">
						<input type="radio" name="fornecedor_status_homologacao" value="<?= h(ClientesPapelCadastro::STATUS_ANALISE) ?>"<?= $stHom === ClientesPapelCadastro::STATUS_ANALISE ? ' checked' : '' ?>>
						<?= h(__('Em análise')) ?>
					</label>
					<label style="display:flex;align-items:center;gap:8px;font-size:12px;cursor:pointer;text-transform:none;letter-spacing:0;">
						<input type="radio" name="fornecedor_status_homologacao" value="<?= h(ClientesPapelCadastro::STATUS_HOMOLOGADO) ?>"<?= $stHom === ClientesPapelCadastro::STATUS_HOMOLOGADO ? ' checked' : '' ?>>
						<?= h(__('Homologado')) ?>
					</label>
				</div>
			</div>
		</div>
		<div class="field" style="max-width:220px;margin-top:10px;">
			<label><?= h(__('Lead time médio (dias úteis)')) ?></label>
			<input type="number" name="fornecedor_lead_time_dias" class="form-control" min="0" max="999" value="<?= (int)($cliente->fornecedor_lead_time_dias ?? 0) ?: '' ?>" placeholder="7">
		</div>
	</div>
</div>
<script>
(function () {
	var $forn = document.getElementById('eh-fornecedor');
	var $box = document.getElementById('cli-fornecedor-extras');
	if (!$forn || !$box) return;
	function sync() { $box.style.display = $forn.checked ? '' : 'none'; }
	$forn.addEventListener('change', sync);
	sync();
})();
</script>
