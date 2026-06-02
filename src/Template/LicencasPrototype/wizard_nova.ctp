<?php
/**
 * Wizard 1/4 — Cliente & Produto (pg-lic-nova).
 *
 * @var array<int,array<string,mixed>> $licClientesBusca
 * @var array<int,array<string,mixed>> $licCategorias
 * @var array<int,array<string,mixed>> $licProdutosWizard
 * @var array<int,array<string,mixed>> $licFornecedores
 * @var int $wizardStepNum
 */
$csrf = (string)$this->request->getAttribute('csrfToken');
$clientes = (array)($licClientesBusca ?? []);
$categorias = (array)($licCategorias ?? []);
$produtos = (array)($licProdutosWizard ?? []);
$fornecedores = (array)($licFornecedores ?? []);
$catDefault = $categorias[0]['id'] ?? 0;
foreach ($categorias as $c) {
	if (mb_stripos((string)($c['codigo'] ?? ''), 'OFFICE') !== false || mb_stripos((string)($c['nome'] ?? ''), 'Office') !== false) {
		$catDefault = (int)$c['id'];
		break;
	}
}
?>
<?= $this->element('LicencasPrototype/wizard_header', ['wizardStepNum' => 1, 'wizardSteps' => $wizardSteps ?? [], 'licId' => 0]) ?>

<form id="lic-wizard-form" method="post" action="<?= h($this->Url->build(['action' => 'salvarWizard'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<input type="hidden" name="wizard_step" value="1">
<input type="hidden" name="idcliente" id="lic-idcliente" value="">
<input type="hidden" name="idcatalogo" id="lic-idcatalogo" value="">
<input type="hidden" name="produto_label" id="lic-produto-label" value="">

<div class="g2" style="gap:14px;align-items:start;">
	<div>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">🏢 <?= h(__('Empresa-cliente')) ?> *</div>
			<div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;"><?= h(__('Busque a empresa no cadastro de clientes do ERP. Se ela ainda não tem perfil no módulo de Licenças, será criada automaticamente.')) ?></div>
			<div class="field">
				<label><?= h(__('Buscar cliente do ERP')) ?></label>
				<input type="text" id="lic-busca-cliente" autocomplete="off" placeholder="<?= h(__('Nome, CNPJ ou nome fantasia…')) ?>" style="font-size:13px;">
			</div>
			<div id="lic-clientes-list" style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow-y:auto;"></div>
			<div id="lic-cliente-selecionado" style="display:none;margin-top:8px;"></div>
			<div style="margin-top:8px;font-size:11px;color:var(--text-muted);">
				<?= $this->Html->link('+ ' . __('Empresa não está na lista? Vincular ao cadastro do ERP'), ['action' => 'view', 'empresa-nova'], ['style' => 'color:var(--teal);']) ?>
			</div>
		</div>

		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">📚 <?= h(__('Categoria & Produto')) ?> *</div>
			<div class="field" style="margin-bottom:12px;">
				<label>1. <?= h(__('Escolha a categoria')) ?> · <?= $this->Html->link(__('gerenciar categorias →'), ['action' => 'view', 'categorias'], ['style' => 'color:var(--teal);font-size:11px;']) ?></label>
				<div id="lic-cat-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:6px;">
					<?php foreach ($categorias as $c) :
						$cid = (int)$c['id'];
						$sel = $cid === (int)$catDefault;
						?>
					<label class="lic-cat-pick" data-id="<?= $cid ?>" style="display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px;background:<?= $sel ? 'var(--teal-light)' : '#fff' ?>;border:1px solid <?= $sel ? 'var(--teal-mid)' : 'var(--border)' ?>;border-radius:6px;cursor:pointer;">
						<input type="radio" name="idcategoria_ui" value="<?= $cid ?>"<?= $sel ? ' checked' : '' ?> style="display:none;">
						<span style="font-size:20px;"><?= h($c['icon'] ?? '📦') ?></span>
						<span style="font-size:11px;<?= $sel ? 'font-weight:600;color:var(--teal-dark);' : '' ?>"><?= h($c['codigo'] ?: $c['nome']) ?></span>
						<span style="font-size:9px;color:var(--text-muted);"><?= h(__('{0} produtos', (int)($c['produtos'] ?? 0))) ?></span>
					</label>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="field">
				<label>2. <?= h(__('Escolha o produto')) ?> <span id="lic-prod-count" style="font-size:11px;color:var(--text-muted);"></span></label>
				<select id="lic-produto-select" name="idcatalogo_ui" style="font-size:13px;" required disabled>
					<option value=""><?= h(__('Selecione um cliente e uma categoria…')) ?></option>
				</select>
				<div style="font-size:10px;color:var(--text-muted);margin-top:4px;"><?= h(__('A lista muda automaticamente ao trocar de categoria')) ?> · <?= $this->Html->link('+ ' . __('Novo produto nesta categoria'), ['action' => 'view', 'produto-novo'], ['style' => 'color:var(--teal);']) ?></div>
			</div>
		</div>

		<div class="card">
			<div class="sec-title">🏭 <?= h(__('Fornecedor')) ?></div>
			<div class="field">
				<label><?= h(__('De onde vai comprar / quem revende')) ?> * <span id="lic-forn-hint" style="font-size:10px;color:var(--text-muted);"></span></label>
				<select id="lic-fornecedor-select" style="font-size:13px;">
					<option value=""><?= h(__('Selecione o produto primeiro…')) ?></option>
					<?php foreach ($fornecedores as $f) : ?>
					<option value="<?= (int)$f['id'] ?>"><?= h($f['nome'] ?? '') ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="g2" style="gap:10px;margin-top:10px;">
				<div class="field" style="margin:0;">
					<label><?= h(__('Tipo de aquisição')) ?></label>
					<select>
						<option><?= h(__('Nova compra')) ?></option>
						<option><?= h(__('Renovação manual')) ?></option>
						<option><?= h(__('Migração de outro fornecedor')) ?></option>
					</select>
				</div>
				<div class="field" style="margin:0;">
					<label><?= h(__('Contrato guarda-chuva (opcional)')) ?></label>
					<select disabled>
						<option><?= h(__('— Sem contrato vinculado —')) ?></option>
					</select>
				</div>
			</div>
		</div>
	</div>

	<div>
		<div class="card" style="position:sticky;top:14px;">
			<div class="sec-title">👁 <?= h(__('Pré-visualização')) ?></div>
			<div style="font-size:12px;line-height:1.7;">
				<div style="padding:10px;background:var(--bg-surface);border-radius:6px;margin-bottom:8px;">
					<div style="color:var(--text-muted);font-size:11px;"><?= h(__('Cliente')) ?></div>
					<strong id="lic-prev-cliente">—</strong>
				</div>
				<div style="padding:10px;background:var(--teal-light);border-radius:6px;margin-bottom:8px;">
					<div style="color:var(--teal-dark);font-size:11px;"><?= h(__('Produto')) ?></div>
					<strong id="lic-prev-produto">—</strong>
					<div id="lic-prev-produto-sub" style="font-size:11px;color:var(--text-muted);"></div>
				</div>
				<div style="padding:10px;background:var(--bg-surface);border-radius:6px;">
					<div style="color:var(--text-muted);font-size:11px;"><?= h(__('Fornecedor')) ?></div>
					<strong id="lic-prev-fornecedor">—</strong>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="card" style="margin-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
	<?= $this->Html->link('← ' . __('Cancelar'), ['action' => 'licencas'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<button type="submit" class="btn btn-primary btn-sm" id="lic-wizard-next"><?= h(__('Próximo: Quantidade & Datas')) ?> →</button>
</div>
</form>

<script>
(function () {
	var clientes = <?= json_encode($clientes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
	var produtos = <?= json_encode($produtos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
	var catDefault = <?= (int)$catDefault ?>;
	var selCliente = null;
	var selCat = catDefault;

	var $busca = document.getElementById('lic-busca-cliente');
	var $lista = document.getElementById('lic-clientes-list');
	var $selBox = document.getElementById('lic-cliente-selecionado');
	var $idCliente = document.getElementById('lic-idcliente');
	var $idCatalogo = document.getElementById('lic-idcatalogo');
	var $prodLabel = document.getElementById('lic-produto-label');
	var $prodSelect = document.getElementById('lic-produto-select');
	var $fornSelect = document.getElementById('lic-fornecedor-select');
	var $prevCli = document.getElementById('lic-prev-cliente');
	var $prevProd = document.getElementById('lic-prev-produto');
	var $prevProdSub = document.getElementById('lic-prev-produto-sub');
	var $prevForn = document.getElementById('lic-prev-fornecedor');
	var $prodCount = document.getElementById('lic-prod-count');
	var $fornHint = document.getElementById('lic-forn-hint');

	function renderClientes(q) {
		q = (q || '').toLowerCase();
		$lista.innerHTML = '';
		var shown = 0;
		clientes.forEach(function (c) {
			var hay = (c.nome + ' ' + c.cnpj).toLowerCase();
			if (q && hay.indexOf(q) === -1) return;
			if (shown >= 8) return;
			shown++;
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'btn btn-ghost btn-sm';
			btn.style.cssText = 'justify-content:flex-start;text-align:left;width:100%;padding:8px;';
			btn.innerHTML = '<strong>' + escapeHtml(c.nome) + '</strong><br><span style="font-size:10px;color:var(--text-muted);">' + escapeHtml(c.cnpj || '') + ' · ' + c.licencas_ativas + ' lic.</span>';
			btn.addEventListener('click', function () { selectCliente(c); });
			$lista.appendChild(btn);
		});
		if (!shown) {
			$lista.innerHTML = '<p style="font-size:12px;color:var(--text-muted);margin:4px 0;"><?= h(__('Nenhum cliente encontrado.')) ?></p>';
		}
	}

	function selectCliente(c) {
		selCliente = c;
		$idCliente.value = c.id;
		$prevCli.textContent = c.nome;
		$selBox.style.display = 'block';
		$selBox.innerHTML = '<div style="padding:10px;background:var(--teal-light);border:2px solid var(--teal-mid);border-radius:6px;display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">' +
			'<div style="display:flex;align-items:center;gap:8px;"><div style="width:32px;height:32px;border-radius:6px;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;">' + escapeHtml(c.iniciais) + '</div>' +
			'<div><strong style="font-size:12px;">' + escapeHtml(c.nome) + '</strong><div style="font-size:10px;color:var(--text-muted);">' + escapeHtml(c.cnpj || '') + ' · ' + c.licencas_ativas + ' <?= h(__('licenças ativas')) ?></div></div></div>' +
			'<span class="badge b-paga" style="font-size:9px;">✓ <?= h(__('selecionada')) ?></span></div>';
		$lista.innerHTML = '';
		$busca.value = c.nome;
		fillProdutos();
	}

	function fillProdutos() {
		var list = produtos.filter(function (p) { return parseInt(p.idcategoria, 10) === selCat; });
		$prodSelect.innerHTML = '';
		if (!selCliente) {
			$prodSelect.disabled = true;
			$prodSelect.innerHTML = '<option value=""><?= h(__('Selecione um cliente…')) ?></option>';
			return;
		}
		$prodSelect.disabled = false;
		if (!list.length) {
			$prodSelect.innerHTML = '<option value=""><?= h(__('Nenhum produto nesta categoria — use catálogo')) ?></option>';
		} else {
			list.forEach(function (p, i) {
				var opt = document.createElement('option');
				opt.value = p.id;
				opt.textContent = p.nome;
				opt.dataset.categoria = p.categoria || '';
				opt.dataset.fornecedor = p.idfornecedor_cliente || '';
				if (i === 0) opt.selected = true;
				$prodSelect.appendChild(opt);
			});
		}
		var catName = '';
		document.querySelectorAll('.lic-cat-pick').forEach(function (el) {
			if (parseInt(el.dataset.id, 10) === selCat) {
				catName = el.querySelector('span:nth-child(3)') ? el.querySelectorAll('span')[1].textContent : '';
			}
		});
		$prodCount.textContent = '· <?= h(__('mostrando')) ?> ' + list.length + ' <?= h(__('produtos da categoria')) ?> ' + catName;
		$fornHint.textContent = catName ? '· <?= h(__('filtrando fornecedores da categoria')) ?> ' + catName : '';
		onProdutoChange();
	}

	function onProdutoChange() {
		var opt = $prodSelect.options[$prodSelect.selectedIndex];
		if (!opt || !opt.value) {
			$idCatalogo.value = '';
			$prodLabel.value = '';
			$prevProd.textContent = '—';
			$prevProdSub.textContent = '';
			return;
		}
		$idCatalogo.value = opt.value;
		$prodLabel.value = opt.textContent;
		$prevProd.textContent = opt.textContent;
		$prevProdSub.textContent = (opt.dataset.categoria || '') + ' · <?= h(__('assinatura por usuário')) ?>';
		var fid = opt.dataset.fornecedor || '';
		if (fid) {
			$fornSelect.value = fid;
		}
		$prevForn.textContent = $fornSelect.options[$fornSelect.selectedIndex] ? $fornSelect.options[$fornSelect.selectedIndex].textContent : '—';
	}

	function escapeHtml(s) {
		return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}

	$busca.addEventListener('input', function () { renderClientes($busca.value); });
	document.querySelectorAll('.lic-cat-pick').forEach(function (el) {
		el.addEventListener('click', function () {
			selCat = parseInt(el.dataset.id, 10);
			document.querySelectorAll('.lic-cat-pick').forEach(function (x) {
				x.style.background = '#fff';
				x.style.borderColor = 'var(--border)';
			});
			el.style.background = 'var(--teal-light)';
			el.style.borderColor = 'var(--teal-mid)';
			fillProdutos();
		});
	});
	$prodSelect.addEventListener('change', onProdutoChange);
	$fornSelect.addEventListener('change', function () {
		$prevForn.textContent = $fornSelect.options[$fornSelect.selectedIndex] ? $fornSelect.options[$fornSelect.selectedIndex].textContent : '—';
	});
	document.getElementById('lic-wizard-form').addEventListener('submit', function (e) {
		if (!$idCliente.value) {
			e.preventDefault();
			alert('<?= h(__('Selecione a empresa-cliente.')) ?>');
			return;
		}
		if (!$idCatalogo.value && !$prodLabel.value) {
			e.preventDefault();
			alert('<?= h(__('Selecione o produto do catálogo.')) ?>');
			return;
		}
	});
	renderClientes('');
	fillProdutos();
})();
</script>
