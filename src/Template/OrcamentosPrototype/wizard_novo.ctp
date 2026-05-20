<?php
/**
 * Wizard · 1/5 Novo orçamento — mockup pg-novo.
 *
 * @var \App\View\AppView $this
 * @var array<int,array{label:string,state:string}> $wizardSteps
 * @var array<int,array<string,mixed>> $orcCatalogo
 * @var array<int,string> $orcClientesOptions
 */
$H = $this->ErpPrototype;
$catalogo = (array)($orcCatalogo ?? []);
$clientes = (array)($orcClientesOptions ?? []);
$tipoLbls = ['prod' => __('Produto'), 'serv' => __('Serviço'), 'lic' => __('Licença'), 'loc' => __('Locação')];
$csrf = (string)$this->request->getAttribute('csrfToken');
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Comercial · Novo orçamento')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📝 <?= h(__('Cabeçalho e cliente')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Cancelar'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<form method="post" action="<?= h($this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'salvarRascunho'])) ?>" id="orc-wizard-form" style="margin:0;">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">

<div class="card">
	<div class="sec-title"><?= h(__('Dados do cliente')) ?></div>
	<div class="g2">
		<div class="field" style="position:relative;">
			<label><?= h(__('Cliente')) ?> *</label>
			<input type="text" id="cliBuscaInput" placeholder="🔍 <?= h(__('Buscar por nome, CNPJ, fantasia... (mín 2 letras)')) ?>" autocomplete="off">
			<input type="hidden" name="idcliente" id="cliIdHidden" required>
			<div id="cliBuscaSel" style="margin-top:6px;font-size:11px;color:var(--text-muted);"></div>
			<div id="cliBuscaDrop" style="display:none;position:absolute;top:62px;left:0;right:0;background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:0 6px 24px rgba(0,0,0,.12);z-index:10;max-height:260px;overflow-y:auto;"></div>
		</div>
		<div class="field">
			<label><?= h(__('Vendedor')) ?></label>
			<input type="text" value="<?= h(trim((string)$this->getRequest()->getSession()->read('Auth.User.name'))) ?>" disabled>
		</div>
		<div class="field">
			<label><?= h(__('Centro de custo')) ?></label>
			<select disabled><option><?= h(__('Comercial')) ?></option></select>
		</div>
		<div class="field">
			<label><?= h(__('Validade (dias)')) ?></label>
			<input type="number" name="validade_dias" value="30" min="1" max="180">
		</div>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Catálogo · escolha itens iniciais')) ?></div>
	<div style="margin-bottom:12px;">
		<input type="search" id="prodBuscaAjax" placeholder="🔍 <?= h(__('Buscar produto/serviço (AJAX, sem reload)...')) ?>" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;">
	</div>
	<div id="catalogoSpinner" style="display:none;text-align:center;padding:12px;color:var(--text-muted);font-size:11px;">⏳ <?= h(__('Buscando...')) ?></div>
	<div id="catalogoLista">
	<?php if ($catalogo === []) : ?>
		<p style="color:var(--text-muted);margin:0;font-size:12px;"><?= h(__('Nenhum produto ativo encontrado. Cadastre via módulo Produtos.')) ?></p>
	<?php else : ?>
		<div style="max-height:340px;overflow-y:auto;border:1px solid var(--border-light);border-radius:var(--radius);">
			<?php foreach ($catalogo as $p) :
				$est = (float)$p['estoque'];
				$estColor = $est <= 0 ? '#7A1822' : ($est < 5 ? '#8A4D02' : 'var(--teal-dark)');
				$tipoLbl = (string)($tipoLbls[$p['tipo']] ?? ucfirst((string)$p['tipo']));
			?>
				<div style="padding:10px 14px;display:flex;justify-content:space-between;align-items:center;gap:12px;border-bottom:1px solid var(--border-light);">
					<div style="flex:1;min-width:0;">
						<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
							<span style="font-family:monospace;font-size:11px;font-weight:600;color:var(--teal-dark);background:var(--teal-light);padding:2px 6px;border-radius:4px;"><?= h((string)$p['codigo']) ?></span>
							<strong style="font-size:12px;"><?= h(\Cake\Utility\Text::truncate((string)$p['descricao'], 64, ['ellipsis' => '…'])) ?></strong>
							<span class="badge b-<?= h((string)$p['tipo'] ?: 'arq') ?>" style="font-size:9px;"><?= h($tipoLbl) ?></span>
						</div>
						<div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
							<?= h(__('Unidade')) ?>: <?= h((string)$p['unidade'] ?: '—') ?>
							· <?= h(__('Estoque')) ?>: <span style="color:<?= h($estColor) ?>;font-weight:600;"><?= number_format($est, 2, ',', '.') ?></span>
						</div>
					</div>
					<div style="text-align:right;min-width:110px;">
						<div style="font-size:14px;font-weight:700;color:var(--teal-dark);"><?= h($H->brl((float)$p['preco'])) ?></div>
						<button type="button" class="btn btn-ghost btn-xs" disabled><?= h(__('+ Adicionar')) ?></button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div style="font-size:11px;color:var(--text-muted);margin-top:8px;" id="catalogoStatus">
			<?= sprintf(h(__('Mostrando %d produtos · digite acima para buscar mais')), count($catalogo)) ?>
		</div>
	<?php endif; ?>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Observações iniciais')) ?></div>
	<div class="field">
		<textarea rows="3" name="solicitacao" placeholder="<?= h(__('Ex.: condições comerciais, prazo de entrega, garantia...')) ?>"></textarea>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar à lista'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<button type="submit" class="btn btn-primary btn-sm">💾 <?= h(__('Salvar rascunho e abrir detalhe')) ?></button>
</div>
</form>

<div class="alert-box alert-blue" style="margin-top:14px;">
	<?= h(__('Ao salvar o rascunho, o orçamento é criado em status Pendente. Você é redirecionado para a tela de detalhe para adicionar itens via fluxo clássico.')) ?>
</div>

<?php $this->start('script'); ?>
<script>
(function () {
	var debounce = function (fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); }; };

	// Busca cliente AJAX
	var cliInput = document.getElementById('cliBuscaInput');
	var cliId = document.getElementById('cliIdHidden');
	var cliSel = document.getElementById('cliBuscaSel');
	var cliDrop = document.getElementById('cliBuscaDrop');
	var urlCli = <?= json_encode($this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'apiClientes'])) ?>;

	function pickCliente(id, nome, cnpj) {
		cliId.value = id;
		cliInput.value = nome;
		cliSel.textContent = '✓ ' + nome + (cnpj ? ' (' + cnpj + ')' : '');
		cliSel.style.color = '#0F6E56';
		cliDrop.style.display = 'none';
	}

	cliInput && cliInput.addEventListener('input', debounce(function () {
		var q = this.value.trim();
		if (q.length < 2) { cliDrop.style.display = 'none'; return; }
		fetch(urlCli + '?q=' + encodeURIComponent(q), {credentials: 'same-origin'})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (!data.ok) return;
				if (data.items.length === 0) {
					cliDrop.innerHTML = '<div style="padding:10px;color:#6b6a65;font-size:11px;">Sem clientes para "' + q + '"</div>';
				} else {
					cliDrop.innerHTML = data.items.map(function (c) {
						return '<div data-id="' + c.id + '" data-nome="' + c.nome.replace(/"/g, '&quot;') + '" data-cnpj="' + c.cnpj + '" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid #f0efec;font-size:12px;" onmouseover="this.style.background=\'#f9f9f8\'" onmouseout="this.style.background=\'#fff\'"><strong>' + c.nome + '</strong>' + (c.cnpj ? ' <span style="color:#6b6a65;font-family:monospace;font-size:10px;">' + c.cnpj + '</span>' : '') + '</div>';
					}).join('');
					cliDrop.querySelectorAll('[data-id]').forEach(function (el) {
						el.addEventListener('click', function () { pickCliente(this.dataset.id, this.dataset.nome, this.dataset.cnpj); });
					});
				}
				cliDrop.style.display = 'block';
			});
	}, 250));

	document.addEventListener('click', function (e) {
		if (cliDrop && !cliDrop.contains(e.target) && e.target !== cliInput) cliDrop.style.display = 'none';
	});

	// Busca catálogo AJAX
	var prodInput = document.getElementById('prodBuscaAjax');
	var catLista = document.getElementById('catalogoLista');
	var catSpin = document.getElementById('catalogoSpinner');
	var urlProd = <?= json_encode($this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'apiProdutos'])) ?>;

	function renderCat(items) {
		if (items.length === 0) {
			catLista.innerHTML = '<p style="color:#6b6a65;margin:0;font-size:12px;">Nenhum produto encontrado.</p>';
			return;
		}
		var html = '<div style="max-height:340px;overflow-y:auto;border:1px solid #f0efec;border-radius:8px;">';
		items.forEach(function (p) {
			var precoBr = 'R$ ' + Number(p.preco).toFixed(2).replace('.', ',');
			html += '<div style="padding:10px 14px;display:flex;justify-content:space-between;align-items:center;gap:12px;border-bottom:1px solid #f0efec;">';
			html += '<div style="flex:1;min-width:0;"><span style="font-family:monospace;font-size:11px;font-weight:600;color:#0F6E56;background:#E1F5EE;padding:2px 6px;border-radius:4px;">' + p.codigo + '</span> <strong style="font-size:12px;">' + p.descricao + '</strong><div style="font-size:11px;color:#6b6a65;margin-top:3px;">' + (p.unidade || '—') + ' · estoque ' + p.estoque + '</div></div>';
			html += '<div style="text-align:right;min-width:110px;"><div style="font-size:14px;font-weight:700;color:#0F6E56;">' + precoBr + '</div></div>';
			html += '</div>';
		});
		html += '</div>';
		catLista.innerHTML = html;
	}

	prodInput && prodInput.addEventListener('input', debounce(function () {
		var q = this.value.trim();
		catSpin.style.display = 'block';
		fetch(urlProd + '?q=' + encodeURIComponent(q), {credentials: 'same-origin'})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				catSpin.style.display = 'none';
				if (data.ok) renderCat(data.items);
			})
			.catch(function () { catSpin.style.display = 'none'; });
	}, 300));
})();
</script>
<?php $this->end(); ?>
